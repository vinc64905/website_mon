<?php
// Підключає файл із налаштуваннями
require_once 'config.php';

// Функція для створення з’єднання з базою даних
function getDbConnection() {
    // Створює нове з’єднання з базою даних
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Перевіряє, чи є помилка підключення
    if ($conn->connect_error) {
        // Зупиняє виконання, якщо підключення не вдалося
        die("Помилка підключення: " . $conn->connect_error);
    }
    
    // Встановлює кодування для коректної роботи з українськими символами
    $conn->set_charset("utf8mb4");
    // Повертає з’єднання
    return $conn;
}
?>