<?php
/**
 * Rate Limiting система для защиты от брутфорса
 *
 * @package CryptoSchool
 */

// Rate limiting система загружена

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс для ограничения частоты запросов
 */
class CryptoSchool_Rate_Limiting {
    
    /**
     * Префикс для ключей в базе данных
     */
    const CACHE_PREFIX = 'cryptoschool_rate_limit_';
    
    /**
     * Инициализация класса
     */
    public static function init() {
        // Проверка rate limiting для форм аутентификации
        add_action('login_form_login', [self::class, 'check_login_rate_limit'], 1);
        add_action('login_form_register', [self::class, 'check_register_rate_limit'], 1);
        add_action('login_form_lostpassword', [self::class, 'check_lostpassword_rate_limit'], 1);
        
        // Проверка rate limiting для AJAX запросов
        add_action('wp_ajax_nopriv_cryptoschool_glossary_search', [self::class, 'check_ajax_rate_limit'], 1);
        add_action('wp_ajax_cryptoschool_glossary_search', [self::class, 'check_ajax_rate_limit'], 1);
    }
    
    /**
     * Получение IP адреса пользователя
     *
     * @return string
     */
    private static function get_client_ip() {
        // Проверяем различные заголовки для получения реального IP
        $ip_keys = ['HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Если есть несколько IP (через запятую), берем первый
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                // Проверяем, что это валидный IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Проверка лимита для определенного действия
     *
     * @param string $action Тип действия
     * @param int $limit Максимальное количество запросов
     * @param int $window Окно времени в секундах
     * @param string $identifier Дополнительный идентификатор
     * @return bool
     */
    private static function is_rate_limited($action, $limit, $window, $identifier = '') {
        $ip = self::get_client_ip();
        $key = self::CACHE_PREFIX . $action . '_' . md5($ip . $identifier);
        
        // Получаем текущий счетчик
        $current_count = get_transient($key);
        
        if ($current_count === false) {
            // Первый запрос в окне времени
            set_transient($key, 1, $window);
            return false;
        }
        
        if ($current_count >= $limit) {
            // Превышен лимит
            return true;
        }
        
        // Увеличиваем счетчик
        set_transient($key, $current_count + 1, $window);
        return false;
    }
    
    /**
     * Логирование подозрительной активности
     *
     * @param string $action Тип действия
     * @param string $details Детали события
     */
    private static function log_suspicious_activity($action, $details = '') {
        $ip = self::get_client_ip();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        // Логирование в файловую систему безопасности (без дублирования в error_log)
        if (class_exists('CryptoSchool_Security_Logger')) {
            CryptoSchool_Security_Logger::log(
                'threats',
                'rate_limit_exceeded',
                "Rate limit exceeded for action: {$action}",
                CryptoSchool_Security_Logger::LEVEL_WARNING,
                [
                    'action' => $action,
                    'details' => $details,
                    'user_agent' => $user_agent
                ]
            );
        }
        
        // Также сохраняем в базу данных для админки
        self::save_security_log($action, $ip, $details);
    }
    
    /**
     * Сохранение лога безопасности в базу данных
     *
     * @param string $action Действие
     * @param string $ip IP адрес
     * @param string $details Детали
     */
    private static function save_security_log($action, $ip, $details) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cryptoschool_security_logs';
        
        // Создаем таблицу если её нет
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            action varchar(50) NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text,
            details text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_ip_action (ip_address, action),
            KEY idx_created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Сохраняем запись
        $wpdb->insert(
            $table_name,
            [
                'action' => $action,
                'ip_address' => $ip,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'details' => $details,
                'created_at' => current_time('mysql')
            ]
        );
    }
    
    /**
     * Проверка rate limiting для входа
     */
    public static function check_login_rate_limit() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        
        // Отключаем для локальной разработки
        $is_local = in_array($_SERVER['HTTP_HOST'] ?? '', [
            'localhost',
            '127.0.0.1',
            '::1'
        ]) || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost:') === 0;
        
        if ($is_local) {
            return;
        }
        
        // 10 попыток входа в течение 15 минут (увеличено для удобства)
        if (self::is_rate_limited('login', 10, 900)) {
            self::log_suspicious_activity('login_rate_limit', 'Too many login attempts');
            
            wp_die(
                'Слишком много попыток входа. Пожалуйста, попробуйте через 15 минут.',
                'Rate limit exceeded',
                ['response' => 429]
            );
        }
    }
    
