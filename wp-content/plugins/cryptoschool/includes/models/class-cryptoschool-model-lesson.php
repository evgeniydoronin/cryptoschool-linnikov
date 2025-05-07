<?php
/**
 * Модель урока
 *
 * @package CryptoSchool
 * @subpackage Models
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс модели урока
 */
class CryptoSchool_Model_Lesson extends CryptoSchool_Model {
    /**
     * Заполняемые атрибуты
     *
     * @var array
     */
    protected $fillable = [
        'course_id',
        'title',
        'content',
        'video_url',
        'lesson_order',
        'slug',
        'completion_points',
        'completion_tasks',
        'is_active',
        'duration',
    ];

    /**
     * Получение курса, к которому относится урок
     *
     * @return mixed
     */
    public function get_course() {
        $repository = new CryptoSchool_Repository_Course();
        return $repository->find($this->course_id);
    }

    /**
     * Получение следующего урока
     *
     * @return mixed
     */
    public function get_next_lesson() {
        $repository = new CryptoSchool_Repository_Lesson();
        return $repository->get_next_lesson($this->id);
    }

    /**
     * Получение предыдущего урока
     *
     * @return mixed
     */
    public function get_previous_lesson() {
        $repository = new CryptoSchool_Repository_Lesson();
        return $repository->get_previous_lesson($this->id);
    }

    /**
     * Получение URL урока
     *
     * @return string
     */
    public function get_url() {
        $course = $this->get_course();
        if (!$course) {
            return '';
        }

        return home_url('/course/' . $course->slug . '/lesson/' . $this->slug);
    }

    /**
     * Получение статуса урока
     *
     * @return string
     */
    public function get_status_label() {
        return $this->is_active ? __('Активен', 'cryptoschool') : __('Неактивен', 'cryptoschool');
    }

    /**
     * Получение форматированной даты создания урока
     *
     * @param string $format Формат даты
     * @return string
     */
    public function get_created_at($format = 'd.m.Y') {
        return date_i18n($format, strtotime($this->created_at));
    }

    /**
     * Получение форматированной даты обновления урока
     *
     * @param string $format Формат даты
     * @return string
     */
    public function get_updated_at($format = 'd.m.Y') {
        return date_i18n($format, strtotime($this->updated_at));
    }

    /**
     * Получение форматированной продолжительности урока
     *
     * @return string
     */
    public function get_duration_formatted() {
        if (empty($this->duration) || $this->duration <= 0) {
            return '';
        }

        $hours = floor($this->duration / 60);
        $minutes = $this->duration % 60;

        if ($hours > 0) {
            return sprintf(
                _n('%d час %d минута', '%d часов %d минут', $hours, 'cryptoschool'),
                $hours,
                $minutes
            );
        } else {
            return sprintf(
                _n('%d минута', '%d минут', $minutes, 'cryptoschool'),
                $minutes
            );
        }
    }

    /**
     * Получение задач для выполнения
     *
     * @return array
     */
    public function get_completion_tasks() {
        if (empty($this->completion_tasks)) {
            return [];
        }

        return json_decode($this->completion_tasks, true);
    }

    /**
     * Проверка, выполнил ли пользователь урок
     *
     * @param int $user_id ID пользователя
     * @return bool
     */
    public function is_completed_by_user($user_id) {
        global $wpdb;
        $progress_table = $wpdb->prefix . 'cryptoschool_user_lesson_progress';

        $query = $wpdb->prepare(
            "SELECT is_completed FROM {$progress_table} WHERE user_id = %d AND lesson_id = %d",
            $user_id,
            $this->id
        );

        $is_completed = $wpdb->get_var($query);

        return (bool) $is_completed;
    }

