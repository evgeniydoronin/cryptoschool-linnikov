<?php
/**
 * Plugin Name: Crypto School
 * Plugin URI: https://cryptoschool.com
 * Description: Образовательная платформа для обучения криптовалютам
 * Version: 1.4.1
 * Author: Evgeniy Doronin
 * Author URI: https://evgenedoronin.dev
 * Text Domain: cryptoschool
 * Domain Path: /languages
 * Requires at least: 6.2
 * Requires PHP: 8.3
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

// Определение констант плагина
define('CRYPTOSCHOOL_VERSION', '1.4.1');
define('CRYPTOSCHOOL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CRYPTOSCHOOL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CRYPTOSCHOOL_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Основной класс плагина
 */
class CryptoSchool {
    /**
     * Экземпляр класса (Singleton)
     *
     * @var CryptoSchool
     */
    private static $instance = null;

    /**
     * Экземпляр загрузчика
     *
     * @var CryptoSchool_Loader
     */
    private $loader;

    /**
     * Получение экземпляра класса
     *
     * @return CryptoSchool
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Конструктор класса
     */
    private function __construct() {
        // Регистрация хуков активации и деактивации
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        // Инициализация плагина
        add_action('plugins_loaded', [$this, 'init']);
    }

    /**
     * Инициализация плагина
     */
    public function init() {
        // Загрузка текстового домена
        load_plugin_textdomain('cryptoschool', false, dirname(CRYPTOSCHOOL_PLUGIN_BASENAME) . '/languages');

        // Подключение автозагрузчика
        $this->load_dependencies();

        // Регистрация хуков
        $this->register_hooks();
    }

