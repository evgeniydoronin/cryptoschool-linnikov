<?php
/**
 * Репозиторий истории начисления баллов
 *
 * @package CryptoSchool
 * @subpackage Repositories
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс репозитория истории начисления баллов
 */
class CryptoSchool_Repository_Points_History extends CryptoSchool_Repository {
    /**
     * Имя таблицы в базе данных
     *
     * @var string
     */
    protected $table_name = 'cryptoschool_points_history';

    /**
     * Имя класса модели
     *
     * @var string
     */
    protected $model_class = 'CryptoSchool_Model_Points_History';

    /**
     * Получение истории баллов пользователя
     *
     * @param int   $user_id ID пользователя
     * @param array $args    Дополнительные аргументы
     * @return array
     */
    public function get_user_points_history($user_id, $args = []) {
        global $wpdb;

        $defaults = [
            'orderby' => 'created_at',
            'order'   => 'DESC',
            'limit'   => 0,
            'offset'  => 0,
            'points_type' => '',
        ];

        $args = wp_parse_args($args, $defaults);

        $query = "SELECT * FROM {$this->table_name} WHERE user_id = %d";
        $params = [$user_id];

        // Фильтрация по типу баллов
        if (!empty($args['points_type'])) {
            $query .= " AND points_type = %s";
            $params[] = $args['points_type'];
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
     * Получение истории баллов пользователя за определенный период
     *
     * @param int    $user_id     ID пользователя
     * @param string $start_date  Начальная дата (формат Y-m-d)
     * @param string $end_date    Конечная дата (формат Y-m-d)
     * @param array  $args        Дополнительные аргументы
     * @return array
     */
    public function get_user_points_history_by_period($user_id, $start_date, $end_date, $args = []) {
        global $wpdb;

        $defaults = [
            'orderby' => 'created_at',
            'order'   => 'DESC',
            'limit'   => 0,
            'offset'  => 0,
            'points_type' => '',
        ];

        $args = wp_parse_args($args, $defaults);

        $query = "SELECT * FROM {$this->table_name} WHERE user_id = %d AND DATE(created_at) BETWEEN %s AND %s";
        $params = [$user_id, $start_date, $end_date];

        // Фильтрация по типу баллов
        if (!empty($args['points_type'])) {
            $query .= " AND points_type = %s";
            $params[] = $args['points_type'];
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
     * Получение суммы баллов пользователя
     *
     * @param int   $user_id ID пользователя
     * @param array $args    Дополнительные аргументы
     * @return int
     */
    public function get_user_total_points($user_id, $args = []) {
        global $wpdb;

        $defaults = [
            'points_type' => '',
            'start_date' => '',
            'end_date' => '',
        ];

        $args = wp_parse_args($args, $defaults);

        $query = "SELECT SUM(points) FROM {$this->table_name} WHERE user_id = %d";
        $params = [$user_id];

        // Фильтрация по типу баллов
        if (!empty($args['points_type'])) {
            $query .= " AND points_type = %s";
            $params[] = $args['points_type'];
        }

        // Фильтрация по периоду
        if (!empty($args['start_date']) && !empty($args['end_date'])) {
            $query .= " AND DATE(created_at) BETWEEN %s AND %s";
            $params[] = $args['start_date'];
            $params[] = $args['end_date'];
        }

        $query = $wpdb->prepare($query, $params);
        $total = $wpdb->get_var($query);

        return (int) $total;
    }

    /**
     * Получение количества баллов, начисленных за прохождение нескольких уроков в день
     *
     * @param int    $user_id ID пользователя
     * @param string $date    Дата (формат Y-m-d)
     * @return int
     */
    public function get_user_multi_lesson_points_for_day($user_id, $date) {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT SUM(points) FROM {$this->table_name} 
            WHERE user_id = %d AND DATE(created_at) = %s AND points_type = 'multi_lesson'",
            $user_id,
            $date
        );

        $total = $wpdb->get_var($query);

        return (int) $total;
    }

    /**
     * Получение количества баллов, начисленных за серию
     *
     * @param int    $user_id ID пользователя
     * @param string $date    Дата (формат Y-m-d)
     * @return int
     */
    public function get_user_streak_points_for_day($user_id, $date) {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT SUM(points) FROM {$this->table_name} 
            WHERE user_id = %d AND DATE(created_at) = %s AND points_type = 'streak'",
            $user_id,
            $date
        );

        $total = $wpdb->get_var($query);

        return (int) $total;
    }

    /**
     * Проверка, были ли начислены баллы за прохождение урока
     *
     * @param int    $user_id   ID пользователя
     * @param int    $lesson_id ID урока
     * @param string $date      Дата (формат Y-m-d)
     * @return bool
     */
    public function has_lesson_points_for_day($user_id, $lesson_id, $date) {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} 
            WHERE user_id = %d AND lesson_id = %d AND DATE(created_at) = %s AND points_type = 'lesson'",
            $user_id,
            $lesson_id,
            $date
        );

        $count = $wpdb->get_var($query);

        return (int) $count > 0;
    }

