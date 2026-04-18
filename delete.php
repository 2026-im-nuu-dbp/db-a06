<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($id <= 0) {
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

    $findSql = 'SELECT image_path, thumb_path FROM dbmemo WHERE id = ? LIMIT 1';
    $findStmt = $pdo->prepare($findSql);
    $findStmt->execute([$id]);
    $memo = $findStmt->fetch(PDO::FETCH_ASSOC);

    $deleteSql = 'DELETE FROM dbmemo WHERE id = ?';
    $deleteStmt = $pdo->prepare($deleteSql);
    $deleteStmt->execute([$id]);

    if ($memo) {
        $paths = [$memo['image_path'] ?? '', $memo['thumb_path'] ?? ''];
        foreach ($paths as $relPath) {
            if ($relPath === '') {
                continue;
            }

            $absPath = __DIR__ . '/' . str_replace('..', '', $relPath);
            if (is_file($absPath)) {
                unlink($absPath);
            }
        }
    }

    header('Location: 備忘錄.html');
    exit;
} catch (PDOException $e) {
    http_response_code(500);
    exit('刪除備忘失敗');
}
