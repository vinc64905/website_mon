<?php
require_once '../includes/config.php'; // Підключення конфігураційного файлу
require_once '../includes/db.php'; // Підключення до бази даних
require_once '../includes/auth.php'; // Підключення функцій авторизації

header('Content-Type: application/json'); // Встановлення формату відповіді JSON

// Перевірка авторизації
if (!isModerator()) { // Перевірка, чи користувач є модератором
    error_log('Unauthorized access to filter_admin_reports.php'); // Запис спроби несанкціонованого доступу
    echo json_encode(['success' => false, 'message' => 'Недостатньо прав доступу'], JSON_UNESCAPED_UNICODE); // Повідомлення про недостатність прав
    exit;
}

$conn = getDbConnection(); // Отримання з’єднання з базою даних

// Отримання параметрів фільтрів, пошуку і пагінації
$region = isset($_GET['region']) ? $conn->real_escape_string($_GET['region']) : ''; // Фільтр за регіоном
$status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : ''; // Фільтр за статусом
$type = isset($_GET['type']) ? $conn->real_escape_string($_GET['type']) : ''; // Фільтр за типом
$search_query = isset($_GET['search_query']) ? $conn->real_escape_string(trim($_GET['search_query'])) : ''; // Пошуковий запит
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_at'; // Поле для сортування
$order = isset($_GET['order']) ? $_GET['order'] : 'DESC'; // Напрямок сортування
$page = max(1, (int)($_GET['page'] ?? 1)); // Номер сторінки
$per_page = 10; // Кількість записів на сторінці

$sort = in_array($sort, ['id', 'region', 'type', 'status', 'created_at']) ? $sort : 'created_at'; // Перевірка коректності поля сортування
$order = in_array($order, ['ASC', 'DESC']) ? $order : 'DESC'; // Перевірка коректності напрямку сортування

error_log("filter_admin_reports.php - region: $region, status: $status, type: $type, search_query: $search_query, sort: $sort, order: $order, page: $page"); // Логування параметрів

// Формування SQL-запиту для підрахунку загальної кількості
$sql_count = "SELECT COUNT(*) as total FROM reports WHERE 1=1"; // Базовий запит для підрахунку звітів
$params = []; // Масив параметрів для запиту
$types = ""; // Типи даних параметрів
if ($region) {
    $sql_count .= " AND region = ?"; // Додавання умови за регіоном
    $params[] = $region;
    $types .= "s";
}
if ($status) {
    $sql_count .= " AND status = ?"; // Додавання умови за статусом
    $params[] = $status;
    $types .= "s";
}
if ($type) {
    $sql_count .= " AND type = ?"; // Додавання умови за типом
    $params[] = $type;
    $types .= "s";
}
if ($search_query) {
    $sql_count .= " AND (description LIKE ? OR region LIKE ? OR city LIKE ? OR id = ?)"; // Умова пошуку за описом, регіоном, містом або ID
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = (int)$search_query;
    $types .= "sssi";
}
$stmt = $conn->prepare($sql_count); // Підготовка запиту
if ($types) {
    $stmt->bind_param($types, ...$params); // Прив’язка параметрів
}
if (!$stmt->execute()) { // Виконання запиту
    error_log("SQL Error in count: " . $stmt->error); // Логування помилки
    echo json_encode(['success' => false, 'message' => 'Помилка при отриманні даних'], JSON_UNESCAPED_UNICODE); // Повідомлення про помилку
    exit;
}
$total_reports = $stmt->get_result()->fetch_assoc()['total']; // Отримання загальної кількості звітів
$total_pages = max(1, ceil($total_reports / $per_page)); // Розрахунок кількості сторінок
$stmt->close(); // Закриття запиту

// Формування SQL-запиту для звітів
$sql = "SELECT * FROM reports WHERE 1=1"; // Базовий запит для отримання звітів
if ($region) {
    $sql .= " AND region = ?"; // Умова за регіоном
}
if ($status) {
    $sql .= " AND status = ?"; // Умова за статусом
}
if ($type) {
    $sql .= " AND type = ?"; // Умова за типом
}
if ($search_query) {
    $sql .= " AND (description LIKE ? OR region LIKE ? OR city LIKE ? OR id = ?)"; // Умова пошуку
}
$sql .= " ORDER BY $sort $order LIMIT ? OFFSET ?"; // Сортування та пагінація
$params = array_merge($params, [$per_page, ($page - 1) * $per_page]); // Додавання параметрів пагінації
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

