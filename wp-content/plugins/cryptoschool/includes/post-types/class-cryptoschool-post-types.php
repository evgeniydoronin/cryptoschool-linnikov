<?php
/**
 * Регистрация Custom Post Types для интеграции с WPML
 *
 * @package CryptoSchool
 * @subpackage PostTypes
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс для регистрации Custom Post Types
 */
class CryptoSchool_Post_Types {

    /**
     * Конструктор класса
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Инициализация хуков
     *
     * @return void
     */
    private function init_hooks() {
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_meta_fields'));
        add_action('init', array($this, 'flush_rewrite_rules_maybe'));
        add_action('admin_menu', array($this, 'hide_post_type_menus'), 999);
        add_filter('manage_cryptoschool_course_posts_columns', array($this, 'add_course_columns'));
        add_filter('manage_cryptoschool_lesson_posts_columns', array($this, 'add_lesson_columns'));
        add_action('manage_cryptoschool_course_posts_custom_column', array($this, 'fill_course_columns'), 10, 2);
        add_action('manage_cryptoschool_lesson_posts_custom_column', array($this, 'fill_lesson_columns'), 10, 2);
    }

    /**
     * Регистрация Custom Post Types
     *
     * @return void
     */
    public function register_post_types() {
        $this->register_course_post_type();
        $this->register_lesson_post_type();
    }

    /**
     * Регистрация Custom Post Type для курсов
     *
     * @return void
     */
    private function register_course_post_type() {
        $labels = array(
            'name'                  => __('Курсы', 'cryptoschool'),
            'singular_name'         => __('Курс', 'cryptoschool'),
            'menu_name'             => __('Курсы', 'cryptoschool'),
            'name_admin_bar'        => __('Курс', 'cryptoschool'),
            'add_new'               => __('Добавить новый', 'cryptoschool'),
            'add_new_item'          => __('Добавить новый курс', 'cryptoschool'),
            'new_item'              => __('Новый курс', 'cryptoschool'),
            'edit_item'             => __('Редактировать курс', 'cryptoschool'),
            'view_item'             => __('Просмотреть курс', 'cryptoschool'),
            'all_items'             => __('Все курсы', 'cryptoschool'),
            'search_items'          => __('Искать курсы', 'cryptoschool'),
            'parent_item_colon'     => __('Родительские курсы:', 'cryptoschool'),
            'not_found'             => __('Курсы не найдены.', 'cryptoschool'),
            'not_found_in_trash'    => __('Курсы не найдены в корзине.', 'cryptoschool'),
            'featured_image'        => __('Изображение курса', 'cryptoschool'),
            'set_featured_image'    => __('Установить изображение курса', 'cryptoschool'),
            'remove_featured_image' => __('Удалить изображение курса', 'cryptoschool'),
            'use_featured_image'    => __('Использовать как изображение курса', 'cryptoschool'),
            'archives'              => __('Архивы курсов', 'cryptoschool'),
            'insert_into_item'      => __('Вставить в курс', 'cryptoschool'),
            'uploaded_to_this_item' => __('Загружено к этому курсу', 'cryptoschool'),
            'filter_items_list'     => __('Фильтровать список курсов', 'cryptoschool'),
            'items_list_navigation' => __('Навигация по списку курсов', 'cryptoschool'),
            'items_list'            => __('Список курсов', 'cryptoschool'),
        );

        $args = array(
            'labels'                => $labels,
            'description'           => __('Курсы для интеграции с WPML', 'cryptoschool'),
            'public'                => true,
            'publicly_queryable'    => false,
            'show_ui'               => true,
            'show_in_menu'          => 'cryptoschool',
            'show_in_admin_bar'     => false,
            'show_in_nav_menus'     => false,
            'show_in_rest'          => true,
            'rest_base'             => 'courses',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'capability_type'       => 'post',
            'map_meta_cap'          => true,
            'hierarchical'          => false,
            'menu_position'         => null,
            'supports'              => array('title', 'editor', 'custom-fields', 'thumbnail', 'excerpt', 'revisions'),
            'rewrite'               => false,
            'query_var'             => false,
            'rest_namespace'        => 'wp/v2',
        );

        register_post_type('cryptoschool_course', $args);
    }

