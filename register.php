<?php

require_once 'dp.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: BWMenuRegister.html');
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
    // Work around schema without AUTO_INCREMENT on id.
    $nextIdSql = "SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM dbusers";
    $nextIdStmt = $pdo->query($nextIdSql);
    $nextId = (int) ($nextIdStmt->fetchColumn() ?: 1);

    $insertSql = "INSERT INTO dbusers (id, account, nickname, password, gender, interests, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $insertStmt = $pdo->prepare($insertSql);
    $insertStmt->execute([$nextId, $account, $nickname, $storedPassword, $gender, $interests]);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        exit('註冊失敗：帳號已存在');
    }
    if (str_contains($e->getMessage(), "Field 'id' doesn't have a default value")) {
        exit('註冊失敗：dbusers.id 需設定 AUTO_INCREMENT，或先修正資料表結構');
    }
    exit('註冊失敗：' . $e->getMessage());
} catch (Throwable $e) {
    exit('註冊失敗：' . $e->getMessage());
}

header('Location: BWMenu.html');
exit;
