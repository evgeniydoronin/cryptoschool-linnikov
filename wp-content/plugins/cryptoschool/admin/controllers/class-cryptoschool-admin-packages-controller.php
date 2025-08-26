<?php
/**
 * Контроллер для управления пакетами в административной части
 *
 * @package CryptoSchool
 * @subpackage Admin\Controllers
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Контроллер для управления пакетами
 */
class CryptoSchool_Admin_Packages_Controller extends CryptoSchool_Admin_Controller {

    /**
     * Сервис для работы с пакетами
     *
     * @var CryptoSchool_Service_Package
     */
    private $package_service;

    /**
     * Конструктор класса
     *
     * @param CryptoSchool_Loader $loader Экземпляр загрузчика плагина
     */
    public function __construct($loader) {
        $this->package_service = new CryptoSchool_Service_Package($loader);
        
        parent::__construct($loader);
    }

    /**
     * Регистрация хуков
     */
    protected function register_hooks() {
        // Регистрация AJAX-обработчиков
        add_action('wp_ajax_cryptoschool_get_packages', array($this, 'ajax_get_packages'));
        add_action('wp_ajax_cryptoschool_get_package', array($this, 'ajax_get_package'));
        add_action('wp_ajax_cryptoschool_create_package', array($this, 'ajax_create_package'));
        add_action('wp_ajax_cryptoschool_update_package', array($this, 'ajax_update_package'));
        add_action('wp_ajax_cryptoschool_delete_package', array($this, 'ajax_delete_package'));
    }

    /**
     * Отображение страницы пакетов
     */
    public function display_packages_page() {
        // Получение списка пакетов
        $packages = $this->package_service->get_all();

        // Получение списка курсов для выбора (Custom Post Types)
        $all_courses = get_posts(array(
            'post_type' => 'cryptoschool_course',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'suppress_filters' => false, // Включаем WPML фильтры
            'meta_query' => array(
                array(
                    'key' => '_cryptoschool_table_id',
                    'compare' => 'EXISTS'
                )
            )
        ));

        // Фильтруем курсы, оставляя только по одному на каждый trid для избежания дублирования языковых версий
        $courses = [];
        $processed_trids = [];
        global $wpdb;

        foreach ($all_courses as $course) {
            // Получаем trid курса
            $trid = $wpdb->get_var($wpdb->prepare(
                "SELECT trid FROM {$wpdb->prefix}icl_translations 
                 WHERE element_id = %d AND element_type = %s",
                $course->ID, 'post_cryptoschool_course'
            ));
            
            // Если trid не найден (WPML не активен) или еще не обработан
            if (!$trid || !in_array($trid, $processed_trids)) {
                $courses[] = $course;
                if ($trid) {
                    $processed_trids[] = $trid;
                }
            }
        }

        // Отображение страницы
        $this->render_view('packages', array(
            'packages' => $packages,
            'courses' => $courses
        ));
    }

    /**
     * AJAX: Получение списка пакетов
     */
    public function ajax_get_packages() {
        // Проверка nonce
        if (!$this->verify_ajax_nonce()) {
            $this->send_ajax_error('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.');
            return;
        }

        // Получение параметров фильтрации
        $is_active = isset($_POST['is_active']) ? sanitize_text_field($_POST['is_active']) : '';
        $package_type = isset($_POST['package_type']) ? sanitize_text_field($_POST['package_type']) : '';
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

        // Получение списка пакетов
        $args = array();
        if (!empty($is_active)) {
            $args['is_active'] = $is_active;
        }
        if (!empty($package_type)) {
            $args['package_type'] = $package_type;
        }
        if (!empty($search)) {
            $args['search'] = $search;
        }
        $packages = $this->package_service->get_all($args);

        // Отправка ответа
        $this->send_ajax_success($packages);
    }

    /**
     * AJAX: Получение данных пакета
     */
    public function ajax_get_package() {
        // Проверка nonce
        if (!$this->verify_ajax_nonce()) {
            $this->send_ajax_error('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.');
            return;
        }

        // Получение ID пакета
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if (!$id) {
            $this->send_ajax_error('Не указан ID пакета.');
            return;
        }

        // Получение данных пакета
        $package = $this->package_service->get_by_id($id);

        if (!$package) {
            $this->send_ajax_error('Пакет не найден.');
            return;
        }

        // Получение курсов пакета
        $courses = $this->package_service->get_courses($id);

        // Подготовка данных для ответа
        $package_data = array(
            'id' => $package->id,
            'title' => $package->title,
            'description' => $package->description,
            'price' => $package->price,
            'package_type' => $package->package_type,
            'duration_months' => $package->duration_months,
            'is_active' => $package->is_active,
            'creoin_points' => $package->creoin_points,
            'features' => json_decode($package->features, true),
            'course_ids' => json_decode($package->course_ids, true),
            'courses' => $courses
        );

        // Отправка ответа
        $this->send_ajax_success($package_data);
    }

