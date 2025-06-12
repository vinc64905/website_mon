<?php
require_once '../includes/config.php'; // Підключення налаштувань системи
require_once '../includes/db.php'; // Підключення до бази даних
require_once '../includes/auth.php'; // Підключення функцій авторизації

// Перевірка авторизації
if (!isLoggedIn()) {
    header("Location: login.php"); // Перенаправлення на сторінку входу, якщо користувач не авторизований
    exit;
}

$conn = getDbConnection(); // Створення з’єднання з базою даних
$user_id = $_SESSION['user_id']; // Отримання ID користувача з сесії
$error = ''; // Змінна для повідомлень про помилки
$message = ''; // Змінна для повідомлень про успіх

// Обробка повідомлення про успішне редагування
if (isset($_GET['success']) && $_GET['success'] === 'report_updated') {
    $message = "Звіт успішно відредаговано!"; // Повідомлення про успішне редагування звіту
} elseif (isset($_GET['success']) && $_GET['success'] === 'report_deleted') {
    $message = "Звіт успішно видалено!"; // Повідомлення про успішне видалення звіту
}

// Обробка редагування профілю
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $email = trim($_POST['email'] ?? ''); // Отримання email з форми
    $name = trim($_POST['name'] ?? ''); // Отримання імені з форми
    $phone = trim($_POST['phone'] ?? ''); // Отримання телефону з форми

    // Валідація
    if (empty($email)) {
        $error = "Будь ласка, введіть email."; // Помилка, якщо email порожній
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Некоректний формат email."; // Помилка, якщо email некоректний
    } elseif (empty($name)) {
        $error = "Будь ласка, введіть ім'я."; // Помилка, якщо ім’я порожнє
    } elseif ($phone && !preg_match('/^\+?\d{10,15}$/', $phone)) {
        $error = "Некоректний формат номера телефону."; // Помилка, якщо телефон некоректний
    } else {
        // Перевірка унікальності email
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?"); // Запит для перевірки email
        $stmt->bind_param("si", $email, $user_id);
        if (!$stmt->execute()) {
            $error = "Помилка бази даних: " . $stmt->error; // Помилка бази даних
        } elseif ($stmt->get_result()->num_rows > 0) {
            $error = "Цей email уже зареєстровано."; // Помилка, якщо email зайнятий
        } else {
            // Оновлення профілю
            $sql = "UPDATE users SET email = ?, name = ?, phone = ? WHERE id = ?"; // Запит для оновлення профілю
            $params = [$email, $name, $phone ?: null, $user_id];
            $types = "sssi";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            if ($stmt->execute()) {
                $message = "Профіль успішно оновлено!"; // Повідомлення про успіх
            } else {
                $error = "Помилка оновлення профілю: " . $stmt->error; // Помилка оновлення
            }
        }
        $stmt->close();
    }
}

// Обробка зміни пароля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $password = $_POST['password'] ?? ''; // Отримання нового пароля
    $confirm_password = $_POST['confirm_password'] ?? ''; // Отримання підтвердження пароля

    if (!$password) {
        $error = "Будь ласка, введіть новий пароль."; // Помилка, якщо пароль порожній
    } elseif ($password !== $confirm_password) {
        $error = "Паролі не співпадають."; // Помилка, якщо паролі не співпадають
    } elseif (strlen($password) < 6) {
        $error = "Пароль має бути не коротшим за 6 символів."; // Помилка, якщо пароль закороткий
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT); // Хешування пароля
        $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?"); // Запит для зміни пароля
        $stmt->bind_param("si", $password_hash, $user_id);
        if ($stmt->execute()) {
            $message = "Пароль успішно змінено!"; // Повідомлення про успіх
        } else {
            $error = "Помилка зміни пароля: " . $stmt->error; // Помилка зміни пароля
        }
        $stmt->close();
    }
}

