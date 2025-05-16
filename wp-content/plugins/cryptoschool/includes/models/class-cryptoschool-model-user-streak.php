<?php
/**
 * Модель ежедневной серии пользователя
 *
 * @package CryptoSchool
 * @subpackage Models
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс модели ежедневной серии пользователя
 */
class CryptoSchool_Model_User_Streak extends CryptoSchool_Model {
    /**
     * Атрибуты модели
     *
     * @var array
     */
    protected $attributes = [
        'id' => null,
        'user_id' => null,
        'current_streak' => 0,
        'max_streak' => 0,
        'last_activity_date' => null,
        'lessons_today' => 0,
        'created_at' => null,
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
     * Увеличение серии на 1 день
     *
     * @return bool
     */
    public function increment_streak() {
        $current_streak = $this->getAttribute('current_streak');
        $current_streak++;
        $this->setAttribute('current_streak', $current_streak);
        
        // Обновление максимальной серии, если текущая серия больше
        $max_streak = $this->getAttribute('max_streak');
        if ($current_streak > $max_streak) {
            $this->setAttribute('max_streak', $current_streak);
        }
        
        return $this->save();
    }

    /**
     * Сброс серии
     *
     * @return bool
     */
    public function reset_streak() {
        $this->setAttribute('current_streak', 0);
        return $this->save();
    }

    /**
     * Увеличение счетчика уроков за день
     *
     * @return bool
     */
    public function increment_lessons_today() {
        $lessons_today = $this->getAttribute('lessons_today');
        $lessons_today++;
        $this->setAttribute('lessons_today', $lessons_today);
        return $this->save();
    }

    /**
     * Сброс счетчика уроков за день
     *
     * @return bool
     */
    public function reset_lessons_today() {
        $this->setAttribute('lessons_today', 0);
        return $this->save();
    }

    /**
     * Расчет баллов за текущий день серии
     *
     * @return int
     */
    public function get_streak_points() {
        $current_streak = $this->getAttribute('current_streak');
        
        // День 1 серии: 0 баллов (базовое прохождение урока без дополнительных баллов)
        if ($current_streak <= 1) {
            return 0;
        }
        
        // День 2-4 серии: +5 баллов
        // День 5 серии: +5 баллов (специальный буст "Щоденний відрізок")
        // День 6+ серии: +5 баллов за каждый последующий день непрерывной серии
        return 5;
    }

    /**
     * Сохранение модели
     *
     * @return bool
     */
    public function save() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cryptoschool_user_streak';
        
        $data = [
            'user_id' => $this->getAttribute('user_id'),
            'current_streak' => $this->getAttribute('current_streak'),
            'max_streak' => $this->getAttribute('max_streak'),
            'last_activity_date' => $this->getAttribute('last_activity_date'),
            'lessons_today' => $this->getAttribute('lessons_today'),
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
            // Проверяем, существует ли запись о серии
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$table_name} WHERE user_id = %d",
                    $this->getAttribute('user_id')
                )
            );
            
            if ($exists) {
                // Обновляем существующую запись
                $result = $wpdb->update(
                    $table_name,
                    $data,
                    [
                        'user_id' => $this->getAttribute('user_id')
                    ]
                );
                
                // Обновляем ID модели
                $this->setAttribute('id', $exists);
            } else {
                // Создаем новую запись
                $data['created_at'] = current_time('mysql');
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