    /**
     * Проверка rate limiting для регистрации
     */
    public static function check_register_rate_limit() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        
        // 20 регистраций в течение часа
        if (self::is_rate_limited('register', 20, 3600)) {
            self::log_suspicious_activity('register_rate_limit', 'Too many registration attempts');
            
            wp_die(
                'Слишком много попыток регистрации. Пожалуйста, попробуйте через час.',
                'Rate limit exceeded',
                ['response' => 429]
            );
        }
    }
    
    /**
     * Проверка rate limiting для восстановления пароля
     */
    public static function check_lostpassword_rate_limit() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        
        // 10 запросов на восстановление в течение часа
        if (self::is_rate_limited('lostpassword', 10, 3600)) {
            self::log_suspicious_activity('lostpassword_rate_limit', 'Too many password reset attempts');
            
            wp_die(
                'Слишком много запросов на восстановление пароля. Пожалуйста, попробуйте через час.',
                'Rate limit exceeded',
                ['response' => 429]
            );
        }
    }
    
    /**
     * Проверка rate limiting для AJAX запросов
     */
    public static function check_ajax_rate_limit() {
        // 30 AJAX запросов в минуту
        if (self::is_rate_limited('ajax', 30, 60)) {
            self::log_suspicious_activity('ajax_rate_limit', 'Too many AJAX requests');
            
            wp_send_json_error('Слишком много запросов. Пожалуйста, подождите минуту.');
        }
    }
    
    /**
     * Проверка на подозрительную активность по User-Agent
     *
     * @return bool
     */
    public static function is_suspicious_user_agent() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Подозрительные User-Agent паттерны
        $suspicious_patterns = [
            '/bot/i',
            '/crawler/i',
            '/spider/i',
            '/scan/i',
            '/curl/i',
            '/wget/i',
            '/python/i',
            '/perl/i',
            '/java/i',
            '/^$/i' // Пустой user agent
        ];
        
        foreach ($suspicious_patterns as $pattern) {
            if (preg_match($pattern, $user_agent)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Блокировка подозрительных запросов
     */
    public static function block_suspicious_requests() {
        // Получаем URI запроса заранее
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        
        // Отключаем для локальной разработки
        $is_local = in_array($host, [
            'localhost',
            '127.0.0.1',
            '::1'
        ]) || strpos($host, 'localhost:') === 0;
        
        if ($is_local) {
            return; // Не блокируем в локальной среде
        }
        
        // Проверяем, что это админ-панель или страница входа
        $admin_pages = ['/wp-admin/', '/wp-login.php', '/admin-ajax.php'];
        $is_admin_page = false;
        foreach ($admin_pages as $page) {
            if (strpos($request_uri, $page) !== false) {
                $is_admin_page = true;
                break;
            }
        }

        // Исключаем админ-панель и администраторов
        if ($is_admin_page || is_admin() || (defined('DOING_AJAX') && DOING_AJAX)) {
            return;
        }

        // Дополнительная проверка для администраторов
        if (is_user_logged_in() && current_user_can('manage_options')) {
            return;
        }
        
        // Проверяем User-Agent
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Empty';
        $is_suspicious_ua = self::is_suspicious_user_agent();
        
        if ($is_suspicious_ua) {
            // Логируем только в течение часа для одного IP
            if (!self::is_rate_limited('suspicious_ua_log', 1, 3600, $user_agent)) {
                self::log_suspicious_activity('suspicious_user_agent', 'User-Agent: ' . $user_agent);
            }
            
            // Блокируем на 24 часа при подозрительной активности
            if (self::is_rate_limited('suspicious_ua_block', 10, 86400, $user_agent)) {
                wp_die('Access denied', 'Forbidden', ['response' => 403]);
            }
        }
        
        // Проверяем количество разных страниц за короткое время (возможный скан)
        // Увеличено до 200 запросов за 5 минут, чтобы не блокировать обычных пользователей
        $page_scan_limited = self::is_rate_limited('page_scan', 200, 300, '');
        
        if ($page_scan_limited) {
            self::log_suspicious_activity('possible_scan', 'URI: ' . $request_uri);
            
            // Блокируем сканирование только после превышения нового лимита
            wp_die('Access denied', 'Forbidden', ['response' => 403]);
        }
    }
    
    /**
     * Получение статистики для админки
     *
     * @param int $days Количество дней для статистики
     * @return array
     */
    public static function get_security_stats($days = 7) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cryptoschool_security_logs';
        $since_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $stats = $wpdb->get_results($wpdb->prepare("
            SELECT 
                action,
                COUNT(*) as count,
                COUNT(DISTINCT ip_address) as unique_ips
            FROM `{$table_name}` 
            WHERE created_at >= %s 
            GROUP BY action
            ORDER BY count DESC
        ", $since_date), ARRAY_A);
        
        return $stats ?: [];
    }
    
    /**
     * Очистка старых логов
     */
    public static function cleanup_old_logs() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cryptoschool_security_logs';
        $cutoff_date = date('Y-m-d H:i:s', strtotime('-30 days'));
        
        $wpdb->query($wpdb->prepare("
            DELETE FROM `{$table_name}` 
            WHERE created_at < %s
        ", $cutoff_date));
    }
}

// Инициализация класса
CryptoSchool_Rate_Limiting::init();

// Проверка подозрительных запросов на каждой странице
add_action('init', [CryptoSchool_Rate_Limiting::class, 'block_suspicious_requests'], 1);

// Еженедельная очистка логов
add_action('wp_scheduled_delete', [CryptoSchool_Rate_Limiting::class, 'cleanup_old_logs']);
