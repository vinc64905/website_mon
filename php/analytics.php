<?php
// Підключення файлу з налаштуваннями
require_once '../includes/config.php';
// Підключення модуля для роботи з базою даних
require_once '../includes/db.php';
// Підключення модуля автентифікації
require_once '../includes/auth.php';

// Створення з'єднання з базою даних
$conn = getDbConnection();

// Запит для отримання топ-5 областей за кількістю звітів
$regions_query = "SELECT region, COUNT(*) as count FROM reports GROUP BY region ORDER BY count DESC LIMIT 5";
$regions_result = $conn->query($regions_query);
$regions_data = [];
$regions_labels = [];
if ($regions_result && $regions_result->num_rows > 0) {
    while ($row = $regions_result->fetch_assoc()) {
        $regions_labels[] = $row['region'];
        $regions_data[] = $row['count'];
    }
} else {
    $regions_labels = ['Немає даних'];
    $regions_data = [0];
}

// Запит для підрахунку звітів за статусами
$status_query = "SELECT status, COUNT(*) as count FROM reports GROUP BY status";
$status_result = $conn->query($status_query);
$status_data = [];
$status_labels = ['Нова', 'В обробці', 'Вирішена'];
$status_counts = ['new' => 0, 'in_progress' => 0, 'resolved' => 0];
if ($status_result && $status_result->num_rows > 0) {
    while ($row = $status_result->fetch_assoc()) {
        $status_counts[$row['status']] = $row['count'];
    }
}
$status_data = array_values($status_counts);

// Запит для аналізу активності звітів за датами
$time_query = "SELECT DATE(created_at) as date, COUNT(*) as count FROM reports GROUP BY DATE(created_at) ORDER BY date";
$time_result = $conn->query($time_query);
$time_labels = [];
$time_data = [];
if ($time_result && $time_result->num_rows > 0) {
    while ($row = $time_result->fetch_assoc()) {
        $time_labels[] = $row['date'];
        $time_data[] = $row['count'];
    }
} else {
    $time_labels = ['Немає даних'];
    $time_data = [0];
}

// Запит для розподілу звітів за типами
$types_query = "SELECT type, COUNT(*) as count FROM reports GROUP BY type ORDER BY count DESC";
$types_result = $conn->query($types_query);
$types_data = [];
$types_labels = [];
$types_mapping = [
    'pothole' => 'Вибоїни',
    'trash' => 'Сміття',
    'light' => 'Освітлення',
    'sign' => 'Дорожні знаки',
    'other' => 'Інше'
];
if ($types_result && $types_result->num_rows > 0) {
    while ($row = $types_result->fetch_assoc()) {
        $types_labels[] = $types_mapping[$row['type']] ?? 'Інше';
        $types_data[] = $row['count'];
    }
} else {
    $types_labels = ['Немає даних'];
    $types_data = [0];
}

// Запит для середнього часу вирішення звітів за регіонами (топ-5)
$avg_time_region_query = "SELECT region, AVG(TIMESTAMPDIFF(HOUR, created_at, NOW())) as avg_time 
                         FROM reports 
                         WHERE status = 'resolved' 
                         GROUP BY region 
                         ORDER BY avg_time DESC 
                         LIMIT 5";
$avg_time_region_result = $conn->query($avg_time_region_query);
$avg_time_region_data = [];
$avg_time_region_labels = [];
if ($avg_time_region_result && $avg_time_region_result->num_rows > 0) {
    while ($row = $avg_time_region_result->fetch_assoc()) {
        $avg_time_region_labels[] = $row['region'];
        $avg_time_region_data[] = round($row['avg_time'], 1);
    }
} else {
    $avg_time_region_labels = ['Немає даних'];
    $avg_time_region_data = [0];
}

// Запит для середнього часу вирішення всіх звітів
$avg_time_query = "SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, NOW())) as avg_time 
                   FROM reports 
                   WHERE status = 'resolved'";
$avg_time_result = $conn->query($avg_time_query);
$avg_time = $avg_time_result && $avg_time_result->num_rows > 0 ? round($avg_time_result->fetch_assoc()['avg_time'], 1) : 0;

// Запит для топ-3 найпоширеніших типів звітів
$types_query = "SELECT type, COUNT(*) as count FROM reports GROUP BY type ORDER BY count DESC LIMIT 3";
$types_result = $conn->query($types_query);
$top_types = [];
while ($row = $types_result->fetch_assoc()) {
    $top_types[] = [
        'type' => $types_mapping[$row['type']] ?? 'Інше',
        'count' => $row['count']
    ];
}

