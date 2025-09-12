<?php
/**
 * CryptoSchool Theme Functions
 *
 * @package CryptoSchool
 */

// ТЕСТОВОЕ ЛОГИРОВАНИЕ отключено для экономии места в логах
// if (class_exists('CryptoSchool_Logger')) {
//     $logger = CryptoSchool_Logger::get_instance();
//     $logger->info('Тестовое сообщение из functions.php', [
//         'timestamp' => date('Y-m-d H:i:s'),
//         'request_uri' => $_SERVER['REQUEST_URI'] ?? 'undefined',
//         'source' => 'theme_functions'
//     ]);

//     // Логируем загрузку functions.php
//     $logger->info('Загрузка functions.php завершена', [
//         'timestamp' => date('Y-m-d H:i:s'),
//         'wp_version' => get_bloginfo('version'),
//         'theme_version' => wp_get_theme()->get('Version'),
//         'php_version' => PHP_VERSION
//     ]);
// }

// Подключение автозагрузчика Composer
if (file_exists(dirname(__DIR__, 3) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__, 3) . '/vendor/autoload.php';
}

// Подключение файла кастомизации авторизации
require_once get_template_directory() . '/inc/auth-customization.php';

// Подключение WPML хелперов
require_once get_template_directory() . '/inc/wpml-helpers.php';

// Подключение функций блога
require_once get_template_directory() . '/inc/blog-features.php';

// Подключение системы комментариев
require_once get_template_directory() . '/inc/comments-system.php';

// Подключение универсальных хлебных крошек
require_once get_template_directory() . '/inc/universal-breadcrumbs.php';

// Подключение поиска по глоссарию
require_once get_template_directory() . '/inc/glossary-search.php';

// Подключение системы валидации и безопасности
require_once get_template_directory() . '/inc/security-validation.php';

// Подключение системы rate limiting
require_once get_template_directory() . '/inc/rate-limiting.php';

// Подключение системы безопасности файлов
require_once get_template_directory() . '/inc/file-security.php';

// Подключение системы заголовков безопасности
require_once get_template_directory() . '/inc/security-headers.php';

// Подключение системы логирования безопасности
require_once get_template_directory() . '/inc/security-logger.php';

// Подключение контроллеров
require_once get_template_directory() . '/inc/controllers/CryptoSchool_Lesson_Controller.php';

/**
 * Увеличение лимитов загрузки для админки (плагины, темы)
 */
function cryptoschool_increase_upload_limits() {
    // Увеличиваем лимиты только для админки
    if (is_admin()) {
        @ini_set('upload_max_filesize', '100M');
        @ini_set('post_max_size', '100M');
        @ini_set('max_execution_time', '300');
        @ini_set('memory_limit', '256M');
    }
}
add_action('admin_init', 'cryptoschool_increase_upload_limits');

/**
 * Увеличение времени жизни nonce для предотвращения истечения токенов
 */
function cryptoschool_extend_nonce_lifetime() {
    return 2 * DAY_IN_SECONDS; // 2 дня вместо стандартных 12-24 часов
}
add_filter('nonce_life', 'cryptoschool_extend_nonce_lifetime');

/**
 * Дополнительные настройки для стабильной работы админки
 */
function cryptoschool_admin_improvements() {
    if (is_admin() && current_user_can('install_plugins')) {
        // Отключаем logged_out проверку для администраторов при установке плагинов
        add_filter('nonce_user_logged_out', '__return_false');
    }
}
add_action('admin_init', 'cryptoschool_admin_improvements');

/**
 * Обновление правил перезаписи URL при активации темы
 */
function cryptoschool_rewrite_flush() {
    // Обновляем правила перезаписи URL
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'cryptoschool_rewrite_flush');

// Обновляем правила перезаписи URL при первой загрузке страницы
flush_rewrite_rules();


/**
 * Отключение автоматических писем сброса пароля при регистрации с паролем
 */
