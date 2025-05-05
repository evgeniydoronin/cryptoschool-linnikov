<?php
/**
 * Модель прогресса пользователя по заданию
 *
 * @package CryptoSchool
 * @subpackage Models
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс модели прогресса пользователя по заданию
 */
class CryptoSchool_Model_User_Task_Progress extends CryptoSchool_Model {
    /**
     * Атрибуты модели
     *
     * @var array
     */
    protected $attributes = [
        'id' => null,
        'user_id' => null,
        'lesson_id' => null,
        'task_id' => null,
        'is_completed' => 0,
        'completed_at' => null
    ];

    /**
     * Получение пользователя
     *
     * @return WP_User|null
     */
    public function get_user() {
        return get_user_by('id', $this->getAttribute('user_id'));
    }

    /**
     * Получение урока
     *
     * @return CryptoSchool_Model_Lesson|null
     */
    public function get_lesson() {
        $lesson_repository = new CryptoSchool_Repository_Lesson();
        return $lesson_repository->find($this->getAttribute('lesson_id'));
    }

    /**
     * Получение задания
     *
     * @return CryptoSchool_Model_Lesson_Task|null
     */
    public function get_task() {
        $task_repository = new CryptoSchool_Repository_Lesson_Task();
        return $task_repository->find($this->getAttribute('task_id'));
    }

    /**
     * Отметка задания как выполненного
     *
     * @return bool
     */
    public function mark_as_completed() {
        $this->setAttribute('is_completed', 1);
        $this->setAttribute('completed_at', current_time('mysql'));
        
        return $this->save();
    }

    /**
     * Отметка задания как невыполненного
     *
     * @return bool
     */
    public function mark_as_uncompleted() {
        $this->setAttribute('is_completed', 0);
        $this->setAttribute('completed_at', null);
        
        return $this->save();
    }

    /**
     * Сохранение модели
     *
     * @return bool
     */
    public function save() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cryptoschool_user_task_progress';
        
        $data = [
            'user_id' => $this->getAttribute('user_id'),
            'lesson_id' => $this->getAttribute('lesson_id'),
            'task_id' => $this->getAttribute('task_id'),
            'is_completed' => $this->getAttribute('is_completed'),
            'completed_at' => $this->getAttribute('completed_at')
        ];
        
        if ($this->getAttribute('id')) {
            // Обновление существующей записи
            $result = $wpdb->update(
                $table_name,
                $data,
                ['id' => $this->getAttribute('id')]
            );
        } else {
            // Проверяем, существует ли запись о прогрессе
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$table_name} WHERE user_id = %d AND task_id = %d",
                    $this->getAttribute('user_id'),
                    $this->getAttribute('task_id')
                )
            );
            
            if ($exists) {
                // Обновляем существующую запись
                $result = $wpdb->update(
                    $table_name,
                    $data,
                    [
                        'user_id' => $this->getAttribute('user_id'),
                        'task_id' => $this->getAttribute('task_id')
                    ]
                );
                
                // Обновляем ID модели
                $this->setAttribute('id', $exists);
            } else {
                // Создаем новую запись
                $result = $wpdb->insert($table_name, $data);
                
                // Обновляем ID модели
                if ($result) {
                    $this->setAttribute('id', $wpdb->insert_id);
                }
            }
        }
        
        // Обновляем прогресс по уроку
        if ($result !== false) {
            $this->update_lesson_progress();
        }
        
        return $result !== false;
    }

    /**
     * Обновление прогресса по уроку
     *
     * @return void
     */
    private function update_lesson_progress() {
        global $wpdb;
        
        $user_id = $this->getAttribute('user_id');
        $lesson_id = $this->getAttribute('lesson_id');
        
        // Получаем общее количество заданий для урока
        $tasks_table = $wpdb->prefix . 'cryptoschool_lesson_tasks';
        $total_tasks = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$tasks_table} WHERE lesson_id = %d",
                $lesson_id
            )
        );
        
        if (!$total_tasks) {
            return;
        }
        
        // Получаем количество выполненных заданий
        $progress_table = $wpdb->prefix . 'cryptoschool_user_task_progress';
        $completed_tasks = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$progress_table} WHERE user_id = %d AND lesson_id = %d AND is_completed = 1",
                $user_id,
                $lesson_id
            )
        );
        
        // Рассчитываем процент прогресса
        $progress_percent = ($completed_tasks / $total_tasks) * 100;
        
        // Обновляем прогресс по уроку
        $lesson_progress_table = $wpdb->prefix . 'cryptoschool_user_lesson_progress';
        
        // Проверяем, существует ли запись о прогрессе по уроку
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$lesson_progress_table} WHERE user_id = %d AND lesson_id = %d",
                $user_id,
                $lesson_id
            )
        );
        
        $now = current_time('mysql');
        
        if ($exists) {
            // Обновляем существующую запись
            $wpdb->update(
                $lesson_progress_table,
                [
                    'progress_percent' => $progress_percent,
                    'is_completed' => ($progress_percent >= 100) ? 1 : 0,
                    'completed_at' => ($progress_percent >= 100) ? $now : null,
                    'updated_at' => $now
                ],
                [
                    'user_id' => $user_id,
                    'lesson_id' => $lesson_id
                ]
            );
        } else {
            // Создаем новую запись
            $wpdb->insert(
                $lesson_progress_table,
                [
                    'user_id' => $user_id,
                    'lesson_id' => $lesson_id,
                    'progress_percent' => $progress_percent,
                    'is_completed' => ($progress_percent >= 100) ? 1 : 0,
                    'completed_at' => ($progress_percent >= 100) ? $now : null,
                    'updated_at' => $now
                ]
            );
        }
    }
}
