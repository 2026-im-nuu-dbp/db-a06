<?php
session_start(); // 啟用 session

require_once "dp.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('請從登入表單送出資料');
}

$username = trim($_POST['account'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    http_response_code(400);
    exit('帳號或密碼不可空白');
}

$sql = "SELECT * FROM dbusers WHERE account = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$username]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

$logStmt = $pdo->prepare("INSERT INTO dblog (account, status) VALUES (?, ?)");

if (!$user) {
    $logStmt->execute([$username, '失敗']);
    echo "帳號不存在";
    exit;
}

if (!password_verify($password, $user['password'])) {
    $logStmt->execute([$username, '失敗']);
    echo "密碼錯誤";
    exit;
}

$logStmt->execute([$username, '成功']);

$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['account'];

echo "登入成功";
?>