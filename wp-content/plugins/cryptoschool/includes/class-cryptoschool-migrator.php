<?php
/**
 * Класс миграций базы данных
 *
 * Отвечает за обновление схемы базы данных при обновлении плагина
 *
 * @package CryptoSchool
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс миграций базы данных
 */
class CryptoSchool_Migrator {
    /**
     * Версия схемы базы данных
     *
     * @var string
     */
    private $db_version;

    /**
     * Текущая версия плагина
     *
     * @var string
     */
    private $plugin_version;

    /**
     * Конструктор класса
     */
    public function __construct() {
        $this->db_version = get_option('cryptoschool_db_version', '0.0.0');
        $this->plugin_version = CRYPTOSCHOOL_VERSION;
    }

    /**
     * Проверка необходимости миграции
     *
     * @return bool
     */
    public function needs_migration() {
        // Проверка версии
        if (version_compare($this->db_version, $this->plugin_version, '<')) {
            return true;
        }
        
        // Дополнительная проверка для версии 1.0.5 - проверяем наличие новых колонок
        if (version_compare($this->plugin_version, '1.0.5', '>=')) {
            return $this->needs_migration_1_0_5();
        }
        
        // Дополнительная проверка для версии 1.4.1 - проверяем наличие полей платежей
        if (version_compare($this->plugin_version, '1.4.1', '>=')) {
            return $this->needs_migration_1_4_1();
        }
        
        return false;
    }
    
    /**
     * Проверка необходимости миграции 1.0.5
     *
     * @return bool
     */
    private function needs_migration_1_0_5() {
        global $wpdb;
        
        $referral_links_table = $wpdb->prefix . 'cryptoschool_referral_links';
        
        // Проверяем существование таблицы
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$referral_links_table}'") == $referral_links_table;
        if (!$table_exists) {
            return true;
        }
        
        // Проверяем наличие новых колонок
        $required_columns = ['link_name', 'clicks_count', 'is_active'];
        
