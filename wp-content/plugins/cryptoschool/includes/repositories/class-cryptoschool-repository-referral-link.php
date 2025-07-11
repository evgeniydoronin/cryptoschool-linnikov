<?php
/**
 * Репозиторий для работы с реферальными ссылками
 *
 * @package CryptoSchool
 * @subpackage Repositories
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс репозитория для реферальных ссылок
 */
class CryptoSchool_Repository_Referral_Link extends CryptoSchool_Repository {
    /**
     * Имя таблицы в базе данных (без префикса)
     *
     * @var string
     */
    protected $table_name = 'cryptoschool_referral_links';

    /**
     * Имя класса модели
     *
     * @var string
     */
    protected $model_class = 'CryptoSchool_Model_Referral_Link';

    /**
     * Получение всех реферальных ссылок пользователя
     *
     * @param int   $user_id ID пользователя
     * @param array $args    Дополнительные параметры
     * @return array
     */
    public function get_user_links($user_id, $args = []) {
        global $wpdb;

        $defaults = [
            'is_active' => null,
            'orderby'   => 'created_at',
            'order'     => 'DESC',
            'limit'     => null,
            'offset'    => 0
        ];

        $args = wp_parse_args($args, $defaults);

        $where_clauses = ['user_id = %d'];
        $values = [$user_id];

        // Фильтр по активности
        if ($args['is_active'] !== null) {
            $where_clauses[] = 'is_active = %d';
            $values[] = (int) $args['is_active'];
        }

        $where_clause = implode(' AND ', $where_clauses);

        // Построение запроса
        $query = "SELECT * FROM {$this->table_name} WHERE {$where_clause}";

        // Сортировка
        $allowed_orderby = ['id', 'link_name', 'created_at', 'updated_at', 'clicks_count', 'conversions_count', 'total_earned'];
        if (in_array($args['orderby'], $allowed_orderby)) {
            $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
            $query .= " ORDER BY {$args['orderby']} {$order}";
        }

        // Лимит
        if ($args['limit']) {
            $query .= " LIMIT {$args['offset']}, {$args['limit']}";
        }

        $prepared_query = $wpdb->prepare($query, $values);
        $results = $wpdb->get_results($prepared_query, ARRAY_A);

        return $this->mapToModels($results);
    }

    /**
     * Поиск реферальной ссылки по коду
     *
     * @param string $referral_code Реферальный код
     * @return CryptoSchool_Model_Referral_Link|null
     */
    public function find_by_code($referral_code) {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE referral_code = %s AND is_active = 1",
            $referral_code
        );

        $result = $wpdb->get_row($query, ARRAY_A);

        if (!$result) {
            return null;
        }

