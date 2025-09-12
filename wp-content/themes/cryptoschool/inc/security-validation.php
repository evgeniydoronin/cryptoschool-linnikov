<?php
/**
 * Улучшенная система валидации и безопасности для форм
 *
 * @package CryptoSchool
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс для валидации входных данных и обеспечения безопасности
 */
class CryptoSchool_Security_Validation {
    
    /**
     * Инициализация класса
     */
    public static function init() {
        // Хуки для валидации при регистрации
        add_filter('registration_errors', [self::class, 'validate_registration_data'], 10, 3);
        
        // Хуки для валидации при входе
        add_action('wp_authenticate_user', [self::class, 'validate_login_attempt'], 10, 2);
        
        // Хук для проверки nonce во всех формах
        add_action('login_form_login', [self::class, 'verify_nonce_login'], 1);
        add_action('login_form_register', [self::class, 'verify_nonce_register'], 1);
        add_action('login_form_lostpassword', [self::class, 'verify_nonce_lostpassword'], 1);
        add_action('login_form_resetpass', [self::class, 'verify_nonce_resetpass'], 1);
        
        // Улучшенная проверка силы пароля
        add_action('validate_password_reset', [self::class, 'validate_password_strength'], 10, 2);
    }
    
    /**
     * Проверка nonce для формы входа
     */
    public static function verify_nonce_login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['cryptoschool_login_nonce']) || !wp_verify_nonce($_POST['cryptoschool_login_nonce'], 'cryptoschool_login_action')) {
                wp_die('Ошибка безопасности: неверный токен формы. Пожалуйста, попробуйте еще раз.', 'Security Error', ['response' => 403]);
            }
        }
    }
    
    /**
     * Проверка nonce для формы регистрации
     */
    public static function verify_nonce_register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Логируем начало проверки nonce
            if (class_exists('CryptoSchool_Logger')) {
                $logger = CryptoSchool_Logger::get_instance();
                $logger->info('Проверка nonce для формы регистрации', [
                    'has_nonce' => isset($_POST['cryptoschool_register_nonce']),
                    'nonce_value_length' => isset($_POST['cryptoschool_register_nonce']) ? strlen($_POST['cryptoschool_register_nonce']) : 0,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'undefined'
                ]);
            }

            if (!isset($_POST['cryptoschool_register_nonce']) || !wp_verify_nonce($_POST['cryptoschool_register_nonce'], 'cryptoschool_register_action')) {
                // Логируем ошибку nonce
                if (class_exists('CryptoSchool_Logger')) {
                    $logger->error('Ошибка проверки nonce для формы регистрации', [
                        'has_nonce' => isset($_POST['cryptoschool_register_nonce']),
                        'nonce_value' => isset($_POST['cryptoschool_register_nonce']) ? substr($_POST['cryptoschool_register_nonce'], 0, 20) . '...' : 'not_set',
                        'expected_action' => 'cryptoschool_register_action',
                        'post_data_keys' => array_keys($_POST),
                        'timestamp' => date('Y-m-d H:i:s')
                    ]);
                }

                wp_die('Ошибка безопасности: неверный токен формы. Пожалуйста, попробуйте еще раз.', 'Security Error', ['response' => 403]);
            }

            // Логируем успешную проверку nonce
            if (class_exists('CryptoSchool_Logger')) {
                $logger->info('Nonce для формы регистрации успешно проверен', [
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            }
        }
    }
    
    /**
     * Проверка nonce для формы восстановления пароля
     */
    public static function verify_nonce_lostpassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['cryptoschool_lostpassword_nonce']) || !wp_verify_nonce($_POST['cryptoschool_lostpassword_nonce'], 'cryptoschool_lostpassword_action')) {
                wp_die('Ошибка безопасности: неверный токен формы. Пожалуйста, попробуйте еще раз.', 'Security Error', ['response' => 403]);
            }
        }
    }
    
    /**
     * Проверка nonce для формы сброса пароля
     */
    public static function verify_nonce_resetpass() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['cryptoschool_resetpass_nonce']) || !wp_verify_nonce($_POST['cryptoschool_resetpass_nonce'], 'cryptoschool_resetpass_action')) {
                wp_die('Ошибка безопасности: неверный токен формы. Пожалуйста, попробуйте еще раз.', 'Security Error', ['response' => 403]);
            }
        }
    }
    
    /**
     * Валидация данных при регистрации
     *
     * @param WP_Error $errors Объект ошибок
     * @param string $sanitized_user_login Очищенный логин
     * @param string $user_email Email пользователя
     * @return WP_Error
     */
    public static function validate_registration_data($errors, $sanitized_user_login, $user_email) {
        // Логируем начало валидации данных регистрации
        if (class_exists('CryptoSchool_Logger')) {
            $logger = CryptoSchool_Logger::get_instance();
            $logger->info('Начало валидации данных регистрации', [
                'sanitized_user_login' => $sanitized_user_login,
                'user_email' => $user_email,
                'post_data_keys' => isset($_POST) ? array_keys($_POST) : [],
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }

        try {
            // Валидация имени пользователя
            if (empty($sanitized_user_login)) {
                $errors->add('empty_username', 'Имя пользователя обязательно для заполнения.');
                if (class_exists('CryptoSchool_Logger')) {
                    $logger->warning('Пустое имя пользователя при регистрации', [
                        'sanitized_user_login' => $sanitized_user_login
                    ]);
                }
            } elseif (strlen($sanitized_user_login) < 3) {
                $errors->add('username_too_short', 'Имя пользователя должно содержать не менее 3 символов.');
                if (class_exists('CryptoSchool_Logger')) {
                    $logger->warning('Слишком короткое имя пользователя', [
                        'sanitized_user_login' => $sanitized_user_login,
                        'length' => strlen($sanitized_user_login)
                    ]);
                }
            } elseif (strlen($sanitized_user_login) > 60) {
                $errors->add('username_too_long', 'Имя пользователя не должно превышать 60 символов.');
                if (class_exists('CryptoSchool_Logger')) {
                    $logger->warning('Слишком длинное имя пользователя', [
                        'sanitized_user_login' => $sanitized_user_login,
                        'length' => strlen($sanitized_user_login)
                    ]);
                }
            } elseif (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $sanitized_user_login)) {
                $errors->add('invalid_username', 'Имя пользователя может содержать только буквы, цифры, подчеркивания, дефисы и точки.');
                if (class_exists('CryptoSchool_Logger')) {
                    $logger->warning('Недопустимые символы в имени пользователя', [
                        'sanitized_user_login' => $sanitized_user_login
                    ]);
                }
            }

            // Валидация email
            if (empty($user_email)) {
                $errors->add('empty_email', 'Email обязателен для заполнения.');
                if (class_exists('CryptoSchool_Logger')) {
                    $logger->warning('Пустой email при регистрации', [
                        'user_email' => $user_email
                    ]);
                }
            } elseif (!is_email($user_email)) {
                $errors->add('invalid_email', 'Пожалуйста, введите действительный email адрес.');
                if (class_exists('CryptoSchool_Logger')) {
                    $logger->warning('Недопустимый формат email', [
                        'user_email' => $user_email
                    ]);
                }
            }

            // Валидация пароля (если указан)
            if (isset($_POST['user_pass']) && !empty($_POST['user_pass'])) {
                $password = $_POST['user_pass'];
                $password_confirmation = isset($_POST['user_pass2']) ? $_POST['user_pass2'] : '';

                // Диагностическое логирование пароля отключено - проблема решена

                $password_errors = self::validate_password($password, $password_confirmation, $sanitized_user_login, $user_email);
                if (!empty($password_errors)) {
                    foreach ($password_errors as $error_code => $error_message) {
                        $errors->add($error_code, $error_message);
                    }

                    if (class_exists('CryptoSchool_Logger')) {
                        $logger->warning('Ошибки валидации пароля', [
                            'password_errors' => $password_errors
                        ]);
                    }
                }
            }

            // Валидация номера телефона (если указан)
            if (isset($_POST['user_phone']) && !empty($_POST['user_phone'])) {
                $phone = sanitize_text_field($_POST['user_phone']);
                if (!self::validate_phone($phone)) {
                    $errors->add('invalid_phone', 'Пожалуйста, введите действительный номер телефона.');
                    if (class_exists('CryptoSchool_Logger')) {
                        $logger->warning('Недопустимый формат номера телефона', [
                            'user_phone' => $phone
                        ]);
                    }
                } else {
                    if (class_exists('CryptoSchool_Logger')) {
                        $logger->info('Номер телефона прошел валидацию', [
                            'user_phone' => $phone
                        ]);
                    }
                }
            }

            // Проверка согласия с условиями
            if (!isset($_POST['agree']) || $_POST['agree'] !== 'on') {
                $errors->add('terms_not_accepted', 'Вы должны согласиться с условиями использования и политикой конфиденциальности.');
                if (class_exists('CryptoSchool_Logger')) {
                    $logger->warning('Не согласен с условиями использования', [
                        'agree_value' => isset($_POST['agree']) ? $_POST['agree'] : 'not_set'
                    ]);
                }
            }

            // Логируем итоги валидации
            if (class_exists('CryptoSchool_Logger')) {
                $logger->info('Завершение валидации данных регистрации', [
                    'has_errors' => $errors->has_errors(),
                    'error_codes' => $errors->get_error_codes(),
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            }

            // Логируем итоги валидации (без прерывания процесса)
            if ($errors->has_errors()) {
                // Логируем ошибки валидации, но НЕ прерываем процесс
                if (class_exists('CryptoSchool_Logger')) {
                    $logger->warning('Обнаружены ошибки валидации, возвращаем их WordPress для обработки', [
                        'error_codes' => $errors->get_error_codes(),
                        'error_messages' => $errors->get_error_messages(),
                        'timestamp' => date('Y-m-d H:i:s')
                    ]);
                }
            } else {
                // Логируем успешную валидацию
                if (class_exists('CryptoSchool_Logger')) {
                    $logger->info('Валидация прошла успешно, пользователь будет создан', [
                        'sanitized_user_login' => $sanitized_user_login,
                        'user_email' => $user_email,
                        'timestamp' => date('Y-m-d H:i:s')
                    ]);
                }
            }

            // Возвращаем ошибки WordPress для стандартной обработки
            // WordPress сам решит, создавать пользователя или показывать ошибки
            return $errors;

        } catch (Exception $e) {
            // Логируем критическую ошибку валидации
            if (class_exists('CryptoSchool_Logger')) {
                $logger->error('Критическая ошибка валидации данных регистрации', [
                    'error_message' => $e->getMessage(),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine(),
                    'sanitized_user_login' => $sanitized_user_login,
                    'user_email' => $user_email
                ]);
            }

            // Добавляем ошибку в объект ошибок
            $errors->add('validation_error', 'Произошла ошибка при валидации данных. Пожалуйста, попробуйте еще раз.');
            return $errors;
        }
    }
    
    /**
     * Валидация пароля
     *
     * @param string $password Пароль
     * @param string $password_confirmation Подтверждение пароля
     * @param string $username Имя пользователя
     * @param string $email Email пользователя
     * @return array Массив ошибок
     */
    public static function validate_password($password, $password_confirmation = '', $username = '', $email = '') {
        $errors = array();
        
        // Проверка длины пароля
        if (strlen($password) < 8) {
            $errors['password_too_short'] = 'Пароль должен содержать не менее 8 символов.';
        }
        
        if (strlen($password) > 128) {
            $errors['password_too_long'] = 'Пароль не должен превышать 128 символов.';
        }
        
        // Проверка сложности пароля
        $has_lowercase = preg_match('/[a-z]/', $password);
        $has_uppercase = preg_match('/[A-Z]/', $password);
        $has_digit = preg_match('/\d/', $password);
        $has_special = preg_match('/[^a-zA-Z\d]/', $password);
        
        $complexity_score = $has_lowercase + $has_uppercase + $has_digit + $has_special;
        
        if ($complexity_score < 3) {
            $errors['password_too_weak'] = 'Пароль должен содержать как минимум 3 из 4 типов символов: строчные буквы, заглавные буквы, цифры, специальные символы.';
        }
        
        // Проверка на совпадение с именем пользователя или email
        if (!empty($username) && stripos($password, $username) !== false) {
            $errors['password_contains_username'] = 'Пароль не должен содержать имя пользователя.';
        }
        
        if (!empty($email)) {
            $email_parts = explode('@', $email);
            if (stripos($password, $email_parts[0]) !== false) {
                $errors['password_contains_email'] = 'Пароль не должен содержать часть email адреса.';
            }
        }
        
        // Проверка на слабые пароли
        $weak_passwords = [
            'password', '12345678', 'qwerty123', 'admin123', 'letmein123',
            'password1', 'password123', 'qwertyuiop', '1234567890'
        ];
        
        if (in_array(strtolower($password), $weak_passwords)) {
            $errors['password_too_common'] = 'Пароль слишком простой. Пожалуйста, выберите более сложный пароль.';
        }
        
        // Проверка подтверждения пароля
        if (!empty($password_confirmation) && $password !== $password_confirmation) {
            $errors['password_mismatch'] = 'Пароли не совпадают.';
        }
        
        return $errors;
    }
    
    /**
     * Валидация номера телефона
     *
     * @param string $phone Номер телефона
     * @return bool
     */
    public static function validate_phone($phone) {
        // Удаляем все нецифровые символы кроме +
        $clean_phone = preg_replace('/[^\d+]/', '', $phone);
        
        // Проверяем формат номера (международный или украинский)
        if (preg_match('/^\+?[1-9]\d{1,14}$/', $clean_phone)) {
            return true;
        }
        
        // Проверяем украинский формат
        if (preg_match('/^(\+38|38|8)?0\d{9}$/', $clean_phone)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Валидация попытки входа
     *
     * @param WP_User|WP_Error $user Объект пользователя или ошибки
     * @param string $password Пароль
     * @return WP_User|WP_Error
     */
    public static function validate_login_attempt($user, $password) {
        if (is_wp_error($user)) {
            return $user;
        }
        
        // Проверка на блокировку аккаунта (можно расширить)
        $failed_attempts = get_user_meta($user->ID, 'failed_login_attempts', true);
        $last_attempt = get_user_meta($user->ID, 'last_failed_login', true);
        
        // Если более 5 неудачных попыток за последние 15 минут - блокируем
        if ($failed_attempts >= 5 && $last_attempt && (time() - $last_attempt < 900)) {
            return new WP_Error('account_locked', 'Аккаунт временно заблокирован из-за множественных неудачных попыток входа. Попробуйте через 15 минут.');
        }
        
        return $user;
    }
    
    /**
     * Валидация силы пароля при сбросе
     *
     * @param WP_Error $errors Объект ошибок
     * @param WP_User $user Объект пользователя
     */
    public static function validate_password_strength($errors, $user) {
        if (isset($_POST['pass1']) && !empty($_POST['pass1'])) {
            $password = $_POST['pass1'];
            $password_confirmation = isset($_POST['pass2']) ? $_POST['pass2'] : '';
            
            $password_errors = self::validate_password($password, $password_confirmation, $user->user_login, $user->user_email);
            
            foreach ($password_errors as $error_code => $error_message) {
                $errors->add($error_code, $error_message);
            }
        }
    }
    
    /**
     * Логирование неудачных попыток входа
     *
     * @param string $username Имя пользователя
     */
    public static function log_failed_login($username) {
        $user = get_user_by('login', $username);
        if (!$user) {
            $user = get_user_by('email', $username);
        }
        
        if ($user) {
            $failed_attempts = get_user_meta($user->ID, 'failed_login_attempts', true);
            $failed_attempts = $failed_attempts ? $failed_attempts + 1 : 1;
            
            update_user_meta($user->ID, 'failed_login_attempts', $failed_attempts);
            update_user_meta($user->ID, 'last_failed_login', time());
        }
        
        // Логирование в файловую систему безопасности
        if (class_exists('CryptoSchool_Security_Logger')) {
            CryptoSchool_Security_Logger::log(
                'auth',
                'login_failed',
                "Failed login attempt for username: {$username}",
                CryptoSchool_Security_Logger::LEVEL_WARNING,
                [
                    'attempted_username' => $username,
                    'user_exists' => $user ? 'yes' : 'no',
                    'failed_attempts' => $failed_attempts ?? 1
                ]
            );
        }
        
        // Логирование в файл для администратора
        error_log(sprintf(
            'Failed login attempt for user: %s from IP: %s at %s',
            $username,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            date('Y-m-d H:i:s')
        ));
    }
    
    /**
     * Сброс счетчика неудачных попыток после успешного входа
     *
     * @param string $user_login Имя пользователя
     * @param WP_User $user Объект пользователя
     */
    public static function reset_failed_login_count($user_login, $user) {
        delete_user_meta($user->ID, 'failed_login_attempts');
        delete_user_meta($user->ID, 'last_failed_login');
    }
}

// Регистрация хуков для неудачных и успешных попыток входа
add_action('wp_login_failed', [CryptoSchool_Security_Validation::class, 'log_failed_login']);
add_action('wp_login', [CryptoSchool_Security_Validation::class, 'reset_failed_login_count'], 10, 2);

// Инициализация класса
CryptoSchool_Security_Validation::init();
