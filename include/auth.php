<?php
// Налаштовує сесію, щоб вона діяла 1 годину
ini_set('session.gc_maxlifetime', 3600); // 1 година
session_set_cookie_params(3600); // Час дії cookie сесії
// Починає сесію
session_start();

// Перевіряє, чи користувач увійшов
function isLoggedIn() {
    // Повертає true, якщо є ідентифікатор користувача
    return isset($_SESSION['user_id']);
}

// Перевіряє, чи користувач є адміністратором
function isAdmin() {
    // Повертає true, якщо користувач увійшов і має роль адміністратора
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Перевіряє, чи користувач є модератором або адміністратором
function isModerator() {
    // Повертає true, якщо користувач увійшов і має роль модератора або адміністратора
    return isLoggedIn() && isset($_SESSION['role']) && ($_SESSION['role'] === 'moderator' || $_SESSION['role'] === 'admin');
}

// Виконує вхід користувача
function login($email, $password, $conn) {
    // Очищає email від небезпечних символів
    $email = $conn->real_escape_string($email);
    // Запитує дані користувача з бази
    $sql = "SELECT id, password_hash, role FROM users WHERE email = '$email'";
    $result = $conn->query($sql);

    // Перевіряє, чи запит виконався
    if ($result === false) {
        // Записує помилку в лог
        error_log("SQL Error: " . $conn->error);
        return false;
    }

    // Якщо знайдено одного користувача
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        // Перевіряє правильність пароля
        if (password_verify($password, $user['password_hash'])) {
            // Зберігає дані користувача в сесії
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            return true;
        }
    }
    // Повертає false, якщо вхід не вдався
    return false;
}

// Виконує вихід користувача
function logout() {
    // Очищає дані сесії
    session_unset();
    // Завершує сесію
    session_destroy();
}
?>