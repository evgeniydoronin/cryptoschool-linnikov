<?php
/**
 * Публичная часть для работы с курсами
 *
 * @package CryptoSchool
 * @subpackage Public
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс для публичной части курсов
 */
class CryptoSchool_Public_Course {
    /**
     * Сервис для работы с курсами
     *
     * @var CryptoSchool_Service_Course
     */
    protected $course_service;

    /**
     * Экземпляр загрузчика
     *
     * @var CryptoSchool_Loader
     */
    protected $loader;

    /**
     * Конструктор
     *
     * @param CryptoSchool_Loader $loader Экземпляр загрузчика
     */
    public function __construct($loader) {
        $this->loader = $loader;
        $this->course_service = new CryptoSchool_Service_Course($loader);
        $this->init();
    }

    /**
     * Инициализация хуков
     *
     * @return void
     */
    public function init() {
        // Регистрация шорткодов
        add_shortcode('cryptoschool_courses', [$this, 'shortcode_courses']);
        add_shortcode('cryptoschool_course', [$this, 'shortcode_course']);
    }

    /**
     * Шорткод для отображения списка курсов
     *
     * @param array $atts Атрибуты шорткода
     * @return string
     */
    public function shortcode_courses($atts) {
        $atts = shortcode_atts([
            'limit' => 10,
            'orderby' => 'course_order',
            'order' => 'ASC',
            'is_active' => 1,
            'featured' => null,
            'difficulty' => '',
            'template' => 'default',
        ], $atts, 'cryptoschool_courses');

        // Получение курсов
        $args = [
            'limit' => (int) $atts['limit'],
            'orderby' => sanitize_text_field($atts['orderby']),
            'order' => sanitize_text_field($atts['order']),
            'is_active' => (int) $atts['is_active'],
        ];

        if ($atts['featured'] !== null) {
            $args['featured'] = (int) $atts['featured'];
        }

        if (!empty($atts['difficulty'])) {
            $args['difficulty'] = sanitize_text_field($atts['difficulty']);
        }

        $courses = $this->course_service->get_all($args);

        // Подключение шаблона
        $template = sanitize_text_field($atts['template']);
        $template_path = plugin_dir_path(dirname(__FILE__)) . 'public/views/shortcodes/courses-' . $template . '.php';

        if (!file_exists($template_path)) {
            $template_path = plugin_dir_path(dirname(__FILE__)) . 'public/views/shortcodes/courses-default.php';
        }

        ob_start();
        include $template_path;
        return ob_get_clean();
    }

    /**
     * Шорткод для отображения информации о курсе
     *
     * @param array $atts Атрибуты шорткода
     * @return string
     */
    public function shortcode_course($atts) {
        $atts = shortcode_atts([
            'id' => 0,
            'slug' => '',
            'template' => 'default',
        ], $atts, 'cryptoschool_course');

        // Получение курса
        $course = null;
        if (!empty($atts['id'])) {
            $course = $this->course_service->get_by_id((int) $atts['id']);
        } elseif (!empty($atts['slug'])) {
            $course = $this->course_service->get_by_slug(sanitize_text_field($atts['slug']));
        }

        if (!$course) {
            return '';
        }

        // Получение уроков курса
        $lessons = $this->course_service->get_lessons($course->id, ['is_active' => 1, 'orderby' => 'lesson_order', 'order' => 'ASC']);

        // Получение прогресса пользователя
        $user_id = get_current_user_id();
        $user_progress = $user_id ? $this->course_service->get_user_progress($course->id, $user_id) : null;

        // Проверка доступа пользователя к курсу
        $has_access = $user_id ? $this->course_service->is_available_for_user($course->id, $user_id) : false;

        // Подключение шаблона
        $template = sanitize_text_field($atts['template']);
        $template_path = plugin_dir_path(dirname(__FILE__)) . 'public/views/shortcodes/course-' . $template . '.php';

        if (!file_exists($template_path)) {
            $template_path = plugin_dir_path(dirname(__FILE__)) . 'public/views/shortcodes/course-default.php';
        }

        ob_start();
        include $template_path;
        return ob_get_clean();
    }
}
