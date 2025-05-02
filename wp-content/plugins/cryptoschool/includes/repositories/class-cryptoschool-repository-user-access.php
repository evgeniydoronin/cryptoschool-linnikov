<?php
/**
 * Репозиторий доступа пользователей
 *
 * @package CryptoSchool
 * @subpackage Repositories
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс репозитория доступа пользователей
 */
class CryptoSchool_Repository_UserAccess extends CryptoSchool_Repository {
    /**
     * Имя таблицы в базе данных
     *
     * @var string
     */
    protected $table_name = 'cryptoschool_user_access';

    /**
     * Имя класса модели
     *
     * @var string
     */
    protected $model_class = 'CryptoSchool_Model_UserAccess';

    /**
     * Получение доступов пользователя
     *
     * @param int   $user_id ID пользователя
     * @param array $args    Дополнительные аргументы
     * @return array
     */
    public function get_user_accesses($user_id, $args = []) {
        global $wpdb;

        $defaults = [
            'status'   => 'active',
            'orderby'  => 'access_start',
            'order'    => 'DESC',
            'limit'    => 0,
            'offset'   => 0,
        ];

        $args = wp_parse_args($args, $defaults);

        $query = "SELECT * FROM {$this->table_name} WHERE user_id = %d";
        $params = [$user_id];

        // Добавление условия статуса
        if (!empty($args['status'])) {
            $query .= " AND status = %s";
            $params[] = $args['status'];
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

        $query = $wpdb->prepare($query, $params);
        $results = $wpdb->get_results($query, ARRAY_A);

        return $this->mapToModels($results);
    }

    /**
     * Получение доступа пользователя к пакету
     *
     * @param int $user_id    ID пользователя
     * @param int $package_id ID пакета
     * @return mixed
     */
    public function get_user_package_access($user_id, $package_id) {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
            WHERE user_id = %d AND package_id = %d AND status = 'active'",
            $user_id,
            $package_id
        );

        $result = $wpdb->get_row($query, ARRAY_A);

        if (!$result) {
            return null;
        }

        return $this->mapToModel($result);
    }

    /**
     * Получение доступа пользователя к курсу
     *
     * @param int $user_id  ID пользователя
     * @param int $course_id ID курса
     * @return mixed
     */
    public function get_user_course_access($user_id, $course_id) {
        global $wpdb;

        $packages_table = $wpdb->prefix . 'cryptoschool_packages';

        $query = $wpdb->prepare(
            "SELECT a.* FROM {$this->table_name} a
            INNER JOIN {$packages_table} p ON a.package_id = p.id
            WHERE a.user_id = %d AND a.status = 'active'
            AND (
                p.package_type = 'course' OR p.package_type = 'combined'
            )
            AND FIND_IN_SET(%d, p.course_ids)",
            $user_id,
            $course_id
        );

        $result = $wpdb->get_row($query, ARRAY_A);

        if (!$result) {
            return null;
        }

        return $this->mapToModel($result);
    }

