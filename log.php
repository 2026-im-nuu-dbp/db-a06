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

$rowsHtml = '';

if (!$logs) {
    $rowsHtml = "<tr><td colspan=\"3\">目前沒有登入紀錄</td></tr>";
} else {
    foreach ($logs as $log) {
        $rowsHtml .= '<tr>'
            . '<td>' . h((string) ($log['account'] ?? '')) . '</td>'
            . '<td>' . h((string) ($log['login_time'] ?? '')) . '</td>'
            . '<td>' . h((string) ($log['status'] ?? '')) . '</td>'
            . '</tr>';
    }
}

$templatePath = __DIR__ . DIRECTORY_SEPARATOR . 'log.html';

if (!is_file($templatePath)) {
    http_response_code(500);
    echo 'Template file log.html not found.';
    exit;
}

$template = file_get_contents($templatePath);

if ($template === false) {
    http_response_code(500);
    echo 'Unable to load template.';
    exit;
}

echo str_replace('{{TABLE_ROWS}}', $rowsHtml, $template);