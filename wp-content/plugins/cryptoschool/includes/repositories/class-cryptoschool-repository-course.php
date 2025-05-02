<?php
/**
 * Репозиторий курсов
 *
 * @package CryptoSchool
 * @subpackage Repositories
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс репозитория курсов
 */
class CryptoSchool_Repository_Course extends CryptoSchool_Repository {
    /**
     * Имя таблицы в базе данных
     *
     * @var string
     */
    protected $table_name = 'cryptoschool_courses';

    /**
     * Имя класса модели
     *
     * @var string
     */
    protected $model_class = 'CryptoSchool_Model_Course';

    /**
     * Получение курсов с фильтрацией и сортировкой
     *
     * @param array $args Аргументы для фильтрации и сортировки
     * @return array
     */
    public function get_courses($args = []) {
        global $wpdb;

        // Отладочный вывод
        error_log('Repository get_courses - Start');
        error_log('Repository get_courses - Table name: ' . $this->table_name);
        error_log('Repository get_courses - Args: ' . json_encode($args));

        $defaults = [
            'orderby'       => 'course_order',
            'order'         => 'ASC',
            'limit'         => 0,
            'offset'        => 0,
            'is_active'     => null,
            'featured'      => null,
            'search'        => '',
            'difficulty'    => '',
        ];

        $args = wp_parse_args($args, $defaults);
        error_log('Repository get_courses - Merged args: ' . json_encode($args));

        $query = "SELECT * FROM {$this->table_name} WHERE 1=1";
        $params = [];

        // Фильтрация по активности
        if ($args['is_active'] !== null) {
            $query .= " AND is_active = %d";
            $params[] = $args['is_active'];
        }

        // Фильтрация по избранным курсам
        if ($args['featured'] !== null) {
            $query .= " AND featured = %d";
            $params[] = $args['featured'];
        }

        // Фильтрация по уровню сложности
        if (!empty($args['difficulty'])) {
            $query .= " AND difficulty_level = %s";
            $params[] = $args['difficulty'];
        }

        // Поиск по названию и описанию
        if (!empty($args['search'])) {
            $query .= " AND (title LIKE %s OR description LIKE %s)";
            
            // Транслитерация поискового запроса, если он содержит кириллицу
            $search = $args['search'];
            if (preg_match('/[А-Яа-яЁёҐґЄєІіЇї]/u', $search)) {
                // Отладочный вывод
                error_log('Repository get_courses - Search term contains Cyrillic: ' . $search);
                
                // Транслитерация поискового запроса
                $search = CryptoSchool_Helper_String::transliterate($search);
                
                // Отладочный вывод
                error_log('Repository get_courses - Transliterated search term: ' . $search);
            }
            
            $search_term = '%' . $wpdb->esc_like($search) . '%';
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

        // Подготовка запроса только если есть параметры
        if (!empty($params)) {
            $prepared_query = $wpdb->prepare($query, $params);
            error_log('Repository get_courses - Query: ' . $prepared_query);
            $results = $wpdb->get_results($prepared_query, ARRAY_A);
        } else {
            error_log('Repository get_courses - Query (no params): ' . $query);
            $results = $wpdb->get_results($query, ARRAY_A);
        }
        error_log('Repository get_courses - Results count: ' . count($results));
        
        if (count($results) > 0) {
            error_log('Repository get_courses - First result: ' . json_encode($results[0]));
            
            // Проверка наличия ID в результатах
            foreach ($results as $index => $result) {
                if (!isset($result['id']) || empty($result['id'])) {
                    error_log('Repository get_courses - ERROR: ID is missing or empty in result ' . $index);
                } else {
                    error_log('Repository get_courses - Result ' . $index . ' ID: ' . $result['id']);
                }
            }
        } else {
            error_log('Repository get_courses - No results found');
            
            // Проверка структуры таблицы
            $structure_query = "DESCRIBE {$this->table_name}";
            $structure = $wpdb->get_results($structure_query, ARRAY_A);
            error_log('Repository get_courses - Table structure: ' . json_encode($structure));
        }

        $models = $this->mapToModels($results);
        error_log('Repository get_courses - Models count: ' . count($models));
        
        if (count($models) > 0) {
            error_log('Repository get_courses - First model ID: ' . $models[0]->getAttribute('id'));
            error_log('Repository get_courses - First model attributes: ' . json_encode($models[0]->getAttributes()));
        }

        return $models;
    }

    /**
     * Получение количества курсов с фильтрацией
     *
     * @param array $args Аргументы для фильтрации
     * @return int
     */
    public function get_courses_count($args = []) {
        global $wpdb;

        $defaults = [
            'is_active'     => null,
            'featured'      => null,
            'search'        => '',
            'difficulty'    => '',
        ];

        $args = wp_parse_args($args, $defaults);

        $query = "SELECT COUNT(*) FROM {$this->table_name} WHERE 1=1";
        $params = [];

        // Фильтрация по активности
        if ($args['is_active'] !== null) {
            $query .= " AND is_active = %d";
            $params[] = $args['is_active'];
        }

        // Фильтрация по избранным курсам
        if ($args['featured'] !== null) {
            $query .= " AND featured = %d";
            $params[] = $args['featured'];
        }

        // Фильтрация по уровню сложности
        if (!empty($args['difficulty'])) {
            $query .= " AND difficulty_level = %s";
            $params[] = $args['difficulty'];
        }

        // Поиск по названию и описанию
        if (!empty($args['search'])) {
            $query .= " AND (title LIKE %s OR description LIKE %s)";
            
            // Транслитерация поискового запроса, если он содержит кириллицу
            $search = $args['search'];
            if (preg_match('/[А-Яа-яЁёҐґЄєІіЇї]/u', $search)) {
                // Отладочный вывод
                error_log('Repository get_courses_count - Search term contains Cyrillic: ' . $search);
                
                // Транслитерация поискового запроса
                $search = CryptoSchool_Helper_String::transliterate($search);
                
                // Отладочный вывод
                error_log('Repository get_courses_count - Transliterated search term: ' . $search);
            }
            
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }

        // Подготовка запроса только если есть параметры
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        
        return (int) $wpdb->get_var($query);
    }

    /**
     * Получение курса по слагу
     *
     * @param string $slug Слаг курса
     * @return mixed
     */
    public function get_by_slug($slug) {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE slug = %s",
            $slug
        );

        $result = $wpdb->get_row($query, ARRAY_A);

        if (!$result) {
            return null;
        }

        return $this->mapToModel($result);
    }

