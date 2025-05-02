<?php
/**
 * Репозиторий уроков
 *
 * @package CryptoSchool
 * @subpackage Repositories
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс репозитория уроков
 */
class CryptoSchool_Repository_Lesson extends CryptoSchool_Repository {
    /**
     * Имя таблицы в базе данных
     *
     * @var string
     */
    protected $table_name = 'cryptoschool_lessons';

    /**
     * Имя класса модели
     *
     * @var string
     */
    protected $model_class = 'CryptoSchool_Model_Lesson';

    /**
     * Получение уроков курса
     *
     * @param int   $course_id ID курса
     * @param array $args      Дополнительные аргументы
     * @return array
     */
    public function get_course_lessons($course_id, $args = []) {
        global $wpdb;

        $defaults = [
            'orderby' => 'lesson_order',
            'order'   => 'ASC',
            'limit'   => 0,
            'offset'  => 0,
        ];

        $args = wp_parse_args($args, $defaults);

        $query = "SELECT * FROM {$this->table_name} WHERE course_id = %d";
        $params = [$course_id];

        // Добавление условия активности
        if (isset($args['is_active'])) {
            $query .= " AND is_active = %d";
            $params[] = $args['is_active'];
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
     * Получение количества уроков курса
     *
     * @param int   $course_id ID курса
     * @param array $args      Дополнительные аргументы
     * @return int
     */
    public function get_course_lessons_count($course_id, $args = []) {
        global $wpdb;

        $query = "SELECT COUNT(*) FROM {$this->table_name} WHERE course_id = %d";
        $params = [$course_id];

        // Добавление условия активности
        if (isset($args['is_active'])) {
            $query .= " AND is_active = %d";
            $params[] = $args['is_active'];
        }

        $query = $wpdb->prepare($query, $params);
        return (int) $wpdb->get_var($query);
    }

    /**
     * Получение урока по слагу
     *
     * @param string $slug Слаг урока
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
     * Обновление порядка уроков
     *
     * @param array $lesson_orders Массив с ID уроков и их порядком
     * @return bool
     */
    public function update_order($lesson_orders) {
        global $wpdb;

        foreach ($lesson_orders as $lesson_id => $order) {
            $wpdb->update(
                $this->table_name,
                ['lesson_order' => $order],
                ['id' => $lesson_id]
            );
        }

        return true;
    }

    /**
     * Получение следующего урока
     *
     * @param int $lesson_id ID текущего урока
     * @return mixed
     */
    public function get_next_lesson($lesson_id) {
        global $wpdb;

        $lesson = $this->find($lesson_id);
        if (!$lesson) {
            return null;
        }

        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
            WHERE course_id = %d AND lesson_order > %d AND is_active = 1
            ORDER BY lesson_order ASC
            LIMIT 1",
            $lesson->course_id,
            $lesson->lesson_order
        );

        $result = $wpdb->get_row($query, ARRAY_A);

        if (!$result) {
            return null;
        }

        return $this->mapToModel($result);
    }

    /**
     * Получение предыдущего урока
     *
     * @param int $lesson_id ID текущего урока
     * @return mixed
     */
    public function get_previous_lesson($lesson_id) {
        global $wpdb;

        $lesson = $this->find($lesson_id);
        if (!$lesson) {
            return null;
        }

        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
            WHERE course_id = %d AND lesson_order < %d AND is_active = 1
            ORDER BY lesson_order DESC
            LIMIT 1",
            $lesson->course_id,
            $lesson->lesson_order
        );

        $result = $wpdb->get_row($query, ARRAY_A);

        if (!$result) {
            return null;
        }

        return $this->mapToModel($result);
    }

    /**
     * Получение первого урока курса
     *
     * @param int $course_id ID курса
     * @return mixed
     */
    public function get_first_lesson($course_id) {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
            WHERE course_id = %d AND is_active = 1
            ORDER BY lesson_order ASC
            LIMIT 1",
            $course_id
        );

        $result = $wpdb->get_row($query, ARRAY_A);

        if (!$result) {
            return null;
        }

        return $this->mapToModel($result);
    }

    /**
     * Получение последнего урока курса
     *
     * @param int $course_id ID курса
     * @return mixed
     */
    public function get_last_lesson($course_id) {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
            WHERE course_id = %d AND is_active = 1
            ORDER BY lesson_order DESC
            LIMIT 1",
            $course_id
        );

        $result = $wpdb->get_row($query, ARRAY_A);

        if (!$result) {
            return null;
        }

        return $this->mapToModel($result);
    }

    /**
     * Генерация уникального слага для урока
     *
     * @param string $title     Название урока
     * @param int    $course_id ID курса
     * @param int    $lesson_id ID урока (для обновления)
     * @return string
     */
    public function generate_unique_slug($title, $course_id, $lesson_id = 0) {
        // Используем общий хелпер для генерации уникального слага
        return CryptoSchool_Helper_String::generate_unique_slug($title, $this->table_name, $lesson_id);
    }
}
