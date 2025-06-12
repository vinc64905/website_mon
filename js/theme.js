// Чекає, поки сторінка повністю завантажиться
document.addEventListener('DOMContentLoaded', function () {
    // Знаходить кнопку перемикання теми та тіло сторінки
    const themeToggle = document.getElementById('theme-toggle');
    const body = document.body;

    // Завантажує збережену тему
    if (localStorage.getItem('theme') === 'dark') {
        // Вмикає темну тему
        body.classList.add('dark-theme');
        themeToggle.textContent = '☀️'; // Показує іконку сонця
    } else {
        themeToggle.textContent = '🌙'; // Показує іконку місяця
    }

    // Перемикає тему при кліку
    themeToggle.addEventListener('click', function () {
        // Змінює тему
        body.classList.toggle('dark-theme');
        if (body.classList.contains('dark-theme')) {
            // Зберігає темну тему
            localStorage.setItem('theme', 'dark');
            themeToggle.textContent = '☀️';
        } else {
            // Зберігає світлу тему
            localStorage.setItem('theme', 'light');
            themeToggle.textContent = '🌙';
        }
    });
});