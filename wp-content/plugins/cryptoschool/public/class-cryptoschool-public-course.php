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
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'template' => 'default',
        ], $atts, 'cryptoschool_courses');

        // Получение курсов через Custom Post Types
        $courses = get_posts([
            'post_type' => 'cryptoschool_course',
            'post_status' => 'publish',
            'numberposts' => (int) $atts['limit'],
            'orderby' => sanitize_text_field($atts['orderby']),
            'order' => sanitize_text_field($atts['order']),
        ]);

        // Подключение шаблона
        $template = sanitize_text_field($atts['template']);
        $template_path = plugin_dir_path(dirname(__FILE__)) . 'public/views/shortcodes/courses-' . $template . '.php';

        if (!file_exists($template_path)) {
            $template_path = plugin_dir_path(dirname(__FILE__)) . 'public/views/shortcodes/courses-default.php';
        }

        ob_start();
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            // Простой вывод, если шаблон не найден
            echo '<div class="cryptoschool-courses">';
            foreach ($courses as $course) {
                echo '<div class="course-item">';
                echo '<h3>' . esc_html($course->post_title) . '</h3>';
                echo '<div class="course-excerpt">' . wp_kses_post($course->post_excerpt) . '</div>';
                echo '</div>';
            }
            echo '</div>';
        }
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

        // Получение курса через Custom Post Types
        $course = null;
        if (!empty($atts['id'])) {
            $course = get_post((int) $atts['id']);
            if ($course && $course->post_type !== 'cryptoschool_course') {
                $course = null;
            }
        } elseif (!empty($atts['slug'])) {
            $courses = get_posts([
                'post_type' => 'cryptoschool_course',
                'name' => sanitize_text_field($atts['slug']),
                'post_status' => 'publish',
                'numberposts' => 1
            ]);
            $course = !empty($courses) ? $courses[0] : null;
        }

        if (!$course) {
            return '';
        }

        // Получение уроков курса через Custom Post Types
        // Используем ACF поле для связи или meta_query
        $lessons = get_posts([
            'post_type' => 'cryptoschool_lesson',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'meta_query' => [
                [
                    'key' => 'lesson_course', // ACF поле связи с курсом
                    'value' => $course->ID,
                    'compare' => '='
                ]
            ]
        ]);

        // Получение прогресса пользователя (временно заглушка)
        $user_id = get_current_user_id();
        $user_progress = null; // TODO: Реализовать через Custom Post Types или мета-поля
        
        // Проверка доступа пользователя к курсу (временно заглушка)
        $has_access = true; // TODO: Реализовать проверку доступа

        // Подключение шаблона
        $template = sanitize_text_field($atts['template']);
        $template_path = plugin_dir_path(dirname(__FILE__)) . 'public/views/shortcodes/course-' . $template . '.php';

        if (!file_exists($template_path)) {
            $template_path = plugin_dir_path(dirname(__FILE__)) . 'public/views/shortcodes/course-default.php';
        }

        ob_start();
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            // Простой вывод, если шаблон не найден
            echo '<div class="cryptoschool-course">';
            echo '<h2>' . esc_html($course->post_title) . '</h2>';
            echo '<div class="course-content">' . wp_kses_post($course->post_content) . '</div>';
            if (!empty($lessons)) {
                echo '<h3>Уроки:</h3>';
                echo '<ul class="course-lessons">';
                foreach ($lessons as $lesson) {
                    echo '<li>' . esc_html($lesson->post_title) . '</li>';
                }
                echo '</ul>';
            }
            echo '</div>';
        }
        return ob_get_clean();
    }
}
