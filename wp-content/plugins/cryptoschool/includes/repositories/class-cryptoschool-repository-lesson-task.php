<?php
/**
 * Репозиторий заданий уроков
 *
 * @package CryptoSchool
 * @subpackage Repositories
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс репозитория заданий уроков
 */
class CryptoSchool_Repository_Lesson_Task extends CryptoSchool_Repository {
    /**
     * Имя таблицы в базе данных
     *
     * @var string
     */
    protected $table_name = 'cryptoschool_lesson_tasks';

    /**
     * Имя класса модели
     *
     * @var string
     */
    protected $model_class = 'CryptoSchool_Model_Lesson_Task';

    /**
     * Получение заданий урока
     *
     * @param int   $lesson_id ID урока
     * @param array $args      Дополнительные аргументы
     * @return array
     */
    public function get_lesson_tasks($lesson_id, $args = []) {
        global $wpdb;

        $defaults = [
            'orderby' => 'task_order',
            'order'   => 'ASC',
            'limit'   => 0,
            'offset'  => 0,
        ];

        $args = wp_parse_args($args, $defaults);

        $query = "SELECT * FROM {$this->table_name} WHERE lesson_id = %d";
        $params = [$lesson_id];

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
     * Получение количества заданий урока
     *
     * @param int $lesson_id ID урока
     * @return int
     */
    public function get_lesson_tasks_count($lesson_id) {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE lesson_id = %d",
            $lesson_id
        );

        return (int) $wpdb->get_var($query);
    }

    /**
     * Создание задания урока
     *
     * @param array $data Данные задания
     * @return int|false
     */
    public function create_task($data) {
        global $wpdb;

        $defaults = [
            'lesson_id' => 0,
            'title' => '',
            'task_order' => 0,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];

        $data = wp_parse_args($data, $defaults);

        $result = $wpdb->insert($this->table_name, $data);

        if ($result === false) {
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Обновление задания урока
     *
     * @param int   $task_id ID задания
     * @param array $data    Данные для обновления
     * @return bool
     */
    public function update_task($task_id, $data) {
        global $wpdb;

        $data['updated_at'] = current_time('mysql');

        $result = $wpdb->update(
            $this->table_name,
            $data,
            ['id' => $task_id]
        );

        return $result !== false;
    }

    /**
     * Удаление задания урока
     *
     * @param int $task_id ID задания
     * @return bool
     */
    public function delete_task($task_id) {
        global $wpdb;

        $result = $wpdb->delete(
            $this->table_name,
            ['id' => $task_id]
        );

        return $result !== false;
    }

    /**
     * Обновление порядка заданий
     *
     * @param array $task_orders Массив с ID заданий и их порядком
     * @return bool
     */
    public function update_order($task_orders) {
        global $wpdb;

        foreach ($task_orders as $task_id => $order) {
            $wpdb->update(
                $this->table_name,
                ['task_order' => $order],
                ['id' => $task_id]
            );
        }

        return true;
    }

    /**
     * Получение заданий урока с прогрессом пользователя
     *
     * @param int $lesson_id ID урока
     * @param int $user_id   ID пользователя
     * @param array $args    Дополнительные аргументы
     * @return array
     */
    public function get_lesson_tasks_with_progress($lesson_id, $user_id, $args = []) {
        global $wpdb;

        $defaults = [
            'orderby' => 't.task_order',
            'order'   => 'ASC',
            'limit'   => 0,
            'offset'  => 0,
        ];

        $args = wp_parse_args($args, $defaults);

        $progress_table = $wpdb->prefix . 'cryptoschool_user_task_progress';

        $query = "
            SELECT t.*, p.is_completed, p.completed_at
            FROM {$this->table_name} t
            LEFT JOIN {$progress_table} p ON t.id = p.task_id AND p.user_id = %d
            WHERE t.lesson_id = %d
        ";
        $params = [$user_id, $lesson_id];

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

        $tasks = [];
        foreach ($results as $result) {
            $task = $this->mapToModel($result);
            $tasks[] = [
                'task' => $task,
                'is_completed' => (bool) $result['is_completed'],
                'completed_at' => $result['completed_at']
            ];
        }

        return $tasks;
    }
}
