<?php
session_start();
require_once 'dp.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;


$id = (int)($_POST['id'] ?? 0);
$content = trim($_POST['content'] ?? '');
$userId = $_SESSION['user_id'] ?? 0;


if (!$id || !$content || !$userId) exit;


$stmt = $pdo->prepare('SELECT image_path, thumb_path FROM dbmemo WHERE id=? AND user_id=?');
$stmt->execute([$id, $userId]);
$memo = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$memo) exit;


$imagePath = $memo['image_path'];
$thumbPath = $memo['thumb_path'];



if (!empty($_FILES['image']['tmp_name'])) {

  
    $uploadDir = __DIR__ . '/uploads';
    $thumbDir = $uploadDir . '/thumbs';

    
    if (!is_dir($uploadDir)) mkdir($uploadDir, true);
    if (!is_dir($thumbDir)) mkdir($thumbDir, true);

    
    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION) ?: 'jpg';
    $time = time();
    $fileName = "memo_$time.$ext";
    $thumbName = "thumb_$time.$ext";

    
    $newImage = $uploadDir . '/' . $fileName;
    move_uploaded_file($_FILES['image']['tmp_name'], $newImage);
    $imagePath = 'uploads/' . $fileName;

    
    if (createThumbnail($newImage, $thumbDir . '/' . $thumbName)) {
        $thumbPath = 'uploads/thumbs/' . $thumbName;
    }
}


$stmt = $pdo->prepare('UPDATE dbmemo SET content=?, image_path=?, thumb_path=? WHERE id=? AND user_id=?');
$stmt->execute([$content, $imagePath, $thumbPath, $id, $userId]);


header('Location: Memo.php');
exit;