// Обробка видалення звіту (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_report'])) {
    header('Content-Type: application/json'); // Встановлення типу відповіді JSON
    $response = ['success' => false, 'message' => '']; // Ініціалізація відповіді

    $report_id = (int)$_POST['report_id']; // Отримання ID звіту
    $stmt = $conn->prepare("SELECT user_id, created_at FROM reports WHERE id = ?"); // Запит для перевірки звіту
    $stmt->bind_param("i", $report_id);
    if (!$stmt->execute()) {
        $response['message'] = "Помилка бази даних: " . $stmt->error; // Помилка бази даних
        error_log("Database error in delete_report: " . $stmt->error);
        echo json_encode($response);
        exit;
    }

    $report = $stmt->get_result()->fetch_assoc();
    if ($report && $report['user_id'] == $user_id) {
        $created_time = strtotime($report['created_at']); // Час створення звіту
        $current_time = time(); // Поточний час
        if (($current_time - $created_time) <= 120) { // Перевірка 2-хвилинного ліміту
            $stmt = $conn->prepare("DELETE FROM reports WHERE id = ?"); // Запит для видалення звіту
            $stmt->bind_param("i", $report_id);
            if ($stmt->execute()) {
                $response['success'] = true; // Успішне видалення
                $response['message'] = "Звіт успішно видалено!";
            } else {
                $response['message'] = "Помилка видалення звіту: " . $stmt->error; // Помилка видалення
                error_log("Database error in delete_report execution: " . $stmt->error);
            }
        } else {
            $response['message'] = "Звіт можна видаляти лише протягом 2 хвилин після створення."; // Помилка через ліміт часу
        }
    } else {
        $response['message'] = "Ви не можете видалити цей звіт."; // Помилка доступу
        error_log("Unauthorized delete attempt for report_id: $report_id by user_id: $user_id");
    }
    $stmt->close();
    echo json_encode($response);
    exit;
}

// Отримання даних користувача
$stmt = $conn->prepare("SELECT email, name, phone, role FROM users WHERE id = ?"); // Запит для даних користувача
$stmt->bind_param("i", $user_id);
if (!$stmt->execute()) {
    $error = "Помилка отримання даних користувача: " . $stmt->error; // Помилка бази даних
}
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Підрахунок непрочитаних повідомлень для адмінів
$admin_unread_count = 0;
if (isModerator()) {
    $sql = "SELECT COUNT(*) as unread_count 
            FROM chat_messages 
            WHERE is_admin_reply = 0 AND is_read = 0"; // Запит для підрахунку непрочитаних повідомлень
    $result = $conn->query($sql);
    if ($result === false) {
        error_log('SQL Error in profile.php (admin_unread_count): ' . $conn->error); // Запис помилки
    } else {
        $admin_unread_count = $result->fetch_assoc()['unread_count'] ?? 0; // Кількість непрочитаних повідомлень
    }
}

// Підрахунок непрочитаних повідомлень від адміністраторів для користувача
$user_unread_count = 0;
$sql = "SELECT COUNT(*) as unread_count 
        FROM chat_messages 
        WHERE user_id = ? AND is_admin_reply = 1 AND is_read = 0"; // Запит для підрахунку повідомлень
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
if ($stmt->execute()) {
    $user_unread_count = $stmt->get_result()->fetch_assoc()['unread_count'] ?? 0; // Кількість повідомлень
} else {
    error_log('SQL Error in profile.php (user_unread_count): ' . $stmt->error); // Запис помилки
}
$stmt->close();

// Початкове завантаження звітів
$status_filter = $_GET['status'] ?? ''; // Фільтр за статусом
$type_filter = $_GET['type'] ?? ''; // Фільтр за типом
$sort = $_GET['sort'] ?? 'created_at'; // Сортування
$order = $_GET['order'] ?? 'DESC'; // Порядок сортування
$page = max(1, (int)($_GET['page'] ?? 1)); // Номер сторінки
$per_page = 10; // Кількість звітів на сторінці

$sort = in_array($sort, ['id', 'created_at']) ? $sort : 'created_at'; // Перевірка сортування
$order = in_array($order, ['ASC', 'DESC']) ? $order : 'DESC'; // Перевірка порядку

error_log("Profile initial load - status: $status_filter, type: $type_filter, sort: $sort, order: $order, page: $page, user_id: $user_id");