    /**
     * Получение количества уроков, пройденных пользователем за день
     *
     * @param int    $user_id ID пользователя
     * @param string $date    Дата (формат Y-m-d)
     * @return int
     */
    public function get_user_lessons_completed_for_day($user_id, $date) {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} 
            WHERE user_id = %d AND DATE(created_at) = %s AND points_type = 'lesson'",
            $user_id,
            $date
        );

        $count = $wpdb->get_var($query);

        return (int) $count;
    }

    /**
     * Получение пользователей с наибольшим количеством баллов
     *
     * @param int   $limit Количество пользователей
     * @param array $args  Дополнительные аргументы
     * @return array
     */
    public function get_top_users_by_points($limit = 10, $args = []) {
        global $wpdb;

        $defaults = [
            'points_type' => '',
            'start_date' => '',
            'end_date' => '',
        ];

        $args = wp_parse_args($args, $defaults);

        $query = "
            SELECT ph.user_id, u.display_name, SUM(ph.points) as total_points
            FROM {$this->table_name} ph
            INNER JOIN {$wpdb->users} u ON ph.user_id = u.ID
            WHERE 1=1
        ";
        $params = [];

        // Фильтрация по типу баллов
        if (!empty($args['points_type'])) {
            $query .= " AND ph.points_type = %s";
            $params[] = $args['points_type'];
        }

        // Фильтрация по периоду
        if (!empty($args['start_date']) && !empty($args['end_date'])) {
            $query .= " AND DATE(ph.created_at) BETWEEN %s AND %s";
            $params[] = $args['start_date'];
            $params[] = $args['end_date'];
        }

        $query .= " GROUP BY ph.user_id, u.display_name ORDER BY total_points DESC LIMIT %d";
        $params[] = $limit;

        $query = $wpdb->prepare($query, $params);
        $results = $wpdb->get_results($query, ARRAY_A);

        return $results;
    }

    /**
     * Добавление баллов за прохождение урока
     *
     * @param int    $user_id    ID пользователя
     * @param int    $lesson_id  ID урока
     * @param int    $points     Количество баллов
     * @param string $description Описание начисления
     * @return int|false
     */
    public function add_lesson_points($user_id, $lesson_id, $points, $description = '') {
        $data = [
            'user_id' => $user_id,
            'lesson_id' => $lesson_id,
            'points' => $points,
            'points_type' => 'lesson',
            'description' => $description,
            'created_at' => current_time('mysql'),
        ];

        return $this->create($data);
    }

    /**
     * Добавление баллов за серию
     *
     * @param int    $user_id    ID пользователя
     * @param int    $points     Количество баллов
     * @param int    $streak_day День серии
     * @param string $description Описание начисления
     * @return int|false
     */
    public function add_streak_points($user_id, $points, $streak_day, $description = '') {
        $data = [
            'user_id' => $user_id,
            'points' => $points,
            'points_type' => 'streak',
            'streak_day' => $streak_day,
            'description' => $description,
            'created_at' => current_time('mysql'),
        ];

        return $this->create($data);
    }

    /**
     * Добавление баллов за прохождение нескольких уроков в день
     *
     * @param int    $user_id           ID пользователя
     * @param int    $lesson_id         ID урока
     * @param int    $points            Количество баллов
     * @param int    $lesson_number_today Номер урока за день
     * @param string $description       Описание начисления
     * @return int|false
     */
    public function add_multi_lesson_points($user_id, $lesson_id, $points, $lesson_number_today, $description = '') {
        $data = [
            'user_id' => $user_id,
            'lesson_id' => $lesson_id,
            'points' => $points,
            'points_type' => 'multi_lesson',
            'lesson_number_today' => $lesson_number_today,
            'description' => $description,
            'created_at' => current_time('mysql'),
        ];

        return $this->create($data);
    }

    /**
     * Добавление баллов за завершение курса
     *
     * @param int    $user_id    ID пользователя
     * @param int    $points     Количество баллов
     * @param string $description Описание начисления
     * @return int|false
     */
    public function add_course_completion_points($user_id, $points, $description = '') {
        $data = [
            'user_id' => $user_id,
            'points' => $points,
            'points_type' => 'course_completion',
            'description' => $description,
            'created_at' => current_time('mysql'),
        ];

        return $this->create($data);
    }
}