    /**
     * Получение прогресса пользователя по уроку
     *
     * @param int $user_id ID пользователя
     * @return array
     */
    public function get_user_progress($user_id) {
        global $wpdb;
        $progress_table = $wpdb->prefix . 'cryptoschool_user_lesson_progress';
        $task_progress_table = $wpdb->prefix . 'cryptoschool_user_task_progress';

        // Получаем прогресс по уроку
        $query = $wpdb->prepare(
            "SELECT * FROM {$progress_table} WHERE user_id = %d AND lesson_id = %d",
            $user_id,
            $this->id
        );

        $progress = $wpdb->get_row($query, ARRAY_A);

        if (!$progress) {
            return [
                'is_completed' => false,
                'progress_percent' => 0,
                'completed_at' => null,
                'completed_tasks' => [],
            ];
        }

        // Получаем выполненные задания
        $query = $wpdb->prepare(
            "SELECT task_id FROM {$task_progress_table} 
            WHERE user_id = %d AND lesson_id = %d AND is_completed = 1",
            $user_id,
            $this->id
        );

        $completed_task_ids = $wpdb->get_col($query);

        $progress['completed_tasks'] = $completed_task_ids;

        return $progress;
    }

    /**
     * Отметка урока как просмотренного пользователем
     *
     * @param int $user_id ID пользователя
     * @return bool
     */
    public function mark_as_viewed_by_user($user_id) {
        global $wpdb;
        $progress_table = $wpdb->prefix . 'cryptoschool_user_lesson_progress';

        $query = $wpdb->prepare(
            "SELECT id FROM {$progress_table} WHERE user_id = %d AND lesson_id = %d",
            $user_id,
            $this->id
        );

        $progress_id = $wpdb->get_var($query);

        if ($progress_id) {
            // Обновление существующей записи
            $wpdb->update(
                $progress_table,
                [
                    'progress_percent' => 50, // Устанавливаем прогресс 50% для просмотренного урока
                    'updated_at' => current_time('mysql'),
                ],
                [
                    'id' => $progress_id,
                ]
            );
        } else {
            // Создание новой записи
            $wpdb->insert(
                $progress_table,
                [
                    'user_id' => $user_id,
                    'lesson_id' => $this->id,
                    'is_completed' => 0,
                    'progress_percent' => 50, // Устанавливаем прогресс 50% для просмотренного урока
                    'updated_at' => current_time('mysql'),
                ]
            );
        }

        // Обновление таблицы активностей
        $activities_table = $wpdb->prefix . 'cryptoschool_recent_activities';
        $wpdb->insert(
            $activities_table,
            [
                'user_id' => $user_id,
                'activity_type' => 'lesson_start',
                'ref_id' => $this->id,
                'title' => $this->title,
                'status' => 'opened',
                'created_at' => current_time('mysql'),
            ]
        );

        return true;
    }

    /**
     * Отметка урока как выполненного пользователем
     *
     * @param int   $user_id        ID пользователя
     * @param array $completed_tasks Выполненные задачи
     * @return bool
     */
    public function mark_as_completed_by_user($user_id, $completed_tasks = []) {
        global $wpdb;
        $progress_table = $wpdb->prefix . 'cryptoschool_user_lesson_progress';
        $task_progress_table = $wpdb->prefix . 'cryptoschool_user_task_progress';

        // Получаем ID записи о прогрессе
        $query = $wpdb->prepare(
            "SELECT id FROM {$progress_table} WHERE user_id = %d AND lesson_id = %d",
            $user_id,
            $this->id
        );

        $progress_id = $wpdb->get_var($query);

        // Текущее время
        $current_time = current_time('mysql');

        if ($progress_id) {
            // Обновление существующей записи
            $wpdb->update(
                $progress_table,
                [
                    'is_completed' => 1,
                    'progress_percent' => 100,
                    'completed_at' => $current_time,
                    'updated_at' => $current_time,
                ],
                [
                    'id' => $progress_id,
                ]
            );
        } else {
            // Создание новой записи
            $wpdb->insert(
                $progress_table,
                [
                    'user_id' => $user_id,
                    'lesson_id' => $this->id,
                    'is_completed' => 1,
                    'progress_percent' => 100,
                    'completed_at' => $current_time,
                    'updated_at' => $current_time,
                ]
            );
        }

        // Обновление прогресса по заданиям
        if (!empty($completed_tasks)) {
            foreach ($completed_tasks as $task_id) {
                // Проверяем, существует ли запись о прогрессе по заданию
                $query = $wpdb->prepare(
                    "SELECT id FROM {$task_progress_table} WHERE user_id = %d AND lesson_id = %d AND task_id = %d",
                    $user_id,
                    $this->id,
                    $task_id
                );
                
                $task_progress_id = $wpdb->get_var($query);
                
                if ($task_progress_id) {
                    // Обновление существующей записи
                    $wpdb->update(
                        $task_progress_table,
                        [
                            'is_completed' => 1,
                            'completed_at' => $current_time,
                        ],
                        [
                            'id' => $task_progress_id,
                        ]
                    );
                } else {
                    // Создание новой записи
                    $wpdb->insert(
                        $task_progress_table,
                        [
                            'user_id' => $user_id,
                            'lesson_id' => $this->id,
                            'task_id' => $task_id,
                            'is_completed' => 1,
                            'completed_at' => $current_time,
                        ]
                    );
                }
            }
        }

        // Обновление таблицы активностей
        $activities_table = $wpdb->prefix . 'cryptoschool_recent_activities';
        $wpdb->insert(
            $activities_table,
            [
                'user_id' => $user_id,
                'activity_type' => 'lesson_complete',
                'ref_id' => $this->id,
                'title' => $this->title,
                'status' => 'completed',
                'created_at' => $current_time,
            ]
        );

        // Обновление таблицы рейтинга
        $this->update_user_leaderboard($user_id);

        return true;
    }

