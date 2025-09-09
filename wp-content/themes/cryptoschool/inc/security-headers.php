<?php
/**
 * Система заголовков безопасности
 *
 * @package CryptoSchool
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс для управления заголовками безопасности
 */
class CryptoSchool_Security_Headers {
    
    /**
     * Инициализация класса
     */
    public static function init() {
        // Добавляем заголовки безопасности
        add_action('send_headers', [self::class, 'set_security_headers']);
        
        // Принудительное использование HTTPS
        add_action('template_redirect', [self::class, 'force_https']);
        
        // Удаляем информацию о версии WordPress
        add_filter('the_generator', '__return_empty_string');
        remove_action('wp_head', 'wp_generator');
        
        // Скрываем информацию в заголовках
        add_filter('wp_headers', [self::class, 'remove_wp_headers']);
        
        // Отключаем XML-RPC (если не нужен)
        add_filter('xmlrpc_enabled', '__return_false');
        
        // Ограничиваем доступ к REST API
        add_filter('rest_authentication_errors', [self::class, 'restrict_rest_api_access']);
        
        // Отключаем редактирование файлов из админки
        if (!defined('DISALLOW_FILE_EDIT')) {
            define('DISALLOW_FILE_EDIT', true);
        }
    }
    
    /**
     * Установка заголовков безопасности
     */
    public static function set_security_headers() {
        $is_local = in_array($_SERVER['HTTP_HOST'] ?? '', [
            'localhost',
            '127.0.0.1', 
            '::1'
        ]) || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost:') === 0;
        
        // Основные заголовки безопасности (не ломающие функционал)
        
        // X-Frame-Options - защита от clickjacking
        header('X-Frame-Options: SAMEORIGIN');
        
        // X-Content-Type-Options - предотвращает MIME-sniffing
        header('X-Content-Type-Options: nosniff');
        
        // X-XSS-Protection - защита от XSS (для старых браузеров)
        header('X-XSS-Protection: 1; mode=block');
        
        // Referrer Policy - контроль передачи referrer
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Strict-Transport-Security (HSTS) - только для HTTPS и продакшена
        if (is_ssl() && !$is_local) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
        
        // Базовые Permissions Policy (только критичные ограничения)
        $basic_permissions = [
            'camera=()',
            'microphone=()',
            'geolocation=()'
        ];
        header("Permissions-Policy: " . implode(', ', $basic_permissions));
        
        // Скрываем информацию о сервере
        if (!headers_sent()) {
            header_remove('Server');
            header_remove('X-Powered-By');
        }
    }
    
    
    /**
     * Принудительное перенаправление на HTTPS
     */
    public static function force_https() {
        // Отключаем принудительное HTTPS для локальной разработки
        $is_local = in_array($_SERVER['HTTP_HOST'] ?? '', [
            'localhost',
            '127.0.0.1',
            '::1'
        ]) || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost:') === 0;
        
        if ($is_local) {
            return; // Не перенаправляем на HTTPS в локальной среде
        }
        
        // Проверяем, включена ли принудительная HTTPS переадресация
        $force_https = get_option('cryptoschool_force_https', false); // По умолчанию выключено
        
        if ($force_https && !is_ssl() && !is_admin()) {
            // Проверяем, не находимся ли мы уже в процессе переадресации
            if (!isset($_SERVER['HTTP_X_FORWARDED_PROTO']) || $_SERVER['HTTP_X_FORWARDED_PROTO'] !== 'https') {
                $redirect_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                wp_redirect($redirect_url, 301);
                exit;
            }
        }
    }
    
    /**
     * Удаление заголовков WordPress
     *
     * @param array $headers Заголовки HTTP
     * @return array
     */
    public static function remove_wp_headers($headers) {
        // Удаляем заголовки, раскрывающие версию WordPress
        unset($headers['X-Pingback']);
        
        // Добавляем собственные заголовки
        $headers['X-Robots-Tag'] = 'noindex, nofollow, nosnippet, noarchive';
        
        return $headers;
    }
    
    /**
     * Ограничение доступа к REST API
     *
     * @param mixed $result Результат аутентификации
     * @return mixed
     */
    public static function restrict_rest_api_access($result) {
        // Отключаем ограничения REST API для локальной среды
        $is_local = in_array($_SERVER['HTTP_HOST'] ?? '', [
            'localhost',
            '127.0.0.1',
            '::1'
        ]) || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost:') === 0;
        
        if ($is_local) {
            return $result; // Не применяем ограничения на локальной среде
        }
        
        // Если пользователь не авторизован
        if (!is_user_logged_in()) {
            // Разрешаем доступ только к определенным endpoints
            $allowed_endpoints = [
                '/wp/v2/users/me',
                '/wp/v2/comments',
                '/cryptoschool/v1/glossary-search',
                // WPML endpoints
                '/wpml/',
                '/wp/v2/',
                // WordPress core endpoints нужные для WPML
                'wp/v2/types',
                'wp/v2/statuses',
                'wp/v2/taxonomies',
                // Корневой REST API endpoint
                ''
            ];
            
            $current_route = $GLOBALS['wp']->query_vars['rest_route'] ?? '';
            
            $is_allowed = false;
            foreach ($allowed_endpoints as $endpoint) {
                if (strpos($current_route, $endpoint) === 0) {
                    $is_allowed = true;
                    break;
                }
            }
            
            if (!$is_allowed) {
                return new WP_Error(
                    'rest_not_logged_in',
                    'You are not currently logged in.',
                    ['status' => 401]
                );
            }
        }
        
        return $result;
    }
    
