<?php
require_once '../includes/config.php'; // Підключення конфігураційного файлу
require_once '../includes/db.php'; // Підключення до бази даних
require_once '../includes/auth.php'; // Підключення функцій авторизації

header('Content-Type: application/json'); // Встановлення формату відповіді JSON

if (!isLoggedIn()) { // Перевірка, чи користувач авторизований
    echo json_encode(['success' => false, 'message' => 'Потрібна авторизація']); // Повідомлення про необхідність авторизації
    exit();
}

$conn = getDbConnection(); // Отримання з’єднання з базою даних
$user_id = $_SESSION['user_id']; // Отримання ID авторизованого користувача

// Підрахунок непрочитаних повідомлень від адміністраторів
$sql = "SELECT COUNT(*) as unread_count 
        FROM chat_messages 
        WHERE user_id = ? AND is_admin_reply = 1 AND is_read = 0"; // Запит для підрахунку непрочитаних повідомлень
$stmt = $conn->prepare($sql); // Підготовка запиту
$stmt->bind_param("i", $user_id); // Прив’язка ID користувача до запиту
if ($stmt->execute()) { // Виконання запиту
    $unread_count = $stmt->get_result()->fetch_assoc()['unread_count'] ?? 0; // Отримання кількості непрочитаних повідомлень
    echo json_encode(['success' => true, 'unread_count' => (int)$unread_count]); // Виведення результату у форматі JSON
} else {
    echo json_encode(['success' => false, 'message' => 'Помилка бази даних: ' . $stmt->error]); // Повідомлення про помилку бази даних
    error_log('SQL Error in get_user_messages.php: ' . $stmt->error); // Запис помилки в лог
}
$stmt->close(); // Закриття підготовленого запиту
$conn->close(); // Закриття з’єднання з базою даних
?>