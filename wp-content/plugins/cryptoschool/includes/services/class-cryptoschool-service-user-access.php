<?php
/**
 * Сервис для работы с доступами пользователей
 *
 * @package CryptoSchool
 * @subpackage Services
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс сервиса для работы с доступами пользователей
 */
class CryptoSchool_Service_UserAccess extends CryptoSchool_Service {
    /**
     * Репозиторий доступов пользователей
     *
     * @var CryptoSchool_Repository_UserAccess
     */
    protected $repository;

    /**
     * Конструктор
     *
     * @param CryptoSchool_Loader $loader Экземпляр загрузчика
     */
    public function __construct(CryptoSchool_Loader $loader) {
        parent::__construct($loader);
        $this->repository = new CryptoSchool_Repository_UserAccess();
    }

    /**
     * Регистрация хуков и фильтров
     *
     * @return void
     */
    protected function register_hooks() {
        // Регистрация AJAX-обработчиков
        $this->add_action('wp_ajax_cryptoschool_get_user_accesses', 'ajax_get_user_accesses');
        $this->add_action('wp_ajax_cryptoschool_create_user_access', 'ajax_create_user_access');
        $this->add_action('wp_ajax_cryptoschool_update_user_access', 'ajax_update_user_access');
        $this->add_action('wp_ajax_cryptoschool_delete_user_access', 'ajax_delete_user_access');
        $this->add_action('wp_ajax_cryptoschool_update_telegram_status', 'ajax_update_telegram_status');
        
        // Регистрация хуков для обработки платежей
        $this->add_action('cryptoschool_payment_completed', 'process_payment_completed', 10, 2);
        
        // Регистрация хуков для проверки истечения доступов
        $this->add_action('cryptoschool_daily_cron', 'check_expired_accesses');
    }

    /**
     * Регистрация пунктов меню администратора
     *
     * @return void
     */
    public function register_admin_menu() {
        // Добавление подпункта для доступов пользователей
        add_submenu_page(
            'cryptoschool',
            __('Доступы пользователей', 'cryptoschool'),
            __('Доступы пользователей', 'cryptoschool'),
            'manage_options',
            'cryptoschool-user-accesses',
            [$this, 'render_admin_user_accesses']
        );
    }

    /**
     * Отображение страницы управления доступами пользователей
     *
     * @return void
     */
    public function render_admin_user_accesses() {
        // Получение доступов пользователей
        $user_accesses = $this->get_all();

        // Получение пакетов для выбора
        $package_service = new CryptoSchool_Service_Package($this->loader);
        $packages = $package_service->get_all(['is_active' => 1]);

        // Получение пользователей для выбора
        $users = get_users(['role__in' => ['subscriber', 'customer']]);

        // Подключение шаблона
        include plugin_dir_path(dirname(dirname(__FILE__))) . 'admin/views/user-accesses.php';
    }

