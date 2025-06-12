// Чекає, поки сторінка повністю завантажиться
document.addEventListener('DOMContentLoaded', function () {
    // Створює карту, центровану на Україні
    const map = L.map('map').setView([48.3794, 31.1656], 6);

    // Додає шар карти з OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        minZoom: 6, // Мінімальний масштаб
        maxZoom: 18, // Максимальний масштаб
        maxBounds: [[44, 22], [53, 40]], // Обмежує карту межами України
        maxBoundsViscosity: 1.0 // Запобігає виходу за межі
    }).addTo(map);

    // Додає маркер, який можна переміщати
    let marker = null;
    map.on('click', function (e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;

        // Перевіряє, чи координати в межах України
        if (lat >= 44 && lat <= 53 && lng >= 22 && lng <= 40) {
            // Оновлює або створює маркер
            if (marker) {
                marker.setLatLng(e.latlng);
            } else {
                marker = L.marker(e.latlng, { draggable: true }).addTo(map);
                // Оновлює координати при перетягуванні
                marker.on('dragend', function () {
                    const position = marker.getLatLng();
                    document.getElementById('latitude').value = position.lat;
                    document.getElementById('longitude').value = position.lng;
                    // Отримує деталі місця
                    debouncedFetchLocationDetails(position.lat, position.lng);
                });
            }
            // Зберігає координати
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;
            // Отримує деталі місця
            debouncedFetchLocationDetails(lat, lng);
        } else {
            // Показує помилку, якщо місце поза Україною
            showError('map', 'Будь ласка, оберіть місце в межах України (широта: 44–53, довгота: 22–40).');
        }
    });

    // Словник для заміни російських назв на українські
    const nameTranslations = {
        'Киевская область': 'Київська область',
        'Киев': 'Київ',
        'Львовская область': 'Львівська область',
        'Львов': 'Львів',
        'Одесская область': 'Одеська область',
        'Одесса': 'Одеса',
        'Харьковская область': 'Харківська область',
        'Харьков': 'Харків',
        'Днепропетровская область': 'Дніпропетровська область',
        'Днепр': 'Дніпро',
    };

    // Список дозволених областей
    const allowedRegions = [
        'Вінницька область',
        'Волинська область',
        'Дніпропетровська область',
        'Донецька область',
        'Житомирська область',
        'Закарпатська область',
        'Запорізька область',
        'Івано-Франківська область',
        'Київська область',
        'Кіровоградська область',
        'Луганська область',
        'Львівська область',
        'Миколаївська область',
        'Одеська область',
        'Полтавська область',
        'Рівненська область',
        'Сумська область',
        'Тернопільська область',
        'Харківська область',
        'Херсонська область',
        'Хмельницька область',
        'Черкаська область',
        'Чернівецька область',
        'Чернігівська область'
    ];

    // Функція для показу помилок
    function showError(fieldId, message) {
        // Знаходить елемент для помилки
        const errorElement = document.getElementById(`${fieldId}-error`);
        if (errorElement) {
            // Показує повідомлення про помилку
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        } else {
            console.error(`Error element ${fieldId}-error not found`);
        }
    }

    // Функція для очищення помилок
    function clearErrors() {
        // Приховує всі повідомлення про помилки
        const errorElements = document.querySelectorAll('.error-message');
        errorElements.forEach(el => {
            el.textContent = '';
            el.style.display = 'none';
        });
    }

    // Обмежує частоту запитів
    function debounce(func, wait) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    // Функція для отримання деталей місця
    async function fetchLocationDetails(lat, lng) {
        // Показує індикатор завантаження
        const loader = document.getElementById('location-loader');
        if (loader) loader.style.display = 'block';

        try {
            // Запитує дані про місце
            const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1&accept-language=uk`);
            const data = await response.json();
            const ukrainianRegex = /^[\u0400-\u04FF\s-]+$/; // Перевіряє українські символи

            if (data && data.address) {
                // Отримує область і населений пункт
                let region = data.address.state || data.address.region || '';
                let city = data.address.city || data.address.town || data.address.village || '';

                // Замінює російські назви
                region = nameTranslations[region] || region;
                city = nameTranslations[city] || city;

                // Перевіряє валідність назв
                let errors = [];
                if (region && !allowedRegions.includes(region)) {
                    console.warn('Назва регіону не в списку дозволених:', region);
                    region = '';
                    errors.push('області');
                }
                if (city && !ukrainianRegex.test(city)) {
                    console.warn('Назва міста не українська:', city);
                    city = '';
                    errors.push('населеного пункту');
                }

                // Встановлює область для Києва
                if (city.toLowerCase() === 'київ') {
                    region = 'Київська область';
                }

                // Оновлює поля форми
                document.getElementById('region').value = region;
                document.getElementById('city').value = city;

                // Показує помилки, якщо є
                if (errors.length > 0) {
                    showError('region', `Не вдалося отримати коректну назву ${errors.join(' та ')}. Будь ласка, оберіть зі списку.`);
                } else if (!region && !city) {
                    showError('region', 'Не вдалося визначити область або населений пункт. Будь ласка, оберіть область зі списку та введіть населений пункт вручну.');
                }
            } else {
                // Очищає поля, якщо дані відсутні
                document.getElementById('region').value = '';
                document.getElementById('city').value = '';
                showError('region', 'Не вдалося отримати дані про місце. Будь ласка, оберіть область зі списку та введіть населений пункт вручну.');
            }
        } catch (error) {
            // Обробляє помилки зв’язку
            console.error('Помилка отримання деталей місця:', error);
            document.getElementById('region').value = '';
            document.getElementById('city').value = '';
            showError('region', 'Не вдалося отримати дані про місце. Спробуйте ще раз або оберіть область зі списку.');
        } finally {
            // Приховує індикатор завантаження
            if (loader) loader.style.display = 'none';
        }
    }

    // Обмежує частоту запитів до Nominatim
    const debouncedFetchLocationDetails = debounce(fetchLocationDetails, 1000);

    // Автоматично встановлює область для Києва
    const cityInput = document.getElementById('city');
    cityInput.addEventListener('input', function () {
        const cityValue = cityInput.value.trim().toLowerCase();
        if (cityValue === 'київ') {
            document.getElementById('region').value = 'Київська область';
        }
    });

    // Валідація форми
    const form = document.getElementById('report-form');
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        clearErrors();
        console.log('Starting form validation...');

        let isValid = true;

        // Перевіряє тип проблеми
        const type = document.getElementById('type').value;
        if (!type) {
            showError('type', 'Будь ласка, оберіть тип проблеми.');
            isValid = false;
        }

        // Перевіряє опис
        const description = document.getElementById('description').value.trim();
        const ukrainianRegex = /^[\u0400-\u04FF\s-]+$/;
        if (!description) {
            showError('description', 'Будь ласка, введіть опис проблеми.');
            isValid = false;
        } else if (description.length < 10) {
            showError('description', 'Опис має містити щонайменше 10 символів.');
            isValid = false;
        } else if (description.length > 500) {
            showError('description', 'Опис не може перевищувати 500 символів.');
            isValid = false;
        } else if (!ukrainianRegex.test(description)) {
            showError('description', 'Опис має містити лише українські літери, пробіли та дефіси.');
            isValid = false;
        }

        // Перевіряє область
        const region = document.getElementById('region').value.trim();
        if (!region) {
            showError('region', 'Будь ласка, оберіть область зі списку.');
            isValid = false;
        } else if (!allowedRegions.includes(region)) {
            showError('region', 'Оберіть область зі списку дозволених.');
            isValid = false;
        }

        // Перевіряє населений пункт
        const city = document.getElementById('city').value.trim();
        if (!city) {
            showError('city', 'Будь ласка, введіть населений пункт.');
            isValid = false;
        } else if (!ukrainianRegex.test(city)) {
            showError('city', 'Населений пункт має містити лише українські літери.');
            isValid = false;
        } else if (city.length > 100) {
            showError('city', 'Населений пункт не може перевищувати 100 символів.');
            isValid = false;
        }

        // Перевіряє координати
        const latitude = parseFloat(document.getElementById('latitude').value);
        const longitude = parseFloat(document.getElementById('longitude').value);
        if (!latitude || !longitude || latitude < 44 || latitude > 53 || longitude < 22 || longitude > 40) {
            showError('map', 'Будь ласка, оберіть коректне місце на карті в межах України.');
            isValid = false;
        }

        // Перевіряє фото
        const photo = document.getElementById('photo').files[0];
        if (photo) {
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(photo.type)) {
                showError('photo', 'Дозволені лише файли JPEG, PNG або GIF.');
                isValid = false;
            } else if (photo.size > 5 * 1024 * 1024) {
                showError('photo', 'Файл не може перевищувати 5 МБ.');
                isValid = false;
            }
        }

        // Відправляє форму, якщо валідна
        if (isValid) {
            console.log('Form is valid, submitting...');
            const submitBtn = document.querySelector('.submit-btn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Відправка...';
            const formData = new FormData(form);
            // Відправляє дані на сервер
            fetch(form.action, {
                method: 'POST',
                body: formData
            }).then(response => response.json().then(data => ({ status: response.status, body: data })))
                .then(({ status, body }) => {
                    console.log('Server response:', body);
                    // Якщо успішно, показує повідомлення
                    if (status === 200 && body.success) {
                        console.log('Form submitted successfully');
                        const messageContainer = document.querySelector('.form-section');
                        const successMessage = document.createElement('div');
                        successMessage.className = 'success-message';
                        successMessage.innerHTML = `
                            ${body.message}
                            <span class="close-btn">×</span>
                        `;
                        messageContainer.prepend(successMessage);
                        const closeBtn = successMessage.querySelector('.close-btn');
                        // Закриває повідомлення при кліку
                        closeBtn.addEventListener('click', () => {
                            successMessage.style.animation = 'slideOut 0.5s ease-in-out';
                            setTimeout(() => {
                                successMessage.remove();
                            }, 500);
                        });
                        // Автоматично закриває через 5 секунд
                        setTimeout(() => {
                            if (successMessage.parentNode) {
                                successMessage.style.animation = 'slideOut 0.5s ease-in-out';
                                setTimeout(() => {
                                    successMessage.remove();
                                }, 500);
                            }
                        }, 5000);
                        // Очищає форму
                        form.reset();
                        if (marker) {
                            map.removeLayer(marker);
                            marker = null;
                        }
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Надіслати звіт';
                    } else {
                        // Якщо помилка, показує повідомлення
                        console.error('Form submission failed:', body.message);
                        showError('form', body.message || 'Помилка відправки форми. Спробуйте ще раз.');
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Надіслати звіт';
                    }
                }).catch(error => {
                    // Обробляє помилки зв’язку
                    console.error('Form submission error:', error);
                    showError('form', 'Помилка відправки форми. Спробуйте ще раз.');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Надіслати звіт';
                });
        } else {
            console.log('Form validation failed');
        }
    });
});