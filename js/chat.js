// Чекає, поки сторінка повністю завантажиться
document.addEventListener('DOMContentLoaded', function () {
    // Знаходить елементи чату на сторінці
    const chatMessages = document.getElementById('chat-messages');
    const chatMessageInput = document.getElementById('chat-message');
    const sendButton = document.getElementById('send-message');

    // Функція для відображення повідомлень
    function displayMessages(messages) {
        // Очищає область чату
        chatMessages.innerHTML = '';
        // Додає кожне повідомлення до чату
        messages.forEach(msg => {
            const messageDiv = document.createElement('div');
            // Визначає стиль для повідомлень (адмін чи користувач)
            messageDiv.className = `message ${msg.is_admin_reply ? 'admin' : 'user'}`;
            // Створює вміст повідомлення з ім’ям і текстом
            messageDiv.innerHTML = `
                <div class="message-header">${msg.name} (${msg.created_at})</div>
                <div class="message-text">${msg.message}</div>
            `;
            // Додає повідомлення до чату
            chatMessages.appendChild(messageDiv);
        });
        // Прокручує чат донизу
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // Функція для позначення повідомлень як прочитаних
    function markMessagesAsRead() {
        // Відправляє запит на сервер, щоб позначити повідомлення прочитаними
        fetch('chat_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `mark_read=1`
        })
        .then(response => response.json())
        .then(data => {
            // Якщо успішно, виводить повідомлення в консоль
            if (data.success) {
                console.log('Повідомлення позначено як прочитані');
            } else {
                // Якщо помилка, показує її в консолі
                console.error('Помилка позначення:', data.message);
            }
        })
        .catch(error => console.error('Помилка:', error)); // Обробляє помилки зв’язку
    }

    // Функція для отримання повідомлень
    function fetchMessages() {
        // Запитує повідомлення з сервера
        fetch('chat_handler.php')
            .then(response => response.json())
            .then(data => {
                // Якщо успішно, відображає повідомлення
                if (data.success) {
                    displayMessages(data.messages);
                    // Позначає повідомлення як прочитані
                    markMessagesAsRead();
                } else {
                    // Якщо помилка, показує її в консолі
                    console.error('Помилка отримання повідомлень:', data.message);
                }
            })
            .catch(error => {
                // Обробляє помилки зв’язку
                console.error('Помилка:', error);
            });
    }

    // Надсилання повідомлення
    sendButton.addEventListener('click', function () {
        // Отримує текст повідомлення
        const message = chatMessageInput.value.trim();
        // Перевіряє, чи повідомлення не пусте
        if (!message) {
            alert('Будь ласка, введіть повідомлення.');
            return;
        }

        // Блокує кнопку надсилання
        sendButton.disabled = true;
        sendButton.textContent = 'Надсилання...';

        // Створює дані для відправки
        const formData = new FormData();
        formData.append('message', message);

        // Відправляє повідомлення на сервер
        fetch('chat_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Якщо успішно, очищає поле вводу і оновлює чат
            if (data.success) {
                chatMessageInput.value = '';
                fetchMessages();
            } else {
                // Якщо помилка, показує повідомлення
                alert(data.message);
            }
        })
        .catch(error => {
            // Обробляє помилки зв’язку
            console.error('Помилка:', error);
            alert('Помилка надсилання повідомлення.');
        })
        .finally(() => {
            // Розблоковує кнопку надсилання
            sendButton.disabled = false;
            sendButton.textContent = 'Надіслати';
        });
    });

    // Дозволяє надсилати повідомлення клавішею Enter
    chatMessageInput.addEventListener('keypress', function (e) {
        // Якщо натиснуто Enter без Shift, надсилає повідомлення
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendButton.click();
        }
    });

    // Оновлює чат кожні 5 секунд
    fetchMessages();
    setInterval(fetchMessages, 5000);
});