document.addEventListener('DOMContentLoaded', function () {
    console.log('analytics.js loaded'); // Повідомлення про завантаження скрипта
    console.log('Chart object:', typeof Chart); // Перевірка наявності бібліотеки Chart
    console.log('Regions data:', regionsLabels, regionsData); // Дані для діаграми регіонів
    console.log('Status data:', statusLabels, statusData); // Дані для діаграми статусів
    console.log('Time data:', timeLabels, timeData); // Дані для діаграми часу
    console.log('Types data:', typesLabels, typesData); // Дані для діаграми типів
    console.log('Avg time by region data:', avgTimeRegionLabels, avgTimeRegionData); // Дані для діаграми середнього часу

    try {
        // Діаграма: Кількість проблем за областями (Топ-5)
        const regionsCtx = document.getElementById('regionsChart').getContext('2d'); // Отримання області для діаграми
        if (!regionsCtx) {
            console.error('regionsChart canvas not found'); // Помилка, якщо область не знайдена
            return;
        }
        new Chart(regionsCtx, { // Створення стовпчикової діаграми
            type: 'bar',
            data: {
                labels: regionsLabels, // Назви регіонів
                datasets: [{
                    label: 'Кількість проблем', // Підпис діаграми
                    data: regionsData, // Дані про кількість проблем
                    backgroundColor: 'rgba(33, 150, 243, 0.8)', // Колір стовпчиків
                    borderColor: 'rgba(33, 150, 243, 1)', // Колір меж
                    borderWidth: 2, // Товщина меж
                    hoverBackgroundColor: 'rgba(33, 150, 243, 1)' // Колір при наведенні
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true, // Початок осі Y з нуля
                        title: {
                            display: true,
                            text: 'Кількість' // Підпис осі Y
                        },
                        grid: {
                            color: 'rgba(200, 200, 200, 0.2)' // Колір сітки
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Область' // Підпис осі X
                        },
                        grid: {
                            display: false // Відключення сітки
                        }
                    }
                },
                plugins: {
                    legend: {
                        labels: {
                            color: '#6B7280' // Колір підписів
                        }
                    }
                }
            }
        });

        // Діаграма: Кількість проблем за статусами
        const statusCtx = document.getElementById('statusChart').getContext('2d'); // Отримання області для діаграми
        if (!statusCtx) {
            console.error('statusChart canvas not found'); // Помилка, якщо область не знайдена
            return;
        }
        new Chart(statusCtx, { // Створення кругової діаграми
            type: 'pie',
            data: {
                labels: statusLabels, // Назви статусів
                datasets: [{
                    label: 'Статус', // Підпис діаграми
                    data: statusData, // Дані про статуси
                    backgroundColor: [ // Кольори секторів
                        'rgba(76, 175, 80, 0.8)', // Зелений
                        'rgba(255, 193, 7, 0.8)', // Жовтий
                        'rgba(244, 67, 54, 0.8)' // Червоний
                    ],
                    borderColor: [ // Кольори меж
                        'rgba(76, 175, 80, 1)',
                        'rgba(255, 193, 7, 1)',
                        'rgba(244, 67, 54, 1)'
                    ],
                    borderWidth: 2, // Товщина меж
                    hoverOffset: 20 // Зміщення при наведенні
                }]
            },
            options: {
                plugins: {
                    legend: {
                        position: 'bottom', // Розташування легенди
                        labels: {
                            color: '#6B7280' // Колір підписів
                        }
                    }
                }
            }
        });

        // Діаграма: Активність за часом
        const timeCtx = document.getElementById('timeChart').getContext('2d'); // Отримання області для діаграми
        if (!timeCtx) {
            console.error('timeChart canvas not found'); // Помилка, якщо область не знайдена
            return;
        }
        new Chart(timeCtx, { // Створення лінійної діаграми
            type: 'line',
            data: {
                labels: timeLabels, // Дати
                datasets: [{
                    label: 'Кількість звітів', // Підпис діаграми
                    data: timeData, // Дані про звіти
                    fill: false, // Без заповнення під лінією
                    borderColor: 'rgba(156, 39, 176, 1)', // Колір лінії
                    backgroundColor: 'rgba(156, 39, 176, 0.8)', // Колір точок
                    borderWidth: 3, // Товщина лінії
                    pointBackgroundColor: 'rgba(156, 39, 176, 1)', // Колір точок
                    pointBorderColor: '#fff', // Колір меж точок
                    pointRadius: 5, // Розмір точок
                    tension: 0.3 // Згладжування лінії
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true, // Початок осі Y з нуля
                        title: {
                            display: true,
                            text: 'Кількість звітів' // Підпис осі Y
                        },
                        grid: {
                            color: 'rgba(200, 200, 200, 0.2)' // Колір сітки
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Дата' // Підпис осі X
                        },
                        grid: {
                            display: false // Відключення сітки
                        }
                    }
                },
                plugins: {
                    legend: {
                        labels: {
                            color: '#6B7280' // Колір підписів
                        }
                    }
                }
            }
        });

        // Нова діаграма: Розподіл типів проблем
        const typesCtx = document.getElementById('typesChart').getContext('2d'); // Отримання області для діаграми
        if (!typesCtx) {
            console.error('typesChart canvas not found'); // Помилка, якщо область не знайдена
            return;
        }
        new Chart(typesCtx, { // Створення кільцевої діаграми
            type: 'doughnut',
            data: {
                labels: typesLabels, // Назви типів
                datasets: [{
                    label: 'Типи проблем', // Підпис діаграми
                    data: typesData, // Дані про типи
                    backgroundColor: [ // Кольори секторів
                        'rgba(244, 67, 54, 0.8)', // Червоний
                        'rgba(33, 150, 243, 0.8)', // Синій
                        'rgba(76, 175, 80, 0.8)', // Зелений
                        'rgba(255, 193, 7, 0.8)', // Жовтий
                        'rgba(156, 39, 176, 0.8)' // Фіолетовий
                    ],
                    borderColor: [ // Кольори меж
                        'rgba(244, 67, 54, 1)',
                        'rgba(33, 150, 243, 1)',
                        'rgba(76, 175, 80, 1)',
                        'rgba(255, 193, 7, 1)',
                        'rgba(156, 39, 176, 1)'
                    ],
                    borderWidth: 2, // Товщина меж
                    hoverOffset: 20 // Зміщення при наведенні
                }]
            },
            options: {
                plugins: {
                    legend: {
                        position: 'bottom', // Розташування легенди
                        labels: {
                            color: '#6B7280' // Колір підписів
                        }
                    }
                }
            }
        });

        // Нова діаграма: Середній час вирішення за регіонами
        const avgTimeRegionCtx = document.getElementById('avgTimeRegionChart').getContext('2d'); // Отримання області для діаграми
        if (!avgTimeRegionCtx) {
            console.error('avgTimeRegionChart canvas not found'); // Помилка, якщо область не знайдена
            return;
        }
        new Chart(avgTimeRegionCtx, { // Створення стовпчикової діаграми
            type: 'bar',
            data: {
                labels: avgTimeRegionLabels, // Назви регіонів
                datasets: [{
                    label: 'Середній час (години)', // Підпис діаграми
                    data: avgTimeRegionData, // Дані про час
                    backgroundColor: 'rgba(255, 87, 34, 0.8)', // Колір стовпчиків
                    borderColor: 'rgba(255, 87, 34, 1)', // Колір меж
                    borderWidth: 2, // Товщина меж
                    hoverBackgroundColor: 'rgba(255, 87, 34, 1)' // Колір при наведенні
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true, // Початок осі Y з нуля
                        title: {
                            display: true,
                            text: 'Години' // Підпис осі Y
                        },
                        grid: {
                            color: 'rgba(200, 200, 200, 0.2)' // Колір сітки
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Область' // Підпис осі X
                        },
                        grid: {
                            display: false // Відключення сітки
                        }
                    }
                },
                plugins: {
                    legend: {
                        labels: {
                            color: '#6B7280' // Колір підписів
                        }
                    }
                }
            }
        });

    } catch (error) {
        console.error('Error initializing charts:', error); // Обробка помилок створення діаграм
    }

    // Динамічна зміна кольору легенд для темної теми
    const isDarkTheme = document.body.classList.contains('dark-theme'); // Перевірка темної теми
    if (isDarkTheme) {
        document.querySelectorAll('.chart-container canvas').forEach(canvas => { // Оновлення кольорів для кожної діаграми
            const chart = Chart.getChart(canvas); // Отримання діаграми
            if (chart) {
                chart.options.plugins.legend.labels.color = '#E5E7EB'; // Колір для темної теми
                chart.update(); // Оновлення діаграми
            }
        });
    }
});