<?php

require_once 'db.php';

$id = $_GET['id'];
$username = $id;
$password = $_GET['password'];
$gender = $_GET['gender'];
$nickname = $_GET['nickname'];
$hobby = $_GET['hobby'];

$sql = "SELECT * FROM users WHERE username = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$username]);

if ($stmt->rowCount() > 0) {
    echo "Username already exists.";
    exit;
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$sql = "INSERT INTO users (username, password) VALUES (?, ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$username, $hashedPassword]);

echo "註冊成功";
?>