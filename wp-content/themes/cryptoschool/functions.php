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
 * Создание страниц авторизации и личного кабинета
 */
function cryptoschool_create_pages() {
    // Массив страниц для создания
    $pages = array(
        // Страницы авторизации
        'sign-in' => array(
            'title' => 'Вход',
            'template' => 'page-sign-in.php'
        ),
        'sign-up' => array(
            'title' => 'Регистрация',
            'template' => 'page-sign-up.php'
        ),
        'forgot-password' => array(
            'title' => 'Восстановление пароля',
            'template' => 'page-forgot-password.php'
        ),
        'set-password' => array(
            'title' => 'Установка нового пароля',
            'template' => 'page-set-password.php'
        ),
        
        // Страницы личного кабинета
        'dashboard' => array(
            'title' => 'Dashboard',
            'template' => 'page-dashboard.php'
        ),
        'courses' => array(
            'title' => 'Навчання',
            'template' => 'page-courses.php'
        ),
        'course' => array(
            'title' => 'Курс',
            'template' => 'page-course.php'
        ),
        'rate' => array(
            'title' => 'Мій тариф',
            'template' => 'page-rate.php'
        ),
        'referral' => array(
            'title' => 'Реферальна програма',
            'template' => 'page-referral.php'
        ),
        'settings' => array(
            'title' => 'Налаштування',
            'template' => 'page-settings.php'
        ),
        'lesson' => array(
            'title' => 'Урок',
            'template' => 'page-lesson.php'
        )
    );
    
    // Создаем страницы
    foreach ($pages as $slug => $page) {
        // Проверяем, существует ли страница с таким slug
        $page_exists = get_page_by_path($slug);
        
        // Если страница не существует, создаем ее
        if (!$page_exists) {
            // Создаем страницу
            $page_id = wp_insert_post(array(
                'post_title' => $page['title'],
                'post_name' => $slug,
                'post_status' => 'publish',
                'post_type' => 'page',
                'comment_status' => 'closed',
                'ping_status' => 'closed'
            ));
            
            // Устанавливаем шаблон страницы
            if ($page_id) {
                update_post_meta($page_id, '_wp_page_template', $page['template']);
            }
        }
    }
}

// Создаем страницы при активации темы
add_action('after_switch_theme', 'cryptoschool_create_pages');

// Создаем страницы при первой загрузке страницы
cryptoschool_create_pages();

// Роль "Студент" уже существует в системе, поэтому нам не нужно ее создавать

/**
 * Перенаправление после регистрации
 */
function cryptoschool_registration_redirect() {
    // Перенаправляем на главную страницу с параметром registration=success
    wp_redirect(home_url('/?registration=success'));
    exit;
}
add_action('registration_redirect', 'cryptoschool_registration_redirect');

/**
 * Перенаправление после регистрации (альтернативный метод)
 * 
 * @param string $redirect_to URL для перенаправления
 * @param string $user_login Логин пользователя
 * @param WP_User $user Объект пользователя
 * @return string
 */
function cryptoschool_register_redirect($redirect_to, $user_login, $user) {
    // Перенаправляем на главную страницу с параметром registration=success
    return home_url('/?registration=success');
}
add_filter('registration_redirect', 'cryptoschool_register_redirect', 10, 3);

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
    // Если пользователь авторизован, но не администратор, и пытается зайти в админку
    if (is_admin() && !current_user_can('administrator') && !(defined('DOING_AJAX') && DOING_AJAX)) {
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
        array('jquery', 'vanilla-drawers'),
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
