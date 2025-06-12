document.addEventListener('DOMContentLoaded', function () {
    const userList = document.getElementById('user-list'); // Список користувачів
    const userSearch = document.getElementById('user-search'); // Поле пошуку
    const chatMessages = document.getElementById('chat-messages'); // Область повідомлень
    const chatUserName = document.getElementById('chat-user-name'); // Ім’я користувача в чаті
    const chatInput = document.getElementById('chat-input'); // Поле введення повідомлення
    const chatMessageInput = document.getElementById('chat-message'); // Текстове поле для повідомлення
    const sendButton = document.getElementById('send-message'); // Кнопка надсилання
    const deleteChatButton = document.getElementById('delete-chat'); // Кнопка видалення чату

    let currentUserId = null; // ID поточного користувача
    let lastUsersHash = ''; // Хеш списку користувачів

    // Перевірка елементів
    if (!userList) {
        console.error('Елемент #user-list не знайдено'); // Помилка, якщо список не знайдено
        return;
    }
    if (!userSearch) {
        console.error('Елемент #user-search не знайдено'); // Помилка, якщо поле пошуку не знайдено
        return;
    }
    if (!deleteChatButton) {
        console.error('Елемент #delete-chat не знайдено'); // Помилка, якщо кнопку видалення не знайдено
        return;
    }
    console.log('Поле пошуку ініціалізовано:', userSearch); // Повідомлення про ініціалізацію

    // Функція для відображення повідомлень
    function displayMessages(messages) { // Відображення повідомлень у чаті
        chatMessages.innerHTML = ''; // Очищення області повідомлень
        messages.forEach(msg => {
            const messageDiv = document.createElement('div'); // Створення контейнера для повідомлення
            messageDiv.className = `message ${msg.is_admin_reply ? 'admin' : 'user'}`; // Клас для стилізації
            messageDiv.innerHTML = `
                <div class="message-header">${msg.name} (${msg.created_at})</div>
                <div class="message-text">${msg.message}</div>
            `; // Вміст повідомлення
            chatMessages.appendChild(messageDiv); // Додавання повідомлення
        });
        chatMessages.scrollTop = chatMessages.scrollHeight; // Прокрутка донизу
    }

    // Функція для отримання повідомлень
    function fetchMessages(userId) { // Отримання повідомлень для користувача
        console.log('Отримання повідомлень для userId:', userId);
        fetch(`chat_handler.php?target_user_id=${userId}`) // Запит на сервер
            .then(response => response.json()) // Обробка відповіді
            .then(data => {
                if (data.success) {
                    console.log('Отримано повідомлення:', data.messages);
                    displayMessages(data.messages); // Відображення повідомлень
                    markMessagesAsRead(userId); // Позначення як прочитаних
                } else {
                    console.error('Помилка отримання повідомлень:', data.message);
                }
            })
            .catch(error => {
                console.error('Помилка запиту:', error); // Обробка помилок
            });
    }

    // Функція для позначення повідомлень як прочитаних
    function markMessagesAsRead(userId) { // Позначення повідомлень як прочитаних
        fetch('chat_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `mark_read=1&target_user_id=${userId}` // Відправлення запиту
        })
        .then(response => response.json()) // Обробка відповіді
        .then(data => {
            if (data.success) {
                console.log('Повідомлення позначено як прочитані');
                fetchUsers(); // Оновлення списку користувачів
            } else {
                console.error('Помилка позначення:', data.message);
            }
        })
        .catch(error => console.error('Помилка:', error)); // Обробка помилок
    }

    // Функція для видалення чату
    function deleteChat(userId) { // Видалення чату
        if (!confirm('Ви впевнені, що хочете видалити цей чат? Усі повідомлення будуть втрачені.')) { // Підтвердження дії
            return;
        }
        fetch('chat_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `delete_chat=1&target_user_id=${userId}` // Відправлення запиту
        })
        .then(response => response.json()) // Обробка відповіді
        .then(data => {
            if (data.success) {
                console.log('Чат видалено');
                chatMessages.innerHTML = ''; // Очищення чату
                chatUserName.textContent = 'Оберіть користувача'; // Скидання заголовка
                chatInput.style.display = 'none'; // Приховування поля введення
                deleteChatButton.style.display = 'none'; // Приховування кнопки
                currentUserId = null; // Скидання ID
                fetchUsers(); // Оновлення списку
            } else {
                alert(data.message); // Виведення помилки
                console.error('Помилка видалення чату:', data.message);
            }
        })
        .catch(error => {
            console.error('Помилка:', error); // Обробка помилок
            alert('Помилка видалення чату.');
        });
    }

    // Функція для відображення списку користувачів
    function displayUsers(users) { // Відображення списку користувачів
        const currentActiveId = currentUserId; // Збереження ID активного користувача
        userList.innerHTML = ''; // Очищення списку
        if (users.length === 0) {
            userList.innerHTML = '<p>Немає активних чатів. Очікуйте повідомлень від користувачів.</p>'; // Повідомлення, якщо чатів немає
            return;
        }
        users.forEach(user => {
            const li = document.createElement('li'); // Створення елемента списку
            li.dataset.userId = user.id; // Додавання ID
            li.dataset.userName = user.name; // Додавання імені
            li.dataset.userEmail = user.email; // Додавання email
            li.dataset.unreadCount = user.unread_count; // Додавання кількості непрочитаних
            li.innerHTML = `${user.name} (${user.email})${user.unread_count > 0 ? `<span class="user-unread-count">${user.unread_count}</span>` : ''}`; // Вміст елемента
            if (user.id == currentActiveId) {
                li.classList.add('active'); // Позначення активного користувача
            }
            userList.appendChild(li); // Додавання до списку
        });
    }

    // Функція для отримання списку користувачів і лічильника
    function fetchUsers() { // Отримання списку користувачів
        fetch('get_users.php') // Запит на сервер
            .then(response => response.json()) // Обробка відповіді
            .then(data => {
                if (data.success) {
                    console.log('Отримано дані:', { users: data.users, unread_count: data.unread_count });
                    const usersHash = JSON.stringify(data.users); // Хешування списку
                    if (usersHash !== lastUsersHash) { // Перевірка змін
                        console.log('Оновлення списку користувачів:', data.users);
                        displayUsers(data.users); // Відображення списку
                        lastUsersHash = usersHash; // Оновлення хешу
                    }
                    updateAdminChatUnreadCount(data.unread_count); // Оновлення лічильника
                } else {
                    console.error('Помилка отримання користувачів:', data.message);
                }
            })
            .catch(error => console.error('Помилка:', error)); // Обробка помилок
    }

    // Функція для оновлення лічильника адмін-чату
    function updateAdminChatUnreadCount(count) { // Оновлення лічильника непрочитаних
        const adminChatBtn = document.querySelector('.admin-chat-btn'); // Кнопка чату
        if (adminChatBtn) {
            let unreadCount = adminChatBtn.querySelector('.unread-count'); // Лічильник
            if (count > 0) {
                if (!unreadCount) {
                    unreadCount = document.createElement('span'); // Створення лічильника
                    unreadCount.className = 'unread-count';
                    adminChatBtn.appendChild(unreadCount); // Додавання до кнопки
                }
                unreadCount.textContent = count; // Оновлення значення
            } else if (unreadCount) {
                unreadCount.classList.add('remove'); // Позначення для видалення
                setTimeout(() => unreadCount.remove(), 300); // Видалення лічильника
            }
        }
    }

    // Обробка вибору користувача
    userList.addEventListener('click', function (e) { // Дія при виборі користувача
        const li = e.target.tagName === 'LI' ? e.target : e.target.closest('li'); // Вибір елемента списку
        if (!li) {
            console.warn('Клік не на елемент <li>');
            return;
        }

        if (!li.dataset.userId || !li.dataset.userName) { // Перевірка даних
            console.error('Відсутні data-user-id або data-user-name у <li>:', li);
            return;
        }

        currentUserId = li.dataset.userId; // Оновлення ID
        const userName = li.dataset.userName; // Отримання імені
        console.log('Обрано користувача:', { userId: currentUserId, userName });

        userList.querySelectorAll('li').forEach(item => item.classList.remove('active')); // Зняття активності
        li.classList.add('active'); // Позначення активного користувача

        chatUserName.textContent = `Чат з ${userName}`; // Оновлення заголовка
        chatInput.style.display = 'flex'; // Показ поля введення
        deleteChatButton.style.display = 'inline-block'; // Показ кнопки видалення
        fetchMessages(currentUserId); // Завантаження повідомлень
    });

    // Обробка видалення чату
    deleteChatButton.addEventListener('click', function () { // Дія при видаленні чату
        if (!currentUserId) {
            alert('Оберіть користувача для видалення чату.');
            return;
        }
        deleteChat(currentUserId); // Виклик функції видалення
    });

    // Надсилання повідомлення
    sendButton.addEventListener('click', function () { // Дія при надсиланні повідомлення
        if (!currentUserId) {
            alert('Оберіть користувача.');
            return;
        }

        const message = chatMessageInput.value.trim(); // Отримання тексту
        if (!message) {
            alert('Будь ласка, введіть повідомлення.');
            return;
        }

        sendButton.disabled = true; // Вимкнення кнопки
        sendButton.textContent = 'Надсилання...'; // Зміна тексту кнопки

        const formData = new FormData(); // Створення даних форми
        formData.append('message', message); // Додавання повідомлення
        formData.append('admin_reply', '1'); // Позначення як відповідь адміна
        formData.append('target_user_id', currentUserId); // Додавання ID користувача

        fetch('chat_handler.php', { // Відправлення на сервер
            method: 'POST',
            body: formData
        })
        .then(response => response.json()) // Обробка відповіді
        .then(data => {
            if (data.success) {
                console.log('Повідомлення надіслано');
                chatMessageInput.value = ''; // Очищення поля
                fetchMessages(currentUserId); // Оновлення повідомлень
            } else {
                console.error('Помилка надсилання:', data.message);
                alert(data.message); // Виведення помилки
            }
        })
        .catch(error => {
            console.error('Помилка:', error); // Обробка помилок
            alert('Помилка надсилання повідомлення.');
        })
        .finally(() => {
            sendButton.disabled = false; // Увімкнення кнопки
            sendButton.textContent = 'Надіслати'; // Відновлення тексту
        });
    });

    // Дозволити надсилання повідомлення клавішею Enter
    chatMessageInput.addEventListener('keypress', function (e) { // Дія при натисканні клавіші
        if (e.key === 'Enter' && !e.shiftKey) { // Якщо натиснуто Enter
            e.preventDefault();
            sendButton.click(); // Виклик надсилання
        }
    });

    // Пошук користувачів
    userSearch.addEventListener('input', function () { // Дія при введенні в пошук
        console.log('Пошук активовано, значення:', this.value);
        const searchTerm = this.value.trim().toLowerCase(); // Отримання пошукового запиту
        const items = userList.querySelectorAll('li'); // Вибір усіх елементів списку
        items.forEach(item => {
            const name = item.dataset.userName.toLowerCase(); // Ім’я користувача
            const email = item.dataset.userEmail.toLowerCase(); // Email користувача
            item.style.display = (searchTerm === '' || name.includes(searchTerm) || email.includes(searchTerm)) ? '' : 'none'; // Фільтрація
        });
    });

    // Періодичне оновлення списку користувачів і лічильника
    fetchUsers(); // Початкове завантаження
    setInterval(fetchUsers, 10000); // Оновлення кожні 10 секунд
});