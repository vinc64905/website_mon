<?php
require_once '../includes/config.php'; // Підключення налаштувань системи
require_once '../includes/db.php'; // Підключення до бази даних
require_once '../includes/auth.php'; // Підключення функцій авторизації

// Запобігання кешуванню сторінки
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0"); // Вимкнення кешування
header("Cache-Control: post-check=0, pre-check=0", false); // Додаткові параметри кешування
header("Pragma: no-cache"); // Вимкнення кешування для старих браузерів
header("Expires: 0"); // Негайне завершення терміну дії сторінки

// Перевірка авторизації
if (!isLoggedIn()) {
    header("Location: login.php"); // Перенаправлення на сторінку входу, якщо користувач не авторизований
    exit;
}

$conn = getDbConnection(); // Створення з’єднання з базою даних
$response = ['success' => false, 'message' => '']; // Ініціалізація відповіді для AJAX

// Список дозволених областей
$allowed_regions = [
    'Вінницька область',
    'Волинська область',
    'Дніпропетровська область',
    'Донецька область',
    'Житомирська область',
    'Закарпатська область',
    'Запорізька область',
    'Івано-Франківська область',
    'Київська область',
    'Кіровоградська область',
    'Луганська область',
    'Львівська область',
    'Миколаївська область',
    'Одеська область',
    'Полтавська область',
    'Рівненська область',
    'Сумська область',
    'Тернопільська область',
    'Харківська область',
    'Херсонська область',
    'Хмельницька область',
    'Черкаська область',
    'Чернівецька область',
    'Чернігівська область'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json'); // Встановлення типу відповіді JSON

    $type = isset($_POST['type']) ? $conn->real_escape_string(trim($_POST['type'])) : ''; // Отримання типу проблеми
    $description = isset($_POST['description']) ? $conn->real_escape_string(trim($_POST['description'])) : ''; // Отримання опису
    $region = isset($_POST['region']) ? $conn->real_escape_string(trim($_POST['region'])) : ''; // Отримання області
    $city = isset($_POST['city']) ? $conn->real_escape_string(trim($_POST['city'])) : ''; // Отримання населеного пункту
    $latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : 0; // Отримання широти
    $longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : 0; // Отримання довготи
    $user_id = $_SESSION['user_id']; // Отримання ID користувача

    // Серверна валідація
    $ukrainianRegex = '/^[\x{0400}-\x{04FF}\s-]+$/u'; // Регулярний вираз для українських літер
    if (!$type) {
        $response['message'] = "Помилка: Оберіть тип проблеми."; // Помилка, якщо тип не вибрано
    } elseif (!in_array($type, ['pothole', 'trash', 'light', 'sign', 'other'])) {
        $response['message'] = "Помилка: Некоректний тип проблеми."; // Помилка, якщо тип некоректний
    } elseif (!$description) {
        $response['message'] = "Помилка: Введіть опис проблеми."; // Помилка, якщо опис порожній
    } elseif (strlen($description) < 10) {
        $response['message'] = "Помилка: Опис має містити щонайменше 10 символів."; // Помилка, якщо опис закороткий
    } elseif (strlen($description) > 500) {
        $response['message'] = "Помилка: Опис не може перевищувати 500 символів."; // Помилка, якщо опис задовгий
    } elseif (!preg_match($ukrainianRegex, $description)) {
        $response['message'] = "Помилка: Опис має містити лише українські літери, пробіли та дефіси."; // Помилка, якщо опис містить некоректні символи
    } elseif (!$region) {
        $response['message'] = "Помилка: Введіть область."; // Помилка, якщо область не вибрано
    } elseif (!in_array($region, $allowed_regions)) {
        $response['message'] = "Помилка: Оберіть область зі списку дозволених."; // Помилка, якщо область некоректна
    } elseif (!$city) {
        $response['message'] = "Помилка: Введіть населений пункт."; // Помилка, якщо населений пункт порожній
    } elseif (!preg_match($ukrainianRegex, $city)) {
        $response['message'] = "Помилка: Населений пункт має містити лише українські літери."; // Помилка, якщо населений пункт містить некоректні символи
    } elseif (strlen($city) > 100) {
        $response['message'] = "Помилка: Населений пункт не може перевищувати 100 символів."; // Помилка, якщо населений пункт задовгий
    } elseif ($latitude < 44 || $latitude > 53 || $longitude < 22 || $longitude > 40) {
        $response['message'] = "Помилка: Координати мають бути в межах України (широта: 44–53, довгота: 22–40)."; // Помилка, якщо координати поза межами України
    } else {
        // Обробка фото
        $photo_path = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif']; // Дозволені типи файлів
            $file_type = mime_content_type($_FILES['photo']['tmp_name']); // Тип файлу
            if (!in_array($file_type, $allowed_types)) {
                $response['message'] = "Помилка: Дозволені лише файли формату JPEG, PNG або GIF."; // Помилка, якщо тип файлу некоректний
            } elseif ($_FILES['photo']['size'] > 5 * 1024 * 1024) {
                $response['message'] = "Помилка: Файл не може перевищувати 5 МБ."; // Помилка, якщо файл завеликий
            } else {
                $photo_name = uniqid() . '-' . basename($_FILES['photo']['name']); // Унікальне ім’я файлу
                $photo_path = 'uploads/' . $photo_name; // Шлях до файлу
                if (!move_uploaded_file($_FILES['photo']['tmp_name'], '../' . $photo_path)) {
                    $response['message'] = "Помилка завантаження фото."; // Помилка завантаження
                }
            }
        }

        // Збереження в базу даних
        if (!$response['message']) {
            $sql = "INSERT INTO reports (user_id, region, city, type, description, latitude, longitude, photo, status)
                    VALUES ($user_id, '$region', '$city', '$type', '$description', $latitude, $longitude, " . ($photo_path ? "'$photo_path'" : "NULL") . ", 'new')"; // Запит для збереження звіту
            if ($conn->query($sql) === TRUE) {
                $response['success'] = true; // Успішне збереження
                $response['message'] = "Звіт успішно надіслано! Перегляньте його у <a href='profile.php'>вашому профілі</a>.";
            } else {
                $response['message'] = "Помилка збереження звіту: " . $conn->error; // Помилка бази даних
                error_log("SQL Error: " . $conn->error);
            }
        }
    }
    if ($response['message']) {
        error_log("Form validation message: {$response['message']} (" . ($response['success'] ? 'success' : 'error') . ")"); // Запис повідомлення в лог
    }
    echo json_encode($response);
    exit;
}

