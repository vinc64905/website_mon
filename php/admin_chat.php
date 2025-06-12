<?php
// Підключення файлу з налаштуваннями
require_once '../includes/config.php';
// Підключення модуля для роботи з базою даних
require_once '../includes/db.php';
// Підключення модуля автентифікації
require_once '../includes/auth.php';

// Перевірка, чи користувач є модератором
if (!isModerator()) {
    // Перенаправлення на сторінку входу
    header('Location: login.php');
    exit();
}

// Створення з'єднання з базою даних
$conn = getDbConnection();

// Запит для отримання списку користувачів із чатами та кількістю непрочитаних повідомлень
$sql = "SELECT DISTINCT u.id, u.name, u.email, 
               (SELECT COUNT(*) FROM chat_messages cm2 
                WHERE cm2.user_id = u.id AND cm2.is_admin_reply = 0 AND cm2.is_read = 0) as unread_count
        FROM users u 
        JOIN chat_messages cm ON u.id = cm.user_id 
        ORDER BY u.name ASC";
$result = $conn->query($sql);
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
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
    <title>Vinc_Road - Адмін-чат</title>
    <!-- Підключення стилів для оформлення сторінки -->
    <link rel="stylesheet" href="../css/common.css">
    <link rel="stylesheet" href="../css/admin_chat.css">
</head>
<body>
    <!-- Кнопка для зміни теми (світла/темна) -->
    <button id="theme-toggle" class="theme-toggle">🌙</button>
    <header>
        <!-- Основний заголовок сторінки -->
        <h1>Vinc_Road: Адмін-чат</h1>
        <!-- Навігаційне меню для переходу між сторінками -->
        <nav>
            <a href="index.php">Головна</a>
            <a href="monitor.php">Моніторинг</a>
            <a href="analytics.php">Аналітика</a>
            <a href="report.php">Повідомити про проблему</a>
            <a href="about.php">Про нас</a>
            <a href="profile.php">Профіль</a>
            <a href="logout.php">Вийти</a>
        </nav>
    </header>
    <main>
        <!-- Секція для адмін-чату -->
        <section class="admin-chat-section">
            <h2>Чат з користувачами</h2>
            <p>Вибір користувача для спілкування та відповіді.</p>
            <div class="admin-chat-container">
                <!-- Список користувачів із чатами -->
                <div class="user-list">
                    <h3>Користувачі</h3>
                    <!-- Поле для пошуку користувачів -->
                    <input type="text" id="user-search" placeholder="Пошук за ім’ям або email..." class="search-input">
                    <!-- Умовне відображення за наявності чатів -->
                    <?php if (empty($users)): ?>
                        <p>Активні чати відсутні.</p>
                    <?php else: ?>
                        <ul id="user-list">
                            <!-- Виведення списку користувачів -->
                            <?php foreach ($users as $user): ?>
                                <li data-user-id="<?php echo htmlspecialchars($user['id']); ?>" 
                                    data-user-name="<?php echo htmlspecialchars($user['name']); ?>" 
                                    data-user-email="<?php echo htmlspecialchars($user['email']); ?>" 
                                    data-unread-count="<?php echo htmlspecialchars($user['unread_count']); ?>">
                                    <?php echo htmlspecialchars($user['name']); ?> (<?php echo htmlspecialchars($user['email']); ?>)
                                    <!-- Позначка кількості непрочитаних повідомлень -->
                                    <?php if ($user['unread_count'] > 0): ?>
                                        <span class="user-unread-count"><?php echo $user['unread_count']; ?></span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <!-- Відображення загальної кількості непрочитаних повідомлень -->
                    <p class="debug-info">Непрочитаних повідомлень: <?php echo array_sum(array_column($users, 'unread_count')); ?></p>
                </div>
                <!-- Область для відображення чату -->
                <div class="chat-area">
                    <!-- Назва активного чату -->
                    <h3 id="chat-user-name">Оберіть користувача</h3>
                    <!-- Кнопка для видалення чату -->
                    <button id="delete-chat" class="btn delete-btn" style="display: none;">Видалити чат</button>
                    <!-- Контейнер для повідомлень чату -->
                    <div class="chat-messages" id="chat-messages"></div>
                    <!-- Область для введення нового повідомлення -->
                    <div class="chat-input" id="chat-input" style="display: none;">
                        <textarea id="chat-message" rows="4" placeholder="Введіть вашу відповідь..." required></textarea>
                        <button id="send-message" class="btn send-btn">Надіслати</button>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <footer>
        <!-- Інформація про авторські права -->
        <p>© 2025 Vinc_Road - Моніторинг інфраструктури України</p>
    </footer>
    <!-- Підключення скриптів для функціональності -->
    <script src="../js/theme.js"></script>
    <script src="../js/admin_chat.js"></script>
</body>
</html>