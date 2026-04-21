<?php

require_once 'dp.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit;
}

$account = trim($_POST['username'] ?? '');
$nickname = trim($_POST['nickname'] ?? '');
$password = $_POST['password'] ?? '';
$gender = trim($_POST['gender'] ?? '');
$hobby = $_POST['hobby'] ?? [];

if ($account === '' || $nickname === '' || $password === '') {
    exit('帳號、暱稱、密碼不可空白');
}

if (!in_array($gender, ['男', '女', '其他'], true)) {
    $gender = null;
}

if (!is_array($hobby)) {
    $hobby = [];
}

$interests = implode(',', array_map('trim', $hobby));
$storedPassword = $password;

try {
    $insertSql = "INSERT INTO dbusers (account, nickname, password, gender, interests, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
    $insertStmt = $pdo->prepare($insertSql);
    $insertStmt->execute([$account, $nickname, $storedPassword, $gender, $interests]);
} catch (Throwable $e) {
    exit('註冊失敗');
}

header('Location: BWMenu.html');
exit;
