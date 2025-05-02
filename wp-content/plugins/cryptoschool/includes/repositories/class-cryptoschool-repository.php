<?php
/**
 * Базовый класс репозитория
 *
 * Предоставляет базовую функциональность для всех репозиториев плагина
 *
 * @package CryptoSchool
 * @subpackage Repositories
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Базовый класс репозитория
 */
abstract class CryptoSchool_Repository {
    /**
     * Имя таблицы в базе данных
     *
     * @var string
     */
    protected $table_name;

    /**
     * Имя класса модели
     *
     * @var string
     */
    protected $model_class;

    /**
     * Первичный ключ таблицы
     *
     * @var string
     */
    protected $primary_key = 'id';

    /**
     * Конструктор класса
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . $this->table_name;
    }

    /**
     * Получение всех записей
     *
     * @return array
     */
    public function all() {
        global $wpdb;

        $results = $wpdb->get_results("SELECT * FROM {$this->table_name}", ARRAY_A);

        return $this->mapToModels($results);
    }

    /**
     * Поиск записи по ID
     *
     * @param int $id ID записи
     * @return mixed
     */
    public function find($id) {
        global $wpdb;

        // Отладочный вывод
        error_log('Repository find - ID: ' . $id . ', Table: ' . $this->table_name);

        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE {$this->primary_key} = %d",
            $id
        );

        // Отладочный вывод
        error_log('Repository find - Query: ' . $query);

        $result = $wpdb->get_row($query, ARRAY_A);

        // Отладочный вывод
        error_log('Repository find - Result: ' . ($result ? 'найден' : 'не найден'));

        if (!$result) {
            return null;
        }

        return $this->mapToModel($result);
    }

    /**
     * Поиск записей по условию
     *
     * @param array  $conditions Условия поиска
     * @param string $operator   Оператор для условий (AND/OR)
     * @return array
     */
    public function where(array $conditions, $operator = 'AND') {
        global $wpdb;

        $where_clauses = [];
        $values = [];

        foreach ($conditions as $column => $value) {
            $placeholder = is_numeric($value) ? '%d' : '%s';
            $where_clauses[] = "{$column} = {$placeholder}";
            $values[] = $value;
        }

        $where_clause = implode(" {$operator} ", $where_clauses);

        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE {$where_clause}",
            $values
        );

        $results = $wpdb->get_results($query, ARRAY_A);

        return $this->mapToModels($results);
    }

    /**
     * Создание новой записи
     *
     * @param array $data Данные для создания
     * @return mixed
     */
    public function create(array $data) {
        global $wpdb;

        $result = $wpdb->insert(
            $this->table_name,
            $data
        );

        if (!$result) {
            return false;
        }

        return $this->find($wpdb->insert_id);
    }

    /**
     * Обновление записи
     *
     * @param int   $id   ID записи
     * @param array $data Данные для обновления
     * @return bool
     */
    public function update($id, array $data) {
        global $wpdb;

        $result = $wpdb->update(
            $this->table_name,
            $data,
            [$this->primary_key => $id]
        );

        return $result !== false;
    }

    /**
     * Удаление записи
     *
     * @param int $id ID записи
     * @return bool
     */
    public function delete($id) {
        global $wpdb;

        $result = $wpdb->delete(
            $this->table_name,
            [$this->primary_key => $id]
        );

        return $result !== false;
    }

    /**
     * Преобразование результата запроса в модель
     *
     * @param array $data Данные из базы данных
     * @return mixed
     */
    protected function mapToModel(array $data) {
        // Отладочный вывод
        error_log('Repository mapToModel - Model class: ' . $this->model_class);
        error_log('Repository mapToModel - Data: ' . json_encode($data));
        
        // Проверка наличия ID в данных
        if (!isset($data['id']) || empty($data['id'])) {
            error_log('Repository mapToModel - ERROR: ID is missing or empty in data!');
        } else {
            error_log('Repository mapToModel - ID from data: ' . $data['id']);
        }
        
        $model_class = $this->model_class;
        $model = new $model_class($data);
        
        // Отладочный вывод
        error_log('Repository mapToModel - Model created: ' . ($model ? 'да' : 'нет'));
        if ($model) {
            error_log('Repository mapToModel - Model ID: ' . $model->getAttribute('id'));
            error_log('Repository mapToModel - Model attributes: ' . json_encode($model->getAttributes()));
        }
        
        return $model;
    }

    /**
     * Преобразование результатов запроса в массив моделей
     *
     * @param array $data_array Массив данных из базы данных
     * @return array
     */
    protected function mapToModels(array $data_array) {
        // Отладочный вывод
        error_log('Repository mapToModels - Data array count: ' . count($data_array));
        if (count($data_array) > 0) {
            error_log('Repository mapToModels - First data item: ' . json_encode($data_array[0]));
        }
        
        $models = [];

        foreach ($data_array as $index => $data) {
            // Проверка наличия ID в данных
            if (!isset($data['id']) || empty($data['id'])) {
                error_log('Repository mapToModels - ERROR: ID is missing or empty in data item ' . $index);
            } else {
                error_log('Repository mapToModels - Data item ' . $index . ' ID: ' . $data['id']);
            }
            
            $model = $this->mapToModel($data);
            
            // Проверка, что модель создана и имеет ID
            if ($model) {
                $model_id = $model->getAttribute('id');
                error_log('Repository mapToModels - Model ' . $index . ' ID: ' . $model_id);
                
                if (empty($model_id)) {
                    error_log('Repository mapToModels - ERROR: Model ' . $index . ' has empty ID');
                }
                
                $models[] = $model;
            } else {
                error_log('Repository mapToModels - ERROR: Failed to create model for data item ' . $index);
            }
        }
        
        error_log('Repository mapToModels - Models count: ' . count($models));
        
        return $models;
    }

    /**
     * Выполнение произвольного SQL-запроса
     *
     * @param string $query SQL-запрос
     * @param array  $args  Аргументы для подготовленного запроса
     * @return array|object|null
     */
    protected function query($query, $args = []) {
        global $wpdb;

        if (!empty($args)) {
            $query = $wpdb->prepare($query, $args);
        }

        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Получение количества записей
     *
     * @param array $conditions Условия для подсчета
     * @return int
     */
    public function count(array $conditions = []) {
        global $wpdb;

        $query = "SELECT COUNT(*) FROM {$this->table_name}";

        if (!empty($conditions)) {
            $where_clauses = [];
            $values = [];

            foreach ($conditions as $column => $value) {
                $placeholder = is_numeric($value) ? '%d' : '%s';
                $where_clauses[] = "{$column} = {$placeholder}";
                $values[] = $value;
            }

            $where_clause = implode(" AND ", $where_clauses);
            $query .= " WHERE {$where_clause}";
            $query = $wpdb->prepare($query, $values);
        }

        return (int) $wpdb->get_var($query);
    }

    /**
     * Получение последней ошибки базы данных
     *
     * @return string
     */
    protected function getLastError() {
        global $wpdb;
        return $wpdb->last_error;
    }

    /**
     * Получение имени таблицы
     *
     * @return string
     */
    public function get_table_name() {
        return $this->table_name;
    }
}
