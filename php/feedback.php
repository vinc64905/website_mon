<?php
require_once '../includes/config.php';
// Підключення налаштувань системи
require_once '../includes/db.php';
// Підключення модуля для роботи з базою даних
require_once '../includes/auth.php';
// Підключення модуля для перевірки авторизації

if (!isLoggedIn()) {
    // Перевірка, чи користувач авторизований
    header('Location: login.php');
    // Перенаправлення на сторінку входу
    exit();
    // Завершення виконання
}

$conn = getDbConnection();
// Створення з’єднання з базою даних
$user_id = $_SESSION['user_id'];
// Отримання ID користувача з сесії

// Отримання імені користувача
$sql = "SELECT name FROM users WHERE id = '$user_id'";
$result = $conn->query($sql);
// Запит до бази для отримання імені користувача
$user_name = $result->fetch_assoc()['name'] ?? 'Користувач';
// Отримання імені або значення за замовчуванням

$conn->close();
// Закриття з’єднання з базою даних
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <!-- Встановлення кодування сторінки -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Адаптація для мобільних пристроїв -->
    <title>Vinc_Road - Зворотний зв’язок</title>
    <!-- Заголовок сторінки -->
    <link rel="stylesheet" href="../css/common.css">
    <!-- Підключення основних стилів -->
    <link rel="stylesheet" href="../css/feedback.css">
    <!-- Підключення стилів для сторінки зворотного зв’язку -->
    <link rel="stylesheet" href="../css/responsive.css">
    <!-- Підключення стилів для адаптивного дизайну -->
</head>
<body>
    <button id="theme-toggle" class="theme-toggle">🌙</button>
    <!-- Кнопка перемикання теми -->
    <header>
        <h1>Vinc_Road: Зворотний зв’язок</h1>
        <!-- Заголовок сторінки -->
        <nav>
            <a href="index.php">Головна</a>
            <a href="monitor.php">Моніторинг</a>
            <a href="analytics.php">Аналітика</a>
            <a href="report.php">Повідомити про проблему</a>
            <a href="about.php">Про нас</a>
            <a href="profile.php">Профіль</a>
            <a href="logout.php">Вийти</a>
            <!-- Навігаційне меню -->
        </nav>
    </header>
    <main>
        <section class="chat-section">
            <h2>Чат з адміністрацією</h2>
            <!-- Заголовок секції чату -->
            <p>Напишіть нам, і наша команда відповість вам якомога швидше!</p>
            <!-- Інформаційний текст -->
            <div class="chat-container">
                <div class="chat-messages" id="chat-messages"></div>
                <!-- Контейнер для повідомлень чату -->
                <div class="chat-input">
                    <textarea id="chat-message" rows="4" placeholder="Введіть ваше повідомлення..." required></textarea>
                    <!-- Поле для введення повідомлення -->
                    <button id="send-message" class="btn send-btn">Надіслати</button>
                    <!-- Кнопка відправки повідомлення -->
                </div>
            </div>
        </section>
    </main>
    <footer>
        <p>© 2025 Vinc_Road - Моніторинг інфраструктури України</p>
        <!-- Нижній колонтитул -->
    </footer>
    <script src="../js/theme.js"></script>
    <!-- Підключення скрипту для перемикання теми -->
    <script src="../js/chat.js"></script>
    <!-- Підключення скрипту для роботи чату -->
    <script>
        // Передача user_id і user_name для chat.js
        const currentUser = {
            id: <?php echo json_encode($user_id); ?>,
            name: <?php echo json_encode($user_name); ?>
        };
        // Передача даних користувача для чату
        // Позначка для оновлення лічильника після прочитання
        sessionStorage.setItem('feedbackMessagesRead', 'true');
        // Позначка прочитання повідомлень
    </script>
</body>
</html>