// Закриття з'єднання з базою даних
$conn->close();
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <!-- Встановлення кодування сторінки -->
    <meta charset="UTF-8">
    <!-- Налаштування адаптивності для мобільних пристроїв -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Назва сторінки в браузері -->
    <title>Vinc_Road - Аналітика</title>
    <!-- Підключення стилів для оформлення сторінки -->
    <link rel="stylesheet" href="../css/common.css">
    <link rel="stylesheet" href="../css/analytics.css">
    <link rel="stylesheet" href="../css/responsive.css">
    <!-- Підключення бібліотеки для створення графіків -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Кнопка для зміни теми (світла/темна) -->
    <button id="theme-toggle" class="theme-toggle">🌙</button>
    <header>
        <!-- Основний заголовок сторінки -->
        <h1>Vinc_Road: Аналітика проблем інфраструктури</h1>
        <!-- Навігаційне меню для переходу між сторінками -->
        <nav>
            <a href="index.php">Головна</a>
            <a href="monitor.php">Моніторинг</a>
            <a href="analytics.php">Аналітика</a>
            <a href="report.php">Повідомити про проблему</a>
            <a href="about.php">Про нас</a>
            <!-- Умовне відображення пунктів меню залежно від автентифікації -->
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="profile.php">Профіль</a>
                <a href="logout.php">Вийти</a>
            <?php else: ?>
                <a href="login.php">Вхід</a>
                <a href="register.php">Реєстрація</a>
            <?php endif; ?>
        </nav>
    </header>
    <main>
        <!-- Секція з вступною інформацією -->
        <section class="intro">
            <h2>Аналітика проблем інфраструктури</h2>
            <p>Основні показники стану інфраструктури України.</p>
        </section>

        <!-- Секція зі статистичними даними -->
        <section class="extended-stats">
            <h3>Детальна статистика</h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <h4>Середній час вирішення</h4>
                    <p><?php echo htmlspecialchars($avg_time); ?> годин</p>
                </div>
                <div class="stat-item">
                    <h4>Найпоширеніші проблеми</h4>
                    <!-- Умовне відображення за наявності даних -->
                    <?php if (empty($top_types)): ?>
                        <p>Дані відсутні.</p>
                    <?php else: ?>
                        <ul>
                            <!-- Виведення топ-3 типів проблем -->
                            <?php foreach ($top_types as $type): ?>
                                <li><?php echo htmlspecialchars($type['type']); ?>: <?php echo htmlspecialchars($type['count']); ?> звітів</li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Секція з графіками -->
        <section class="charts">
            <div class="chart-container">
                <h3>Проблеми за областями (Топ-5)</h3>
                <canvas id="regionsChart"></canvas>
                <!-- Повідомлення про відсутність даних -->
                <?php if (empty($regions_data) || $regions_data[0] == 0): ?>
                    <p class="no-data">Дані відсутні.</p>
                <?php endif; ?>
            </div>

            <div class="chart-container">
                <h3>Проблеми за статусами</h3>
                <canvas id="statusChart"></canvas>
                <!-- Повідомлення про відсутність даних -->
                <?php if (empty($status_data) || array_sum($status_data) == 0): ?>
                    <p class="no-data">Дані відсутні.</p>
                <?php endif; ?>
            </div>

            <div class="chart-container">
                <h3>Активність за часом</h3>
                <canvas id="timeChart"></canvas>
                <!-- Повідомлення про відсутність даних -->
                <?php if (empty($time_data) || $time_data[0] == 0): ?>
                    <p class="no-data">Дані відсутні.</p>
                <?php endif; ?>
            </div>

            <div class="chart-container">
                <h3>Типи проблем</h3>
                <canvas id="typesChart"></canvas>
                <!-- Повідомлення про відсутність даних -->
                <?php if (empty($types_data) || $types_data[0] == 0): ?>
                    <p class="no-data">Дані відсутні.</p>
                <?php endif; ?>
            </div>

            <div class="chart-container">
                <h3>Час вирішення за регіонами (Топ-5)</h3>
                <canvas id="avgTimeRegionChart"></canvas>
                <!-- Повідомлення про відсутність даних -->
                <?php if (empty($avg_time_region_data) || $avg_time_region_data[0] == 0): ?>
                    <p class="no-data">Дані відсутні.</p>
                <?php endif; ?>
            </div>
        </section>

        <!-- Секція з аналізом тенденцій -->
        <section class="trends">
            <h3>Тенденції</h3>
            <p>Найпоширеніша проблема: <?php echo htmlspecialchars($top_types[0]['type'] ?? 'невідомо'); ?>. Зростання активності користувачів.</p>
            <p>Середній час вирішення: <?php echo htmlspecialchars($avg_time); ?> годин.</p>
        </section>

        <!-- Секція із закликом до дії -->
        <section class="cta">
            <h3>Допоможіть покращити міста!</h3>
            <p>Ваші звіти допомагають вирішувати проблеми інфраструктури.</p>
            <!-- Умовне відображення кнопки залежно від автентифікації -->
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="register.php" class="btn">Зареєструватися</a>
            <?php else: ?>
                <a href="report.php" class="btn">Повідомити про проблему</a>
            <?php endif; ?>
        </section>
    </main>
    <footer>
        <!-- Інформація про авторські права -->
        <p>© 2025 Vinc_Road - Моніторинг інфраструктури України</p>
    </footer>
    <!-- Підключення скриптів для функціональності -->
    <script src="../js/theme.js"></script>
    <script>
        // Передача даних для графіків до JavaScript
        const regionsLabels = <?php echo json_encode($regions_labels, JSON_UNESCAPED_UNICODE); ?>;
        const regionsData = <?php echo json_encode($regions_data); ?>;
        const statusLabels = <?php echo json_encode($status_labels, JSON_UNESCAPED_UNICODE); ?>;
        const statusData = <?php echo json_encode($status_data); ?>;
        const timeLabels = <?php echo json_encode($time_labels, JSON_UNESCAPED_UNICODE); ?>;
        const timeData = <?php echo json_encode($time_data); ?>;
        const typesLabels = <?php echo json_encode($types_labels, JSON_UNESCAPED_UNICODE); ?>;
        const typesData = <?php echo json_encode($types_data); ?>;
        const avgTimeRegionLabels = <?php echo json_encode($avg_time_region_labels, JSON_UNESCAPED_UNICODE); ?>;
        const avgTimeRegionData = <?php echo json_encode($avg_time_region_data); ?>;
    </script>
    <script src="../js/analytics.js"></script>
</body>
</html>