    /**
     * Загрузка зависимостей
     */
    private function load_dependencies() {
        // Подключение автозагрузчика Composer, если он существует
        if (file_exists(CRYPTOSCHOOL_PLUGIN_DIR . 'vendor/autoload.php')) {
            require_once CRYPTOSCHOOL_PLUGIN_DIR . 'vendor/autoload.php';
        }

    // Подключение основных файлов плагина
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/class-cryptoschool-loader.php';
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/class-cryptoschool-autoloader.php';
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/class-cryptoschool-migrator.php';
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/class-cryptoschool-logger.php';
    
    // Подключение базовых классов
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/helpers/class-cryptoschool-helper-string.php';
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/models/class-cryptoschool-model.php';
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/models/class-cryptoschool-model-user-access.php';
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/models/class-cryptoschool-model-user-lesson-progress.php';
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/models/class-cryptoschool-model-user-task-progress.php';
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/models/class-cryptoschool-model-points-history.php';
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/models/class-cryptoschool-model-user-leaderboard.php';
    
    // Подключение моделей реферальной системы
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/models/class-cryptoschool-model-referral-link.php';
    
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/services/class-cryptoschool-service.php';
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/repositories/class-cryptoschool-repository.php';
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/repositories/class-cryptoschool-repository-user-access.php';
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/repositories/class-cryptoschool-repository-lesson-task.php';
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/repositories/class-cryptoschool-repository-user-lesson-progress.php';
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/repositories/class-cryptoschool-repository-user-task-progress.php';
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/repositories/class-cryptoschool-repository-points-history.php';
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/repositories/class-cryptoschool-repository-user-streak.php';
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/repositories/class-cryptoschool-repository-user-leaderboard.php';
    
    // Подключение репозиториев реферальной системы
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/repositories/class-cryptoschool-repository-referral-link.php';
    
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/services/class-cryptoschool-service-accessibility.php';
    
    // Подключение сервиса баллов
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/services/class-cryptoschool-service-points.php';
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/models/class-cryptoschool-model-user-streak.php';
    
    // Подключение сервисов реферальной системы
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/services/class-cryptoschool-service-referral.php';
    
    // Подключение сервисов WPML
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/services/class-cryptoschool-service-wpml.php';
    
    // Подключение Custom Post Types
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/post-types/class-cryptoschool-post-types.php';
    
    
    // Подключение API контроллеров
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/api/class-cryptoschool-api-referral-simple.php';
    
    // Инициализация API контроллера реферальной системы
    new CryptoSchool_API_Referral_Simple();
    
    // Добавление rewrite rules для реферальных ссылок
    add_action('init', function() {
        add_rewrite_rule(
            '^ref/([^/]+)/?$', 
            'index.php?cryptoschool_referral_code=$matches[1]', 
            'top'
        );
    });
    
    // Регистрация query var для реферального кода
    add_filter('query_vars', function($vars) {
        $vars[] = 'cryptoschool_referral_code';
        return $vars;
    });
    
    // Обработчик реферальных ссылок (приоритет 5 - раньше других)
    add_action('template_redirect', function() {
        $referral_code = get_query_var('cryptoschool_referral_code');
        error_log('CryptoSchool Referral: Template redirect - query var = ' . ($referral_code ?: 'пустая'));
        
        if (!empty($referral_code)) {
            // Проверяем, что код существует в БД
            global $wpdb;
            $link = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}cryptoschool_referral_links WHERE referral_code = %s AND is_active = 1",
                $referral_code
            ), ARRAY_A);
            
            if ($link) {
                // Увеличиваем счетчик кликов
                $wpdb->update(
                    $wpdb->prefix . 'cryptoschool_referral_links',
                    ['clicks_count' => $link['clicks_count'] + 1],
                    ['id' => $link['id']]
                );
                
                // Сохраняем реферальный код в cookie на 30 дней с правильными параметрами
                $cookie_domain = parse_url(home_url(), PHP_URL_HOST);
                setcookie('cryptoschool_referral_code', $referral_code, time() + (30 * 24 * 60 * 60), '/', $cookie_domain, false, false);
                
                // Дополнительно сохраняем в сессии WordPress
                if (!session_id()) {
                    session_start();
                }
                $_SESSION['cryptoschool_referral_code'] = $referral_code;
                
                // Логируем установку
                error_log('CryptoSchool Referral: Установлен код в cookie и сессию: ' . $referral_code . ' для домена: ' . $cookie_domain);
                
                // Перенаправляем на главную страницу
                wp_redirect(home_url('/'));
                exit;
            } else {
                // Неверный код - перенаправляем на 404
                global $wp_query;
                $wp_query->set_404();
                status_header(404);
            }
        }
    }, 5);
    
    // Обработчик регистрации с реферальным кодом
    add_action('user_register', 'cryptoschool_handle_referral_registration', 20);
        
        // Подключение административной части
        if (is_admin()) {
            require_once CRYPTOSCHOOL_PLUGIN_DIR . 'admin/class-cryptoschool-admin.php';
            require_once CRYPTOSCHOOL_PLUGIN_DIR . 'admin/class-cryptoschool-admin-wpml.php';
        }
        
    // Подключение публичной части
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'public/class-cryptoschool-public-course.php';
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'public/class-cryptoschool-public-profile.php';
        
        // Создание экземпляра загрузчика
        $this->loader = new CryptoSchool_Loader();
        
        // Запуск миграций базы данных
        $migrator = new CryptoSchool_Migrator();
        if ($migrator->needs_migration()) {
            $migrator->run_migrations();
        }
        
        // Инициализация логгера
        $logger = CryptoSchool_Logger::get_instance();
        // $logger->info('Плагин CryptoSchool инициализирован', ['version' => CRYPTOSCHOOL_VERSION]); // ВРЕМЕННО ОТКЛЮЧЕНО для диагностики
    }

    /**
     * Регистрация хуков
     */
    private function register_hooks() {
        // Регистрация хуков для админки
        if (is_admin()) {
            // Инициализация админ-сервисов
            $admin_services = $this->get_admin_services();
            foreach ($admin_services as $service_class) {
                new $service_class($this->loader);
            }
        }

        // Регистрация хуков для публичной части
        $public_services = $this->get_public_services();
        foreach ($public_services as $service_class) {
            new $service_class($this->loader);
        }

        // Запуск загрузчика
        $this->loader->run();
    }

    /**
     * Получение списка сервисов для админки
     *
     * @return array
     */
    private function get_admin_services() {
        return [
            'CryptoSchool_Admin',
            'CryptoSchool_Admin_WPML'
        ];
    }

    /**
     * Получение списка сервисов для публичной части
     *
     * @return array
     */
    private function get_public_services() {
        return [
            'CryptoSchool_Public_Course',
            'CryptoSchool_Public_Profile',
            'CryptoSchool_Service_Referral',
            'CryptoSchool_Service_Points',
            'CryptoSchool_Post_Types'
        ];
    }

    /**
     * Активация плагина
     */
    public function activate() {
        // Создание таблиц в базе данных
        require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/class-cryptoschool-activator.php';
        CryptoSchool_Activator::activate();
        
        // Установка флага активации для перенаправления на страницу настроек
        set_transient('cryptoschool_activation_redirect', true, 30);
    }

    /**
     * Деактивация плагина
     */
    public function deactivate() {
        // Очистка данных при деактивации
        require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/class-cryptoschool-deactivator.php';
        CryptoSchool_Deactivator::deactivate();
    }
}

// Инициализация плагина
function cryptoschool() {
    return CryptoSchool::get_instance();
}

