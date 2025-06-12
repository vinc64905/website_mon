document.addEventListener('DOMContentLoaded', function () {
    console.log('admin.js loaded'); // Повідомлення про завантаження скрипта

    // Автоматичне зникнення повідомлень
    const messages = document.querySelectorAll('.message, .error'); // Вибір усіх повідомлень
    messages.forEach(message => {
        setTimeout(() => message.remove(), 5000); // Видалення повідомлення через 5 секунд
    });

    // Показ/приховування форм редагування
    const editButtons = document.querySelectorAll('.edit-user-btn'); // Вибір кнопок редагування
    const cancelButtons = document.querySelectorAll('.cancel-btn'); // Вибір кнопок скасування

    editButtons.forEach(button => {
        button.addEventListener('click', function () { // Дія при натисканні кнопки редагування
            const userId = button.dataset.userId; // Отримання ID користувача
            if (userId == 2) { // Перевірка, чи це захищений акаунт
                alert('Цей акаунт не можна редагувати.');
                return;
            }
            const editForm = document.getElementById(`edit-form-${userId}`); // Пошук форми редагування
            if (editForm) {
                const isVisible = editForm.style.display === 'table-row'; // Перевірка видимості форми
                // Ховаємо всі форми
                document.querySelectorAll('.edit-form-row').forEach(form => {
                    form.style.display = 'none'; // Приховування всіх форм
                });
                // Показуємо або ховаємо потрібну форму
                editForm.style.display = isVisible ? 'none' : 'table-row'; // Перемикання видимості форми
            }
        });
    });

    cancelButtons.forEach(button => {
        button.addEventListener('click', function () { // Дія при натисканні кнопки скасування
            const userId = button.dataset.userId; // Отримання ID користувача
            const editForm = document.getElementById(`edit-form-${userId}`); // Пошук форми
            if (editForm) {
                editForm.style.display = 'none'; // Приховування форми
            }
        });
    });

    // Валідація форм редагування
    document.querySelectorAll('.edit-user-form').forEach(form => {
        form.addEventListener('submit', function (e) { // Дія при відправленні форми
            let errors = []; // Список помилок
            const userId = form.querySelector('input[name="user_id"]').value; // Отримання ID користувача
            const email = form.querySelector('input[name="email"]').value.trim(); // Отримання email
            const name = form.querySelector('input[name="name"]').value.trim(); // Отримання імені
            const phone = form.querySelector('input[name="phone"]').value.trim(); // Отримання телефону

            if (userId == 2) { // Перевірка захищеного акаунту
                e.preventDefault();
                errors.push('Цей акаунт не можна редагувати.');
            }

            if (!email) { // Перевірка, чи введено email
                errors.push('Будь ласка, введіть email.');
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { // Перевірка формату email
                errors.push('Некоректний формат email.');
            }

            if (name && !/^[А-ЯҐЄІЇа-яґєії\s-]{1,100}$/.test(name)) { // Перевірка формату імені
                errors.push('Ім’я має містити лише українські літери, пробіли, дефіси та бути не довшим за 100 символів.');
            }

            if (phone && !/^\+?\d{10,15}$/.test(phone)) { // Перевірка формату телефону
                errors.push('Некоректний формат номера телефону (наприклад, +380123456789).');
            }

            if (errors.length > 0) { // Якщо є помилки
                e.preventDefault();
                const errorContainer = document.createElement('p'); // Створення контейнера для помилок
                errorContainer.className = 'error';
                errorContainer.textContent = errors.join(' '); // Виведення всіх помилок
                const existingError = form.querySelector('.error'); // Пошук існуючої помилки
                if (existingError) existingError.remove(); // Видалення старої помилки
                form.prepend(errorContainer); // Додавання нової помилки
                setTimeout(() => errorContainer.remove(), 5000); // Видалення помилки через 5 секунд
            } else { // Якщо помилок немає
                e.preventDefault();
                const formData = new FormData(form); // Збір даних форми
                fetch('admin.php', { // Відправлення даних на сервер
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text()) // Обробка відповіді
                .then(() => {
                    fetchUsers(); // Оновлення списку користувачів
                })
                .catch(error => { // Обробка помилок сервера
                    console.error('Помилка редагування:', error);
                    const errorContainer = document.createElement('p');
                    errorContainer.className = 'error';
                    errorContainer.textContent = 'Помилка при редагуванні користувача.';
                    form.prepend(errorContainer);
                    setTimeout(() => errorContainer.remove(), 5000);
                });
            }
        });
    });

    // Динамічна фільтрація користувачів
    const userFilterForm = document.getElementById('user-filter-form'); // Пошук форми фільтрації
    if (userFilterForm) {
        const searchIdInput = document.getElementById('search_id'); // Поле пошуку за ID
        const searchEmailInput = document.getElementById('search_email'); // Поле пошуку за email
        let currentUserFilters = { search_id: '', search_email: '', page: 1 }; // Поточні фільтри

        function fetchUsers() { // Функція для отримання користувачів
            console.log('Fetching users with filters:', currentUserFilters);
            const usersContainer = document.getElementById('users-container'); // Контейнер для користувачів
            if (!usersContainer) {
                console.error('Users container not found');
                return;
            }
            usersContainer.innerHTML = '<p class="filter-message"><i class="fas fa-spinner fa-spin"></i> Завантаження...</p>'; // Показ індикатора завантаження

            const params = new URLSearchParams(currentUserFilters); // Формування параметрів запиту
            const url = `filter_admin_users.php?${params.toString()}`; // URL для запиту
            console.log('Fetching users URL:', url);

            fetch(url, { // Відправлення запиту
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) { // Перевірка статусу відповіді
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json(); // Отримання даних
            })
            .then(data => { // Обробка отриманих даних
                console.log('Received users data:', data);
                if (data.success) {
                    usersContainer.innerHTML = data.users_html; // Виведення списку користувачів
                    bindEditEvents(); // Повторна прив’язка подій
                } else {
                    usersContainer.innerHTML = '<p class="filter-message">Помилка: ' + (data.message || 'Невідома помилка') + '</p>'; // Виведення помилки
                }
            })
            .catch(error => { // Обробка помилок
                console.error('Помилка завантаження користувачів:', error);
                usersContainer.innerHTML = '<p class="filter-message">Помилка завантаження користувачів. Спробуйте ще раз.</p>';
            });
        }

        // Прив’язка подій до кнопок редагування та скасування
        function bindEditEvents() { // Функція для повторної прив’язки подій
            const editButtons = document.querySelectorAll('.edit-user-btn'); // Вибір кнопок редагування
            const cancelButtons = document.querySelectorAll('.cancel-btn'); // Вибір кнопок скасування

            editButtons.forEach(button => {
                button.addEventListener('click', function () { // Дія для кнопки редагування
                    const userId = button.dataset.userId; // Отримання ID
                    if (userId == 2) {
                        alert('Цей акаунт не можна редагувати.');
                        return;
                    }
                    const editForm = document.getElementById(`edit-form-${userId}`); // Пошук форми
                    if (editForm) {
                        const isVisible = editForm.style.display === 'table-row'; // Перевірка видимості
                        document.querySelectorAll('.edit-form-row').forEach(form => {
                            form.style.display = 'none'; // Приховування всіх форм
                        });
                        editForm.style.display = isVisible ? 'none' : 'table-row'; // Перемикання видимості
                    }
                });
            });

            cancelButtons.forEach(button => {
                button.addEventListener('click', function () { // Дія для кнопки скасування
                    const userId = button.dataset.userId; // Отримання ID
                    const editForm = document.getElementById(`edit-form-${userId}`); // Пошук форми
                    if (editForm) {
                        editForm.style.display = 'none'; // Приховування форми
                    }
                });
            });

            // Повторно прив’язуємо валідацію до нових форм
            document.querySelectorAll('.edit-user-form').forEach(form => {
                form.addEventListener('submit', function (e) { // Дія при відправленні форми
                    let errors = [];
                    const userId = form.querySelector('input[name="user_id"]').value; // Отримання ID
                    const email = form.querySelector('input[name="email"]').value.trim(); // Отримання email
                    const name = form.querySelector('input[name="name"]').value.trim(); // Отримання імені
                    const phone = form.querySelector('input[name="phone"]').value.trim(); // Отримання телефону

                    if (userId == 2) { // Перевірка захищеного акаунту
                        e.preventDefault();
                        errors.push('Цей акаунт не можна редагувати.');
                    }

                    if (!email) { // Перевірка email
                        errors.push('Будь ласка, введіть email.');
                    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { // Перевірка формату email
                        errors.push('Некоректний формат email.');
                    }

                    if (name && !/^[А-ЯҐЄІЇа-яґєії\s-]{1,100}$/.test(name)) { // Перевірка імені
                        errors.push('Ім’я має містити лише українські літери, пробіли, дефіси та бути не довшим за 100 символів.');
                    }

                    if (phone && !/^\+?\d{10,15}$/.test(phone)) { // Перевірка телефону
                        errors.push('Некоректний формат номера телефону (наприклад, +380123456789).');
                    }

                    if (errors.length > 0) { // Якщо є помилки
                        e.preventDefault();
                        const errorContainer = document.createElement('p'); // Контейнер для помилок
                        errorContainer.className = 'error';
                        errorContainer.textContent = errors.join(' '); // Виведення помилок
                        const existingError = form.querySelector('.error'); // Пошук старої помилки
                        if (existingError) existingError.remove(); // Видалення старої помилки
                        form.prepend(errorContainer); // Додавання нової помилки
                        setTimeout(() => errorContainer.remove(), 5000); // Видалення через 5 секунд
                    } else { // Якщо помилок немає
                        e.preventDefault();
                        const formData = new FormData(form); // Збір даних форми
                        fetch('admin.php', { // Відправлення на сервер
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.text()) // Обробка відповіді
                        .then(() => {
                            fetchUsers(); // Оновлення списку
                        })
                        .catch(error => { // Обробка помилок
                            console.error('Помилка редагування:', error);
                            const errorContainer = document.createElement('p');
                            errorContainer.className = 'error';
                            errorContainer.textContent = 'Помилка при редагуванні користувача.';
                            form.prepend(errorContainer);
                            setTimeout(() => errorContainer.remove(), 5000);
                        });
                    }
                });
            });
        }

        // Обробка введення ID
        searchIdInput.addEventListener('input', function () { // Дія при введенні ID
            currentUserFilters.search_id = this.value.trim(); // Оновлення фільтру ID
            currentUserFilters.page = 1; // Скидання сторінки
            console.log('Search ID changed:', currentUserFilters);
            fetchUsers(); // Оновлення списку
        });

        // Обробка введення Email
        searchEmailInput.addEventListener('input', function () { // Дія при введенні email
            currentUserFilters.search_email = this.value.trim(); // Оновлення фільтру email
            currentUserFilters.page = 1; // Скидання сторінки
            console.log('Search Email changed:', currentUserFilters);
            fetchUsers(); // Оновлення списку
        });

        // Обробка пагінації
        document.addEventListener('click', function (e) { // Дія при натисканні на посилання сторінки
            if (e.target.classList.contains('user-page-link')) {
                e.preventDefault();
                currentUserFilters.page = parseInt(e.target.dataset.page); // Оновлення сторінки
                console.log('User page changed:', currentUserFilters);
                fetchUsers(); // Оновлення списку
            }
        });

        // Обробка скидання фільтрів
        userFilterForm.querySelector('.reset-filters').addEventListener('click', function () { // Дія при скиданні фільтрів
            console.log('Resetting user filters');
            searchIdInput.value = ''; // Очищення поля ID
            searchEmailInput.value = ''; // Очищення поля email
            currentUserFilters = { search_id: '', search_email: '', page: 1 }; // Скидання фільтрів
            fetchUsers(); // Оновлення списку
        });

        // Початкове завантаження користувачів
        currentUserFilters.search_id = searchIdInput.value.trim(); // Отримання початкового ID
        currentUserFilters.search_email = searchEmailInput.value.trim(); // Отримання початкового email
        fetchUsers(); // Завантаження користувачів
    }
});