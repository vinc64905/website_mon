// Чекає, поки сторінка повністю завантажиться
document.addEventListener('DOMContentLoaded', function () {
    console.log('register.js loaded'); // Повідомляє, що скрипт завантажено

    // Знаходить форму реєстрації
    const registerForm = document.getElementById('register-form');

    if (registerForm) {
        // Обробляє відправку форми
        registerForm.addEventListener('submit', function (e) {
            let errors = []; // Зберігає помилки
            // Отримує дані з полів
            const email = document.getElementById('email').value.trim();
            const name = document.getElementById('name').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            // Перевіряє email
            if (!email) {
                errors.push('Будь ласка, введіть email.');
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                errors.push('Некоректний формат email.');
            }

            // Перевіряє ім’я
            if (!name) {
                errors.push('Будь ласка, введіть ім’я.');
            } else if (!/^[\u0400-\u04FF\s-]{1,100}$/.test(name)) {
                errors.push('Ім’я має містити лише українські літери, пробіли, дефіси та бути не довшим за 100 символів.');
            }

            // Перевіряє телефон
            if (!phone) {
                errors.push('Будь ласка, введіть номер телефону.');
            } else if (!/^\+?\d{10,15}$/.test(phone)) {
                errors.push('Некоректний формат номера телефону (наприклад, +380123456789).');
            }

            // Перевіряє пароль
            if (!password) {
                errors.push('Будь ласка, введіть пароль.');
            } else if (password !== confirmPassword) {
                errors.push('Паролі не співпадають.');
            } else if (password.length < 6) {
                errors.push('Пароль має бути не коротшим за 6 символів.');
            }

            // Якщо є помилки, блокує відправку
            if (errors.length > 0) {
                e.preventDefault();
                // Створює повідомлення про помилки
                const errorContainer = document.createElement('p');
                errorContainer.className = 'error';
                errorContainer.textContent = errors.join(' ');
                // Додає повідомлення до форми
                registerForm.prepend(errorContainer);
                // Видаляє повідомлення через 5 секунд
                setTimeout(() => errorContainer.remove(), 5000);
            }
        });
    } else {
        console.error('Register form not found'); // Повідомляє, якщо форма не знайдена
    }
});