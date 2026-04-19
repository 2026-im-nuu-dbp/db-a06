<?php
session_start(); // 啟用 session

require_once "dp.php";

header('Content-Type: application/json; charset=utf-8');

function respondJson(int $statusCode, string $message, array $data = []): void
{
    http_response_code($statusCode);
    echo json_encode([
        'success' => $statusCode >= 200 && $statusCode < 300,
        'message' => $message,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function expectsJsonResponse(): bool
{
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

    if (stripos($accept, 'application/json') !== false) {
        return true;
    }

    return strcasecmp($requestedWith, 'XMLHttpRequest') === 0;
}

function writeLoginLog(PDO $pdo, string $account, string $status): void
{
    try {
        $pdo->beginTransaction();

        $idStmt = $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM dblog FOR UPDATE");
        $newLogId = (int) $idStmt->fetchColumn();

        $logStmt = $pdo->prepare("INSERT INTO dblog (id, account, status) VALUES (?, ?, ?)");
        $logStmt->execute([$newLogId, $account, $status]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('Failed to write login log: ' . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondJson(405, '請從登入表單送出資料');
}

$username = trim($_POST['account'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    respondJson(400, '帳號或密碼不可空白');
}

$sql = "SELECT * FROM dbusers WHERE account = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$username]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    writeLoginLog($pdo, $username, '失敗');
    if (expectsJsonResponse()) {
        respondJson(404, '帳號不存在');
    }

    header('Location: BWMenuRegister.html');
    exit;
}

// 兼容舊資料（雜湊）與新資料（明文）
$storedPassword = (string) ($user['password'] ?? '');
$passwordInfo = password_get_info($storedPassword);
$isPasswordValid = false;

if (($passwordInfo['algo'] ?? null) !== null && ($passwordInfo['algo'] ?? 0) !== 0) {
    $isPasswordValid = password_verify($password, $storedPassword);
} else {
    $isPasswordValid = hash_equals($storedPassword, $password);
}

if (!$isPasswordValid) {
    writeLoginLog($pdo, $username, '失敗');
    respondJson(401, '密碼錯誤');
}

writeLoginLog($pdo, $username, '成功');

$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['account'];

if (expectsJsonResponse()) {
    respondJson(200, '登入成功', [
        'id' => (int) $user['id'],
        'account' => $user['account'],
    ]);
}

header('Location: Memo.php');
exit;
?>