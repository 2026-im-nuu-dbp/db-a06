<?php
session_start();

require_once 'dp.php';

$userId = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? '';

if ($userId === null) {
    header('Location: BWMenu.html');
    exit;
}

$memos = [];

try {
    $stmt = $pdo->prepare('SELECT id, content, image_path, thumb_path, created_at FROM dbmemo WHERE user_id = ? ORDER BY created_at DESC, id DESC');
    $stmt->execute([(int) $userId]);
    $memos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $memos = [];
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$cardsHtml = '';

if (!$memos) {
    $cardsHtml = '<div class="memo-card"><p>目前還沒有備忘，先新增第一筆吧！</p></div>';
} else {
    foreach ($memos as $memo) {
        $memoId = (int) ($memo['id'] ?? 0);
        $content = (string) ($memo['content'] ?? '');
        $displayImage = (string) (($memo['thumb_path'] ?? '') !== '' ? $memo['thumb_path'] : ($memo['image_path'] ?? ''));

        $cardsHtml .= '<div class="memo-card">';
        $cardsHtml .= '<p>' . nl2br(h($content)) . '</p>';

        if ($displayImage !== '') {
            $cardsHtml .= '<img src="' . h($displayImage) . '" alt="備忘圖片">';
        }

        $cardsHtml .= '<form action="update.php" method="post" enctype="multipart/form-data">';
        $cardsHtml .= '<input type="hidden" name="id" value="' . $memoId . '">';
        $cardsHtml .= '<textarea name="content">' . h($content) . '</textarea>';
        $cardsHtml .= '<button class="submit-btn">修改</button>';
        $cardsHtml .= '</form>';

        $cardsHtml .= '<form action="delete.php" method="post">';
        $cardsHtml .= '<input type="hidden" name="id" value="' . $memoId . '">';
        $cardsHtml .= '<button class="submit-btn">刪除</button>';
        $cardsHtml .= '</form>';

        $cardsHtml .= '</div>';
    }
}

$templatePath = __DIR__ . DIRECTORY_SEPARATOR . 'Memo.html';

if (!is_file($templatePath)) {
    http_response_code(500);
    echo 'Template file Memo.html not found.';
    exit;
}

$template = file_get_contents($templatePath);

if ($template === false) {
    http_response_code(500);
    echo 'Unable to load template.';
    exit;
}

$pattern = '/<div class="memo-card">[\s\S]*?<\/div>/';
$result = preg_replace($pattern, $cardsHtml, $template, 1);

if ($result === null) {
    http_response_code(500);
    echo 'Failed to render template.';
    exit;
}

echo $result;