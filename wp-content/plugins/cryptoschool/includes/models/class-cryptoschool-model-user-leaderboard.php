<?php
/**
 * Модель рейтинга пользователей
 *
 * @package CryptoSchool
 * @subpackage Models
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс модели рейтинга пользователей
 */
class CryptoSchool_Model_User_Leaderboard extends CryptoSchool_Model {
    /**
     * Атрибуты модели
     *
     * @var array
     */
    protected $attributes = [
        'id' => null,
        'user_id' => null,
        'total_points' => 0,
        'rank' => 0,
        'completed_lessons' => 0,
        'days_active' => 0,
        'last_updated' => null
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
     * Получение имени пользователя
     *
     * @return string
     */
    public function get_user_name() {
        $user = $this->get_user();
        if (!$user) {
            return '';
        }
        
        return $user->display_name;
    }

    /**
     * Получение аватара пользователя
     *
     * @param int $size Размер аватара
     * @return string
     */
    public function get_user_avatar($size = 96) {
        $user = $this->get_user();
        if (!$user) {
            return '';
        }
        
        return get_avatar_url($user->ID, ['size' => $size]);
    }

    /**
     * Получение форматированного количества баллов
     *
     * @return string
     */
    public function get_formatted_points() {
        return number_format($this->getAttribute('total_points'));
    }

    /**
     * Получение форматированного ранга
     *
     * @return string
     */
    public function get_formatted_rank() {
        $rank = $this->getAttribute('rank');
        
        if ($rank <= 0) {
            return '-';
        }
        
        return '#' . $rank;
    }

    /**
     * Получение форматированного количества завершенных уроков
     *
     * @return string
     */
    public function get_formatted_completed_lessons() {
        return number_format($this->getAttribute('completed_lessons'));
    }

    /**
     * Получение форматированного количества дней активности
     *
     * @return string
     */
    public function get_formatted_days_active() {
        return number_format($this->getAttribute('days_active'));
    }

    /**
     * Получение даты последнего обновления
     *
     * @param string $format Формат даты
     * @return string
     */
    public function get_last_updated($format = 'd.m.Y H:i') {
        $last_updated = $this->getAttribute('last_updated');
        if (!$last_updated) {
            return '';
        }
        
        return date_i18n($format, strtotime($last_updated));
    }

    /**
     * Сохранение модели
     *
     * @return bool
     */
    public function save() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cryptoschool_user_leaderboard';
        
        $data = [
            'user_id' => $this->getAttribute('user_id'),
            'total_points' => $this->getAttribute('total_points'),
            'rank' => $this->getAttribute('rank'),
            'completed_lessons' => $this->getAttribute('completed_lessons'),
            'days_active' => $this->getAttribute('days_active'),
            'last_updated' => current_time('mysql')
        ];
        
        if ($this->getAttribute('id')) {
            // Обновление существующей записи
            $result = $wpdb->update(
                $table_name,
                $data,
                ['id' => $this->getAttribute('id')]
            );
        } else {
            // Проверяем, существует ли запись о рейтинге
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
