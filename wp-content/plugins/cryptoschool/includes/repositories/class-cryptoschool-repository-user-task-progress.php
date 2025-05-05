<?php
/**
 * Репозиторий прогресса пользователя по заданиям
 *
 * @package CryptoSchool
 * @subpackage Repositories
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс репозитория прогресса пользователя по заданиям
 */
class CryptoSchool_Repository_User_Task_Progress extends CryptoSchool_Repository {
    /**
     * Имя таблицы в базе данных
     *
     * @var string
     */
    protected $table_name = 'cryptoschool_user_task_progress';

    /**
     * Имя класса модели
     *
     * @var string
     */
    protected $model_class = 'CryptoSchool_Model_User_Task_Progress';

    /**
     * Получение прогресса пользователя по заданию
     *
     * @param int $user_id ID пользователя
     * @param int $task_id ID задания
     * @return CryptoSchool_Model_User_Task_Progress|null
     */
    public function get_user_task_progress($user_id, $task_id) {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE user_id = %d AND task_id = %d",
            $user_id,
            $task_id
        );

        $result = $wpdb->get_row($query, ARRAY_A);

        if (!$result) {
            return null;
        }

        return $this->mapToModel($result);
    }

    /**
     * Получение прогресса пользователя по всем заданиям урока
     *
     * @param int   $user_id   ID пользователя
     * @param int   $lesson_id ID урока
     * @param array $args      Дополнительные аргументы
     * @return array
     */
    public function get_user_lesson_tasks_progress($user_id, $lesson_id, $args = []) {
        global $wpdb;

        $defaults = [
            'orderby' => 'task_id',
            'order'   => 'ASC',
            'limit'   => 0,
            'offset'  => 0,
        ];

        $args = wp_parse_args($args, $defaults);

        $query = "SELECT * FROM {$this->table_name} WHERE user_id = %d AND lesson_id = %d";
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

        return $this->mapToModels($results);
    }

    /**
     * Получение количества выполненных заданий урока
     *
     * @param int $user_id   ID пользователя
     * @param int $lesson_id ID урока
     * @return int
     */
    public function get_completed_tasks_count($user_id, $lesson_id) {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE user_id = %d AND lesson_id = %d AND is_completed = 1",
            $user_id,
            $lesson_id
        );

        return (int) $wpdb->get_var($query);
    }

    /**
     * Проверка, выполнены ли все задания урока
     *
     * @param int $user_id   ID пользователя
     * @param int $lesson_id ID урока
     * @return bool
     */
    public function are_all_tasks_completed($user_id, $lesson_id) {
        global $wpdb;

        $tasks_table = $wpdb->prefix . 'cryptoschool_lesson_tasks';

        // Получаем общее количество заданий для урока
        $total_tasks = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$tasks_table} WHERE lesson_id = %d",
                $lesson_id
            )
        );

        if (!$total_tasks) {
            return true; // Если заданий нет, считаем, что все выполнены
        }

        // Получаем количество выполненных заданий
        $completed_tasks = $this->get_completed_tasks_count($user_id, $lesson_id);

        return $completed_tasks >= $total_tasks;
    }

    /**
     * Создание или обновление прогресса пользователя по заданию
     *
     * @param int   $user_id   ID пользователя
     * @param int   $task_id   ID задания
     * @param array $data      Данные прогресса
     * @return int|false
     */
    public function create_or_update_progress($user_id, $task_id, $data) {
        global $wpdb;

        // Получаем информацию о задании
        $tasks_table = $wpdb->prefix . 'cryptoschool_lesson_tasks';
        $task = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$tasks_table} WHERE id = %d",
                $task_id
            ),
            ARRAY_A
        );

        if (!$task) {
            return false;
        }

        // Проверяем, существует ли запись о прогрессе
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$this->table_name} WHERE user_id = %d AND task_id = %d",
                $user_id,
                $task_id
            )
        );

        $defaults = [
            'user_id' => $user_id,
            'lesson_id' => $task['lesson_id'],
            'task_id' => $task_id,
            'is_completed' => 0,
            'completed_at' => null,
        ];

        $data = wp_parse_args($data, $defaults);

        if ($exists) {
            // Обновляем существующую запись
            $result = $wpdb->update(
                $this->table_name,
                $data,
                [
                    'user_id' => $user_id,
                    'task_id' => $task_id
                ]
            );

            return $result !== false ? $exists : false;
        } else {
            // Создаем новую запись
            $result = $wpdb->insert($this->table_name, $data);

            return $result !== false ? $wpdb->insert_id : false;
        }
    }

    /**
     * Отметка задания как выполненного
     *
     * @param int $user_id ID пользователя
     * @param int $task_id ID задания
     * @return bool
     */
    public function mark_task_as_completed($user_id, $task_id) {
        $now = current_time('mysql');

        return $this->create_or_update_progress($user_id, $task_id, [
            'is_completed' => 1,
            'completed_at' => $now,
        ]) !== false;
    }

    /**
     * Отметка задания как невыполненного
     *
     * @param int $user_id ID пользователя
     * @param int $task_id ID задания
     * @return bool
     */
    public function mark_task_as_uncompleted($user_id, $task_id) {
        return $this->create_or_update_progress($user_id, $task_id, [
            'is_completed' => 0,
            'completed_at' => null,
        ]) !== false;
    }

    /**
     * Отметка всех заданий урока как выполненных
     *
     * @param int $user_id   ID пользователя
     * @param int $lesson_id ID урока
     * @return bool
     */
    public function mark_all_lesson_tasks_as_completed($user_id, $lesson_id) {
        global $wpdb;

        $tasks_table = $wpdb->prefix . 'cryptoschool_lesson_tasks';
        $now = current_time('mysql');

        // Получаем все задания урока
        $tasks = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id FROM {$tasks_table} WHERE lesson_id = %d",
                $lesson_id
            ),
            ARRAY_A
        );

        if (!$tasks) {
            return true; // Если заданий нет, считаем операцию успешной
        }

        $success = true;

        foreach ($tasks as $task) {
            $result = $this->mark_task_as_completed($user_id, $task['id']);
            if (!$result) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Отметка всех заданий урока как невыполненных
     *
     * @param int $user_id   ID пользователя
     * @param int $lesson_id ID урока
     * @return bool
     */
    public function mark_all_lesson_tasks_as_uncompleted($user_id, $lesson_id) {
        global $wpdb;

        $tasks_table = $wpdb->prefix . 'cryptoschool_lesson_tasks';

        // Получаем все задания урока
        $tasks = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id FROM {$tasks_table} WHERE lesson_id = %d",
                $lesson_id
            ),
            ARRAY_A
        );

        if (!$tasks) {
            return true; // Если заданий нет, считаем операцию успешной
        }

        $success = true;

        foreach ($tasks as $task) {
            $result = $this->mark_task_as_uncompleted($user_id, $task['id']);
            if (!$result) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Обновление прогресса пользователя по заданию
     *
     * @param int  $user_id     ID пользователя
     * @param int  $lesson_id   ID урока
     * @param int  $task_id     ID задания
     * @param bool $is_completed Статус выполнения
     * @return bool
     */
    public function update_progress($user_id, $lesson_id, $task_id, $is_completed) {
        $now = current_time('mysql');
        
        $data = [
            'user_id' => $user_id,
            'lesson_id' => $lesson_id,
            'task_id' => $task_id,
            'is_completed' => $is_completed ? 1 : 0,
            'completed_at' => $is_completed ? $now : null,
        ];
        
        return $this->create_or_update_progress($user_id, $task_id, $data) !== false;
    }
}
