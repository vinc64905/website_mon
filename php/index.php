<?php
require_once '../includes/config.php'; // Підключення налаштувань системи
require_once '../includes/db.php'; // Підключення до бази даних
require_once '../includes/auth.php'; // Підключення функцій авторизації

$conn = getDbConnection(); // Створення з’єднання з базою даних

// Отримання статистики
$total_query = "SELECT COUNT(*) as total FROM reports"; // Запит для підрахунку всіх звітів
$new_query = "SELECT COUNT(*) as new FROM reports WHERE status = 'new'"; // Запит для нових звітів
$in_progress_query = "SELECT COUNT(*) as in_progress FROM reports WHERE status = 'in_progress'"; // Запит для звітів в обробці
$resolved_query = "SELECT COUNT(*) as resolved FROM reports WHERE status = 'resolved'"; // Запит для вирішених звітів
$users_query = "SELECT COUNT(*) as users FROM users"; // Запит для підрахунку користувачів
$regions_query = "SELECT COUNT(DISTINCT region) as regions FROM reports"; // Запит для підрахунку охоплених регіонів

$total_result = $conn->query($total_query); // Виконання запиту для всіх звітів
$new_result = $conn->query($new_query); // Виконання запиту для нових звітів
$in_progress_result = $conn->query($in_progress_query); // Виконання запиту для звітів в обробці
$resolved_result = $conn->query($resolved_query); // Виконання запиту для вирішених звітів
$users_result = $conn->query($users_query); // Виконання запиту для користувачів
$regions_result = $conn->query($regions_query); // Виконання запиту для регіонів

$total = $total_result->fetch_assoc()['total']; // Збереження кількості всіх звітів
$new = $new_result->fetch_assoc()['new']; // Збереження кількості нових звітів
$in_progress = $in_progress_result->fetch_assoc()['in_progress']; // Збереження кількості звітів в обробці
$resolved = $resolved_result->fetch_assoc()['resolved']; // Збереження кількості вирішених звітів
$users = $users_result->fetch_assoc()['users']; // Збереження кількості користувачів
$regions = $regions_result->fetch_assoc()['regions']; // Збереження кількості регіонів

$conn->close(); // Закриття з’єднання з базою даних
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8"> <!-- Встановлення кодування сторінки -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Адаптація для мобільних пристроїв -->
    <title>Vinc_Road - Моніторинг проблем інфраструктури</title> <!-- Заголовок сторінки -->
    <link rel="stylesheet" href="../css/common.css"> <!-- Підключення основних стилів -->
    <link rel="stylesheet" href="../css/index.css"> <!-- Підключення стилів для головної сторінки -->
    <link rel="stylesheet" href="../css/responsive.css"> <!-- Підключення адаптивних стилів -->
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
        <section class="hero">
            <h2>Зробимо міста України кращими разом!</h2> <!-- Заголовок секції -->
            <p>Vinc_Road допомагає громадянам повідомляти про проблеми інфраструктури та сприяє їх швидкому вирішенню.</p> <!-- Опис -->
            <a href="report.php" class="btn">Повідомити про проблему</a> <!-- Кнопка для створення звіту -->
        </section>

        <section class="about">
            <h2>Про Vinc_Road</h2> <!-- Заголовок секції -->
            <p>Компанія <strong>Vinc_Road</strong> створена з метою покращення інфраструктури населених пунктів України. Ми надаємо платформу, яка дозволяє громадянам швидко повідомляти про проблеми, такі як вибоїни, сміття, несправне освітлення чи дорожні знаки, а владі та організаціям – ефективно реагувати на ці виклики.</p> <!-- Опис проєкту -->
            <p>Наша місія – зробити міста комфортнішими та безпечнішими для всіх мешканців завдяки сучасним технологіям та спільним зусиллям громади.</p> <!-- Місія проєкту -->
        </section>

        <section class="stats">
            <h2>Статистика роботи Vinc_Road</h2> <!-- Заголовок секції -->
            <div class="stats-grid">
                <div class="stat-item">
                    <h3><?php echo htmlspecialchars($total); ?></h3> <!-- Виведення загальної кількості звітів -->
                    <p>Загальна кількість повідомлень</p> <!-- Опис -->
                </div>
                <div class="stat-item">
                    <h3><?php echo htmlspecialchars($new); ?></h3> <!-- Виведення кількості нових звітів -->
                    <p>Нові звіти</p> <!-- Опис -->
                </div>
                <div class="stat-item">
                    <h3><?php echo htmlspecialchars($in_progress); ?></h3> <!-- Виведення кількості звітів в обробці -->
                    <p>В обробці</p> <!-- Опис -->
                </div>
                <div class="stat-item">
                    <h3><?php echo htmlspecialchars($resolved); ?></h3> <!-- Виведення кількості вирішених звітів -->
                    <p>Вирішені</p> <!-- Опис -->
                </div>
                <div class="stat-item">
                    <h3><?php echo htmlspecialchars($users); ?></h3> <!-- Виведення кількості користувачів -->
                    <p>Зареєстрованих користувачів</p> <!-- Опис -->
                </div>
                <div class="stat-item">
                    <h3><?php echo htmlspecialchars($regions); ?></h3> <!-- Виведення кількості регіонів -->
                    <p>Областей охоплено</p> <!-- Опис -->
                </div>
            </div>
        </section>

        <section class="features">
            <h2>Чому обирають Vinc_Road?</h2> <!-- Заголовок секції -->
            <div class="features-grid">
                <div class="feature-item">
                    <h3>Зручність</h3> <!-- Перевага -->
                    <p>Повідомляйте про проблеми у кілька кліків з будь-якого пристрою.</p> <!-- Опис -->
                </div>
                <div class="feature-item">
                    <h3>Прозорість</h3> <!-- Перевага -->
                    <p>Відстежуйте статус ваших звітів у реальному часі.</p> <!-- Опис -->
                </div>
                <div class="feature-item">
                    <h3>Ефективність</h3> <!-- Перевага -->
                    <p>Допомагаємо владі швидко реагувати на проблеми.</p> <!-- Опис -->
                </div>
                <div class="feature-item">
                    <h3>Спільнота</h3> <!-- Перевага -->
                    <p>Приєднуйтесь до тисяч користувачів, які змінюють міста на краще.</p> <!-- Опис -->
                </div>
            </div>
        </section>

        <section class="cta">
            <h2>Приєднуйтесь до Vinc_Road!</h2> <!-- Заголовок секції -->
            <p>Станьте частиною руху за покращення інфраструктури України. Разом ми можемо зробити наші міста кращими!</p> <!-- Опис -->
            <?php if (!isset($_SESSION['user_id'])): ?> <!-- Перевірка, чи користувач не увійшов -->
                <a href="register.php" class="btn">Зареєструватися</a> <!-- Кнопка для реєстрації -->
            <?php else: ?>
                <a href="report.php" class="btn">Повідомити про проблему</a> <!-- Кнопка для створення звіту -->
            <?php endif; ?>
        </section>
    </main>
    <footer>
        <p>© 2025 Vinc_Road - Моніторинг інфраструктури України</p> <!-- Нижній колонтитул -->
    </footer>
    <script src="../js/theme.js"></script> <!-- Підключення скрипта для зміни теми -->
</body>
</html>