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
        return version_compare($this->db_version, $this->plugin_version, '<');
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
