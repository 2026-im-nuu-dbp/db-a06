<?php
session_start();
require_once 'dp.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$id = (int)($_POST['id'] ?? 0);
$content = trim($_POST['content'] ?? '');
$userId = $_SESSION['user_id'] ?? 0;

if (!$id || !$content || !$userId) exit;

// 找原本資料
$stmt = $pdo->prepare('SELECT image_path, thumb_path FROM dbmemo WHERE id=? AND user_id=?');
$stmt->execute([$id, $userId]);
$memo = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$memo) exit;

// 預設用舊圖片
$imagePath = $memo['image_path'];
$thumbPath = $memo['thumb_path'];


if (!empty($_FILES['image']['tmp_name'])) {

    $uploadDir = __DIR__ . '/uploads';
    if (!is_dir($uploadDir)) mkdir($uploadDir, true);

$ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION) ?: 'jpg';

$fileName = 'memo_' . time() . '.' . $ext;
$newImage = $uploadDir . '/' . $fileName;

if (move_uploaded_file($_FILES['image']['tmp_name'], $newImage)) {
    $imagePath = 'uploads/' . $fileName;
}

}

// 更新資料
$stmt = $pdo->prepare('UPDATE dbmemo SET content=?, image_path=?, thumb_path=? WHERE id=? AND user_id=?');
$stmt->execute([$content, $imagePath, $thumbPath, $id, $userId]);

header('Location: Memo.php');
exit;