function cryptoschool_disable_new_user_notification_email($wp_new_user_notification_email, $user, $blogname) {
    // Проверяем, была ли регистрация с паролем
    if (isset($_POST['user_pass']) && !empty($_POST['user_pass'])) {
        // Логируем отключение письма
        if (class_exists('CryptoSchool_Logger')) {
            $logger = CryptoSchool_Logger::get_instance();
            $logger->info('Отключение письма сброса пароля для регистрации с паролем', [
                'user_id' => $user->ID,
                'user_login' => $user->user_login,
                'user_email' => $user->user_email,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
        
        // Возвращаем null чтобы отключить отправку письма
        return null;
    }
    
    // Для регистраций без пароля оставляем стандартное поведение
    return $wp_new_user_notification_email;
}
add_filter('wp_new_user_notification_email', 'cryptoschool_disable_new_user_notification_email', 10, 3);

/**
 * Перенаправление после регистрации
 * 
 * @param string $redirect_to URL для перенаправления
 * @param string $user_login Логин пользователя
 * @param WP_User $user Объект пользователя
 * @return string
 */
function cryptoschool_registration_redirect($redirect_to, $user_login, $user) {
    // Перенаправляем на главную страницу с параметром registration=success
    return home_url('/?registration=success');
}
add_filter('registration_redirect', 'cryptoschool_registration_redirect', 10, 3);

/**
 * Перехват формы регистрации
 */
function cryptoschool_register_form_override() {
    // Логируем начало обработки формы регистрации
    if (class_exists('CryptoSchool_Logger')) {
        $logger = CryptoSchool_Logger::get_instance();
        $logger->info('Начало обработки формы регистрации', [
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'undefined',
            'action' => $_REQUEST['action'] ?? 'undefined',
            'timestamp' => date('Y-m-d H:i:s'),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'undefined',
            'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'undefined'
        ]);
    }

    try {
        // Если это POST-запрос на регистрацию
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_REQUEST['action']) && $_REQUEST['action'] == 'register') {
            // Логируем получение POST-запроса на регистрацию
            if (class_exists('CryptoSchool_Logger')) {
                $logger->info('Получен POST-запрос на регистрацию', [
                    'post_data_keys' => array_keys($_POST),
                    'has_user_login' => isset($_POST['user_login']),
                    'has_user_email' => isset($_POST['user_email']),
                    'has_user_pass' => isset($_POST['user_pass'])
                ]);
            }

            // Добавляем хук, который будет срабатывать после регистрации пользователя
            add_action('login_redirect', 'cryptoschool_after_register_redirect', 10, 3);
        }
    } catch (Exception $e) {
        // Логируем ошибку в обработке формы регистрации
        if (class_exists('CryptoSchool_Logger')) {
            $logger->error('Ошибка в обработке формы регистрации', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'undefined',
                'action' => $_REQUEST['action'] ?? 'undefined'
            ]);
        }

        // Не выбрасываем исключение дальше
        error_log('CryptoSchool: Error in register form override: ' . $e->getMessage());
    }
}
add_action('login_form_register', 'cryptoschool_register_form_override');

/**
 * Перенаправление после успешной регистрации
 */
