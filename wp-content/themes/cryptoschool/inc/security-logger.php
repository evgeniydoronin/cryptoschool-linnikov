<?php
/**
 * Система файлового логирования событий безопасности
 *
 * @package CryptoSchool
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс для логирования событий безопасности в файлы
 */
class CryptoSchool_Security_Logger {
    
    /**
     * Базовая директория для логов
     */
    const LOG_DIR = WP_CONTENT_DIR . '/security-logs';
    
    /**
     * Максимальный размер файла лога (10MB)
     */
    const MAX_LOG_FILE_SIZE = 10485760;
    
    /**
     * Срок хранения логов (дни)
     */
    const LOG_RETENTION_DAYS = 30;
    
    /**
     * Уровни важности событий
     */
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_CRITICAL = 'critical';
    
    /**
     * Инициализация системы логирования
     */
    public static function init() {
        // Создаем структуру директорий
        self::create_log_directories();
        
        // Защищаем директорию логов
        self::protect_log_directory();
        
        // Регистрируем хуки для очистки логов
        add_action('wp_scheduled_delete', [self::class, 'cleanup_old_logs']);
        
        // Регистрируем хуки для логирования различных событий
        add_action('wp_login', [self::class, 'log_successful_login'], 10, 2);
        add_action('wp_login_failed', [self::class, 'log_failed_login']);
        add_action('user_register', [self::class, 'log_user_registration']);
        add_action('password_reset', [self::class, 'log_password_reset'], 10, 2);
        add_action('admin_init', [self::class, 'log_admin_access']);
        add_action('init', [self::class, 'log_suspicious_requests'], 1);
    }
    
