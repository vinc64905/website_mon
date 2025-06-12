<?php
// Підключення файлу з налаштуваннями
require_once '../includes/config.php';
// Підключення модуля для роботи з базою даних
require_once '../includes/db.php';
// Підключення модуля для автентифікації користувачів
require_once '../includes/auth.php';

// Створення з'єднання з базою даних
$conn = getDbConnection();
// Закриття з'єднання з базою даних
$conn->close();
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <!-- Встановлення кодування для коректного відображення кирилиці -->
    <meta charset="UTF-8">
    <!-- Налаштування адаптивності для мобільних пристроїв -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Назва сторінки в браузері -->
    <title>Vinc_Road - Про нас</title>
    <!-- Підключення стилів для оформлення сторінки -->
    <link rel="stylesheet" href="../css/common.css">
    <link rel="stylesheet" href="../css/about.css">
    <link rel="stylesheet" href="../css/responsive.css">
</head>
<body>
    <!-- Кнопка для зміни теми (світла/темна) -->
    <button id="theme-toggle" class="theme-toggle">🌙</button>
    <header>
        <!-- Основний заголовок сторінки -->
        <h1>Vinc_Road: Про нас</h1>
        <!-- Навігаційне меню для переходу між сторінками -->
        <nav>
            <a href="index.php">Головна</a>
            <a href="monitor.php">Моніторинг</a>
            <a href="analytics.php">Аналітика</a>
            <a href="report.php">Повідомити про проблему</a>
            <a href="about.php">Про нас</a>
            <!-- Умовне відображення пунктів меню залежно від автентифікації -->
            <?php if (isLoggedIn()): ?>
                <a href="profile.php">Профіль</a>
                <a href="logout.php">Вийти</a>
            <?php else: ?>
                <a href="login.php">Вхід</a>
                <a href="register.php">Реєстрація</a>
            <?php endif; ?>
        </nav>
    </header>
    <main>
        <!-- Секція з описом проєкту -->
        <section class="intro">
            <h2>Про Vinc_Road</h2>
            <p>Платформа <strong>Vinc_Road</strong> допомагає покращувати міську інфраструктуру України через співпрацю громадян і влади.</p>
            <div class="text-block">
                <h4>Концепція</h4>
                <p>Забезпечує відкритий діалог між громадянами та органами влади.</p>
            </div>
            <div class="text-block">
                <h4>Механізм роботи</h4>
                <ul>
                    <li>Подання звіту через форму.</li>
                    <li>Відстеження статусу звіту.</li>
                    <li>Впровадження змін.</li>
                </ul>
            </div>
            <p>Запрошення громадян долучитися до покращення інфраструктури.</p>
        </section>

        <!-- Секція з фотогалереєю -->
        <section class="gallery">
            <h3>Результати роботи</h3>
            <p>Фотографії проблем та їх вирішення.</p>
            <div class="gallery-grid">
                <div class="gallery-item">
                    <img src="../images/pothole.jpg" alt="Вибоїна на дорозі">
                    <p>Вибоїна на дорозі</p>
                </div>
                <div class="gallery-item">
                    <img src="../images/trash.jpg" alt="Сміття на вулиці">
                    <p>Сміття на вулиці</p>
                </div>
                <div class="gallery-item">
                    <img src="../images/light.jpg" alt="Несправне освітлення">
                    <p>Несправне освітлення</p>
                </div>
                <div class="gallery-item">
                    <img src="../images/resolved.jpg" alt="Відремонтована дорога">
                    <p>Відремонтована дорога</p>
                </div>
            </div>
        </section>

        <!-- Секція з досягненнями -->
        <section class="achievements">
            <h3>Досягнення</h3>
            <p>Результати співпраці з громадами.</p>
            <div class="achievements-content">
                <div class="achievements-text">
                    <div class="text-block">
                        <h4>Статистика</h4>
                        <ul>
                            <li>Понад 10,000 звітів.</li>
                            <li>Понад 6,000 вирішених проблем.</li>
                            <li>Охоплення 24 областей.</li>
                        </ul>
                    </div>
                    <div class="text-block">
                        <h4>Вплив</h4>
                        <p>Покращення інфраструктури завдяки звітам громадян.</p>
                    </div>
                </div>
                <div class="achievements-image">
                    <img src="../images/achievements.jpg" alt="Досягнення Vinc_Road">
                </div>
            </div>
        </section>

        <!-- Секція з місією проєкту -->
        <section class="mission">
            <h3>Місія</h3>
            <p>Створення сучасних і комфортних міст України.</p>
            <div class="mission-content">
                <div class="mission-text">
                    <div class="text-block">
                        <h4>Прозорість</h4>
                        <p>Відкритість інформації про звіти та їх статуси.</p>
                    </div>
                    <div class="text-block">
                        <h4>Ефективність</h4>
                        <p>Швидке реагування на проблеми спільно з владою.</p>
                    </div>
                </div>
                <div class="mission-image">
                    <img src="../images/ukraine_map.jpg" alt="Карта України">
                </div>
            </div>
            <p>Запрошення громадян приєднатися до місії.</p>
        </section>

        <!-- Секція з інформацією про команду -->
        <section class="team">
            <h3>Команда</h3>
            <p>Фахівці, які поєднують технології та громадську активність.</p>
            <div class="text-block">
                <h4>Цінності</h4>
                <ul>
                    <li>Інновації: використання сучасних технологій.</li>
                    <li>Відкритість: урахування думок користувачів.</li>
                    <li>Відповідальність: важливість кожного звіту.</li>
                </ul>
            </div>
            <div class="team-image">
                <img src="../images/team.jpg" alt="Команда Vinc_Road">
            </div>
        </section>

        <!-- Секція з контактною інформацією -->
        <section class="contact-info">
            <h3>Контакти</h3>
            <p>Дані для зв’язку та співпраці.</p>
            <div class="contact-content">
                <div class="contact-details">
                    <div class="text-block">
                        <h4>Дані</h4>
                        <ul>
                            <li><strong>Email:</strong> <a href="mailto:info@vincroad.ua">info@vincroad.ua</a></li>
                            <li><strong>Телефон:</strong> <a href="tel:+380123456789">+380 123 456 789</a></li>
                            <li><strong>Адреса:</strong> вул. Центральна, 1, м. Київ</li>
                        </ul>
                    </div>
                    <!-- Кнопка для зворотного зв’язку (доступна автентифікованим користувачам) -->
                    <?php if (isLoggedIn()): ?>
                        <a href="feedback.php" class="btn feedback-btn">Зворотний зв’язок</a>
                    <?php endif; ?>
                </div>
            </div>
            <p>Готовність відповісти на запити.</p>
        </section>
    </main>
    <footer>
        <!-- Інформація про авторські права -->
        <p>© 2025 Vinc_Road - Моніторинг інфраструктури України</p>
    </footer>
    <!-- Підключення скрипта для зміни теми -->
    <script src="../js/theme.js"></script>
</body>
</html>