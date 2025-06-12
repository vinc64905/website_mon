<?php
// Імпорт конфігураційного файлу
require_once '../includes/config.php';
// Підключення налаштувань системи
// Імпорт модуля бази даних
require_once '../includes/db.php';
// Підключення модуля для роботи з базою даних
// Імпорт модуля автентифікації
require_once '../includes/auth.php';
// Підключення модуля для перевірки авторизації

// Встановлення формату відповіді JSON
header('Content-Type: application/json');
// Встановлення формату відповіді як JSON

// Перевірка авторизації
if (!isLoggedIn()) {
    // Перевірка, чи користувач авторизований
    echo json_encode(['success' => false, 'message' => 'Потрібна авторизація']);
    // Відповідь: потрібна авторизація
    exit();
    // Завершення виконання
}

// Ініціалізація з'єднання з базою та отримання ID користувача
$conn = getDbConnection();
// Створення з’єднання з базою даних
$user_id = $_SESSION['user_id'];
// Отримання ID користувача з сесії

// Обробка POST-запитів
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Перевірка, чи запит є POST
    // Видалення чату (для модераторів)
    if (isset($_POST['delete_chat']) && isModerator()) {
        // Перевірка запиту на видалення чату та прав модератора
        $target_user_id = isset($_POST['target_user_id']) ? (int)$_POST['target_user_id'] : 0;
        // Отримання ID цільового користувача
        if ($target_user_id <= 0) {
            // Перевірка валідності ID
            echo json_encode(['success' => false, 'message' => 'Невалідний ID користувача']);
            // Відповідь: невалідний ID
            exit();
            // Завершення виконання
        }
        // Видалення повідомлень чату
        $sql = "DELETE FROM chat_messages WHERE user_id = ?";
        // Запит для видалення повідомлень
        $stmt = $conn->prepare($sql);
        // Підготовка запиту
        $stmt->bind_param("i", $target_user_id);
        // Прив’язка ID користувача
        if ($stmt->execute()) {
            // Виконання запиту
            echo json_encode(['success' => true, 'message' => 'Чат успішно видалено']);
            // Відповідь: чат видалено
        } else {
            echo json_encode(['success' => false, 'message' => 'Помилка: ' . $stmt->error]);
            // Відповідь: помилка видалення
        }
        $stmt->close();
        // Закриття запиту
    // Позначення повідомлень як прочитаних
    } elseif (isset($_POST['mark_read'])) {
        // Перевірка запиту на позначення прочитаних повідомлень
        $target_user_id = isset($_POST['target_user_id']) && isModerator() ? (int)$_POST['target_user_id'] : $user_id;
        // Вибір ID користувача (модератор або поточний)
        if ($target_user_id <= 0) {
            // Перевірка валідності ID
            echo json_encode(['success' => false, 'message' => 'Невалідний ID користувача']);
            // Відповідь: невалідний ID
            exit();
            // Завершення виконання
        }
        // Оновлення статусу непрочитаних повідомлень
        $sql = isModerator()
            ? "UPDATE chat_messages SET is_read = 1 WHERE user_id = ? AND is_admin_reply = 0 AND is_read = 0"
            : "UPDATE chat_messages SET is_read = 1 WHERE user_id = ? AND is_admin_reply = 1 AND is_read = 0";
        // Запит для позначення повідомлень прочитаними (залежить від ролі)
        $stmt = $conn->prepare($sql);
        // Підготовка запиту
        $stmt->bind_param("i", $target_user_id);
        // Прив’язка ID користувача
        if ($stmt->execute()) {
            // Виконання запиту
            echo json_encode(['success' => true, 'message' => 'Повідомлення позначено як прочитані']);
            // Відповідь: повідомлення прочитано
        } else {
            echo json_encode(['success' => false, 'message' => 'Помилка: ' . $stmt->error]);
            // Відповідь: помилка оновлення
        }
        $stmt->close();
        // Закриття запиту
    // Надсилання повідомлення
    } else {
        $message = isset($_POST['message']) ? $conn->real_escape_string(trim($_POST['message'])) : '';
        // Отримання та очищення тексту повідомлення
        $is_admin_reply = isset($_POST['admin_reply']) && isModerator() ? 1 : 0;
        // Перевірка, чи повідомлення від адміністратора
        $target_user_id = isset($_POST['target_user_id']) && isModerator() ? (int)$_POST['target_user_id'] : $user_id;
        // Вибір ID користувача (модератор або поточний)
        
        if (empty($message)) {
            // Перевірка, чи повідомлення не порожнє
            echo json_encode(['success' => false, 'message' => 'Повідомлення не може бути порожнім']);
            // Відповідь: порожнє повідомлення
            exit();
            // Завершення виконання
        }
        
        if (strlen($message) > 1000) {
            // Перевірка довжини повідомлення
            echo json_encode(['success' => false, 'message' => 'Повідомлення не може перевищувати 1000 символів']);
            // Відповідь: занадто довге повідомлення
            exit();
            // Завершення виконання
        }
        
        if ($is_admin_reply && $target_user_id <= 0) {
            // Перевірка валідності ID для адмін-відповіді
            echo json_encode(['success' => false, 'message' => 'Невалідний ID користувача']);
            // Відповідь: невалідний ID
            exit();
            // Завершення виконання
        }
        
        // Додавання нового повідомлення
        $sql = "INSERT INTO chat_messages (user_id, message, is_admin_reply, is_read) VALUES (?, ?, ?, 0)";
        // Запит для додавання повідомлення
        $stmt = $conn->prepare($sql);
        // Підготовка запиту
        $stmt->bind_param("isi", $target_user_id, $message, $is_admin_reply);
        // Прив’язка параметрів
        if ($stmt->execute()) {
            // Виконання запиту
            echo json_encode(['success' => true, 'message' => 'Повідомлення надіслано']);
            // Відповідь: повідомлення надіслано
        } else {
            echo json_encode(['success' => false, 'message' => 'Помилка: ' . $stmt->error]);
            // Відповідь: помилка надсилання
        }
        $stmt->close();
        // Закриття запиту
    }
