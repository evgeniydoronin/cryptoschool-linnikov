<?php
/**
 * Кастомизация форм авторизации WordPress
 *
 * @package CryptoSchool
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс для кастомизации форм авторизации WordPress
 */
class CryptoSchool_Auth_Customization {
    /**
     * Инициализация класса
     */
    public static function init() {
        // Регистрация хуков
        add_action('login_enqueue_scripts', [self::class, 'enqueue_login_scripts']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_auth_scripts']);
        
        // Кастомизация формы входа
        add_filter('login_form_top', [self::class, 'custom_login_form_top']);
        add_filter('login_form_middle', [self::class, 'custom_login_form_middle']);
        add_filter('login_form_bottom', [self::class, 'custom_login_form_bottom']);
        
        // Кастомизация формы регистрации
        add_action('register_form', [self::class, 'custom_register_form']);
        
        // Кастомизация формы восстановления пароля
        add_action('lostpassword_form', [self::class, 'custom_lostpassword_form']);
        add_action('resetpass_form', [self::class, 'custom_resetpass_form']);
        
        // Назначение роли "студент" новым пользователям
        add_action('user_register', [self::class, 'set_user_role']);
        
        // Перенаправление после входа
        add_filter('login_redirect', [self::class, 'custom_login_redirect'], 10, 3);
        
        // Перенаправление для авторизованных пользователей
        add_action('template_redirect', [self::class, 'redirect_logged_in_users']);
        
        // Перехват стандартных URL-адресов WordPress
        add_action('init', [self::class, 'custom_login_url']);
        add_action('login_form_login', [self::class, 'redirect_to_custom_login']);
        add_action('login_form_register', [self::class, 'redirect_to_custom_register']);
        add_action('login_form_lostpassword', [self::class, 'redirect_to_custom_lostpassword']);
        add_action('login_form_rp', [self::class, 'redirect_to_custom_reset_password']);
        add_action('login_form_resetpass', [self::class, 'redirect_to_custom_reset_password']);
        
        // Изменение URL для входа, регистрации и восстановления пароля
        add_filter('login_url', [self::class, 'custom_login_url_filter'], 10, 3);
        add_filter('register_url', [self::class, 'custom_register_url_filter']);
        add_filter('lostpassword_url', [self::class, 'custom_lostpassword_url_filter']);
    }
    
    /**
     * Перехват стандартных URL-адресов WordPress
     */
    public static function custom_login_url() {
        // Добавляем правила перезаписи URL
        add_rewrite_rule('^sign-in/?$', 'index.php?pagename=sign-in', 'top');
        add_rewrite_rule('^sign-up/?$', 'index.php?pagename=sign-up', 'top');
        add_rewrite_rule('^forgot-password/?$', 'index.php?pagename=forgot-password', 'top');
        add_rewrite_rule('^set-password/?$', 'index.php?pagename=set-password', 'top');
    }
    
    /**
     * Перенаправление на кастомную страницу входа
     */
    public static function redirect_to_custom_login() {
        // Если это не POST-запрос, перенаправляем на кастомную страницу входа
        if ('GET' == $_SERVER['REQUEST_METHOD']) {
            // Если пользователь уже авторизован, перенаправляем на главную страницу
            if (is_user_logged_in()) {
                wp_redirect(home_url());
                exit;
            }
            
            // Перенаправляем на кастомную страницу входа
            wp_redirect(home_url('/sign-in/'));
            exit;
        }
    }
    
    /**
     * Перенаправление на кастомную страницу регистрации
     */
    public static function redirect_to_custom_register() {
        // Если это не POST-запрос, перенаправляем на кастомную страницу регистрации
        if ('GET' == $_SERVER['REQUEST_METHOD']) {
            // Если пользователь уже авторизован, перенаправляем на главную страницу
            if (is_user_logged_in()) {
                wp_redirect(home_url());
                exit;
            }
            
            // Перенаправляем на кастомную страницу регистрации
            wp_redirect(home_url('/sign-up/'));
            exit;
        }
    }
    
    /**
     * Перенаправление на кастомную страницу восстановления пароля
     */
    public static function redirect_to_custom_lostpassword() {
        // Если это не POST-запрос, перенаправляем на кастомную страницу восстановления пароля
        if ('GET' == $_SERVER['REQUEST_METHOD']) {
            // Если пользователь уже авторизован, перенаправляем на главную страницу
            if (is_user_logged_in()) {
                wp_redirect(home_url());
                exit;
            }
            
            // Перенаправляем на кастомную страницу восстановления пароля
            wp_redirect(home_url('/forgot-password/'));
            exit;
        }
    }
    
    /**
     * Перенаправление на кастомную страницу установки нового пароля
     */
    public static function redirect_to_custom_reset_password() {
        // Если это не POST-запрос, перенаправляем на кастомную страницу установки нового пароля
        if ('GET' == $_SERVER['REQUEST_METHOD']) {
            // Если пользователь уже авторизован, перенаправляем на главную страницу
            if (is_user_logged_in()) {
                wp_redirect(home_url());
                exit;
            }
            
            // Получаем параметры из URL
            $key = isset($_GET['key']) ? $_GET['key'] : '';
            $login = isset($_GET['login']) ? $_GET['login'] : '';
            
            // Если ключ или логин отсутствуют, перенаправляем на страницу восстановления пароля
            if (empty($key) || empty($login)) {
                wp_redirect(home_url('/forgot-password/'));
                exit;
            }
            
            // Перенаправляем на кастомную страницу установки нового пароля
            wp_redirect(home_url("/set-password/?key={$key}&login={$login}"));
            exit;
        }
    }
    
    /**
     * Изменение URL для входа
     *
     * @param string $login_url URL для входа
     * @param string $redirect URL для перенаправления после входа
     * @param bool $force_reauth Принудительная повторная авторизация
     * @return string
     */
    public static function custom_login_url_filter($login_url, $redirect, $force_reauth) {
        // Если указан URL для перенаправления, добавляем его к URL для входа
        if (!empty($redirect)) {
            return home_url('/sign-in/?redirect_to=' . urlencode($redirect));
        }
        
        return home_url('/sign-in/');
    }
    
    /**
     * Изменение URL для регистрации
     *
     * @param string $register_url URL для регистрации
     * @return string
     */
    public static function custom_register_url_filter($register_url) {
        return home_url('/sign-up/');
    }
    
    /**
     * Изменение URL для восстановления пароля
     *
     * @param string $lostpassword_url URL для восстановления пароля
     * @return string
     */
    public static function custom_lostpassword_url_filter($lostpassword_url) {
        return home_url('/forgot-password/');
    }
    
    /**
     * Подключение скриптов и стилей для страницы входа
     */
    public static function enqueue_login_scripts() {
        // Подключение стилей темы
        wp_enqueue_style(
            'cryptoschool-login-style',
            get_template_directory_uri() . '/frontend-source/dist/assets/main.css',
            array(),
            filemtime(get_template_directory() . '/frontend-source/dist/assets/main.css')
        );
        
        // Скрипт для защищенных полей (пароль) уже включен в main.js
        // Не подключаем отдельный скрипт, чтобы избежать конфликтов
    }
    
    /**
     * Подключение скриптов и стилей для страниц авторизации
     */
    public static function enqueue_auth_scripts() {
        // Проверяем, находимся ли мы на странице авторизации, регистрации или восстановления пароля
        if (is_page(array('sign-in', 'sign-up', 'forgot-password', 'set-password'))) {
            // Скрипт для защищенных полей (пароль) уже включен в main.js
            // Не подключаем отдельный скрипт, чтобы избежать конфликтов
        }
    }
    
    /**
     * Кастомизация верхней части формы входа
     *
     * @param string $content Содержимое верхней части формы
     * @return string
     */
    public static function custom_login_form_top($content) {
        $output = '<div class="auth__header">';
        $output .= '<h4 class="h4 auth__title">Вхід до кабінету</h4>';
        $output .= '<div class="text-small auth__text">Увійдіть, щоб отримати доступ до свого облікового запису</div>';
        $output .= '</div>';
        
        return $output . $content;
    }
    
    /**
     * Кастомизация средней части формы входа
     *
     * @param string $content Содержимое средней части формы
     * @return string
     */
    public static function custom_login_form_middle($content) {
        return $content;
    }
    
    /**
     * Кастомизация нижней части формы входа
     *
     * @param string $content Содержимое нижней части формы
     * @return string
     */
    public static function custom_login_form_bottom($content) {
        $output = '<div class="auth__footer">';
        $output .= '<button type="submit" class="auth__submit text">Увійти</button>';
        $output .= '<a href="' . esc_url(wp_registration_url()) . '" class="auth__other-way auth_base text-small">';
        $output .= 'Не маєте облікового запису? <span class="auth_highlight">Зареєструватися</span>';
        $output .= '</a>';
        $output .= '</div>';
        
        // $output .= '<div class="auth__separator">';
        // $output .= '<div class="auth__separator-line"></div>';
        // $output .= '<span class="text-small">Або увійдіть за допомогою</span>';
        // $output .= '<div class="auth__separator-line"></div>';
        // $output .= '</div>';
        
        // $output .= '<div class="auth__helpers">';
        // $output .= '<a href="#" class="auth__helper" id="facebook-login">';
        // $output .= '<img src="' . get_template_directory_uri() . '/frontend-source/dist/assets/img/auth/facebook.svg">';
        // $output .= '</a>';
        // $output .= '<a href="#" class="auth__helper" id="google-login">';
        // $output .= '<img src="' . get_template_directory_uri() . '/frontend-source/dist/assets/img/auth/google.svg">';
        // $output .= '</a>';
        // $output .= '<a href="#" class="auth__helper" id="apple-login">';
        // $output .= '<span class="icon-apple"></span>';
        // $output .= '</a>';
        // $output .= '</div>';
        
        return $content . $output;
    }
    
    /**
     * Кастомизация формы регистрации
     */
    public static function custom_register_form() {
        ?>
        <div class="auth__header">
            <h4 class="h4 auth__title">Зареєструватися</h4>
            <div class="text-small auth__text">
                Давайте налаштуємо вас, щоб ви могли отримати доступ до свого особистого кабінету.
            </div>
        </div>
        
        <div class="auth-field">
            <div class="auth-field__control">
                <label for="user_phone" class="auth-field__label">Номер телефону</label>
                <input type="tel" name="user_phone" id="user_phone" class="auth-field__input text-small">
            </div>
        </div>
        
        <div class="auth__checkbox">
            <span class="checkbox">
                <input id="agree" type="checkbox" class="checkbox__input" name="agree" required>
                <label for="agree" class="checkbox__body">
                    <span class="icon-checkbox-arrow checkbox__icon"></span>
                </label>
            </span>
            <label for="agree" class="auth_base text-small">
                Я погоджуюся з усіма <a href="#" class="auth_highlight">Умовами</a> та <a href="#" class="auth_highlight">Політикою конфіденційності</a>
            </label>
        </div>
        <?php
    }
    
    /**
     * Кастомизация формы восстановления пароля
     */
    public static function custom_lostpassword_form() {
        ?>
        <div class="auth__header auth__header_margin-big">
            <a href="<?php echo esc_url(wp_login_url()); ?>" class="auth__nav">
                <span class="icon-nav-arrow-left auth__nav-arrow"></span>
                <div class="auth__nav-label text-small">Повернутися до входу</div>
            </a>
            <h4 class="h4 auth__title">Забули пароль?</h4>
            <div class="text-small auth__text">
                Не хвилюйтеся, це трапляється з усіма нами. Введіть свій email нижче, щоб відновити пароль
            </div>
        </div>
        <?php
    }
    
    /**
     * Кастомизация формы установки нового пароля
     */
    public static function custom_resetpass_form() {
        ?>
        <div class="auth__header auth__header_margin-big">
            <h4 class="h4 auth__title">Встановіть пароль</h4>
            <div class="text-small auth__text">
                Ваш попередній пароль було скинуто. Будь ласка, встановіть новий пароль для свого облікового запису.
            </div>
        </div>
        <?php
    }
    
    /**
     * Назначение роли "студент" новым пользователям
     *
     * @param int $user_id ID пользователя
     */
    public static function set_user_role($user_id) {
        // Логируем начало процесса назначения роли
        if (class_exists('CryptoSchool_Logger')) {
            $logger = CryptoSchool_Logger::get_instance();
            $logger->info('Начало назначения роли пользователю', [
                'user_id' => $user_id,
                'timestamp' => date('Y-m-d H:i:s'),
                'request_uri' => $_SERVER['REQUEST_URI'] ?? 'undefined',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'undefined'
            ]);
        }

        try {
            $user = new WP_User($user_id);

            // Проверяем, существует ли пользователь
            if (!$user->exists()) {
                if (class_exists('CryptoSchool_Logger')) {
                    $logger->error('Пользователь не найден при назначении роли', [
                        'user_id' => $user_id,
                        'user_object' => $user
                    ]);
                }
                return;
            }

            // Логируем перед назначением роли
            if (class_exists('CryptoSchool_Logger')) {
                $logger->info('Попытка назначения роли cryptoschool_student', [
                    'user_id' => $user_id,
                    'current_roles' => $user->roles,
                    'user_login' => $user->user_login
                ]);
            }

            // Назначаем роль "cryptoschool_student"
            $user->set_role('cryptoschool_student');

            // Проверяем, была ли роль назначена успешно
            $user->get_role_caps(); // Обновляем данные ролей
            $has_role = in_array('cryptoschool_student', $user->roles);

            if ($has_role) {
                if (class_exists('CryptoSchool_Logger')) {
                    $logger->info('Роль cryptoschool_student успешно назначена', [
                        'user_id' => $user_id,
                        'user_roles' => $user->roles
                    ]);
                }
            } else {
                if (class_exists('CryptoSchool_Logger')) {
                    $logger->warning('Роль cryptoschool_student не была назначена', [
                        'user_id' => $user_id,
                        'user_roles' => $user->roles,
                        'available_roles' => wp_roles()->roles
                    ]);
                }
            }

            // Сохранение дополнительных данных пользователя
            if (isset($_POST['user_phone'])) {
                $phone = sanitize_text_field($_POST['user_phone']);
                update_user_meta($user_id, 'user_phone', $phone);

                if (class_exists('CryptoSchool_Logger')) {
                    $logger->info('Телефон пользователя сохранен', [
                        'user_id' => $user_id,
                        'phone_length' => strlen($phone)
                    ]);
                }
            }

            // Логируем успешное завершение
            if (class_exists('CryptoSchool_Logger')) {
                $logger->info('Процесс назначения роли завершен успешно', [
                    'user_id' => $user_id,
                    'final_roles' => $user->roles
                ]);
            }

        } catch (Exception $e) {
            // Логируем критическую ошибку
            if (class_exists('CryptoSchool_Logger')) {
                $logger->error('Критическая ошибка при назначении роли пользователю', [
                    'user_id' => $user_id,
                    'error_message' => $e->getMessage(),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine(),
                    'error_trace' => $e->getTraceAsString()
                ]);
            }

            // Не выбрасываем исключение дальше, чтобы не ломать процесс регистрации
            error_log('CryptoSchool: Critical error in set_user_role: ' . $e->getMessage());
        }
    }
    
    /**
     * Кастомное перенаправление после входа
     *
     * @param string $redirect_to URL для перенаправления
     * @param string $request URL запроса
     * @param WP_User $user Объект пользователя
     * @return string
     */
    public static function custom_login_redirect($redirect_to, $request, $user) {
        // Если пользователь не авторизован, возвращаем стандартное перенаправление
        if (!is_a($user, 'WP_User')) {
            return $redirect_to;
        }
        
        // Если пользователь администратор, перенаправляем в админку
        if (in_array('administrator', $user->roles)) {
            return admin_url();
        }
        
        // Для остальных пользователей перенаправляем на главную страницу
        return home_url();
    }
    
    /**
     * Перенаправление авторизованных пользователей со страниц авторизации
     */
    public static function redirect_logged_in_users() {
        // Если пользователь авторизован и находится на странице авторизации, регистрации или восстановления пароля
        if (is_user_logged_in() && (is_page('sign-in') || is_page('sign-up') || is_page('forgot-password') || is_page('set-password'))) {
            wp_redirect(home_url());
            exit;
        }
    }
}

// Инициализация класса
CryptoSchool_Auth_Customization::init();

// Функция создания JavaScript-файла для защищенных полей удалена,
// так как используется функциональность из main.js
