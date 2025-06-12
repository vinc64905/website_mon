<?php
// Увімкнення буферизації для керування виведенням
ob_start();
// Підключення файлу з налаштуваннями
require_once '../includes/config.php';
// Підключення модуля для роботи з базою даних
require_once '../includes/db.php';
// Підключення модуля автентифікації
require_once '../includes/auth.php';

// Перевірка, чи користувач є модератором
if (!isModerator()) {
    // Логування спроби доступу без прав
    error_log("Redirecting to login.php: User is not moderator");
    // Перенаправлення на сторінку входу
    header("Location: login.php");
    exit;
}

// Створення з'єднання з базою даних
$conn = getDbConnection();
// Ініціалізація змінних для повідомлень про помилки та успіх
$error = '';
$message = '';

// Обробка запитів від адміністраторів
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAdmin()) {
    // Редагування даних користувача
    if (isset($_POST['edit_user'])) {
        // Отримання даних із форми
        $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $role = isset($_POST['role']) ? $_POST['role'] : 'user';

        // Логування запиту на редагування
        error_log("Edit user request: user_id=$user_id, email=$email, name=$name, phone=$phone, role=$role");

        // Перевірка коректності даних
        if ($user_id === 2) {
            $error = "Цей акаунт не можна редагувати.";
        } elseif ($user_id === 0) {
            $error = "Некоректний ID користувача.";
        } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Некоректний email.";
        } elseif ($name && !preg_match('/^[\x{0400}-\x{04FF}\s-]{1,100}$/u', $name)) {
            $error = "Ім’я має містити лише українські літери, пробіли, дефіси.";
        } elseif ($phone && !preg_match('/^\+?\d{10,15}$/', $phone)) {
            $error = "Некоректний формат телефону.";
        } elseif (!in_array($role, ['user', 'moderator', 'admin'])) {
            $error = "Некоректна роль.";
        } else {
            // Перевірка, чи email не зайнятий іншим користувачем
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->bind_param("si", $email, $user_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $error = "Цей email уже використовується.";
            } else {
                // Оновлення даних користувача в базі
                $name = $name ?: null;
                $phone = $phone ?: null;
                $stmt = $conn->prepare("UPDATE users SET email = ?, name = ?, phone = ?, role = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $email, $name, $phone, $role, $user_id);
                if ($stmt->execute()) {
                    $message = "Користувача успішно оновлено.";
                    error_log("User updated: user_id: $user_id, email: $email, role: $role");
                } else {
                    $error = "Помилка оновлення: " . $stmt->error;
                    error_log("Database error in edit_user: " . $stmt->error);
                }
            }
            $stmt->close();
        }
    // Видалення користувача
    } elseif (isset($_POST['delete_user'])) {
        $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        if ($user_id === 2) {
            $error = "Цей акаунт не можна видалити.";
        } elseif ($user_id === $_SESSION['user_id']) {
            $error = "Ви не можете видалити власний акаунт.";
        } elseif ($user_id === 0) {
            $error = "Некоректний ID користувача.";
        } else {
            // Видалення користувача з бази даних
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            if ($stmt->execute()) {
                $message = "Користувача успішно видалено.";
                error_log("User deleted: user_id: $user_id");
            } else {
                $error = "Помилка видалення: " . $stmt->error;
                error_log("Database error in delete_user: " . $stmt->error);
            }
            $stmt->close();
        }
    }
}

// Обробка зміни статусу звіту
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_id']) && isset($_POST['new_status'])) {
    // Встановлення формату відповіді у JSON
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    $report_id = (int)$_POST['report_id'];
    $new_status = $conn->real_escape_string($_POST['new_status']);
    if (in_array($new_status, ['new', 'in_progress', 'resolved'])) {
        // Оновлення статусу звіту в базі даних
        $stmt = $conn->prepare("UPDATE reports SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $report_id);
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = "Статус звіту оновлено.";
        } else {
            $response['message'] = "Помилка оновлення статусу: " . $stmt->error;
            error_log("Database error in update_status: " . $stmt->error);
        }
        $stmt->close();
    } else {
        $response['message'] = "Некоректний статус.";
    }
    // Виведення JSON-відповіді
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    ob_end_clean();
    exit;
}

