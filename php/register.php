<?php
require_once '../includes/config.php'; // Підключення налаштувань системи
require_once '../includes/db.php'; // Підключення до бази даних
require_once '../includes/auth.php'; // Підключення функцій авторизації

$conn = getDbConnection(); // Створення з’єднання з базою даних
$error = ''; // Змінна для повідомлень про помилки
$message = ''; // Змінна для повідомлень про успіх

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : ''; // Отримання email з форми
    $password = isset($_POST['password']) ? $_POST['password'] : ''; // Отримання пароля
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : ''; // Отримання підтвердження пароля
    $name = isset($_POST['name']) ? trim($_POST['name']) : ''; // Отримання імені
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : ''; // Отримання телефону

    // Валідація
    if (empty($email) || empty($password) || empty($confirm_password) || empty($name) || empty($phone)) {
        $error = "Будь ласка, заповніть усі обов’язкові поля (email, ім’я, телефон, пароль, підтвердження пароля)."; // Помилка, якщо поля порожні
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Некоректний формат email."; // Помилка, якщо email некоректний
    } elseif ($password !== $confirm_password) {
        $error = "Паролі не співпадають."; // Помилка, якщо паролі не співпадають
    } elseif (strlen($password) < 6) {
        $error = "Пароль має бути не коротшим за 6 символів."; // Помилка, якщо пароль закороткий
    } elseif (!preg_match('/^[\x{0400}-\x{04FF}\s-]{1,100}$/u', $name)) {
        $error = "Ім’я має містити лише українські літери, пробіли, дефіси та бути не довшим за 100 символів."; // Помилка, якщо ім’я некоректне
    } elseif (!preg_match('/^\+?\d{10,15}$/', $phone)) {
        $error = "Некоректний формат номера телефону (наприклад, +380123456789)."; // Помилка, якщо телефон некоректний
    } else {
        // Перевірка унікальності email
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?"); // Запит для перевірки email
        $stmt->bind_param("s", $email);
        if (!$stmt->execute()) {
            $error = "Помилка бази даних: " . $stmt->error; // Помилка бази даних
            error_log("Database error in email check: " . $stmt->error);
        } elseif ($stmt->get_result()->num_rows > 0) {
            $error = "Цей email уже зареєстровано."; // Помилка, якщо email зайнятий
        } else {
            // Реєстрація користувача
            $password_hash = password_hash($password, PASSWORD_DEFAULT); // Хешування пароля
            $sql = "INSERT INTO users (email, password_hash, role, name, phone) VALUES (?, ?, 'user', ?, ?)"; // Запит для збереження користувача
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $email, $password_hash, $name, $phone);
            if ($stmt->execute()) {
                $message = "Реєстрація успішна! Ви можете <a href='login.php'>увійти</a>."; // Повідомлення про успіх
            } else {
                $error = "Помилка реєстрації: " . $stmt->error; // Помилка бази даних
                error_log("Database error in registration: " . $stmt->error);
            }
        }
        $stmt->close();
    }
}

$conn->close(); // Закриття з’єднання з базою даних
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8"> <!-- Встановлення кодування сторінки -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Адаптація для мобільних пристроїв -->
    <title>Реєстрація - Vinc_Road</title> <!-- Заголовок сторінки -->
    <link rel="stylesheet" href="../css/common.css"> <!-- Підключення основних стилів -->
    <link rel="stylesheet" href="../css/auth.css"> <!-- Підключення стилів для авторизації -->
    <link rel="stylesheet" href="../css/responsive.css"> <!-- Підключення адаптивних стилів -->
</head>
<body>
    <button id="theme-toggle" class="theme-toggle">🌙</button> <!-- Кнопка зміни теми -->
    <header>
        <h1>Vinc_Road: Реєстрація</h1> <!-- Заголовок сторінки -->
        <nav>
            <a href="index.php">Головна</a> <!-- Посилання на головну сторінку -->
            <a href="monitor.php">Моніторинг</a> <!-- Посилання на моніторинг -->
            <a href="analytics.php">Аналітика</a> <!-- Посилання на аналітику -->
            <a href="report.php">Повідомити про проблему</a> <!-- Посилання для створення звіту -->
            <a href="about.php">Про нас</a> <!-- Посилання на сторінку про проєкт -->
            <?php if (isLoggedIn()): ?>
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
            <h2>Створіть акаунт</h2> <!-- Заголовок секції -->
            <p>Зареєструйтесь, щоб почати повідомляти про проблеми інфраструктури та відстежувати ваші звіти.</p> <!-- Опис -->
        </section>
        <section class="form-section">
            <?php if ($error): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p> <!-- Виведення помилки -->
            <?php endif; ?>
            <?php if ($message): ?>
                <p class="message"><?php echo $message; ?></p> <!-- Виведення повідомлення -->
            <?php endif; ?>
            <form method="POST" id="register-form"> <!-- Форма реєстрації -->
                <div class="form-group">
                    <label for="email">Email *</label> <!-- Мітка для email -->
                    <div class="input-wrapper">
                        <span class="icon">📧</span> <!-- Іконка для поля -->
                        <input type="email" name="email" id="email" required placeholder="Ваш email"> <!-- Поле для email -->
                    </div>
                </div>
                <div class="form-group">
                    <label for="name">Ім'я *</label> <!-- Мітка для імені -->
                    <div class="input-wrapper">
                        <span class="icon">👤</span> <!-- Іконка для поля -->
                        <input type="text" name="name" id="name" required placeholder="Ваше ім'я"> <!-- Поле для імені -->
                    </div>
                </div>
                <div class="form-group">
                    <label for="phone">Номер телефону *</label> <!-- Мітка для телефону -->
                    <div class="input-wrapper">
                        <span class="icon">📱</span> <!-- Іконка для поля -->
                        <input type="text" name="phone" id="phone" required placeholder="Наприклад, +380123456789"> <!-- Поле для телефону -->
                    </div>
                </div>
                <div class="form-group">
                    <label for="password">Пароль *</label> <!-- Мітка для пароля -->
                    <div class="input-wrapper">
                        <span class="icon">🔒</span> <!-- Іконка для поля -->
                        <input type="password" name="password" id="password" required placeholder="Ваш пароль"> <!-- Поле для пароля -->
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Підтвердіть пароль *</label> <!-- Мітка для підтвердження -->
                    <div class="input-wrapper">
                        <span class="icon">🔒</span> <!-- Іконка для поля -->
                        <input type="password" name="confirm_password" id="confirm_password" required placeholder="Підтвердіть пароль"> <!-- Поле для підтвердження -->
                    </div>
                </div>
                <button type="submit" class="btn">Зареєструватися</button> <!-- Кнопка реєстрації -->
            </form>
            <p class="register-link">Вже маєте акаунт? <a href="login.php">Увійдіть</a>.</p> <!-- Посилання на сторінку входу -->
        </section>
    </main>
    <footer>
        <p>© 2025 Vinc_Road - Моніторинг інфраструктури України</p> <!-- Нижній колонтитул -->
    </footer>
    <script src="../js/theme.js"></script> <!-- Підключення скрипта для зміни теми -->
    <script src="../js/register.js"></script> <!-- Підключення скрипта для реєстрації -->
</body>
</html>