<?php
/**
 * Класс для отладки проблем с Gutenberg и REST API
 *
 * @package CryptoSchool
 * @subpackage Debug
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс для отладки
 */
class CryptoSchool_Debug {

    /**
     * Конструктор класса
     */
    public function __construct() {
        // Простое логирование инициализации
        error_log('[' . current_time('Y-m-d H:i:s') . '] [DEBUG] CryptoSchool_Debug класс инициализирован');
        
        $this->init_hooks();
        
        // Тестовое логирование в наш файл
        $this->log('Debug Init', array('message' => 'Класс отладки успешно инициализирован'));
    }

    /**
     * Инициализация хуков для отладки
     *
     * @return void
     */
    private function init_hooks() {
        // Логирование только REST API ошибок
        add_filter('rest_post_dispatch', array($this, 'log_rest_response'), 10, 3);
        
        // Логирование только ошибок при сохранении постов
        add_action('save_post', array($this, 'log_save_post'), 1, 2);
        
        // Перехват только PHP ошибок
        add_action('init', array($this, 'setup_error_handler'));
    }

    /**
     * Логирование REST API запросов
     *
     * @param mixed           $result  Результат
     * @param WP_REST_Server  $server  Сервер
     * @param WP_REST_Request $request Запрос
     * @return mixed
     */
    public function log_rest_request($result, $server, $request) {
        $route = $request->get_route();
        
        // Логируем только запросы к нашим Custom Post Types
        if (strpos($route, '/wp/v2/courses') !== false || strpos($route, '/wp/v2/lessons') !== false) {
            $this->log('REST API Request', array(
                'route' => $route,
                'method' => $request->get_method(),
                'params' => $request->get_params(),
                'headers' => $request->get_headers(),
                'body' => $request->get_body()
            ));
        }
        
        return $result;
    }

    /**
     * Логирование REST API ответов
     *
     * @param WP_HTTP_Response $result  Результат
     * @param WP_REST_Server   $server  Сервер
     * @param WP_REST_Request  $request Запрос
     * @return WP_HTTP_Response
     */
    public function log_rest_response($result, $server, $request) {
        $route = $request->get_route();
        
        // Логируем ВСЕ запросы для наших Custom Post Types (временно для отладки)
        if ((strpos($route, '/wp/v2/courses') !== false || strpos($route, '/wp/v2/lessons') !== false)) {
            $status = $result->get_status();
            $data = $result->get_data();
            
            $log_data = array(
                'route' => $route,
                'method' => $request->get_method(),
                'status' => $status,
                'request_params' => $request->get_params()
            );
            
            // Если ошибка, добавляем детали
            if ($status >= 400) {
                $log_data['error_message'] = isset($data['message']) ? $data['message'] : 'Unknown error';
                $log_data['error_code'] = isset($data['code']) ? $data['code'] : 'unknown';
                $log_data['error_data'] = $data;
                $this->log('REST API Error', $log_data, 'ERROR');
            } else {
                // Логируем успешные запросы тоже
                $log_data['response_data'] = is_array($data) && isset($data['id']) ? array('id' => $data['id'], 'title' => $data['title'] ?? 'N/A') : 'Success';
                $this->log('REST API Success', $log_data, 'INFO');
            }
        }
        
        return $result;
    }

    /**
     * Логирование сохранения постов
     *
     * @param int     $post_id ID поста
     * @param WP_Post $post    Объект поста
     * @return void
     */
    public function log_save_post($post_id, $post) {
        if (in_array($post->post_type, ['cryptoschool_course', 'cryptoschool_lesson'])) {
            // Логируем только если это не автосохранение или ревизия
            if (!wp_is_post_autosave($post_id) && !wp_is_post_revision($post_id)) {
                $this->log('Save Post', array(
                    'post_id' => $post_id,
                    'post_type' => $post->post_type,
                    'post_title' => $post->post_title,
                    'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
                ));
            }
        }
    }

    /**
     * Логирование вставки постов
     *
     * @param int     $post_id ID поста
     * @param WP_Post $post    Объект поста
     * @param bool    $update  Обновление или создание
     * @return void
     */
    public function log_insert_post($post_id, $post, $update) {
        if (in_array($post->post_type, ['cryptoschool_course', 'cryptoschool_lesson'])) {
            $this->log('Insert Post', array(
                'post_id' => $post_id,
                'post_type' => $post->post_type,
                'is_update' => $update,
                'post_status' => $post->post_status,
                'post_title' => $post->post_title
            ));
        }
    }

