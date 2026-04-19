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

$logStmt = $pdo->prepare("INSERT INTO dblog (account, status) VALUES (?, ?)");

if (!$user) {
    $logStmt->execute([$username, '失敗']);
    respondJson(404, '帳號不存在');
}

if (!password_verify($password, $user['password'])) {
    $logStmt->execute([$username, '失敗']);
    respondJson(401, '密碼錯誤');
}

$logStmt->execute([$username, '成功']);

$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['account'];

respondJson(200, '登入成功', [
    'id' => (int) $user['id'],
    'account' => $user['account'],
]);
?>