    /**
     * Регистрация Custom Post Type для уроков
     *
     * @return void
     */
    private function register_lesson_post_type() {
        $labels = array(
            'name'                  => __('Уроки', 'cryptoschool'),
            'singular_name'         => __('Урок', 'cryptoschool'),
            'menu_name'             => __('Уроки', 'cryptoschool'),
            'name_admin_bar'        => __('Урок', 'cryptoschool'),
            'add_new'               => __('Добавить новый', 'cryptoschool'),
            'add_new_item'          => __('Добавить новый урок', 'cryptoschool'),
            'new_item'              => __('Новый урок', 'cryptoschool'),
            'edit_item'             => __('Редактировать урок', 'cryptoschool'),
            'view_item'             => __('Просмотреть урок', 'cryptoschool'),
            'all_items'             => __('Все уроки', 'cryptoschool'),
            'search_items'          => __('Искать уроки', 'cryptoschool'),
            'parent_item_colon'     => __('Родительские уроки:', 'cryptoschool'),
            'not_found'             => __('Уроки не найдены.', 'cryptoschool'),
            'not_found_in_trash'    => __('Уроки не найдены в корзине.', 'cryptoschool'),
            'featured_image'        => __('Изображение урока', 'cryptoschool'),
            'set_featured_image'    => __('Установить изображение урока', 'cryptoschool'),
            'remove_featured_image' => __('Удалить изображение урока', 'cryptoschool'),
            'use_featured_image'    => __('Использовать как изображение урока', 'cryptoschool'),
            'archives'              => __('Архивы уроков', 'cryptoschool'),
            'insert_into_item'      => __('Вставить в урок', 'cryptoschool'),
            'uploaded_to_this_item' => __('Загружено к этому уроку', 'cryptoschool'),
            'filter_items_list'     => __('Фильтровать список уроков', 'cryptoschool'),
            'items_list_navigation' => __('Навигация по списку уроков', 'cryptoschool'),
            'items_list'            => __('Список уроков', 'cryptoschool'),
        );

        $args = array(
            'labels'                => $labels,
            'description'           => __('Уроки для интеграции с WPML', 'cryptoschool'),
            'public'                => true,
            'publicly_queryable'    => false,
            'show_ui'               => true,
            'show_in_menu'          => 'cryptoschool',
            'show_in_admin_bar'     => false,
            'show_in_nav_menus'     => false,
            'show_in_rest'          => true,
            'rest_base'             => 'lessons',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'capability_type'       => 'post',
            'map_meta_cap'          => true,
            'hierarchical'          => false,
            'menu_position'         => null,
            'supports'              => array('title', 'editor', 'custom-fields', 'thumbnail', 'excerpt', 'revisions'),
            'rewrite'               => false,
            'query_var'             => false,
            'rest_namespace'        => 'wp/v2',
        );

        register_post_type('cryptoschool_lesson', $args);
    }


    /**
     * Скрытие пунктов меню Custom Post Types
     * Пользователи должны работать только с нашими интерфейсами
     *
     * @return void
     */
    public function hide_post_type_menus() {
        // Скрываем пункты меню, но оставляем возможность редактирования
        // Это позволит WPML работать с постами, но скроет их от обычных пользователей
        
        // Можно раскомментировать для полного скрытия:
        // remove_menu_page('edit.php?post_type=cryptoschool_course');
        // remove_menu_page('edit.php?post_type=cryptoschool_lesson');
        // remove_menu_page('edit.php?post_type=cryptoschool_task');
    }