$sql = "SELECT * FROM reports WHERE user_id = ?"; // Запит для звітів користувача
$params = [$user_id];
$types = "i";
if ($status_filter) {
    $sql .= " AND status = ?"; // Додавання фільтру за статусом
    $params[] = $status_filter;
    $types .= "s";
}
if ($type_filter) {
    $sql .= " AND type = ?"; // Додавання фільтру за типом
    $params[] = $type_filter;
    $types .= "s";
}
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
if (!$stmt->execute()) {
    $error = "Помилка отримання звітів: " . $stmt->error; // Помилка бази даних
    error_log("SQL Error in initial fetch: " . $stmt->error);
}
$all_reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$total_reports = count($all_reports); // Загальна кількість звітів
$total_pages = max(1, ceil($total_reports / $per_page)); // Кількість сторінок

$sql .= " ORDER BY $sort $order LIMIT ? OFFSET ?"; // Додавання сортування та пагінації
$params[] = $per_page;
$params[] = ($page - 1) * $per_page;
$types .= "ii";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
if (!$stmt->execute()) {
    $error = "Помилка отримання звітів: " . $stmt->error; // Помилка бази даних
    error_log("SQL Error in initial paginated fetch: " . $stmt->error);
}
$reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close(); // Закриття з’єднання з базою даних

// Масиви для зіставлення
$roles = [
    'admin' => 'Адміністратор', // Роль: адміністратор
    'moderator' => 'Модератор', // Роль: модератор
    'user' => 'Користувач' // Роль: користувач
];
$types = [
    'pothole' => 'Вибоїна', // Тип проблеми: вибоїна
    'trash' => 'Сміття', // Тип проблеми: сміття
    'light' => 'Освітлення', // Тип проблеми: освітлення
    'sign' => 'Дорожній знак', // Тип проблеми: дорожній знак
    'other' => 'Інше' // Тип проблеми: інше
];
$statuses = [
    'new' => 'Нова', // Статус: нова
    'in_progress' => 'В обробці', // Статус: в обробці
    'resolved' => 'Вирішена' // Статус: вирішена
];
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8"> <!-- Встановлення кодування сторінки -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Адаптація для мобільних пристроїв -->
    <title>Профіль користувача - Vinc_Road</title> <!-- Заголовок сторінки -->
    <link rel="stylesheet" href="../css/common.css"> <!-- Підключення основних стилів -->
    <link rel="stylesheet" href="../css/profile.css"> <!-- Підключення стилів для профілю -->
    <link rel="stylesheet" href="../css/admin_chat.css"> <!-- Підключення стилів для чату -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"> <!-- Підключення іконок Font Awesome -->
    <link rel="stylesheet" href="../css/responsive.css"> <!-- Підключення адаптивних стилів -->