    /**
     * Настройка обработчика ошибок
     *
     * @return void
     */
    public function setup_error_handler() {
        // Устанавливаем обработчик ошибок только для админки
        if (is_admin()) {
            set_error_handler(array($this, 'handle_php_error'));
            register_shutdown_function(array($this, 'handle_fatal_error'));
        }
    }

    /**
     * Обработка PHP ошибок
     *
     * @param int    $errno   Номер ошибки
     * @param string $errstr  Сообщение ошибки
     * @param string $errfile Файл с ошибкой
     * @param int    $errline Строка с ошибкой
     * @return bool
     */
    public function handle_php_error($errno, $errstr, $errfile, $errline) {
        // Логируем только ошибки связанные с нашим плагином
        if (strpos($errfile, 'cryptoschool') !== false) {
            $this->log('PHP Error', array(
                'errno' => $errno,
                'errstr' => $errstr,
                'errfile' => $errfile,
                'errline' => $errline,
                'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
            ));
        }
        
        return false; // Позволяем стандартному обработчику тоже сработать
    }

    /**
     * Обработка фатальных ошибок
     *
     * @return void
     */
    public function handle_fatal_error() {
        $error = error_get_last();
        
        if ($error && strpos($error['file'], 'cryptoschool') !== false) {
            $this->log('Fatal Error', array(
                'type' => $error['type'],
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line']
            ));
        }
    }

    /**
     * Логирование heartbeat запросов
     *
     * @return void
     */
    public function log_heartbeat() {
        if (isset($_POST['data']) && is_array($_POST['data'])) {
            $data = $_POST['data'];
            
            // Проверяем, есть ли данные связанные с нашими постами
            if (isset($data['wp_autosave']) || isset($data['post_id'])) {
                $this->log('Heartbeat', array(
                    'data' => $data,
                    'timestamp' => current_time('mysql')
                ));
            }
        }
    }

    /**
     * Логирование всех хуков (только для отладки)
     *
     * @param string $hook_name Название хука
     * @return void
     */
    public function log_all_hooks($hook_name) {
        // Логируем только хуки связанные с сохранением и REST API
        $relevant_hooks = [
            'rest_api_init',
            'rest_pre_dispatch',
            'rest_post_dispatch',
            'save_post',
            'wp_insert_post',
            'wp_insert_post_data',
            'pre_post_update',
            'post_updated'
        ];
        
        if (in_array($hook_name, $relevant_hooks)) {
            $this->log('Hook Called', array(
                'hook' => $hook_name,
                'args' => func_get_args(),
                'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
            ));
        }
    }

    /**
     * Логирование в файл
     *
     * @param string $type    Тип события
     * @param mixed  $data    Данные для логирования
     * @param string $level   Уровень логирования
     * @return void
     */
    private function log($type, $data, $level = 'DEBUG') {
        $timestamp = current_time('Y-m-d H:i:s');
        
        // Компактный формат логирования
        $log_message = '[' . $timestamp . '] [' . $level . '] [' . $type . '] ' . 
                      json_encode($data, JSON_UNESCAPED_UNICODE) . PHP_EOL;
        
        // Записываем только в отдельный файл для нашего плагина
        $plugin_log_file = WP_CONTENT_DIR . '/uploads/cryptoschool-logs/gutenberg-debug.log';
        
        // Создаем директорию если её нет
        $log_dir = dirname($plugin_log_file);
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        
        error_log($log_message, 3, $plugin_log_file);
    }

    /**
     * Очистка старых логов
     *
     * @return void
     */
    public function cleanup_logs() {
        $plugin_log_file = WP_CONTENT_DIR . '/uploads/cryptoschool-logs/gutenberg-debug.log';
        
        if (file_exists($plugin_log_file) && filesize($plugin_log_file) > 10 * 1024 * 1024) { // 10MB
            // Оставляем только последние 1000 строк
            $lines = file($plugin_log_file);
            $lines = array_slice($lines, -1000);
            file_put_contents($plugin_log_file, implode('', $lines));
        }
    }
}
