<?php
require_once "dp.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('請從登入表單送出資料');
}

$username = trim($_POST['username'] ?? ($_POST['account'] ?? ''));
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    exit('帳號或密碼不可空白');
}

$sql = "SELECT password FROM dbusers WHERE account = ? LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([$username]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    exit('登入失敗');
}

$storedPassword = (string) ($user['password'] ?? '');
$isPasswordValid = hash_equals($storedPassword, $password);

if ($isPasswordValid) {
    exit('登入成功');
}

exit('登入失敗');
?>