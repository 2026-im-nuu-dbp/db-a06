<?php
session_start();

require_once 'dp.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$content = trim($_POST['content'] ?? '');
if ($content === '') {
    http_response_code(400);
    exit('備忘內容不可為空');
}

$userId = $_SESSION['user_id'] ?? null;
$imagePath = null;
$thumbPath = null;

if ($userId === null) {
    http_response_code(401);
    exit('請先登入');
}

function createThumbnail(string $sourcePath, string $targetPath, int $maxWidth = 240, int $maxHeight = 240): bool
{
    $imageInfo = @getimagesize($sourcePath);
    if ($imageInfo === false) {
        return false;
    }

    [$width, $height, $type] = $imageInfo;
    if ($width <= 0 || $height <= 0) {
        return false;
    }

    if (!function_exists('imagecreatetruecolor')) {
        return false;
    }

    switch ($type) {
        case IMAGETYPE_JPEG:
            if (!function_exists('imagecreatefromjpeg')) {
                return false;
            }
            $source = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            if (!function_exists('imagecreatefrompng')) {
                return false;
            }
            $source = imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_GIF:
            if (!function_exists('imagecreatefromgif')) {
                return false;
            }
            $source = imagecreatefromgif($sourcePath);
            break;
        default:
            return false;
    }

    if (!$source) {
        return false;
    }

    $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
    $newWidth = (int) floor($width * $ratio);
    $newHeight = (int) floor($height * $ratio);

    $thumb = imagecreatetruecolor($newWidth, $newHeight);
    if (!$thumb) {
        imagedestroy($source);
        return false;
    }

    if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
        imagecolortransparent($thumb, imagecolorallocatealpha($thumb, 0, 0, 0, 127));
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
    }

    imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    $saved = false;
    if ($type === IMAGETYPE_JPEG) {
        $saved = imagejpeg($thumb, $targetPath, 85);
    } elseif ($type === IMAGETYPE_PNG) {
        $saved = imagepng($thumb, $targetPath, 6);
    } elseif ($type === IMAGETYPE_GIF) {
        $saved = imagegif($thumb, $targetPath);
    }

    imagedestroy($source);
    imagedestroy($thumb);

    return $saved;
}

function buildUploadFileName(string $prefix, int $userId, string $originalName, string $extension, string $targetDir): string
{
    $baseName = pathinfo($originalName, PATHINFO_FILENAME);
    $baseName = preg_replace('/[^A-Za-z0-9_-]+/', '_', $baseName ?? '');
    $baseName = trim((string) $baseName, '_');
    if ($baseName === '') {
        $baseName = 'image';
    }

    $timePart = date('Ymd_His');
    $candidate = sprintf('%s_u%d_%s_%s.%s', $prefix, $userId, $timePart, $baseName, $extension);
    $counter = 1;

    while (is_file($targetDir . '/' . $candidate)) {
        $candidate = sprintf('%s_u%d_%s_%s_%d.%s', $prefix, $userId, $timePart, $baseName, $counter, $extension);
        $counter++;
    }

    return $candidate;
}

if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        exit('圖片上傳失敗');
    }

    $tmpName = $_FILES['image']['tmp_name'];
    $imageInfo = @getimagesize($tmpName);
    if ($imageInfo === false) {
        http_response_code(400);
        exit('請上傳圖片檔案');
    }

    $mime = $imageInfo['mime'] ?? '';
    $extMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif'
    ];

    if (!isset($extMap[$mime])) {
        http_response_code(400);
        exit('僅支援 JPG、PNG、GIF');
    }

    $uploadDir = __DIR__ . '/uploads';
    $thumbDir = $uploadDir . '/thumbs';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
        http_response_code(500);
        exit('建立上傳資料夾失敗');
    }
    if (!is_dir($thumbDir) && !mkdir($thumbDir, 0777, true)) {
        http_response_code(500);
        exit('建立縮圖資料夾失敗');
    }

    $originalName = $_FILES['image']['name'] ?? '';
    $fileName = buildUploadFileName('memo', (int) $userId, (string) $originalName, $extMap[$mime], $uploadDir);
    $targetAbsPath = $uploadDir . '/' . $fileName;
    $targetRelPath = 'uploads/' . $fileName;

    if (!move_uploaded_file($tmpName, $targetAbsPath)) {
        http_response_code(500);
        exit('儲存圖片失敗');
    }

    $thumbFileName = buildUploadFileName('thumb', (int) $userId, (string) $originalName, $extMap[$mime], $thumbDir);
    $thumbAbsPath = $thumbDir . '/' . $thumbFileName;
    $thumbRelPath = 'uploads/thumbs/' . $thumbFileName;

    if (!createThumbnail($targetAbsPath, $thumbAbsPath)) {
        if (is_file($targetAbsPath)) {
            unlink($targetAbsPath);
        }
        http_response_code(500);
        exit('建立縮圖失敗');
    }

    $imagePath = $targetRelPath;
    $thumbPath = $thumbRelPath;
}

try {
    $pdo->beginTransaction();

    $idStmt = $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM dbmemo FOR UPDATE");
    $newMemoId = (int) $idStmt->fetchColumn();

    $sql = 'INSERT INTO dbmemo (id, user_id, content, image_path, thumb_path) VALUES (?, ?, ?, ?, ?)';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$newMemoId, $userId, $content, $imagePath, $thumbPath]);

    $pdo->commit();

    header('Location: Memo.php');
    exit;
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    exit('新增備忘失敗');
}
