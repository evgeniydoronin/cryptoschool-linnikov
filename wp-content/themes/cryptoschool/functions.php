<?php
/**
 * CryptoSchool Theme Functions
 *
 * @package CryptoSchool
 */

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
    // Если это POST-запрос на регистрацию
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_REQUEST['action']) && $_REQUEST['action'] == 'register') {
        // Добавляем хук, который будет срабатывать после регистрации пользователя
        add_action('login_redirect', 'cryptoschool_after_register_redirect', 10, 3);
    }
}
add_action('login_form_register', 'cryptoschool_register_form_override');

/**
 * Перенаправление после успешной регистрации
 */
function cryptoschool_after_register_redirect($redirect_to, $requested_redirect_to, $user) {
    // Если пользователь не авторизован, возвращаем стандартное перенаправление
    if (!is_a($user, 'WP_User')) {
        return $redirect_to;
    }
    
    // Перенаправляем на главную страницу с параметром registration=success
    return home_url('/?registration=success');
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
                messageElement.innerHTML = 'Регистрация успешно завершена. Пожалуйста, проверьте вашу электронную почту для подтверждения регистрации.';
                
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
 */
function cryptoschool_redirect_non_admin_users() {
    // Добавляем детальное логирование
    if (is_admin() && !(defined('DOING_AJAX') && DOING_AJAX)) {
        $current_user = wp_get_current_user();
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        
        // Логируем все детали
        error_log('=== CryptoSchool Redirect Debug ===');
        error_log('Request URI: ' . $request_uri);
        error_log('User ID: ' . $current_user->ID);
        error_log('User login: ' . $current_user->user_login);
        error_log('User roles: ' . implode(', ', $current_user->roles));
        error_log('Is admin page: ' . (is_admin() ? 'yes' : 'no'));
        error_log('Current page: ' . (isset($_GET['page']) ? $_GET['page'] : 'not set'));
        error_log('Pagenow: ' . ($GLOBALS['pagenow'] ?? 'not set'));
        
        // Проверяем различные capabilities
        error_log('Capabilities check:');
        error_log('- administrator: ' . (current_user_can('administrator') ? 'yes' : 'no'));
        error_log('- manage_options: ' . (current_user_can('manage_options') ? 'yes' : 'no'));
        error_log('- activate_plugins: ' . (current_user_can('activate_plugins') ? 'yes' : 'no'));
        error_log('- install_plugins: ' . (current_user_can('install_plugins') ? 'yes' : 'no'));
        
        // Проверяем, на какой именно странице мы находимся
        if (strpos($request_uri, 'plugins.php') !== false) {
            error_log('!!! On plugins.php page !!!');
        }
        
        error_log('=== End Debug ===');
        
        // Оригинальная проверка
        if (!current_user_can('administrator')) {
            error_log('REDIRECTING: User does not have administrator capability');
            wp_redirect(home_url());
            exit;
        }
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
