// Чекає, поки сторінка повністю завантажиться
document.addEventListener('DOMContentLoaded', function () {
    console.log('map.js loaded'); // Повідомляє, що скрипт завантажено

    // Створює карту, центровану на Україні
    let map = L.map('map').setView([48.3794, 31.1656], 6);

    // Додає шар карти з OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        minZoom: 6, // Мінімальний масштаб
        maxZoom: 18, // Максимальний масштаб
        maxBounds: [[44, 22], [53, 40]], // Обмежує карту межами України
        maxBoundsViscosity: 1.0 // Запобігає виходу за межі
    }).addTo(map);

    // Визначає іконки для типів проблем
    const iconTypes = {
        pothole: { class: 'fas fa-road', color: '#d32f2f' }, // Ями
        trash: { class: 'fas fa-trash', color: '#4caf50' }, // Сміття
        light: { class: 'fas fa-lightbulb', color: '#ff9800' }, // Освітлення
        sign: { class: 'fas fa-sign', color: '#2196f3' }, // Знаки
        other: { class: 'fas fa-question-circle', color: '#9e9e9e' } // Інше
    };

    // Створює групу для кластеризації маркерів
    let markerClusterGroup = L.markerClusterGroup({
        maxClusterRadius: 50, // Радіус кластера
        spiderfyOnMaxZoom: true, // Розгортає маркери при максимальному масштабі
        showCoverageOnHover: true, // Показує межі кластера при наведенні
        zoomToBoundsOnClick: true, // Наближає при кліку
        // Налаштовує вигляд кластера
        iconCreateFunction: function (cluster) {
            const count = cluster.getChildCount();
            return L.divIcon({
                html: `<div class="cluster-icon">${count}</div>`,
                className: 'custom-cluster',
                iconSize: L.point(40, 40)
            });
        }
    });

    // Додає групу кластерів на карту
    map.addLayer(markerClusterGroup);
    let markers = []; // Зберігає маркери

    // Функція для оновлення карти
    function updateMap(reportsData) {
        console.log('Updating map with reports:', reportsData ? reportsData.length : 'no data');
        // Перевіряє, чи ініціалізовано групу кластерів
        if (!markerClusterGroup) {
            console.error('MarkerClusterGroup not initialized');
            return;
        }

        // Перевіряє, чи дані є масивом
        if (!Array.isArray(reportsData)) {
            console.error('Invalid reports data:', reportsData);
            return;
        }

        // Очищає попередні маркери
        markerClusterGroup.clearLayers();
        markers = [];

        // Обробляє кожен звіт
        reportsData.forEach((report, index) => {
            // Перевіряє валідність звіту
            if (!report || typeof report !== 'object') {
                console.warn(`Invalid report at index ${index}:`, report);
                return;
            }

            // Перевіряє наявність координат
            if (!report.latitude || !report.longitude) {
                console.warn(`Missing coordinates for report at index ${index}:`, report);
                return;
            }

            try {
                // Вибирає іконку для типу звіту
                const iconConfig = iconTypes[report.type] || iconTypes.other;
                // Створює маркер
                const marker = L.marker([parseFloat(report.latitude), parseFloat(report.longitude)], {
                    icon: L.divIcon({
                        html: `<i class="${iconConfig.class}" style="color: ${iconConfig.color}; font-size: 24px;"></i>`,
                        className: 'custom-marker',
                        iconSize: L.point(30, 30),
                        iconAnchor: L.point(15, 15),
                        popupAnchor: [0, -15]
                    })
                });
                // Визначає назву типу звіту
                const typeLabel = types && types[report.type] ? types[report.type] : 'Інше';
                // Створює спливаюче вікно з інформацією
                const popupContent = `
                    <strong>${typeLabel}</strong><br>
                    Область: ${report.region || 'Невідомо'}<br>
                    Населений пункт: ${report.city || 'Невідомо'}<br>
                    Опис: ${report.description || 'Немає'}<br>
                    Статус: ${report.status === 'new' ? 'Нова' : report.status === 'in_progress' ? 'В обробці' : report.status === 'resolved' ? 'Вирішена' : 'Невідомо'}<br>
                    Час: ${report.created_at || 'Невідомо'}
                    ${isAdmin ? `<br><button class="delete-from-map" data-report-id="${report.id}">Видалити</button>` : ''}
                `;
                // Прив’язує спливаюче вікно до маркера
                marker.bindPopup(popupContent);
                // Додає маркер до кластера
                markerClusterGroup.addLayer(marker);
                // Зберігає маркер
                markers.push({ id: report.id || null, marker: marker });
            } catch (error) {
                console.warn(`Error processing report at index ${index}:`, error, report);
            }
        });
        console.log('Map updated with', markers.length, 'markers');
    }

    // Функція для зміни статусу звіту
    function updateStatus(select, reportId) {
        const newStatus = select.value; // Отримує новий статус
        console.log('Updating status for report:', reportId, 'to', newStatus);
        // Відправляє запит на сервер
        fetch('admin.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `report_id=${reportId}&new_status=${newStatus}`
        })
        .then(response => {
            // Перевіряє відповідь сервера
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Status update response:', data);
            // Якщо успішно, оновлює звіти
            if (data.success) {
                fetchReports();
            } else {
                // Якщо помилка, показує повідомлення
                showMessage('error', data.message || 'Помилка зміни статусу.');
            }
        })
        .catch(error => {
            // Обробляє помилки зв’язку
            console.error('Помилка зміни статусу:', error);
            showMessage('error', 'Виникла помилка при зміні статусу.');
        });
    }

    // Функція для виконання масових дій
    function executeBulkAction() {
        console.log('executeBulkAction called');
        // Отримує форму і вибрану дію
        const form = document.getElementById('bulk-actions-form');
        const action = document.getElementById('bulk_action').value;
        // Отримує вибрані звіти
        const selectedReports = Array.from(document.querySelectorAll('input[name="report_ids[]"]:checked')).map(input => input.value);

        console.log('Action:', action, 'Selected reports:', selectedReports);

        // Перевіряє права адміністратора
        if (!isAdmin) {
            showMessage('error', 'Недостатньо прав для виконання масової дії.');
            return;
        }

        // Перевіряє, чи вибрано дію
        if (!action) {
            showMessage('error', 'Будь ласка, оберіть дію.');
            return;
        }

        // Перевіряє, чи вибрано звіти
        if (selectedReports.length === 0) {
            showMessage('error', 'Будь ласка, виберіть хоча б один звіт.');
            return;
        }

        // Підтверджує видалення
        if (action === 'delete' && !confirm('Ви впевнені, що хочете видалити вибрані звіти?')) {
            return;
        }

        // Створює дані для відправки
        const formData = new FormData();
        formData.append('bulk_action', action);
        selectedReports.forEach(id => formData.append('report_ids[]', id));
        // Додає новий статус, якщо змінюється
        if (action === 'change_status') {
            const newStatus = document.getElementById('bulk_status').value;
            if (!newStatus) {
                showMessage('error', 'Будь ласка, оберіть новий статус.');
                return;
            }
            formData.append('new_status', newStatus);
            console.log('New status:', newStatus);
        }

        console.log('Sending bulk action request to bulk_actions.php');
        // Відправляє запит на сервер
        fetch('bulk_actions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Bulk action response status:', response.status);
            // Перевіряє відповідь сервера
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Bulk action response:', data);
            // Якщо успішно, оновлює звіти
            if (data.success) {
                fetchReports();
                showMessage('message', data.message);
            } else {
                // Якщо помилка, показує повідомлення
                console.error('Server error:', data.message);
                showMessage('error', data.message || 'Помилка виконання масової дії.');
            }
        })
        .catch(error => {
            // Обробляє помилки зв’язку
            console.error('Network or client error:', error);
            showMessage('error', `Виникла помилка при виконанні масової дії: ${error.message}`);
        });
    }

    // Функція для показу повідомлень
    function showMessage(type, text) {
        // Створює повідомлення
        const message = document.createElement('p');
        message.className = type;
        message.textContent = text;
        // Додає повідомлення на сторінку
        document.querySelector('main').prepend(message);
        // Видаляє повідомлення через 5 секунд
        setTimeout(() => message.remove(), 5000);
    }

    // Обробка чекбокса "Вибрати все"
    function bindCheckboxEvents() {
        const selectAll = document.getElementById('select-all-reports');
        if (selectAll) {
            // Вибирає або знімає вибір усіх звітів
            selectAll.addEventListener('change', function () {
                console.log('Select all changed:', this.checked);
                document.querySelectorAll('input[name="report_ids[]"]').forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
            });
        } else {
            console.warn('Select all checkbox not found');
        }
    }

    // Обробка вибору дії
    function bindBulkActionEvents() {
        const bulkAction = document.getElementById('bulk_action');
        const bulkStatus = document.getElementById('bulk_status');
        if (bulkAction && bulkStatus) {
            // Показує поле статусу при зміні статусу
            bulkAction.addEventListener('change', function () {
                console.log('Bulk action changed:', this.value);
                bulkStatus.style.display = this.value === 'change_status' ? 'inline-block' : 'none';
            });
        } else {
            console.warn('Bulk action or status select not found');
        }

        // Виконує масову дію при кліку
        const executeButton = document.querySelector('#bulk-actions-form .action-btn');
        if (executeButton) {
            executeButton.addEventListener('click', function (e) {
                e.preventDefault();
                console.log('Execute button clicked');
                executeBulkAction();
            });
        } else {
            console.warn('Execute button not found');
        }
    }

    // Функція для пошуку звітів
    let currentReportFilters = { region: '', status: '', type: '', search_query: '', sort: 'created_at', order: 'DESC', page: 1 };

    function fetchReports() {
        console.log('Fetching reports with filters:', currentReportFilters);
        // Знаходить контейнер для звітів
        const reportsContainer = document.getElementById('reports-container');
        if (!reportsContainer) {
            console.error('Reports container not found');
            return;
        }
        // Показує повідомлення про завантаження
        reportsContainer.innerHTML = '<p class="filter-message"><i class="fas fa-spinner fa-spin"></i> Завантаження...</p>';

        // Створює URL із фільтрами
        const params = new URLSearchParams(currentReportFilters);
        const url = `filter_admin_reports.php?${params.toString()}`;
        console.log('Fetching reports URL:', url);

        // Запитує звіти з сервера
        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => {
            console.log('Reports response status:', response.status);
            // Перевіряє відповідь сервера
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Received reports data:', data);
            // Якщо успішно, оновлює звіти і карту
            if (data.success) {
                reportsContainer.innerHTML = data.reports_html;
                updateMap(data.reports);
                bindCheckboxEvents();
                bindBulkActionEvents();
            } else {
                // Якщо помилка, показує повідомлення
                reportsContainer.innerHTML = '<p class="filter-message">Помилка: ' + (data.message || 'Невідома помилка') + '</p>';
            }
        })
        .catch(error => {
            // Обробляє помилки зв’язку
            console.error('Помилка завантаження звітів:', error);
            reportsContainer.innerHTML = '<p class="filter-message">Помилка завантаження звітів. Спробуйте ще раз.</p>';
        });
    }

    // Обробка видалення звіту
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('delete-from-map')) {
            const reportId = e.target.getAttribute('data-report-id');
            console.log('Delete from map clicked, reportId:', reportId);
            // Підтверджує видалення
            if (confirm('Ви впевнені, що хочете видалити цей звіт?')) {
                // Відправляє запит на сервер
                fetch('delete_report_ajax.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `id=${reportId}`
                })
                .then(response => {
                    // Перевіряє відповідь сервера
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Delete response:', data);
                    // Якщо успішно, видаляє маркер і рядок
                    if (data.success) {
                        const markerObj = markers.find(m => m.id == reportId);
                        if (markerObj) {
                            markerClusterGroup.removeLayer(markerObj.marker);
                            markers.splice(markers.indexOf(markerObj), 1);
                        }
                        const row = document.querySelector(`tr[data-report-id="${reportId}"]`);
                        if (row) {
                            row.remove();
                        }
                        showMessage('message', data.message);
                    } else {
                        // Якщо помилка, показує повідомлення
                        showMessage('error', data.message);
                    }
                })
                .catch(error => {
                    // Обробляє помилки зв’язку
                    console.error('Помилка видалення:', error);
                    showMessage('error', 'Виникла помилка при видаленні звіту.');
                });
            }
        }
    });

    // Обробка фільтрів і пошуку звітів
    const filterForm = document.getElementById('filter-form');
    if (filterForm) {
        console.log('Filter form found');
        // Знаходить елементи фільтрів
        const regionSelect = document.getElementById('region');
        const statusSelect = document.getElementById('status');
        const typeSelect = document.getElementById('type');
        const searchQueryInput = document.getElementById('search_query');

        // Оновлює фільтр області
        regionSelect.addEventListener('change', function () {
            currentReportFilters.region = this.value;
            currentReportFilters.page = 1;
            console.log('Region filter changed:', currentReportFilters);
            fetchReports();
        });

        // Оновлює фільтр статусу
        statusSelect.addEventListener('change', function () {
            currentReportFilters.status = this.value;
            currentReportFilters.page = 1;
            console.log('Status filter changed:', currentReportFilters);
            fetchReports();
        });

        // Оновлює фільтр типу
        typeSelect.addEventListener('change', function () {
            currentReportFilters.type = this.value;
            currentReportFilters.page = 1;
            console.log('Type filter changed:', currentReportFilters);
            fetchReports();
        });

        // Оновлює пошуковий запит
        searchQueryInput.addEventListener('input', function () {
            currentReportFilters.search_query = this.value;
            currentReportFilters.page = 1;
            console.log('Search query changed:', currentReportFilters);
            fetchReports();
        });

        // Скидає фільтри
        document.querySelector('#filter-form .reset-filters').addEventListener('click', function () {
            console.log('Resetting report filters');
            regionSelect.value = '';
            statusSelect.value = '';
            typeSelect.value = '';
            searchQueryInput.value = '';
            currentReportFilters = { region: '', status: '', type: '', search_query: '', sort: 'created_at', order: 'DESC', page: 1 };
            fetchReports();
        });
    } else {
        console.error('Filter form not found');
    }

    // Обробка сортування
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('sort-link')) {
            e.preventDefault();
            // Оновлює параметри сортування
            currentReportFilters.sort = e.target.dataset.sort;
            currentReportFilters.order = e.target.dataset.order;
            currentReportFilters.page = 1;
            console.log('Sort changed:', currentReportFilters);
            fetchReports();
        }
    });

    // Обробка пагінації
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('page-link')) {
            e.preventDefault();
            // Оновлює номер сторінки
            currentReportFilters.page = parseInt(e.target.dataset.page);
            console.log('Report page changed:', currentReportFilters);
            fetchReports();
        }
    });

    // Завантажує звіти при старті
    console.log('Initial fetch of reports');
    fetchReports();
});