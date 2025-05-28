<?php
require_once 'config.php'; // Подключаем настройки сессии

// Уничтожаем все данные сессии
session_unset(); // Удаляем все переменные сессии
session_destroy(); // Уничтожаем сессию

// Удаляем cookies с настройками (если они есть)
setcookie('background_color', '', time() - 3600, '/');
setcookie('text_color', '', time() - 3600, '/');

// Перенаправляем на страницу входа
header("Location: login.php");
exit;
?>