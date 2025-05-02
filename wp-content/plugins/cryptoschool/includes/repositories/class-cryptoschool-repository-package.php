<?php
/**
 * Репозиторий пакетов
 *
 * @package CryptoSchool
 * @subpackage Repositories
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс репозитория пакетов
 */
class CryptoSchool_Repository_Package extends CryptoSchool_Repository {
    /**
     * Имя таблицы в базе данных
     *
     * @var string
     */
    protected $table_name = 'cryptoschool_packages';

    /**
     * Имя класса модели
     *
     * @var string
     */
    protected $model_class = 'CryptoSchool_Model_Package';

    /**
     * Получение пакетов с фильтрацией и сортировкой
     *
     * @param array $args Аргументы для фильтрации и сортировки
     * @return array
     */
    public function get_packages($args = []) {
        global $wpdb;

        $defaults = [
            'orderby'       => 'price',
            'order'         => 'ASC',
            'limit'         => 0,
            'offset'        => 0,
            'is_active'     => null,
            'package_type'  => null,
            'search'        => '',
        ];

        $args = wp_parse_args($args, $defaults);

        $query = "SELECT * FROM {$this->table_name} WHERE 1=1";
        $params = [];

        // Фильтрация по активности
        if ($args['is_active'] !== null) {
            $query .= " AND is_active = %d";
            $params[] = $args['is_active'];
        }

        // Фильтрация по типу пакета
        if (!empty($args['package_type'])) {
            $query .= " AND package_type = %s";
            $params[] = $args['package_type'];
        }

        // Поиск по названию и описанию
        if (!empty($args['search'])) {
            $query .= " AND (title LIKE %s OR description LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }

        // Сортировка
        $query .= " ORDER BY {$args['orderby']} {$args['order']}";

        // Лимит и смещение
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
     * Получение количества пакетов с фильтрацией
     *
     * @param array $args Аргументы для фильтрации
     * @return int
     */
    public function get_packages_count($args = []) {
        global $wpdb;

        $defaults = [
            'is_active'     => null,
            'package_type'  => null,
            'search'        => '',
        ];

        $args = wp_parse_args($args, $defaults);

        $query = "SELECT COUNT(*) FROM {$this->table_name} WHERE 1=1";
        $params = [];

        // Фильтрация по активности
        if ($args['is_active'] !== null) {
            $query .= " AND is_active = %d";
            $params[] = $args['is_active'];
        }

        // Фильтрация по типу пакета
        if (!empty($args['package_type'])) {
            $query .= " AND package_type = %s";
            $params[] = $args['package_type'];
        }

        // Поиск по названию и описанию
        if (!empty($args['search'])) {
            $query .= " AND (title LIKE %s OR description LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $query = $wpdb->prepare($query, $params);
        return (int) $wpdb->get_var($query);
    }

    /**
     * Получение пакетов по типу
     *
     * @param string $package_type Тип пакета
     * @param array  $args         Дополнительные аргументы
     * @return array
     */
    public function get_packages_by_type($package_type, $args = []) {
        $args['package_type'] = $package_type;
        return $this->get_packages($args);
    }

    /**
     * Получение пакетов с курсом
     *
     * @param int   $course_id ID курса
     * @param array $args      Дополнительные аргументы
     * @return array
     */
    public function get_packages_with_course($course_id, $args = []) {
        global $wpdb;

        $defaults = [
            'orderby'       => 'price',
            'order'         => 'ASC',
            'limit'         => 0,
            'offset'        => 0,
            'is_active'     => null,
            'package_type'  => null,
        ];

        $args = wp_parse_args($args, $defaults);

        $query = "SELECT * FROM {$this->table_name} WHERE FIND_IN_SET(%d, course_ids)";
        $params = [$course_id];

        // Фильтрация по активности
        if ($args['is_active'] !== null) {
            $query .= " AND is_active = %d";
            $params[] = $args['is_active'];
        }

        // Фильтрация по типу пакета
        if (!empty($args['package_type'])) {
            $query .= " AND package_type = %s";
            $params[] = $args['package_type'];
        }

        // Сортировка
        $query .= " ORDER BY {$args['orderby']} {$args['order']}";

        // Лимит и смещение
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
     * Получение пакетов, доступных пользователю
     *
     * @param int   $user_id ID пользователя
     * @param array $args    Дополнительные аргументы
     * @return array
     */
    public function get_user_packages($user_id, $args = []) {
        global $wpdb;

        $defaults = [
            'orderby'       => 'p.price',
            'order'         => 'ASC',
            'limit'         => 0,
            'offset'        => 0,
            'is_active'     => 1,
            'status'        => 'active',
        ];

        $args = wp_parse_args($args, $defaults);

        $access_table = $wpdb->prefix . 'cryptoschool_user_access';

        $query = "
            SELECT p.* FROM {$this->table_name} p
            INNER JOIN {$access_table} a ON a.package_id = p.id
            WHERE a.user_id = %d
        ";
        $params = [$user_id];

        // Фильтрация по активности пакета
        if ($args['is_active'] !== null) {
            $query .= " AND p.is_active = %d";
            $params[] = $args['is_active'];
        }

        // Фильтрация по статусу доступа
        if (!empty($args['status'])) {
            $query .= " AND a.status = %s";
            $params[] = $args['status'];
        }

        // Сортировка
        $query .= " ORDER BY {$args['orderby']} {$args['order']}";

        // Лимит и смещение
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
     * Получение количества пакетов, доступных пользователю
     *
     * @param int   $user_id ID пользователя
     * @param array $args    Дополнительные аргументы
     * @return int
     */
    public function get_user_packages_count($user_id, $args = []) {
        global $wpdb;

        $defaults = [
            'is_active'     => 1,
            'status'        => 'active',
        ];

        $args = wp_parse_args($args, $defaults);

        $access_table = $wpdb->prefix . 'cryptoschool_user_access';

        $query = "
            SELECT COUNT(DISTINCT p.id) FROM {$this->table_name} p
            INNER JOIN {$access_table} a ON a.package_id = p.id
            WHERE a.user_id = %d
        ";
        $params = [$user_id];

        // Фильтрация по активности пакета
        if ($args['is_active'] !== null) {
            $query .= " AND p.is_active = %d";
            $params[] = $args['is_active'];
        }

        // Фильтрация по статусу доступа
        if (!empty($args['status'])) {
            $query .= " AND a.status = %s";
            $params[] = $args['status'];
        }

        $query = $wpdb->prepare($query, $params);
        return (int) $wpdb->get_var($query);
    }

    /**
     * Получение курсов, включенных в пакет
     *
     * @param int   $package_id ID пакета
     * @param array $args       Дополнительные аргументы
     * @return array
     */
    public function get_package_courses($package_id, $args = []) {
        global $wpdb;

        $package = $this->find($package_id);
        if (!$package || empty($package->course_ids)) {
            return [];
        }

        $course_ids = explode(',', $package->course_ids);
        if (empty($course_ids)) {
            return [];
        }

        $defaults = [
            'orderby'       => 'course_order',
            'order'         => 'ASC',
            'is_active'     => null,
        ];

        $args = wp_parse_args($args, $defaults);

        $courses_table = $wpdb->prefix . 'cryptoschool_courses';

        $placeholders = implode(',', array_fill(0, count($course_ids), '%d'));
        $query = "SELECT * FROM {$courses_table} WHERE id IN ({$placeholders})";
        $params = $course_ids;

        // Фильтрация по активности
        if ($args['is_active'] !== null) {
            $query .= " AND is_active = %d";
            $params[] = $args['is_active'];
        }

        // Сортировка
        $query .= " ORDER BY {$args['orderby']} {$args['order']}";

        $query = $wpdb->prepare($query, $params);
        $results = $wpdb->get_results($query, ARRAY_A);

        $course_repository = new CryptoSchool_Repository_Course();
        return $course_repository->mapToModels($results);
    }

    /**
     * Добавление курса в пакет
     *
     * @param int $package_id ID пакета
     * @param int $course_id  ID курса
     * @return bool
     */
    public function add_course_to_package($package_id, $course_id) {
        global $wpdb;

        $package = $this->find($package_id);
        if (!$package) {
            return false;
        }

        $course_ids = !empty($package->course_ids) ? explode(',', $package->course_ids) : [];
        if (in_array($course_id, $course_ids)) {
            return true; // Курс уже добавлен в пакет
        }

        $course_ids[] = $course_id;
        $course_ids_str = implode(',', $course_ids);

        $result = $wpdb->update(
            $this->table_name,
            ['course_ids' => $course_ids_str],
            ['id' => $package_id]
        );

        return $result !== false;
    }

    /**
     * Удаление курса из пакета
     *
     * @param int $package_id ID пакета
     * @param int $course_id  ID курса
     * @return bool
     */
    public function remove_course_from_package($package_id, $course_id) {
        global $wpdb;

        $package = $this->find($package_id);
        if (!$package || empty($package->course_ids)) {
            return false;
        }

        $course_ids = explode(',', $package->course_ids);
        $key = array_search($course_id, $course_ids);
        if ($key === false) {
            return true; // Курс уже удален из пакета
        }

        unset($course_ids[$key]);
        $course_ids_str = implode(',', $course_ids);

        $result = $wpdb->update(
            $this->table_name,
            ['course_ids' => $course_ids_str],
            ['id' => $package_id]
        );

        return $result !== false;
    }

    /**
     * Обновление курсов в пакете
     *
     * @param int   $package_id ID пакета
     * @param array $course_ids Массив ID курсов
     * @return bool
     */
    public function update_package_courses($package_id, $course_ids) {
        global $wpdb;

        $course_ids_str = implode(',', $course_ids);

        $result = $wpdb->update(
            $this->table_name,
            ['course_ids' => $course_ids_str],
            ['id' => $package_id]
        );

        return $result !== false;
    }
}
