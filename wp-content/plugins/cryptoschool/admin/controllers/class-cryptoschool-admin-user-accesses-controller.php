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
 * Класс контроллера для управления доступами пользователей
 */
class CryptoSchool_Admin_UserAccesses_Controller {
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
     * @param CryptoSchool_Loader $loader Экземпляр загрузчика
     */
    public function __construct($loader) {
        // Инициализация сервисов
        $this->user_access_service = new CryptoSchool_Service_UserAccess($loader);
        $this->package_service = new CryptoSchool_Service_Package($loader);

        // Регистрация AJAX-обработчиков
        add_action('wp_ajax_cryptoschool_get_user_accesses', array($this, 'ajax_get_user_accesses'));
        add_action('wp_ajax_cryptoschool_get_user_access', array($this, 'ajax_get_user_access'));
        add_action('wp_ajax_cryptoschool_create_user_access', array($this, 'ajax_create_user_access'));
        add_action('wp_ajax_cryptoschool_update_user_access', array($this, 'ajax_update_user_access'));
        add_action('wp_ajax_cryptoschool_delete_user_access', array($this, 'ajax_delete_user_access'));
        add_action('wp_ajax_cryptoschool_update_telegram_status', array($this, 'ajax_update_telegram_status'));
    }

    /**
     * Отображение страницы управления доступами пользователей
     */
    public function display_user_accesses_page() {
        // Получение доступов пользователей
        $user_accesses = $this->user_access_service->get_all();

        // Получение пакетов для выбора
        $packages = $this->package_service->get_all(['is_active' => 1]);

        // Получение пользователей для выбора
        $users = get_users(['role__in' => ['subscriber', 'customer', 'cryptoschool_student']]);

        // Подключение шаблона
        require_once plugin_dir_path(dirname(__FILE__)) . 'views/user-accesses.php';
    }

