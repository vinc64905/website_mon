-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3307
-- Час створення: Чрв 12 2025 р., 14:59
-- Версія сервера: 10.4.32-MariaDB
-- Версія PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База даних: `urban_issues`
--

-- --------------------------------------------------------

--
-- Структура таблиці `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_admin_reply` tinyint(1) DEFAULT 0,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `user_id`, `message`, `is_admin_reply`, `is_read`, `created_at`) VALUES
(3, 8, 'Пока', 0, 1, '2025-05-25 12:32:10'),
(6, 8, 'Привіт', 1, 1, '2025-05-25 12:35:26'),
(29, 8, 'Классна програма', 1, 1, '2025-05-25 13:19:34'),
(30, 8, 'як ваші справи', 1, 1, '2025-05-25 13:23:39'),
(31, 8, 'у мене все добре', 0, 1, '2025-05-25 13:27:12'),
(32, 10, 'Добрий день! Як надіслати звіт про проблему?', 0, 1, '2025-05-25 13:41:46'),
(33, 10, 'Добрий день, можете зробити це на сторінці \\\"Повідомити про проблему\\\"', 1, 1, '2025-05-25 13:43:23'),
(34, 10, 'Дякую', 0, 1, '2025-05-25 13:44:04'),
(35, 11, 'ЯК подати про проблему', 0, 1, '2025-05-25 14:00:51'),
(36, 11, 'на сторінці', 1, 1, '2025-05-25 14:01:45'),
(37, 13, 'Добрий день!', 0, 1, '2025-05-25 18:02:00'),
(38, 13, 'Добрий день!', 1, 1, '2025-05-25 18:03:16'),
(39, 14, 'Не можу переглянути свої звіти', 0, 1, '2025-05-25 18:50:03'),
(40, 14, 'Можете переглянути на сторінці \\\"Профіль\\\"', 1, 0, '2025-05-25 18:54:44'),
(41, 14, 'Дякую!', 0, 1, '2025-05-25 19:00:51'),
(42, 16, 'Як вирішити проблему?', 0, 1, '2025-05-28 18:57:51'),
(43, 16, 'Добрий день!', 1, 1, '2025-05-28 18:58:24'),
(44, 16, 'Добрий день, допоможете?', 0, 1, '2025-05-28 19:49:41'),
(45, 2, 'Добрий день! Як користуватися додатком?', 0, 1, '2025-06-05 13:06:01'),
(48, 8, 'Добрий день', 0, 1, '2025-06-05 16:43:16'),
(51, 2, 'Добрий день', 0, 1, '2025-06-11 08:38:13'),
(52, 9, 'Добрий день? Чи вирішена моя проблема?', 0, 1, '2025-06-11 08:43:47'),
(53, 9, 'Добрий день', 1, 1, '2025-06-11 08:44:25');

-- --------------------------------------------------------

--
-- Структура таблиці `cities`
--

