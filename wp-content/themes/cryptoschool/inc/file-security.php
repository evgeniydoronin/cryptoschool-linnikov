<?php
/**
 * Система безопасности загружаемых файлов
 *
 * @package CryptoSchool
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс для контроля безопасности файлов
 */
class CryptoSchool_File_Security {
    
    /**
     * Разрешенные типы файлов для загрузки (обычные пользователи)
     */
    const ALLOWED_FILE_TYPES = [
        'image/jpeg',
        'image/jpg', 
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'text/plain',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];
    
    /**
     * Дополнительные типы файлов для администраторов (плагины, темы)
     */
    const ADMIN_ALLOWED_FILE_TYPES = [
        'application/zip',
        'application/x-zip-compressed',
        'application/x-zip',
        'application/octet-stream'
    ];
    
    /**
     * Разрешенные расширения файлов (обычные пользователи)
     */
    const ALLOWED_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif', 'webp',
        'pdf', 'txt', 'doc', 'docx', 'xls', 'xlsx'
    ];
    
    /**
     * Дополнительные расширения для администраторов
     */
    const ADMIN_ALLOWED_EXTENSIONS = [
        'zip'
    ];
    
    /**
     * Максимальный размер файла в байтах (100MB для плагинов и тем)
     */
    const MAX_FILE_SIZE = 104857600;
    
    /**
     * Инициализация класса
     */
    public static function init() {
        // Ограничение типов файлов при загрузке
        add_filter('upload_mimes', [self::class, 'restrict_file_types']);
        
        // Дополнительная проверка файлов
        add_filter('wp_handle_upload_prefilter', [self::class, 'validate_file_upload']);
        
        // Проверка файлов после загрузки
        add_filter('wp_handle_upload', [self::class, 'scan_uploaded_file']);
        
        // Защита от исполнения PHP файлов в uploads
        add_action('init', [self::class, 'protect_uploads_directory']);
        
        // Ограничение размера загружаемых файлов
        add_filter('upload_size_limit', [self::class, 'limit_upload_size']);
        
        // Переименование загружаемых файлов
        add_filter('sanitize_file_name', [self::class, 'sanitize_filename'], 10, 1);
    }
    
    /**
     * Ограничение типов файлов для загрузки
     *
     * @param array $mimes Разрешенные MIME типы
     * @return array
     */
    public static function restrict_file_types($mimes) {
        // Удаляем опасные типы файлов
        unset($mimes['exe']);
        unset($mimes['bat']);
        unset($mimes['cmd']);
        unset($mimes['com']);
        unset($mimes['pif']);
        unset($mimes['scr']);
        unset($mimes['vbs']);
        unset($mimes['js']);
        unset($mimes['jar']);
        unset($mimes['php']);
        unset($mimes['phtml']);
        unset($mimes['php3']);
        unset($mimes['php4']);
        unset($mimes['php5']);
        unset($mimes['phps']);
        unset($mimes['pl']);
        unset($mimes['py']);
        unset($mimes['rb']);
        unset($mimes['sh']);
        
        // Определяем разрешенные типы в зависимости от роли пользователя
        $allowed_types = self::ALLOWED_FILE_TYPES;
        
        // Администраторы могут загружать дополнительные типы (ZIP для плагинов/тем)
        if (current_user_can('administrator') || current_user_can('install_plugins') || current_user_can('install_themes')) {
            $allowed_types = array_merge($allowed_types, self::ADMIN_ALLOWED_FILE_TYPES);
        }
        
        // Оставляем только разрешенные типы
        $allowed_mimes = [];
        foreach ($mimes as $ext => $mime) {
            if (in_array($mime, $allowed_types)) {
                $allowed_mimes[$ext] = $mime;
            }
        }
        
        // Добавляем ZIP файлы для администраторов если их нет в стандартных MIME типах
        if (current_user_can('administrator') || current_user_can('install_plugins') || current_user_can('install_themes')) {
            $allowed_mimes['zip'] = 'application/zip';
        }
        
        return $allowed_mimes;
    }
    
    /**
     * Валидация файла перед загрузкой
     *
     * @param array $file Массив с данными файла
     * @return array
     */
    public static function validate_file_upload($file) {
        // Проверка размера файла
        if ($file['size'] > self::MAX_FILE_SIZE) {
            $file['error'] = 'Файл слишком большой. Максимальный размер: ' . (self::MAX_FILE_SIZE / 1048576) . 'MB';
            return $file;
        }
        
        // Определяем разрешенные расширения и типы в зависимости от роли пользователя
        $allowed_extensions = self::ALLOWED_EXTENSIONS;
        $allowed_types = self::ALLOWED_FILE_TYPES;
        
        // Администраторы могут загружать дополнительные типы файлов
        if (current_user_can('administrator') || current_user_can('install_plugins') || current_user_can('install_themes')) {
            $allowed_extensions = array_merge($allowed_extensions, self::ADMIN_ALLOWED_EXTENSIONS);
            $allowed_types = array_merge($allowed_types, self::ADMIN_ALLOWED_FILE_TYPES);
        }
        
        // Проверка расширения файла
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_ext, $allowed_extensions)) {
            $file['error'] = 'Недопустимый тип файла. Разрешены: ' . implode(', ', $allowed_extensions);
            return $file;
        }
        
        // Проверка MIME типа
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mime_type, $allowed_types)) {
                $file['error'] = 'Недопустимый MIME тип файла: ' . $mime_type;
                return $file;
            }
        }
        
        // Проверка на исполняемый код в изображениях (только для обычных пользователей)
        $is_admin = current_user_can('administrator') || current_user_can('install_plugins') || current_user_can('install_themes');
        
        if (!$is_admin && in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            if (self::has_embedded_code($file['tmp_name'])) {
                $file['error'] = 'Обнаружен подозрительный код в изображении.';
                return $file;
            }
        }
        
        // Проверка имени файла на подозрительные паттерны
        if (self::is_suspicious_filename($file['name'])) {
            $file['error'] = 'Недопустимое имя файла.';
            return $file;
        }
        
        return $file;
    }
    
    /**
     * Проверка файла на наличие встроенного кода
     *
     * @param string $file_path Путь к файлу
     * @return bool
     */
    private static function has_embedded_code($file_path) {
        $content = file_get_contents($file_path, false, null, 0, 8192);
        
        // Поиск PHP кода
        $php_patterns = [
            '/<\?php/',
            '/<\?=/',
            '/<\?[^x]/',
            '/\beval\s*\(/',
            '/\bexec\s*\(/',
            '/\bsystem\s*\(/',
            '/\bpassthru\s*\(/',
            '/\bshell_exec\s*\(/',
            '/\b__FILE__\b/',
            '/\b__DIR__\b/',
            '/\$_GET/',
            '/\$_POST/',
            '/\$_REQUEST/',
            '/\$_SERVER/',
            '/\$_COOKIE/'
        ];
        
        foreach ($php_patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }
        
        // Поиск JavaScript кода
        $js_patterns = [
            '/<script/',
            '/javascript:/',
            '/onclick\s*=/',
            '/onload\s*=/',
            '/onerror\s*=/'
        ];
        
        foreach ($js_patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Проверка подозрительного имени файла
     *
     * @param string $filename Имя файла
     * @return bool
     */
    private static function is_suspicious_filename($filename) {
        // Подозрительные паттерны в именах файлов
        $suspicious_patterns = [
            '/\.(php|phtml|php3|php4|php5|phps|pl|py|rb|sh|bat|exe|com|scr|vbs|jar)$/i',
            '/^\./i', // Скрытые файлы
            '/\.\./i', // Попытка directory traversal
            '/[<>:"|?*]/', // Недопустимые символы
            '/^(con|prn|aux|nul|com[1-9]|lpt[1-9])$/i', // Зарезервированные имена Windows
            '/\0/', // Null байт
            '/\.(htaccess|htpasswd)$/i' // Конфигурационные файлы Apache
        ];
        
        foreach ($suspicious_patterns as $pattern) {
            if (preg_match($pattern, $filename)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Сканирование загруженного файла
     *
     * @param array $file Данные загруженного файла
     * @return array
     */
    public static function scan_uploaded_file($file) {
        if (isset($file['file']) && file_exists($file['file'])) {
            // Логирование загрузки файла
            self::log_file_upload($file);
            
            // Пропускаем проверку на вредоносность для администраторов и ZIP файлов
            // Администраторы могут загружать плагины и темы, которые содержат PHP код
            $file_ext = strtolower(pathinfo($file['file'], PATHINFO_EXTENSION));
            $is_admin = current_user_can('administrator') || current_user_can('install_plugins') || current_user_can('install_themes');
            
            // Сканируем только если это НЕ администратор или НЕ архив
            if (!$is_admin && !in_array($file_ext, ['zip'])) {
                // Дополнительная проверка безопасности только для обычных пользователей
                if (self::is_malicious_file($file['file'])) {
                    // Удаляем опасный файл
                    unlink($file['file']);
                    
                    $file['error'] = 'Файл был удален из-за обнаружения потенциальной угрозы безопасности.';
                    
                    // Логируем инцидент
                    self::log_security_incident($file['file'], 'Malicious file detected and removed');
                }
            }
        }
        
        return $file;
    }
    
    /**
     * Проверка файла на наличие вредоносного содержимого
     *
     * @param string $file_path Путь к файлу
     * @return bool
     */
    private static function is_malicious_file($file_path) {
        // Проверяем размер файла (слишком большой файл может быть подозрительным)
        if (filesize($file_path) > self::MAX_FILE_SIZE) {
            return true;
        }
        
        // Чтение первых 1024 байт для анализа
        $handle = fopen($file_path, 'rb');
        if (!$handle) {
            return false;
        }
        
        $content = fread($handle, 1024);
        fclose($handle);
        
        // Поиск подозрительных сигнатур
        $malicious_patterns = [
            // PHP backdoors
            '/c99shell/i',
            '/r57shell/i',
            '/webshell/i',
            '/backdoor/i',
            '/\$_REQUEST\s*\[\s*[\'"]c[\'"]\s*\]/i',
            '/eval\s*\(\s*base64_decode/i',
            '/gzinflate\s*\(\s*base64_decode/i',
            '/str_rot13\s*\(\s*[\'"][a-zA-Z0-9+\/=]+[\'"]/i',
            
            // Suspicious binary signatures
            '/\x00\x00\x00\x00\x00\x00\x00\x00.*\xFF\xD8\xFF/', // Suspicious JPEG
            '/PK\x03\x04.*\.php/i', // PHP in ZIP
        ];
        
        foreach ($malicious_patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Защита директории uploads от исполнения PHP
     */
    public static function protect_uploads_directory() {
        $upload_dir = wp_upload_dir();
        $htaccess_file = $upload_dir['basedir'] . '/.htaccess';
        
        if (!file_exists($htaccess_file)) {
            $htaccess_content = "# Защита от исполнения скриптов\n";
            $htaccess_content .= "Options -ExecCGI\n";
            $htaccess_content .= "AddHandler cgi-script .php .pl .py .jsp .asp .sh .cgi\n";
            $htaccess_content .= "<Files *.php>\n";
            $htaccess_content .= "    Deny from all\n";
            $htaccess_content .= "</Files>\n";
            $htaccess_content .= "<Files *.phtml>\n";
            $htaccess_content .= "    Deny from all\n";
            $htaccess_content .= "</Files>\n";
            $htaccess_content .= "<Files *.php3>\n";
            $htaccess_content .= "    Deny from all\n";
            $htaccess_content .= "</Files>\n";
            $htaccess_content .= "<Files *.php4>\n";
            $htaccess_content .= "    Deny from all\n";
            $htaccess_content .= "</Files>\n";
            $htaccess_content .= "<Files *.php5>\n";
            $htaccess_content .= "    Deny from all\n";
            $htaccess_content .= "</Files>\n";
            
            file_put_contents($htaccess_file, $htaccess_content);
        }
        
        // Создаем index.php в uploads для дополнительной защиты
        $index_file = $upload_dir['basedir'] . '/index.php';
        if (!file_exists($index_file)) {
            file_put_contents($index_file, "<?php\n// Access denied\nheader('HTTP/1.0 403 Forbidden');\nexit;\n");
        }
    }
    
    /**
     * Ограничение размера загружаемых файлов
     *
     * @param int $size Текущий лимит размера
     * @return int
     */
    public static function limit_upload_size($size) {
        return min($size, self::MAX_FILE_SIZE);
    }
    
    /**
     * Санитизация имени файла
     *
     * @param string $filename Исходное имя файла
     * @return string
     */
    public static function sanitize_filename($filename) {
        // Проверяем, является ли это файлом лога безопасности
        // Если да, то не добавляем timestamp для сохранения правильного именования
        if (preg_match('/\.(log)$/i', $filename) && 
            preg_match('/^[a-zA-Z0-9_-]+-\d{4}-\d{2}-\d{2}\.log$/i', $filename)) {
            // Это файл лога с правильным форматом (event_type-YYYY-MM-DD.log)
            // Возвращаем как есть, только с базовой санитизацией
            $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
            $filename = preg_replace('/\.+/', '.', $filename);
            $filename = trim($filename, '.');
            
            if (strlen($filename) > 255) {
                $filename = substr($filename, 0, 255);
            }
            
            return $filename;
        }
        
        // Для всех остальных файлов применяем стандартную обработку
        // Удаляем опасные символы
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Удаляем множественные точки
        $filename = preg_replace('/\.+/', '.', $filename);
        
        // Удаляем точки в начале и конце
        $filename = trim($filename, '.');
        
        // Ограничиваем длину
        if (strlen($filename) > 255) {
            $filename = substr($filename, 0, 255);
        }
        
        // Добавляем временную метку для уникальности (только для НЕ-логов)
        $info = pathinfo($filename);
        $name = $info['filename'];
        $ext = isset($info['extension']) ? '.' . $info['extension'] : '';
        
        return $name . '_' . time() . $ext;
    }
    
    /**
     * Логирование загрузки файлов
     *
     * @param array $file Данные файла
     */
    private static function log_file_upload($file) {
        $user_id = get_current_user_id();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $log_entry = sprintf(
            '[%s] File uploaded - User ID: %d, File: %s, Size: %d, IP: %s',
            date('Y-m-d H:i:s'),
            $user_id,
            basename($file['file']),
            filesize($file['file']),
            $ip
        );
        
        error_log($log_entry);
    }
    
    /**
     * Логирование инцидентов безопасности
     *
     * @param string $file_path Путь к файлу
     * @param string $reason Причина блокировки
     */
    private static function log_security_incident($file_path, $reason) {
        $user_id = get_current_user_id();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $log_entry = sprintf(
            '[%s] SECURITY INCIDENT - File: %s, Reason: %s, User ID: %d, IP: %s',
            date('Y-m-d H:i:s'),
            basename($file_path),
            $reason,
            $user_id,
            $ip
        );
        
        error_log($log_entry);
        
        // Отправка уведомления администратору
        wp_mail(
            get_option('admin_email'),
            'Инцидент безопасности - загрузка файла',
            $log_entry,
            ['Content-Type: text/plain; charset=UTF-8']
        );
    }
    
    /**
     * Сканирование существующих файлов на безопасность
     */
    public static function scan_existing_files() {
        $upload_dir = wp_upload_dir();
        $files = glob($upload_dir['basedir'] . '/**/*', GLOB_BRACE);
        
        $suspicious_files = [];
        
        foreach ($files as $file) {
            if (is_file($file) && self::is_malicious_file($file)) {
                $suspicious_files[] = $file;
                
                // Карантин подозрительного файла
                self::quarantine_file($file);
            }
        }
        
        return $suspicious_files;
    }
    
    /**
     * Помещение файла в карантин
     *
     * @param string $file_path Путь к файлу
     */
    private static function quarantine_file($file_path) {
        $upload_dir = wp_upload_dir();
        $quarantine_dir = $upload_dir['basedir'] . '/quarantine';
        
        // Создаем директорию карантина
        if (!file_exists($quarantine_dir)) {
            wp_mkdir_p($quarantine_dir);
            file_put_contents($quarantine_dir . '/.htaccess', "Deny from all\n");
        }
        
        $quarantine_path = $quarantine_dir . '/' . basename($file_path) . '.quarantine';
        
        // Перемещаем файл в карантин
        if (rename($file_path, $quarantine_path)) {
            self::log_security_incident($file_path, 'File moved to quarantine');
        }
    }
}

// Инициализация класса
CryptoSchool_File_Security::init();