        return $this->mapToModel($result);
    }

    /**
     * Проверка уникальности реферального кода
     *
     * @param string $referral_code Реферальный код
     * @param int    $exclude_id    ID ссылки для исключения из проверки
     * @return bool
     */
    public function is_code_unique($referral_code, $exclude_id = null) {
        global $wpdb;

        $where_clause = 'referral_code = %s';
        $values = [$referral_code];

        if ($exclude_id) {
            $where_clause .= ' AND id != %d';
            $values[] = $exclude_id;
        }

        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_clause}",
            $values
        );

        $count = $wpdb->get_var($query);

        return $count == 0;
    }

    /**
     * Генерация уникального реферального кода
     *
     * @param int $length Длина кода
     * @return string
     */
    public function generate_unique_code($length = 8) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $max_attempts = 100;
        $attempt = 0;

        do {
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= $characters[random_int(0, strlen($characters) - 1)];
            }
            $attempt++;
        } while (!$this->is_code_unique($code) && $attempt < $max_attempts);

        if ($attempt >= $max_attempts) {
            // Если не удалось сгенерировать уникальный код, добавляем timestamp
            $code = $code . time();
        }

        return $code;
    }

    /**
     * Создание новой реферальной ссылки
     *
     * @param array $data Данные для создания
     * @return CryptoSchool_Model_Referral_Link|false
     */
    public function create(array $data) {
        // Генерируем уникальный код, если не передан
        if (empty($data['referral_code'])) {
            $data['referral_code'] = $this->generate_unique_code();
        }

        // Устанавливаем дефолтные значения
        $defaults = [
            'discount_percent'   => 20.0,
            'commission_percent' => 20.0,
            'clicks_count'       => 0,
            'conversions_count'  => 0,
            'total_earned'       => 0.0,
            'is_active'          => 1,
            'created_at'         => current_time('mysql'),
            'updated_at'         => current_time('mysql')
        ];

        $data = wp_parse_args($data, $defaults);

        // Убираем ID из данных для создания (если он есть)
        unset($data['id']);

        // Создаем модель для валидации
        $model = new $this->model_class($data);
        if (!$model->is_valid()) {
            error_log('Repository create - Model validation failed for data: ' . json_encode($data));
            return false;
        }

        // Используем исходные данные для создания, а не данные из модели
        error_log('Repository create - Creating with data: ' . json_encode($data));
        return parent::create($data);
    }

    /**
     * Обновление реферальной ссылки
     *
     * @param int   $id   ID ссылки
     * @param array $data Данные для обновления
     * @return bool
     */
    public function update($id, array $data) {
        // Добавляем время обновления
        $data['updated_at'] = current_time('mysql');

        // Если изменяется реферальный код, проверяем уникальность
        if (isset($data['referral_code'])) {
            if (!$this->is_code_unique($data['referral_code'], $id)) {
                return false;
            }
        }

        // Создаем модель для валидации
        $existing_data = $this->find($id);
        if (!$existing_data) {
            return false;
        }

        $model_data = array_merge($existing_data->toArray(), $data);
        $model = new $this->model_class($model_data);
        if (!$model->is_valid()) {
            return false;
        }

        return parent::update($id, $data);
    }

    /**
     * Увеличение счетчика переходов
     *
     * @param int $id ID ссылки
     * @return bool
     */
    public function increment_clicks($id) {
        global $wpdb;

        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->table_name} SET clicks_count = clicks_count + 1, updated_at = %s WHERE id = %d",
                current_time('mysql'),
                $id
            )
        );

        return $result !== false;
    }

    /**
     * Увеличение счетчика конверсий
     *
     * @param int $id ID ссылки
     * @return bool
     */
    public function increment_conversions($id) {
        global $wpdb;

        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->table_name} SET conversions_count = conversions_count + 1, updated_at = %s WHERE id = %d",
                current_time('mysql'),
                $id
            )
        );

        return $result !== false;
    }

    /**
     * Добавление к сумме заработка
     *
     * @param int   $id     ID ссылки
     * @param float $amount Сумма для добавления
     * @return bool
     */
    public function add_earnings($id, $amount) {
        global $wpdb;

        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->table_name} SET total_earned = total_earned + %f, updated_at = %s WHERE id = %d",
                $amount,
                current_time('mysql'),
                $id
            )
        );

        return $result !== false;
    }

    /**
     * Получение статистики по ссылкам пользователя
     *
     * @param int $user_id ID пользователя
     * @return array
     */
    public function get_user_stats($user_id) {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT 
                COUNT(*) as total_links,
                COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_links,
                SUM(clicks_count) as total_clicks,
                SUM(conversions_count) as total_conversions,
                SUM(total_earned) as total_earned
            FROM {$this->table_name} 
            WHERE user_id = %d",
            $user_id
        );

        $result = $wpdb->get_row($query, ARRAY_A);

        return [
            'total_links'       => (int) $result['total_links'],
            'active_links'      => (int) $result['active_links'],
            'total_clicks'      => (int) $result['total_clicks'],
            'total_conversions' => (int) $result['total_conversions'],
            'total_earned'      => (float) $result['total_earned'],
            'conversion_rate'   => $result['total_clicks'] > 0 
                ? round(($result['total_conversions'] / $result['total_clicks']) * 100, 2) 
                : 0.0
        ];
    }

    /**
     * Получение топ ссылок по заработку
     *
     * @param int $user_id ID пользователя
     * @param int $limit   Количество ссылок
     * @return array
     */
    public function get_top_earning_links($user_id, $limit = 5) {
        return $this->get_user_links($user_id, [
            'orderby' => 'total_earned',
            'order'   => 'DESC',
            'limit'   => $limit
        ]);
    }

    /**
     * Получение топ ссылок по конверсии
     *
     * @param int $user_id ID пользователя
     * @param int $limit   Количество ссылок
     * @return array
     */
    public function get_top_converting_links($user_id, $limit = 5) {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT *, 
                CASE 
                    WHEN clicks_count > 0 THEN (conversions_count / clicks_count) * 100 
                    ELSE 0 
                END as conversion_rate
            FROM {$this->table_name} 
            WHERE user_id = %d AND clicks_count > 0
            ORDER BY conversion_rate DESC, conversions_count DESC
            LIMIT %d",
            $user_id,
            $limit
        );

        $results = $wpdb->get_results($query, ARRAY_A);

        return $this->mapToModels($results);
    }

    /**
     * Деактивация всех ссылок пользователя
     *
     * @param int $user_id ID пользователя
     * @return bool
     */
    public function deactivate_user_links($user_id) {
        global $wpdb;

        $result = $wpdb->update(
            $this->table_name,
            [
                'is_active'  => 0,
                'updated_at' => current_time('mysql')
            ],
            ['user_id' => $user_id]
        );

        return $result !== false;
    }

    /**
     * Получение количества ссылок пользователя
     *
     * @param int   $user_id   ID пользователя
     * @param array $conditions Дополнительные условия
     * @return int
     */
    public function count_user_links($user_id, $conditions = []) {
        $conditions['user_id'] = $user_id;
        return $this->count($conditions);
    }
}
