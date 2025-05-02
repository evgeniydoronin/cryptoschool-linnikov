<?php
/**
 * Контроллер для управления доступами пользователей в административной части
 *
 * @package CryptoSchool
 * @subpackage Admin\Controllers
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Контроллер для управления доступами пользователей
 */
class CryptoSchool_Admin_UserAccesses_Controller extends CryptoSchool_Admin_Controller {

    /**
     * Сервис для работы с доступами пользователей
     *
     * @var CryptoSchool_Service_UserAccess
     */
    private $user_access_service;

    /**
     * Сервис для работы с пакетами
     *
     * @var CryptoSchool_Service_Package
     */
    private $package_service;

    /**
     * Конструктор класса
     *
     * @param CryptoSchool_Loader $loader Экземпляр загрузчика плагина
     */
    public function __construct($loader) {
        $this->user_access_service = new CryptoSchool_Service_UserAccess($loader);
        $this->package_service = new CryptoSchool_Service_Package($loader);
        
        parent::__construct($loader);
    }

    /**
     * Регистрация хуков
     */
    protected function register_hooks() {
        // Регистрация AJAX-обработчиков
        add_action('wp_ajax_cryptoschool_get_user_accesses', array($this, 'ajax_get_user_accesses'));
        add_action('wp_ajax_cryptoschool_get_user_access', array($this, 'ajax_get_user_access'));
        add_action('wp_ajax_cryptoschool_create_user_access', array($this, 'ajax_create_user_access'));
        add_action('wp_ajax_cryptoschool_update_user_access', array($this, 'ajax_update_user_access'));
        add_action('wp_ajax_cryptoschool_delete_user_access', array($this, 'ajax_delete_user_access'));
        add_action('wp_ajax_cryptoschool_update_telegram_status', array($this, 'ajax_update_telegram_status'));
    }

    /**
     * Отображение страницы доступов пользователей
     */
    public function display_user_accesses_page() {
        // Получение списка доступов пользователей
        $user_accesses = $this->user_access_service->get_all();

        // Получение списка пакетов для выбора
        $packages = $this->package_service->get_all(['is_active' => 1]);

        // Получение списка пользователей для выбора
        $users = get_users(['role__in' => ['subscriber', 'customer']]);

        // Отображение страницы
        $this->render_view('user-accesses', array(
            'user_accesses' => $user_accesses,
            'packages' => $packages,
            'users' => $users
        ));
    }

    /**
     * AJAX: Получение списка доступов пользователей
     */
    public function ajax_get_user_accesses() {
        // Проверка nonce
        if (!$this->verify_ajax_nonce()) {
            $this->send_ajax_error('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.');
            return;
        }

        // Получение параметров фильтрации
        $user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
        $package_id = isset($_POST['package_id']) ? (int) $_POST['package_id'] : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $telegram_status = isset($_POST['telegram_status']) ? sanitize_text_field($_POST['telegram_status']) : '';
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

        // Получение списка доступов пользователей
        $args = array();
        if ($user_id) {
            $args['user_id'] = $user_id;
        }
        if ($package_id) {
            $args['package_id'] = $package_id;
        }
        if (!empty($status)) {
            $args['status'] = $status;
        }
        if (!empty($telegram_status)) {
            $args['telegram_status'] = $telegram_status;
        }
        if (!empty($search)) {
            $args['search'] = $search;
        }
        $user_accesses = $this->user_access_service->get_all($args);

        // Подготовка данных для ответа
        $data = array();
        foreach ($user_accesses as $access) {
            $user = get_userdata($access->user_id);
            $package = $this->package_service->get_by_id($access->package_id);

            $data[] = array(
                'id' => $access->id,
                'user_id' => $access->user_id,
                'user_name' => $user ? $user->display_name : 'Неизвестный пользователь',
                'user_email' => $user ? $user->user_email : '',
                'package_id' => $access->package_id,
                'package_title' => $package ? $package->title : 'Неизвестный пакет',
                'access_start' => $access->access_start,
                'access_end' => $access->access_end,
                'status' => $access->status,
                'telegram_status' => $access->telegram_status
            );
        }

        // Отправка ответа
        $this->send_ajax_success($data);
    }

    /**
     * AJAX: Получение данных доступа пользователя
     */
    public function ajax_get_user_access() {
        // Проверка nonce
        if (!$this->verify_ajax_nonce()) {
            $this->send_ajax_error('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.');
            return;
        }

        // Получение ID доступа
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if (!$id) {
            $this->send_ajax_error('Не указан ID доступа.');
            return;
        }

        // Получение данных доступа
        $access = $this->user_access_service->get_by_id($id);

        if (!$access) {
            $this->send_ajax_error('Доступ не найден.');
            return;
        }

        // Получение данных пользователя и пакета
        $user = get_userdata($access->user_id);
        $package = $this->package_service->get_by_id($access->package_id);

        // Подготовка данных для ответа
        $access_data = array(
            'id' => $access->id,
            'user_id' => $access->user_id,
            'user_name' => $user ? $user->display_name : 'Неизвестный пользователь',
            'user_email' => $user ? $user->user_email : '',
            'package_id' => $access->package_id,
            'package_title' => $package ? $package->title : 'Неизвестный пакет',
            'access_start' => $access->access_start,
            'access_end' => $access->access_end,
            'status' => $access->status,
            'telegram_status' => $access->telegram_status
        );

        // Отправка ответа
        $this->send_ajax_success($access_data);
    }

