<?php
require_once '../includes/config.php'; // Підключення конфігураційного файлу
require_once '../includes/db.php'; // Підключення до бази даних
require_once '../includes/auth.php'; // Підключення функцій авторизації

header('Content-Type: application/json'); // Встановлення формату відповіді JSON

$conn = getDbConnection(); // Отримання з’єднання з базою даних

// Отримання фільтрів
$region = isset($_GET['region']) ? $conn->real_escape_string($_GET['region']) : ''; // Фільтр за регіоном
$type = isset($_GET['type']) ? $conn->real_escape_string($_GET['type']) : ''; // Фільтр за типом
$status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : ''; // Фільтр за статусом

// Формування SQL-запиту
$sql = "SELECT DISTINCT * FROM reports WHERE latitude BETWEEN 44 AND 53 AND longitude BETWEEN 22 AND 40"; // Базовий запит для звітів у межах координат
if ($region) {
    $sql .= " AND region = '$region'"; // Умова за регіоном
}
if ($type) {
    $sql .= " AND type = '$type'"; // Умова за типом
}
if ($status) {
    $sql .= " AND status = '$status'"; // Умова за статусом
}

$result = $conn->query($sql); // Виконання запиту
if ($result === false) { // Перевірка на помилку
    error_log("SQL Error: " . $conn->error); // Логування помилки
    echo json_encode([
        'success' => false,
        'message' => 'Помилка при отриманні звітів.'
    ], JSON_UNESCAPED_UNICODE); // Повідомлення про помилку
    exit;
}
$reports = []; // Масив для зберігання звітів
while ($row = $result->fetch_assoc()) {
    $reports[] = $row; // Додавання звіту до масиву
}

// Масив для зіставлення типів проблем
$types = [
    'pothole' => 'Вибоїна',
    'trash' => 'Сміття',
    'light' => 'Освітлення',
    'sign' => 'Дорожній знак',
    'other' => 'Інше'
]; // Переклад типів звітів

// Масив для зіставлення статусів
$statuses = [
    'new' => 'Нова',
    'in_progress' => 'В обробці',
    'resolved' => 'Вирішена'
]; // Переклад статусів звітів

// Масив для іконок типів
$icons = [
    'pothole' => '<i class="fas fa-road" style="color: #d32f2f;"></i>',
    'trash' => '<i class="fas fa-trash" style="color: #4caf50;"></i>',
    'light' => '<i class="fas fa-lightbulb" style="color: #ff9800;"></i>',
    'sign' => '<i class="fas fa-sign" style="color: #2196f3;"></i>',
    'other' => '<i class="fas fa-question-circle" style="color: #9e9e9e;"></i>'
]; // Іконки для типів звітів

// Генерація HTML для сітки звітів
ob_start(); // Початок буферизації виведення
if (empty($reports)) {
    echo '<p class="filter-message">Немає звітів за вибраними фільтрами.</p>'; // Повідомлення про відсутність звітів
} else {
    echo '<div class="reports-grid">';
    foreach ($reports as $report) {
        echo '<div class="report-card" data-report-id="' . htmlspecialchars($report['id']) . '">'; // Картка звіту
        echo '<div class="report-icon">' . ($icons[$report['type']] ?? $icons['other']) . '</div>'; // Іконка типу
        echo '<h4>' . htmlspecialchars($types[$report['type']] ?? 'Інше') . '</h4>'; // Тип звіту
        echo '<p><strong>Область:</strong> ' . htmlspecialchars($report['region']) . '</p>'; // Регіон
        echo '<p><strong>Населений пункт:</strong> ' . htmlspecialchars($report['city']) . '</p>'; // Місто
        echo '<p><strong>Опис:</strong> ' . htmlspecialchars($report['description'] ?? 'Немає') . '</p>'; // Опис звіту
        echo '<p><strong>Статус:</strong> ' . htmlspecialchars($statuses[$report['status']] ?? 'Невідомо') . '</p>'; // Статус звіту
        echo '<p><strong>Час:</strong> ' . htmlspecialchars($report['created_at']) . '</p>'; // Час створення
        if ($report['photo']) {
            echo '<img src="../' . htmlspecialchars($report['photo']) . '" alt="Фото проблеми" class="report-image">'; // Фото звіту
        }
        echo '</div>';
    }
    echo '</div>';
}
$reports_html = ob_get_clean(); // Збереження HTML сітки

// Підрахунок загальної кількості звітів
$stats_query = "SELECT COUNT(*) as total FROM reports WHERE latitude BETWEEN 44 AND 53 AND longitude BETWEEN 22 AND 40"; // Запит для підрахунку звітів
if ($region) {
    $stats_query .= " AND region = '$region'"; // Умова за регіоном
}
if ($type) {
    $stats_query .= " AND type = '$type'"; // Умова за типом
}
if ($status) {
    $stats_query .= " AND status = '$status'"; // Умова за статусом
}
$stats_result = $conn->query($stats_query); // Виконання запиту
if ($stats_result === false) { // Перевірка на помилку
    error_log("SQL Error: " . $conn->error); // Логування помилки
    echo json_encode([
        'success' => false,
        'message' => 'Помилка при отриманні статистики.'
    ], JSON_UNESCAPED_UNICODE); // Повідомлення про помилку
    exit;
}
$total_reports = $stats_result->fetch_assoc()['total']; // Отримання загальної кількості звітів

$conn->close(); // Закриття з’єднання з базою

// Повернення JSON
echo json_encode([
    'success' => true,
    'reports' => $reports,
    'reports_html' => $reports_html,
    'total_reports' => $total_reports
], JSON_UNESCAPED_UNICODE); // Виведення результату у форматі JSON
?>