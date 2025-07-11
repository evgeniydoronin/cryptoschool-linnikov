<?php
/**
 * Класс для административной части плагина
 *
 * @package CryptoSchool
 * @subpackage Admin
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс для административной части плагина
 */
class CryptoSchool_Admin {

    /**
     * Экземпляр загрузчика плагина
     *
     * @var CryptoSchool_Loader
     */
    private $loader;

    /**
     * Контроллер для управления курсами
     *
     * @var CryptoSchool_Admin_Courses_Controller
     */
    private $courses_controller;


    /**
     * Контроллер для управления уроками
     *
     * @var CryptoSchool_Admin_Lessons_Controller
     */
    private $lessons_controller;

    /**
     * Контроллер для управления пакетами
     *
     * @var CryptoSchool_Admin_Packages_Controller
     */
    private $packages_controller;

    /**
     * Контроллер для управления доступами пользователей
     *
     * @var CryptoSchool_Admin_UserAccesses_Controller
     */
    private $user_accesses_controller;

    /**
     * Контроллер для управления реферальной системой
     *
     * @var CryptoSchool_Admin_Referrals_Controller
     */
    private $referrals_controller;

    /**
     * Конструктор класса
     *
     * @param CryptoSchool_Loader $loader Экземпляр загрузчика плагина
     */
    public function __construct($loader) {
        $this->loader = $loader;

        // Инициализация контроллеров
        $this->init_controllers();

        // Регистрация хуков
        $this->register_hooks();
    }

    /**
     * Инициализация контроллеров
     */
    private function init_controllers() {
        // Подключение файлов сервисов
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/services/class-cryptoschool-service.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/services/class-cryptoschool-service-course.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/services/class-cryptoschool-service-lesson.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/services/class-cryptoschool-service-package.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/services/class-cryptoschool-service-user-access.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/services/class-cryptoschool-service-influencer.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/services/class-cryptoschool-service-withdrawal.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/services/class-cryptoschool-service-referral-stats.php';
        
        // Подключение файлов репозиториев
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/repositories/class-cryptoschool-repository.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/repositories/class-cryptoschool-repository-course.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/repositories/class-cryptoschool-repository-lesson.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/repositories/class-cryptoschool-repository-package.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/repositories/class-cryptoschool-repository-user-access.php';
        
        // Подключение файлов моделей
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/models/class-cryptoschool-model.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/models/class-cryptoschool-model-course.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/models/class-cryptoschool-model-lesson.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/models/class-cryptoschool-model-package.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/models/class-cryptoschool-model-user-access.php';
        
        // Подключение файлов контроллеров
        require_once plugin_dir_path(__FILE__) . 'controllers/class-cryptoschool-admin-controller.php';
        require_once plugin_dir_path(__FILE__) . 'controllers/class-cryptoschool-admin-courses-controller.php';
        require_once plugin_dir_path(__FILE__) . 'controllers/class-cryptoschool-admin-lessons-controller.php';
        require_once plugin_dir_path(__FILE__) . 'controllers/class-cryptoschool-admin-packages-controller.php';
        require_once plugin_dir_path(__FILE__) . 'controllers/class-cryptoschool-admin-user-accesses-controller.php';
        require_once plugin_dir_path(__FILE__) . 'controllers/class-cryptoschool-admin-referrals-controller.php';
        
        // Подключение файлов помощников
        require_once plugin_dir_path(__FILE__) . 'helpers/modal-helper.php';
        
        // Инициализация контроллеров
        $this->courses_controller = new CryptoSchool_Admin_Courses_Controller($this->loader);
        $this->lessons_controller = new CryptoSchool_Admin_Lessons_Controller($this->loader);
        $this->packages_controller = new CryptoSchool_Admin_Packages_Controller($this->loader);
        $this->user_accesses_controller = new CryptoSchool_Admin_UserAccesses_Controller($this->loader);
        $this->referrals_controller = new CryptoSchool_Admin_Referrals_Controller($this->loader);
    }