    /**
     * AJAX: Создание доступа пользователя
     */
    public function ajax_create_user_access() {
        // Проверка nonce
        if (!$this->verify_ajax_nonce()) {
            $this->send_ajax_error('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.');
            return;
        }

        // Получение данных доступа
        $user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
        $package_id = isset($_POST['package_id']) ? (int) $_POST['package_id'] : 0;
        $access_start = isset($_POST['access_start']) ? sanitize_text_field($_POST['access_start']) : current_time('mysql');
        $duration_months = isset($_POST['duration_months']) ? (int) $_POST['duration_months'] : null;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'active';
        $telegram_status = isset($_POST['telegram_status']) ? sanitize_text_field($_POST['telegram_status']) : 'none';

        // Проверка обязательных полей
        if (!$user_id) {
            $this->send_ajax_error('Не указан ID пользователя.');
            return;
        }

        if (!$package_id) {
            $this->send_ajax_error('Не указан ID пакета.');
            return;
        }

        // Создание доступа
        $access_data = array(
            'user_id' => $user_id,
            'package_id' => $package_id,
            'access_start' => $access_start,
            'duration_months' => $duration_months,
            'status' => $status,
            'telegram_status' => $telegram_status
        );

        $access_id = $this->user_access_service->create($access_data);

        if (!$access_id) {
            $this->send_ajax_error('Не удалось создать доступ.');
            return;
        }

        // Отправка ответа
        $this->send_ajax_success(array(
            'id' => $access_id,
            'message' => 'Доступ успешно создан.',
        ));
    }

    /**
     * AJAX: Обновление доступа пользователя
     */
    public function ajax_update_user_access() {
        // Проверка nonce
        if (!$this->verify_ajax_nonce()) {
            $this->send_ajax_error('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.');
            return;
        }

        // Получение данных доступа
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
        $package_id = isset($_POST['package_id']) ? (int) $_POST['package_id'] : 0;
        $access_start = isset($_POST['access_start']) ? sanitize_text_field($_POST['access_start']) : '';
        $duration_months = isset($_POST['duration_months']) ? (int) $_POST['duration_months'] : null;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $telegram_status = isset($_POST['telegram_status']) ? sanitize_text_field($_POST['telegram_status']) : '';

        // Проверка обязательных полей
        if (!$id) {
            $this->send_ajax_error('Не указан ID доступа.');
            return;
        }

        // Обновление доступа
        $access_data = array();
        if ($user_id) {
            $access_data['user_id'] = $user_id;
        }
        if ($package_id) {
            $access_data['package_id'] = $package_id;
        }
        if (!empty($access_start)) {
            $access_data['access_start'] = $access_start;
        }
        if ($duration_months !== null) {
            $access_data['duration_months'] = $duration_months;
        }
        if (!empty($status)) {
            $access_data['status'] = $status;
        }
        if (!empty($telegram_status)) {
            $access_data['telegram_status'] = $telegram_status;
        }

        $result = $this->user_access_service->update($id, $access_data);

        if (!$result) {
            $this->send_ajax_error('Не удалось обновить доступ.');
            return;
        }

        // Отправка ответа
        $this->send_ajax_success(array(
            'message' => 'Доступ успешно обновлен.',
        ));
    }

    /**
     * AJAX: Удаление доступа пользователя
     */
    public function ajax_delete_user_access() {
        // Проверка nonce
        if (!$this->verify_ajax_nonce()) {
            $this->send_ajax_error('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.');
            return;
        }

        // Получение ID доступа
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if (!$id) {
            $this->send_ajax_error('Не указан ID доступа.');
            return;
        }

        // Удаление доступа
        $result = $this->user_access_service->delete($id);

        if (!$result) {
            $this->send_ajax_error('Не удалось удалить доступ.');
            return;
        }

        // Отправка ответа
        $this->send_ajax_success(array(
            'message' => 'Доступ успешно удален.',
        ));
    }

    /**
     * AJAX: Обновление статуса в Telegram
     */
    public function ajax_update_telegram_status() {
        // Проверка nonce
        if (!$this->verify_ajax_nonce()) {
            $this->send_ajax_error('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.');
            return;
        }

        // Получение ID доступа и нового статуса
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $telegram_status = isset($_POST['telegram_status']) ? sanitize_text_field($_POST['telegram_status']) : '';

        if (!$id) {
            $this->send_ajax_error('Не указан ID доступа.');
            return;
        }

        if (empty($telegram_status)) {
            $this->send_ajax_error('Не указан статус в Telegram.');
            return;
        }

        // Обновление статуса в Telegram
        $result = $this->user_access_service->update_telegram_status($id, $telegram_status);

        if (!$result) {
            $this->send_ajax_error('Не удалось обновить статус в Telegram.');
            return;
        }

        // Получение обновленного доступа
        $access = $this->user_access_service->get_by_id($id);

        // Если статус изменен на "invited", отправляем запрос на добавление пользователя
        if ($telegram_status === 'invited') {
            do_action('cryptoschool_telegram_invite_user', $access->user_id, $access->package_id);
        }

        // Если статус изменен на "removed", отправляем запрос на удаление пользователя
        if ($telegram_status === 'removed') {
            do_action('cryptoschool_telegram_remove_user', $access->user_id, $access->package_id);
        }

        // Отправка ответа
        $this->send_ajax_success(array(
            'message' => 'Статус в Telegram успешно обновлен.',
        ));
    }
}
