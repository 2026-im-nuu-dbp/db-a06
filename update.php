<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$content = trim($_POST['content'] ?? '');

if ($id <= 0 || $content === '') {
    http_response_code(400);
    exit('參數錯誤');
}

$host = 'localhost';
$db = 'a06';
$user = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $sql = 'UPDATE dbmemo SET content = ? WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$content, $id]);

    header('Location: Memo.html');
    exit;
} catch (PDOException $e) {
    http_response_code(500);
    exit('修改備忘失敗');
}