    /**
     * Обновление рейтинга пользователя
     *
     * @param int $user_id ID пользователя
     * @return bool
     */
    private function update_user_leaderboard($user_id) {
        global $wpdb;
        $leaderboard_table = $wpdb->prefix . 'cryptoschool_user_leaderboard';
        $progress_table = $wpdb->prefix . 'cryptoschool_user_lesson_progress';
        $lessons_table = $wpdb->prefix . 'cryptoschool_lessons';

        // Получение общего количества баллов пользователя
        // Баллы рассчитываются как сумма completion_points для всех завершенных уроков
        $query = $wpdb->prepare(
            "SELECT SUM(l.completion_points) 
            FROM {$progress_table} p
            INNER JOIN {$lessons_table} l ON p.lesson_id = l.id
            WHERE p.user_id = %d AND p.is_completed = 1",
            $user_id
        );
        $total_points = (int) $wpdb->get_var($query);

        // Получение количества завершенных уроков
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$progress_table} WHERE user_id = %d AND is_completed = 1",
            $user_id
        );
        $completed_lessons = (int) $wpdb->get_var($query);

        // Получение количества дней на проекте
        $query = $wpdb->prepare(
            "SELECT DATEDIFF(NOW(), MIN(updated_at)) FROM {$progress_table} WHERE user_id = %d",
            $user_id
        );
        $days_active = (int) $wpdb->get_var($query);
        $days_active = max(1, $days_active); // Минимум 1 день

        // Проверка, существует ли запись в таблице рейтинга
        $query = $wpdb->prepare(
            "SELECT id FROM {$leaderboard_table} WHERE user_id = %d",
            $user_id
        );
        $leaderboard_id = $wpdb->get_var($query);

        if ($leaderboard_id) {
            // Обновление существующей записи
            $wpdb->update(
                $leaderboard_table,
                [
                    'total_points' => $total_points,
                    'completed_lessons' => $completed_lessons,
                    'days_active' => $days_active,
                    'last_updated' => current_time('mysql'),
                ],
                [
                    'id' => $leaderboard_id,
                ]
            );
        } else {
            // Создание новой записи
            $wpdb->insert(
                $leaderboard_table,
                [
                    'user_id' => $user_id,
                    'total_points' => $total_points,
                    'completed_lessons' => $completed_lessons,
                    'days_active' => $days_active,
                    'last_updated' => current_time('mysql'),
                ]
            );
        }

        // Обновление рангов всех пользователей
        $wpdb->query(
            "SET @rank = 0; 
            UPDATE {$leaderboard_table} 
            SET rank = (@rank := @rank + 1) 
            ORDER BY total_points DESC, completed_lessons DESC, days_active ASC"
        );

        return true;
    }
}
