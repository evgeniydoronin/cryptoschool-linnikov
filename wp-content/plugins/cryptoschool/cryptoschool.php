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
    
    // Подключение моделей реферальной системы
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/models/class-cryptoschool-model-referral-link.php';
    
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/services/class-cryptoschool-service.php';
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/repositories/class-cryptoschool-repository.php';
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/repositories/class-cryptoschool-repository-user-access.php';
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/repositories/class-cryptoschool-repository-lesson-task.php';
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/repositories/class-cryptoschool-repository-user-lesson-progress.php';
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/repositories/class-cryptoschool-repository-user-task-progress.php';
    
    // Подключение репозиториев реферальной системы
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/repositories/class-cryptoschool-repository-referral-link.php';
    
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/services/class-cryptoschool-service-accessibility.php';
    
    // Подключение сервисов реферальной системы
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/services/class-cryptoschool-service-referral.php';
    
    // Подключение сервисов WPML
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/services/class-cryptoschool-service-wpml.php';
    
    // Подключение Custom Post Types
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/post-types/class-cryptoschool-post-types.php';
    
    
    // Подключение API контроллеров
    require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/api/class-cryptoschool-api-referral-simple.php';
        
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
        $logger->info('Плагин CryptoSchool инициализирован', ['version' => CRYPTOSCHOOL_VERSION]);
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

// Запуск плагина
cryptoschool();