    /**
     * Проверка AJAX nonce
     *
     * @return bool
     */
    private function verify_ajax_nonce() {
        return isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'cryptoschool_admin_nonce');
    }

    /**
     * Отправка успешного AJAX-ответа
     *
     * @param mixed $data Данные для ответа
     * @return void
     */
    private function send_ajax_success($data) {
        wp_send_json_success($data);
    }

    /**
     * Отправка AJAX-ответа с ошибкой
     *
     * @param string $message Сообщение об ошибке
     * @return void
     */
    private function send_ajax_error($message) {
        wp_send_json_error($message);
    }

    /**
     * AJAX: Получение списка доступов пользователей
     *
     * @return void
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
     *
     * @return void
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
     * AJAX-обработчик для создания доступа пользователя
     *
     * @return void
     */
    public function ajax_create_user_access() {
        // Детальное логирование
        error_log('CryptoSchool_Admin_UserAccesses_Controller::ajax_create_user_access - Начало обработки запроса');
        error_log('CryptoSchool_Admin_UserAccesses_Controller::ajax_create_user_access - POST данные: ' . json_encode($_POST));
        
        // Проверка nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cryptoschool_admin_nonce')) {
            error_log('CryptoSchool_Admin_UserAccesses_Controller::ajax_create_user_access - Ошибка проверки nonce');
            $this->send_ajax_error(__('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.', 'cryptoschool'));
            return;
        }

        // Проверка прав доступа
        if (!current_user_can('manage_options')) {
            error_log('CryptoSchool_Admin_UserAccesses_Controller::ajax_create_user_access - Недостаточно прав доступа');
            $this->send_ajax_error(__('У вас нет прав для выполнения этого действия.', 'cryptoschool'));
            return;
        }

        // Получение данных из запроса
        $user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
        $package_id = isset($_POST['package_id']) ? (int) $_POST['package_id'] : 0;
        $access_start = isset($_POST['access_start']) ? sanitize_text_field($_POST['access_start']) : current_time('mysql');
        $duration_months = isset($_POST['duration_months']) ? (int) $_POST['duration_months'] : null;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'active';
        $telegram_status = isset($_POST['telegram_status']) ? sanitize_text_field($_POST['telegram_status']) : 'none';

        error_log('CryptoSchool_Admin_UserAccesses_Controller::ajax_create_user_access - Обработанные данные:');
        error_log('CryptoSchool_Admin_UserAccesses_Controller::ajax_create_user_access - user_id: ' . $user_id);
        error_log('CryptoSchool_Admin_UserAccesses_Controller::ajax_create_user_access - package_id: ' . $package_id);
        error_log('CryptoSchool_Admin_UserAccesses_Controller::ajax_create_user_access - access_start: ' . $access_start);
        error_log('CryptoSchool_Admin_UserAccesses_Controller::ajax_create_user_access - duration_months: ' . $duration_months);
        error_log('CryptoSchool_Admin_UserAccesses_Controller::ajax_create_user_access - status: ' . $status);
        error_log('CryptoSchool_Admin_UserAccesses_Controller::ajax_create_user_access - telegram_status: ' . $telegram_status);

        // Проверка обязательных полей
        if ($user_id <= 0) {
            error_log('CryptoSchool_Admin_UserAccesses_Controller::ajax_create_user_access - Некорректный ID пользователя: ' . $user_id);
            $this->send_ajax_error(__('Некорректный ID пользователя.', 'cryptoschool'));
            return;
        }

        if ($package_id <= 0) {
            error_log('CryptoSchool_Admin_UserAccesses_Controller::ajax_create_user_access - Некорректный ID пакета: ' . $package_id);
            $this->send_ajax_error(__('Некорректный ID пакета.', 'cryptoschool'));
            return;
        }

        // Проверка существования пользователя
        $user = get_userdata($user_id);
        if (!$user) {
            error_log('CryptoSchool_Admin_UserAccesses_Controller::ajax_create_user_access - Пользователь с ID ' . $user_id . ' не найден');
            $this->send_ajax_error(__('Пользователь не найден.', 'cryptoschool'));
            return;
        }
        error_log('CryptoSchool_Admin_UserAccesses_Controller::ajax_create_user_access - Пользователь найден: ' . $user->display_name . ' (' . $user->user_email . ')');

        // Проверка существования пакета
        $package = $this->package_service->get_by_id($package_id);
        if (!$package) {
            error_log('CryptoSchool_Admin_UserAccesses_Controller::ajax_create_user_access - Пакет с ID ' . $package_id . ' не найден');
            $this->send_ajax_error(__('Пакет не найден.', 'cryptoschool'));
            return;
        }
        error_log('CryptoSchool_Admin_UserAccesses_Controller::ajax_create_user_access - Пакет найден: ' . $package->title);

        // Проверка, есть ли уже доступ к этому пакету
        $existing_access = $this->user_access_service->get_user_package_access($user_id, $package_id);
        if ($existing_access) {
            error_log('CryptoSchool_Admin_UserAccesses_Controller::ajax_create_user_access - У пользователя уже есть доступ к пакету: ' . json_encode($existing_access));
            
            // Если доступ уже есть, но истек, можно его обновить
            if ($existing_access->status === 'expired') {
                error_log('CryptoSchool_Admin_UserAccesses_Controller::ajax_create_user_access - Доступ истек, обновляем его');
                
                $update_data = [
                    'status' => 'active'
                ];
                
                // Если указана продолжительность в месяцах, рассчитываем дату окончания
                if (!empty($duration_months)) {
                    $start_date = new DateTime($access_start);
                    $start_date->modify("+{$duration_months} months");
                    $update_data['access_end'] = $start_date->format('Y-m-d H:i:s');
                    $update_data['access_start'] = $access_start;
                } else {
                    $update_data['access_end'] = null; // Пожизненный доступ
                }
                
                error_log('CryptoSchool_Admin_UserAccesses_Controller::ajax_create_user_access - Данные для обновления: ' . json_encode($update_data));
                
                // Обновляем существующий доступ
                $result = $this->user_access_service->update($existing_access->id, $update_data);
                
                if ($result) {
                    error_log('CryptoSchool_Admin_UserAccesses_Controller::ajax_create_user_access - Доступ успешно обновлен');
                    
                    // Получение обновленного доступа
                    $access = $this->user_access_service->get_by_id($existing_access->id);
                    
                    // Подготовка данных для ответа
                    $data = [
                        'id' => $access->id,
                        'user_id' => $access->user_id,
                        'user_name' => $user->display_name,
                        'user_email' => $user->user_email,
                        'package_id' => $access->package_id,
                        'package_title' => $package->title,
                        'access_start' => $access->access_start,
                        'access_end' => $access->access_end,
                        'status' => $access->status,
                        'telegram_status' => $access->telegram_status,
                    ];
                    
                    $this->send_ajax_success($data);
                    return;
                } else {
                    error_log('CryptoSchool_Admin_UserAccesses_Controller::ajax_create_user_access - Не удалось обновить доступ');
                }
            }
            
            $this->send_ajax_error(__('У пользователя уже есть доступ к этому пакету.', 'cryptoschool'));
            return;
        }

        // Создание доступа
        $access_data = [
            'user_id' => $user_id,
            'package_id' => $package_id,
            'access_start' => $access_start,
            'duration_months' => $duration_months,
            'status' => $status,
            'telegram_status' => $telegram_status,
        ];

        error_log('CryptoSchool_Admin_UserAccesses_Controller::ajax_create_user_access - Данные для создания доступа: ' . json_encode($access_data));
        
        // Проверка таблицы
        global $wpdb;
        $table_name = $wpdb->prefix . 'cryptoschool_user_access';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
        
        if (!$table_exists) {
            error_log('CryptoSchool_Admin_UserAccesses_Controller::ajax_create_user_access - Таблица ' . $table_name . ' не существует!');
            
            // Создание таблицы
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id bigint(20) UNSIGNED NOT NULL,
                package_id bigint(20) UNSIGNED NOT NULL,
                access_start datetime NOT NULL,
                access_end datetime DEFAULT NULL,
                status enum('active', 'expired') DEFAULT 'active',
                telegram_status enum('none', 'invited', 'active', 'removed') DEFAULT 'none',
                telegram_invite_link varchar(255) DEFAULT NULL,
                telegram_invite_date datetime DEFAULT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY user_id (user_id),
                KEY package_id (package_id),
                KEY status (status)
            ) $charset_collate;";
            
            dbDelta($sql);
            
            // Проверка, создалась ли таблица
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
            if (!$table_exists) {
                error_log('CryptoSchool_Admin_UserAccesses_Controller::ajax_create_user_access - Не удалось создать таблицу ' . $table_name);
                $this->send_ajax_error(__('Не удалось создать таблицу для доступов пользователей.', 'cryptoschool'));
                return;
            }
            
            error_log('CryptoSchool_Admin_UserAccesses_Controller::ajax_create_user_access - Таблица ' . $table_name . ' успешно создана');
        } else {
            error_log('CryptoSchool_Admin_UserAccesses_Controller::ajax_create_user_access - Таблица ' . $table_name . ' существует');
        }

        $access_id = $this->user_access_service->create($access_data);

        if (!$access_id) {
            error_log('CryptoSchool_Admin_UserAccesses_Controller::ajax_create_user_access - Не удалось создать доступ');
            $this->send_ajax_error(__('Не удалось создать доступ. Возможно, у пользователя уже есть доступ к этому пакету.', 'cryptoschool'));
            return;
        }

        error_log('CryptoSchool_Admin_UserAccesses_Controller::ajax_create_user_access - Доступ успешно создан с ID: ' . $access_id);

        // Получение созданного доступа
        $access = $this->user_access_service->get_by_id($access_id);
        if (!$access) {
            error_log('CryptoSchool_Admin_UserAccesses_Controller::ajax_create_user_access - Не удалось получить созданный доступ с ID: ' . $access_id);
            $this->send_ajax_error(__('Доступ создан, но не удалось получить его данные.', 'cryptoschool'));
            return;
        }

        error_log('CryptoSchool_Admin_UserAccesses_Controller::ajax_create_user_access - Получен доступ: ' . json_encode($access));

        // Подготовка данных для ответа
        $data = [
            'id' => $access->id,
            'user_id' => $access->user_id,
            'user_name' => $user->display_name,
            'user_email' => $user->user_email,
            'package_id' => $access->package_id,
            'package_title' => $package->title,
            'access_start' => $access->access_start,
            'access_end' => $access->access_end,
            'status' => $access->status,
            'telegram_status' => $access->telegram_status,
        ];

        error_log('CryptoSchool_Admin_UserAccesses_Controller::ajax_create_user_access - Отправка успешного ответа: ' . json_encode($data));
        $this->send_ajax_success($data);
    }

    /**
     * AJAX: Обновление доступа пользователя
     *
     * @return void
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
        
        // Преобразование формата даты из YYYY-MM-DDTHH:MM в YYYY-MM-DD HH:MM:SS
        $access_start = isset($_POST['access_start']) ? sanitize_text_field($_POST['access_start']) : '';
        if ($access_start && strpos($access_start, 'T') !== false) {
            $access_start = str_replace('T', ' ', $access_start) . ':00';
        }
        
        $duration_months = isset($_POST['duration_months']) ? (int) $_POST['duration_months'] : null;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $telegram_status = isset($_POST['telegram_status']) ? sanitize_text_field($_POST['telegram_status']) : '';

        // Проверка обязательных полей
        if (!$id) {
            $this->send_ajax_error('Не указан ID доступа.');
            return;
        }

        // Проверка существования доступа
        $access = $this->user_access_service->get_by_id($id);
        if (!$access) {
            $this->send_ajax_error('Доступ с ID ' . $id . ' не найден.');
            return;
        }

        // Проверка существования пользователя, если ID пользователя изменен
        if ($user_id && $user_id != $access->user_id) {
            $user = get_userdata($user_id);
            if (!$user) {
                $this->send_ajax_error('Пользователь с ID ' . $user_id . ' не найден.');
                return;
            }
        }

        // Проверка существования пакета, если ID пакета изменен
        if ($package_id && $package_id != $access->package_id) {
            $package = $this->package_service->get_by_id($package_id);
            if (!$package) {
                $this->send_ajax_error('Пакет с ID ' . $package_id . ' не найден.');
                return;
            }
        }

        // Проверка формата даты
        if (!empty($access_start) && !strtotime($access_start)) {
            $this->send_ajax_error('Некорректный формат даты начала доступа: ' . $access_start);
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

        try {
            $result = $this->user_access_service->update($id, $access_data);

            if (!$result) {
                $this->send_ajax_error('Не удалось обновить доступ. Возможно, у пользователя уже есть доступ к этому пакету.');
                return;
            }

            // Отправка ответа
            $this->send_ajax_success(array(
                'message' => 'Доступ успешно обновлен.',
            ));
        } catch (Exception $e) {
            $this->send_ajax_error('Ошибка при обновлении доступа: ' . $e->getMessage());
        }
    }

    /**
     * AJAX: Удаление доступа пользователя
     *
     * @return void
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

        // Проверка существования доступа
        $access = $this->user_access_service->get_by_id($id);
        if (!$access) {
            $this->send_ajax_error('Доступ с ID ' . $id . ' не найден.');
            return;
        }

        try {
            // Удаление доступа
            $result = $this->user_access_service->delete($id);

            if (!$result) {
                $this->send_ajax_error('Не удалось удалить доступ. Возможно, он используется в других частях системы.');
                return;
            }

            // Отправка ответа
            $this->send_ajax_success(array(
                'message' => 'Доступ успешно удален.',
            ));
        } catch (Exception $e) {
            $this->send_ajax_error('Ошибка при удалении доступа: ' . $e->getMessage());
        }
    }

    /**
     * AJAX: Обновление статуса в Telegram
     *
     * @return void
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

        // Проверка существования доступа
        $access = $this->user_access_service->get_by_id($id);
        if (!$access) {
            $this->send_ajax_error('Доступ с ID ' . $id . ' не найден.');
            return;
        }

        // Проверка корректности статуса
        $valid_statuses = array('none', 'invited', 'active', 'removed');
        if (!in_array($telegram_status, $valid_statuses)) {
            $this->send_ajax_error('Некорректный статус в Telegram: ' . $telegram_status . '. Допустимые значения: ' . implode(', ', $valid_statuses));
            return;
        }

        try {
            // Обновление статуса в Telegram
            $result = $this->user_access_service->update_telegram_status($id, $telegram_status);

            if (!$result) {
                $this->send_ajax_error('Не удалось обновить статус в Telegram. Возможно, доступ уже имеет этот статус.');
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
                'message' => 'Статус в Telegram успешно обновлен на "' . $telegram_status . '".',
            ));
        } catch (Exception $e) {
            $this->send_ajax_error('Ошибка при обновлении статуса в Telegram: ' . $e->getMessage());
        }
    }
}