function cryptoschool_after_register_redirect($redirect_to, $requested_redirect_to, $user) {
    // Логируем начало процесса перенаправления
    if (class_exists('CryptoSchool_Logger')) {
        $logger = CryptoSchool_Logger::get_instance();
        $logger->info('Начало перенаправления после регистрации', [
            'redirect_to' => $redirect_to,
            'requested_redirect_to' => $requested_redirect_to,
            'user_id' => is_a($user, 'WP_User') ? $user->ID : 'not_user_object',
            'user_login' => is_a($user, 'WP_User') ? $user->user_login : 'not_user_object',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    try {
        // Если пользователь не авторизован, возвращаем стандартное перенаправление
        if (!is_a($user, 'WP_User')) {
            if (class_exists('CryptoSchool_Logger')) {
                $logger->warning('Пользователь не авторизован при перенаправлении', [
                    'user_object' => $user,
                    'redirect_to' => $redirect_to
                ]);
            }
            return $redirect_to;
        }

        // Логируем успешное перенаправление
        if (class_exists('CryptoSchool_Logger')) {
            $logger->info('Перенаправление после успешной регистрации', [
                'user_id' => $user->ID,
                'user_login' => $user->user_login,
                'user_roles' => $user->roles,
                'final_redirect_url' => home_url('/?registration=success')
            ]);
        }

        // Перенаправляем на главную страницу с параметром registration=success
        return home_url('/?registration=success');

    } catch (Exception $e) {
        // Логируем ошибку перенаправления
        if (class_exists('CryptoSchool_Logger')) {
            $logger->error('Ошибка при перенаправлении после регистрации', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'user_id' => is_a($user, 'WP_User') ? $user->ID : 'not_user_object',
                'redirect_to' => $redirect_to
            ]);
        }

        // В случае ошибки возвращаем стандартное перенаправление
        error_log('CryptoSchool: Error in after register redirect: ' . $e->getMessage());
        return $redirect_to;
    }
}

/**
 * Вывод сообщения о успешной регистрации
 */
function cryptoschool_registration_success_message() {
    if (isset($_GET['registration']) && $_GET['registration'] == 'success') {
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Создаем элемент для сообщения
                var messageElement = document.createElement('div');
                messageElement.className = 'auth-message auth-message_success auth-message_popup';
                messageElement.innerHTML = 'Регистрация успешно завершена! Теперь вы можете войти в свой аккаунт.';
                
                // Добавляем стили для всплывающего сообщения
                var style = document.createElement('style');
                style.textContent = `
                    .auth-message_popup {
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        z-index: 999999;
                        padding: 15px 20px;
                        border-radius: 5px;
                        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
                        font-size: 16px;
                        font-weight: 500;
                        background-color: rgba(52, 199, 89, 0.95);
                        color: white;
                        animation: fadeInOut 5s forwards;
                    }
                    @keyframes fadeInOut {
                        0% { opacity: 0; transform: translateY(-20px); }
                        10% { opacity: 1; transform: translateY(0); }
                        90% { opacity: 1; transform: translateY(0); }
                        100% { opacity: 0; transform: translateY(-20px); }
                    }
                `;
                document.head.appendChild(style);
                
                // Добавляем сообщение на страницу
                document.body.appendChild(messageElement);
                
                // Удаляем сообщение через 5 секунд
                setTimeout(function() {
                    document.body.removeChild(messageElement);
                }, 5000);
            });
        </script>
        <?php
    }
}
add_action('wp_footer', 'cryptoschool_registration_success_message');

/**
 * Настройка темы
 */
function cryptoschool_theme_setup() {
    // Добавляем поддержку миниатюр постов (featured images)
    add_theme_support('post-thumbnails');
}
add_action('after_setup_theme', 'cryptoschool_theme_setup');


/**
 * Скрытие админ-панели WordPress для студентов
 */
function cryptoschool_hide_admin_bar() {
    // Если пользователь не администратор, скрываем админ-панель
    if (!current_user_can('administrator')) {
        show_admin_bar(false);
    }
}
add_action('after_setup_theme', 'cryptoschool_hide_admin_bar');

/**
 * Перенаправление студентов с админки на главную страницу
 * Диагностическая версия с расширенным логированием
 */
function cryptoschool_redirect_non_admin_users() {
    global $pagenow;
    
    // Проверки без избыточного логирования
    if (!is_admin()) {
        return;
    }
    
    if (defined('DOING_AJAX') && DOING_AJAX) {
        return;
    }
    
    if (in_array($pagenow, ['wp-login.php', 'wp-cron.php', 'xmlrpc.php'])) {
        return;
    }
    
    // Проверка прав
    if (!current_user_can('administrator') && !current_user_can('manage_options')) {
        wp_redirect(home_url());
        exit;
    }
}
add_action('init', 'cryptoschool_redirect_non_admin_users');

