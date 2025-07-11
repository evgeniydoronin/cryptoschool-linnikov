/**
 * ТЕСТОВЫЕ ДАННЫЕ ДЛЯ РЕФЕРАЛЬНОЙ СИСТЕМЫ
 * Создает тестовые данные для пользователя ID 1 (администратор)
 * 
 * Включает:
 * - 4 реферальные ссылки с разными настройками
 * - 5 фиктивных рефералов (4 купили, 1 только зарегистрировался)
 * - Транзакции с комиссиями
 * - Историю запросов на вывод
 * 
 * Дата создания: 16.06.2025
 */

-- ================================
-- 1. РЕФЕРАЛЬНЫЕ ССЫЛКИ
-- ================================

-- Очищаем существующие тестовые данные для пользователя ID 1
DELETE FROM wp_cryptoschool_referral_transactions WHERE referrer_id = 1;
DELETE FROM wp_cryptoschool_referral_users WHERE referrer_id = 1;
DELETE FROM wp_cryptoschool_withdrawal_requests WHERE user_id = 1;
DELETE FROM wp_cryptoschool_referral_links WHERE user_id = 1;

-- Добавляем 4 реферальные ссылки для пользователя ID 1
INSERT INTO wp_cryptoschool_referral_links (
    id, user_id, referral_code, discount_percent, commission_percent, created_at, updated_at
) VALUES
(1, 1, 'YT1ABC123', 10.00, 30.00, '2025-06-01 10:00:00', '2025-06-01 10:00:00'),
(2, 1, 'TG1XYZ456', 15.00, 25.00, '2025-06-02 14:30:00', '2025-06-02 14:30:00'),
(3, 1, 'IG1DEF789', 20.00, 20.00, '2025-06-03 16:45:00', '2025-06-03 16:45:00'),
(4, 1, 'PC1GHI012', 5.00, 35.00, '2025-06-04 09:15:00', '2025-06-04 09:15:00');

-- ================================
-- 2. ФИКТИВНЫЕ РЕФЕРАЛЫ
-- ================================

-- Добавляем 5 фиктивных рефералов (без создания реальных пользователей)
-- Используем user_id от 100 до 104 (фиктивные ID)
INSERT INTO wp_cryptoschool_referral_users (
    id, referrer_id, user_id, referral_link_id, registration_date, status, created_at, updated_at
) VALUES
(1, 1, 100, 1, '2025-06-05 12:00:00', 'purchased', '2025-06-05 12:00:00', '2025-06-05 12:30:00'),
(2, 1, 101, 2, '2025-06-06 15:30:00', 'purchased', '2025-06-06 15:30:00', '2025-06-06 16:00:00'),
(3, 1, 102, 3, '2025-06-07 11:20:00', 'purchased', '2025-06-07 11:20:00', '2025-06-07 11:50:00'),
(4, 1, 103, 4, '2025-06-08 14:45:00', 'purchased', '2025-06-08 14:45:00', '2025-06-08 15:15:00'),
(5, 1, 104, 1, '2025-06-09 10:30:00', 'registered', '2025-06-09 10:30:00', '2025-06-09 10:30:00');

-- ================================
-- 3. ФИКТИВНЫЕ ПЛАТЕЖИ
-- ================================

-- Добавляем фиктивные платежи для расчета комиссий
-- Используем package_id = 1 (предполагаем, что такой пакет существует)
INSERT INTO wp_cryptoschool_payments (
    id, user_id, package_id, amount, currency, payment_method, status, 
    referral_link_id, payment_date, created_at, updated_at
) VALUES
(1, 100, 1, 100.00, 'USD', 'crypto', 'completed', 1, '2025-06-05 12:30:00', '2025-06-05 12:30:00', '2025-06-05 12:30:00'),
(2, 101, 1, 150.00, 'USD', 'crypto', 'completed', 2, '2025-06-06 16:00:00', '2025-06-06 16:00:00', '2025-06-06 16:00:00'),
(3, 102, 1, 80.00, 'USD', 'crypto', 'completed', 3, '2025-06-07 11:50:00', '2025-06-07 11:50:00', '2025-06-07 11:50:00'),
(4, 103, 1, 200.00, 'USD', 'crypto', 'completed', 4, '2025-06-08 15:15:00', '2025-06-08 15:15:00', '2025-06-08 15:15:00');