// Отримання унікальних областей зі звітів
$regions_result = $conn->query("SELECT DISTINCT region FROM reports ORDER BY region");
$regions = [];
while ($row = $regions_result->fetch_assoc()) {
    $regions[] = $row['region'];
}

// Отримання списку користувачів для адміністраторів
$users = [];
$search_email = '';
$search_id = '';
if (isAdmin()) {
    $sql = "SELECT id, email, name, phone, role FROM users WHERE 1=1";
    $params = [];
    $types = "";
    if (isset($_GET['search_email']) && trim($_GET['search_email']) !== '') {
        // Фільтрація користувачів за email
        $search_email = $conn->real_escape_string(trim($_GET['search_email']));
        $sql .= " AND email LIKE ?";
        $search_param = "%$search_email%";
        $params[] = $search_param;
        $types .= "s";
    }
    if (isset($_GET['search_id']) && trim($_GET['search_id']) !== '' && is_numeric($_GET['search_id'])) {
        // Фільтрація користувачів за ID
        $search_id = (int)$_GET['search_id'];
        $sql .= " AND id = ?";
        $params[] = $search_id;
        $types .= "i";
    }
    $sql .= " ORDER BY id";
    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt->close();
}

// Закриття з'єднання з базою даних
$conn->close();

// Список типів проблем для фільтрації
$types = [
    'pothole' => 'Вибоїна',
    'trash' => 'Сміття',
    'light' => 'Освітлення',
    'sign' => 'Дорожній знак',
    'other' => 'Інше'
];
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <!-- Встановлення кодування сторінки -->
    <meta charset="UTF-8">
    <!-- Налаштування адаптивності для мобільних пристроїв -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Назва сторінки в браузері -->
    <title>Панель адміністратора - Vinc_Road</title>
    <!-- Підключення стилів для оформлення сторінки -->
    <link rel="stylesheet" href="../css/common.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/map.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <link rel="stylesheet" href="../css/responsive.css">
    <!-- Підключення бібліотек для роботи з мапами -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
