<?php
require_once '../includes/config.php'; // Підключення конфігураційного файлу
require_once '../includes/db.php'; // Підключення до бази даних
require_once '../includes/auth.php'; // Підключення функцій авторизації

header('Content-Type: application/json'); // Встановлення формату відповіді JSON

// Перевірка авторизації
if (!isLoggedIn()) { // Перевірка, чи користувач авторизований
    error_log('Unauthorized access to filter_profile_reports.php'); // Запис спроби несанкціонованого доступу
    echo json_encode(['success' => false, 'message' => 'Необхідно увійти в систему'], JSON_UNESCAPED_UNICODE); // Повідомлення про необхідність авторизації
    exit;
}

$conn = getDbConnection(); // Отримання з’єднання з базою даних
$user_id = $_SESSION['user_id']; // Отримання ID авторизованого користувача

// Отримання параметрів фільтрів і пагінації
$status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : ''; // Фільтр за статусом
$type = isset($_GET['type']) ? $conn->real_escape_string($_GET['type']) : ''; // Фільтр за типом
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_at'; // Поле для сортування
$order = isset($_GET['order']) ? $_GET['order'] : 'DESC'; // Напрямок сортування
$page = max(1, (int)($_GET['page'] ?? 1)); // Номер сторінки
$per_page = 10; // Кількість записів на сторінці

$sort = in_array($sort, ['id', 'created_at']) ? $sort : 'created_at'; // Перевірка коректності поля сортування
$order = in_array($order, ['ASC', 'DESC']) ? $order : 'DESC'; // Перевірка коректності напрямку сортування

error_log("filter_profile_reports.php - user_id: $user_id, status: $status, type: $type, sort: $sort, order: $order, page: $page"); // Логування параметрів

// Формування SQL-запиту для підрахунку загальної кількості
$sql_count = "SELECT COUNT(*) as total FROM reports WHERE user_id = ?"; // Базовий запит для підрахунку звітів користувача
$params = [$user_id]; // Параметр ID користувача
$types = "i"; // Тип даних параметра
if ($status) {
    $sql_count .= " AND status = ?"; // Умова за статусом
    $params[] = $status;
    $types .= "s";
}
if ($type) {
    $sql_count .= " AND type = ?"; // Умова за типом
    $params[] = $type;
    $types .= "s";
}
$stmt = $conn->prepare($sql_count); // Підготовка запиту
$stmt->bind_param($types, ...$params); // Прив’язка параметрів
if (!$stmt->execute()) { // Виконання запиту
    error_log("SQL Error in count: " . $stmt->error); // Логування помилки
    echo json_encode(['success' => false, 'message' => 'Помилка при отриманні даних'], JSON_UNESCAPED_UNICODE); // Повідомлення про помилку
    exit;
}
$total_reports = $stmt->get_result()->fetch_assoc()['total']; // Отримання загальної кількості звітів
$total_pages = max(1, ceil($total_reports / $per_page)); // Розрахунок кількості сторінок
$stmt->close(); // Закриття запиту

// Формування SQL-запиту для звітів
$sql = "SELECT * FROM reports WHERE user_id = ?"; // Базовий запит для отримання звітів користувача
if ($status) {
    $sql .= " AND status = ?"; // Умова за статусом
}
if ($type) {
    $sql .= " AND type = ?"; // Умова за типом
}
$sql .= " ORDER BY $sort $order LIMIT ? OFFSET ?"; // Сортування та пагінація
$params[] = $per_page; // Додавання параметрів пагінації
$params[] = ($page - 1) * $per_page;
$types .= "ii";

$stmt = $conn->prepare($sql); // Підготовка запиту
$stmt->bind_param($types, ...$params); // Прив’язка параметрів
if (!$stmt->execute()) { // Виконання запиту
    error_log("SQL Error in fetch: " . $stmt->error); // Логування помилки
    echo json_encode(['success' => false, 'message' => 'Помилка при отриманні звітів'], JSON_UNESCAPED_UNICODE); // Повідомлення про помилку
    exit;
}
$reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); // Отримання всіх звітів
$stmt->close(); // Закриття запиту
$conn->close(); // Закриття з’єднання з базою

// Масиви для зіставлення
$types_map = [
    'pothole' => 'Вибоїна',
    'trash' => 'Сміття',
    'light' => 'Освітлення',
    'sign' => 'Дорожній знак',
    'other' => 'Інше'
]; // Переклад типів звітів
$statuses = [
    'new' => 'Нова',
    'in_progress' => 'В обробці',
    'resolved' => 'Вирішена'
]; // Переклад статусів звітів

