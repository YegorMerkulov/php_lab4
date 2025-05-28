<?php
session_start();
$host = 'MySQL-8.4';
$db   = 'php_Merkulov';
$user = 'root';
$pass = '';

$dsn = "mysql:host=$host;dbname=$db";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}

// Функция проверки авторизации
function checkAuth($requiredRole = null) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
    if ($requiredRole && $_SESSION['role'] !== $requiredRole) {
        die("Доступ запрещен");
    }
}
?>