    /**
     * Отключение ненужных WordPress features
     */
    public static function disable_wp_features() {
        // Отключаем WordPress embeds
        remove_action('wp_head', 'wp_oembed_add_discovery_links');
        remove_action('wp_head', 'wp_oembed_add_host_js');
        
        // Отключаем RSD link
        remove_action('wp_head', 'rsd_link');
        
        // Отключаем Windows Live Writer
        remove_action('wp_head', 'wlwmanifest_link');
        
        // Отключаем RSS feeds
        remove_action('wp_head', 'feed_links_extra', 3);
        remove_action('wp_head', 'feed_links', 2);
        
        // Отключаем WordPress shortlinks
        remove_action('wp_head', 'wp_shortlink_wp_head');
        
        // Отключаем REST API links
        remove_action('wp_head', 'rest_output_link_wp_head');
        
        // Отключаем emoji scripts
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('admin_print_styles', 'print_emoji_styles');
    }
    
    /**
     * Настройка cookie безопасности
     */
    public static function secure_cookies() {
        // Устанавливаем secure флаги для cookies
        if (is_ssl()) {
            ini_set('session.cookie_secure', 1);
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_samesite', 'Strict');
        }
    }
    
    /**
     * Защита от информационных утечек
     */
    public static function prevent_information_disclosure() {
        // Отключаем отображение ошибок PHP на продакшене
        if (!WP_DEBUG) {
            ini_set('display_errors', 0);
            ini_set('log_errors', 1);
        }
        
        // Скрываем версию PHP
        if (function_exists('header_remove')) {
            header_remove('X-Powered-By');
        }
        
        // Блокируем доступ к конфиденциальным файлам через .htaccess
        add_action('init', [self::class, 'protect_sensitive_files']);
    }
    
    /**
     * Защита конфиденциальных файлов
     */
    public static function protect_sensitive_files() {
        $htaccess_content = "# Защита конфиденциальных файлов\n";
        $htaccess_content .= "<Files ~ \"^\\.(htaccess|htpasswd|ini|log|sh|inc|bak|config)$\">\n";
        $htaccess_content .= "    Order allow,deny\n";
        $htaccess_content .= "    Deny from all\n";
        $htaccess_content .= "    Satisfy All\n";
        $htaccess_content .= "</Files>\n\n";
        
        $htaccess_content .= "# Блокировка доступа к wp-config.php\n";
        $htaccess_content .= "<Files wp-config.php>\n";
        $htaccess_content .= "    Order allow,deny\n";
        $htaccess_content .= "    Deny from all\n";
        $htaccess_content .= "</Files>\n\n";
        
        $htaccess_content .= "# Блокировка доступа к readme.html и license.txt\n";
        $htaccess_content .= "<FilesMatch \"^(readme|license|changelog)\\.(html|txt)$\">\n";
        $htaccess_content .= "    Order allow,deny\n";
        $htaccess_content .= "    Deny from all\n";
        $htaccess_content .= "</FilesMatch>\n";
        
        // Добавляем в корневой .htaccess (осторожно)
        $htaccess_file = ABSPATH . '.htaccess';
        if (is_writable(dirname($htaccess_file))) {
            $existing_content = file_exists($htaccess_file) ? file_get_contents($htaccess_file) : '';
            
            // Проверяем, нет ли уже наших правил
            if (strpos($existing_content, '# Защита конфиденциальных файлов') === false) {
                file_put_contents($htaccess_file, $htaccess_content . "\n" . $existing_content);
            }
        }
    }
    
    /**
     * Получение отчета о безопасности
     *
     * @return array
     */
    public static function get_security_report() {
        $report = [
            'https_enabled' => is_ssl(),
            'wp_version_hidden' => !has_action('wp_head', 'wp_generator'),
            'xmlrpc_disabled' => !apply_filters('xmlrpc_enabled', true),
            'file_editing_disabled' => defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT,
            'debug_disabled' => !WP_DEBUG,
            'uploads_protected' => file_exists(wp_upload_dir()['basedir'] . '/.htaccess'),
            'security_headers_active' => true, // Всегда активны после инициализации
        ];
        
        $report['security_score'] = array_sum($report) / count($report) * 100;
        
        return $report;
    }
}

// Инициализация класса
CryptoSchool_Security_Headers::init();

// Дополнительные меры безопасности
CryptoSchool_Security_Headers::disable_wp_features();
CryptoSchool_Security_Headers::secure_cookies();
CryptoSchool_Security_Headers::prevent_information_disclosure();