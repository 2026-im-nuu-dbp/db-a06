<?php

require_once 'dp.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('請從註冊表單送出資料');
}

$account = trim($_POST['username'] ?? '');
$nickname = trim($_POST['nickname'] ?? '');
$password = $_POST['password'] ?? '';
$gender = trim($_POST['gender'] ?? '');
$hobby = $_POST['hobby'] ?? [];

if ($account === '' || $nickname === '' || $password === '') {
    http_response_code(400);
    exit('帳號、暱稱、密碼不可空白');
}

if (!in_array($gender, ['男', '女', '其他'], true)) {
    $gender = null;
}

if (!is_array($hobby)) {
    $hobby = [];
}

$interests = implode(',', array_map('trim', $hobby));
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$checkSql = "SELECT id FROM dbusers WHERE account = ? LIMIT 1";
$checkStmt = $pdo->prepare($checkSql);
$checkStmt->execute([$account]);

if ($checkStmt->fetch()) {
    http_response_code(409);
    exit('帳號已存在');
}

$insertSql = "INSERT INTO dbusers (account, nickname, password, gender, interests) VALUES (?, ?, ?, ?, ?)";
$insertStmt = $pdo->prepare($insertSql);
$insertStmt->execute([$account, $nickname, $hashedPassword, $gender, $interests]);

echo '註冊成功';