</head>
<body>
    <!-- Кнопка для зміни теми (світла/темна) -->
    <button id="theme-toggle" class="theme-toggle">🌙</button>
    <header>
        <!-- Основний заголовок сторінки -->
        <h1>Панель адміністратора</h1>
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
        <!-- Секція для управління звітами -->
        <section>
            <h2>Управління звітами</h2>
            <!-- Контейнер для відображення мапи зі звітами -->
            <div class="map-container">
                <h3>Мапа звітів</h3>
                <div id="map" style="height: 400px;"></div>
            </div>

            <!-- Форма для фільтрації звітів -->
            <form id="filter-form" class="filter-form">
                <div class="form-group">
                    <label for="search_query">Пошук:</label>
                    <input type="text" id="search_query" name="search_query" placeholder="ID, регіон, місто, опис">
                </div>
                <div class="form-group">
                    <label for="region">Область:</label>
                    <select name="region" id="region">
                        <option value="">Усі області</option>
                        <!-- Виведення доступних областей -->
                        <?php foreach ($regions as $reg): ?>
                            <option value="<?php echo htmlspecialchars($reg); ?>">
                                <?php echo htmlspecialchars($reg); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status">Статус:</label>
                    <select name="status" id="status">
                        <option value="">Усі статуси</option>
                        <option value="new">Нова</option>
                        <option value="in_progress">В обробці</option>
                        <option value="resolved">Вирішена</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="type">Тип:</label>
                    <select name="type" id="type">
                        <option value="">Усі типи</option>
                        <!-- Виведення типів проблем -->
                        <?php foreach ($types as $key => $label): ?>
                            <option value="<?php echo $key; ?>">
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="button" class="reset-filters"><i class="fas fa-times"></i> Скинути фільтри</button>
            </form>

            <!-- Контейнер для відображення відфільтрованих звітів -->
            <div id="reports-container">
                <p class="filter-message">Виберіть фільтри для відображення звітів.</p>
            </div>
        </section>

        <!-- Секція для управління користувачами (доступна адміністраторам) -->
        <?php if (isAdmin()): ?>
            <section>
                <h2>Управління користувачами</h2>
                <!-- Відображення повідомлень про помилки -->
                <?php if ($error): ?>
                    <p class="error"><?php echo htmlspecialchars($error); ?></p>
                <?php endif; ?>
                <!-- Відображення повідомлень про успіх -->
                <?php if ($message): ?>
                    <p class="message"><?php echo htmlspecialchars($message); ?></p>
                <?php endif; ?>
                <!-- Форма для фільтрації користувачів -->
                <form id="user-filter-form" class="filter-form">
                    <div class="form-group">
                        <label for="search_id">Пошук за ID:</label>
                        <input type="number" id="search_id" name="search_id" value="<?php echo htmlspecialchars($search_id); ?>" placeholder="Введіть ID" min="1">
                    </div>
                    <div class="form-group">
                        <label for="search_email">Пошук за Email:</label>
                        <input type="text" id="search_email" name="search_email" value="<?php echo htmlspecialchars($search_email); ?>" placeholder="Введіть email">
                    </div>
                    <button type="button" class="reset-filters"><i class="fas fa-times"></i> Скинути пошук</button>
                </form>
                <!-- Таблиця з даними користувачів -->
                <div id="users-container">
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Email</th>
                                    <th>Ім'я</th>
                                    <th>Телефон</th>
                                    <th>Роль</th>
                                    <th>Дії</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Виведення списку користувачів -->
                                <?php foreach ($users as $user): ?>
                                    <tr<?php echo $user['id'] === 2 ? ' class="main-account"' : ''; ?>>
                                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['name'] ?? 'Не вказано'); ?></td>
                                        <td><?php echo htmlspecialchars($user['phone'] ?? 'Не вказано'); ?></td>
                                        <td><?php echo htmlspecialchars($user['role']); ?></td>
                                        <td>
                                            <?php if ($user['id'] !== 2): ?>
                                                <!-- Кнопка для редагування користувача -->
                                                <button class="action-btn edit-user-btn" data-user-id="<?php echo $user['id']; ?>">Редагувати</button>
                                                <!-- Форма для видалення користувача -->
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="delete_user" value="1">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" class="action-btn delete-btn" onclick="return confirm('Ви впевнені, що хочете видалити цього користувача?');">Видалити</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="main-account-label">Основний</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <!-- Форма для редагування користувача -->
                                    <?php if ($user['id'] !== 2): ?>
                                        <tr id="edit-form-<?php echo $user['id']; ?>" class="edit-form-row" style="display: none;">
                                            <td colspan="6">
                                                <form method="POST" class="edit-user-form">
                                                    <input type="hidden" name="edit_user" value="1">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <div class="form-group">
                                                        <label for="edit-email-<?php echo $user['id']; ?>">Email</label>
                                                        <input type="email" name="email" id="edit-email-<?php echo $user['id']; ?>" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="edit-name-<?php echo $user['id']; ?>">Ім'я</label>
                                                        <input type="text" name="name" id="edit-name-<?php echo $user['id']; ?>" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" placeholder="Введіть ім'я">
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="edit-phone-<?php echo $user['id']; ?>">Телефон</label>
                                                        <input type="text" name="phone" id="edit-phone-<?php echo $user['id']; ?>" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="Наприклад, +380123456789">
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="edit-role-<?php echo $user['id']; ?>">Роль</label>
                                                        <select name="role" id="edit-role-<?php echo $user['id']; ?>">
                                                            <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>Користувач</option>
                                                            <option value="moderator" <?php echo $user['role'] === 'moderator' ? 'selected' : ''; ?>>Модератор</option>
                                                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Адміністратор</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-actions">
                                                        <button type="submit" class="action-btn">Зберегти</button>
                                                        <button type="button" class="action-btn cancel-btn" data-user-id="<?php echo $user['id']; ?>">Скасувати</button>
                                                    </div>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>
    <footer>
        <!-- Інформація про авторські права -->
        <p>© 2025 Vinc_Road - Моніторинг інфраструктури України</p>
    </footer>
    <!-- Підключення скриптів для функціональності -->
    <script src="../js/theme.js"></script>
    <script src="../js/map.js"></script>
    <script src="../js/admin.js"></script>
    <script>
        // Передача даних про типи проблем і статус адміністратора до JavaScript
        const types = <?php echo json_encode($types); ?>;
        const isAdmin = <?php echo isAdmin() ? 'true' : 'false'; ?>;
    </script>
</body>
</html>
<?php ob_end_flush(); ?>