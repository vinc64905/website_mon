<?php
require_once '../includes/config.php'; // Підключення налаштувань системи
require_once '../includes/db.php'; // Підключення до бази даних
require_once '../includes/auth.php'; // Підключення функцій авторизації

$conn = getDbConnection(); // Створення з’єднання з базою даних
$error = ''; // Змінна для зберігання повідомлень про помилки

if ($_SERVER['REQUEST_METHOD'] === 'POST') { // Перевірка, чи форма надіслана
    $email = isset($_POST['email']) ? trim($_POST['email']) : ''; // Отримання email з форми
    $password = isset($_POST['password']) ? $_POST['password'] : ''; // Отримання пароля з форми

    if (empty($email) || empty($password)) { // Перевірка, чи заповнені всі поля
        $error = "Будь ласка, заповніть усі поля."; // Повідомлення, якщо поля порожні
    } else {
        if (login($email, $password, $conn)) { // Спроба авторизації
            if (isModerator()) { // Перевірка, чи користувач є модератором
                header("Location: admin.php"); // Перенаправлення модератора на адмін-панель
            } else {
                header("Location: index.php"); // Перенаправлення звичайного користувача на головну
            }
            exit; // Завершення виконання скрипта
        } else {
            $error = "Неправильний email або пароль."; // Повідомлення про помилку авторизації
        }
    }
}

$conn->close(); // Закриття з’єднання з базою даних
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8"> <!-- Встановлення кодування сторінки -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Адаптація для мобільних пристроїв -->
    <title>Вхід - Vinc_Road</title> <!-- Заголовок сторінки -->
    <link rel="stylesheet" href="../css/common.css"> <!-- Підключення основних стилів -->
    <link rel="stylesheet" href="../css/auth.css"> <!-- Підключення стилів для авторизації -->
    <link rel="stylesheet" href="../css/responsive.css"> <!-- Підключення адаптивних стилів -->
</head>
<body>
    <button id="theme-toggle" class="theme-toggle">🌙</button> <!-- Кнопка зміни теми -->
    <header>
        <h1>Vinc_Road: Вхід</h1> <!-- Заголовок сторінки -->
        <nav>
            <a href="index.php">Головна</a> <!-- Посилання на головну сторінку -->
            <a href="monitor.php">Моніторинг</a> <!-- Посилання на сторінку моніторингу -->
            <a href="analytics.php">Аналітика</a> <!-- Посилання на аналітику -->
            <a href="report.php">Повідомити про проблему</a> <!-- Посилання для створення звіту -->
            <a href="about.php">Про нас</a> <!-- Посилання на сторінку про проєкт -->
            <?php if (isLoggedIn()): ?> <!-- Перевірка, чи користувач увійшов -->
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
            <h2>Увійдіть до вашого акаунту</h2> <!-- Заголовок секції -->
            <p>Ввійдіть, щоб почати повідомляти про проблеми інфраструктури та відстежувати ваші звіти.</p> <!-- Опис -->
        </section>
        <section class="form-section">
            <?php if ($error): ?> <!-- Перевірка, чи є помилка -->
                <p class="error"><?php echo htmlspecialchars($error); ?></p> <!-- Виведення повідомлення про помилку -->
            <?php endif; ?>
            <form method="POST"> <!-- Форма для входу -->
                <div class="form-group">
                    <label for="email">Email</label> <!-- Мітка для поля email -->
                    <div class="input-wrapper">
                        <span class="icon">📧</span> <!-- Іконка для поля -->
                        <input type="email" name="email" id="email" required placeholder="Ваш email"> <!-- Поле для email -->
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Пароль</label> <!-- Мітка для поля пароля -->
                    <div class="input-wrapper">
                        <span class="icon">🔒</span> <!-- Іконка для поля -->
                        <input type="password" name="password" id="password" required placeholder="Ваш пароль"> <!-- Поле для пароля -->
                    </div>
                </div>

                <button type="submit" class="btn">Увійти</button> <!-- Кнопка для відправлення форми -->
            </form>
            <p class="register-link">Немає акаунта? <a href="register.php">Зареєструйтесь</a>.</p> <!-- Посилання на реєстрацію -->
        </section>
    </main>
    <footer>
        <p>© 2025 Vinc_Road - Моніторинг інфраструктури України</p> <!-- Нижній колонтитул -->
    </footer>
    <script src="../js/theme.js"></script> <!-- Підключення скрипта для зміни теми -->
</body>
</html>