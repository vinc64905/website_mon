<?php
// Визначає адресу сервера бази даних
define('DB_HOST', 'localhost:3307'); // Включає порт
// Визначає ім’я користувача бази даних
define('DB_USER', 'root'); // Потрібно оновити для вашої системи
// Визначає пароль для бази даних
define('DB_PASS', '');     // Потрібно оновити для вашої системи
// Визначає назву бази даних
define('DB_NAME', 'urban_issues');
// Визначає папку для зберігання завантажених файлів
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
// Визначає URL сайту
define('SITE_URL', 'http://localhost/urban_issues/'); // Потрібно оновити для вашого сайту
// Визначає email для відправки листів
define('EMAIL_FROM', 'no-reply@urbanissues.ua');
?>