// Генерація HTML для таблиці
ob_start(); // Початок буферизації виведення
if (empty($reports)) {
    echo '<p class="filter-message">Немає звітів за вибраними фільтрами.</p>'; // Повідомлення про відсутність звітів
} else {
    echo '<div class="table-wrapper">';
    echo '<table>';
    echo '<thead>';
    echo '<tr>';
    echo '<th><a href="#" class="sort-link" data-sort="id" data-order="' . ($sort === 'id' && $order === 'ASC' ? 'DESC' : 'ASC') . '">ID</a></th>'; // Сортування за ID
    echo '<th>Область</th>';
    echo '<th>Населений пункт</th>';
    echo '<th>Тип</th>';
    echo '<th>Опис</th>';
    echo '<th>Фото</th>';
    echo '<th>Статус</th>';
    echo '<th><a href="#" class="sort-link" data-sort="created_at" data-order="' . ($sort === 'created_at' && $order === 'ASC' ? 'DESC' : 'ASC') . '">Час</a></th>'; // Сортування за часом
    echo '<th>Дії</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    foreach ($reports as $report) {
        $created_time = strtotime($report['created_at']); // Час створення звіту
        $current_time = time(); // Поточний час
        $time_left = max(0, 120 - ($current_time - $created_time)); // Час, що залишився для редагування (120 секунд)
        $can_edit = $time_left > 0; // Чи можна редагувати звіт
        echo '<tr data-report-id="' . htmlspecialchars($report['id']) . '">'; // Рядок звіту
        echo '<td>' . htmlspecialchars($report['id']) . '</td>'; // ID звіту
        echo '<td>' . htmlspecialchars($report['region']) . '</td>'; // Регіон
        echo '<td>' . htmlspecialchars($report['city']) . '</td>'; // Місто
        echo '<td>' . htmlspecialchars($types_map[$report['type']] ?? 'Інше') . '</td>'; // Тип звіту
        echo '<td>' . htmlspecialchars($report['description'] ?? 'Немає') . '</td>'; // Опис звіту
        echo '<td>';
        if ($report['photo']) {
            echo '<img src="../' . htmlspecialchars($report['photo']) . '" alt="Фото" style="max-width: 100px;">'; // Фото звіту
        } else {
            echo 'Немає';
        }
        echo '</td>';
        echo '<td>' . htmlspecialchars($statuses[$report['status']] ?? 'Невідомо') . '</td>'; // Статус звіту
        echo '<td>';
        echo htmlspecialchars($report['created_at']); // Час створення
        if ($can_edit) {
            echo '<br><span class="time-left" data-time-left="' . $time_left . '"></span>'; // Час, що залишився для редагування
        }
        echo '</td>';
        echo '<td>';
        if ($can_edit) {
            echo '<a href="edit_report.php?id=' . $report['id'] . '" class="action-btn">Редагувати</a>'; // Кнопка редагування
            echo '<form method="POST" style="display: inline;" class="delete-report-form" data-report-id="' . $report['id'] . '">';
            echo '<input type="hidden" name="delete_report" value="1">';
            echo '<input type="hidden" name="report_id" value="' . $report['id'] . '">';
            echo '<button type="submit" class="action-btn delete-btn">Видалити</button>'; // Кнопка видалення
            echo '</form>';
        }
        echo '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>';

    // Пагінація
    if ($total_pages > 1) {
        echo '<div class="pagination">';
        if ($page > 1) {
            echo '<a href="#" class="btn page-link" data-page="' . ($page - 1) . '">Попередня</a>'; // Попередня сторінка
        }
        for ($i = 1; $i <= $total_pages; $i++) {
            echo '<a href="#" class="btn page-link ' . ($i === $page ? 'active' : '') . '" data-page="' . $i . '">' . $i . '</a>'; // Номери сторінок
        }
        if ($page < $total_pages) {
            echo '<a href="#" class="btn page-link" data-page="' . ($page + 1) . '">Наступна</a>'; // Наступна сторінка
        }
        echo '</div>';
    }
}
$reports_html = ob_get_clean(); // Збереження HTML таблиці

// Повернення JSON
echo json_encode([
    'success' => true,
    'reports_html' => $reports_html,
    'total_reports' => $total_reports
], JSON_UNESCAPED_UNICODE); // Виведення результату у форматі JSON
?>