    /**
     * Создание структуры директорий для логов
     */
    private static function create_log_directories() {
        $directories = [
            self::LOG_DIR,
            self::LOG_DIR . '/auth',
            self::LOG_DIR . '/threats',
            self::LOG_DIR . '/access',
            self::LOG_DIR . '/summary',
            self::LOG_DIR . '/archive'
        ];
        
        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
            }
        }
    }
    
    /**
     * Защита директории логов
     */
    private static function protect_log_directory() {
        $htaccess_file = self::LOG_DIR . '/.htaccess';
        $htaccess_content = "Order deny,allow\nDeny from all\n";
        
        if (!file_exists($htaccess_file)) {
            file_put_contents($htaccess_file, $htaccess_content);
        }
        
        // Добавляем index.php для дополнительной защиты
        $index_file = self::LOG_DIR . '/index.php';
        if (!file_exists($index_file)) {
            file_put_contents($index_file, '<?php // Silence is golden');
        }
    }
    
    /**
     * Основной метод для записи лога
     *
     * @param string $category Категория события (auth, threats, access, etc.)
     * @param string $event_type Тип события
     * @param string $message Сообщение
     * @param string $level Уровень важности
     * @param array $context Дополнительный контекст
     */
    public static function log($category, $event_type, $message, $level = self::LEVEL_INFO, $context = []) {
        // Получаем информацию о запросе
        $request_info = self::get_request_info();
        
        // Формируем запись лога
        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level,
            'category' => $category,
            'event_type' => $event_type,
            'message' => $message,
            'ip' => $request_info['ip'],
            'user_agent' => $request_info['user_agent'],
            'request_uri' => $request_info['request_uri'],
            'request_method' => $request_info['request_method'],
            'referer' => $request_info['referer'],
            'user_id' => get_current_user_id(),
            'session_id' => session_id(),
            'context' => $context
        ];
        
        // Записываем в файл (человекочитаемый формат)
        self::write_to_file($category, $event_type, $log_entry, 'readable');
        
        // Записываем в JSON файл для парсинга
        self::write_to_file($category, $event_type, $log_entry, 'json');
        
        // Отправляем алерт при критических событиях
        if ($level === self::LEVEL_CRITICAL) {
            self::send_alert($log_entry);
        }
    }
    
    /**
     * Получение информации о текущем запросе
     *
     * @return array
     */
    private static function get_request_info() {
        return [
            'ip' => self::get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
            'referer' => $_SERVER['HTTP_REFERER'] ?? '',
            'host' => $_SERVER['HTTP_HOST'] ?? '',
            'protocol' => isset($_SERVER['HTTPS']) ? 'https' : 'http'
        ];
    }
    
    /**
     * Получение реального IP адреса клиента
     *
     * @return string
     */
    private static function get_client_ip() {
        $ip_keys = ['HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Запись лога в файл
     *
     * @param string $category Категория
     * @param string $event_type Тип события
     * @param array $log_entry Данные для записи
     * @param string $format Формат (readable или json)
     */
    private static function write_to_file($category, $event_type, $log_entry, $format = 'readable') {
        $date = date('Y-m-d');
        $filename = sanitize_file_name("{$event_type}-{$date}.log");
        $filepath = self::LOG_DIR . "/{$category}/{$filename}";
        
        // Проверяем размер файла и ротируем если нужно
        if (file_exists($filepath) && filesize($filepath) > self::MAX_LOG_FILE_SIZE) {
            self::rotate_log_file($filepath);
        }
        
        if ($format === 'json') {
            $filepath .= '.json';
            $content = json_encode($log_entry, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
        } else {
            // Человекочитаемый формат
            $content = sprintf(
                "[%s] %s [%s] %s - IP: %s, User-Agent: %s, URI: %s\n",
                $log_entry['timestamp'],
                strtoupper($log_entry['level']),
                $log_entry['event_type'],
                $log_entry['message'],
                $log_entry['ip'],
                substr($log_entry['user_agent'], 0, 100),
                $log_entry['request_uri']
            );
        }
        
        // Блокирующая запись в файл
        $file = fopen($filepath, 'a');
        if ($file) {
            if (flock($file, LOCK_EX)) {
                fwrite($file, $content);
                flock($file, LOCK_UN);
            }
            fclose($file);
        }
    }
    
    /**
     * Ротация файла лога
     *
     * @param string $filepath Путь к файлу
     */
    private static function rotate_log_file($filepath) {
        $timestamp = date('His');
        $archive_path = str_replace(self::LOG_DIR, self::LOG_DIR . '/archive', $filepath);
        $archive_dir = dirname($archive_path);
        
        if (!file_exists($archive_dir)) {
            wp_mkdir_p($archive_dir);
        }
        
        $archive_file = str_replace('.log', "-{$timestamp}.log", $archive_path);
        rename($filepath, $archive_file);
        
        // Сжимаем архивный файл если доступен gzip
        if (function_exists('gzencode')) {
            $compressed = gzencode(file_get_contents($archive_file));
            file_put_contents($archive_file . '.gz', $compressed);
            unlink($archive_file);
        }
    }
    
    /**
     * Логирование успешного входа
     *
     * @param string $user_login Логин пользователя
     * @param WP_User $user Объект пользователя
     */
    public static function log_successful_login($user_login, $user) {
        self::log(
            'auth',
            'login_success',
            "Successful login for user: {$user_login}",
            self::LEVEL_INFO,
            [
                'user_id' => $user->ID,
                'user_roles' => $user->roles,
                'login_method' => 'standard'
            ]
        );
    }
    
    /**
     * Логирование неудачного входа
     *
     * @param string $username Имя пользователя
     */
    public static function log_failed_login($username) {
        self::log(
            'auth',
            'login_failed',
            "Failed login attempt for username: {$username}",
            self::LEVEL_WARNING,
            [
                'attempted_username' => $username
            ]
        );
    }
    
    /**
     * Логирование регистрации пользователя
     *
     * @param int $user_id ID пользователя
     */
    public static function log_user_registration($user_id) {
        $user = get_user_by('id', $user_id);
        self::log(
            'auth',
            'user_registration',
            "New user registered: {$user->user_login}",
            self::LEVEL_INFO,
            [
                'user_id' => $user_id,
                'user_email' => $user->user_email
            ]
        );
    }
    
    /**
     * Логирование сброса пароля
     *
     * @param WP_User $user Объект пользователя
     * @param string $new_pass Новый пароль (хешированный)
     */
    public static function log_password_reset($user, $new_pass) {
        self::log(
            'auth',
            'password_reset',
            "Password reset for user: {$user->user_login}",
            self::LEVEL_INFO,
            [
                'user_id' => $user->ID
            ]
        );
    }
    
    /**
     * Логирование доступа к админке
     */
    public static function log_admin_access() {
        if (!is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }
        
        $current_user = wp_get_current_user();
        if ($current_user->ID === 0) {
            // Неавторизованная попытка доступа к админке
            self::log(
                'access',
                'unauthorized_admin',
                'Unauthorized admin access attempt',
                self::LEVEL_WARNING
            );
        } else {
            // Обычный доступ к админке
            self::log(
                'access',
                'admin_access',
                "Admin access by user: {$current_user->user_login}",
                self::LEVEL_INFO,
                [
                    'user_id' => $current_user->ID,
                    'admin_page' => $_GET['page'] ?? 'dashboard'
                ]
            );
        }
    }
    
    /**
     * Логирование подозрительных запросов
     */
    public static function log_suspicious_requests() {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $query_string = $_SERVER['QUERY_STRING'] ?? '';
        
        // Проверка на SQL инъекции в URL
        $sql_patterns = [
            '/union\s+select/i',
            '/drop\s+table/i',
            '/insert\s+into/i',
            '/update\s+set/i',
            '/delete\s+from/i',
            '/\'\s*or\s*\d+\s*=\s*\d+/i',
            '/\'\s*union/i'
        ];
        
        foreach ($sql_patterns as $pattern) {
            if (preg_match($pattern, $query_string)) {
                self::log(
                    'threats',
                    'sql_injection',
                    'SQL injection attempt detected',
                    self::LEVEL_CRITICAL,
                    [
                        'pattern_matched' => $pattern,
                        'query_string' => $query_string
                    ]
                );
                break;
            }
        }
        
        // Проверка на XSS в параметрах
        $xss_patterns = [
            '/<script[^>]*>/i',
            '/javascript:/i',
            '/onload\s*=/i',
            '/onerror\s*=/i',
            '/<iframe[^>]*>/i'
        ];
        
        foreach ($xss_patterns as $pattern) {
            if (preg_match($pattern, $query_string)) {
                self::log(
                    'threats',
                    'xss_attempt',
                    'XSS attempt detected',
                    self::LEVEL_CRITICAL,
                    [
                        'pattern_matched' => $pattern,
                        'query_string' => $query_string
                    ]
                );
                break;
            }
        }
        
        // Проверка подозрительных User-Agent
        $suspicious_ua_patterns = [
            '/sqlmap/i',
            '/nmap/i',
            '/nikto/i',
            '/masscan/i',
            '/acunetix/i',
            '/burpsuite/i',
            '/python-requests/i'
        ];
        
        foreach ($suspicious_ua_patterns as $pattern) {
            if (preg_match($pattern, $user_agent)) {
                self::log(
                    'threats',
                    'suspicious_user_agent',
                    'Suspicious User-Agent detected',
                    self::LEVEL_WARNING,
                    [
                        'pattern_matched' => $pattern,
                        'full_user_agent' => $user_agent
                    ]
                );
                break;
            }
        }
        
        // Проверка попыток доступа к конфиденциальным файлам
        $sensitive_files = [
            '/wp-config.php',
            '/.env',
            '/config.php',
            '/database.php',
            '/.htaccess',
            '/wp-admin/install.php',
            '/readme.html'
        ];
        
        foreach ($sensitive_files as $file) {
            if (strpos($request_uri, $file) !== false) {
                self::log(
                    'access',
                    'sensitive_file_access',
                    "Attempt to access sensitive file: {$file}",
                    self::LEVEL_WARNING,
                    [
                        'requested_file' => $file
                    ]
                );
                break;
            }
        }
    }
    
    /**
     * Отправка алерта при критических событиях
     *
     * @param array $log_entry Данные события
     */
    private static function send_alert($log_entry) {
        // Проверяем, не отправляли ли мы уже алерт для этого IP в последние 5 минут
        $cache_key = 'security_alert_' . md5($log_entry['ip'] . $log_entry['event_type']);
        if (get_transient($cache_key)) {
            return; // Алерт уже отправлен
        }
        
        $admin_email = get_option('admin_email');
        if (!$admin_email) {
            return;
        }
        
        $subject = '[SECURITY ALERT] ' . $log_entry['event_type'] . ' - ' . get_bloginfo('name');
        $message = sprintf(
            "КРИТИЧЕСКОЕ СОБЫТИЕ БЕЗОПАСНОСТИ\n\n" .
            "Сайт: %s\n" .
            "Время: %s\n" .
            "Тип события: %s\n" .
            "Сообщение: %s\n" .
            "IP адрес: %s\n" .
            "User-Agent: %s\n" .
            "URI: %s\n\n" .
            "Пожалуйста, проверьте логи безопасности для получения дополнительной информации.",
            get_bloginfo('name'),
            $log_entry['timestamp'],
            $log_entry['event_type'],
            $log_entry['message'],
            $log_entry['ip'],
            $log_entry['user_agent'],
            $log_entry['request_uri']
        );
        
        wp_mail($admin_email, $subject, $message);
        
        // Устанавливаем кеш на 5 минут
        set_transient($cache_key, true, 300);
    }
    
    /**
     * Очистка старых логов
     */
    public static function cleanup_old_logs() {
        $cutoff_date = strtotime('-' . self::LOG_RETENTION_DAYS . ' days');
        
        $directories = [
            self::LOG_DIR . '/auth',
            self::LOG_DIR . '/threats',
            self::LOG_DIR . '/access',
            self::LOG_DIR . '/summary',
            self::LOG_DIR . '/archive'
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            
            $files = glob($dir . '/*.{log,json,gz}', GLOB_BRACE);
            foreach ($files as $file) {
                if (filemtime($file) < $cutoff_date) {
                    unlink($file);
                }
            }
        }
    }
    
    /**
     * Получение статистики логов
     *
     * @param int $days Количество дней для анализа
     * @return array
     */
    public static function get_log_statistics($days = 7) {
        $stats = [
            'total_events' => 0,
            'by_level' => [
                self::LEVEL_INFO => 0,
                self::LEVEL_WARNING => 0,
                self::LEVEL_CRITICAL => 0
            ],
            'by_category' => [],
            'top_ips' => [],
            'recent_threats' => []
        ];
        
        $start_date = date('Y-m-d', strtotime("-{$days} days"));
        $directories = glob(self::LOG_DIR . '/*/*.json');
        
        foreach ($directories as $file) {
            $file_date = basename($file, '.log.json');
            $file_date = substr($file_date, strrpos($file_date, '-') + 1, 10);
            
            if ($file_date >= $start_date) {
                $content = file_get_contents($file);
                $lines = explode("\n", trim($content));
                
                foreach ($lines as $line) {
                    if (empty($line)) continue;
                    
                    $entry = json_decode($line, true);
                    if ($entry) {
                        $stats['total_events']++;
                        $stats['by_level'][$entry['level']]++;
                        
                        if (!isset($stats['by_category'][$entry['category']])) {
                            $stats['by_category'][$entry['category']] = 0;
                        }
                        $stats['by_category'][$entry['category']]++;
                        
                        if (!isset($stats['top_ips'][$entry['ip']])) {
                            $stats['top_ips'][$entry['ip']] = 0;
                        }
                        $stats['top_ips'][$entry['ip']]++;
                        
                        if ($entry['level'] === self::LEVEL_CRITICAL) {
                            $stats['recent_threats'][] = $entry;
                        }
                    }
                }
            }
        }
        
        // Сортируем IP по количеству событий
        arsort($stats['top_ips']);
        $stats['top_ips'] = array_slice($stats['top_ips'], 0, 10, true);
        
        // Ограничиваем количество последних угроз
        $stats['recent_threats'] = array_slice($stats['recent_threats'], -20);
        
        return $stats;
    }
}

// Инициализация системы логирования
CryptoSchool_Security_Logger::init();