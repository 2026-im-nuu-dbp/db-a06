<?php
session_start();

require_once 'dp.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    exit('參數錯誤');
}

$userId = $_SESSION['user_id'] ?? null;
if ($userId === null) {
    http_response_code(401);
    exit('請先登入');
}

try {
    $findSql = 'SELECT image_path, thumb_path FROM dbmemo WHERE id = ? AND user_id = ? LIMIT 1';
    $findStmt = $pdo->prepare($findSql);
    $findStmt->execute([$id, (int) $userId]);
    $memo = $findStmt->fetch(PDO::FETCH_ASSOC);

    $deleteSql = 'DELETE FROM dbmemo WHERE id = ? AND user_id = ?';
    $deleteStmt = $pdo->prepare($deleteSql);
    $deleteStmt->execute([$id, (int) $userId]);

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

    header('Location: Memo.php');
    exit;
} catch (PDOException $e) {
    http_response_code(500);
    exit('刪除備忘失敗');
}
