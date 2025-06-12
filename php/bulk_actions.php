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

// Перевірка прав адміністратора
if (!isAdmin()) {
    // Перевірка, чи користувач є адміністратором
    http_response_code(403);
    // Встановлення коду помилки: доступ заборонено
    echo json_encode(['success' => false, 'message' => 'Недостатньо прав для виконання масової дії'], JSON_UNESCAPED_UNICODE);
    // Відповідь: недостатньо прав
    exit;
    // Завершення виконання
}

// Ініціалізація з'єднання з базою даних
$conn = getDbConnection();
// Створення з’єднання з базою даних
// Встановлення формату відповіді JSON
header('Content-Type: application/json');
// Встановлення формату відповіді як JSON
$response = ['success' => false, 'message' => ''];
// Ініціалізація структури відповіді

// Перевірка методу запиту та дії
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['bulk_action'])) {
    // Перевірка, чи запит є POST і чи передано дію
    $response['message'] = 'Некоректний запит.';
    // Повідомлення: некоректний запит
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    // Виведення відповіді
    exit;
    // Завершення виконання
}

// Отримання дії та списку звітів
$bulk_action = $_POST['bulk_action'];
// Отримання типу масової дії
$report_ids = isset($_POST['report_ids']) && is_array($_POST['report_ids']) ? array_map('intval', $_POST['report_ids']) : [];
// Отримання списку ID звітів як цілі числа

// Логування запиту
error_log("Bulk action requested: action: $bulk_action, report_ids: " . implode(',', $report_ids));
// Логування інформації про запит

// Перевірка наявності звітів
if (empty($report_ids)) {
    // Перевірка, чи вибрано звіти
    $response['message'] = 'Не вибрано жодного звіту.';
    // Повідомлення: звіти не вибрано
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    // Виведення відповіді
    exit;
    // Завершення виконання
}

// Початок транзакції
try {
    $conn->begin_transaction();
    // Початок транзакції для безпечного виконання

    // Зміна статусу звітів
    if ($bulk_action === 'change_status') {
        // Перевірка дії: зміна статусу
        $new_status = isset($_POST['new_status']) ? $conn->real_escape_string($_POST['new_status']) : '';
        // Отримання та очищення нового статусу
        if (!in_array($new_status, ['new', 'in_progress', 'resolved'])) {
            // Перевірка валідності статусу
            $response['message'] = 'Некоректний статус.';
            // Повідомлення: некоректний статус
        } else {
            // Оновлення статусу для вибраних звітів
            $placeholders = implode(',', array_fill(0, count($report_ids), '?'));
            // Створення заповнювачів для ID
            $stmt = $conn->prepare("UPDATE reports SET status = ? WHERE id IN ($placeholders)");
            // Підготовка запиту для оновлення статусу
            $params = array_merge([$new_status], $report_ids);
            // Об’єднання статусу та ID звітів
            $types = 's' . str_repeat('i', count($report_ids));
            // Визначення типів параметрів
            $stmt->bind_param($types, ...$params);
            // Прив’язка параметрів
            if ($stmt->execute()) {
                // Виконання запиту
                $response['success'] = true;
                $response['message'] = 'Статус звітів успішно змінено.';
                // Відповідь: статус змінено
                error_log("Bulk status change: new_status: $new_status, report_ids: " . implode(',', $report_ids));
                // Логування успішної зміни
            } else {
                throw new Exception('Помилка зміни статусу: ' . $stmt->error);
                // Помилка: невдале оновлення
            }
            $stmt->close();
            // Закриття запиту
        }
    // Видалення звітів
    } elseif ($bulk_action === 'delete') {
        // Перевірка дії: видалення
        // Отримання шляхів до фото
        $placeholders = implode(',', array_fill(0, count($report_ids), '?'));
        // Створення заповнювачів для ID
        $stmt = $conn->prepare("SELECT photo FROM reports WHERE id IN ($placeholders)");
        // Підготовка запиту для отримання фото
        $stmt->bind_param(str_repeat('i', count($report_ids)), ...$report_ids);
        // Прив’язка ID звітів
        $stmt->execute();
        // Виконання запиту
        $result = $stmt->get_result();
        // Отримання результатів
        // Видалення файлів фото
        while ($row = $result->fetch_assoc()) {
            // Обробка кожного звіту
            if ($row['photo']) {
                // Перевірка наявності фото
                $photo_path = '../' . $row['photo'];
                // Формування шляху до фото
                if (file_exists($photo_path)) {
                    // Перевірка існування файлу
                    if (!unlink($photo_path)) {
                        // Видалення файлу
                        error_log("Failed to delete photo: $photo_path");
                        // Логування: помилка видалення фото
                    }
                }
            }
        }
        $stmt->close();
        // Закриття запиту

        // Видалення звітів із бази
        $stmt = $conn->prepare("DELETE FROM reports WHERE id IN ($placeholders)");
        // Підготовка запиту для видалення звітів
        $stmt->bind_param(str_repeat('i', count($report_ids)), ...$report_ids);
        // Прив’язка ID звітів
        if ($stmt->execute()) {
            // Виконання запиту
            $response['success'] = true;
            $response['message'] = 'Вибрані звіти успішно видалено.';
            // Відповідь: звіти видалено
            error_log("Bulk delete: report_ids: " . implode(',', $report_ids));
            // Логування успішного видалення
        } else {
            throw new Exception('Помилка видалення: ' . $stmt->error);
            // Помилка: невдале видалення
        }
        $stmt->close();
        // Закриття запиту
    } else {
        $response['message'] = 'Некоректна дія.';
        // Повідомлення: некоректна дія
    }

    // Підтвердження транзакції
    $conn->commit();
    // Підтвердження транзакції
} catch (Exception $e) {
    // Відкат транзакції у разі помилки
    $conn->rollback();
    // Відкат змін
    $response['message'] = $e->getMessage();
    // Повідомлення: текст помилки
    error_log("Transaction error in bulk action: " . $e->getMessage());
    // Логування помилки
}

// Виведення відповіді
echo json_encode($response, JSON_UNESCAPED_UNICODE);
// Виведення JSON-відповіді
// Завершення з'єднання з базою
$conn->close();
// Закриття з’єднання з базою даних
?>