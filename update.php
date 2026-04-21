<?php
session_start();

require_once 'dp.php';

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

$userId = $_SESSION['user_id'] ?? null;
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

$findSql = 'SELECT image_path, thumb_path FROM dbmemo WHERE id = ? AND user_id = ? LIMIT 1';
$findStmt = $pdo->prepare($findSql);
$findStmt->execute([$id, (int) $userId]);
$memo = $findStmt->fetch(PDO::FETCH_ASSOC);

if (!$memo) {
    http_response_code(404);
    exit('找不到備忘資料');
}

$nextImagePath = (string) ($memo['image_path'] ?? '');
$nextThumbPath = (string) ($memo['thumb_path'] ?? '');
$newImageAbsPath = '';
$newThumbAbsPath = '';

if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        exit('圖片上傳失敗');
    }

    $tmpName = $_FILES['image']['tmp_name'];
    $originalName = (string) ($_FILES['image']['name'] ?? '');
    $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
    if ($extension === '') {
        $extension = 'dat';
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

    $fileName = buildUploadFileName('memo', (int) $userId, $originalName, $extension, $uploadDir);
    $newImageAbsPath = $uploadDir . '/' . $fileName;
    $nextImagePath = 'uploads/' . $fileName;

    if (!move_uploaded_file($tmpName, $newImageAbsPath)) {
        http_response_code(500);
        exit('儲存圖片失敗');
    }

    $thumbFileName = buildUploadFileName('thumb', (int) $userId, $originalName, $extension, $thumbDir);
    $newThumbAbsPath = $thumbDir . '/' . $thumbFileName;

    if (createThumbnail($newImageAbsPath, $newThumbAbsPath)) {
        $nextThumbPath = 'uploads/thumbs/' . $thumbFileName;
    } else {
        $newThumbAbsPath = '';
        $nextThumbPath = '';
    }
}

try {
    $sql = 'UPDATE dbmemo SET content = ?, image_path = ?, thumb_path = ? WHERE id = ? AND user_id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$content, $nextImagePath, $nextThumbPath, $id, (int) $userId]);

    if ($newImageAbsPath !== '' && $newThumbAbsPath !== '') {
        $oldImageRelPath = (string) ($memo['image_path'] ?? '');
        $oldThumbRelPath = (string) ($memo['thumb_path'] ?? '');
        $oldImageAbsPath = __DIR__ . '/' . str_replace('..', '', $oldImageRelPath);
        $oldThumbAbsPath = __DIR__ . '/' . str_replace('..', '', $oldThumbRelPath);

        if ($oldImageRelPath !== '' && is_file($oldImageAbsPath)) {
            unlink($oldImageAbsPath);
        }
        if ($oldThumbRelPath !== '' && is_file($oldThumbAbsPath)) {
            unlink($oldThumbAbsPath);
        }
    }

    header('Location: Memo.php');
    exit;
} catch (PDOException $e) {
    if ($newImageAbsPath !== '' && is_file($newImageAbsPath)) {
        unlink($newImageAbsPath);
    }
    if ($newThumbAbsPath !== '' && is_file($newThumbAbsPath)) {
        unlink($newThumbAbsPath);
    }
    http_response_code(500);
    exit('修改備忘失敗');
}