CREATE TABLE `cities` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `region_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `cities`
--

INSERT INTO `cities` (`id`, `name`, `region_id`) VALUES
(44, 'Бердичів', 21),
(24, 'Біла Церква', 2),
(1, 'Білопілля', 1),
(46, 'Бровари', 2),
(8, 'Вінниця', 7),
(7, 'Дніпро', 6),
(25, 'Дрогобич', 3),
(35, 'Дубно', 12),
(22, 'Житомир', 21),
(30, 'Жмеринка', 7),
(18, 'Запоріжжя', 17),
(14, 'Івано-Франківськ', 13),
(26, 'Ізмаїл', 4),
(27, 'Ізюм', 5),
(34, 'Кам\'янець-Подільський', 11),
(3, 'Київ', 2),
(38, 'Ковель', 15),
(36, 'Коломия', 13),
(29, 'Конотоп', 1),
(31, 'Кременчук', 8),
(28, 'Кривий Ріг', 6),
(19, 'Кропивницький', 18),
(49, 'Лозова', 5),
(16, 'Луцьк', 15),
(4, 'Львів', 3),
(40, 'Мелітополь', 17),
(20, 'Миколаїв', 19),
(51, 'Мирне', 1),
(39, 'Мукачево', 16),
(32, 'Ніжин', 9),
(43, 'Нова Каховка', 20),
(5, 'Одеса', 4),
(41, 'Олександрія', 18),
(50, 'Павлоград', 6),
(42, 'Первомайськ', 19),
(9, 'Полтава', 8),
(13, 'Рівне', 12),
(47, 'Стрий', 3),
(2, 'Суми', 1),
(15, 'Тернопіль', 14),
(17, 'Ужгород', 16),
(33, 'Умань', 10),
(6, 'Харків', 5),
(21, 'Херсон', 20),
(12, 'Хмельницький', 11),
(45, 'Хотин', 22),
(11, 'Черкаси', 10),
(23, 'Чернівці', 22),
(10, 'Чернігів', 9),
(48, 'Чорноморськ', 4),
(37, 'Чортків', 14);

-- --------------------------------------------------------

--
-- Структура таблиці `regions`
--

CREATE TABLE `regions` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `regions`
--

INSERT INTO `regions` (`id`, `name`) VALUES
(7, 'Вінницька область'),
(15, 'Волинська область'),
(6, 'Дніпропетровська область'),
(21, 'Житомирська область'),
(16, 'Закарпатська область'),
(17, 'Запорізька область'),
(13, 'Івано-Франківська область'),
(2, 'Київська область'),
(18, 'Кіровоградська область'),
(3, 'Львівська область'),
(19, 'Миколаївська область'),
(4, 'Одеська область'),
(8, 'Полтавська область'),
(12, 'Рівненська область'),
(1, 'Сумська область'),
(14, 'Тернопільська область'),
(5, 'Харківська область'),
(20, 'Херсонська область'),
(11, 'Хмельницька область'),
(10, 'Черкаська область'),
(22, 'Чернівецька область'),
(9, 'Чернігівська область');

-- --------------------------------------------------------

--
-- Структура таблиці `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `region` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `type` enum('pothole','trash','light','sign','other') NOT NULL,
  `description` text DEFAULT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `status` enum('new','in_progress','resolved') DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `region_id` int(11) DEFAULT NULL,
  `city_id` int(11) DEFAULT NULL,
  `report_type_id` int(11) DEFAULT NULL,
  `status_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `reports`
--

INSERT INTO `reports` (`id`, `user_id`, `region`, `city`, `type`, `description`, `latitude`, `longitude`, `photo`, `status`, `created_at`, `region_id`, `city_id`, `report_type_id`, `status_id`) VALUES
(63, 13, 'Сумська область', 'Білопілля', 'trash', 'Багато сміття на узбічі', 51.13911900, 34.29049300, 'Uploads/68335af43ae3d_Без названия.jpg', 'in_progress', '2025-05-25 18:00:51', 1, 1, 1, 1),
(64, 14, 'Сумська область', 'Суми', 'trash', 'Лежить сміття звалище', 50.92445028, 34.79357989, 'Uploads/6833653adf16d_Без названия.jpg', 'resolved', '2025-05-25 18:44:33', 1, 2, 1, 2),
(67, 2, 'Одеська область', 'Одеса', 'light', 'Вуличний ліхтар не працює, темрява ввечері.', 46.48250000, 30.72330000, NULL, 'resolved', '2025-05-26 14:53:12', 4, 5, 3, 2),
(68, 2, 'Харківська область', 'Харків', 'sign', 'Дорожній знак \"Стоп\" пошкоджено.', 49.99350000, 36.23040000, NULL, 'resolved', '2025-05-26 14:53:12', 5, 6, 4, 2),
(69, 2, 'Дніпропетровська область', 'Дніпро', 'other', 'Затоплення дороги після дощу, потрібен ремонт.', 48.46470000, 35.04620000, NULL, 'resolved', '2025-05-26 14:53:12', 6, 7, 5, 2),
(70, 2, 'Сумська область', 'Суми', 'pothole', 'Велика яма на тротуарі, небезпека для пішоходів.', 50.90770000, 34.79810000, NULL, 'resolved', '2025-05-26 14:53:12', 1, 2, 2, 2),
(71, 2, 'Вінницька область', 'Вінниця', 'trash', 'Сміттєві баки переповнені, потрібне вивезення.', 49.23310000, 28.46820000, NULL, 'resolved', '2025-05-26 14:53:12', 7, 8, 1, 2),
(72, 2, 'Полтавська область', 'Полтава', 'light', 'Освітлення в парку не працює, небезпечно.', 49.58830000, 34.55140000, NULL, 'resolved', '2025-05-26 14:53:12', 8, 9, 3, 2),
(73, 2, 'Чернігівська область', 'Чернігів', 'sign', 'Знак \"Пішохідний перехід\" вицвів.', 51.49820000, 31.28930000, NULL, 'resolved', '2025-05-26 14:53:12', 9, 10, 4, 2),
(74, 2, 'Черкаська область', 'Черкаси', 'other', 'Проблема з каналізацією, витік води.', 49.44440000, 32.05980000, NULL, 'resolved', '2025-05-26 14:53:12', 10, 11, 5, 2),
(75, 2, 'Хмельницька область', 'Хмельницький', 'pothole', 'Вибоїна на перехресті, ускладнює рух.', 49.42290000, 26.98710000, NULL, 'in_progress', '2025-05-26 14:53:12', 11, 12, 2, 1),
(76, 2, 'Рівненська область', 'Рівне', 'trash', 'Сміття біля зупинки, потрібне прибирання.', 50.62310000, 26.22760000, NULL, 'in_progress', '2025-05-26 14:53:12', 12, 13, 1, 1),
(77, 2, 'Івано-Франківська область', 'Івано-Франківськ', 'light', 'Ліхтар на вулиці не працює.', 48.92150000, 24.70970000, NULL, 'in_progress', '2025-05-26 14:53:12', 13, 14, 3, 1),
(78, 2, 'Тернопільська область', 'Тернопіль', 'sign', 'Дорожній знак похилений, потрібен ремонт.', 49.55350000, 25.59480000, NULL, 'in_progress', '2025-05-26 14:53:12', 14, 15, 4, 1),
(79, 2, 'Волинська область', 'Луцьк', 'other', 'Засмічення зливової каналізації.', 50.74720000, 25.32540000, NULL, 'in_progress', '2025-05-26 14:53:12', 15, 16, 5, 1),
(80, 2, 'Закарпатська область', 'Ужгород', 'pothole', 'Яма на дорозі біля ринку.', 48.62080000, 22.28790000, NULL, 'in_progress', '2025-05-26 14:53:12', 16, 17, 2, 1),
(81, 2, 'Запорізька область', 'Запоріжжя', 'trash', 'Сміття на дитячому майданчику.', 47.83880000, 35.13960000, NULL, 'in_progress', '2025-05-26 14:53:12', 17, 18, 1, 1),
(82, 2, 'Кіровоградська область', 'Кропивницький', 'light', 'Несправне освітлення на мосту.', 48.50790000, 32.26230000, NULL, 'in_progress', '2025-05-26 14:53:12', 18, 19, 3, 1),
(83, 2, 'Миколаївська область', 'Миколаїв', 'sign', 'Знак \"Обережно, діти\" відсутній.', 46.97500000, 31.99460000, NULL, 'in_progress', '2025-05-26 14:53:12', 19, 20, 4, 1),
(84, 2, 'Херсонська область', 'Херсон', 'other', 'Пошкоджена труба, витік води.', 46.63540000, 32.61690000, NULL, 'in_progress', '2025-05-26 14:53:12', 20, 21, 5, 1),
(85, 2, 'Житомирська область', 'Житомир', 'pothole', 'Вибоїна на головній вулиці.', 50.26490000, 28.67670000, NULL, 'in_progress', '2025-05-26 14:53:12', 21, 22, 2, 1),
(86, 2, 'Чернівецька область', 'Чернівці', 'trash', 'Сміття біля вокзалу, потрібне прибирання.', 48.29210000, 25.93580000, NULL, 'in_progress', '2025-05-26 14:53:12', 22, 23, 1, 1),
(87, 2, 'Сумська область', 'Білопілля', 'light', 'Ліхтар на перехресті не працює.', 51.13910000, 34.29050000, NULL, 'in_progress', '2025-05-26 14:53:12', 1, 1, 3, 1),
(88, 2, 'Київська область', 'Біла Церква', 'sign', 'Дорожній знак закритий гілками.', 49.79680000, 30.11540000, NULL, 'in_progress', '2025-05-26 14:53:12', 2, 24, 4, 1),
(89, 2, 'Львівська область', 'Дрогобич', 'other', 'Затоплення після сильного дощу.', 49.34990000, 23.50040000, NULL, 'in_progress', '2025-05-26 14:53:12', 3, 25, 5, 1),
(90, 2, 'Одеська область', 'Ізмаїл', 'pothole', 'Велика яма на виїзді з міста.', 45.35150000, 28.83630000, NULL, 'in_progress', '2025-05-26 14:53:12', 4, 26, 2, 1),
(91, 2, 'Харківська область', 'Ізюм', 'trash', 'Сміття на узбіччі траси.', 49.20880000, 37.27840000, NULL, 'in_progress', '2025-05-26 14:53:12', 5, 27, 1, 1),
(92, 2, 'Дніпропетровська область', 'Кривий Ріг', 'light', 'Освітлення на площі не працює.', 47.90970000, 33.38040000, NULL, 'in_progress', '2025-05-26 14:53:12', 6, 28, 3, 1),
(93, 2, 'Сумська область', 'Конотоп', 'sign', 'Знак \"Пішохідна зона\" пошкоджено.', 51.24030000, 33.20260000, NULL, 'in_progress', '2025-05-26 14:53:12', 1, 29, 4, 1),
(94, 2, 'Вінницька область', 'Жмеринка', 'other', 'Проблема з водопостачанням.', 49.03710000, 28.11200000, NULL, 'in_progress', '2025-05-26 14:53:12', 7, 30, 5, 1),
(95, 2, 'Полтавська область', 'Кременчук', 'pothole', 'Вибоїна на мосту, небезпечно.', 49.06800000, 33.42040000, NULL, 'in_progress', '2025-05-26 14:53:12', 8, 31, 2, 1),
(96, 2, 'Чернігівська область', 'Ніжин', 'trash', 'Сміття біля школи, потрібне прибирання.', 51.04800000, 31.88690000, NULL, 'in_progress', '2025-05-26 14:53:12', 9, 32, 1, 1),
(97, 2, 'Черкаська область', 'Умань', 'light', 'Ліхтар у парку несправний.', 48.74840000, 30.22190000, NULL, 'in_progress', '2025-05-26 14:53:12', 10, 33, 3, 1),
(98, 2, 'Хмельницька область', 'Кам\'янець-Подільський', 'sign', 'Дорожній знак упав.', 48.68470000, 26.58250000, NULL, 'in_progress', '2025-05-26 14:53:12', 11, 34, 4, 1),
(99, 2, 'Рівненська область', 'Дубно', 'other', 'Засмічення каналізації, потрібен ремонт.', 50.41690000, 25.73530000, NULL, 'in_progress', '2025-05-26 14:53:12', 12, 35, 5, 1),
(100, 2, 'Івано-Франківська область', 'Коломия', 'pothole', 'Яма на дорозі до вокзалу.', 48.53060000, 25.04030000, NULL, 'in_progress', '2025-05-26 14:53:12', 13, 36, 2, 1),
(101, 2, 'Тернопільська область', 'Чортків', 'trash', 'Сміття на центральній площі.', 49.01710000, 25.79830000, NULL, 'in_progress', '2025-05-26 14:53:12', 14, 37, 1, 1),
(102, 2, 'Волинська область', 'Ковель', 'light', 'Освітлення на перехресті не працює.', 51.21530000, 24.70870000, NULL, 'in_progress', '2025-05-26 14:53:12', 15, 38, 3, 1),
(103, 2, 'Закарпатська область', 'Мукачево', 'sign', 'Знак \"Обмеження швидкості\" вицвів.', 48.44150000, 22.71770000, NULL, 'in_progress', '2025-05-26 14:53:12', 16, 39, 4, 1),
(104, 2, 'Запорізька область', 'Мелітополь', 'other', 'Пошкоджена огорожа біля дороги.', 46.84890000, 35.36510000, NULL, 'in_progress', '2025-05-26 14:53:12', 17, 40, 5, 1),
(105, 2, 'Кіровоградська область', 'Олександрія', 'pothole', 'Вибоїна на виїзді з міста.', 48.66960000, 33.11590000, NULL, 'new', '2025-05-26 14:53:12', 18, 41, 2, 3),
(106, 2, 'Миколаївська область', 'Первомайськ', 'trash', 'Сміття біля річки, потрібне прибирання.', 48.04430000, 30.85070000, NULL, 'new', '2025-05-26 14:53:12', 19, 42, 1, 3),
(107, 2, 'Херсонська область', 'Нова Каховка', 'light', 'Ліхтар на набережній несправний.', 46.75450000, 33.34860000, NULL, 'new', '2025-05-26 14:53:12', 20, 43, 3, 3),
(108, 2, 'Житомирська область', 'Бердичів', 'sign', 'Дорожній знак закритий рекламою.', 49.89930000, 28.60240000, NULL, 'new', '2025-05-26 14:53:12', 21, 44, 4, 3),
(109, 2, 'Чернівецька область', 'Хотин', 'other', 'Проблема з дренажною системою.', 48.50690000, 26.49120000, NULL, 'new', '2025-05-26 14:53:12', 22, 45, 5, 3),
(110, 2, 'Київська область', 'Бровари', 'pothole', 'Яма на дорозі до супермаркету.', 50.51810000, 30.79080000, NULL, 'new', '2025-05-26 14:53:12', 2, 46, 2, 3),
(111, 2, 'Львівська область', 'Стрий', 'trash', 'Сміття на автобусній зупинці.', 49.26220000, 23.85610000, NULL, 'new', '2025-05-26 14:53:12', 3, 47, 1, 3),
(112, 2, 'Одеська область', 'Чорноморськ', 'light', 'Освітлення на пляжі не працює.', 46.30170000, 30.65690000, NULL, 'new', '2025-05-26 14:53:12', 4, 48, 3, 3),
(113, 2, 'Харківська область', 'Лозова', 'sign', 'Знак \"Напрямок руху\" пошкоджено.', 48.88940000, 36.31760000, NULL, 'new', '2025-05-26 14:53:12', 5, 49, 4, 3),
(114, 2, 'Дніпропетровська область', 'Павлоград', 'other', 'Затоплення дороги після дощу.', 48.53210000, 35.87010000, NULL, 'new', '2025-05-26 14:53:12', 6, 50, 5, 3),
(117, 17, 'Сумська область', 'Суми', 'pothole', 'Велика яма на дорозі', 50.89758047, 34.80474160, 'Uploads/6841ae87d6eca_pothole.jpg', 'new', '2025-06-05 14:48:16', NULL, NULL, NULL, NULL),
(120, 2, 'Вінницька область', 'Вінниця', 'trash', 'Велике сміття', 49.22927370, 28.46945307, NULL, 'new', '2025-06-11 08:37:10', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Структура таблиці `report_statuses`
--

CREATE TABLE `report_statuses` (
  `id` int(11) NOT NULL,
  `name` enum('new','in_progress','resolved') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `report_statuses`
--

INSERT INTO `report_statuses` (`id`, `name`) VALUES
(3, 'new'),
(1, 'in_progress'),
(2, 'resolved');

-- --------------------------------------------------------

--
-- Структура таблиці `report_types`
--

CREATE TABLE `report_types` (
  `id` int(11) NOT NULL,
  `name` enum('pothole','trash','light','sign','other') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `report_types`
--

INSERT INTO `report_types` (`id`, `name`) VALUES
(2, 'pothole'),
(1, 'trash'),
(3, 'light'),
(4, 'sign'),
(5, 'other');

-- --------------------------------------------------------

--
-- Структура таблиці `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','moderator','user') DEFAULT 'user',
  `name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `role`, `name`, `phone`) VALUES
(2, 'esinkirilo@gmail.com', '$2y$10$a.h6eill26HM02H42pUjmOLh7KEMRfW0lZYxLCwsU/26i4hWc7/GK', 'admin', 'Кирило', '+38577275703'),
(3, 'esinkirilov@gmail.com', '$2y$10$2QWxRwq8sW61jYsGAQ8F.u5n3rFoaThstU5Da3mSyTeY.WPgbX6HG', 'admin', 'Кирило', '+48577275703'),
(8, '1234@gmail.com', '$2y$10$1EMwRjBFt5TNKe2kkB9p8emLbSobGrQIHMu92S3E2VMXluzHcXu76', 'moderator', 'Кирило', NULL),
(9, 'admin@example.com', '$2y$10$Xs4syJoMNjn5HeGg0ru32eYXMP4LNvZWHqe/xNedGKIe0ZK8t5dS6', 'user', 'Адмін', '+48577275703'),
(10, '12345@gmai.com', '$2y$10$zSsVFWe0CiEd6H.zrYd.D.efmb.ECLb3gxKzt75a2izXfLpuCFSsK', 'user', 'Вікторіяя', '+380953732726'),
(11, 'esinatatiana2018@gmail.com', '$2y$10$RZl6P8MoXZ/vKl212HH/jeB4oU5FYntEfmTAaB22v5O9aUIuCbk46', 'user', 'Тетянаааа', '+380953732726'),
(13, 'esinatatiana2019@gmail.com', '$2y$10$z1mgdRVLN89xmUb2YiXwZeqse4aV5OeKYGxROEHY0.gSZNhMhBszO', 'admin', 'Тетяна', '+380954732311'),
(14, 'esin@ukr.net', '$2y$10$xqAyBtZC6qTGLXAzL676AuO0H7jYrESiz1AwBQz30KhVgcPmQmxMi', 'admin', 'Сергійоооо', '+380954732311'),
(15, '12346@gmail.com', '$2y$10$3sD8CEO82eHN9D4.UQtDJu24EnxZiXNAtxU0xhbzD6x9nGCQ4BPXu', 'user', 'Олег', '+380953732726'),
(16, 'tralelo@gmail.com', '$2y$10$LU14MJEKqo5IsbV5Y0r9zuGLpGjouSHQWI.iUNPe6o/oYMLB8BGoq', 'user', 'Кирило', '+48577275703'),
(17, 'esin64@gmail.com', '$2y$10$.8jaY2ldVmu8E3q3038bs.1LJBPp7No4U6xitxC6JQ0mqxZcJZjeO', 'user', 'Кирило', '+48577275703'),
(18, '1234678@gmail.com', '$2y$10$R2gCQgc5MfocFGakMgFuTurgZR4BZ3nUfij.TkszPZnQ82NoFrIbm', 'user', 'Кирило', '+48577275703');

--
-- Індекси збережених таблиць
--

--
-- Індекси таблиці `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Індекси таблиці `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`,`region_id`),
  ADD KEY `region_id` (`region_id`);

