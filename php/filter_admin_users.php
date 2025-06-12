<?php
require_once '../includes/config.php'; // Підключення конфігураційного файлу
require_once '../includes/db.php'; // Підключення до бази даних
require_once '../includes/auth.php'; // Підключення функцій авторизації

header('Content-Type: application/json'); // Встановлення формату відповіді JSON

// Перевірка авторизації
if (!isAdmin()) { // Перевірка, чи користувач є адміністратором
    error_log('Unauthorized access to filter_admin_users.php'); // Запис спроби несанкціонованого доступу
    echo json_encode(['success' => false, 'message' => 'Недостатньо прав доступу'], JSON_UNESCAPED_UNICODE); // Повідомлення про недостатність прав
    exit;
}

$conn = getDbConnection(); // Отримання з’єднання з базою даних

// Отримання параметрів пошуку
$search_email = isset($_GET['search_email']) ? $conn->real_escape_string(trim($_GET['search_email'])) : ''; // Пошук за email
$search_id = isset($_GET['search_id']) && is_numeric($_GET['search_id']) ? (int)$_GET['search_id'] : ''; // Пошук за ID
$page = max(1, (int)($_GET['page'] ?? 1)); // Номер сторінки
$per_page = 10; // Кількість записів на сторінці

error_log("filter_admin_users.php - search_email: $search_email, search_id: $search_id, page: $page"); // Логування параметрів

// Формування SQL-запиту для підрахунку загальної кількості
$sql_count = "SELECT COUNT(*) as total FROM users WHERE 1=1"; // Базовий запит для підрахунку користувачів
$params = []; // Масив параметрів
$types = ""; // Типи даних параметрів
if ($search_email) {
    $sql_count .= " AND email LIKE ?"; // Умова пошуку за email
    $search_param = "%$search_email%";
    $params[] = $search_param;
    $types .= "s";
}
if ($search_id) {
    $sql_count .= " AND id = ?"; // Умова пошуку за ID
    $params[] = $search_id;
    $types .= "i";
}
$stmt = $conn->prepare($sql_count); // Підготовка запиту
if ($params) {
    $stmt->bind_param($types, ...$params); // Прив’язка параметрів
}
if (!$stmt->execute()) { // Виконання запиту
    error_log("SQL Error in count: " . $stmt->error); // Логування помилки
    echo json_encode(['success' => false, 'message' => 'Помилка при отриманні даних'], JSON_UNESCAPED_UNICODE); // Повідомлення про помилку
    exit;
}
$total_users = $stmt->get_result()->fetch_assoc()['total']; // Отримання загальної кількості користувачів
$total_pages = max(1, ceil($total_users / $per_page)); // Розрахунок кількості сторінок
$stmt->close(); // Закриття запиту

// Формування SQL-запиту для користувачів
$sql = "SELECT id, email, name, phone, role FROM users WHERE 1=1"; // Базовий запит для отримання користувачів
if ($search_email) {
    $sql .= " AND email LIKE ?"; // Умова пошуку за email
}
if ($search_id) {
    $sql .= " AND id = ?"; // Умова пошуку за ID
}
$sql .= " ORDER BY id LIMIT ? OFFSET ?"; // Сортування та пагінація
$params = array_merge($params, [$per_page, ($page - 1) * $per_page]); // Додавання параметрів пагінації
$types .= "ii";
$stmt = $conn->prepare($sql); // Підготовка запиту
$stmt->bind_param($types, ...$params); // Прив’язка параметрів
if (!$stmt->execute()) { // Виконання запиту
    error_log("SQL Error in fetch: " . $stmt->error); // Логування помилки
    echo json_encode(['success' => false, 'message' => 'Помилка при отриманні користувачів'], JSON_UNESCAPED_UNICODE); // Повідомлення про помилку
    exit;
}
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); // Отримання всіх користувачів
$stmt->close(); // Закриття запиту
$conn->close(); // Закриття з’єднання з базою

