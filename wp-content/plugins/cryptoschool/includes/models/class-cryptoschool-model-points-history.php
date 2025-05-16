<?php
/**
 * Модель истории начисления баллов
 *
 * @package CryptoSchool
 * @subpackage Models
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс модели истории начисления баллов
 */
class CryptoSchool_Model_Points_History extends CryptoSchool_Model {
    /**
     * Атрибуты модели
     *
     * @var array
     */
    protected $attributes = [
        'id' => null,
        'user_id' => null,
        'lesson_id' => null,
        'points' => 0,
        'points_type' => 'lesson',
        'streak_day' => null,
        'lesson_number_today' => null,
        'description' => '',
        'created_at' => null
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
        if (!$this->getAttribute('lesson_id')) {
            return null;
        }
        
        $lesson_repository = new CryptoSchool_Repository_Lesson();
        return $lesson_repository->find($this->getAttribute('lesson_id'));
    }

    /**
     * Добавление баллов за прохождение урока
     *
     * @param int    $user_id    ID пользователя
     * @param int    $lesson_id  ID урока
     * @param int    $points     Количество баллов
     * @param string $description Описание начисления
     * @return bool
     */
    public static function add_lesson_points($user_id, $lesson_id, $points, $description = '') {
        $model = new self([
            'user_id' => $user_id,
            'lesson_id' => $lesson_id,
            'points' => $points,
            'points_type' => 'lesson',
            'description' => $description,
            'created_at' => current_time('mysql')
        ]);
        
        return $model->save();
    }

    /**
     * Добавление баллов за серию
     *
     * @param int    $user_id    ID пользователя
     * @param int    $points     Количество баллов
     * @param int    $streak_day День серии
     * @param string $description Описание начисления
     * @return bool
     */
    public static function add_streak_points($user_id, $points, $streak_day, $description = '') {
        $model = new self([
            'user_id' => $user_id,
            'points' => $points,
            'points_type' => 'streak',
            'streak_day' => $streak_day,
            'description' => $description,
            'created_at' => current_time('mysql')
        ]);
        
        return $model->save();
    }

    /**
     * Добавление баллов за прохождение нескольких уроков в день
     *
     * @param int    $user_id           ID пользователя
     * @param int    $lesson_id         ID урока
     * @param int    $points            Количество баллов
     * @param int    $lesson_number_today Номер урока за день
     * @param string $description       Описание начисления
     * @return bool
     */
    public static function add_multi_lesson_points($user_id, $lesson_id, $points, $lesson_number_today, $description = '') {
        $model = new self([
            'user_id' => $user_id,
            'lesson_id' => $lesson_id,
            'points' => $points,
            'points_type' => 'multi_lesson',
            'lesson_number_today' => $lesson_number_today,
            'description' => $description,
            'created_at' => current_time('mysql')
        ]);
        
        return $model->save();
    }

    /**
     * Добавление баллов за завершение курса
     *
     * @param int    $user_id    ID пользователя
     * @param int    $points     Количество баллов
     * @param string $description Описание начисления
     * @return bool
     */
    public static function add_course_completion_points($user_id, $points, $description = '') {
        $model = new self([
            'user_id' => $user_id,
            'points' => $points,
            'points_type' => 'course_completion',
            'description' => $description,
            'created_at' => current_time('mysql')
        ]);
        
        return $model->save();
    }

    /**
     * Сохранение модели
     *
     * @return bool
     */
    public function save() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cryptoschool_points_history';
        
        $data = [
            'user_id' => $this->getAttribute('user_id'),
            'lesson_id' => $this->getAttribute('lesson_id'),
            'points' => $this->getAttribute('points'),
            'points_type' => $this->getAttribute('points_type'),
            'streak_day' => $this->getAttribute('streak_day'),
            'lesson_number_today' => $this->getAttribute('lesson_number_today'),
            'description' => $this->getAttribute('description'),
            'created_at' => $this->getAttribute('created_at') ?: current_time('mysql')
        ];
        
        if ($this->getAttribute('id')) {
            // Обновление существующей записи
            $result = $wpdb->update(
                $table_name,
                $data,
                ['id' => $this->getAttribute('id')]
            );
        } else {
            // Создание новой записи
            $result = $wpdb->insert($table_name, $data);
            
            // Обновляем ID модели
            if ($result) {
                $this->setAttribute('id', $wpdb->insert_id);
            }
        }
        
        return $result !== false;
    }
}
