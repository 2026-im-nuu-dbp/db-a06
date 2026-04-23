<?php
session_start();
require_once 'dp.php';

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    header('Location: BWMenu.html');
    exit;
}

// 抓資料
$stmt = $pdo->prepare('SELECT id, content, image_path FROM dbmemo WHERE user_id=? ORDER BY id DESC');
$stmt->execute([$userId]);
$memos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 防 XSS
function h($v) {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

$cardsHtml = '';

foreach ($memos as $m) {

    $img = $m['image_path'] ?? '';

    $cardsHtml .= '
    <div class="memo-card">

        <p>' . nl2br(h($m['content'])) . '</p>' .

        ($img ? '<img src="/forms/db-a06/' . h($img) . '" style="max-width:200px;">' : '') . '

        <form action="update.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="id" value="' . $m['id'] . '">
            <textarea name="content">' . h($m['content']) . '</textarea>
            <input type="file" name="image">
            <button>修改</button>
        </form>

        <form action="delete.php" method="post">
            <input type="hidden" name="id" value="' . $m['id'] . '">
            <button>刪除</button>
        </form>

    </div>';
}

// 套模板
$template = file_get_contents('Memo.html');
echo preg_replace('/<div class="memo-card">[\s\S]*?<\/div>/', $cardsHtml, $template, 1);