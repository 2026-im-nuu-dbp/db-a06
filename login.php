<?php
session_start();

require_once "dp.php";

function writeLoginLog(PDO $pdo, string $account, string $status): void
{
    try {
        $stmt = $pdo->prepare("INSERT INTO dblog (account, login_time, status) VALUES (?, NOW(), ?)");
        $stmt->execute([$account, $status]);
        return;
    } catch (PDOException $e) {
        if (!str_contains($e->getMessage(), "Field 'id' doesn't have a default value")) {
            return;
        }
    }

    // Fallback for schema where dblog.id is not AUTO_INCREMENT.
    try {
        $nextIdStmt = $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 FROM dblog");
        $nextId = (int) ($nextIdStmt->fetchColumn() ?: 1);
        $stmt = $pdo->prepare("INSERT INTO dblog (id, account, login_time, status) VALUES (?, ?, NOW(), ?)");
        $stmt->execute([$nextId, $account, $status]);
    } catch (Throwable $e) {
        // Do not block login flow when logging fails.
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('請從登入表單送出資料');
}

$username = trim($_POST['username'] ?? ($_POST['account'] ?? ''));
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    exit('帳號或密碼不可空白');
}

$sql = "SELECT id, account, password FROM dbusers WHERE account = ? LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([$username]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    writeLoginLog($pdo, $username, '失敗');
    exit('登入失敗');
}

$storedPassword = (string) ($user['password'] ?? '');
$isPasswordValid = hash_equals($storedPassword, $password);

if ($isPasswordValid) {
    writeLoginLog($pdo, (string) ($user['account'] ?? $username), '成功');
    $_SESSION['user_id'] = (int) ($user['id'] ?? 0);
    $_SESSION['username'] = (string) ($user['account'] ?? $username);
    header('Location: Memo.php');
    exit;
}

writeLoginLog($pdo, $username, '失敗');
exit('登入失敗');
?>