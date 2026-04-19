<?php

require_once 'dp.php';

function respondJson(int $statusCode, string $message, array $data = []): void
{
    header('Content-Type: application/json; charset=utf-8');
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondJson(405, '請從註冊表單送出資料');
}

$account = trim($_POST['username'] ?? '');
$nickname = trim($_POST['nickname'] ?? '');
$password = $_POST['password'] ?? '';
$gender = trim($_POST['gender'] ?? '');
$hobby = $_POST['hobby'] ?? [];

if ($account === '' || $nickname === '' || $password === '') {
    respondJson(400, '帳號、暱稱、密碼不可空白');
}

if (!in_array($gender, ['男', '女', '其他'], true)) {
    $gender = null;
}

if (!is_array($hobby)) {
    $hobby = [];
}

$interests = implode(',', array_map('trim', $hobby));
$storedPassword = $password;

$checkSql = "SELECT id FROM dbusers WHERE account = ? LIMIT 1";
$checkStmt = $pdo->prepare($checkSql);
$checkStmt->execute([$account]);

if ($checkStmt->fetch()) {
    respondJson(409, '帳號已存在');
}

$pdo->beginTransaction();

try {
    $idStmt = $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM dbusers FOR UPDATE");
    $newUserId = (int) $idStmt->fetchColumn();

    $insertSql = "INSERT INTO dbusers (id, account, nickname, password, gender, interests, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $insertStmt = $pdo->prepare($insertSql);
    $insertStmt->execute([$newUserId, $account, $nickname, $storedPassword, $gender, $interests]);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    respondJson(500, '註冊失敗，請稍後再試');
}

$timeStmt = $pdo->prepare("SELECT created_at FROM dbusers WHERE id = ? LIMIT 1");
$timeStmt->execute([$newUserId]);
$createdAt = $timeStmt->fetchColumn();

if (expectsJsonResponse()) {
    respondJson(201, '註冊成功', [
        'id' => $newUserId,
        'account' => $account,
        'created_at' => $createdAt,
    ]);
}

header('Location: BWMenu.html');
exit;
