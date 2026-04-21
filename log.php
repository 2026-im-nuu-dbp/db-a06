<?php
session_start();

require_once 'dp.php';

$username = $_SESSION['username'] ?? '';

if ($username === '') {
    header('Location: BWMenu.html');
    exit;
}

$logs = [];

try {
    $stmt = $pdo->prepare('SELECT account, login_time, status FROM dblog WHERE account = ? ORDER BY login_time DESC, id DESC');
    $stmt->execute([(string) $username]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $logs = [];
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
    <title>登入紀錄</title>
    <link rel="stylesheet" href="recipe-login.css">
</head>
<body>
<div class="container">
    <h1>登入紀錄</h1>
    <p><a href="Memo.php">回到備忘錄</a></p>

    <table class="log-table">
        <thead>
            <tr>
                <th>帳號</th>
                <th>時間</th>
                <th>結果</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$logs): ?>
            <tr><td colspan="3">目前沒有登入紀錄</td></tr>
        <?php else: ?>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= h((string) ($log['account'] ?? '')) ?></td>
                    <td><?= h((string) ($log['login_time'] ?? '')) ?></td>
                    <td><?= h((string) ($log['status'] ?? '')) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>