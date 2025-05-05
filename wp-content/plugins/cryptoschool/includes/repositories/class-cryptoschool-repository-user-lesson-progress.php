<?php
/**
 * Репозиторий прогресса пользователя по урокам
 *
 * @package CryptoSchool
 * @subpackage Repositories
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс репозитория прогресса пользователя по урокам
 */
class CryptoSchool_Repository_User_Lesson_Progress extends CryptoSchool_Repository {
    /**
     * Имя таблицы в базе данных
     *
     * @var string
     */
    protected $table_name = 'cryptoschool_user_lesson_progress';

    /**
     * Имя класса модели
     *
     * @var string
     */
    protected $model_class = 'CryptoSchool_Model_User_Lesson_Progress';

    /**
     * Получение прогресса пользователя по уроку
     *
     * @param int $user_id   ID пользователя
     * @param int $lesson_id ID урока
     * @return CryptoSchool_Model_User_Lesson_Progress|null
     */
    public function get_user_lesson_progress($user_id, $lesson_id) {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE user_id = %d AND lesson_id = %d",
            $user_id,
            $lesson_id
        );

        $result = $wpdb->get_row($query, ARRAY_A);

        if (!$result) {
            return null;
        }

        return $this->mapToModel($result);
    }

    /**
     * Получение прогресса пользователя по всем урокам
     *
     * @param int   $user_id ID пользователя
     * @param array $args    Дополнительные аргументы
     * @return array
     */
    public function get_user_lessons_progress($user_id, $args = []) {
        global $wpdb;

        $defaults = [
            'orderby' => 'lesson_id',
            'order'   => 'ASC',
            'limit'   => 0,
            'offset'  => 0,
        ];

        $args = wp_parse_args($args, $defaults);

        $query = "SELECT * FROM {$this->table_name} WHERE user_id = %d";
        $params = [$user_id];

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
     * Получение прогресса пользователя по урокам курса
     *
     * @param int   $user_id   ID пользователя
     * @param int   $course_id ID курса
     * @param array $args      Дополнительные аргументы
     * @return array
     */
    public function get_user_course_lessons_progress($user_id, $course_id, $args = []) {
        global $wpdb;

        $defaults = [
            'orderby' => 'l.lesson_order',
            'order'   => 'ASC',
            'limit'   => 0,
            'offset'  => 0,
        ];

        $args = wp_parse_args($args, $defaults);

        $lessons_table = $wpdb->prefix . 'cryptoschool_lessons';

        $query = "
            SELECT p.*, l.title as lesson_title, l.lesson_order
            FROM {$this->table_name} p
            INNER JOIN {$lessons_table} l ON p.lesson_id = l.id
            WHERE p.user_id = %d AND l.course_id = %d
        ";
        $params = [$user_id, $course_id];

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
     * Получение общего прогресса пользователя по курсу
     *
     * @param int $user_id   ID пользователя
     * @param int $course_id ID курса
     * @return float
     */
    public function get_user_course_progress($user_id, $course_id) {
        global $wpdb;

        $lessons_table = $wpdb->prefix . 'cryptoschool_lessons';

        // Получаем общее количество уроков в курсе
        $total_lessons = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$lessons_table} WHERE course_id = %d",
                $course_id
            )
        );

        if (!$total_lessons) {
            return 0;
        }

        // Получаем количество выполненных уроков
        $completed_lessons = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} p
                INNER JOIN {$lessons_table} l ON p.lesson_id = l.id
                WHERE p.user_id = %d AND l.course_id = %d AND p.is_completed = 1",
                $user_id,
                $course_id
            )
        );

        // Рассчитываем процент прогресса
        return ($completed_lessons / $total_lessons) * 100;
    }

    /**
     * Создание или обновление прогресса пользователя по уроку
     *
     * @param int   $user_id         ID пользователя
     * @param int   $lesson_id       ID урока
     * @param array $data            Данные прогресса
     * @return int|false
     */
    public function create_or_update_progress($user_id, $lesson_id, $data) {
        global $wpdb;

        // Проверяем, существует ли запись о прогрессе
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$this->table_name} WHERE user_id = %d AND lesson_id = %d",
                $user_id,
                $lesson_id
            )
        );

        $defaults = [
            'user_id' => $user_id,
            'lesson_id' => $lesson_id,
            'is_completed' => 0,
            'progress_percent' => 0,
            'completed_at' => null,
            'updated_at' => current_time('mysql'),
        ];

        $data = wp_parse_args($data, $defaults);

        if ($exists) {
            // Обновляем существующую запись
            $result = $wpdb->update(
                $this->table_name,
                $data,
                [
                    'user_id' => $user_id,
                    'lesson_id' => $lesson_id
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
     * Отметка урока как выполненного
     *
     * @param int $user_id   ID пользователя
     * @param int $lesson_id ID урока
     * @return bool
     */
    public function mark_lesson_as_completed($user_id, $lesson_id) {
        $now = current_time('mysql');

        return $this->create_or_update_progress($user_id, $lesson_id, [
            'is_completed' => 1,
            'progress_percent' => 100,
            'completed_at' => $now,
        ]) !== false;
    }

    /**
     * Отметка урока как невыполненного
     *
     * @param int $user_id   ID пользователя
     * @param int $lesson_id ID урока
     * @return bool
     */
    public function mark_lesson_as_uncompleted($user_id, $lesson_id) {
        return $this->create_or_update_progress($user_id, $lesson_id, [
            'is_completed' => 0,
            'completed_at' => null,
        ]) !== false;
    }

    /**
     * Обновление прогресса урока
     *
     * @param int $user_id         ID пользователя
     * @param int $lesson_id       ID урока
     * @param int $progress_percent Процент прогресса
     * @return bool
     */
    public function update_lesson_progress($user_id, $lesson_id, $progress_percent) {
        $data = [
            'progress_percent' => $progress_percent,
        ];

        // Если прогресс достиг 100%, отмечаем урок как выполненный
        if ($progress_percent >= 100) {
            $data['is_completed'] = 1;
            $data['completed_at'] = current_time('mysql');
        }

        return $this->create_or_update_progress($user_id, $lesson_id, $data) !== false;
    }

    /**
     * Обновление прогресса пользователя по уроку
     *
     * @param int  $user_id         ID пользователя
     * @param int  $lesson_id       ID урока
     * @param bool $is_completed    Статус выполнения
     * @param int  $progress_percent Процент прогресса
     * @return bool
     */
    public function update_progress($user_id, $lesson_id, $is_completed, $progress_percent) {
        $now = current_time('mysql');
        
        $data = [
            'user_id' => $user_id,
            'lesson_id' => $lesson_id,
            'is_completed' => $is_completed ? 1 : 0,
            'progress_percent' => $progress_percent,
            'completed_at' => $is_completed ? $now : null,
            'updated_at' => $now,
        ];
        
        return $this->create_or_update_progress($user_id, $lesson_id, $data) !== false;
    }
}
