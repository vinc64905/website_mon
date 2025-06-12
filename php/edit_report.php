<?php
require_once '../includes/config.php';
// Підключення налаштувань системи
require_once '../includes/db.php';
// Підключення модуля для роботи з базою даних
require_once '../includes/auth.php';
// Підключення модуля для перевірки авторизації

// Перевірка авторизації
if (!isLoggedIn()) {
    // Перевірка, чи користувач авторизований
    header("Location: login.php");
    // Перенаправлення на сторінку входу
    exit;
    // Завершення виконання
}

$conn = getDbConnection();
// Створення з’єднання з базою даних
$user_id = $_SESSION['user_id'];
// Отримання ID користувача з сесії
$error = '';
// Змінна для зберігання помилок
$message = '';
// Змінна для зберігання повідомлень

// Перевірка ID звіту
$report_id = (int)($_GET['id'] ?? 0);
// Отримання ID звіту з параметрів
if (!$report_id) {
    // Перевірка, чи передано ID
    header("Location: profile.php");
    // Перенаправлення до профілю
    exit;
    // Завершення виконання
}

// Отримання даних звіту
$stmt = $conn->prepare("SELECT * FROM reports WHERE id = ? AND user_id = ?");
// Підготовка запиту для отримання звіту
$stmt->bind_param("ii", $report_id, $user_id);
// Прив’язка параметрів ID звіту та користувача
if (!$stmt->execute()) {
    // Виконання запиту
    $error = "Помилка отримання звіту: " . $stmt->error;
    // Збереження помилки
}
$report = $stmt->get_result()->fetch_assoc();
// Отримання даних звіту
$stmt->close();
// Закриття запиту

if (!$report) {
    // Перевірка, чи знайдено звіт
    header("Location: profile.php");
    // Перенаправлення до профілю
    exit;
    // Завершення виконання
}

// Перевірка 2-хвилинного вікна
$created_time = strtotime($report['created_at']);
// Час створення звіту
$current_time = time();
// Поточний час
if (($current_time - $created_time) > 120) {
    // Перевірка, чи пройшло більше 2 хвилин
    $error = "Редагувати звіт можна лише протягом 2 хвилин після створення.";
    // Повідомлення про закінчення часу редагування
}

