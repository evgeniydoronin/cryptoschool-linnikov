<?php
/**
 * Класс логирования
 *
 * Отвечает за логирование ошибок и информационных сообщений
 *
 * @package CryptoSchool
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс логирования
 */
class CryptoSchool_Logger {
    /**
     * Экземпляр класса (Singleton)
     *
     * @var CryptoSchool_Logger
     */
    private static $instance = null;

    /**
     * Путь к файлу лога
     *
     * @var string
     */
    private $log_file;

    /**
     * Максимальный размер файла лога в байтах (10 МБ)
     *
     * @var int
     */
    private $max_log_size = 10485760;

    /**
     * Уровни логирования
     *
     * @var array
     */
    private $log_levels = [
        'error'   => 1,
        'warning' => 2,
        'info'    => 3,
        'debug'   => 4,
    ];

    /**
     * Текущий уровень логирования
     *
     * @var string
     */
    private $current_level = 'info';

    /**
     * Получение экземпляра класса
     *
     * @return CryptoSchool_Logger
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Конструктор класса
     */
    private function __construct() {
        $upload_dir = wp_upload_dir();
        $this->log_file = $upload_dir['basedir'] . '/cryptoschool-logs/cryptoschool.log';

        // Создание директории для логов, если она не существует
        $log_dir = dirname($this->log_file);
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }

        // Установка уровня логирования из настроек
        $this->current_level = get_option('cryptoschool_log_level', 'info');
    }

    /**
     * Логирование ошибки
     *
     * @param string $message Сообщение об ошибке
     * @param array  $context Контекст ошибки
     * @return void
     */
    public function error($message, $context = []) {
        $this->log('error', $message, $context);
    }

    /**
     * Логирование предупреждения
     *
     * @param string $message Предупреждающее сообщение
     * @param array  $context Контекст предупреждения
     * @return void
     */
    public function warning($message, $context = []) {
        $this->log('warning', $message, $context);
    }

    /**
     * Логирование информационного сообщения
     *
     * @param string $message Информационное сообщение
     * @param array  $context Контекст сообщения
     * @return void
     */
    public function info($message, $context = []) {
        $this->log('info', $message, $context);
    }

    /**
     * Логирование отладочного сообщения
     *
     * @param string $message Отладочное сообщение
     * @param array  $context Контекст сообщения
     * @return void
     */
    public function debug($message, $context = []) {
        $this->log('debug', $message, $context);
    }

    /**
     * Логирование сообщения
     *
     * @param string $level   Уровень логирования
     * @param string $message Сообщение
     * @param array  $context Контекст сообщения
     * @return void
     */
    private function log($level, $message, $context = []) {
        // Проверка уровня логирования
        if (!$this->should_log($level)) {
            return;
        }

        // Проверка размера файла лога
        $this->rotate_log_if_needed();

        // Форматирование сообщения
        $log_message = $this->format_message($level, $message, $context);

        // Запись в файл лога
        $this->write_to_log($log_message);

        // Дублирование в error_log, если включен режим отладки
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log($log_message);
        }
    }

    /**
     * Проверка, нужно ли логировать сообщение с указанным уровнем
     *
     * @param string $level Уровень логирования
     * @return bool
     */
    private function should_log($level) {
        return $this->log_levels[$level] <= $this->log_levels[$this->current_level];
    }

    /**
     * Ротация файла лога, если его размер превышает максимальный
     *
     * @return void
     */
    private function rotate_log_if_needed() {
        if (!file_exists($this->log_file)) {
            return;
        }

        $file_size = filesize($this->log_file);
        if ($file_size >= $this->max_log_size) {
            $backup_file = $this->log_file . '.' . date('Y-m-d-H-i-s') . '.bak';
            rename($this->log_file, $backup_file);
        }
    }

    /**
     * Форматирование сообщения для лога
     *
     * @param string $level   Уровень логирования
     * @param string $message Сообщение
     * @param array  $context Контекст сообщения
     * @return string
     */
    private function format_message($level, $message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $level_upper = strtoupper($level);
        $context_json = !empty($context) ? ' ' . json_encode($context) : '';
        return "[{$timestamp}] [{$level_upper}] {$message}{$context_json}" . PHP_EOL;
    }

    /**
     * Запись сообщения в файл лога
     *
     * @param string $message Сообщение для записи
     * @return void
     */
    private function write_to_log($message) {
        file_put_contents($this->log_file, $message, FILE_APPEND);
    }

    /**
     * Установка уровня логирования
     *
     * @param string $level Уровень логирования
     * @return void
     */
    public function set_level($level) {
        if (array_key_exists($level, $this->log_levels)) {
            $this->current_level = $level;
            update_option('cryptoschool_log_level', $level);
        }
    }

    /**
     * Получение текущего уровня логирования
     *
     * @return string
     */
    public function get_level() {
        return $this->current_level;
    }

    /**
     * Очистка файла лога
     *
     * @return void
     */
    public function clear_log() {
        if (file_exists($this->log_file)) {
            file_put_contents($this->log_file, '');
        }
    }

    /**
     * Получение содержимого файла лога
     *
     * @param int $lines Количество строк для получения (0 - все строки)
     * @return string
     */
    public function get_log_content($lines = 0) {
        if (!file_exists($this->log_file)) {
            return '';
        }

        $content = file_get_contents($this->log_file);
        if ($lines <= 0) {
            return $content;
        }

        $log_lines = explode(PHP_EOL, $content);
        $log_lines = array_filter($log_lines);
        $log_lines = array_slice($log_lines, -$lines);
        return implode(PHP_EOL, $log_lines);
    }
}
