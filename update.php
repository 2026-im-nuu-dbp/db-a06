<?php
session_start();
require_once 'dp.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$id = $_POST['id'];
$content = $_POST['content'];
$userId = $_SESSION['user_id'];

// 先抓舊圖片
$stmt = $pdo->prepare("SELECT image_path FROM dbmemo WHERE id=? AND user_id=?");
$stmt->execute([$id, $userId]);
$row = $stmt->fetch();

$imagePath = $row['image_path'];


if (!empty($_FILES['image']['tmp_name'])) {

    $fileName = 'memo_' . time() . '.jpg';
    $savePath = __DIR__ . '/uploads/' . $fileName;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $savePath)) {
        $imagePath = 'uploads/' . $fileName; // 更新路徑
    }
}

// 更新資料
$stmt = $pdo->prepare("UPDATE dbmemo SET content=?, image_path=? WHERE id=? AND user_id=?");
$stmt->execute([$content, $imagePath, $id, $userId]);

header('Location: Memo.php');
exit;