-- ================================
-- 4. РЕФЕРАЛЬНЫЕ ТРАНЗАКЦИИ (КОМИССИИ)
-- ================================

-- Добавляем транзакции с комиссиями для каждой покупки
INSERT INTO wp_cryptoschool_referral_transactions (
    id, referrer_id, user_id, payment_id, amount, status, created_at, processed_at
) VALUES
(1, 1, 100, 1, 30.00, 'completed', '2025-06-05 12:30:00', '2025-06-05 12:35:00'),  -- 30% от $100
(2, 1, 101, 2, 37.50, 'completed', '2025-06-06 16:00:00', '2025-06-06 16:05:00'),  -- 25% от $150
(3, 1, 102, 3, 16.00, 'completed', '2025-06-07 11:50:00', '2025-06-07 11:55:00'),  -- 20% от $80
(4, 1, 103, 4, 70.00, 'completed', '2025-06-08 15:15:00', '2025-06-08 15:20:00');  -- 35% от $200

-- ================================
-- 5. ИСТОРИЯ ЗАПРОСОВ НА ВЫВОД
-- ================================

-- Добавляем историю запросов на вывод с разными статусами
INSERT INTO wp_cryptoschool_withdrawal_requests (
    id, user_id, amount, crypto_address, status, request_date, payment_date, comment, created_at, updated_at
) VALUES
(1, 1, 150.00, 'TQn9J5aTqe7pij1mHjNwVqnzVx8JMhzrvJ', 'paid', '2025-06-15 14:20:00', '2025-06-15 18:30:00', 'Выплачено', '2025-06-15 14:20:00', '2025-06-15 18:30:00'),
(2, 1, 100.00, 'TQn9J5aTqe7pij1mHjNwVqnzVx8JMhzrvJ', 'approved', '2025-06-10 16:30:00', NULL, '', '2025-06-10 16:30:00', '2025-06-12 10:15:00'),
(3, 1, 80.00, 'TQn9J5aTqe7pij1mHjNwVqnzVx8JMhzrvJ', 'rejected', '2025-06-05 12:45:00', NULL, 'Неправильные реквизиты', '2025-06-05 12:45:00', '2025-06-05 15:20:00');

-- ================================
-- 6. ОБНОВЛЕНИЕ СТРУКТУРЫ ТАБЛИЦ
-- ================================

-- Добавляем недостающие поля в таблицу реферальных ссылок для множественных ссылок
-- Проверяем и добавляем колонки по одной (игнорируем ошибки если колонка уже существует)

-- Добавляем link_name
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'wp_cryptoschool_referral_links' 
     AND column_name = 'link_name' 
     AND table_schema = DATABASE()) = 0,
    'ALTER TABLE wp_cryptoschool_referral_links ADD COLUMN link_name VARCHAR(255) DEFAULT NULL AFTER referral_code',
    'SELECT "Column link_name already exists"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Добавляем link_description
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'wp_cryptoschool_referral_links' 
     AND column_name = 'link_description' 
     AND table_schema = DATABASE()) = 0,
    'ALTER TABLE wp_cryptoschool_referral_links ADD COLUMN link_description TEXT DEFAULT NULL AFTER link_name',
    'SELECT "Column link_description already exists"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Добавляем clicks_count
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'wp_cryptoschool_referral_links' 
     AND column_name = 'clicks_count' 
     AND table_schema = DATABASE()) = 0,
    'ALTER TABLE wp_cryptoschool_referral_links ADD COLUMN clicks_count INT DEFAULT 0 AFTER link_description',
    'SELECT "Column clicks_count already exists"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Добавляем conversions_count
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'wp_cryptoschool_referral_links' 
     AND column_name = 'conversions_count' 
     AND table_schema = DATABASE()) = 0,
    'ALTER TABLE wp_cryptoschool_referral_links ADD COLUMN conversions_count INT DEFAULT 0 AFTER clicks_count',
    'SELECT "Column conversions_count already exists"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Добавляем total_earned
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'wp_cryptoschool_referral_links' 
     AND column_name = 'total_earned' 
     AND table_schema = DATABASE()) = 0,
    'ALTER TABLE wp_cryptoschool_referral_links ADD COLUMN total_earned DECIMAL(10,2) DEFAULT 0 AFTER conversions_count',
    'SELECT "Column total_earned already exists"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Обновляем данные реферальных ссылок с названиями и статистикой