</head>
<body>
    <button id="theme-toggle" class="theme-toggle">🌙</button> <!-- Кнопка зміни теми -->
    <header>
        <h1>Vinc_Road: Профіль користувача</h1> <!-- Заголовок сторінки -->
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
        <section>
            <h2>Профіль користувача</h2> <!-- Заголовок секції -->
            <div id="messages">
                <?php if ($error): ?>
                    <p class="error"><?php echo htmlspecialchars($error); ?></p> <!-- Виведення помилки -->
                <?php endif; ?>
                <?php if ($message): ?>
                    <p class="message"><?php echo htmlspecialchars($message); ?></p> <!-- Виведення повідомлення -->
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p> <!-- Виведення email -->
                <p><strong>Ім'я:</strong> <?php echo htmlspecialchars($user['name'] ?? 'Не вказано'); ?></p> <!-- Виведення імені -->
                <p><strong>Номер телефону:</strong> <?php echo htmlspecialchars($user['phone'] ?? 'Не вказано'); ?></p> <!-- Виведення телефону -->
                <p><strong>Роль:</strong> <?php echo htmlspecialchars($roles[$user['role']] ?? 'Користувач'); ?></p> <!-- Виведення ролі -->
                <div class="profile-actions">
                    <a href="#" class="action-btn" data-form="edit-info">Редагувати інформацію</a> <!-- Кнопка редагування профілю -->
                    <a href="#" class="action-btn" data-form="change-password">Змінити пароль</a> <!-- Кнопка зміни пароля -->
                    <?php if (isAdmin()): ?>
                        <a href="admin.php" class="action-btn admin-btn">Перейти до адмінпанелі</a> <!-- Кнопка для адмінів -->
                    <?php endif; ?>
                    <?php if (isModerator()): ?>
                        <a href="admin.php" class="action-btn admin-btn">Перейти до адмінпанелі</a> <!-- Кнопка для модераторів -->
                    <?php endif; ?>
                    <?php if (isModerator()): ?>
                        <a href="admin_chat.php" class="action-btn admin-chat-btn">
                            Адмін-Чат
                            <?php if ($admin_unread_count > 0): ?>
                                <span class="unread-count"><?php echo $admin_unread_count; ?></span> <!-- Кількість непрочитаних повідомлень -->
                            <?php endif; ?>
                        </a> <!-- Кнопка для чату модераторів -->
                    <?php endif; ?>
                    <a href="feedback.php" class="action-btn feedback-btn">
                        Зворотний зв’язок
                        <?php if ($user_unread_count > 0): ?>
                            <span class="unread-count"><?php echo $user_unread_count; ?></span> <!-- Кількість повідомлень від адмінів -->
                        <?php endif; ?>
                    </a> <!-- Кнопка для зворотного зв’язку -->
                </div>
                <form method="POST" class="form-section edit-info-form" style="display: none;"> <!-- Форма редагування профілю -->
                    <input type="hidden" name="update_profile" value="1">
                    <div class="form-group">
                        <label for="email">Email</label> <!-- Мітка для email -->
                        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" required> <!-- Поле для email -->
                    </div>
                    <div class="form-group">
                        <label for="name">Ім'я</label> <!-- Мітка для імені -->
                        <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" placeholder="Введіть ваше ім'я" required> <!-- Поле для імені -->
                    </div>
                    <div class="form-group">
                        <label for="phone">Номер телефону</label> <!-- Мітка для телефону -->
                        <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="Наприклад, +380123456789"> <!-- Поле для телефону -->
                    </div>
                    <button type="submit" class="btn">Зберегти зміни</button> <!-- Кнопка збереження -->
                    <button type="button" class="btn cancel-btn" data-form="edit-info">Скасувати</button> <!-- Кнопка скасування -->
                </form>
                <form method="POST" class="form-section change-password-form" style="display: none;"> <!-- Форма зміни пароля -->
                    <input type="hidden" name="update_password" value="1">
                    <div class="form-group">
                        <label for="password">Новий пароль</label> <!-- Мітка для пароля -->
                        <input type="password" name="password" id="password" placeholder="Введіть новий пароль" required> <!-- Поле для пароля -->
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Підтвердіть пароль</label> <!-- Мітка для підтвердження -->
                        <input type="password" name="confirm_password" id="confirm_password" placeholder="Підтвердіть новий пароль" required> <!-- Поле для підтвердження -->
                    </div>
                    <button type="submit" class="btn">Змінити пароль</button> <!-- Кнопка зміни пароля -->
                    <button type="button" class="btn cancel-btn" data-form="change-password">Скасувати</button> <!-- Кнопка скасування -->
                </form>
            </div>

            <h3>Мої звіти</h3> <!-- Заголовок секції звітів -->
            <form class="filter-form" id="filter-form"> <!-- Форма фільтрації звітів -->
                <div class="form-group">
                    <label for="status">Статус</label> <!-- Мітка для статусу -->
                    <select name="status" id="status"> <!-- Випадаючий список статусів -->
                        <option value="">Усі статуси</option>
                        <option value="new" <?php echo $status_filter === 'new' ? 'selected' : ''; ?>>Нова</option>
                        <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>В обробці</option>
                        <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>Вирішена</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="type">Тип</label> <!-- Мітка для типу -->
                    <select name="type" id="type"> <!-- Випадаючий список типів -->
                        <option value="">Усі типи</option>
                        <?php foreach ($types as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php echo $type_filter === $key ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="button" class="reset-filters"><i class="fas fa-times"></i> Скинути фільтри</button> <!-- Кнопка скидання фільтрів -->
            </form>
            <div id="reports-container">
                <?php if (empty($reports)): ?>
                    <p class="filter-message">Немає звітів за вибраними фільтрами.</p> <!-- Повідомлення, якщо звіти відсутні -->
                <?php else: ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th><a href="#" class="sort-link" data-sort="id" data-order="<?php echo $sort === 'id' && $order === 'ASC' ? 'DESC' : 'ASC'; ?>">ID</a></th> <!-- Сортування за ID -->
                                    <th>Область</th> <!-- Колонка області -->
                                    <th>Населений пункт</th> <!-- Колонка міста -->
                                    <th>Тип</th> <!-- Колонка типу -->
                                    <th>Опис</th> <!-- Колонка опису -->
                                    <th>Фото</th> <!-- Колонка фото -->
                                    <th>Статус</th> <!-- Колонка статусу -->
                                    <th><a href="#" class="sort-link" data-sort="created_at" data-order="<?php echo $sort === 'created_at' && $order === 'ASC' ? 'DESC' : 'ASC'; ?>">Час</a></th> <!-- Сортування за часом -->
                                    <th>Дії</th> <!-- Колонка дій -->
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reports as $report): ?>
                                    <?php
                                    $created_time = strtotime($report['created_at']); // Час створення звіту
                                    $current_time = time(); // Поточний час
                                    $time_left = max(0, 120 - ($current_time - $created_time)); // Час, що залишився для редагування
                                    $can_edit = $time_left > 0; // Чи можна редагувати
                                    ?>
                                    <tr data-report-id="<?php echo htmlspecialchars($report['id']); ?>"> <!-- Рядок звіту -->
                                        <td><?php echo htmlspecialchars($report['id']); ?></td> <!-- ID звіту -->
                                        <td><?php echo htmlspecialchars($report['region']); ?></td> <!-- Область -->
                                        <td><?php echo htmlspecialchars($report['city']); ?></td> <!-- Місто -->
                                        <td><?php echo htmlspecialchars($types[$report['type']] ?? 'Інше'); ?></td> <!-- Тип звіту -->
                                        <td><?php echo htmlspecialchars($report['description'] ?? 'Немає'); ?></td> <!-- Опис -->
                                        <td>
                                            <?php if ($report['photo']): ?>
                                                <img src="../<?php echo htmlspecialchars($report['photo']); ?>" alt="Фото" style="max-width: 100px;"> <!-- Фото звіту -->
                                            <?php else: ?>
                                                Немає
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($statuses[$report['status']] ?? 'Невідомо'); ?></td> <!-- Статус -->
                                        <td>
                                            <?php echo htmlspecialchars($report['created_at']); ?> <!-- Час створення -->
                                            <?php if ($can_edit): ?>
                                                <br><span class="time-left" data-time-left="<?php echo $time_left; ?>"></span> <!-- Час, що залишився -->
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($can_edit): ?>
                                                <a href="edit_report.php?id=<?php echo $report['id']; ?>" class="action-btn">Редагувати</a> <!-- Кнопка редагування -->
                                                <form method="POST" style="display: inline;" class="delete-report-form" data-report-id="<?php echo $report['id']; ?>"> <!-- Форма видалення -->
                                                    <input type="hidden" name="delete_report" value="1">
                                                    <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                                    <button type="submit" class="action-btn delete-btn">Видалити</button> <!-- Кнопка видалення -->
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="#" class="btn page-link" data-page="<?php echo $page - 1; ?>">Попередня</a> <!-- Попередня сторінка -->
                            <?php endif; ?>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="#" class="btn page-link <?php echo $i === $page ? 'active' : ''; ?>" data-page="<?php echo $i; ?>"><?php echo $i; ?></a> <!-- Номери сторінок -->
                            <?php endfor; ?>
                            <?php if ($page < $total_pages): ?>
                                <a href="#" class="btn page-link" data-page="<?php echo $page + 1; ?>">Наступна</a> <!-- Наступна сторінка -->
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>
    <footer>
        <p>© 2025 Vinc_Road - Моніторинг інфраструктури України</p> <!-- Нижній колонтитул -->
    </footer>
    <script src="../js/theme.js"></script> <!-- Підключення скрипта для зміни теми -->
    <script src="../js/profile.js"></script> <!-- Підключення скрипта для профілю -->
</body>
</html>