// Обробка GET-запитів (отримання повідомлень)
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Перевірка, чи запит є GET
    $target_user_id = isset($_GET['target_user_id']) && isModerator() ? (int)$_GET['target_user_id'] : $user_id;
    // Вибір ID користувача (модератор або поточний)
    
    if ($target_user_id <= 0) {
        // Перевірка валідності ID
        echo json_encode(['success' => false, 'message' => 'Невалідний ID користувача']);
        // Відповідь: невалідний ID
        exit();
        // Завершення виконання
    }
    
    // Отримання повідомлень чату
    $sql = "SELECT cm.id, cm.user_id, cm.message, cm.is_admin_reply, cm.created_at, u.name 
            FROM chat_messages cm 
            JOIN users u ON cm.user_id = u.id 
            WHERE cm.user_id = ? OR (cm.is_admin_reply = 1 AND cm.user_id = ?) 
            ORDER BY cm.created_at ASC";
    // Запит для отримання повідомлень
    $stmt = $conn->prepare($sql);
    // Підготовка запиту
    $stmt->bind_param("ii", $target_user_id, $target_user_id);
    // Прив’язка ID користувача
    if ($stmt->execute()) {
        // Виконання запиту
        $result = $stmt->get_result();
        // Отримання результатів
        $messages = [];
        // Масив для повідомлень
        while ($row = $result->fetch_assoc()) {
            // Обробка кожного повідомлення
            $messages[] = [
                'id' => $row['id'],
                'user_id' => $row['user_id'],
                'name' => $row['is_admin_reply'] ? 'Адміністратор' : $row['name'],
                'message' => $row['message'],
                'is_admin_reply' => (bool)$row['is_admin_reply'],
                'created_at' => date('d.m.Y H:i', strtotime($row['created_at']))
            ];
            // Формування структури повідомлення
        }
        echo json_encode(['success' => true, 'messages' => $messages]);
        // Відповідь: список повідомлень
    } else {
        echo json_encode(['success' => false, 'message' => 'Помилка запиту: ' . $stmt->error]);
        // Відповідь: помилка запиту
    }
    $stmt->close();
    // Закриття запиту
}

// Завершення з'єднання з базою
$conn->close();
// Закриття з’єднання з базою даних
?>