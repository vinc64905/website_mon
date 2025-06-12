// Чекає, поки сторінка повністю завантажиться
document.addEventListener('DOMContentLoaded', function () {
    console.log('profile.js loaded'); // Повідомляє, що скрипт завантажено

    // Приховує повідомлення через 5 секунд
    const messages = document.querySelectorAll('#messages .message, #messages .error');
    messages.forEach(message => {
        setTimeout(() => message.remove(), 5000);
    });

    // Показує або ховає форми
    const actionButtons = document.querySelectorAll('.profile-actions .action-btn');
    const editInfoForm = document.querySelector('.edit-info-form');
    const changePasswordForm = document.querySelector('.change-password-form');

    actionButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            // Дозволяє перехід для певних кнопок
            if (button.classList.contains('admin-btn') || button.classList.contains('admin-chat-btn') || button.classList.contains('feedback-btn')) {
                return;
            }

            e.preventDefault();
            const formType = button.dataset.form;
            // Показує або ховає форму редагування
            if (formType === 'edit-info') {
                editInfoForm.style.display = editInfoForm.style.display === 'block' ? 'none' : 'block';
                changePasswordForm.style.display = 'none';
            // Показує або ховає форму зміни пароля
            } else if (formType === 'change-password') {
                changePasswordForm.style.display = changePasswordForm.style.display === 'block' ? 'none' : 'block';
                editInfoForm.style.display = 'none';
            }
        });
    });

    // Обробляє кнопки скасування
    const cancelButtons = document.querySelectorAll('.cancel-btn');
    cancelButtons.forEach(button => {
        button.addEventListener('click', function () {
            const formType = button.dataset.form;
            // Ховає форму редагування
            if (formType === 'edit-info') {
                editInfoForm.style.display = 'none';
            // Ховає форму зміни пароля
            } else if (formType === 'change-password') {
                changePasswordForm.style.display = 'none';
            }
        });
    });

    // Перевіряє форму редагування інформації
    if (editInfoForm) {
        editInfoForm.addEventListener('submit', function (e) {
            let errors = [];
            const email = document.getElementById('email').value.trim();
            const name = document.getElementById('name').value.trim();
            const phone = document.getElementById('phone').value.trim();

            // Перевіряє email
            if (!email) {
                errors.push('Будь ласка, введіть email.');
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                errors.push('Некоректний формат email.');
            }

            // Перевіряє ім’я
            if (!name) {
                errors.push('Будь ласка, введіть ім\'я.');
            } else if (!/^[\u0400-\u04FF\s-]{1,100}$/.test(name)) {
                errors.push('Ім’я має містити лише українські літери, пробіли, дефіси та бути не довшим за 100 символів.');
            }

            // Перевіряє телефон
            if (phone && !/^\+?\d{10,15}$/.test(phone)) {
                errors.push('Некоректний формат номера телефону (наприклад, +380123456789).');
            }

            // Якщо є помилки, блокує відправку
            if (errors.length > 0) {
                e.preventDefault();
                const errorContainer = document.createElement('p');
                errorContainer.className = 'error';
                errorContainer.textContent = errors.join(' ');
                editInfoForm.prepend(errorContainer);
                setTimeout(() => errorContainer.remove(), 5000);
            }
        });
    }

    // Перевіряє форму зміни пароля
    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', function (e) {
            let errors = [];
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            // Перевіряє пароль
            if (!password) {
                errors.push('Будь ласка, введіть новий пароль.');
            } else if (password !== confirmPassword) {
                errors.push('Паролі не співпадають.');
            } else if (password.length < 6) {
                errors.push('Пароль має бути не коротшим за 6 символів.');
            }

            // Якщо є помилки, блокує відправку
            if (errors.length > 0) {
                e.preventDefault();
                const errorContainer = document.createElement('p');
                errorContainer.className = 'error';
                errorContainer.textContent = errors.join(' ');
                changePasswordForm.prepend(errorContainer);
                setTimeout(() => errorContainer.remove(), 5000);
            }
        });
    }

    // Оновлює лічильник непрочитаних повідомлень
    function updateFeedbackUnreadCount() {
        console.log('Оновлення лічильника зворотного зв’язку');
        // Запитує дані з сервера
        fetch('get_user_messages.php', {
            cache: 'no-store' // Уникає кешування
        })
            .then(response => response.json())
            .then(data => {
                console.log('Отримано дані лічильника:', data);
                if (data.success) {
                    const feedbackBtn = document.querySelector('.feedback-btn');
                    if (feedbackBtn) {
                        let unreadCount = feedbackBtn.querySelector('.unread-count');
                        // Показує кількість непрочитаних
                        if (data.unread_count > 0) {
                            if (!unreadCount) {
                                unreadCount = document.createElement('span');
                                unreadCount.className = 'unread-count';
                                feedbackBtn.appendChild(unreadCount);
                            }
                            unreadCount.textContent = data.unread_count;
                            unreadCount.style.opacity = '1';
                        } else if (unreadCount) {
                            // Ховає лічильник, якщо немає непрочитаних
                            unreadCount.classList.add('remove');
                            setTimeout(() => {
                                unreadCount.remove();
                            }, 300);
                        }
                    }
                    // Зберігає кількість у пам’яті
                    sessionStorage.setItem('feedbackUnreadCount', data.unread_count);
                } else {
                    console.error('Помилка отримання лічильника зворотного зв’язку:', data.message);
                }
            })
            .catch(error => console.error('Помилка:', error));
    }

    // Перевіряє, чи повернулися з feedback.php
    if (sessionStorage.getItem('feedbackMessagesRead') === 'true') {
        console.log('Повернення з feedback.php, оновлення лічильника');
        sessionStorage.removeItem('feedbackMessagesRead');
        updateFeedbackUnreadCount();
    }

    // Оновлює лічильник кожні 10 секунд
    updateFeedbackUnreadCount();
    setInterval(updateFeedbackUnreadCount, 10000);

    // Оновлює таймери для звітів
    function updateTimers() {
        console.log('Updating timers');
        const timeLeftSpans = document.querySelectorAll('.time-left');
        timeLeftSpans.forEach(span => {
            let timeLeft = parseInt(span.dataset.timeLeft);
            if (timeLeft > 0) {
                // Оновлює таймер кожну секунду
                const updateTimer = () => {
                    if (timeLeft <= 0) {
                        const row = span.closest('tr');
                        const actionCell = row.querySelector('td:last-child');
                        actionCell.innerHTML = '';
                        span.textContent = '';
                        return;
                    }
                    const minutes = Math.floor(timeLeft / 60);
                    const seconds = timeLeft % 60;
                    span.textContent = `(Залишилось: ${minutes}:${seconds.toString().padStart(2, '0')})`;
                    timeLeft--;
                    setTimeout(updateTimer, 1000);
                };
                updateTimer();
            }
        });
    }

    // Обробляє видалення звітів
    function bindDeleteForms() {
        console.log('Binding delete forms');
        document.querySelectorAll('.delete-report-form').forEach(form => {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const reportId = form.dataset.reportId;
                const timeLeftSpan = form.closest('tr').querySelector('.time-left');
                const timeLeft = timeLeftSpan ? parseInt(timeLeftSpan.dataset.timeLeft) : 0;

                // Перевіряє, чи час видалення минув
                if (timeLeft <= 0) {
                    const errorMsg = document.createElement('p');
                    errorMsg.className = 'error';
                    errorMsg.textContent = 'Час для видалення звіту минув.';
                    document.querySelector('#messages').prepend(errorMsg);
                    setTimeout(() => errorMsg.remove(), 5000);
                    return;
                }

                // Підтверджує видалення
                if (!confirm('Ви впевнені, що хочете видалити цей звіт?')) {
                    return;
                }

                // Відправляє запит на сервер
                const formData = new FormData(form);
                fetch('profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    // Показує повідомлення
                    const messageContainer = document.createElement('p');
                    messageContainer.className = data.success ? 'message' : 'error';
                    messageContainer.textContent = data.message || (data.success ? 'Звіт успішно видалено!' : 'Помилка видалення звіту.');
                    document.querySelector('#messages').prepend(messageContainer);
                    setTimeout(() => messageContainer.remove(), 5000);

                    // Оновлює звіти, якщо успішно
                    if (data.success) {
                        fetchReports();
                    }
                })
                .catch(error => {
                    // Обробляє помилки
                    console.error('Помилка видалення:', error);
                    const errorMsg = document.createElement('p');
                    errorMsg.className = 'error';
                    errorMsg.textContent = 'Помилка сервера при видаленні звіту. Спробуйте ще раз.';
                    document.querySelector('#messages').prepend(errorMsg);
                    setTimeout(() => errorMsg.remove(), 5000);
                });
            });
        });
    }

    // Завантажує звіти
    let currentFilters = { status: '', type: '', sort: 'created_at', order: 'DESC', page: 1 };

    function fetchReports() {
        console.log('Fetching reports with filters:', currentFilters);
        const reportsContainer = document.getElementById('reports-container');
        if (!reportsContainer) {
            console.error('Reports container not found');
            return;
        }
        // Показує повідомлення про завантаження
        reportsContainer.innerHTML = '<p class="filter-message"><i class="fas fa-spinner fa-spin"></i> Завантаження...</p>';

        // Створює URL із фільтрами
        const params = new URLSearchParams(currentFilters);
        const url = `filter_profile_reports.php?${params.toString()}`;
        console.log('Fetching URL:', url);

        // Запитує звіти
        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Received data:', data);
            // Оновлює звіти і таймери
            if (data.success) {
                reportsContainer.innerHTML = data.reports_html;
                updateTimers();
                bindDeleteForms();
                bindSortLinks();
                bindPageLinks();
            } else {
                reportsContainer.innerHTML = '<p class="filter-message">Помилка: ' + (data.message || 'Невідома помилка') + '</p>';
            }
        })
        .catch(error => {
            // Обробляє помилки
            console.error('Помилка завантаження звітів:', error);
            reportsContainer.innerHTML = '<p class="filter-message">Помилка завантаження звітів. Спробуйте ще раз.</p>';
        });
    }

    // Обробляє сортування
    function bindSortLinks() {
        console.log('Binding sort links');
        document.querySelectorAll('.sort-link').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                // Оновлює параметри сортування
                currentFilters.sort = this.dataset.sort;
                currentFilters.order = this.dataset.order;
                currentFilters.page = 1;
                console.log('Sort changed:', currentFilters);
                fetchReports();
            });
        });
    }

    // Обробляє пагінацію
    function bindPageLinks() {
        console.log('Binding page links');
        document.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                // Оновлює сторінку
                currentFilters.page = parseInt(this.dataset.page);
                console.log('Page changed:', currentFilters);
                fetchReports();
            });
        });
    }

    // Обробляє фільтри
    const filterForm = document.getElementById('filter-form');
    if (filterForm) {
        console.log('Filter form found');
        const statusSelect = document.getElementById('status');
        const typeSelect = document.getElementById('type');

        // Оновлює фільтр статусу
        statusSelect.addEventListener('change', function () {
            currentFilters.status = this.value;
            currentFilters.page = 1;
            console.log('Status filter changed:', currentFilters);
            fetchReports();
        });

        // Оновлює фільтр типу
        typeSelect.addEventListener('change', function () {
            currentFilters.type = this.value;
            currentFilters.page = 1;
            console.log('Type filter changed:', currentFilters);
            fetchReports();
        });

        // Скидає фільтри
        document.querySelector('.reset-filters').addEventListener('click', function () {
            console.log('Resetting filters');
            statusSelect.value = '';
            typeSelect.value = '';
            currentFilters = { status: '', type: '', sort: 'created_at', order: 'DESC', page: 1 };
            fetchReports();
        });
    } else {
        console.error('Filter form not found');
    }

    // Завантажує звіти при старті
    console.log('Initial fetch of reports');
    fetchReports();

    // Ініціалізує таймери та події
    updateTimers();
    bindDeleteForms();
    bindSortLinks();
    bindPageLinks();
});