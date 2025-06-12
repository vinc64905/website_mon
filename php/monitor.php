<?php
require_once '../includes/config.php'; // Підключення налаштувань системи
require_once '../includes/db.php'; // Підключення до бази даних
require_once '../includes/auth.php'; // Підключення функцій авторизації

$conn = getDbConnection(); // Створення з’єднання з базою даних

// Отримання фільтрів
$region = isset($_GET['region']) ? $conn->real_escape_string($_GET['region']) : ''; // Отримання області з параметрів запиту
$type = isset($_GET['type']) ? $conn->real_escape_string($_GET['type']) : ''; // Отримання типу проблеми
$status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : ''; // Отримання статусу звіту

// Формування SQL-запиту для звітів
$sql = "SELECT DISTINCT * FROM reports WHERE latitude BETWEEN 44 AND 53 AND longitude BETWEEN 22 AND 40"; // Запит для звітів у межах координат України
if ($region) {
    $sql .= " AND region = '$region'"; // Додавання фільтру за областю
}
if ($type) {
    $sql .= " AND type = '$type'"; // Додавання фільтру за типом проблеми
}
if ($status) {
    $sql .= " AND status = '$status'"; // Додавання фільтру за статусом
}

$result = $conn->query($sql); // Виконання запиту
if ($result === false) {
    error_log("SQL Error: " . $conn->error); // Запис помилки в лог
    die("Помилка при отриманні звітів."); // Виведення повідомлення про помилку
}
$reports = [];
while ($row = $result->fetch_assoc()) {
    $reports[] = $row; // Збереження звітів у масив
}

// Отримання статистики для вибраних фільтрів
$stats_query = "SELECT COUNT(*) as total FROM reports WHERE latitude BETWEEN 44 AND 53 AND longitude BETWEEN 22 AND 40"; // Запит для підрахунку звітів
if ($region) {
    $stats_query .= " AND region = '$region'"; // Фільтр за областю
}
if ($type) {
    $stats_query .= " AND type = '$type'"; // Фільтр за типом
}
if ($status) {
    $stats_query .= " AND status = '$status'"; // Фільтр за статусом
}
$stats_result = $conn->query($stats_query); // Виконання запиту
if ($stats_result === false) {
    error_log("SQL Error: " . $conn->error); // Запис помилки в лог
    die("Помилка при отриманні статистики."); // Виведення повідомлення про помилку
}
$total_reports = $stats_result->fetch_assoc()['total']; // Збереження загальної кількості звітів

// Отримання унікальних областей для фільтру
$regions_result = $conn->query("SELECT DISTINCT region FROM reports ORDER BY region"); // Запит для отримання всіх областей
if ($regions_result === false) {
    error_log("SQL Error: " . $conn->error); // Запис помилки в лог
    die("Помилка при отриманні областей."); // Виведення повідомлення про помилку
}
$regions = [];
while ($row = $regions_result->fetch_assoc()) {
    $regions[] = $row['region']; // Збереження областей у масив
}

$conn->close(); // Закриття з’єднання з базою даних

// Масив для зіставлення типів проблем
$types = [
    'pothole' => 'Вибоїна', // Тип проблеми: вибоїна
    'trash' => 'Сміття', // Тип проблеми: сміття
    'light' => 'Освітлення', // Тип проблеми: освітлення
    'sign' => 'Дорожній знак', // Тип проблеми: дорожній знак
    'other' => 'Інше' // Тип проблеми: інше
];

// Масив для зіставлення статусів
$statuses = [
    'new' => 'Нова', // Статус: нова
    'in_progress' => 'В обробці', // Статус: в обробці
    'resolved' => 'Вирішена' // Статус: вирішена
];

// Масив для іконок типів (узгоджено з map.js)
$icons = [
    'pothole' => '<i class="fas fa-road" style="color: #d32f2f;"></i>', // Іконка для вибоїни
    'trash' => '<i class="fas fa-trash" style="color: #4caf50;"></i>', // Іконка для сміття
    'light' => '<i class="fas fa-lightbulb" style="color: #ff9800;"></i>', // Іконка для освітлення
    'sign' => '<i class="fas fa-sign" style="color: #2196f3;"></i>', // Іконка для дорожнього знака
    'other' => '<i class="fas fa-question-circle" style="color: #9e9e9e;"></i>' // Іконка для іншого
];

// Визначення початкового тексту для фільтрів
$region_text = $region ? $region : ''; // Текст для відображення вибраної області
$type_text = $type ? $types[$type] : ''; // Текст для відображення типу проблеми
$status_text = $status ? $statuses[$status] : ''; // Текст для відображення статусу

