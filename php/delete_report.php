<?php
// Імпорт конфігураційного файлу
require_once '../includes/config.php';
// Підключення налаштувань системи
require_once '../includes/db.php';
// Підключення модуля для роботи з базою даних
require_once '../includes/auth.php';
// Підключення модуля для перевірки авторизації

// Перевірка авторизації
if (!isLoggedIn()) {
    // Перевірка, чи користувач авторизований
    error_log("User not logged in");
    // Логування: користувач не авторизований
    header("Location: login.php");
    // Перенаправлення на сторінку входу
    exit;
    // Завершення виконання
}

// Перевірка ролі адміністратора
if (!isAdmin()) {
    // Перевірка, чи користувач є адміністратором
    error_log("User is not admin. Role: " . (isset($_SESSION['role']) ? $_SESSION['role'] : 'not set'));
    // Логування: користувач не адміністратор
    header("Location: login.php");
    // Перенаправлення на сторінку входу
    exit;
    // Завершення виконання
}

// Ініціалізація з'єднання з базою даних
$conn = getDbConnection();
// Створення з’єднання з базою даних

// Перевірка наявності та коректності ID звіту
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Перевірка, чи передано ID звіту та чи є воно числом
    error_log("Invalid report ID");
    // Логування: невірний ID
    header("Location: admin.php");
    // Перенаправлення до адмін-панелі
    exit;
    // Завершення виконання
}

$report_id = (int)$_GET['id'];
// Перетворення ID звіту в ціле число

// Перевірка існування звіту
$sql = "SELECT photo FROM reports WHERE id = $report_id";
$result = $conn->query($sql);
// Запит до бази для пошуку звіту за ID

if ($result->num_rows === 0) {
    // Перевірка, чи знайдено звіт
    error_log("Report not found: ID $report_id");
    // Логування: звіт не знайдено
    header("Location: admin.php");
    // Перенаправлення до адмін-панелі
    exit;
    // Завершення виконання
}

// Видалення фото, якщо воно існує
$row = $result->fetch_assoc();
// Отримання даних звіту
if ($row['photo']) {
    // Перевірка, чи є фото у звіті
    $photo_path = '../' . $row['photo'];
    // Формування шляху до файлу фото
    if (file_exists($photo_path)) {
        // Перевірка існування файлу
        if (unlink($photo_path)) {
            // Видалення файлу
            error_log("Photo deleted: $photo_path");
            // Логування: фото видалено
        } else {
            error_log("Failed to delete photo: $photo_path");
            // Логування: помилка видалення фото
        }
    } else {
        error_log("Photo file not found: $photo_path");
        // Логування: файл не знайдено
    }
}

// Видалення звіту з бази даних
$sql = "DELETE FROM reports WHERE id = $report_id";
if ($conn->query($sql) === TRUE) {
    // Виконання запиту на видалення звіту
    error_log("Report deleted: ID $report_id");
    // Логування: звіт видалено
} else {
    error_log("Failed to delete report: " . $conn->error);
    // Логування: помилка видалення
}

// Завершення з'єднання з базою
$conn->close();
// Закриття з’єднання з базою даних

// Перенаправлення до адмін-панелі
header("Location: admin.php");
// Перенаправлення до адмін-панелі
exit;
// Завершення виконання
?>