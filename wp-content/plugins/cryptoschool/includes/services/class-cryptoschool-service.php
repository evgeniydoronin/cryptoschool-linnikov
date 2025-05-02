<?php
/**
 * Базовый класс сервиса
 *
 * Предоставляет базовую функциональность для всех сервисов плагина
 *
 * @package CryptoSchool
 * @subpackage Services
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Базовый класс сервиса
 */
abstract class CryptoSchool_Service {
    /**
     * Экземпляр загрузчика
     *
     * @var CryptoSchool_Loader
     */
    protected $loader;

    /**
     * Конструктор класса
     *
     * @param CryptoSchool_Loader $loader Экземпляр загрузчика
     */
    public function __construct(CryptoSchool_Loader $loader) {
        $this->loader = $loader;
        $this->register_hooks();
    }

    /**
     * Регистрация хуков и фильтров
     *
     * @return void
     */
    abstract protected function register_hooks();

    /**
     * Добавление действия WordPress
     *
     * @param string $hook          Имя хука WordPress
     * @param string $callback      Имя метода в текущем классе
     * @param int    $priority      Приоритет
     * @param int    $accepted_args Количество аргументов
     * @return void
     */
    protected function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
        $this->loader->add_action($hook, $this, $callback, $priority, $accepted_args);
    }

    /**
     * Добавление фильтра WordPress
     *
     * @param string $hook          Имя хука WordPress
     * @param string $callback      Имя метода в текущем классе
     * @param int    $priority      Приоритет
     * @param int    $accepted_args Количество аргументов
     * @return void
     */
    protected function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
        $this->loader->add_filter($hook, $this, $callback, $priority, $accepted_args);
    }

    /**
     * Добавление шорткода WordPress
     *
     * @param string $tag      Тег шорткода
     * @param string $callback Имя метода в текущем классе
     * @return void
     */
    protected function add_shortcode($tag, $callback) {
        $this->loader->add_shortcode($tag, $this, $callback);
    }

    /**
     * Получение значения опции
     *
     * @param string $option_name Имя опции
     * @param mixed  $default     Значение по умолчанию
     * @return mixed
     */
    protected function get_option($option_name, $default = false) {
        return get_option($option_name, $default);
    }

    /**
     * Обновление значения опции
     *
     * @param string $option_name Имя опции
     * @param mixed  $value       Новое значение
     * @return bool
     */
    protected function update_option($option_name, $value) {
        return update_option($option_name, $value);
    }

    /**
     * Удаление опции
     *
     * @param string $option_name Имя опции
     * @return bool
     */
    protected function delete_option($option_name) {
        return delete_option($option_name);
    }

    /**
     * Логирование ошибки
     *
     * @param string $message Сообщение об ошибке
     * @param array  $context Контекст ошибки
     * @return void
     */
    protected function log_error($message, $context = []) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('[CryptoSchool Error] %s: %s', $message, json_encode($context)));
        }
    }

    /**
     * Логирование информации
     *
     * @param string $message Информационное сообщение
     * @param array  $context Контекст сообщения
     * @return void
     */
    protected function log_info($message, $context = []) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('[CryptoSchool Info] %s: %s', $message, json_encode($context)));
        }
    }

    /**
     * Проверка, является ли текущий запрос AJAX-запросом
     *
     * @return bool
     */
    protected function is_ajax() {
        return defined('DOING_AJAX') && DOING_AJAX;
    }

    /**
     * Проверка, является ли текущий запрос запросом REST API
     *
     * @return bool
     */
    protected function is_rest() {
        return defined('REST_REQUEST') && REST_REQUEST;
    }

    /**
     * Отправка JSON-ответа для AJAX-запроса
     *
     * @param mixed $data    Данные для ответа
     * @param bool  $success Успешен ли запрос
     * @return void
     */
    protected function send_json_response($data, $success = true) {
        $response = [
            'success' => $success,
            'data'    => $data,
        ];

        wp_send_json($response);
    }

    /**
     * Отправка успешного JSON-ответа для AJAX-запроса
     *
     * @param mixed $data Данные для ответа
     * @return void
     */
    protected function send_json_success($data = null) {
        $this->send_json_response($data, true);
    }

    /**
     * Отправка ошибочного JSON-ответа для AJAX-запроса
     *
     * @param mixed $data Данные для ответа
     * @return void
     */
    protected function send_json_error($data = null) {
        $this->send_json_response($data, false);
    }
}
