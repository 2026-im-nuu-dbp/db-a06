<?php
session_start();
require_once 'dp.php';

// 1. 權限與內容檢查 (一行搞定)
$userId = $_SESSION['user_id'] ?? exit('請先登入');
$content = trim($_POST['content'] ?? '') ?: exit('內容不可為空');
$imagePath = $thumbPath = null;

// 2. 圖片上傳與縮圖邏輯
if (!empty($_FILES['image']['tmp_name'])) {
    $info = @getimagesize($_FILES['image']['tmp_name']);
    $ext = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif'][$info['mime'] ?? ''] ?? exit('格式錯誤');
    
    if (!is_dir('uploads/thumbs')) mkdir('uploads/thumbs', 0777, true);
    
    $name = uniqid() . '.' . $ext;
    if (move_uploaded_file($_FILES['image']['tmp_name'], "uploads/$name")) {
        $imagePath = "uploads/$name";
        
        // 縮圖核心邏輯
        [$w, $h, $type] = $info;
        $ratio = min(240/$w, 240/$h, 1);
        $tw = (int)($w*$ratio); $th = (int)($h*$ratio);
        
        $src = match($type) { 1=>imagecreatefromgif($imagePath), 2=>imagecreatefromjpeg($imagePath), 3=>imagecreatefrompng($imagePath), default=>null };
        $tImg = imagecreatetruecolor($tw, $th);
        
        if ($src) {
            imagealphablending($tImg, false); imagesavealpha($tImg, true); // 保持透明
            imagecopyresampled($tImg, $src, 0,0,0,0, $tw, $th, $w, $h);
            $thumbPath = "uploads/thumbs/t_$name";
            match($type) { 1=>imagegif($tImg, $thumbPath), 2=>imagejpeg($tImg, $thumbPath, 80), 3=>imagepng($tImg, $thumbPath, 6) };
            imagedestroy($src); imagedestroy($tImg);
        }
    }
}

// 3. 資料庫存檔 (Transaction 確保安全)
try {
    $pdo->beginTransaction();
    // 手動計算 ID (符合你原始要求)
    $newId = $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 FROM dbmemo FOR UPDATE")->fetchColumn();
    
    $pdo->prepare("INSERT INTO dbmemo (id, user_id, content, image_path, thumb_path) VALUES (?,?,?,?,?)")
        ->execute([$newId, $userId, $content, $imagePath, $thumbPath]);
    
    $pdo->commit();
    header('Location: Memo.php');
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    exit('新增失敗');
}