    /**
     * Обновление порядка курсов
     *
     * @param array $course_orders Массив с ID курсов и их порядком
     * @return bool
     */
    public function update_order($course_orders) {
        global $wpdb;

        foreach ($course_orders as $course_id => $order) {
            $wpdb->update(
                $this->table_name,
                ['course_order' => $order],
                ['id' => $course_id]
            );
        }

        return true;
    }

    /**
     * Получение курсов, доступных пользователю
     *
     * @param int   $user_id ID пользователя
     * @param array $args    Аргументы для фильтрации и сортировки
     * @return array
     */
    public function get_user_courses($user_id, $args = []) {
        global $wpdb;

        $defaults = [
            'orderby'       => 'c.course_order',
            'order'         => 'ASC',
            'limit'         => 0,
            'offset'        => 0,
            'is_active'     => 1,
            'search'        => '',
        ];

        $args = wp_parse_args($args, $defaults);

        $access_table = $wpdb->prefix . 'cryptoschool_user_access';
        $packages_table = $wpdb->prefix . 'cryptoschool_packages';

        $query = "
            SELECT DISTINCT c.* FROM {$this->table_name} c
            INNER JOIN {$packages_table} p ON FIND_IN_SET(c.id, p.course_ids)
            INNER JOIN {$access_table} a ON a.package_id = p.id
            WHERE a.user_id = %d AND a.status = 'active'
        ";
        $params = [$user_id];

        // Фильтрация по активности
        if ($args['is_active'] !== null) {
            $query .= " AND c.is_active = %d";
            $params[] = $args['is_active'];
        }

        // Поиск по названию и описанию
        if (!empty($args['search'])) {
            $query .= " AND (c.title LIKE %s OR c.description LIKE %s)";
            
            // Транслитерация поискового запроса, если он содержит кириллицу
            $search = $args['search'];
            if (preg_match('/[А-Яа-яЁёҐґЄєІіЇї]/u', $search)) {
                // Отладочный вывод
                error_log('Repository get_user_courses - Search term contains Cyrillic: ' . $search);
                
                // Транслитерация поискового запроса
                $search = CryptoSchool_Helper_String::transliterate($search);
                
                // Отладочный вывод
                error_log('Repository get_user_courses - Transliterated search term: ' . $search);
            }
            
            $search_term = '%' . $wpdb->esc_like($search) . '%';
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

        // Подготовка запроса только если есть параметры
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        
        $results = $wpdb->get_results($query, ARRAY_A);

        return $this->mapToModels($results);
    }

    /**
     * Получение количества курсов, доступных пользователю
     *
     * @param int   $user_id ID пользователя
     * @param array $args    Аргументы для фильтрации
     * @return int
     */
    public function get_user_courses_count($user_id, $args = []) {
        global $wpdb;

        $defaults = [
            'is_active'     => 1,
            'search'        => '',
        ];

        $args = wp_parse_args($args, $defaults);

        $access_table = $wpdb->prefix . 'cryptoschool_user_access';
        $packages_table = $wpdb->prefix . 'cryptoschool_packages';

        $query = "
            SELECT COUNT(DISTINCT c.id) FROM {$this->table_name} c
            INNER JOIN {$packages_table} p ON FIND_IN_SET(c.id, p.course_ids)
            INNER JOIN {$access_table} a ON a.package_id = p.id
            WHERE a.user_id = %d AND a.status = 'active'
        ";
        $params = [$user_id];

        // Фильтрация по активности
        if ($args['is_active'] !== null) {
            $query .= " AND c.is_active = %d";
            $params[] = $args['is_active'];
        }

        // Поиск по названию и описанию
        if (!empty($args['search'])) {
            $query .= " AND (c.title LIKE %s OR c.description LIKE %s)";
            
            // Транслитерация поискового запроса, если он содержит кириллицу
            $search = $args['search'];
            if (preg_match('/[А-Яа-яЁёҐґЄєІіЇї]/u', $search)) {
                // Отладочный вывод
                error_log('Repository get_user_courses_count - Search term contains Cyrillic: ' . $search);
                
                // Транслитерация поискового запроса
                $search = CryptoSchool_Helper_String::transliterate($search);
                
                // Отладочный вывод
                error_log('Repository get_user_courses_count - Transliterated search term: ' . $search);
            }
            
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }

        // Подготовка запроса только если есть параметры
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        
        return (int) $wpdb->get_var($query);
    }

    /**
     * Генерация уникального слага для курса
     *
     * @param string $title     Название курса
     * @param int    $course_id ID курса (для обновления)
     * @return string
     */
    public function generate_unique_slug($title, $course_id = 0) {
        // Используем общий хелпер для генерации уникального слага
        return CryptoSchool_Helper_String::generate_unique_slug($title, $this->table_name, $course_id);
    }
}
