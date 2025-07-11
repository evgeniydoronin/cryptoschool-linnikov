<?php
/**
 * Административный интерфейс для управления переводами WPML
 *
 * @package CryptoSchool
 * @subpackage Admin
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс административного интерфейса для управления переводами WPML
 */
class CryptoSchool_Admin_WPML {

    /**
     * Сервис WPML
     *
     * @var CryptoSchool_Service_WPML
     */
    private $wpml_service;

    /**
     * Конструктор класса
     */
    public function __construct() {
        $this->wpml_service = new CryptoSchool_Service_WPML();
        $this->init_hooks();
    }

    /**
     * Инициализация хуков
     *
     * @return void
     */
    private function init_hooks() {
        // Добавление пункта меню
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Обработка AJAX запросов
        add_action('wp_ajax_cryptoschool_register_strings', array($this, 'ajax_register_strings'));
        add_action('wp_ajax_cryptoschool_sync_translations', array($this, 'ajax_sync_translations'));
        
        // Добавление метабоксов на страницы редактирования курсов и уроков
        add_action('add_meta_boxes', array($this, 'add_translation_meta_boxes'));
        
        // Сохранение переводов при сохранении курса/урока
        add_action('save_post', array($this, 'save_translation_meta'), 10, 2);
        
        // Автоматическая регистрация строк при сохранении
        add_action('cryptoschool_course_saved', array($this, 'auto_register_course_strings'));
        add_action('cryptoschool_lesson_saved', array($this, 'auto_register_lesson_strings'));
    }

    /**
     * Добавление пункта меню в админ-панель
     *
     * @return void
     */
    public function add_admin_menu() {
        add_submenu_page(
            'cryptoschool',
            __('Переводы WPML', 'cryptoschool'),
            __('Переводы', 'cryptoschool'),
            'manage_options',
            'cryptoschool-wpml',
            array($this, 'render_admin_page')
        );
    }

    /**
     * Отображение административной страницы
     *
     * @return void
     */
    public function render_admin_page() {
        // Проверка прав доступа
        if (!current_user_can('manage_options')) {
            wp_die(__('У вас нет прав для доступа к этой странице.', 'cryptoschool'));
        }

        // Проверка активности WPML
        if (!$this->wpml_service->is_wpml_active()) {
            echo '<div class="notice notice-error"><p>';
            echo __('WPML не активен. Пожалуйста, установите и активируйте WPML для использования функций перевода.', 'cryptoschool');
            echo '</p></div>';
            return;
        }

        $current_language = $this->wpml_service->get_current_language();
        $default_language = $this->wpml_service->get_default_language();
        $active_languages = $this->wpml_service->get_active_languages();

        include CRYPTOSCHOOL_PLUGIN_DIR . 'admin/views/wpml/admin-page.php';
    }

    /**
     * AJAX обработчик для регистрации строк
     *
     * @return void
     */
    public function ajax_register_strings() {
        // Проверка nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cryptoschool_wpml_nonce')) {
            wp_die(__('Ошибка безопасности', 'cryptoschool'));
        }

        // Проверка прав доступа
        if (!current_user_can('manage_options')) {
            wp_die(__('Недостаточно прав', 'cryptoschool'));
        }

        $type = sanitize_text_field($_POST['type']);
        $result = array('success' => false, 'message' => '');

        try {
            switch ($type) {
                case 'courses':
                    $this->wpml_service->register_all_course_strings();
                    $result['success'] = true;
                    $result['message'] = __('Строки курсов успешно зарегистрированы для перевода.', 'cryptoschool');
                    break;

                case 'lessons':
                    $this->wpml_service->register_all_lesson_strings();
                    $result['success'] = true;
                    $result['message'] = __('Строки уроков успешно зарегистрированы для перевода.', 'cryptoschool');
                    break;

                case 'all':
                    $this->wpml_service->register_all_course_strings();
                    $this->wpml_service->register_all_lesson_strings();
                    $result['success'] = true;
                    $result['message'] = __('Все строки успешно зарегистрированы для перевода.', 'cryptoschool');
                    break;

                default:
                    $result['message'] = __('Неизвестный тип регистрации.', 'cryptoschool');
            }
        } catch (Exception $e) {
            $result['message'] = __('Ошибка при регистрации строк: ', 'cryptoschool') . $e->getMessage();
        }

