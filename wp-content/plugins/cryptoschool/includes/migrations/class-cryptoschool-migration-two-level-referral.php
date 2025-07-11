<?php
/**
 * Миграция для двухуровневой реферальной системы
 *
 * @package CryptoSchool
 * @subpackage Migrations
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс миграции для двухуровневой реферальной системы
 */
class CryptoSchool_Migration_Two_Level_Referral {

    /**
     * Версия миграции
     *
     * @var string
     */
    public $version = '1.4.0';

    /**
     * Выполнение миграции
     *
     * @return bool
     */
    public function up() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $success = true;

        // 1. Обновляем таблицу платежей для поддержки скидок
        $payments_table = $wpdb->prefix . 'cryptoschool_payments';
        
        // Проверяем, существует ли таблица платежей
        if ($wpdb->get_var("SHOW TABLES LIKE '{$payments_table}'") != $payments_table) {
            // Создаем таблицу платежей, если её нет
            $sql = "CREATE TABLE {$payments_table} (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id BIGINT(20) UNSIGNED NOT NULL,
                package_id BIGINT(20) UNSIGNED NULL,
                amount DECIMAL(10,2) NOT NULL,
                original_amount DECIMAL(10,2) NULL,
                discount_percent DECIMAL(5,2) DEFAULT 0,
                discount_amount DECIMAL(10,2) DEFAULT 0,
                final_amount DECIMAL(10,2) NULL,
                currency VARCHAR(10) DEFAULT 'USD',
                payment_method VARCHAR(50) NULL,
                payment_gateway VARCHAR(50) NULL,
                transaction_id VARCHAR(255) NULL,
                referral_link_id BIGINT(20) UNSIGNED NULL,
                status VARCHAR(50) DEFAULT 'pending',
                payment_date DATETIME NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY user_id (user_id),
                KEY package_id (package_id),
                KEY referral_link_id (referral_link_id),
                KEY status (status),
                KEY payment_date (payment_date)
            ) {$charset_collate};";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);