// Подключение стилей и скриптов из frontend-source
function cryptoschool_enqueue_frontend_assets() {
    // Базовый путь к директории frontend-source/dist/assets
    $frontend_base = get_template_directory_uri() . '/frontend-source/dist/assets';
    $frontend_dist = get_template_directory_uri() . '/frontend-source/dist';
    
    // Подключение основного CSS темы
    wp_enqueue_style(
        'cryptoschool-style',
        get_template_directory_uri() . '/style.css',
        array(),
        filemtime(get_template_directory() . '/style.css')
    );
    
    // Подключение основного CSS из frontend-source
    wp_enqueue_style(
        'cryptoschool-main-style',
        $frontend_base . '/main.css',
        array('cryptoschool-style'),
        filemtime(get_template_directory() . '/frontend-source/dist/assets/main.css')
    );
    
    // Принудительное подключение jQuery на страницах уроков для работы видео плеера
    if (is_page_template('page-lesson.php') || (isset($_GET['id']) && is_numeric($_GET['id']))) {
        wp_enqueue_script('jquery');
    }
    
    // Подключение vanilla-drawers с атрибутом type="module"
    add_filter('script_loader_tag', 'cryptoschool_add_module_type', 10, 3);
    
    // Подключение vanilla-drawers
    wp_enqueue_script(
        'vanilla-drawers',
        'https://cdn.jsdelivr.net/npm/vanilla-drawers@1.1.22/dist/drawers.umd.js',
        array(),
        '1.1.22',
        true
    );
    
    // Подключение основного JS
    wp_enqueue_script(
        'cryptoschool-main-script',
        $frontend_base . '/main.js',
        array(), // Убираем зависимости для ES6 модулей
        filemtime(get_template_directory() . '/frontend-source/dist/assets/main.js'),
        true
    );
    
    // Подключение скрипта и стилей валидации регистрации только на странице регистрации
    if (is_page('sign-up')) {
        wp_enqueue_style(
            'cryptoschool-registration-validation-css',
            get_template_directory_uri() . '/assets/css/registration-validation.css',
            array('cryptoschool-main-style'),
            filemtime(get_template_directory() . '/assets/css/registration-validation.css')
        );
        
        wp_enqueue_script(
            'cryptoschool-registration-validation',
            get_template_directory_uri() . '/assets/js/registration-validation.js',
            array(),
            filemtime(get_template_directory() . '/assets/js/registration-validation.js'),
            true
        );
    }
    
    // Подключение стилей и скриптов для страницы урока
    if (is_page_template('page-lesson.php') || (isset($_GET['id']) && is_numeric($_GET['id']))) {
        wp_enqueue_style(
            'cryptoschool-lesson-page-css',
            get_template_directory_uri() . '/assets/css/lesson-page.css',
            array('cryptoschool-main-style'),
            filemtime(get_template_directory() . '/assets/css/lesson-page.css')
        );
        
        wp_enqueue_script(
            'cryptoschool-lesson-progress',
            get_template_directory_uri() . '/assets/js/lesson-progress.js',
            array('jquery'),
            filemtime(get_template_directory() . '/assets/js/lesson-progress.js'),
            true
        );
        
        // Передаем данные урока в JavaScript
        add_action('wp_footer', 'cryptoschool_lesson_data_script');
    }
}
add_action('wp_enqueue_scripts', 'cryptoschool_enqueue_frontend_assets');

/**
 * Добавляет атрибут type="module" к определенным скриптам
 */
function cryptoschool_add_module_type($tag, $handle, $src) {
    // Список скриптов, которым нужно добавить атрибут type="module"
    $scripts_to_module = array('vanilla-drawers', 'cryptoschool-main-script');
    
    if (in_array($handle, $scripts_to_module)) {
        return '<script src="' . $src . '" type="module"></script>';
    }
    
    return $tag;
}

/**
 * Добавляем скрипт инициализации drawers через wp_footer
 * Это обходное решение, так как wp_add_inline_script не работает с модульными скриптами
 */
function cryptoschool_init_drawers_script() {
    ?>
    <script>
    // Ждем загрузки ES6 модулей и инициализируем drawers
    (function() {
        let attempts = 0;
        const maxAttempts = 50; // Максимум 5 секунд ожидания
        
        function initDrawers() {
            attempts++;
            
            // Проверяем, загружена ли библиотека drawers
            if (window.app && window.app.drawers) {
                // Проверяем, не была ли уже вызвана инициализация
                if (!window.drawersInitialized) {
                    try {
                        window.app.drawers.init();
                        
                        // Настраиваем опции только для существующих drawers
                        const cabinetMenuDrawer = window.app.drawers.get("cabinet-menu");
                        if (cabinetMenuDrawer) {
                            cabinetMenuDrawer.setOptions({ lockPageScroll: false });
                        }
                        
                        const mainMenuDrawer = window.app.drawers.get("main-menu");
                        if (mainMenuDrawer) {
                            mainMenuDrawer.setOptions({ lockPageScroll: false });
                        }
                        
                        window.drawersInitialized = true;
                        console.log('Drawers initialized successfully');
                    } catch (e) {
                        console.error('Error initializing drawers:', e);
                    }
                }
            } else if (attempts < maxAttempts) {
                // Если библиотека еще не загружена, пробуем снова через 100мс
                setTimeout(initDrawers, 100);
            } else {
                console.warn('Drawers library not found after ' + maxAttempts + ' attempts');
            }
        }
        
        // Запускаем проверку после небольшой задержки
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(initDrawers, 100);
            });
        } else {
            // DOM уже загружен
            setTimeout(initDrawers, 100);
        }
    })();
    </script>
    <?php
}
add_action('wp_footer', 'cryptoschool_init_drawers_script', 100);