// Якщо не POST, рендеримо форму
$version = time(); // Динамічний таймстемп для уникнення кешування
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8"> <!-- Встановлення кодування сторінки -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Адаптація для мобільних пристроїв -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate"> <!-- Вимкнення кешування -->
    <meta http-equiv="Pragma" content="no-cache"> <!-- Вимкнення кешування для старих браузерів -->
    <meta http-equiv="Expires" content="0"> <!-- Негайне завершення терміну дії -->
    <title>Vinc_Road - Повідомити про проблему</title> <!-- Заголовок сторінки -->
    <link rel="stylesheet" href="../css/common.css?v=<?php echo $version; ?>"> <!-- Підключення основних стилів -->
    <link rel="stylesheet" href="../css/report.css?v=<?php echo $version; ?>"> <!-- Підключення стилів для звіту -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" /> <!-- Підключення стилів Leaflet -->
    <link rel="stylesheet" href="../css/responsive.css"> <!-- Підключення адаптивних стилів -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script> <!-- Підключення бібліотеки Leaflet -->
</head>
<body>
    <button id="theme-toggle" class="theme-toggle">🌙</button> <!-- Кнопка зміни теми -->
    <header>
        <h1>Vinc_Road: Повідомити про проблему</h1> <!-- Заголовок сторінки -->
        <nav>
            <a href="index.php">Головна</a> <!-- Посилання на головну сторінку -->
            <a href="monitor.php">Моніторинг</a> <!-- Посилання на моніторинг -->
            <a href="analytics.php">Аналітика</a> <!-- Посилання на аналітику -->
            <a href="report.php">Повідомити про проблему</a> <!-- Посилання для створення звіту -->
            <a href="about.php">Про нас</a> <!-- Посилання на сторінку про проєкт -->
            <a href="profile.php">Профіль</a> <!-- Посилання на профіль -->
            <a href="logout.php">Вийти</a> <!-- Посилання для виходу -->
        </nav>
    </header>
    <main>
        <section class="intro">
            <h2>Повідомте про проблему</h2> <!-- Заголовок секції -->
            <p>Допоможіть зробити наші міста кращими! Заповніть форму нижче, щоб повідомити про проблему інфраструктури. Ваш звіт буде розглянуто якомога швидше.</p> <!-- Опис -->
        </section>

        <section class="form-section">
            <form id="report-form" method="POST" enctype="multipart/form-data"> <!-- Форма для створення звіту -->
                <div class="form-group">
                    <label for="type">Тип проблеми <span class="required">*</span></label> <!-- Мітка для типу -->
                    <select name="type" id="type"> <!-- Випадаючий список типів -->
                        <option value="">Оберіть тип</option>
                        <option value="pothole">Вибоїна</option>
                        <option value="trash">Сміття</option>
                        <option value="light">Освітлення</option>
                        <option value="sign">Дорожній знак</option>
                        <option value="other">Інше</option>
                    </select>
                    <small class="error-message" id="type-error"></small> <!-- Повідомлення про помилку -->
                    <small class="form-hint">Оберіть категорію проблеми, яку ви хочете повідомити.</small> <!-- Підказка -->
                </div>

                <div class="form-group">
                    <label for="description">Опис <span class="required">*</span></label> <!-- Мітка для опису -->
                    <textarea name="description" id="description" rows="4"></textarea> <!-- Поле для опису -->
                    <small class="error-message" id="description-error"></small> <!-- Повідомлення про помилку -->
                    <small class="form-hint">Опишіть проблему якомога детальніше, використовуючи лише українські літери.</small> <!-- Підказка -->
                </div>

                <div class="form-group">
                    <label for="region">Область <span class="required">*</span></label> <!-- Мітка для області -->
                    <select name="region" id="region"> <!-- Випадаючий список областей -->
                        <option value="">Оберіть область</option>
                        <?php foreach ($allowed_regions as $reg): ?>
                            <option value="<?php echo htmlspecialchars($reg, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($reg, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="error-message" id="region-error"></small> <!-- Повідомлення про помилку -->
                    <small class="form-hint">Вкажіть область, де розташована проблема.</small> <!-- Підказка -->
                </div>

                <div class="form-group">
                    <label for="city">Населений пункт <span class="required">*</span></label> <!-- Мітка для населеного пункту -->
                    <input type="text" name="city" id="city"> <!-- Поле для населеного пункту -->
                    <small class="error-message" id="city-error"></small> <!-- Повідомлення про помилку -->
                    <small class="form-hint">Вкажіть місто або село, де розташована проблема.</small> <!-- Підказка -->
                </div>

                <div class="form-group">
                    <label>Оберіть місце на карті <span class="required">*</span></label> <!-- Мітка для карти -->
                    <div id="map" style="height: 400px;"></div> <!-- Контейнер для карти -->
                    <small class="error-message" id="map-error"></small> <!-- Повідомлення про помилку -->
                    <div id="location-loader" style="display: none; text-align: center; margin-top: 0.5rem;">
                        <span>Завантаження...</span> <!-- Індикатор завантаження -->
                    </div>
                    <input type="hidden" name="latitude" id="latitude"> <!-- Приховане поле для широти -->
                    <input type="hidden" name="longitude" id="longitude"> <!-- Приховане поле для довготи -->
                    <small class="form-hint">Клікніть на карті, щоб обрати точне місце проблеми.</small> <!-- Підказка -->
                </div>

                <div class="form-group">
                    <label for="photo">Фото (опціонально)</label> <!-- Мітка для фото -->
                    <input type="file" name="photo" id="photo" accept="image/jpeg,image/png,image/gif"> <!-- Поле для фото -->
                    <small class="error-message" id="photo-error"></small> <!-- Повідомлення про помилку -->
                    <small class="form-hint">Додайте фото, щоб краще проілюструвати проблему (JPEG, PNG, GIF).</small> <!-- Підказка -->
                </div>

                <small class="error-message" id="form-error"></small> <!-- Загальне повідомлення про помилку -->
                <button type="submit" class="btn submit-btn">Надіслати звіт</button> <!-- Кнопка відправлення -->
            </form>
        </section>

        <section class="instructions">
            <h3>Як подати звіт?</h3> <!-- Заголовок інструкцій -->
            <ol>
                <li><strong>Оберіть тип проблеми:</strong> Вкажіть категорію, до якої відноситься проблема (наприклад, вибоїна, сміття).</li>
                <li><strong>Опишіть проблему:</strong> Детально розкажіть, що саме вас турбує, використовуючи українські літери.</li>
                <li><strong>Вкажіть місце:</strong> Оберіть область і населений пункт, а потім вкажіть точне місце на карті.</li>
                <li><strong>Додайте фото (за бажанням):</strong> Завантажте фото, щоб допомогти швидше ідентифікувати проблему.</li>
                <li><strong>Надішліть звіт:</strong> Натисніть "Надіслати звіт", і ми розглянемо вашу заявку якомога швидше.</li>
            </ol>
        </section>
    </main>
    <footer>
        <p>© 2025 Vinc_Road - Моніторинг інфраструктури України</p> <!-- Нижній колонтитул -->
    </footer>
    <script src="../js/form.js?v=<?php echo $version; ?>"></script> <!-- Підключення скрипта для форми -->
    <script src="../js/theme.js?v=<?php echo $version; ?>"></script> <!-- Підключення скрипта для зміни теми -->
</body>
</html>