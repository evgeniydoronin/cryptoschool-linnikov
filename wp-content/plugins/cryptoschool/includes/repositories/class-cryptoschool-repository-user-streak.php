<?php
/**
 * Репозиторий ежедневной серии пользователя
 *
 * @package CryptoSchool
 * @subpackage Repositories
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс репозитория ежедневной серии пользователя
 */
class CryptoSchool_Repository_User_Streak extends CryptoSchool_Repository {
    /**
     * Имя таблицы в базе данных
     *
     * @var string
     */
    protected $table_name = 'cryptoschool_user_streak';

    /**
     * Имя класса модели
     *
     * @var string
     */
    protected $model_class = 'CryptoSchool_Model_User_Streak';

    /**
     * Получение серии пользователя
     *
     * @param int $user_id ID пользователя
     * @return CryptoSchool_Model_User_Streak|null
     */
    public function get_by_user_id($user_id) {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE user_id = %d",
            $user_id
        );

        $result = $wpdb->get_row($query, ARRAY_A);

        if (!$result) {
            return null;
        }

        return $this->mapToModel($result);
    }

    /**
     * Создание или обновление серии пользователя
     *
     * @param int   $user_id ID пользователя
     * @param array $data    Данные серии
     * @return int|false
     */
    public function create_or_update($user_id, $data) {
        global $wpdb;

        // Проверяем, существует ли запись о серии
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$this->table_name} WHERE user_id = %d",
                $user_id
            )
        );

        $defaults = [
            'user_id' => $user_id,
            'current_streak' => 0,
            'max_streak' => 0,
            'last_activity_date' => current_time('Y-m-d'),
            'lessons_today' => 0,
            'updated_at' => current_time('mysql'),
        ];

        $data = wp_parse_args($data, $defaults);

        if ($exists) {
            // Обновляем существующую запись
            $result = $wpdb->update(
                $this->table_name,
                $data,
                [
                    'user_id' => $user_id
                ]
            );

            return $result !== false ? $exists : false;
        } else {
            // Создаем новую запись
            $data['created_at'] = current_time('mysql');
            $result = $wpdb->insert($this->table_name, $data);

            return $result !== false ? $wpdb->insert_id : false;
        }
    }

    /**
     * Увеличение серии пользователя
     *
     * @param int $user_id ID пользователя
     * @return bool
     */
    public function increment_streak($user_id) {
        $streak = $this->get_by_user_id($user_id);
        
        if (!$streak) {
            // Создаем новую запись о серии
            $data = [
                'user_id' => $user_id,
                'current_streak' => 1,
                'max_streak' => 1,
                'last_activity_date' => current_time('Y-m-d'),
                'lessons_today' => 0,
            ];
            
            return $this->create_or_update($user_id, $data) !== false;
        }
        
        // Увеличиваем серию
        $current_streak = $streak->current_streak + 1;
        $max_streak = max($streak->max_streak, $current_streak);
        
        $data = [
            'current_streak' => $current_streak,
            'max_streak' => $max_streak,
            'last_activity_date' => current_time('Y-m-d'),
        ];
        
        return $this->create_or_update($user_id, $data) !== false;
    }

    /**
     * Сброс серии пользователя
     *
     * @param int $user_id ID пользователя
     * @return bool
     */
    public function reset_streak($user_id) {
        $data = [
            'current_streak' => 0,
            'last_activity_date' => current_time('Y-m-d'),
        ];
        
        return $this->create_or_update($user_id, $data) !== false;
    }

    /**
     * Увеличение счетчика уроков за день
     *
     * @param int $user_id ID пользователя
     * @return bool
     */
    public function increment_lessons_today($user_id) {
        $streak = $this->get_by_user_id($user_id);
        
        if (!$streak) {
            // Создаем новую запись о серии
            $data = [
                'user_id' => $user_id,
                'current_streak' => 0,
                'max_streak' => 0,
                'last_activity_date' => current_time('Y-m-d'),
                'lessons_today' => 1,
            ];
            
            return $this->create_or_update($user_id, $data) !== false;
        }
        
        // Увеличиваем счетчик уроков
        $lessons_today = $streak->lessons_today + 1;
        
        $data = [
            'lessons_today' => $lessons_today,
            'last_activity_date' => current_time('Y-m-d'),
        ];
        
        return $this->create_or_update($user_id, $data) !== false;
    }

    /**
     * Сброс счетчика уроков за день
     *
     * @param int $user_id ID пользователя
     * @return bool
     */
    public function reset_lessons_today($user_id) {
        $data = [
            'lessons_today' => 0,
            'last_activity_date' => current_time('Y-m-d'),
        ];
        
        return $this->create_or_update($user_id, $data) !== false;
    }

    /**
     * Получение пользователей с наибольшей текущей серией
     *
     * @param int $limit Количество пользователей
     * @return array
     */
    public function get_top_streaks($limit = 10) {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT s.*, u.display_name 
            FROM {$this->table_name} s
            INNER JOIN {$wpdb->users} u ON s.user_id = u.ID
            WHERE s.current_streak > 0
            ORDER BY s.current_streak DESC, s.max_streak DESC
            LIMIT %d",
            $limit
        );

        $results = $wpdb->get_results($query, ARRAY_A);

        return $this->mapToModels($results);
    }

    /**
     * Получение пользователей с наибольшей максимальной серией
     *
     * @param int $limit Количество пользователей
     * @return array
     */
    public function get_top_max_streaks($limit = 10) {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT s.*, u.display_name 
            FROM {$this->table_name} s
            INNER JOIN {$wpdb->users} u ON s.user_id = u.ID
            WHERE s.max_streak > 0
            ORDER BY s.max_streak DESC, s.current_streak DESC
            LIMIT %d",
            $limit
        );

        $results = $wpdb->get_results($query, ARRAY_A);

        return $this->mapToModels($results);
    }
}
