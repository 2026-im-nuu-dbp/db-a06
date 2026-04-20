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
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>備忘錄</title>
    <link rel="stylesheet" href="recipe-login.css">
</head>
<body>

<div class="container">

    <h1>我的備忘錄</h1>
    <p class="note">登入帳號：<?= h((string) $username) ?></p>
    <p><a href="log.php">查看登入紀錄</a></p>

    <form action="addMemo.php" method="post" enctype="multipart/form-data">

        <label>備忘內容</label>
        <textarea name="content" rows="4" required></textarea>

        <label>上傳圖片</label>
        <input type="file" name="image" accept="image/jpeg,image/png,image/gif">

        <button class="submit-btn" type="submit">新增備忘</button>

    </form>

    <hr>

    <div class="memo-list">
        <?php if (!$memos): ?>
            <p class="note">目前還沒有備忘，先新增第一筆吧！</p>
        <?php endif; ?>

        <?php foreach ($memos as $memo): ?>
            <div class="memo-card">
                <p><?= nl2br(h((string) ($memo['content'] ?? ''))) ?></p>

                <?php
                $displayImage = (string) (($memo['thumb_path'] ?? '') !== '' ? $memo['thumb_path'] : ($memo['image_path'] ?? ''));
                ?>
                <?php if ($displayImage !== ''): ?>
                    <img src="<?= h($displayImage) ?>" alt="備忘圖片">
                <?php endif; ?>

                <p class="note">建立時間：<?= h((string) ($memo['created_at'] ?? '')) ?></p>

                <form action="update.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?= (int) ($memo['id'] ?? 0) ?>">
                    <textarea name="content" rows="3" required><?= h((string) ($memo['content'] ?? '')) ?></textarea>
                    <label>重新上傳圖片（可選）</label>
                    <input type="file" name="image" accept="image/jpeg,image/png,image/gif">
                    <button class="submit-btn" type="submit">修改</button>
                </form>

                <form action="delete.php" method="post">
                    <input type="hidden" name="id" value="<?= (int) ($memo['id'] ?? 0) ?>">
                    <button class="submit-btn" type="submit">刪除</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

</div>

</body>
</html>