    /**
     * AJAX: Создание пакета
     */
    public function ajax_create_package() {
        // Проверка nonce
        if (!$this->verify_ajax_nonce()) {
            $this->send_ajax_error('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.');
            return;
        }

        // Получение данных пакета
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $description = isset($_POST['description']) ? wp_kses_post($_POST['description']) : '';
        $price = isset($_POST['price']) ? (float) $_POST['price'] : 0;
        $package_type = isset($_POST['package_type']) ? sanitize_text_field($_POST['package_type']) : 'course';
        $duration_months = isset($_POST['duration_months']) ? (int) $_POST['duration_months'] : null;
        $is_active = isset($_POST['is_active']) ? (int) $_POST['is_active'] : 1;
        $creoin_points = isset($_POST['creoin_points']) ? (int) $_POST['creoin_points'] : 0;
        $features = isset($_POST['features']) ? $_POST['features'] : array();
        $course_post_ids = isset($_POST['course_ids']) ? $_POST['course_ids'] : array();
        
        // Преобразуем Post ID в table_id для консистентности с остальной системой
        $course_ids = array();
        foreach ($course_post_ids as $post_id) {
            $table_id = get_post_meta($post_id, '_cryptoschool_table_id', true);
            if ($table_id) {
                $course_ids[] = $table_id;
            } else {
                // Fallback к Post ID если table_id не найден
                $course_ids[] = $post_id;
            }
        }

        // Проверка обязательных полей
        if (empty($title)) {
            $this->send_ajax_error('Название пакета обязательно для заполнения.');
            return;
        }

        // Создание пакета
        $package_data = array(
            'title' => $title,
            'description' => $description,
            'price' => $price,
            'package_type' => $package_type,
            'duration_months' => $duration_months,
            'is_active' => $is_active,
            'creoin_points' => $creoin_points,
            'features' => $features,
            'course_ids' => $course_ids,
        );

        $package_id = $this->package_service->create($package_data);

        if (!$package_id) {
            $this->send_ajax_error('Не удалось создать пакет.');
            return;
        }

        // Отправка ответа
        $this->send_ajax_success(array(
            'id' => $package_id,
            'message' => 'Пакет успешно создан.',
        ));
    }

    /**
     * AJAX: Обновление пакета
     */
    public function ajax_update_package() {
        // Проверка nonce
        if (!$this->verify_ajax_nonce()) {
            $this->send_ajax_error('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.');
            return;
        }

        // Получение данных пакета
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $description = isset($_POST['description']) ? wp_kses_post($_POST['description']) : '';
        $price = isset($_POST['price']) ? (float) $_POST['price'] : 0;
        $package_type = isset($_POST['package_type']) ? sanitize_text_field($_POST['package_type']) : 'course';
        $duration_months = isset($_POST['duration_months']) ? (int) $_POST['duration_months'] : null;
        $is_active = isset($_POST['is_active']) ? (int) $_POST['is_active'] : 1;
        $creoin_points = isset($_POST['creoin_points']) ? (int) $_POST['creoin_points'] : 0;
        $features = isset($_POST['features']) ? $_POST['features'] : array();
        $course_post_ids = isset($_POST['course_ids']) ? $_POST['course_ids'] : array();
        
        // Преобразуем Post ID в table_id для консистентности с остальной системой
        $course_ids = array();
        foreach ($course_post_ids as $post_id) {
            $table_id = get_post_meta($post_id, '_cryptoschool_table_id', true);
            if ($table_id) {
                $course_ids[] = $table_id;
            } else {
                // Fallback к Post ID если table_id не найден
                $course_ids[] = $post_id;
            }
        }

        // Проверка обязательных полей
        if (!$id) {
            $this->send_ajax_error('Не указан ID пакета.');
            return;
        }

        if (empty($title)) {
            $this->send_ajax_error('Название пакета обязательно для заполнения.');
            return;
        }

        // Обновление пакета
        $package_data = array(
            'title' => $title,
            'description' => $description,
            'price' => $price,
            'package_type' => $package_type,
            'duration_months' => $duration_months,
            'is_active' => $is_active,
            'creoin_points' => $creoin_points,
            'features' => $features,
            'course_ids' => $course_ids,
        );

        $result = $this->package_service->update($id, $package_data);

        if (!$result) {
            $this->send_ajax_error('Не удалось обновить пакет.');
            return;
        }

        // Отправка ответа
        $this->send_ajax_success(array(
            'message' => 'Пакет успешно обновлен.',
        ));
    }

    /**
     * AJAX: Удаление пакета
     */
    public function ajax_delete_package() {
        // Проверка nonce
        if (!$this->verify_ajax_nonce()) {
            $this->send_ajax_error('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.');
            return;
        }

        // Получение ID пакета
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if (!$id) {
            $this->send_ajax_error('Не указан ID пакета.');
            return;
        }

        // Удаление пакета
        $result = $this->package_service->delete($id);

        if (!$result) {
            $this->send_ajax_error('Не удалось удалить пакет.');
            return;
        }

        // Отправка ответа
        $this->send_ajax_success(array(
            'message' => 'Пакет успешно удален.',
        ));
    }
}
