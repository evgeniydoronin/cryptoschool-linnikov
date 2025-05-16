<?php
/**
 * Репозиторий рейтинга пользователей
 *
 * @package CryptoSchool
 * @subpackage Repositories
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс репозитория рейтинга пользователей
 */
class CryptoSchool_Repository_User_Leaderboard extends CryptoSchool_Repository {
    /**
     * Имя таблицы в базе данных
     *
     * @var string
     */
    protected $table_name = 'cryptoschool_user_leaderboard';

    /**
     * Имя класса модели
     *
     * @var string
     */
    protected $model_class = 'CryptoSchool_Model_User_Leaderboard';

    /**
     * Получение рейтинга пользователя
     *
     * @param int $user_id ID пользователя
     * @return mixed
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
     * Получение топ пользователей по баллам
     *
     * @param int $limit Количество пользователей
     * @return array
     */
    public function get_top_users($limit = 10) {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT l.*, u.display_name 
            FROM {$this->table_name} l
            INNER JOIN {$wpdb->users} u ON l.user_id = u.ID
            ORDER BY l.rank ASC
            LIMIT %d",
            $limit
        );

        $results = $wpdb->get_results($query, ARRAY_A);

        return $this->mapToModels($results);
    }

    /**
     * Обновление рангов всех пользователей
     *
     * @return bool
     */
    public function update_ranks() {
        global $wpdb;

        // Получение всех пользователей, отсортированных по баллам
        $query = "
            SELECT id, user_id 
            FROM {$this->table_name} 
            ORDER BY total_points DESC, completed_lessons DESC
        ";

        $users = $wpdb->get_results($query, ARRAY_A);

        // Обновление рангов
        foreach ($users as $index => $user) {
            $wpdb->update(
                $this->table_name,
                ['rank' => $index + 1],
                ['id' => $user['id']]
            );
        }

        return true;
    }

    /**
     * Получение позиции пользователя в рейтинге
     *
     * @param int $user_id ID пользователя
     * @return int
     */
    public function get_user_rank($user_id) {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT rank FROM {$this->table_name} WHERE user_id = %d",
            $user_id
        );

        $rank = $wpdb->get_var($query);

        return (int) $rank;
    }

    /**
     * Получение общего количества баллов пользователя
     *
     * @param int $user_id ID пользователя
     * @return int
     */
    public function get_user_total_points($user_id) {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT total_points FROM {$this->table_name} WHERE user_id = %d",
            $user_id
        );

        $total_points = $wpdb->get_var($query);

        return (int) $total_points;
    }

    /**
     * Получение количества завершенных уроков пользователя
     *
     * @param int $user_id ID пользователя
     * @return int
     */
    public function get_user_completed_lessons($user_id) {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT completed_lessons FROM {$this->table_name} WHERE user_id = %d",
            $user_id
        );

        $completed_lessons = $wpdb->get_var($query);

        return (int) $completed_lessons;
    }

    /**
     * Получение количества дней активности пользователя
     *
     * @param int $user_id ID пользователя
     * @return int
     */
    public function get_user_days_active($user_id) {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT days_active FROM {$this->table_name} WHERE user_id = %d",
            $user_id
        );

        $days_active = $wpdb->get_var($query);

        return (int) $days_active;
    }

    /**
     * Получение пользователей с наибольшим количеством баллов
     *
     * @param int $limit Количество пользователей
     * @return array
     */
    public function get_top_users_by_points($limit = 10) {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT l.*, u.display_name 
            FROM {$this->table_name} l
            INNER JOIN {$wpdb->users} u ON l.user_id = u.ID
            ORDER BY l.total_points DESC
            LIMIT %d",
            $limit
        );

        $results = $wpdb->get_results($query, ARRAY_A);

        return $this->mapToModels($results);
    }

    /**
     * Получение пользователей с наибольшим количеством завершенных уроков
     *
     * @param int $limit Количество пользователей
     * @return array
     */
    public function get_top_users_by_completed_lessons($limit = 10) {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT l.*, u.display_name 
            FROM {$this->table_name} l
            INNER JOIN {$wpdb->users} u ON l.user_id = u.ID
            ORDER BY l.completed_lessons DESC
            LIMIT %d",
            $limit
        );

        $results = $wpdb->get_results($query, ARRAY_A);

        return $this->mapToModels($results);
    }
}