// Обробка редагування звіту
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_report'])) {
    // Перевірка, чи форма відправлена
    if (($current_time - $created_time) > 120) {
        // Повторна перевірка часу редагування
        $error = "Час для редагування звіту минув.";
        // Повідомлення про закінчення часу
    } else {
        $type = trim($_POST['type'] ?? '');
        // Отримання типу звіту
        $description = trim($_POST['description'] ?? '');
        // Отримання опису
        $region = trim($_POST['region'] ?? '');
        // Отримання області
        $city = trim($_POST['city'] ?? '');
        // Отримання населеного пункту
        $latitude = (float)($_POST['latitude'] ?? 0);
        // Отримання широти
        $longitude = (float)($_POST['longitude'] ?? 0);
        // Отримання довготи
        $photo = $_FILES['photo'] ?? null;
        // Отримання файлу фото

        // Валідація
        $allowed_types = ['pothole', 'trash', 'light', 'sign', 'other'];
        // Дозволені типи звітів
        $allowed_regions = [
            'Вінницька область', 'Волинська область', 'Дніпропетровська область', 'Донецька область',
            'Житомирська область', 'Закарпатська область', 'Запорізька область', 'Івано-Франківська область',
            'Київська область', 'Кіровоградська область', 'Луганська область', 'Львівська область',
            'Миколаївська область', 'Одеська область', 'Полтавська область', 'Рівненська область',
            'Сумська область', 'Тернопільська область', 'Харківська область', 'Херсонська область',
            'Хмельницька область', 'Черкаська область', 'Чернівецька область', 'Чернігівська область'
        ];
        // Дозволені області
        $ukrainian_regex = '/^[\x{0400}-\x{04FF}\s-]+$/u';
        // Регулярний вираз для українських символів

        if (!in_array($type, $allowed_types)) {
            // Перевірка типу звіту
            $error = "Некоректний тип звіту.";
            // Помилка: некоректний тип
        } elseif (!$description || strlen($description) < 10 || strlen($description) > 500 || !preg_match($ukrainian_regex, $description)) {
            // Перевірка опису
            $error = "Опис має бути 10–500 символів і містити лише українські літери, пробіли та дефіси.";
            // Помилка: некоректний опис
        } elseif (!$region || !in_array($region, $allowed_regions)) {
            // Перевірка області
            $error = "Оберіть коректну область зі списку.";
            // Помилка: некоректна область
        } elseif (!$city || !preg_match($ukrainian_regex, $city) || strlen($city) > 100) {
            // Перевірка населеного пункту
            $error = "Населений пункт має містити лише українські літери і бути не довшим за 100 символів.";
            // Помилка: некоректний населений пункт
        } elseif ($latitude < 44 || $latitude > 53 || $longitude < 22 || $longitude > 40) {
            // Перевірка координат
            $error = "Координати мають бути в межах України.";
            // Помилка: некоректні координати
        } else {
            // Обробка фото
            $photo_path = $report['photo'];
            // Збереження поточного шляху до фото
            if ($photo && $photo['size'] > 0) {
                // Перевірка, чи завантажено нове фото
                $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];
                // Дозволені формати фото
                $max_size = 5 * 1024 * 1024; // 5 MB
                // Максимальний розмір файлу
                if (!in_array($photo['type'], $allowed_mime_types)) {
                    // Перевірка формату фото
                    $error = "Дозволені лише файли JPEG, PNG або GIF.";
                    // Помилка: некоректний формат
                } elseif ($photo['size'] > $max_size) {
                    // Перевірка розміру фото
                    $error = "Файл не може перевищувати 5 МБ.";
                    // Помилка: завеликий файл
                } else {
                    $upload_dir = '../Uploads/';
                    // Папка для збереження фото
                    $db_upload_dir = 'Uploads/';
                    // Шлях для бази даних
                    if (!is_dir($upload_dir)) {
                        // Перевірка існування папки
                        mkdir($upload_dir, 0755, true);
                        // Створення папки
                    }
                    $photo_name = uniqid() . '_' . basename($photo['name']);
                    // Унікальне ім’я файлу
                    $photo_path = $db_upload_dir . $photo_name;
                    // Шлях для бази даних
                    $file_path = $upload_dir . $photo_name;
                    // Шлях для збереження файлу
                    if (!move_uploaded_file($photo['tmp_name'], $file_path)) {
                        // Переміщення файлу
                        $error = "Помилка завантаження фото.";
                        // Помилка: невдале завантаження
                        error_log("Failed to upload photo for report_id: $report_id");
                        // Логування помилки
                    }
                }
            }

            if (!$error) {
                // Оновлення звіту
                $stmt = $conn->prepare("UPDATE reports SET type = ?, description = ?, region = ?, city = ?, latitude = ?, longitude = ?, photo = ? WHERE id = ? AND user_id = ?");
                // Підготовка запиту для оновлення звіту
                $stmt->bind_param("ssssddssi", $type, $description, $region, $city, $latitude, $longitude, $photo_path, $report_id, $user_id);
                // Прив’язка параметрів
                if ($stmt->execute()) {
                    // Виконання запиту
                    header("Location: profile.php?success=report_updated");
                    // Перенаправлення з повідомленням про успіх
                    exit;
                    // Завершення виконання
                } else {
                    $error = "Помилка оновлення звіту: " . $stmt->error;
                    // Помилка: невдале оновлення
                    error_log("Database error in edit_report: " . $stmt->error);
                    // Логування помилки
                }
                $stmt->close();
                // Закриття запиту
            }
        }
    }
}

$types = [
    'pothole' => 'Вибоїна',
    'trash' => 'Сміття',
    'light' => 'Освітлення',
    'sign' => 'Дорожній знак',
    'other' => 'Інше'
];
// Список типів звітів
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <!-- Встановлення кодування сторінки -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Адаптація для мобільних пристроїв -->
    <title>Редагувати звіт - Vinc_Road</title>
    <!-- Заголовок сторінки -->

    <link rel="stylesheet" href="../css/edit_report.css">
    <!-- Підключення стилів для сторінки редагування -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Підключення стилів для карти -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Підключення іконок -->
    <link rel="stylesheet" href="../css/responsive.css">
    <!-- Підключення стилів для адаптивного дизайну -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Підключення скрипту для роботи з картою -->