    /**
     * Получение всех доступов пользователей
     *
     * @param array $args Аргументы для фильтрации и сортировки
     * @return array
     */
    public function get_all($args = []) {
        global $wpdb;
        $table_name = $this->repository->get_table_name();
        
        $defaults = [
            'orderby' => 'access_start',
            'order' => 'DESC',
            'limit' => 0,
            'offset' => 0,
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $query = "SELECT * FROM {$table_name}";
        $params = [];
        
        // Добавление условий фильтрации
        $where_clauses = [];
        
        if (isset($args['user_id'])) {
            $where_clauses[] = "user_id = %d";
            $params[] = $args['user_id'];
        }
        
        if (isset($args['package_id'])) {
            $where_clauses[] = "package_id = %d";
            $params[] = $args['package_id'];
        }
        
        if (isset($args['status'])) {
            $where_clauses[] = "status = %s";
            $params[] = $args['status'];
        }
        
        if (isset($args['telegram_status'])) {
            $where_clauses[] = "telegram_status = %s";
            $params[] = $args['telegram_status'];
        }
        
        if (!empty($where_clauses)) {
            $query .= " WHERE " . implode(' AND ', $where_clauses);
        }
        
        // Добавление сортировки
        $query .= " ORDER BY {$args['orderby']} {$args['order']}";
        
        // Добавление лимита
        if ($args['limit'] > 0) {
            $query .= " LIMIT %d";
            $params[] = $args['limit'];
            
            if ($args['offset'] > 0) {
                $query .= " OFFSET %d";
                $params[] = $args['offset'];
            }
        }
        
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        // Преобразование результатов в модели
        $models = [];
        foreach ($results as $result) {
            $models[] = $this->repository->find($result['id']);
        }
        
        return $models;
    }

    /**
     * Получение количества доступов пользователей
     *
     * @param array $args Аргументы для фильтрации
     * @return int
     */
    public function get_count($args = []) {
        global $wpdb;
        $table_name = $this->repository->get_table_name();
        
        $query = "SELECT COUNT(*) FROM {$table_name}";
        $params = [];
        
        // Добавление условий фильтрации
        $where_clauses = [];
        
        if (isset($args['user_id'])) {
            $where_clauses[] = "user_id = %d";
            $params[] = $args['user_id'];
        }
        
        if (isset($args['package_id'])) {
            $where_clauses[] = "package_id = %d";
            $params[] = $args['package_id'];
        }
        
        if (isset($args['status'])) {
            $where_clauses[] = "status = %s";
            $params[] = $args['status'];
        }
        
        if (isset($args['telegram_status'])) {
            $where_clauses[] = "telegram_status = %s";
            $params[] = $args['telegram_status'];
        }
        
        if (!empty($where_clauses)) {
            $query .= " WHERE " . implode(' AND ', $where_clauses);
        }
        
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        
        return (int) $wpdb->get_var($query);
    }

    /**
     * Получение доступа по ID
     *
     * @param int $id ID доступа
     * @return mixed
     */
    public function get_by_id($id) {
        return $this->repository->find($id);
    }

    /**
     * Получение доступов пользователя
     *
     * @param int   $user_id ID пользователя
     * @param array $args    Аргументы для фильтрации и сортировки
     * @return array
     */
    public function get_user_accesses($user_id, $args = []) {
        return $this->repository->get_user_accesses($user_id, $args);
    }

    /**
     * Получение доступа пользователя к пакету
     *
     * @param int $user_id    ID пользователя
     * @param int $package_id ID пакета
     * @return mixed
     */
    public function get_user_package_access($user_id, $package_id) {
        return $this->repository->get_user_package_access($user_id, $package_id);
    }

    /**
     * Получение доступа пользователя к курсу
     *
     * @param int $user_id   ID пользователя
     * @param int $course_id ID курса
     * @return mixed
     */
    public function get_user_course_access($user_id, $course_id) {
        return $this->repository->get_user_course_access($user_id, $course_id);
    }

    /**
     * Создание доступа пользователя
     *
     * @param array $data Данные доступа
     * @return int|false ID созданного доступа или false в случае ошибки
     */
    public function create($data) {
        global $wpdb;
        
        // Проверка существования таблицы
        $table_name = $this->repository->get_table_name();
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
        
        if (!$table_exists) {
            error_log('CryptoSchool_Service_UserAccess::create - Таблица ' . $table_name . ' не существует. Попытка создать таблицу...');
            
            // Создание таблицы
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id bigint(20) UNSIGNED NOT NULL,
                package_id bigint(20) UNSIGNED NOT NULL,
                access_start datetime NOT NULL,
                access_end datetime DEFAULT NULL,
                duration_months int(11) DEFAULT NULL,
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
                error_log('CryptoSchool_Service_UserAccess::create - Не удалось создать таблицу ' . $table_name);
                return false;
            }
            
            error_log('CryptoSchool_Service_UserAccess::create - Таблица ' . $table_name . ' успешно создана');
        }
        
        // Проверка наличия обязательных полей
        if (!isset($data['user_id']) || !isset($data['package_id'])) {
            error_log('CryptoSchool_Service_UserAccess::create - Отсутствуют обязательные поля user_id или package_id');
            return false;
        }

        // Проверка существования пользователя
        $user = get_userdata($data['user_id']);
        if (!$user) {
            error_log('CryptoSchool_Service_UserAccess::create - Пользователь с ID ' . $data['user_id'] . ' не найден');
            return false;
        }

        // Проверка существования пакета
        $package_service = new CryptoSchool_Service_Package($this->loader);
        $package = $package_service->get_by_id($data['package_id']);
        if (!$package) {
            error_log('CryptoSchool_Service_UserAccess::create - Пакет с ID ' . $data['package_id'] . ' не найден');
            return false;
        }

        // Проверка, есть ли уже доступ к этому пакету
        $existing_access = $this->get_user_package_access($data['user_id'], $data['package_id']);
        if ($existing_access) {
            error_log('CryptoSchool_Service_UserAccess::create - У пользователя с ID ' . $data['user_id'] . ' уже есть доступ к пакету с ID ' . $data['package_id']);
            
            // Если доступ уже есть, но истек, можно его обновить
            if ($existing_access->status === 'expired') {
                $update_data = [
                    'status' => 'active'
                ];
                
                // Если указана продолжительность в месяцах, рассчитываем дату окончания
                if (!empty($data['duration_months'])) {
                    $start_date = isset($data['access_start']) ? new DateTime($data['access_start']) : new DateTime();
                    $start_date->modify("+{$data['duration_months']} months");
                    $update_data['access_end'] = $start_date->format('Y-m-d H:i:s');
                    $update_data['access_start'] = isset($data['access_start']) ? $data['access_start'] : current_time('mysql');
                } else {
                    $update_data['access_end'] = null; // Пожизненный доступ
                }
                
                // Обновляем существующий доступ
                $result = $this->update($existing_access->id, $update_data);
                
                if ($result) {
                    return $existing_access->id;
                }
            }
            
            return false;
        }

        // Установка дат начала и окончания доступа
        if (!isset($data['access_start'])) {
            $data['access_start'] = current_time('mysql');
        }

        // Создаем новый массив данных только с полями, которые есть в таблице
        $insert_data = [
            'user_id' => $data['user_id'],
            'package_id' => $data['package_id'],
            'access_start' => $data['access_start'],
            'status' => isset($data['status']) ? $data['status'] : 'active',
            'telegram_status' => isset($data['telegram_status']) ? $data['telegram_status'] : 'none',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        // Если указана продолжительность в месяцах, рассчитываем дату окончания
        if (!empty($data['duration_months'])) {
            $start_date = new DateTime($data['access_start']);
            $start_date->modify("+{$data['duration_months']} months");
            $insert_data['access_end'] = $start_date->format('Y-m-d H:i:s');
        } else {
            $insert_data['access_end'] = null; // Пожизненный доступ
        }

        // Добавляем duration_months в insert_data, если оно есть в data
        if (isset($data['duration_months'])) {
            $insert_data['duration_months'] = $data['duration_months'];
        }

        // Выводим SQL-запрос, который будет выполнен
        $sql_query = "INSERT INTO $table_name (";
        $sql_query .= implode(", ", array_keys($insert_data));
        $sql_query .= ") VALUES (";
        $placeholders = array();
        foreach ($insert_data as $value) {
            if (is_null($value)) {
                $placeholders[] = "NULL";
            } elseif (is_numeric($value)) {
                $placeholders[] = $value;
            } else {
                $placeholders[] = "'" . esc_sql($value) . "'";
            }
        }
        $sql_query .= implode(", ", $placeholders);
        $sql_query .= ")";
        
        error_log('CryptoSchool_Service_UserAccess::create - SQL-запрос: ' . $sql_query);
        
        // Прямая вставка в базу данных, минуя репозиторий
        $result = $wpdb->insert($table_name, $insert_data);
        
        if ($result === false) {
            error_log('CryptoSchool_Service_UserAccess::create - Не удалось создать доступ. Ошибка: ' . $wpdb->last_error);
            
            // Проверка, есть ли уже запись с такими user_id и package_id
            $check_query = $wpdb->prepare(
                "SELECT * FROM $table_name WHERE user_id = %d AND package_id = %d",
                $data['user_id'],
                $data['package_id']
            );
            error_log('CryptoSchool_Service_UserAccess::create - Проверка существующей записи: ' . $check_query);
            
            $existing_record = $wpdb->get_row($check_query);
            if ($existing_record) {
                error_log('CryptoSchool_Service_UserAccess::create - Найдена существующая запись: ' . json_encode($existing_record));
                return false;
            }
            
            // Проверка структуры таблицы
            $table_structure_query = "DESCRIBE $table_name";
            error_log('CryptoSchool_Service_UserAccess::create - Проверка структуры таблицы: ' . $table_structure_query);
            
            $table_structure = $wpdb->get_results($table_structure_query);
            error_log('CryptoSchool_Service_UserAccess::create - Структура таблицы: ' . json_encode($table_structure));
            
            // Проверка, все ли поля существуют в таблице
            $missing_fields = array();
            foreach (array_keys($data) as $field) {
                $field_exists = false;
                foreach ($table_structure as $column) {
                    if ($column->Field === $field) {
                        $field_exists = true;
                        break;
                    }
                }
                if (!$field_exists) {
                    $missing_fields[] = $field;
                }
            }
            
            if (!empty($missing_fields)) {
                error_log('CryptoSchool_Service_UserAccess::create - Отсутствуют поля в таблице: ' . implode(', ', $missing_fields));
            }
            
            return false;
        }
        
        $access_id = $wpdb->insert_id;
        error_log('CryptoSchool_Service_UserAccess::create - Доступ успешно создан с ID: ' . $access_id);
        
        return $access_id;
    }

    /**
     * Обновление доступа пользователя
     *
     * @param int   $id   ID доступа
     * @param array $data Данные для обновления
     * @return bool
     */
    public function update($id, $data) {
        // Если указана продолжительность в месяцах, рассчитываем дату окончания
        if (!empty($data['duration_months'])) {
            $access = $this->get_by_id($id);
            if ($access) {
                $start_date = isset($data['access_start']) ? new DateTime($data['access_start']) : new DateTime($access->access_start);
                $start_date->modify("+{$data['duration_months']} months");
                $data['access_end'] = $start_date->format('Y-m-d H:i:s');
            }
        } elseif (isset($data['duration_months']) && $data['duration_months'] === 0) {
            $data['access_end'] = null; // Пожизненный доступ
        }

        return $this->repository->update($id, $data);
    }

    /**
     * Удаление доступа пользователя
     *
     * @param int $id ID доступа
     * @return bool
     */
    public function delete($id) {
        return $this->repository->delete($id);
    }

    /**
     * Обновление статуса в Telegram
     *
     * @param int    $id     ID доступа
     * @param string $status Новый статус
     * @return bool
     */
    public function update_telegram_status($id, $status) {
        return $this->repository->update($id, ['telegram_status' => $status]);
    }

    /**
     * Проверка истечения доступов
     *
     * @return void
     */
    public function check_expired_accesses() {
        global $wpdb;
        $table_name = $this->repository->get_table_name();

        // Получение всех активных доступов с истекшим сроком
        $query = $wpdb->prepare(
            "SELECT * FROM {$table_name} 
            WHERE status = 'active' 
            AND access_end IS NOT NULL 
            AND access_end < %s",
            current_time('mysql')
        );
        $expired_accesses = $wpdb->get_results($query, ARRAY_A);

        // Обновление статуса для истекших доступов
        foreach ($expired_accesses as $access) {
            $this->repository->update($access['id'], ['status' => 'expired']);

            // Если пользователь был добавлен в Telegram, отправляем запрос на удаление
            if ($access['telegram_status'] === 'active') {
                $this->repository->update($access['id'], ['telegram_status' => 'removed']);
                do_action('cryptoschool_telegram_remove_user', $access['user_id'], $access['package_id']);
            }
        }
    }

    /**
     * Обработка завершенного платежа
     *
     * @param int   $payment_id ID платежа
     * @param array $payment    Данные платежа
     * @return void
     */
    public function process_payment_completed($payment_id, $payment) {
        // Получение данных пакета
        $package_service = new CryptoSchool_Service_Package($this->loader);
        $package = $package_service->get_by_id($payment['package_id']);

        if (!$package) {
            return;
        }

        // Проверка, есть ли уже доступ к этому пакету
        $existing_access = $this->get_user_package_access($payment['user_id'], $package->id);

        if ($existing_access) {
            // Если доступ уже есть, продлеваем его
            $duration_months = $package->duration_months;
            if ($duration_months) {
                // Если доступ истек, начинаем с текущей даты
                if ($existing_access->status === 'expired') {
                    $start_date = new DateTime();
                } else {
                    // Если доступ активен, продлеваем от даты окончания
                    $end_date = $existing_access->access_end ? new DateTime($existing_access->access_end) : new DateTime();
                    $start_date = $end_date;
                }

                $start_date->modify("+{$duration_months} months");
                $access_end = $start_date->format('Y-m-d H:i:s');

                $this->update($existing_access->id, [
                    'status' => 'active',
                    'access_end' => $access_end,
                ]);
            } else {
                // Если пакет с пожизненным доступом, просто активируем
                $this->update($existing_access->id, [
                    'status' => 'active',
                    'access_end' => null,
                ]);
            }
        } else {
            // Создание нового доступа
            $access_data = [
                'user_id' => $payment['user_id'],
                'package_id' => $package->id,
                'access_start' => current_time('mysql'),
                'duration_months' => $package->duration_months,
                'status' => 'active',
                'telegram_status' => 'none',
            ];

            $access_id = $this->create($access_data);

            // Если пакет включает доступ к Telegram-группам, отправляем запрос на добавление
            if ($package->package_type === 'community' || $package->package_type === 'combined') {
                $this->update($access_id, ['telegram_status' => 'invited']);
                do_action('cryptoschool_telegram_invite_user', $payment['user_id'], $package->id);
            }
        }
    }

    /**
     * AJAX-обработчик для получения доступов пользователей
     *
     * @return void
     */
    public function ajax_get_user_accesses() {
        // Проверка nonce
        check_ajax_referer('cryptoschool_nonce', 'nonce');

        // Проверка прав доступа
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('У вас нет прав для выполнения этого действия.', 'cryptoschool'));
        }

