<?php
/**
 * Миграция для создания таблиц заданий уроков
 *
 * @package CryptoSchool
 * @subpackage Migrations
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс миграции для создания таблиц заданий уроков
 */
class CryptoSchool_Migration_Lesson_Tasks {
    /**
     * Версия миграции
     *
     * @var string
     */
    private $version = '1.0.0';

    /**
     * Запуск миграции
     *
     * @return bool
     */
    public function run() {
        global $wpdb;

        // Логирование начала миграции
        $logger = CryptoSchool_Logger::get_instance();
        $logger->info('Запуск миграции для создания таблиц заданий уроков', ['version' => $this->version]);

        // Получение SQL-запросов из файла
        $sql_file = CRYPTOSCHOOL_PLUGIN_DIR . 'add_lesson_tasks_tables.sql';
        if (!file_exists($sql_file)) {
            $logger->error('Файл SQL-запросов не найден', ['file' => $sql_file]);
            return false;
        }

        $sql = file_get_contents($sql_file);
        if (empty($sql)) {
            $logger->error('Файл SQL-запросов пуст', ['file' => $sql_file]);
            return false;
        }

        // Замена префикса таблиц
        $sql = str_replace('wp_', $wpdb->prefix, $sql);

        // Разделение SQL-запросов
        $queries = explode(';', $sql);

        // Выполнение SQL-запросов
        $success = true;
        foreach ($queries as $query) {
            $query = trim($query);
            if (empty($query)) {
                continue;
            }

            $result = $wpdb->query($query);
            if ($result === false) {
                $logger->error('Ошибка выполнения SQL-запроса', [
                    'query' => $query,
                    'error' => $wpdb->last_error
                ]);
                $success = false;
            }
        }

        // Обновление версии миграции
        if ($success) {
            update_option('cryptoschool_migration_lesson_tasks_version', $this->version);
            $logger->info('Миграция для создания таблиц заданий уроков успешно выполнена', ['version' => $this->version]);
        } else {
            $logger->error('Миграция для создания таблиц заданий уроков завершилась с ошибками', ['version' => $this->version]);
        }

        return $success;
    }

    /**
     * Проверка необходимости выполнения миграции
     *
     * @return bool
     */
    public function needs_migration() {
        $current_version = get_option('cryptoschool_migration_lesson_tasks_version', '0.0.0');
        return version_compare($current_version, $this->version, '<');
    }
}