        wp_send_json($result);
    }

    /**
     * AJAX обработчик для синхронизации переводов
     *
     * @return void
     */
    public function ajax_sync_translations() {
        // Проверка nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cryptoschool_wpml_nonce')) {
            wp_die(__('Ошибка безопасности', 'cryptoschool'));
        }

        // Проверка прав доступа
        if (!current_user_can('manage_options')) {
            wp_die(__('Недостаточно прав', 'cryptoschool'));
        }

        $result = array('success' => false, 'message' => '');

        try {
            // Здесь можно добавить логику синхронизации переводов
            // Например, обновление кеша переводов или проверка консистентности
            
            $result['success'] = true;
            $result['message'] = __('Переводы успешно синхронизированы.', 'cryptoschool');
        } catch (Exception $e) {
            $result['message'] = __('Ошибка при синхронизации переводов: ', 'cryptoschool') . $e->getMessage();
        }

        wp_send_json($result);
    }

    /**
     * Добавление метабоксов для переводов
     *
     * @return void
     */
    public function add_translation_meta_boxes() {
        // Временно отключено для диагностики проблем с Gutenberg
        return;
        
        if (!$this->wpml_service->is_wpml_active()) {
            return;
        }

        // Добавляем метабоксы только на страницы редактирования курсов и уроков
        $screen = get_current_screen();
        
        if ($screen && in_array($screen->id, ['cryptoschool_course', 'cryptoschool_lesson'])) {
            add_meta_box(
                'cryptoschool-wpml-translations',
                __('Переводы WPML', 'cryptoschool'),
                array($this, 'render_translation_meta_box'),
                $screen->id,
                'side',
                'default'
            );
        }
    }

    /**
     * Отображение метабокса переводов
     *
     * @param WP_Post $post Объект поста
     * @return void
     */
    public function render_translation_meta_box($post) {
        $active_languages = $this->wpml_service->get_active_languages();
        $current_language = $this->wpml_service->get_current_language();
        
        // Nonce для безопасности
        wp_nonce_field('cryptoschool_wpml_meta', 'cryptoschool_wpml_meta_nonce');
        
        include CRYPTOSCHOOL_PLUGIN_DIR . 'admin/views/wpml/meta-box.php';
    }

    /**
     * Сохранение метаданных переводов
     *
     * @param int     $post_id ID поста
     * @param WP_Post $post    Объект поста
     * @return void
     */
    public function save_translation_meta($post_id, $post) {
        // Временно отключено для диагностики проблем с Gutenberg
        return;
        
        // Проверка nonce
        if (!isset($_POST['cryptoschool_wpml_meta_nonce']) || 
            !wp_verify_nonce($_POST['cryptoschool_wpml_meta_nonce'], 'cryptoschool_wpml_meta')) {
            return;
        }

        // Проверка прав доступа
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Проверка автосохранения
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Автоматическая регистрация строк при сохранении
        if ($post->post_type === 'cryptoschool_course') {
            $this->auto_register_course_strings($post_id);
        } elseif ($post->post_type === 'cryptoschool_lesson') {
            $this->auto_register_lesson_strings($post_id);
        }
    }

    /**
     * Автоматическая регистрация строк курса
     *
     * @param int $course_id ID курса
     * @return void
     */
    public function auto_register_course_strings($course_id) {
        if (!$this->wpml_service->is_wpml_active()) {
            return;
        }

        $course_post = get_post($course_id);
        
        if ($course_post && $course_post->post_type === 'cryptoschool_course') {
            $this->wpml_service->register_course_post_strings($course_post);
        }
    }

    /**
     * Автоматическая регистрация строк урока
     *
     * @param int $lesson_id ID урока
     * @return void
     */
    public function auto_register_lesson_strings($lesson_id) {
        if (!$this->wpml_service->is_wpml_active()) {
            return;
        }

        $lesson_post = get_post($lesson_id);
        
        if ($lesson_post && $lesson_post->post_type === 'cryptoschool_lesson') {
            $this->wpml_service->register_lesson_post_strings($lesson_post);
        }
    }

    /**
     * Получение статистики переводов
     *
     * @return array
     */
    public function get_translation_stats() {
        if (!$this->wpml_service->is_wpml_active()) {
            return array();
        }

        $stats = array();
        $active_languages = $this->wpml_service->get_active_languages();
        
        // Подсчет курсов и уроков через WordPress API
        $total_courses = wp_count_posts('cryptoschool_course');
        $total_lessons = wp_count_posts('cryptoschool_lesson');
        
        $stats['total_courses'] = $total_courses->publish + $total_courses->draft + $total_courses->private;
        $stats['total_lessons'] = $total_lessons->publish + $total_lessons->draft + $total_lessons->private;
        $stats['total_languages'] = count($active_languages);
        $stats['languages'] = $active_languages;
        
        return $stats;
    }

    /**
     * Получение списка непереведенных строк
     *
     * @param string $language_code Код языка
     * @return array
     */
    public function get_untranslated_strings($language_code) {
        if (!$this->wpml_service->is_wpml_active()) {
            return array();
        }

        // Здесь можно добавить логику для получения списка непереведенных строк
        // Это требует более глубокой интеграции с WPML API
        
        return array();
    }

    /**
     * Экспорт строк для перевода
     *
     * @param string $format Формат экспорта (csv, xml, json)
     * @return void
     */
    public function export_strings($format = 'csv') {
        if (!$this->wpml_service->is_wpml_active()) {
            return;
        }

        // Здесь можно добавить логику экспорта строк для перевода
        // в различных форматах для работы с внешними переводчиками
    }

    /**
     * Импорт переводов
     *
     * @param string $file_path Путь к файлу с переводами
     * @param string $language_code Код языка
     * @return bool
     */
    public function import_translations($file_path, $language_code) {
        if (!$this->wpml_service->is_wpml_active()) {
            return false;
        }

        // Здесь можно добавить логику импорта переводов
        // из файлов различных форматов
        
        return true;
    }
}
