// Чекає, поки сторінка повністю завантажиться
document.addEventListener('DOMContentLoaded', function () {
    console.log('monitor.js loaded'); // Повідомляє, що скрипт завантажено

    // Змінні для карти та маркерів
    let map = null;
    let markerClusterGroup = null;
    const markers = [];

    // Ініціалізує карту
    function initMap() {
        console.log('Initializing map');
        const mapContainer = document.getElementById('map');
        if (!mapContainer) {
            console.error('Map container not found');
            return;
        }

        // Перевіряє наявність бібліотеки Leaflet
        if (typeof L === 'undefined') {
            console.error('Leaflet library not loaded');
            mapContainer.innerHTML = '<p style="color: red; text-align: center;">Помилка завантаження карти. Перевірте підключення до мережі.</p>';
            return;
        }

        try {
            // Створює карту, центровану на Україні
            map = L.map('map').setView([48.3794, 31.1656], 6);

            // Додає шар карти
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                minZoom: 6,
                maxZoom: 18,
                maxBounds: [[44, 22], [53, 40]], // Обмежує межами України
                maxBoundsViscosity: 1.0
            }).addTo(map);

            // Створює групу кластерів
            markerClusterGroup = L.markerClusterGroup({
                maxClusterRadius: 50,
                spiderfyOnMaxZoom: true,
                showCoverageOnHover: true,
                zoomToBoundsOnClick: true,
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

            // Додає групу на карту
            map.addLayer(markerClusterGroup);
            console.log('Map initialized successfully');

            // Додає початкові звіти
            if (typeof reports !== 'undefined' && Array.isArray(reports)) {
                console.log('Adding initial reports:', reports.length);
                updateMap(reports);
            } else {
                console.error('Reports data is undefined or not an array:', reports);
            }
        } catch (error) {
            console.error('Error initializing map:', error);
        }
    }

    // Оновлює карту зі звітами
    function updateMap(reportsData) {
        console.log('Updating map with reports:', reportsData ? reportsData.length : 'no data');
        if (!markerClusterGroup) {
            console.error('MarkerClusterGroup not initialized');
            return;
        }

        // Перевіряє, чи дані є масивом
        if (!Array.isArray(reportsData)) {
            console.error('Invalid reports data:', reportsData);
            return;
        }

        // Очищає маркери
        markerClusterGroup.clearLayers();
        markers.length = 0;

        // Визначає іконки для типів звітів
        const iconTypes = {
            pothole: { class: 'fas fa-road', color: '#d32f2f' },
            trash: { class: 'fas fa-trash', color: '#4caf50' },
            light: { class: 'fas fa-lightbulb', color: '#ff9800' },
            sign: { class: 'fas fa-sign', color: '#2196f3' },
            other: { class: 'fas fa-question-circle', color: '#9e9e9e' }
        };

        reportsData.forEach((report, index) => {
            // Перевіряє валідність звіту
            if (!report || typeof report !== 'object') {
                console.warn(`Invalid report at index ${index}:`, report);
                return;
            }

            // Перевіряє координати
            if (!report.latitude || !report.longitude) {
                console.warn(`Missing coordinates for report at index ${index}:`, report);
                return;
            }

            try {
                // Створює маркер
                const iconConfig = iconTypes[report.type] || iconTypes.other;
                const marker = L.marker([parseFloat(report.latitude), parseFloat(report.longitude)], {
                    icon: L.divIcon({
                        html: `<i class="${iconConfig.class}" style="color: ${iconConfig.color}; font-size: 24px;"></i>`,
                        className: 'custom-marker',
                        iconSize: L.point(30, 30),
                        iconAnchor: L.point(15, 15),
                        popupAnchor: [0, -15]
                    })
                });
                // Визначає назву типу
                const typeLabel = types && types[report.type] ? types[report.type] : 'Інше';
                // Створює спливаюче вікно
                const popupContent = `
                    <strong>${typeLabel}</strong><br>
                    Область: ${report.region || 'Невідомо'}<br>
                    Населений пункт: ${report.city || 'Невідомо'}<br>
                    Опис: ${report.description || 'Немає'}<br>
                    Статус: ${report.status === 'new' ? 'Нова' : report.status === 'in_progress' ? 'В обробці' : report.status === 'resolved' ? 'Вирішена' : 'Невідомо'}<br>
                    Час: ${report.created_at || 'Невідомо'}
                    ${isAdmin && report.id ? `<br><button class="delete-from-map" data-report-id="${report.id}">Видалити</button>` : ''}
                `;
                marker.bindPopup(popupContent);
                // Додає маркер до кластера
                markerClusterGroup.addLayer(marker);
                markers.push({ id: report.id || null, marker: marker });
            } catch (error) {
                console.warn(`Error processing report at index ${index}:`, error, report);
            }
        });
        console.log('Map updated with', markers.length, 'markers');
    }

    // Завантажує звіти з фільтрами
    let currentReportFilters = { region: '', status: '', type: '' };

    function fetchReports() {
        console.log('Fetching reports with filters:', currentReportFilters);
        const reportsContainer = document.getElementById('reports-container');
        const statsContainer = document.getElementById('total-reports');
        if (!reportsContainer || !statsContainer) {
            console.error('Reports container or stats container not found');
            return;
        }
        // Показує повідомлення про завантаження
        reportsContainer.innerHTML = '<p class="filter-message"><i class="fas fa-spinner fa-spin"></i> Завантаження...</p>';

        // Створює URL із фільтрами
        const params = new URLSearchParams(currentReportFilters);
        const url = `filter_reports.php?${params.toString()}`;
        console.log('Fetching reports URL:', url);

        // Запитує звіти
        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => {
            console.log('Reports response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Received reports data:', data);
            // Оновлює звіти, статистику і карту
            if (data.success) {
                reportsContainer.innerHTML = data.reports_html;
                statsContainer.textContent = data.total_reports;
                updateMap(data.reports);
                bindFilterEvents();
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

    // Налаштовує випадаючі списки
    function bindFilterEvents() {
        const filterGroups = document.querySelectorAll('.filter-group');

        console.log('Found filter groups:', filterGroups.length);

        filterGroups.forEach(group => {
            const select = group.querySelector('select');
            const filterSelected = group.querySelector('.filter-selected');
            const filterText = filterSelected.querySelector('.filter-text');
            const filterOptions = group.querySelector('.filter-options');
            const options = select.querySelectorAll('option');

            if (!select || !filterSelected || !filterText || !filterOptions) {
                console.error('Missing elements in filter-group:', { select, filterSelected, filterText, filterOptions });
                return;
            }

            console.log('Processing filter group:', select.id);

            // Очищає попередні опції
            filterOptions.innerHTML = '';

            // Оновлює елементи для уникнення повторних подій
            const newFilterSelected = filterSelected.cloneNode(true);
            filterSelected.parentNode.replaceChild(newFilterSelected, filterSelected);
            const newFilterOptions = filterOptions.cloneNode(false);
            filterOptions.parentNode.replaceChild(newFilterOptions, filterOptions);

            // Додає нові опції
            options.forEach(option => {
                const filterOption = document.createElement('div');
                filterOption.classList.add('filter-option');
                filterOption.textContent = option.textContent;
                filterOption.dataset.value = option.value;
                newFilterOptions.appendChild(filterOption);

                // Обробляє вибір опції
                filterOption.addEventListener('click', () => {
                    console.log('Option clicked:', option.value);
                    select.value = option.value;
                    newFilterSelected.querySelector('.filter-text').textContent = filterOption.textContent;
                    newFilterOptions.classList.remove('show');
                    newFilterSelected.classList.remove('active');
                    // Оновлює фільтри
                    currentReportFilters[select.name] = option.value;
                    fetchReports();
                });
            });

            // Показує або ховає опції
            newFilterSelected.addEventListener('click', (e) => {
                console.log('Filter selected clicked:', select.id);
                e.preventDefault();
                newFilterOptions.classList.toggle('show');
                newFilterSelected.classList.toggle('active');
            });

            // Ховає список при кліку поза ним
            document.addEventListener('click', (e) => {
                if (!group.contains(e.target)) {
                    newFilterOptions.classList.remove('show');
                    newFilterSelected.classList.remove('active');
                }
            });

            // Встановлює поточне значення
            const selectedOption = select.querySelector('option[selected]') || select.options[select.selectedIndex];
            newFilterSelected.querySelector('.filter-text').textContent = selectedOption.textContent;
        });
    }

    // Скидає фільтри
    document.querySelector('.reset-filters')?.addEventListener('click', () => {
        console.log('Resetting filters...');
        const filterGroups = document.querySelectorAll('.filter-group');
        filterGroups.forEach(group => {
            const select = group.querySelector('select');
            const filterText = group.querySelector('.filter-text');
            select.value = '';
            filterText.textContent = select.options[0].textContent;
        });
        currentReportFilters = { region: '', status: '', type: '' };
        fetchReports();
    });

    // Обробляє видалення звіту
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('delete-from-map')) {
            const reportId = e.target.getAttribute('data-report-id');
            if (!reportId) {
                console.warn('Report ID not found for delete action');
                return;
            }
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
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Видаляє маркер
                        const markerObj = markers.find(m => m.id == reportId);
                        if (markerObj) {
                            markerClusterGroup.removeLayer(markerObj.marker);
                            markers.splice(markers.indexOf(markerObj), 1);
                        } else {
                            console.warn('Marker not found for report ID:', reportId);
                        }
                        // Видаляє картку звіту
                        const card = document.querySelector(`.report-card[data-report-id="${reportId}"]`);
                        if (card) {
                            card.remove();
                        } else {
                            console.warn('Report card not found for report ID:', reportId);
                        }
                        // Оновлює статистику
                        const totalReports = document.getElementById('total-reports');
                        if (totalReports) {
                            totalReports.textContent = parseInt(totalReports.textContent) - 1;
                        }
                        alert(data.message);
                    } else {
                        console.error('Delete failed:', data.message);
                        alert(data.message);
                    }
                })
                .catch(error => {
                    // Обробляє помилки
                    console.error('Помилка видалення:', error);
                    alert('Виникла помилка при видаленні звіту.');
                });
            }
        }
    });

    // Ініціалізує карту та події
    console.log('Starting map initialization...');
    initMap();
    bindFilterEvents();
});