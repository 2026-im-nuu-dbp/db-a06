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
