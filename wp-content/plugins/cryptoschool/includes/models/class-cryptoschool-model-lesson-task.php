<?php
/**
 * Модель задания урока
 *
 * @package CryptoSchool
 * @subpackage Models
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс модели задания урока
 */
class CryptoSchool_Model_Lesson_Task extends CryptoSchool_Model {
    /**
     * Атрибуты модели
     *
     * @var array
     */
    protected $attributes = [
        'id' => null,
        'lesson_id' => null,
        'title' => '',
        'task_order' => 0,
        'created_at' => null,
        'updated_at' => null
    ];

    /**
     * Получение урока, к которому относится задание
     *
     * @return CryptoSchool_Model_Lesson|null
     */
    public function get_lesson() {
        $lesson_repository = new CryptoSchool_Repository_Lesson();
        return $lesson_repository->find($this->getAttribute('lesson_id'));
    }

    /**
     * Получение статуса выполнения задания для пользователя
     *
     * @param int $user_id ID пользователя
     * @return bool
     */
    public function is_completed_by_user($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cryptoschool_user_task_progress';
        $query = $wpdb->prepare(
            "SELECT is_completed FROM {$table_name} WHERE user_id = %d AND task_id = %d",
            $user_id,
            $this->getAttribute('id')
        );
        
        $result = $wpdb->get_var($query);
        
        return (bool) $result;
    }

    /**
     * Отметка задания как выполненного для пользователя
     *
     * @param int $user_id ID пользователя
     * @return bool
     */
    public function mark_as_completed_by_user($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cryptoschool_user_task_progress';
        
        // Проверяем, существует ли запись о прогрессе
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table_name} WHERE user_id = %d AND task_id = %d",
                $user_id,
                $this->getAttribute('id')
            )
        );
        
        $now = current_time('mysql');
        
        if ($exists) {
            // Обновляем существующую запись
            $result = $wpdb->update(
                $table_name,
                [
                    'is_completed' => 1,
                    'completed_at' => $now
                ],
                [
                    'user_id' => $user_id,
                    'task_id' => $this->getAttribute('id')
                ]
            );
        } else {
            // Создаем новую запись
            $result = $wpdb->insert(
                $table_name,
                [
                    'user_id' => $user_id,
                    'lesson_id' => $this->getAttribute('lesson_id'),
                    'task_id' => $this->getAttribute('id'),
                    'is_completed' => 1,
                    'completed_at' => $now
                ]
            );
        }
        
        return $result !== false;
    }

    /**
     * Отметка задания как невыполненного для пользователя
     *
     * @param int $user_id ID пользователя
     * @return bool
     */
    public function mark_as_uncompleted_by_user($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cryptoschool_user_task_progress';
        
        // Проверяем, существует ли запись о прогрессе
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table_name} WHERE user_id = %d AND task_id = %d",
                $user_id,
                $this->getAttribute('id')
            )
        );
        
        if ($exists) {
            // Обновляем существующую запись
            $result = $wpdb->update(
                $table_name,
                [
                    'is_completed' => 0,
                    'completed_at' => null
                ],
                [
                    'user_id' => $user_id,
                    'task_id' => $this->getAttribute('id')
                ]
            );
        } else {
            // Создаем новую запись
            $result = $wpdb->insert(
                $table_name,
                [
                    'user_id' => $user_id,
                    'lesson_id' => $this->getAttribute('lesson_id'),
                    'task_id' => $this->getAttribute('id'),
                    'is_completed' => 0,
                    'completed_at' => null
                ]
            );
        }
        
        return $result !== false;
    }
}