        // Получение параметров
        $args = [];
        if (isset($_POST['user_id'])) {
            $args['user_id'] = (int) $_POST['user_id'];
        }
        if (isset($_POST['package_id'])) {
            $args['package_id'] = (int) $_POST['package_id'];
        }
        if (isset($_POST['status'])) {
            $args['status'] = sanitize_text_field($_POST['status']);
        }
        if (isset($_POST['telegram_status'])) {
            $args['telegram_status'] = sanitize_text_field($_POST['telegram_status']);
        }
        if (isset($_POST['search'])) {
            $args['search'] = sanitize_text_field($_POST['search']);
        }
        if (isset($_POST['orderby'])) {
            $args['orderby'] = sanitize_text_field($_POST['orderby']);
        }
        if (isset($_POST['order'])) {
            $args['order'] = sanitize_text_field($_POST['order']);
        }

        // Получение доступов
        $user_accesses = $this->get_all($args);

        // Подготовка данных для ответа
        $data = [];
        foreach ($user_accesses as $access) {
            $user = get_userdata($access->user_id);
            $package_service = new CryptoSchool_Service_Package($this->loader);
            $package = $package_service->get_by_id($access->package_id);

            $data[] = [
                'id' => $access->id,
                'user_id' => $access->user_id,
                'user_name' => $user ? $user->display_name : __('Неизвестный пользователь', 'cryptoschool'),
                'user_email' => $user ? $user->user_email : '',
                'package_id' => $access->package_id,
                'package_title' => $package ? $package->title : __('Неизвестный пакет', 'cryptoschool'),
                'access_start' => $access->access_start,
                'access_end' => $access->access_end,
                'status' => $access->status,
                'telegram_status' => $access->telegram_status,
            ];
        }