    /**
     * Регистрация хуков
     */
    private function register_hooks() {
        // Добавление пунктов меню
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Подключение стилей и скриптов
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Добавление пунктов меню в админ-панель
     */
    public function add_admin_menu() {
        // Главное меню плагина
        add_menu_page(
            __('Крипто Школа', 'cryptoschool'),
            __('Крипто Школа', 'cryptoschool'),
            'manage_options',
            'cryptoschool',
            array($this, 'display_dashboard_page'),
            'dashicons-welcome-learn-more',
            30
        );

        // Подменю: Дашборд
        add_submenu_page(
            'cryptoschool',
            __('Дашборд', 'cryptoschool'),
            __('Дашборд', 'cryptoschool'),
            'manage_options',
            'cryptoschool',
            array($this, 'display_dashboard_page')
        );

        // Подменю: Курсы
        add_submenu_page(
            'cryptoschool',
            __('Курсы', 'cryptoschool'),
            __('Курсы', 'cryptoschool'),
            'manage_options',
            'cryptoschool-courses',
            array($this, 'display_courses_page')
        );


        // Подменю: Уроки
        add_submenu_page(
            'cryptoschool',
            __('Уроки', 'cryptoschool'),
            __('Уроки', 'cryptoschool'),
            'manage_options',
            'cryptoschool-lessons',
            array($this, 'display_lessons_page')
        );
        
        // Скрытые страницы для создания и редактирования уроков
        add_submenu_page(
            null, // Не показывать в меню
            __('Добавить урок', 'cryptoschool'),
            __('Добавить урок', 'cryptoschool'),
            'manage_options',
            'cryptoschool-add-lesson',
            array($this->lessons_controller, 'display_add_lesson_page')
        );
        
        add_submenu_page(
            null, // Не показывать в меню
            __('Редактировать урок', 'cryptoschool'),
            __('Редактировать урок', 'cryptoschool'),
            'manage_options',
            'cryptoschool-edit-lesson',
            array($this->lessons_controller, 'display_edit_lesson_page')
        );

        // Подменю: Пакеты
        add_submenu_page(
            'cryptoschool',
            __('Пакеты', 'cryptoschool'),
            __('Пакеты', 'cryptoschool'),
            'manage_options',
            'cryptoschool-packages',
            array($this, 'display_packages_page')
        );

        // Подменю: Доступы пользователей
        add_submenu_page(
            'cryptoschool',
            __('Доступы пользователей', 'cryptoschool'),
            __('Доступы пользователей', 'cryptoschool'),
            'manage_options',
            'cryptoschool-user-accesses',
            array($this, 'display_user_accesses_page')
        );

        // Подменю: Реферальная система
        add_submenu_page(
            'cryptoschool',
            __('Реферальная система', 'cryptoschool'),
            __('Реферальная система', 'cryptoschool'),
            'manage_options',
            'cryptoschool-referrals',
            array($this, 'display_referrals_page')
        );

        // Подменю: Настройки
        add_submenu_page(
            'cryptoschool',
            __('Настройки', 'cryptoschool'),
            __('Настройки', 'cryptoschool'),
            'manage_options',
            'cryptoschool-settings',
            array($this, 'display_settings_page')
        );
    }

    /**
     * Подключение стилей
     */
    public function enqueue_styles() {
        // Проверка, что мы на странице плагина
        if (!$this->is_plugin_page()) {
            return;
        }

        // Подключение стилей
        wp_enqueue_style(
            'cryptoschool-admin',
            plugin_dir_url(__FILE__) . 'css/cryptoschool-admin.css',
            array(),
            CRYPTOSCHOOL_VERSION,
            'all'
        );
    }

    /**
     * Подключение скриптов
     */
    public function enqueue_scripts() {
        // Проверка, что мы на странице плагина
        if (!$this->is_plugin_page()) {
            return;
        }

        // Подключение медиа-загрузчика WordPress (должно быть перед подключением скриптов)
        wp_enqueue_media();

        // Подключение скриптов
        wp_enqueue_script(
            'cryptoschool-admin',
            plugin_dir_url(__FILE__) . 'js/cryptoschool-admin.js',
            array('jquery', 'jquery-ui-sortable', 'wp-util', 'media-upload'),
            CRYPTOSCHOOL_VERSION,
            true
        );

        // Локализация скриптов
        wp_localize_script(
            'cryptoschool-admin',
            'cryptoschool_admin',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('cryptoschool_admin_nonce'),
                'media_title' => __('Выберите изображение', 'cryptoschool'),
                'media_button' => __('Использовать это изображение', 'cryptoschool'),
                'media_select' => __('Выбрать изображение', 'cryptoschool'),
                'media_change' => __('Изменить изображение', 'cryptoschool'),
                'confirm_default' => __('Вы уверены?', 'cryptoschool'),
                'confirm_delete' => __('Вы уверены, что хотите удалить этот элемент? Это действие нельзя отменить.', 'cryptoschool'),
                'error_message' => __('Произошла ошибка. Пожалуйста, попробуйте еще раз.', 'cryptoschool'),
                'success_message' => __('Успешно сохранено!', 'cryptoschool'),
            )
        );
    }

    /**
     * Проверка, что текущая страница относится к плагину
     *
     * @return bool
     */
    private function is_plugin_page() {
        $screen = get_current_screen();
        
        if (!$screen) {
            return false;
        }
        
        // Проверка по части ID экрана
        if (strpos($screen->id, 'cryptoschool') !== false) {
            return true;
        }
        
        // Проверка по конкретным ID экранов
        $plugin_pages = array(
            'toplevel_page_cryptoschool',
            'крипто-школа_page_cryptoschool-courses',
            'crypto-school_page_cryptoschool-courses',
            'admin_page_cryptoschool-lessons',
            'крипто-школа_page_cryptoschool-packages',
            'crypto-school_page_cryptoschool-packages',
            'крипто-школа_page_cryptoschool-user-accesses',
            'crypto-school_page_cryptoschool-user-accesses',
            'крипто-школа_page_cryptoschool-referrals',
            'crypto-school_page_cryptoschool-referrals',
            'крипто-школа_page_cryptoschool-settings',
            'crypto-school_page_cryptoschool-settings',
        );
        
        return in_array($screen->id, $plugin_pages);
    }

    /**
     * Отображение страницы дашборда
     */
    public function display_dashboard_page() {
        // Подключение шаблона
        require_once plugin_dir_path(__FILE__) . 'views/dashboard.php';
    }

    /**
     * Отображение страницы курсов
     */
    public function display_courses_page() {
        $this->courses_controller->display_courses_page();
    }


    /**
     * Отображение страницы уроков
     * Если ID курса не указан, отображаются все уроки из всех курсов
     */
    public function display_lessons_page() {
        $course_id = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;
        $this->lessons_controller->display_lessons_page($course_id);
    }

    /**
     * Отображение страницы пакетов
     */
    public function display_packages_page() {
        $this->packages_controller->display_packages_page();
    }

    /**
     * Отображение страницы доступов пользователей
     */
    public function display_user_accesses_page() {
        $this->user_accesses_controller->display_user_accesses_page();
    }

    /**
     * Отображение страницы реферальной системы
     */
    public function display_referrals_page() {
        $this->referrals_controller->display_referrals_page();
    }

    /**
     * Отображение страницы настроек
     */
    public function display_settings_page() {
        // Подключение шаблона
        require_once plugin_dir_path(__FILE__) . 'views/settings.php';
    }

}