        foreach ($required_columns as $column) {
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$referral_links_table} LIKE '{$column}'");
            if (empty($column_exists)) {
                $this->log_migration("Миграция 1.0.5 нужна - отсутствует колонка: {$column}");
                return true;
            }
        }
        
        // Проверяем существование таблицы иерархии
        $hierarchy_table = $wpdb->prefix . 'cryptoschool_referral_hierarchy';
        $hierarchy_exists = $wpdb->get_var("SHOW TABLES LIKE '{$hierarchy_table}'") == $hierarchy_table;
        if (!$hierarchy_exists) {
            $this->log_migration("Миграция 1.0.5 нужна - отсутствует таблица иерархии");
            return true;
        }
        
        return false;
    }

    /**
     * Проверка необходимости миграции 1.4.1
     *
     * @return bool
     */
    private function needs_migration_1_4_1() {
        global $wpdb;
        
        $payments_table = $wpdb->prefix . 'cryptoschool_payments';
        
        // Проверяем существование таблицы
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$payments_table}'") == $payments_table;
        if (!$table_exists) {
            $this->log_migration("Миграция 1.4.1 не нужна - таблица платежей не существует");
            return false;
        }
        
        // Проверяем наличие новых колонок для реферальной системы
        $required_columns = ['original_amount', 'discount_percent', 'discount_amount', 'final_amount'];
        
        foreach ($required_columns as $column) {
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$payments_table} LIKE '{$column}'");
            if (empty($column_exists)) {
                $this->log_migration("Миграция 1.4.1 нужна - отсутствует колонка: {$column}");
                return true;
            }
        }
        
        return false;
    }

    /**
     * Запуск миграций
     *
     * @return void
     */
    public function run_migrations() {
        if (!$this->needs_migration()) {
            return;
        }

        // Получение списка миграций
        $migrations = $this->get_migrations();

        // Выполнение миграций
        foreach ($migrations as $version => $migration) {
            if (version_compare($this->db_version, $version, '<')) {
                $this->run_migration($migration);
            }
        }

        // Обновление версии схемы базы данных
        update_option('cryptoschool_db_version', $this->plugin_version);
    }

    /**
     * Получение списка миграций
     *
     * @return array
     */
    private function get_migrations() {
        return [
            '1.0.1' => [$this, 'migration_1_0_1'],
            '1.0.2' => [$this, 'migration_1_0_2'],
            '1.0.3' => [$this, 'migration_1_0_3'],
            '1.0.4' => [$this, 'migration_1_0_4'],
            '1.0.5' => [$this, 'migration_1_0_5'], // Реферальная система
            '1.4.1' => [$this, 'migration_1_4_1'], // Поля для платежей с реферальными скидками
            // Добавьте здесь другие миграции
        ];
    }

    /**
     * Выполнение миграции
     *
     * @param callable $migration Функция миграции
     * @return void
     */
    private function run_migration($migration) {
        if (is_callable($migration)) {
            call_user_func($migration);
        }
    }

    /**
     * Миграция для версии 1.0.1
     *
     * @return void
     */
    public function migration_1_0_1() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Пример миграции: добавление поля в таблицу курсов
        $table_name = $wpdb->prefix . 'cryptoschool_courses';
        $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN featured tinyint(1) DEFAULT 0 AFTER is_active");

        // Логирование миграции
        $this->log_migration('1.0.1');
    }

    /**
     * Миграция для версии 1.0.2
     *
     * @return void
     */
    public function migration_1_0_2() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Пример миграции: добавление поля в таблицу пакетов
        $table_name = $wpdb->prefix . 'cryptoschool_packages';
        $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN discount_price decimal(10,2) DEFAULT NULL AFTER price");

        // Логирование миграции
        $this->log_migration('1.0.2');
    }

    /**
     * Миграция для версии 1.0.3
     *
     * @return void
     */
    public function migration_1_0_3() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Пример миграции: создание новой таблицы
        $table_name = $wpdb->prefix . 'cryptoschool_user_notes';
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            lesson_id bigint(20) UNSIGNED NOT NULL,
            note_text longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY lesson_id (lesson_id)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Логирование миграции
        $this->log_migration('1.0.3');
    }

    /**
     * Миграция для версии 1.0.4
     *
     * @return void
     */
    public function migration_1_0_4() {
        // Подключаем класс миграции для создания таблиц заданий уроков
        require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/migrations/class-cryptoschool-migration-lesson-tasks.php';
        
        // Создаем экземпляр класса миграции
        $migration = new CryptoSchool_Migration_Lesson_Tasks();
        
        // Запускаем миграцию
        $migration->run();
        
        // Логирование миграции
        $this->log_migration('1.0.4');
    }

    /**
     * Миграция для версии 1.0.5 - Реферальная система
     *
     * @return void
     */
    public function migration_1_0_5() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $this->log_migration('Начало миграции 1.0.5 - Реферальная система');

        // Обновление таблицы реферальных ссылок для поддержки множественных ссылок
        $referral_links_table = $wpdb->prefix . 'cryptoschool_referral_links';
        
        // Проверяем существование таблицы
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$referral_links_table}'") == $referral_links_table;
        if (!$table_exists) {
            $this->log_migration("Ошибка: таблица {$referral_links_table} не существует");
            return;
        }
        
        $this->log_migration("Добавление новых колонок в таблицу {$referral_links_table}");
        
        // Добавляем колонки по одной с проверкой
        $columns_to_add = [
            'link_name' => "VARCHAR(255) DEFAULT NULL COMMENT 'Название ссылки'",
            'link_description' => "TEXT DEFAULT NULL COMMENT 'Описание ссылки'",
            'clicks_count' => "INT DEFAULT 0 COMMENT 'Количество переходов по ссылке'",
            'conversions_count' => "INT DEFAULT 0 COMMENT 'Количество конверсий'",
            'total_earned' => "DECIMAL(10,2) DEFAULT 0 COMMENT 'Общая сумма заработка по ссылке'",
            'is_active' => "TINYINT(1) DEFAULT 1 COMMENT 'Активна ли ссылка'"
        ];
        
        foreach ($columns_to_add as $column_name => $column_definition) {
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$referral_links_table} LIKE '{$column_name}'");
            if (empty($column_exists)) {
                $result = $wpdb->query("ALTER TABLE {$referral_links_table} ADD COLUMN {$column_name} {$column_definition}");
                if ($result === false) {
                    $this->log_migration("Ошибка добавления колонки {$column_name}: " . $wpdb->last_error);
                } else {
                    $this->log_migration("Колонка {$column_name} добавлена успешно");
                }
            } else {
                $this->log_migration("Колонка {$column_name} уже существует");
            }
        }

        // Создание таблицы для двухуровневой иерархии рефералов
        $hierarchy_table = $wpdb->prefix . 'cryptoschool_referral_hierarchy';
        $hierarchy_exists = $wpdb->get_var("SHOW TABLES LIKE '{$hierarchy_table}'") == $hierarchy_table;
        
        if (!$hierarchy_exists) {
            $this->log_migration("Создание таблицы {$hierarchy_table}");
            
            $sql_hierarchy = "CREATE TABLE {$hierarchy_table} (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                level1_user_id BIGINT(20) UNSIGNED NOT NULL COMMENT 'ID пользователя 1-го уровня (прямой рефовод)',
                level2_user_id BIGINT(20) UNSIGNED NOT NULL COMMENT 'ID пользователя 2-го уровня (реферал рефовода)',
                referral_link_id BIGINT(20) UNSIGNED NOT NULL COMMENT 'ID реферальной ссылки',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата создания связи',
                PRIMARY KEY (id),
                UNIQUE KEY unique_hierarchy (level1_user_id, level2_user_id),
                KEY level1_user_id (level1_user_id),
                KEY level2_user_id (level2_user_id),
                KEY referral_link_id (referral_link_id)
            ) {$charset_collate} COMMENT='Двухуровневая иерархия рефералов';";

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            $result = dbDelta($sql_hierarchy);
            
            if (!empty($result)) {
                $this->log_migration("Таблица {$hierarchy_table} создана успешно");
            } else {
                $this->log_migration("Ошибка создания таблицы {$hierarchy_table}");
            }
        } else {
            $this->log_migration("Таблица {$hierarchy_table} уже существует");
        }

        // Обновление таблицы реферальных транзакций для поддержки двухуровневой системы
        $transactions_table = $wpdb->prefix . 'cryptoschool_referral_transactions';
        $transactions_exists = $wpdb->get_var("SHOW TABLES LIKE '{$transactions_table}'") == $transactions_table;
        
        if ($transactions_exists) {
            $this->log_migration("Обновление таблицы транзакций {$transactions_table}");
            
            $transaction_columns = [
                'referral_level' => "TINYINT DEFAULT 1 COMMENT 'Уровень реферала (1 или 2)'",
                'level1_commission' => "DECIMAL(10,2) DEFAULT 0 COMMENT 'Комиссия 1-го уровня'",
                'level2_commission' => "DECIMAL(10,2) DEFAULT 0 COMMENT 'Комиссия 2-го уровня'",
                'referral_link_id' => "BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'ID реферальной ссылки'"
            ];
            
            foreach ($transaction_columns as $column_name => $column_definition) {
                $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$transactions_table} LIKE '{$column_name}'");
                if (empty($column_exists)) {
                    $result = $wpdb->query("ALTER TABLE {$transactions_table} ADD COLUMN {$column_name} {$column_definition}");
                    if ($result === false) {
                        $this->log_migration("Ошибка добавления колонки {$column_name} в таблицу транзакций: " . $wpdb->last_error);
                    } else {
                        $this->log_migration("Колонка {$column_name} добавлена в таблицу транзакций");
                    }
                } else {
                    $this->log_migration("Колонка {$column_name} уже существует в таблице транзакций");
                }
            }
        } else {
            $this->log_migration("Таблица транзакций {$transactions_table} не существует - будет создана при первой транзакции");
        }

        // Создание индексов для оптимизации запросов
        $this->log_migration("Создание индексов для оптимизации");
        
        $indexes = [
            [
                'name' => 'idx_referral_links_user_active',
                'table' => $referral_links_table,
                'sql' => "CREATE INDEX idx_referral_links_user_active ON {$referral_links_table} (user_id, is_active)"
            ],
            [
                'name' => 'idx_referral_links_code_active',
                'table' => $referral_links_table,
                'sql' => "CREATE INDEX idx_referral_links_code_active ON {$referral_links_table} (referral_code, is_active)"
            ]
        ];
        
        if ($transactions_exists) {
            $indexes[] = [
                'name' => 'idx_referral_transactions_level',
                'table' => $transactions_table,
                'sql' => "CREATE INDEX idx_referral_transactions_level ON {$transactions_table} (referral_level)"
            ];
            $indexes[] = [
                'name' => 'idx_referral_transactions_link',
                'table' => $transactions_table,
                'sql' => "CREATE INDEX idx_referral_transactions_link ON {$transactions_table} (referral_link_id)"
            ];
        }
        
        foreach ($indexes as $index) {
            // Проверяем, существует ли индекс
            $existing_index = $wpdb->get_results("SHOW INDEX FROM {$index['table']} WHERE Key_name = '{$index['name']}'");
            
            if (empty($existing_index)) {
                $result = $wpdb->query($index['sql']);
                if ($result === false) {
                    $this->log_migration("Ошибка создания индекса {$index['name']}: " . $wpdb->last_error);
                } else {
                    $this->log_migration("Индекс {$index['name']} создан успешно");
                }
            } else {
                $this->log_migration("Индекс {$index['name']} уже существует");
            }
        }

        // Обновление существующих записей (если есть)
        $this->log_migration("Обновление существующих записей");
        
        $update_result = $wpdb->query("UPDATE {$referral_links_table} SET is_active = 1 WHERE is_active IS NULL");
        $this->log_migration("Обновлено записей в таблице ссылок: " . $update_result);
        
        if ($transactions_exists) {
            $update_result = $wpdb->query("UPDATE {$transactions_table} SET referral_level = 1 WHERE referral_level IS NULL");
            $this->log_migration("Обновлено записей в таблице транзакций: " . $update_result);
        }

        // Логирование завершения миграции
        $this->log_migration('Миграция 1.0.5 завершена успешно');
    }

    /**
     * Миграция для версии 1.4.1 - Поля для платежей с реферальными скидками
     *
     * @return void
     */
    public function migration_1_4_1() {
        global $wpdb;

        $this->log_migration('Начало миграции 1.4.1 - Поля для платежей с реферальными скидками');

        // Обновление таблицы платежей для поддержки реферальных скидок
        $payments_table = $wpdb->prefix . 'cryptoschool_payments';
        
        // Проверяем существование таблицы
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$payments_table}'") == $payments_table;
        if (!$table_exists) {
            $this->log_migration("Ошибка: таблица {$payments_table} не существует");
            return;
        }
        
        $this->log_migration("Добавление новых колонок в таблицу {$payments_table}");
        
        // Добавляем колонки для реферальной системы
        $columns_to_add = [
            'original_amount' => "DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Исходная цена до скидки'",
            'discount_percent' => "DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Процент скидки'",
            'discount_amount' => "DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Сумма скидки в деньгах'",
            'final_amount' => "DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Финальная цена после скидки'"
        ];
        
        foreach ($columns_to_add as $column_name => $column_definition) {
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$payments_table} LIKE '{$column_name}'");
            if (empty($column_exists)) {
                $result = $wpdb->query("ALTER TABLE {$payments_table} ADD COLUMN {$column_name} {$column_definition} AFTER amount");
                if ($result === false) {
                    $this->log_migration("Ошибка добавления колонки {$column_name}: " . $wpdb->last_error);
                } else {
                    $this->log_migration("Колонка {$column_name} добавлена успешно");
                }
            } else {
                $this->log_migration("Колонка {$column_name} уже существует");
            }
        }

        // Обновление существующих записей - заполняем новые поля на основе существующих данных
        $this->log_migration("Обновление существующих записей платежей");
        
        // Для существующих платежей устанавливаем original_amount = amount и final_amount = amount
        $update_sql = "UPDATE {$payments_table} 
                      SET original_amount = amount, 
                          final_amount = amount 
                      WHERE original_amount = 0 OR original_amount IS NULL";
        
        $update_result = $wpdb->query($update_sql);
        $this->log_migration("Обновлено записей в таблице платежей: " . $update_result);

        // Создание индексов для оптимизации запросов
        $this->log_migration("Создание индексов для оптимизации");
        
        $indexes = [
            [
                'name' => 'idx_payments_referral_discount',
                'table' => $payments_table,
                'sql' => "CREATE INDEX idx_payments_referral_discount ON {$payments_table} (referral_link_id, discount_percent)"
            ],
            [
                'name' => 'idx_payments_amounts',
                'table' => $payments_table,
                'sql' => "CREATE INDEX idx_payments_amounts ON {$payments_table} (original_amount, final_amount)"
            ]
        ];
        
        foreach ($indexes as $index) {
            // Проверяем, существует ли индекс
            $existing_index = $wpdb->get_results("SHOW INDEX FROM {$index['table']} WHERE Key_name = '{$index['name']}'");
            
            if (empty($existing_index)) {
                $result = $wpdb->query($index['sql']);
                if ($result === false) {
                    $this->log_migration("Ошибка создания индекса {$index['name']}: " . $wpdb->last_error);
                } else {
                    $this->log_migration("Индекс {$index['name']} создан успешно");
                }
            } else {
                $this->log_migration("Индекс {$index['name']} уже существует");
            }
        }

        // Логирование завершения миграции
        $this->log_migration('Миграция 1.4.1 завершена успешно');
    }

    /**
     * Логирование миграции
     *
     * @param string $version Версия миграции
     * @return void
     */
    private function log_migration($version) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('[CryptoSchool Migration] Выполнена миграция до версии %s', $version));
        }
    }
}