        wp_send_json_success($data);
    }

    /**
     * AJAX-обработчик для создания доступа пользователя
     *
     * @return void
     */
    public function ajax_create_user_access() {
        // Проверка nonce
        check_ajax_referer('cryptoschool_nonce', 'nonce');

        // Проверка прав доступа
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('У вас нет прав для выполнения этого действия.', 'cryptoschool'));
        }

        // Получение данных из запроса
        $user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
        $package_id = isset($_POST['package_id']) ? (int) $_POST['package_id'] : 0;
        $access_start = isset($_POST['access_start']) ? sanitize_text_field($_POST['access_start']) : current_time('mysql');
        $duration_months = isset($_POST['duration_months']) ? (int) $_POST['duration_months'] : null;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'active';
        $telegram_status = isset($_POST['telegram_status']) ? sanitize_text_field($_POST['telegram_status']) : 'none';

        // Проверка обязательных полей
        if ($user_id <= 0) {
            wp_send_json_error(__('Некорректный ID пользователя.', 'cryptoschool'));
        }

        if ($package_id <= 0) {
            wp_send_json_error(__('Некорректный ID пакета.', 'cryptoschool'));
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

        $access_id = $this->create($access_data);

        if (!$access_id) {
            wp_send_json_error(__('Не удалось создать доступ.', 'cryptoschool'));
        }

        // Получение созданного доступа
        $access = $this->get_by_id($access_id);

        // Получение данных пользователя и пакета
        $user = get_userdata($access->user_id);
        $package_service = new CryptoSchool_Service_Package($this->loader);
        $package = $package_service->get_by_id($access->package_id);

        // Подготовка данных для ответа
        $data = [
            'id' => $access->id,
            'user_id' => $access->user_id,
            'user_name' => $user ? $user->display_name : __('Неизвестный пользователь', 'cryptoschool'),
            'user_email' => $user ? $user->user_email : '',
            'package_id' => $access->package_id,
            'package_title' => $package ? $package->title : __('Неизвестный пакет', 'cryptoschool'),
            'access_start' => $access->access_start,
            'access_end' => $access->access_end,
            'status' => $access->status,
            'telegram_status' => $access->telegram_status,
        ];

        wp_send_json_success($data);
    }

    /**
     * AJAX-обработчик для обновления доступа пользователя
     *
     * @return void
     */
    public function ajax_update_user_access() {
        // Проверка nonce
        check_ajax_referer('cryptoschool_nonce', 'nonce');

        // Проверка прав доступа
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('У вас нет прав для выполнения этого действия.', 'cryptoschool'));
        }

        // Получение ID доступа
        $access_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($access_id <= 0) {
            wp_send_json_error(__('Некорректный ID доступа.', 'cryptoschool'));
        }

        // Получение данных из запроса
        $access_start = isset($_POST['access_start']) ? sanitize_text_field($_POST['access_start']) : null;
        $duration_months = isset($_POST['duration_months']) ? (int) $_POST['duration_months'] : null;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : null;
        $telegram_status = isset($_POST['telegram_status']) ? sanitize_text_field($_POST['telegram_status']) : null;

        // Обновление доступа
        $access_data = [];
        if ($access_start !== null) {
            $access_data['access_start'] = $access_start;
        }
        if ($duration_months !== null) {
            $access_data['duration_months'] = $duration_months;
        }
        if ($status !== null) {
            $access_data['status'] = $status;
        }
        if ($telegram_status !== null) {
            $access_data['telegram_status'] = $telegram_status;
        }

        $result = $this->update($access_id, $access_data);

        if (!$result) {
            wp_send_json_error(__('Не удалось обновить доступ.', 'cryptoschool'));
        }

        // Получение обновленного доступа
        $access = $this->get_by_id($access_id);

        // Получение данных пользователя и пакета
        $user = get_userdata($access->user_id);
        $package_service = new CryptoSchool_Service_Package($this->loader);
        $package = $package_service->get_by_id($access->package_id);

        // Подготовка данных для ответа
        $data = [
            'id' => $access->id,
            'user_id' => $access->user_id,
            'user_name' => $user ? $user->display_name : __('Неизвестный пользователь', 'cryptoschool'),
            'user_email' => $user ? $user->user_email : '',
            'package_id' => $access->package_id,
            'package_title' => $package ? $package->title : __('Неизвестный пакет', 'cryptoschool'),
            'access_start' => $access->access_start,
            'access_end' => $access->access_end,
            'status' => $access->status,
            'telegram_status' => $access->telegram_status,
        ];

        wp_send_json_success($data);
    }

    /**
     * AJAX-обработчик для удаления доступа пользователя
     *
     * @return void
     */
    public function ajax_delete_user_access() {
        // Проверка nonce
        check_ajax_referer('cryptoschool_nonce', 'nonce');

        // Проверка прав доступа
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('У вас нет прав для выполнения этого действия.', 'cryptoschool'));
        }

        // Получение ID доступа
        $access_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($access_id <= 0) {
            wp_send_json_error(__('Некорректный ID доступа.', 'cryptoschool'));
        }

        // Удаление доступа
        $result = $this->delete($access_id);

        if (!$result) {
            wp_send_json_error(__('Не удалось удалить доступ.', 'cryptoschool'));
        }

        wp_send_json_success();
    }

    /**
     * AJAX-обработчик для обновления статуса в Telegram
     *
     * @return void
     */
    public function ajax_update_telegram_status() {
        // Проверка nonce
        check_ajax_referer('cryptoschool_nonce', 'nonce');

        // Проверка прав доступа
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('У вас нет прав для выполнения этого действия.', 'cryptoschool'));
        }

        // Получение ID доступа
        $access_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($access_id <= 0) {
            wp_send_json_error(__('Некорректный ID доступа.', 'cryptoschool'));
        }

        // Получение нового статуса
        $telegram_status = isset($_POST['telegram_status']) ? sanitize_text_field($_POST['telegram_status']) : '';
        if (empty($telegram_status)) {
            wp_send_json_error(__('Некорректный статус в Telegram.', 'cryptoschool'));
        }

        // Обновление статуса
        $result = $this->update_telegram_status($access_id, $telegram_status);

        if (!$result) {
            wp_send_json_error(__('Не удалось обновить статус в Telegram.', 'cryptoschool'));
        }

        // Получение обновленного доступа
        $access = $this->get_by_id($access_id);

        // Если статус изменен на "invited", отправляем запрос на добавление пользователя
        if ($telegram_status === 'invited') {
            do_action('cryptoschool_telegram_invite_user', $access->user_id, $access->package_id);
        }

        // Если статус изменен на "removed", отправляем запрос на удаление пользователя
        if ($telegram_status === 'removed') {
            do_action('cryptoschool_telegram_remove_user', $access->user_id, $access->package_id);
        }

        wp_send_json_success();
    }
}