</head>
<body>
    <button id="theme-toggle" class="theme-toggle">🌙</button>
    <!-- Кнопка перемикання теми -->
    <header>
        <h1>Vinc_Road: Редагувати звіт</h1>
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
        <section class="edit-report-section">
            <h2>Редагувати звіт</h2>
            <!-- Заголовок секції редагування -->
            <div id="messages">
                <?php if ($error): ?>
                    <p class="error"><?php echo htmlspecialchars($error); ?></p>
                    <!-- Відображення помилок -->
                <?php endif; ?>
                <?php if ($message): ?>
                    <p class="message"><?php echo htmlspecialchars($message); ?></p>
                    <!-- Відображення повідомлень -->
                <?php endif; ?>
            </div>
            <?php if (($current_time - $created_time) <= 120): ?>
                <!-- Перевірка, чи доступне редагування -->
                <form method="POST" enctype="multipart/form-data" id="report-form" class="form-section">
                    <!-- Форма для редагування звіту -->
                    <input type="hidden" name="edit_report" value="1">
                    <!-- Приховане поле для позначки редагування -->
                    <div class="form-subsection">
                        <h3>Інформація про проблему</h3>
                        <!-- Секція інформації про проблему -->
                        <div class="form-group">
                            <label for="type">Тип проблеми</label>
                            <!-- Поле вибору типу проблеми -->
                            <select name="type" id="type" required>
                                <option value="">Оберіть тип</option>
                                <?php foreach ($types as $key => $label): ?>
                                    <option value="<?php echo $key; ?>" <?php echo $report['type'] === $key ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                    <!-- Список типів проблем -->
                                <?php endforeach; ?>
                            </select>
                            <span id="type-error" class="error-message"></span>
                            <!-- Поле для помилок типу -->
                        </div>
                        <div class="form-group">
                            <label for="description">Опис проблеми</label>
                            <!-- Поле введення опису -->
                            <textarea name="description" id="description" required placeholder="Опишіть проблему (10–500 символів)"><?php echo htmlspecialchars($report['description'] ?? ''); ?></textarea>
                            <!-- Текстове поле для опису -->
                            <span id="description-error" class="error-message"></span>
                            <!-- Поле для помилок опису -->
                        </div>
                    </div>
                    <div class="form-subsection">
                        <h3>Розташування</h3>
                        <!-- Секція розташування -->
                        <div class="form-group form-group-inline">
                            <div class="form-group">
                                <label for="region">Область</label>
                                <!-- Поле вибору області -->
                                <select name="region" id="region" required>
                                    <option value="">Оберіть область</option>
                                    <?php
                                    $allowed_regions = [
                                        'Вінницька область', 'Волинська область', 'Дніпропетровська область', 'Донецька область',
                                        'Житомирська область', 'Закарпатська область', 'Запорізька область', 'Івано-Франківська область',
                                        'Київська область', 'Кіровоградська область', 'Луганська область', 'Львівська область',
                                        'Миколаївська область', 'Одеська область', 'Полтавська область', 'Рівненська область',
                                        'Сумська область', 'Тернопільська область', 'Харківська область', 'Херсонська область',
                                        'Хмельницька область', 'Черкаська область', 'Чернівецька область', 'Чернігівська область'
                                    ];
                                    foreach ($allowed_regions as $reg): ?>
                                        <option value="<?php echo $reg; ?>" <?php echo $report['region'] === $reg ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($reg); ?>
                                        </option>
                                        <!-- Список областей -->
                                    <?php endforeach; ?>
                                </select>
                                <span id="region-error" class="error-message"></span>
                                <!-- Поле для помилок області -->
                            </div>
                            <div class="form-group">
                                <label for="city">Населений пункт</label>
                                <!-- Поле введення населеного пункту -->
                                <input type="text" name="city" id="city" value="<?php echo htmlspecialchars($report['city'] ?? ''); ?>" required placeholder="Введіть населений пункт">
                                <!-- Текстове поле для міста -->
                                <span id="city-error" class="error-message"></span>
                                <!-- Поле для помилок міста -->
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="map">Місце на карті</label>
                            <!-- Поле для карти -->
                            <div id="map" style="height: 400px; position: relative;">
                                <div id="map-loader" class="map-loader"><i class="fas fa-spinner fa-spin"></i> Завантаження...</div>
                                <!-- Контейнер для карти -->
                            </div>
                            <input type="hidden" name="latitude" id="latitude" value="<?php echo htmlspecialchars($report['latitude'] ?? ''); ?>">
                            <!-- Приховане поле для широти -->
                            <input type="hidden" name="longitude" id="longitude" value="<?php echo htmlspecialchars($report['longitude'] ?? ''); ?>">
                            <!-- Приховане поле для довготи -->
                            <span id="map-error" class="error-message"></span>
                            <!-- Поле для помилок карти -->
                        </div>
                    </div>
                    <div class="form-subsection">
                        <h3>Фото</h3>
                        <!-- Секція фото -->
                        <div class="form-group">
                            <label for="photo">Фото (необов’язково)</label>
                            <!-- Поле завантаження фото -->
                            <input type="file" name="photo" id="photo" accept="image/jpeg,image/png,image/gif">
                            <!-- Поле для вибору файлу -->
                            <?php if ($report['photo']): ?>
                                <p class="current-photo">Поточне фото: <img src="../<?php echo htmlspecialchars(str_replace('../', '', $report['photo'])); ?>" alt="Фото" style="max-width: 100px; border-radius: 8px;"></p>
                                <!-- Відображення поточного фото -->
                            <?php endif; ?>
                            <span id="photo-error" class="error-message"></span>
                            <!-- Поле для помилок фото -->
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn submit-btn">Зберегти зміни</button>
                        <!-- Кнопка збереження -->
                        <a href="profile.php" class="btn cancel-btn">Скасувати</a>
                        <!-- Кнопка скасування -->
                    </div>
                </form>
            <?php else: ?>
                <p class="error">Редагувати звіт можна лише протягом 2 хвилин після створення.</p>
                <!-- Повідомлення про закінчення часу редагування -->
                <a href="profile.php" class="btn cancel-btn">Повернутися до профілю</a>
                <!-- Кнопка повернення до профілю -->
            <?php endif; ?>
        </section>
    </main>
    <footer>
        <p>© 2025 Vinc_Road - Моніторинг інфраструктури України</p>
        <!-- Нижній колонтитул -->
    </footer>
    <script src="../js/theme.js"></script>
    <!-- Підключення скрипту для перемикання теми -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Ініціалізація карти
            const map = L.map('map').setView([<?php echo $report['latitude'] ?: 48.3794; ?>, <?php echo $report['longitude'] ?: 31.1656; ?>], 10);
            // Створення карти з початковими координатами
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                minZoom: 6,
                maxZoom: 18,
                maxBounds: [[44, 22], [53, 40]],
                maxBoundsViscosity: 1.0
            }).addTo(map);
            // Додавання шару карти з обмеженнями

            // Додавання маркера
            let marker = L.marker([<?php echo $report['latitude'] ?: 48.3794; ?>, <?php echo $report['longitude'] ?: 31.1656; ?>], { draggable: true }).addTo(map);
            // Створення пересувного маркера
            marker.on('dragend', function () {
                // Обробка перетягування маркера
                const position = marker.getLatLng();
                // Отримання нових координат
                document.getElementById('latitude').value = position.lat.toFixed(6);
                // Оновлення широти
                document.getElementById('longitude').value = position.lng.toFixed(6);
                // Оновлення довготи
                debouncedFetchLocationDetails(position.lat, position.lng);
                // Запит деталей місця
            });

            map.on('click', function (e) {
                // Обробка кліку по карті
                const lat = e.latlng.lat;
                const lng = e.latlng.lng;
                // Отримання координат кліку
                if (lat >= 44 && lat <= 53 && lng >= 22 && lng <= 40) {
                    // Перевірка, чи координати в межах України
                    marker.setLatLng(e.latlng);
                    // Переміщення маркера
                    document.getElementById('latitude').value = lat.toFixed(6);
                    // Оновлення широти
                    document.getElementById('longitude').value = lng.toFixed(6);
                    // Оновлення довготи
                    debouncedFetchLocationDetails(lat, lng);
                    // Запит деталей місця
                } else {
                    showError('map', 'Будь ласка, оберіть місце в межах України.');
                    // Помилка: некоректні координати
                }
            });

            // Словник для заміни російських назв
            const nameTranslations = {
                'Киевская область': 'Київська область',
                'Киев': 'Київ',
                'Львовская область': 'Львівська область',
                'Львов': 'Львів',
                'Одесская область': 'Одеська область',
                'Одесса': 'Одеса',
                'Харьковская область': 'Харківська область',
                'Харьков': 'Харків',
                'Днепропетровская область': 'Дніпропетровська область',
                'Днепр': 'Дніпро'
            };
            // Переклад російських назв

            // Список дозволених областей
            const allowedRegions = <?php echo json_encode($allowed_regions); ?>;
            // Список областей для валідації

            // Функція для показу помилок
            function showError(fieldId, message) {
                const errorElement = document.getElementById(`${fieldId}-error`);
                if (errorElement) {
                    errorElement.textContent = message;
                    // Встановлення тексту помилки
                    errorElement.style.display = 'block';
                    // Показ помилки
                    errorElement.classList.add('error-visible');
                    // Додавання стилю видимості
                    setTimeout(() => {
                        errorElement.classList.remove('error-visible');
                        errorElement.style.display = 'none';
                        errorElement.textContent = '';
                    }, 5000);
                    // Приховання помилки через 5 секунд
                }
            }

            // Функція для очищення помилок
            function clearErrors() {
                document.querySelectorAll('.error-message').forEach(el => {
                    el.classList.remove('error-visible');
                    el.style.display = 'none';
                    el.textContent = '';
                });
                // Очищення всіх помилок
            }

            // Debounce для запитів до Nominatim
            function debounce(func, wait) {
                let timeout;
                return function (...args) {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => func.apply(this, args), wait);
                };
                // Затримка виконання функції
            }

            // Отримання деталей місця
            async function fetchLocationDetails(lat, lng) {
                const loader = document.getElementById('map-loader');
                if (loader) loader.style.display = 'block';
                // Показ завантаження
                try {
                    const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1&accept-language=uk`);
                    // Запит до Nominatim
                    const data = await response.json();
                    // Отримання даних
                    const ukrainianRegex = /^[\u0400-\u04FF\s-]+$/;
                    // Регулярний вираз для українських символів

                    if (data && data.address) {
                        // Перевірка наявності адреси
                        let region = data.address.state || data.address.region || '';
                        let city = data.address.city || data.address.town || data.address.village || '';
                        // Отримання області та міста

                        region = nameTranslations[region] || region;
                        city = nameTranslations[city] || city;
                        // Переклад назв

                        let errors = [];
                        if (region && !allowedRegions.includes(region)) {
                            region = '';
                            errors.push('області');
                        }
                        if (city && !ukrainianRegex.test(city)) {
                            city = '';
                            errors.push('населеного пункту');
                        }
                        // Валідація регіону та міста

                        if (city.toLowerCase() === 'київ') {
                            region = 'Київська область';
                        }
                        // Автоматичне заповнення для Києва

                        document.getElementById('region').value = region;
                        document.getElementById('city').value = city;
                        // Оновлення полів

                        if (errors.length > 0) {
                            showError('region', `Не вдалося отримати коректну назву ${errors.join(' та ')}. Оберіть зі списку.`);
                            // Помилка: некоректні дані
                        } else if (!region && !city) {
                            showError('region', 'Не вдалося визначити область або населений пункт. Оберіть зі списку.');
                            // Помилка: відсутні дані
                        }
                    } else {
                        document.getElementById('region').value = '';
                        document.getElementById('city').value = '';
                        showError('region', 'Не вдалося отримати дані про місце. Оберіть зі списку.');
                        // Помилка: відсутні дані
                    }
                } catch (error) {
                    console.error('Помилка отримання деталей місця:', error);
                    // Логування помилки
                    showError('region', 'Не вдалося отримати дані про місце. Оберіть зі списку.');
                    // Помилка: невдалий запит
                } finally {
                    if (loader) loader.style.display = 'none';
                    // Приховання завантаження
                }
            }

            const debouncedFetchLocationDetails = debounce(fetchLocationDetails, 1000);
            // Затримка запитів до Nominatim

            // Автоматичне заповнення для Києва
            document.getElementById('city').addEventListener('input', function () {
                if (this.value.trim().toLowerCase() === 'київ') {
                    document.getElementById('region').value = 'Київська область';
                }
                // Заповнення області для Києва
            });

            // Валідація форми
            document.getElementById('report-form').addEventListener('submit', function (e) {
                e.preventDefault();
                // Зупинка стандартного відправлення форми
                clearErrors();
                // Очищення помилок
                let isValid = true;
                let errors = [];
                // Змінні для валідації

                const type = document.getElementById('type').value;
                if (!type) {
                    errors.push(['type', 'Оберіть тип проблеми.']);
                    isValid = false;
                }
                // Перевірка типу

                const description = document.getElementById('description').value.trim();
                const ukrainianRegex = /^[\u0400-\u04FF\s-]+$/;
                if (!description) {
                    errors.push(['description', 'Введіть опис проблеми.']);
                    isValid = false;
                } else if (description.length < 10) {
                    errors.push(['description', 'Опис має містити щонайменше 10 символів.']);
                    isValid = false;
                } else if (description.length > 500) {
                    errors.push(['description', 'Опис не може перевищувати 500 символів.']);
                    isValid = false;
                } else if (!ukrainianRegex.test(description)) {
                    errors.push(['description', 'Опис має містити лише українські літери, пробіли та дефіси.']);
                    isValid = false;
                }
                // Перевірка опису

                const region = document.getElementById('region').value.trim();
                if (!region || !allowedRegions.includes(region)) {
                    errors.push(['region', 'Оберіть коректну область зі списку.']);
                    isValid = false;
                }
                // Перевірка області

                const city = document.getElementById('city').value.trim();
                if (!city) {
                    errors.push(['city', 'Введіть населений пункт.']);
                    isValid = false;
                } else if (!ukrainianRegex.test(city)) {
                    errors.push(['city', 'Населений пункт має містити лише українські літери.']);
                    isValid = false;
                } else if (city.length > 100) {
                    errors.push(['city', 'Населений пункт не може перевищувати 100 символів.']);
                    isValid = false;
                }
                // Перевірка міста

                const latitude = parseFloat(document.getElementById('latitude').value);
                const longitude = parseFloat(document.getElementById('longitude').value);
                if (!latitude || !longitude || latitude < 44 || latitude > 53 || longitude < 22 || longitude > 40) {
                    errors.push(['map', 'Оберіть коректне місце на карті в межах України.']);
                    isValid = false;
                }
                // Перевірка координат

                const photo = document.getElementById('photo').files[0];
                if (photo) {
                    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    if (!allowedTypes.includes(photo.type)) {
                        errors.push(['photo', 'Дозволені лише файли JPEG, PNG або GIF.']);
                        isValid = false;
                    } else if (photo.size > 5 * 1024 * 1024) {
                        errors.push(['photo', 'Файл не може перевищувати 5 МБ.']);
                        isValid = false;
                    }
                }
                // Перевірка фото

                if (!isValid) {
                    errors.forEach(([fieldId, message]) => showError(fieldId, message));
                    // Відображення помилок
                }

                if (isValid) {
                    const submitBtn = document.querySelector('.submit-btn');
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Збереження...';
                    // Блокування кнопки збереження
                    this.submit();
                    // Відправлення форми
                }
            });

            // Ініціалізація координат
            if (<?php echo $report['latitude'] ? 'true' : 'false'; ?>) {
                debouncedFetchLocationDetails(<?php echo $report['latitude']; ?>, <?php echo $report['longitude']; ?>);
                // Запит деталей для наявних координат
            }
        });
    </script>
</body>
</html>