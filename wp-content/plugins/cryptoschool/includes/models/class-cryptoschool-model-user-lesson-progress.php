<?php
/**
 * Модель прогресса пользователя по уроку
 *
 * @package CryptoSchool
 * @subpackage Models
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс модели прогресса пользователя по уроку
 */
class CryptoSchool_Model_User_Lesson_Progress extends CryptoSchool_Model {
    /**
     * Атрибуты модели
     *
     * @var array
     */
    protected $attributes = [
        'id' => null,
        'user_id' => null,
        'lesson_id' => null,
        'is_completed' => 0,
        'progress_percent' => 0,
        'completed_at' => null,
        'updated_at' => null
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
     * Обновление прогресса пользователя по уроку
     *
     * @param int $progress_percent Процент прогресса
     * @return bool
     */
    public function update_progress($progress_percent) {
        $this->setAttribute('progress_percent', $progress_percent);
        
        // Если прогресс достиг 100%, отмечаем урок как выполненный
        if ($progress_percent >= 100) {
            $this->setAttribute('is_completed', 1);
            $this->setAttribute('completed_at', current_time('mysql'));
        }
        
        $this->setAttribute('updated_at', current_time('mysql'));
        
        return $this->save();
    }

    /**
     * Отметка урока как выполненного
     *
     * @return bool
     */
    public function mark_as_completed() {
        $this->setAttribute('is_completed', 1);
        $this->setAttribute('progress_percent', 100);
        $this->setAttribute('completed_at', current_time('mysql'));
        $this->setAttribute('updated_at', current_time('mysql'));
        
        return $this->save();
    }

    /**
     * Отметка урока как невыполненного
     *
     * @return bool
     */
    public function mark_as_uncompleted() {
        $this->setAttribute('is_completed', 0);
        $this->setAttribute('completed_at', null);
        $this->setAttribute('updated_at', current_time('mysql'));
        
        return $this->save();
    }

    /**
     * Сохранение модели
     *
     * @return bool
     */
    public function save() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cryptoschool_user_lesson_progress';
        
        $data = [
            'user_id' => $this->getAttribute('user_id'),
            'lesson_id' => $this->getAttribute('lesson_id'),
            'is_completed' => $this->getAttribute('is_completed'),
            'progress_percent' => $this->getAttribute('progress_percent'),
            'completed_at' => $this->getAttribute('completed_at'),
            'updated_at' => current_time('mysql')
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
                    "SELECT id FROM {$table_name} WHERE user_id = %d AND lesson_id = %d",
                    $this->getAttribute('user_id'),
                    $this->getAttribute('lesson_id')
                )
            );
            
            if ($exists) {
                // Обновляем существующую запись
                $result = $wpdb->update(
                    $table_name,
                    $data,
                    [
                        'user_id' => $this->getAttribute('user_id'),
                        'lesson_id' => $this->getAttribute('lesson_id')
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
        
        return $result !== false;
    }
}