            if ($wpdb->last_error) {
                error_log('Ошибка создания таблицы платежей: ' . $wpdb->last_error);
                $success = false;
            }
        } else {
            // Добавляем новые поля к существующей таблице
            $columns_to_add = [
                'original_amount' => 'DECIMAL(10,2) NULL AFTER amount',
                'discount_percent' => 'DECIMAL(5,2) DEFAULT 0 AFTER original_amount',
                'discount_amount' => 'DECIMAL(10,2) DEFAULT 0 AFTER discount_percent',
                'final_amount' => 'DECIMAL(10,2) NULL AFTER discount_amount',
                'referral_link_id' => 'BIGINT(20) UNSIGNED NULL AFTER transaction_id'
            ];

            foreach ($columns_to_add as $column => $definition) {
                // Проверяем, существует ли колонка
                $column_exists = $wpdb->get_results(
                    $wpdb->prepare(
                        "SHOW COLUMNS FROM {$payments_table} LIKE %s",
                        $column
                    )
                );

                if (empty($column_exists)) {
                    $wpdb->query("ALTER TABLE {$payments_table} ADD COLUMN {$column} {$definition}");
                    
                    if ($wpdb->last_error) {
                        error_log("Ошибка добавления колонки {$column}: " . $wpdb->last_error);
                        $success = false;
                    }
                }
            }

            // Добавляем индекс для referral_link_id, если его нет
            $index_exists = $wpdb->get_results(
                "SHOW INDEX FROM {$payments_table} WHERE Key_name = 'referral_link_id'"
            );

            if (empty($index_exists)) {
                $wpdb->query("ALTER TABLE {$payments_table} ADD KEY referral_link_id (referral_link_id)");
                
                if ($wpdb->last_error) {
                    error_log('Ошибка добавления индекса referral_link_id: ' . $wpdb->last_error);
                    $success = false;
                }
            }
        }

        // 2. Обновляем таблицу реферальных транзакций
        $transactions_table = $wpdb->prefix . 'cryptoschool_referral_transactions';
        
        // Проверяем, существует ли колонка referral_level
        $level_column_exists = $wpdb->get_results(
            $wpdb->prepare(
                "SHOW COLUMNS FROM {$transactions_table} LIKE %s",
                'referral_level'
            )
        );

        if (empty($level_column_exists)) {
            $wpdb->query("ALTER TABLE {$transactions_table} ADD COLUMN referral_level TINYINT(1) DEFAULT 1 AFTER referral_link_id");
            
            if ($wpdb->last_error) {
                error_log('Ошибка добавления колонки referral_level: ' . $wpdb->last_error);
                $success = false;
            }
        }

        // Добавляем индекс для referral_level, если его нет
        $level_index_exists = $wpdb->get_results(
            "SHOW INDEX FROM {$transactions_table} WHERE Key_name = 'referral_level'"
        );

        if (empty($level_index_exists)) {
            $wpdb->query("ALTER TABLE {$transactions_table} ADD KEY referral_level (referral_level)");
            
            if ($wpdb->last_error) {
                error_log('Ошибка добавления индекса referral_level: ' . $wpdb->last_error);
                $success = false;
            }
        }

        // 3. Создаем таблицу для отслеживания иерархии рефералов
        $hierarchy_table = $wpdb->prefix . 'cryptoschool_referral_hierarchy';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$hierarchy_table}'") != $hierarchy_table) {
            $sql = "CREATE TABLE {$hierarchy_table} (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                level1_user_id BIGINT(20) UNSIGNED NOT NULL COMMENT 'ID рефовода 1-го уровня',
                level2_user_id BIGINT(20) UNSIGNED NOT NULL COMMENT 'ID рефовода 2-го уровня',
                referral_user_id BIGINT(20) UNSIGNED NOT NULL COMMENT 'ID реферала',
                level1_link_id BIGINT(20) UNSIGNED NOT NULL COMMENT 'ID ссылки 1-го уровня',
                level2_link_id BIGINT(20) UNSIGNED NULL COMMENT 'ID ссылки 2-го уровня',
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY level1_user_id (level1_user_id),
                KEY level2_user_id (level2_user_id),
                KEY referral_user_id (referral_user_id),
                KEY level1_link_id (level1_link_id),
                KEY level2_link_id (level2_link_id),
                UNIQUE KEY unique_referral (referral_user_id)
            ) {$charset_collate};";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);

            if ($wpdb->last_error) {
                error_log('Ошибка создания таблицы иерархии рефералов: ' . $wpdb->last_error);
                $success = false;
            }
        }

        // 4. Обновляем таблицу реферальных пользователей
        $referral_users_table = $wpdb->prefix . 'cryptoschool_referral_users';
        
        // Добавляем колонку для отслеживания покупок
        $purchased_column_exists = $wpdb->get_results(
            $wpdb->prepare(
                "SHOW COLUMNS FROM {$referral_users_table} LIKE %s",
                'has_purchased'
            )
        );

        if (empty($purchased_column_exists)) {
            $wpdb->query("ALTER TABLE {$referral_users_table} ADD COLUMN has_purchased TINYINT(1) DEFAULT 0 AFTER status");
            
            if ($wpdb->last_error) {
                error_log('Ошибка добавления колонки has_purchased: ' . $wpdb->last_error);
                $success = false;
            }
        }

        // Добавляем колонку для суммы покупок
        $purchase_amount_column_exists = $wpdb->get_results(
            $wpdb->prepare(
                "SHOW COLUMNS FROM {$referral_users_table} LIKE %s",
                'total_purchase_amount'
            )
        );

        if (empty($purchase_amount_column_exists)) {
            $wpdb->query("ALTER TABLE {$referral_users_table} ADD COLUMN total_purchase_amount DECIMAL(10,2) DEFAULT 0 AFTER has_purchased");
            
            if ($wpdb->last_error) {
                error_log('Ошибка добавления колонки total_purchase_amount: ' . $wpdb->last_error);
                $success = false;
            }
        }

        // 5. Создаем триггеры для автоматического обновления статистики (если поддерживается)
        $this->create_triggers();

        // 6. Обновляем существующие данные
        $this->migrate_existing_data();

        // Логируем результат миграции
        if ($success) {
            error_log('Миграция двухуровневой реферальной системы выполнена успешно');
        } else {
            error_log('Миграция двухуровневой реферальной системы завершена с ошибками');
        }

        return $success;
    }

    /**
     * Откат миграции
     *
     * @return bool
     */
    public function down() {
        global $wpdb;

        $success = true;

        // Удаляем добавленные колонки из таблицы платежей
        $payments_table = $wpdb->prefix . 'cryptoschool_payments';
        $columns_to_remove = ['original_amount', 'discount_percent', 'discount_amount', 'final_amount', 'referral_link_id'];

        foreach ($columns_to_remove as $column) {
            $wpdb->query("ALTER TABLE {$payments_table} DROP COLUMN IF EXISTS {$column}");
            
            if ($wpdb->last_error) {
                error_log("Ошибка удаления колонки {$column}: " . $wpdb->last_error);
                $success = false;
            }
        }

        // Удаляем колонку referral_level из таблицы транзакций
        $transactions_table = $wpdb->prefix . 'cryptoschool_referral_transactions';
        $wpdb->query("ALTER TABLE {$transactions_table} DROP COLUMN IF EXISTS referral_level");

        // Удаляем таблицу иерархии
        $hierarchy_table = $wpdb->prefix . 'cryptoschool_referral_hierarchy';
        $wpdb->query("DROP TABLE IF EXISTS {$hierarchy_table}");

        // Удаляем добавленные колонки из таблицы реферальных пользователей
        $referral_users_table = $wpdb->prefix . 'cryptoschool_referral_users';
        $wpdb->query("ALTER TABLE {$referral_users_table} DROP COLUMN IF EXISTS has_purchased");
        $wpdb->query("ALTER TABLE {$referral_users_table} DROP COLUMN IF EXISTS total_purchase_amount");

        // Удаляем триггеры
        $this->drop_triggers();

        return $success;
    }

    /**
     * Создание триггеров для автоматического обновления статистики
     *
     * @return void
     */
    private function create_triggers() {
        global $wpdb;

        // Триггер для обновления статистики при добавлении транзакции
        $trigger_sql = "
        CREATE TRIGGER IF NOT EXISTS update_referral_stats_after_insert
        AFTER INSERT ON {$wpdb->prefix}cryptoschool_referral_transactions
        FOR EACH ROW
        BEGIN
            UPDATE {$wpdb->prefix}cryptoschool_referral_links 
            SET total_earned = total_earned + NEW.amount,
                updated_at = NOW()
            WHERE id = NEW.referral_link_id;
            
            UPDATE {$wpdb->prefix}cryptoschool_referral_users 
            SET has_purchased = 1,
                total_purchase_amount = total_purchase_amount + NEW.amount
            WHERE user_id = NEW.user_id AND referrer_id = NEW.referrer_id;
        END";

        $wpdb->query($trigger_sql);

        if ($wpdb->last_error) {
            error_log('Ошибка создания триггера update_referral_stats_after_insert: ' . $wpdb->last_error);
        }
    }

    /**
     * Удаление триггеров
     *
     * @return void
     */
    private function drop_triggers() {
        global $wpdb;

        $wpdb->query("DROP TRIGGER IF EXISTS update_referral_stats_after_insert");
    }

    /**
     * Миграция существующих данных
     *
     * @return void
     */
    private function migrate_existing_data() {
        global $wpdb;

        // Обновляем существующие транзакции, устанавливая referral_level = 1
        $wpdb->query("
            UPDATE {$wpdb->prefix}cryptoschool_referral_transactions 
            SET referral_level = 1 
            WHERE referral_level IS NULL OR referral_level = 0
        ");

        // Обновляем статистику реферальных пользователей на основе существующих транзакций
        $wpdb->query("
            UPDATE {$wpdb->prefix}cryptoschool_referral_users ru
            SET has_purchased = 1,
                total_purchase_amount = (
                    SELECT COALESCE(SUM(rt.amount), 0)
                    FROM {$wpdb->prefix}cryptoschool_referral_transactions rt
                    WHERE rt.user_id = ru.user_id AND rt.referrer_id = ru.referrer_id
                )
            WHERE EXISTS (
                SELECT 1 
                FROM {$wpdb->prefix}cryptoschool_referral_transactions rt
                WHERE rt.user_id = ru.user_id AND rt.referrer_id = ru.referrer_id
            )
        ");

        error_log('Миграция существующих данных завершена');
    }

    /**
     * Проверка необходимости выполнения миграции
     *
     * @return bool
     */
    public function should_run() {
        global $wpdb;

        // Проверяем, существует ли колонка referral_level
        $level_column_exists = $wpdb->get_results(
            $wpdb->prepare(
                "SHOW COLUMNS FROM {$wpdb->prefix}cryptoschool_referral_transactions LIKE %s",
                'referral_level'
            )
        );

        // Если колонки нет, миграция нужна
        return empty($level_column_exists);
    }
}