// Генерація HTML для таблиці користувачів
ob_start(); // Початок буферизації виведення
if (empty($users)) {
    echo '<p class="filter-message">Немає користувачів за заданим пошуком.</p>'; // Повідомлення про відсутність користувачів
} else {
    echo '<div class="table-wrapper">';
    echo '<table id="users-table">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Email</th>';
    echo '<th>Ім\'я</th>';
    echo '<th>Телефон</th>';
    echo '<th>Роль</th>';
    echo '<th>Дії</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    foreach ($users as $user) {
        echo '<tr' . ($user['id'] === 2 ? ' class="main-account"' : '') . '>'; // Рядок користувача, позначення основного акаунта
        echo '<td>' . htmlspecialchars($user['id']) . '</td>'; // ID користувача
        echo '<td>' . htmlspecialchars($user['email']) . '</td>'; // Email користувача
        echo '<td>' . htmlspecialchars($user['name'] ?? 'Не вказано') . '</td>'; // Ім’я користувача
        echo '<td>' . htmlspecialchars($user['phone'] ?? 'Не вказано') . '</td>'; // Телефон користувача
        echo '<td>' . htmlspecialchars($user['role']) . '</td>'; // Роль користувача
        echo '<td>';
        if ($user['id'] !== 2) {
            echo '<button class="action-btn edit-user-btn" data-user-id="' . $user['id'] . '">Редагувати</button>'; // Кнопка редагування
            echo '<form method="POST" style="display: inline;">';
            echo '<input type="hidden" name="delete_user" value="1">';
            echo '<input type="hidden" name="user_id" value="' . $user['id'] . '">';
            echo '<button type="submit" class="action-btn delete-btn" onclick="return confirm(\'Ви впевнені, що хочете видалити цього користувача?\');">Видалити</button>'; // Кнопка видалення
            echo '</form>';
        } else {
            echo '<span class="main-account-label">Основний</span>'; // Позначка основного акаунта
        }
        echo '</td>';
        echo '</tr>';
        if ($user['id'] !== 2) {
            echo '<tr id="edit-form-' . $user['id'] . '" class="edit-form-row" style="display: none;">'; // Форма редагування
            echo '<td colspan="6">';
            echo '<form method="POST" class="edit-user-form">';
            echo '<input type="hidden" name="edit_user" value="1">';
            echo '<input type="hidden" name="user_id" value="' . $user['id'] . '">';
            echo '<div class="form-group">';
            echo '<label for="edit-email-' . $user['id'] . '">Email</label>';
            echo '<input type="email" name="email" id="edit-email-' . $user['id'] . '" value="' . htmlspecialchars($user['email']) . '" required>'; // Поле email
            echo '</div>';
            echo '<div class="form-group">';
            echo '<label for="edit-name-' . $user['id'] . '">Ім\'я</label>';
            echo '<input type="text" name="name" id="edit-name-' . $user['id'] . '" value="' . htmlspecialchars($user['name'] ?? '') . '" placeholder="Введіть ім\'я">'; // Поле імені
            echo '</div>';
            echo '<div class="form-group">';
            echo '<label for="edit-phone-' . $user['id'] . '">Телефон</label>';
            echo '<input type="text" name="phone" id="edit-phone-' . $user['id'] . '" value="' . htmlspecialchars($user['phone'] ?? '') . '" placeholder="Наприклад, +380123456789">'; // Поле телефону
            echo '</div>';
            echo '<div class="form-group">';
            echo '<label for="edit-role-' . $user['id'] . '">Роль</label>';
            echo '<select name="role" id="edit-role-' . $user['id'] . '">';
            echo '<option value="user" ' . ($user['role'] === 'user' ? 'selected' : '') . '>Користувач</option>';
            echo '<option value="moderator" ' . ($user['role'] === 'moderator' ? 'selected' : '') . '>Модератор</option>';
            echo '<option value="admin" ' . ($user['role'] === 'admin' ? 'selected' : '') . '>Адміністратор</option>';
            echo '</select>'; // Вибір ролі
            echo '</div>';
            echo '<div class="form-actions">';
            echo '<button type="submit" class="action-btn">Зберегти</button>'; // Кнопка збереження
            echo '<button type="button" class="action-btn cancel-btn" data-user-id="' . $user['id'] . '">Скасувати</button>'; // Кнопка скасування
            echo '</div>';
            echo '</form>';
            echo '</td>';
            echo '</tr>';
        }
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>';

    // Пагінація
    if ($total_pages > 1) {
        echo '<div class="pagination">';
        if ($page > 1) {
            echo '<a href="#" class="btn page-link user-page-link" data-page="' . ($page - 1) . '">Попередня</a>'; // Попередня сторінка
        }
        for ($i = 1; $i <= $total_pages; $i++) {
            echo '<a href="#" class="btn page-link user-page-link ' . ($i === $page ? 'active' : '') . '" data-page="' . $i . '">' . $i . '</a>'; // Номери сторінок
        }
        if ($page < $total_pages) {
            echo '<a href="#" class="btn page-link user-page-link" data-page="' . ($page + 1) . '">Наступна</a>'; // Наступна сторінка
        }
        echo '</div>';
    }
}
$users_html = ob_get_clean(); // Збереження HTML таблиці

// Повернення JSON
echo json_encode([
    'success' => true,
    'users' => $users,
    'users_html' => $users_html,
    'total_users' => $total_users
], JSON_UNESCAPED_UNICODE); // Виведення результату у форматі JSON
?>