/**
 * AJAX обработчик для логирования попыток регистрации
 */
function cryptoschool_log_registration_attempt() {
    // Проверяем nonce для безопасности
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cryptoschool_log_registration')) {
        wp_die('Security check failed');
    }

    // Логируем попытку регистрации через AJAX
    if (class_exists('CryptoSchool_Logger')) {
        $logger = CryptoSchool_Logger::get_instance();
        $logger->info('Попытка регистрации через AJAX', [
            'timestamp' => date('Y-m-d H:i:s'),
            'user_login' => sanitize_text_field($_POST['user_login'] ?? ''),
            'user_email' => sanitize_email($_POST['user_email'] ?? ''),
            'has_phone' => $_POST['has_phone'] ?? false,
            'agree_checked' => $_POST['agree_checked'] ?? false,
            'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'undefined',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'undefined'
        ]);
    }

    // Возвращаем успешный ответ
    wp_send_json_success(['status' => 'logged']);
}
add_action('wp_ajax_cryptoschool_log_registration_attempt', 'cryptoschool_log_registration_attempt');
add_action('wp_ajax_nopriv_cryptoschool_log_registration_attempt', 'cryptoschool_log_registration_attempt');

/**
 * ИСПРАВЛЕНИЕ ПРОБЛЕМЫ С ПАРОЛЕМ ПРИ РЕГИСТРАЦИИ
 * 
 * WordPress функция register_new_user() игнорирует пароль из $_POST
 * и генерирует случайный. Этот хук перехватывает данные перед вставкой
 * в БД и заменяет сгенерированный пароль на пароль из формы.
 */
add_filter('wp_pre_insert_user_data', 'cryptoschool_use_custom_password', 10, 4);
function cryptoschool_use_custom_password($data, $update, $user_id, $userdata) {
    // Только для новых пользователей (не обновление) и только если есть пароль в POST
    if (!$update && isset($_POST['user_pass']) && !empty($_POST['user_pass'])) {
        // Дополнительная проверка - убеждаемся что это регистрация
        $is_registration = (
            isset($_POST['action']) && $_POST['action'] === 'register'
        ) || (
            isset($_REQUEST['action']) && $_REQUEST['action'] === 'register'
        ) || (
            strpos($_SERVER['REQUEST_URI'] ?? '', 'wp-login.php') !== false &&
            isset($_GET['action']) && $_GET['action'] === 'register'
        );
        
        if (!$is_registration) {
            return $data; // Не регистрация - не трогаем пароль
        }
        
        // Получаем пароль из POST
        $custom_password = $_POST['user_pass'];
        
        // Хешируем пароль
        $hashed_password = wp_hash_password($custom_password);
        
        // Заменяем сгенерированный пароль на наш
        $data['user_pass'] = $hashed_password;
    }
    
    return $data;
}

/**
 * Передача данных урока в JavaScript
 */
function cryptoschool_lesson_data_script() {
    // Проверяем, что мы на странице урока
    if (!is_page_template('page-lesson.php') && !(isset($_GET['id']) && is_numeric($_GET['id']))) {
        return;
    }
    
    // Получаем данные урока через контроллер
    try {
        $controller = new CryptoSchool_Lesson_Controller();
        $lesson_data = $controller->prepare_lesson_page();
        
        ?>
        <script>
        window.cryptoschoolLessonData = {
            isCompleted: <?php echo $lesson_data['is_lesson_completed'] ? 'true' : 'false'; ?>,
            lessonId: <?php echo intval($lesson_data['lesson_id']); ?>,
            tasksCount: <?php echo count($lesson_data['tasks']); ?>
        };
        </script>
        <?php
    } catch (Exception $e) {
        // В случае ошибки передаем минимальные данные
        ?>
        <script>
        window.cryptoschoolLessonData = {
            isCompleted: false,
            lessonId: 0,
            tasksCount: 0
        };
        </script>
        <?php
    }
}

// ДИАГНОСТИЧЕСКОЕ ЛОГИРОВАНИЕ ОТКЛЮЧЕНО - проблема решена
// Избыточное логирование может вызывать критические ошибки WordPress