// Формування query_params для збереження фільтрів
$query_params = http_build_query([
    'region' => $region,
    'status' => $status,
    'type' => $type,
    'search_query' => $search_query,
    'sort' => $sort,
    'order' => $order
]); // Збереження параметрів фільтрів

// Генерація HTML для таблиці
ob_start(); // Початок буферизації виведення
if (empty($reports)) {
    echo '<p class="filter-message">Немає звітів за заданим пошуком.</p>'; // Повідомлення про відсутність звітів
} else {
    echo '<form id="bulk-actions-form" class="bulk-actions-form">'; // Форма для групових дій
    if (isAdmin()) {
        echo '<div class="form-group">';
        echo '<select name="bulk_action" id="bulk_action">';
        echo '<option value="">Оберіть дію</option>';
        echo '<option value="change_status">Змінити статус</option>';
        echo '<option value="delete">Видалити</option>';
        echo '</select>'; // Вибір групової дії
        echo '<select name="new_status" id="bulk_status" style="display: none;">';
        echo '<option value="new">Нова</option>';
        echo '<option value="in_progress">В обробці</option>';
        echo '<option value="resolved">Вирішена</option>';
        echo '</select>'; // Вибір нового статусу
        echo '<button type="button" class="action-btn">Виконати</button>'; // Кнопка виконання
        echo '</div>';
    }
    echo '<div class="table-wrapper">';
    echo '<table>';
    echo '<thead>';
    echo '<tr>';
    echo '<th><input type="checkbox" id="select-all-reports"></th>'; // Чекбокс для вибору всіх звітів
    echo '<th><a href="#" class="sort-link" data-sort="id" data-order="' . ($sort === 'id' && $order === 'ASC' ? 'DESC' : 'ASC') . '">ID</a></th>'; // Сортування за ID
    echo '<th><a href="#" class="sort-link" data-sort="region" data-order="' . ($sort === 'region' && $order === 'ASC' ? 'DESC' : 'ASC') . '">Область</a></th>'; // Сортування за регіоном
    echo '<th>Населений пункт</th>';
    echo '<th><a href="#" class="sort-link" data-sort="type" data-order="' . ($sort === 'type' && $order === 'ASC' ? 'DESC' : 'ASC') . '">Тип</a></th>'; // Сортування за типом
    echo '<th>Опис</th>';
    echo '<th>Фото</th>';
    echo '<th><a href="#" class="sort-link" data-sort="status" data-order="' . ($sort === 'status' && $order === 'ASC' ? 'DESC' : 'ASC') . '">Статус</a></th>'; // Сортування за статусом
    echo '<th><a href="#" class="sort-link" data-sort="created_at" data-order="' . ($sort === 'created_at' && $order === 'ASC' ? 'DESC' : 'ASC') . '">Час</a></th>'; // Сортування за часом
    echo '<th>Дії</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    foreach ($reports as $report) {
        echo '<tr data-report-id="' . htmlspecialchars($report['id']) . '">'; // Рядок звіту
        echo '<td><input type="checkbox" name="report_ids[]" value="' . htmlspecialchars($report['id']) . '"></td>'; // Чекбокс для вибору звіту
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
        echo '<td>';
        echo '<form method="POST" style="display: inline;" class="status-form">';
        echo '<input type="hidden" name="report_id" value="' . $report['id'] . '">';
        echo '<select name="new_status" onchange="updateStatus(this, ' . $report['id'] . ')">';
        echo '<option value="new" ' . ($report['status'] === 'new' ? 'selected' : '') . '>Нова</option>';
        echo '<option value="in_progress" ' . ($report['status'] === 'in_progress' ? 'selected' : '') . '>В обробці</option>';
        echo '<option value="resolved" ' . ($report['status'] === 'resolved' ? 'selected' : '') . '>Вирішена</option>';
        echo '</select>'; // Вибір статусу
        echo '</form>';
        echo '</td>';
        echo '<td>' . htmlspecialchars($report['created_at']) . '</td>'; // Час створення
        echo '<td>';
        if (isAdmin()) {
            echo '<a href="delete_report.php?id=' . $report['id'] . '&' . $query_params . '" onclick="return confirm(\'Ви впевнені, що хочете видалити цей звіт?\');" class="action-btn delete-btn">Видалити</a>'; // Кнопка видалення
        }
        echo '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    echo '</form>';

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
    'reports' => $reports,
    'reports_html' => $reports_html,
    'total_reports' => $total_reports
], JSON_UNESCAPED_UNICODE); // Виведення результату у форматі JSON
?>