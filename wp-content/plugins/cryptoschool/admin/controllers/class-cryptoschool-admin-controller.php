<?php
/**
 * Абстрактный базовый класс для контроллеров административной части
 *
 * @package CryptoSchool
 * @subpackage Admin\Controllers
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Абстрактный базовый класс для контроллеров административной части
 */
abstract class CryptoSchool_Admin_Controller {

    /**
     * Экземпляр загрузчика плагина
     *
     * @var CryptoSchool_Loader
     */
    protected $loader;

    /**
     * Конструктор класса
     *
     * @param CryptoSchool_Loader $loader Экземпляр загрузчика плагина
     */
    public function __construct($loader) {
        $this->loader = $loader;

        // Регистрация хуков
        $this->register_hooks();
    }

    /**
     * Регистрация хуков
     * 
     * Этот метод должен быть переопределен в дочерних классах
     */
    abstract protected function register_hooks();

    /**
     * Проверка nonce для AJAX-запросов
     *
     * @param string $nonce_key Ключ nonce в запросе
     * @return bool Результат проверки
     */
    protected function verify_ajax_nonce($nonce_key = 'nonce') {
        return check_ajax_referer('cryptoschool_admin_nonce', $nonce_key, false);
    }

    /**
     * Отправка ошибки в ответ на AJAX-запрос
     *
     * @param string $message Сообщение об ошибке
     */
    protected function send_ajax_error($message) {
        wp_send_json_error(__($message, 'cryptoschool'));
    }

    /**
     * Отправка успешного ответа на AJAX-запрос
     *
     * @param mixed $data Данные для отправки
     */
    protected function send_ajax_success($data) {
        wp_send_json_success($data);
    }

    /**
     * Получение страницы для отображения
     *
     * @param string $view_name Имя файла представления (без расширения)
     * @param array $data Данные для передачи в представление
     */
    protected function render_view($view_name, $data = array()) {
        // Извлечение переменных из массива для использования в представлении
        extract($data);

        // Подключение шаблона
        require_once plugin_dir_path(dirname(__FILE__)) . 'views/' . $view_name . '.php';
    }
}
