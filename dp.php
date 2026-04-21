<?php

$host = "localhost";
$dbCandidates = ['db-a06', 'a06'];
$user = 'root';
$password = '';

$lastError = null;

foreach ($dbCandidates as $db) {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return;
    } catch (PDOException $e) {
        $lastError = $e;
    }
}

if ($lastError instanceof PDOException) {
    die("連接失敗: " . $lastError->getMessage());
}
