<?php
require_once '../includes/config.php'; // Підключення конфігураційного файлу
require_once '../includes/db.php'; // Підключення до бази даних
require_once '../includes/auth.php'; // Підключення функцій авторизації

header('Content-Type: application/json'); // Встановлення формату відповіді JSON

if (!isModerator()) { // Перевірка, чи користувач є модератором
    echo json_encode(['success' => false, 'message' => 'Потрібна авторизація модератора']); // Повідомлення про необхідність прав модератора
    exit();
}

$conn = getDbConnection(); // Отримання з’єднання з базою даних

// Отримання списку користувачів із повідомленнями та кількістю непрочитаних
$sql = "SELECT DISTINCT u.id, u.name, u.email, 
               (SELECT COUNT(*) FROM chat_messages cm2 
                WHERE cm2.user_id = u.id AND cm2.is_admin_reply = 0 AND cm2.is_read = 0) as unread_count
        FROM users u 
        JOIN chat_messages cm ON u.id = cm.user_id 
        ORDER BY u.name ASC"; // Запит для отримання користувачів та їхніх непрочитаних повідомлень
$result = $conn->query($sql); // Виконання запиту
$users = []; // Ініціалізація масиву для зберігання даних користувачів
while ($row = $result->fetch_assoc()) { // Обробка результатів запиту
    $users[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'email' => $row['email'],
        'unread_count' => (int)$row['unread_count'] // Додавання даних користувача до масиву
    ];
}

// Підрахунок загальної кількості непрочитаних повідомлень
$sql = "SELECT COUNT(*) as unread_count 
        FROM chat_messages 
        WHERE is_admin_reply = 0 AND is_read = 0"; // Запит для підрахунку всіх непрочитаних повідомлень
$result = $conn->query($sql); // Виконання запиту
if ($result === false) { // Перевірка на помилку
    error_log('SQL Error in get_users.php: ' . $conn->error); // Запис помилки в лог
    $unread_count = 0; // Встановлення значення 0 у разі помилки
} else {
    $unread_count = $result->fetch_assoc()['unread_count'] ?? 0; // Отримання загальної кількості непрочитаних повідомлень
}

$conn->close(); // Закриття з’єднання з базою даних

echo json_encode([
    'success' => true,
    'users' => $users,
    'unread_count' => (int)$unread_count // Виведення результату у форматі JSON
]);
?>