    /**
     * Проверка, имеет ли пользователь доступ к приватным группам
     *
     * @param int $user_id ID пользователя
     * @return bool
     */
    public function has_user_community_access($user_id) {
        global $wpdb;

        $packages_table = $wpdb->prefix . 'cryptoschool_packages';

        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} a
            INNER JOIN {$packages_table} p ON a.package_id = p.id
            WHERE a.user_id = %d AND a.status = 'active'
            AND (
                p.package_type = 'community' OR p.package_type = 'combined'
            )",
            $user_id
        );

        $count = (int) $wpdb->get_var($query);

        return $count > 0;
    }

    /**
     * Создание доступа пользователя к пакету
     *
     * @param int   $user_id    ID пользователя
     * @param int   $package_id ID пакета
     * @param array $args       Дополнительные аргументы
     * @return int|false
     */
    public function create_user_access($user_id, $package_id, $args = []) {
        global $wpdb;

        // Получение информации о пакете
        $packages_table = $wpdb->prefix . 'cryptoschool_packages';
        $package_query = $wpdb->prepare(
            "SELECT * FROM {$packages_table} WHERE id = %d",
            $package_id
        );
        $package = $wpdb->get_row($package_query);

        if (!$package) {
            return false;
        }

        // Определение срока доступа
        $access_end = null;
        if ($package->duration_months > 0) {
            $access_end = date('Y-m-d H:i:s', strtotime("+{$package->duration_months} months"));
        }

        $defaults = [
            'access_start' => current_time('mysql'),
            'access_end' => $access_end,
            'status' => 'active',
            'telegram_status' => 'none',
            'telegram_invite_link' => null,
            'telegram_invite_date' => null,
        ];

        $data = wp_parse_args($args, $defaults);
        $data['user_id'] = $user_id;
        $data['package_id'] = $package_id;
        $data['created_at'] = current_time('mysql');
        $data['updated_at'] = current_time('mysql');

        $wpdb->insert($this->table_name, $data);

        return $wpdb->insert_id;
    }

    /**
     * Обновление доступа пользователя
     *
     * @param int   $access_id ID доступа
     * @param array $data      Данные для обновления
     * @return bool
     */
    public function update_user_access($access_id, $data) {
        global $wpdb;

        $data['updated_at'] = current_time('mysql');

        $result = $wpdb->update(
            $this->table_name,
            $data,
            ['id' => $access_id]
        );

        return $result !== false;
    }

    /**
     * Продление доступа пользователя
     *
     * @param int $access_id       ID доступа
     * @param int $duration_months Продолжительность в месяцах
     * @return bool
     */
    public function extend_user_access($access_id, $duration_months) {
        global $wpdb;

        // Получение информации о доступе
        $access = $this->find($access_id);
        if (!$access) {
            return false;
        }

        // Определение новой даты окончания доступа
        $access_end = null;
        if ($access->access_end) {
            // Если доступ уже имеет срок окончания, продлеваем его
            $access_end = date('Y-m-d H:i:s', strtotime($access->access_end . " +{$duration_months} months"));
        } elseif ($duration_months > 0) {
            // Если доступ бессрочный, но теперь нужно установить срок
            $access_end = date('Y-m-d H:i:s', strtotime("+{$duration_months} months"));
        }

        // Обновление доступа
        $data = [
            'access_end' => $access_end,
            'status' => 'active',
            'updated_at' => current_time('mysql'),
        ];

        $result = $wpdb->update(
            $this->table_name,
            $data,
            ['id' => $access_id]
        );

        return $result !== false;
    }

    /**
     * Отмена доступа пользователя
     *
     * @param int $access_id ID доступа
     * @return bool
     */
    public function cancel_user_access($access_id) {
        global $wpdb;

        $data = [
            'status' => 'expired',
            'updated_at' => current_time('mysql'),
        ];

        $result = $wpdb->update(
            $this->table_name,
            $data,
            ['id' => $access_id]
        );

        return $result !== false;
    }

    /**
     * Обновление статуса Telegram для доступа
     *
     * @param int    $access_id      ID доступа
     * @param string $telegram_status Статус Telegram
     * @param string $invite_link     Ссылка-приглашение
     * @return bool
     */
    public function update_telegram_status($access_id, $telegram_status, $invite_link = null) {
        global $wpdb;

        $data = [
            'telegram_status' => $telegram_status,
            'updated_at' => current_time('mysql'),
        ];

        if ($invite_link) {
            $data['telegram_invite_link'] = $invite_link;
            $data['telegram_invite_date'] = current_time('mysql');
        }

        $result = $wpdb->update(
            $this->table_name,
            $data,
            ['id' => $access_id]
        );

        return $result !== false;
    }

    /**
     * Получение истекших доступов
     *
     * @return array
     */
    public function get_expired_accesses() {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
            WHERE status = 'active' AND access_end IS NOT NULL AND access_end < %s",
            current_time('mysql')
        );

        $results = $wpdb->get_results($query, ARRAY_A);

        return $this->mapToModels($results);
    }

    /**
     * Проверка и обновление истекших доступов
     *
     * @return int Количество обновленных доступов
     */
    public function check_and_update_expired_accesses() {
        global $wpdb;

        // Получение истекших доступов
        $expired_accesses = $this->get_expired_accesses();
        $updated_count = 0;

        foreach ($expired_accesses as $access) {
            // Обновление статуса доступа
            $this->cancel_user_access($access->id);
            $updated_count++;

            // Логирование
            $logger = CryptoSchool_Logger::get_instance();
            $logger->info(
                'Доступ пользователя истек',
                [
                    'user_id' => $access->user_id,
                    'package_id' => $access->package_id,
                    'access_id' => $access->id,
                    'access_end' => $access->access_end,
                ]
            );
        }

        return $updated_count;
    }
}