--
-- Індекси таблиці `regions`
--
ALTER TABLE `regions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Індекси таблиці `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_reports_region` (`region_id`),
  ADD KEY `fk_reports_city` (`city_id`),
  ADD KEY `fk_reports_type` (`report_type_id`),
  ADD KEY `fk_reports_status` (`status_id`);

--
-- Індекси таблиці `report_statuses`
--
ALTER TABLE `report_statuses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Індекси таблиці `report_types`
--
ALTER TABLE `report_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Індекси таблиці `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT для збережених таблиць
--

--
-- AUTO_INCREMENT для таблиці `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT для таблиці `cities`
--
ALTER TABLE `cities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT для таблиці `regions`
--
ALTER TABLE `regions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT для таблиці `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;

--
-- AUTO_INCREMENT для таблиці `report_statuses`
--
ALTER TABLE `report_statuses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблиці `report_types`
--
ALTER TABLE `report_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблиці `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Обмеження зовнішнього ключа збережених таблиць
--

--
-- Обмеження зовнішнього ключа таблиці `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Обмеження зовнішнього ключа таблиці `cities`
--
ALTER TABLE `cities`
  ADD CONSTRAINT `cities_ibfk_1` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`);

--
-- Обмеження зовнішнього ключа таблиці `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `fk_reports_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_reports_region` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_reports_status` FOREIGN KEY (`status_id`) REFERENCES `report_statuses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_reports_type` FOREIGN KEY (`report_type_id`) REFERENCES `report_types` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