// Генерація HTML для списку звітів
$reports_html = '';
if (empty($reports)) {
    $reports_html = '<p class="filter-message">Немає звітів за вибраними фільтрами.</p>'; // Повідомлення, якщо звіти відсутні
} else {
    $reports_html = '<div class="reports-grid">'; // Початок сітки звітів
    foreach ($reports as $report) {
        $reports_html .= '<div class="report-card" data-report-id="' . htmlspecialchars($report['id']) . '">'; // Картка звіту
        $reports_html .= '<div class="report-icon">' . ($icons[$report['type']] ?? $icons['other']) . '</div>'; // Іконка типу звіту
        $reports_html .= '<h4>' . htmlspecialchars($types[$report['type']] ?? 'Інше') . '</h4>'; // Назва типу звіту
        $reports_html .= '<p><strong>Область:</strong> ' . htmlspecialchars($report['region']) . '</p>'; // Область звіту
        $reports_html .= '<p><strong>Населений пункт:</strong> ' . htmlspecialchars($report['city']) . '</p>'; // Місто звіту
        $reports_html .= '<p><strong>Опис:</strong> ' . htmlspecialchars($report['description'] ?? 'Немає') . '</p>'; // Опис звіту
        $reports_html .= '<p><strong>Статус:</strong> ' . htmlspecialchars($statuses[$report['status']] ?? 'Невідомо') . '</p>'; // Статус звіту
        $reports_html .= '<p><strong>Час:</strong> ' . htmlspecialchars($report['created_at']) . '</p>'; // Час створення звіту
        if ($report['photo']) {
            $reports_html .= '<img src="../' . htmlspecialchars($report['photo']) . '" alt="Фото проблеми" class="report-image">'; // Фото звіту, якщо є
        }
        $reports_html .= '</div>'; // Завершення картки звіту
    }
    $reports_html .= '</div>'; // Завершення сітки звітів
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8"> <!-- Встановлення кодування сторінки -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Адаптація для мобільних пристроїв -->
    <title>Vinc_Road - Моніторинг проблем</title> <!-- Заголовок сторінки -->
    <link rel="stylesheet" href="../css/common.css"> <!-- Підключення основних стилів -->
    <link rel="stylesheet" href="../css/monitor.css"> <!-- Підключення стилів для сторінки моніторингу -->
    <link rel="stylesheet" href="../css/map.css"> <!-- Підключення стилів для карти -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" /> <!-- Підключення стилів бібліотеки Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" /> <!-- Підключення стилів для кластеризації маркерів -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" /> <!-- Підключення іконок Font Awesome -->
    <link rel="stylesheet" href="../css/responsive.css"> <!-- Підключення адаптивних стилів -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script> <!-- Підключення бібліотеки Leaflet -->
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script> <!-- Підключення кластеризації маркерів -->
</head>
<body>
    <button id="theme-toggle" class="theme-toggle">🌙</button> <!-- Кнопка зміни теми -->
    <header>
        <h1>Vinc_Road: Моніторинг проблем інфраструктури</h1> <!-- Заголовок сторінки -->
        <nav>
            <a href="index.php">Головна</a> <!-- Посилання на головну сторінку -->
            <a href="monitor.php">Моніторинг</a> <!-- Посилання на сторінку моніторингу -->
            <a href="analytics.php">Аналітика</a> <!-- Посилання на аналітику -->
            <a href="report.php">Повідомити про проблему</a> <!-- Посилання для створення звіту -->
            <a href="about.php">Про нас</a> <!-- Посилання на сторінку про проєкт -->
            <?php if (isset($_SESSION['user_id'])): ?> <!-- Перевірка, чи користувач увійшов -->
                <a href="profile.php">Профіль</a> <!-- Посилання на профіль -->
                <a href="logout.php">Вийти</a> <!-- Посилання для виходу -->
            <?php else: ?>
                <a href="login.php">Вхід</a> <!-- Посилання на сторінку входу -->
                <a href="register.php">Реєстрація</a> <!-- Посилання на сторінку реєстрації -->
            <?php endif; ?>
        </nav>
    </header>
    <main>
        <section class="intro">
            <h2>Моніторинг проблем на карті</h2> <!-- Заголовок секції -->
            <p>Переглядайте та фільтруйте звіти про проблеми інфраструктури в населених пунктах України. Оберіть область, тип проблеми або статус, щоб знайти потрібну інформацію.</p> <!-- Опис -->
        </section>

        <section class="filters">
            <h3>Фільтри</h3> <!-- Заголовок секції фільтрів -->
            <form id="report-filter-form"> <!-- Форма для фільтрації звітів -->
                <div class="filter-group">
                    <label for="region">Область</label> <!-- Мітка для фільтру області -->
                    <div class="filter-select">
                        <div class="filter-selected">
                            <i class="fas fa-map-marker-alt" style="color: #d32f2f;"></i> <!-- Іконка для фільтру -->
                            <span class="filter-text"><?php echo htmlspecialchars($region_text); ?></span> <!-- Вибрана область -->
                        </div>
                        <div class="filter-options"></div> <!-- Контейнер для варіантів вибору -->
                    </div>
                    <select name="region" id="region"> <!-- Випадаючий список областей -->
                        <option value="">Усі регіони</option> <!-- Варіант для всіх регіонів -->
                        <?php foreach ($regions as $reg): ?>
                            <option value="<?php echo htmlspecialchars($reg); ?>" <?php echo $region === $reg ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($reg); ?> <!-- Виведення області -->
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="type">Тип проблеми</label> <!-- Мітка для фільтру типу проблеми -->
                    <div class="filter-select">
                        <div class="filter-selected">
                            <i class="fas fa-exclamation-circle" style="color: #4caf50;"></i> <!-- Іконка для фільтру -->
                            <span class="filter-text"><?php echo htmlspecialchars($type_text); ?></span> <!-- Вибраний тип -->
                        </div>
                        <div class="filter-options"></div> <!-- Контейнер для варіантів вибору -->
                    </div>
                    <select name="type" id="type"> <!-- Випадаючий список типів проблем -->
                        <option value="">Усі типи</option> <!-- Варіант для всіх типів -->
                        <option value="pothole" <?php echo $type === 'pothole' ? 'selected' : ''; ?>>Вибоїна</option>
                        <option value="trash" <?php echo $type === 'trash' ? 'selected' : ''; ?>>Сміття</option>
                        <option value="light" <?php echo $type === 'light' ? 'selected' : ''; ?>>Освітлення</option>
                        <option value="sign" <?php echo $type === 'sign' ? 'selected' : ''; ?>>Дорожній знак</option>
                        <option value="other" <?php echo $type === 'other' ? 'selected' : ''; ?>>Інше</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="status">Статус</label> <!-- Мітка для фільтру статусу -->
                    <div class="filter-select">
                        <div class="filter-selected">
                            <i class="fas fa-tasks" style="color: #ff9800;"></i> <!-- Іконка для фільтру -->
                            <span class="filter-text"><?php echo htmlspecialchars($status_text); ?></span> <!-- Вибраний статус -->
                        </div>
                        <div class="filter-options"></div> <!-- Контейнер для варіантів вибору -->
                    </div>
                    <select name="status" id="status"> <!-- Випадаючий список статусів -->
                        <option value="">Усі статуси</option> <!-- Варіант для всіх статусів -->
                        <option value="new" <?php echo $status === 'new' ? 'selected' : ''; ?>>Нова</option>
                        <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>В обробці</option>
                        <option value="resolved" <?php echo $status === 'resolved' ? 'selected' : ''; ?>>Вирішена</option>
                    </select>
                </div>

                <button type="button" class="reset-filters"><i class="fas fa-times"></i> Скинути фільтри</button> <!-- Кнопка для скидання фільтрів -->
            </form>
            <div class="stats">
                <p>Знайдено звітів: <strong id="total-reports"><?php echo htmlspecialchars($total_reports); ?></strong></p> <!-- Виведення кількості звітів -->
            </div>
        </section>

        <section class="map-reports">
            <div class="map-container">
                <h3>Карта проблем</h3> <!-- Заголовок карти -->
                <div id="map" style="height: 500px; position: relative;"> <!-- Контейнер для карти -->
                    <div class="map-legend">
                        <h4>Типи проблем</h4> <!-- Легенда карти -->
                        <div><i class="fas fa-road" style="color: #d32f2f;"></i> Вибоїна</div> <!-- Позначка для вибоїни -->
                        <div><i class="fas fa-trash" style="color: #4caf50;"></i> Сміття</div> <!-- Позначка для сміття -->
                        <div><i class="fas fa-lightbulb" style="color: #ff9800;"></i> Освітлення</div> <!-- Позначка для освітлення -->
                        <div><i class="fas fa-sign" style="color: #2196f3;"></i> Дорожній знак</div> <!-- Позначка для дорожнього знака -->
                        <div><i class="fas fa-question-circle" style="color: #9e9e9e;"></i> Інше</div> <!-- Позначка для іншого -->
                    </div>
                </div>
            </div>
            <div class="reports-list">
                <h3>Список звітів</h3> <!-- Заголовок списку звітів -->
                <div id="reports-container">
                    <?php echo $reports_html; ?> <!-- Виведення списку звітів -->
                </div>
            </div>
        </section>
    </main>
    <footer>
        <p>© 2025 Vinc_Road - Моніторинг інфраструктури України</p> <!-- Нижній колонтитул -->
    </footer>
    <script src="../js/monitor.js"></script> <!-- Підключення скрипта для моніторингу -->
    <script src="../js/theme.js"></script> <!-- Підключення скрипта для зміни теми -->
    <script>
        // Передача даних до JavaScript
        const reports = <?php echo json_encode($reports, JSON_UNESCAPED_UNICODE); ?>; // Передача звітів
        const types = <?php echo json_encode($types, JSON_UNESCAPED_UNICODE); ?>; // Передача типів проблем
        const isAdmin = <?php echo isAdmin() ? 'true' : 'false'; ?>; // Передача статусу адміністратора
    </script>
</body>
</html>