UPDATE wp_cryptoschool_referral_links SET 
    link_name = 'YouTube канал',
    link_description = 'Ссылка для продвижения через YouTube канал',
    clicks_count = 45,
    conversions_count = 2,
    total_earned = 67.50  -- 30.00 + 37.50 (если Ольга тоже купит)
WHERE id = 1;

UPDATE wp_cryptoschool_referral_links SET 
    link_name = 'Telegram канал',
    link_description = 'Ссылка для продвижения через Telegram канал',
    clicks_count = 32,
    conversions_count = 1,
    total_earned = 37.50
WHERE id = 2;

UPDATE wp_cryptoschool_referral_links SET 
    link_name = 'Instagram',
    link_description = 'Ссылка для продвижения через Instagram',
    clicks_count = 28,
    conversions_count = 1,
    total_earned = 16.00
WHERE id = 3;

UPDATE wp_cryptoschool_referral_links SET 
    link_name = 'Личные контакты',
    link_description = 'Ссылка для личных рекомендаций',
    clicks_count = 15,
    conversions_count = 1,
    total_earned = 70.00
WHERE id = 4;

-- ================================
-- ИТОГОВАЯ СТАТИСТИКА ДЛЯ ПОЛЬЗОВАТЕЛЯ ID 1
-- ================================

/*
ОБЩАЯ СТАТИСТИКА:
- Всего приглашено: 5 человек
- Купили программу: 4 человека  
- Общий заработок: $153.50
- Выплачено: $150.00
- В обработке: $100.00 (одобрено)
- Отклонено: $80.00
- Доступно к выводу: $3.50 (153.50 - 150.00)

СТАТИСТИКА ПО ССЫЛКАМ:
1. YouTube канал: 45 кликов, 2 конверсии, $67.50 заработано
2. Telegram канал: 32 клика, 1 конверсия, $37.50 заработано
3. Instagram: 28 кликов, 1 конверсия, $16.00 заработано
4. Личные контакты: 15 кликов, 1 конверсия, $70.00 заработано

РЕФЕРАЛЫ:
1. Анатолий (@anatoly_crypto) - через YouTube - купил за $100 - статус "Успешно"
2. Евгений (@evgeny_trader) - через Telegram - купил за $150 - статус "Успешно"
3. Мария (@maria_invest) - через Instagram - купила за $80 - статус "Успешно"
4. Дмитрий (@dmitry_btc) - через Личные контакты - купил за $200 - статус "Успешно"
5. Ольга (@olga_newbie) - через YouTube - только зарегистрировалась - статус "Зареєстрований"
*/

-- Выводим итоговую информацию
SELECT 
    'ИТОГОВАЯ СТАТИСТИКА ДЛЯ ПОЛЬЗОВАТЕЛЯ ID 1' as info,
    COUNT(DISTINCT ru.user_id) as total_referrals,
    COUNT(DISTINCT CASE WHEN ru.status = 'purchased' THEN ru.user_id END) as purchased_referrals,
    COALESCE(SUM(rt.amount), 0) as total_earned,
    (SELECT COUNT(*) FROM wp_cryptoschool_withdrawal_requests WHERE user_id = 1 AND status = 'paid') as paid_withdrawals,
    (SELECT COALESCE(SUM(amount), 0) FROM wp_cryptoschool_withdrawal_requests WHERE user_id = 1 AND status = 'paid') as total_paid_out
FROM wp_cryptoschool_referral_users ru
LEFT JOIN wp_cryptoschool_referral_transactions rt ON ru.user_id = rt.user_id AND ru.referrer_id = rt.referrer_id
WHERE ru.referrer_id = 1;