    /**
     * Добавление колонок в список курсов
     *
     * @param array $columns Существующие колонки
     * @return array
     */
    public function add_course_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'title') {
                $new_columns['table_id'] = __('ID в таблице', 'cryptoschool');
                $new_columns['difficulty'] = __('Сложность', 'cryptoschool');
                $new_columns['course_order'] = __('Порядок', 'cryptoschool');
            }
        }
        
        return $new_columns;
    }

    /**
     * Добавление колонок в список уроков
     *
     * @param array $columns Существующие колонки
     * @return array
     */
    public function add_lesson_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'title') {
                $new_columns['table_id'] = __('ID в таблице', 'cryptoschool');
                $new_columns['course_id'] = __('ID курса', 'cryptoschool');
                $new_columns['lesson_order'] = __('Порядок', 'cryptoschool');
            }
        }
        
        return $new_columns;
    }

    /**
     * Заполнение кастомных колонок для курсов
     *
     * @param string $column  Название колонки
     * @param int    $post_id ID поста
     * @return void
     */
    public function fill_course_columns($column, $post_id) {
        switch ($column) {
            case 'table_id':
                $table_id = get_post_meta($post_id, '_cryptoschool_table_id', true);
                echo $table_id ? esc_html($table_id) : '—';
                break;
                
            case 'difficulty':
                $difficulty = get_post_meta($post_id, 'difficulty_level', true);
                echo $difficulty ? esc_html($difficulty) : '—';
                break;
                
            case 'course_order':
                $order = get_post_meta($post_id, 'course_order', true);
                echo $order ? esc_html($order) : '—';
                break;
        }
    }

    /**
     * Заполнение кастомных колонок для уроков
     *
     * @param string $column  Название колонки
     * @param int    $post_id ID поста
     * @return void
     */
    public function fill_lesson_columns($column, $post_id) {
        switch ($column) {
            case 'table_id':
                $table_id = get_post_meta($post_id, '_cryptoschool_table_id', true);
                echo $table_id ? esc_html($table_id) : '—';
                break;
                
            case 'course_id':
                $course_id = get_post_meta($post_id, 'course_id', true);
                echo $course_id ? esc_html($course_id) : '—';
                break;
                
            case 'lesson_order':
                $order = get_post_meta($post_id, 'lesson_order', true);
                echo $order ? esc_html($order) : '—';
                break;
        }
    }

    /**
     * Получение ID поста по ID записи в таблице
     *
     * @param int    $table_id ID записи в таблице
     * @param string $post_type Тип поста
     * @return int|null
     */
    public static function get_post_id_by_table_id($table_id, $post_type) {
        global $wpdb;
        
        $post_id = $wpdb->get_var($wpdb->prepare(
            "SELECT p.ID FROM {$wpdb->posts} p 
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
             WHERE p.post_type = %s 
             AND pm.meta_key = '_cryptoschool_table_id' 
             AND pm.meta_value = %s",
            $post_type,
            $table_id
        ));
        
        return $post_id ? (int) $post_id : null;
    }

    /**
     * Получение ID записи в таблице по ID поста
     *
     * @param int $post_id ID поста
     * @return int|null
     */
    public static function get_table_id_by_post_id($post_id) {
        $table_id = get_post_meta($post_id, '_cryptoschool_table_id', true);
        return $table_id ? (int) $table_id : null;
    }

    /**
     * Регистрация мета-полей для REST API
     *
     * @return void
     */
    public function register_meta_fields() {
        // Мета-поля для курсов
        register_meta('post', 'difficulty_level', array(
            'object_subtype' => 'cryptoschool_course',
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'auth_callback' => array($this, 'meta_auth_callback')
        ));

        register_meta('post', 'course_order', array(
            'object_subtype' => 'cryptoschool_course',
            'type' => 'integer',
            'single' => true,
            'show_in_rest' => true,
            'auth_callback' => array($this, 'meta_auth_callback')
        ));

        register_meta('post', '_cryptoschool_table_id', array(
            'object_subtype' => 'cryptoschool_course',
            'type' => 'integer',
            'single' => true,
            'show_in_rest' => true,
            'auth_callback' => array($this, 'meta_auth_callback')
        ));

        // Мета-поля для уроков
        register_meta('post', 'course_id', array(
            'object_subtype' => 'cryptoschool_lesson',
            'type' => 'integer',
            'single' => true,
            'show_in_rest' => true,
            'auth_callback' => array($this, 'meta_auth_callback')
        ));

        register_meta('post', 'lesson_order', array(
            'object_subtype' => 'cryptoschool_lesson',
            'type' => 'integer',
            'single' => true,
            'show_in_rest' => true,
            'auth_callback' => array($this, 'meta_auth_callback')
        ));

        register_meta('post', 'completion_tasks', array(
            'object_subtype' => 'cryptoschool_lesson',
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'auth_callback' => array($this, 'meta_auth_callback')
        ));

        register_meta('post', '_cryptoschool_table_id', array(
            'object_subtype' => 'cryptoschool_lesson',
            'type' => 'integer',
            'single' => true,
            'show_in_rest' => true,
            'auth_callback' => array($this, 'meta_auth_callback')
        ));
    }

    /**
     * Проверка прав доступа для мета-полей
     *
     * @param bool   $allowed   Разрешено ли
     * @param string $meta_key  Ключ мета-поля
     * @param int    $object_id ID объекта
     * @param int    $user_id   ID пользователя
     * @param string $cap       Capability
     * @param array  $caps      Capabilities
     * @return bool
     */
    public function meta_auth_callback($allowed, $meta_key, $object_id, $user_id, $cap, $caps) {
        return current_user_can('edit_post', $object_id);
    }

    /**
     * Сброс rewrite rules при необходимости
     *
     * @return void
     */
    public function flush_rewrite_rules_maybe() {
        // Проверяем, нужно ли сбросить правила
        $version = get_option('cryptoschool_post_types_version', '0');
        $current_version = '1.2'; // Увеличиваем при изменении post types (исправление Gutenberg)
        
        if (version_compare($version, $current_version, '<')) {
            flush_rewrite_rules();
            update_option('cryptoschool_post_types_version', $current_version);
        }
    }
}