/**
 * Обработчик регистрации пользователя с реферальным кодом
 *
 * @param int $user_id ID нового пользователя
 */
function cryptoschool_handle_referral_registration($user_id) {
    // Стартуем сессию если нужно
    if (!session_id()) {
        session_start();
    }
    
    // Отладочная информация о доступных cookies и сессии
    error_log('CryptoSchool Referral: Доступные cookies: ' . print_r($_COOKIE, true));
    error_log('CryptoSchool Referral: Доступная сессия: ' . print_r($_SESSION, true));
    
    $referral_code = null;
    
    // Проверяем реферальный код в cookies
    if (isset($_COOKIE['cryptoschool_referral_code'])) {
        $referral_code = sanitize_text_field($_COOKIE['cryptoschool_referral_code']);
        error_log('CryptoSchool Referral: Найден код в cookies: ' . $referral_code);
    }
    // Если не найден в cookies, проверяем в сессии
    elseif (isset($_SESSION['cryptoschool_referral_code'])) {
        $referral_code = sanitize_text_field($_SESSION['cryptoschool_referral_code']);
        error_log('CryptoSchool Referral: Найден код в сессии: ' . $referral_code);
    }
    
    if (!$referral_code) {
        error_log('CryptoSchool Referral: Нет реферального кода в cookies или сессии для пользователя ' . $user_id);
        return;
    }
    error_log('CryptoSchool Referral: Обрабатываем регистрацию с кодом ' . $referral_code . ' для пользователя ' . $user_id);
    
    global $wpdb;
    
    // Ищем реферальную ссылку по коду
    $referral_link = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}cryptoschool_referral_links WHERE referral_code = %s AND is_active = 1",
        $referral_code
    ), ARRAY_A);
    
    if (!$referral_link) {
        error_log('CryptoSchool Referral: Не найдена активная ссылка с кодом ' . $referral_code);
        return;
    }
    
    error_log('CryptoSchool Referral: Найдена ссылка ID ' . $referral_link['id'] . ' от пользователя ' . $referral_link['user_id']);
    
    // Создаем связь между рефереером и новым пользователем
    $result = $wpdb->insert(
        $wpdb->prefix . 'cryptoschool_referral_users',
        array(
            'referrer_id' => $referral_link['user_id'],
            'user_id' => $user_id,
            'referral_link_id' => $referral_link['id'],
            'registration_date' => current_time('mysql'),
            'status' => 'registered'
        ),
        array('%d', '%d', '%d', '%s', '%s')
    );
    
    if ($result === false) {
        error_log('CryptoSchool Referral: Ошибка при создании связи: ' . $wpdb->last_error);
        return;
    }
    
    $referral_user_id = $wpdb->insert_id;
    error_log('CryptoSchool Referral: Создана связь ID ' . $referral_user_id . ' между рефереером ' . $referral_link['user_id'] . ' и новым пользователем ' . $user_id);
    
    // Обновляем счетчики конверсии в реферальной ссылке
    $updated = $wpdb->update(
        $wpdb->prefix . 'cryptoschool_referral_links',
        [
            'conversions_count' => $referral_link['conversions_count'] + 1,
        ],
        ['id' => $referral_link['id']],
        ['%d'],
        ['%d']
    );
    
    // Пересчитываем процент конверсии
    $new_conversions = $referral_link['conversions_count'] + 1;
    $clicks = max(1, $referral_link['clicks_count']); // Избегаем деления на ноль
    $conversion_rate = round(($new_conversions / $clicks) * 100, 2);
    
    $wpdb->update(
        $wpdb->prefix . 'cryptoschool_referral_links',
        ['conversion_rate' => $conversion_rate],
        ['id' => $referral_link['id']],
        ['%f'],
        ['%d']
    );
    
    error_log('CryptoSchool Referral: Обновлены счетчики - конверсии: ' . $new_conversions . ', процент: ' . $conversion_rate . '%');
    
    // Очищаем cookie и сессию после успешной обработки
    $cookie_domain = parse_url(home_url(), PHP_URL_HOST);
    setcookie('cryptoschool_referral_code', '', time() - 3600, '/', $cookie_domain);
    
    if (isset($_SESSION['cryptoschool_referral_code'])) {
        unset($_SESSION['cryptoschool_referral_code']);
    }
    
    // Логируем событие для статистики
    error_log('CryptoSchool Referral: ✅ УСПЕШНО: Пользователь ' . $user_id . ' зарегистрирован по реферальной ссылке пользователя ' . $referral_link['user_id']);
}

// Запуск плагина
cryptoschool();
