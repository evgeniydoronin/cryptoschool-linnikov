<?php
/**
 * Сервис для работы с пакетами
 *
 * @package CryptoSchool
 * @subpackage Services
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс сервиса для работы с пакетами
 */
class CryptoSchool_Service_Package extends CryptoSchool_Service {
    /**
     * Репозиторий пакетов
     *
     * @var CryptoSchool_Repository_Package
     */
    protected $repository;

    /**
     * Конструктор
     *
     * @param CryptoSchool_Loader $loader Экземпляр загрузчика
     */
    public function __construct(CryptoSchool_Loader $loader) {
        parent::__construct($loader);
        $this->repository = new CryptoSchool_Repository_Package();
    }

    /**
     * Регистрация хуков и фильтров
     *
     * @return void
     */
    protected function register_hooks() {
        // Регистрация AJAX-обработчиков
        $this->add_action('wp_ajax_cryptoschool_get_packages', 'ajax_get_packages');
        $this->add_action('wp_ajax_cryptoschool_create_package', 'ajax_create_package');
        $this->add_action('wp_ajax_cryptoschool_update_package', 'ajax_update_package');
        $this->add_action('wp_ajax_cryptoschool_delete_package', 'ajax_delete_package');
        
        // Регистрация шорткодов
        $this->add_shortcode('cryptoschool_packages', 'shortcode_packages');
        $this->add_shortcode('cryptoschool_package', 'shortcode_package');
    }

    /**
     * Регистрация пунктов меню администратора
     *
     * @return void
     */
    public function register_admin_menu() {
        // Добавление подпункта для пакетов
        add_submenu_page(
            'cryptoschool',
            __('Пакеты', 'cryptoschool'),
            __('Пакеты', 'cryptoschool'),
            'manage_options',
            'cryptoschool-packages',
            [$this, 'render_admin_packages']
        );
    }

    /**
     * Отображение страницы управления пакетами
     *
     * @return void
     */
    public function render_admin_packages() {
        // Получение пакетов
        $packages = $this->get_all();

        // Получение курсов для выбора
        $course_service = new CryptoSchool_Service_Course($this->loader);
        $courses = $course_service->get_all(['is_active' => 1]);

        // Подключение шаблона
        include plugin_dir_path(dirname(dirname(__FILE__))) . 'admin/views/packages.php';
    }

    /**
     * Получение всех пакетов
     *
     * @param array $args Аргументы для фильтрации и сортировки
     * @return array
     */
    public function get_all($args = []) {
        return $this->repository->get_packages($args);
    }

    /**
     * Получение количества пакетов
     *
     * @param array $args Аргументы для фильтрации
     * @return int
     */
    public function get_count($args = []) {
        return $this->repository->get_packages_count($args);
    }

    /**
     * Получение пакета по ID
     *
     * @param int $id ID пакета
     * @return mixed
     */
    public function get_by_id($id) {
        return $this->repository->find($id);
    }

    /**
     * Создание пакета
     *
     * @param array $data Данные пакета
     * @return int|false ID созданного пакета или false в случае ошибки
     */
    public function create($data) {
        // Преобразование массива курсов в JSON
        if (isset($data['course_ids']) && is_array($data['course_ids'])) {
            $data['course_ids'] = json_encode($data['course_ids']);
        }

        // Преобразование массива особенностей в JSON
        if (isset($data['features']) && is_array($data['features'])) {
            $data['features'] = json_encode($data['features']);
        }

        // Установка дат создания и обновления
        $data['created_at'] = current_time('mysql');
        $data['updated_at'] = current_time('mysql');

        return $this->repository->create($data);
    }

    /**
     * Обновление пакета
     *
     * @param int   $id   ID пакета
     * @param array $data Данные для обновления
     * @return bool
     */
    public function update($id, $data) {
        // Преобразование массива курсов в JSON
        if (isset($data['course_ids']) && is_array($data['course_ids'])) {
            $data['course_ids'] = json_encode($data['course_ids']);
        }

        // Преобразование массива особенностей в JSON
        if (isset($data['features']) && is_array($data['features'])) {
            $data['features'] = json_encode($data['features']);
        }

        // Установка даты обновления
        $data['updated_at'] = current_time('mysql');

        return $this->repository->update($id, $data);
    }

    /**
     * Удаление пакета
     *
     * @param int $id ID пакета
     * @return bool
     */
    public function delete($id) {
        return $this->repository->delete($id);
    }

    /**
     * Получение курсов, включенных в пакет
     *
     * @param int $package_id ID пакета
     * @return array
     */
    public function get_courses($package_id) {
        $package = $this->get_by_id($package_id);
        if (!$package || empty($package->course_ids)) {
            return [];
        }

        $course_ids = json_decode($package->course_ids, true);
        if (!is_array($course_ids) || empty($course_ids)) {
            return [];
        }

        $course_repository = new CryptoSchool_Repository_Course();
        $courses = [];

        foreach ($course_ids as $course_id) {
            $course = $course_repository->find($course_id);
            if ($course) {
                $courses[] = $course;
            }
        }

        return $courses;
    }

    /**
     * Получение пакетов, включающих курс
     *
     * @param int $course_id ID курса
     * @return array
     */
    public function get_packages_with_course($course_id) {
        return $this->repository->get_packages_with_course($course_id);
    }

    /**
     * Получение пакетов, доступных пользователю
     *
     * @param int $user_id ID пользователя
     * @return array
     */
    public function get_user_packages($user_id) {
        $user_access_repository = new CryptoSchool_Repository_UserAccess();
        $user_accesses = $user_access_repository->get_user_accesses($user_id, ['status' => 'active']);

        $packages = [];
        foreach ($user_accesses as $access) {
            $package = $this->get_by_id($access->package_id);
            if ($package) {
                $packages[] = $package;
            }
        }

        return $packages;
    }

    /**
     * Проверка, доступен ли пакет пользователю
     *
     * @param int $package_id ID пакета
     * @param int $user_id    ID пользователя
     * @return bool
     */
    public function is_available_for_user($package_id, $user_id) {
        $user_access_repository = new CryptoSchool_Repository_UserAccess();
        $access = $user_access_repository->get_user_package_access($user_id, $package_id);
        return $access !== null && $access->status === 'active';
    }

    /**
     * AJAX-обработчик для получения пакетов
     *
     * @return void
     */
    public function ajax_get_packages() {
        // Проверка nonce
        check_ajax_referer('cryptoschool_nonce', 'nonce');

        // Проверка прав доступа
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('У вас нет прав для выполнения этого действия.', 'cryptoschool'));
        }

        // Получение параметров
        $args = [];
        if (isset($_POST['is_active'])) {
            $args['is_active'] = (int) $_POST['is_active'];
        }
        if (isset($_POST['package_type'])) {
            $args['package_type'] = sanitize_text_field($_POST['package_type']);
        }
        if (isset($_POST['search'])) {
            $args['search'] = sanitize_text_field($_POST['search']);
        }
        if (isset($_POST['orderby'])) {
            $args['orderby'] = sanitize_text_field($_POST['orderby']);
        }
        if (isset($_POST['order'])) {
            $args['order'] = sanitize_text_field($_POST['order']);
        }

        // Получение пакетов
        $packages = $this->get_all($args);

        // Подготовка данных для ответа
        $data = [];
        foreach ($packages as $package) {
            $data[] = [
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
                'created_at' => $package->get_created_at(),
                'updated_at' => $package->get_updated_at(),
            ];
        }

        wp_send_json_success($data);
    }

    /**
     * AJAX-обработчик для создания пакета
     *
     * @return void
     */
    public function ajax_create_package() {
        // Проверка nonce
        check_ajax_referer('cryptoschool_nonce', 'nonce');

        // Проверка прав доступа
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('У вас нет прав для выполнения этого действия.', 'cryptoschool'));
        }

        // Получение данных из запроса
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $description = isset($_POST['description']) ? wp_kses_post($_POST['description']) : '';
        $price = isset($_POST['price']) ? (float) $_POST['price'] : 0;
        $package_type = isset($_POST['package_type']) ? sanitize_text_field($_POST['package_type']) : 'course';
        $duration_months = isset($_POST['duration_months']) ? (int) $_POST['duration_months'] : null;
        $is_active = isset($_POST['is_active']) ? (int) $_POST['is_active'] : 1;
        $creoin_points = isset($_POST['creoin_points']) ? (int) $_POST['creoin_points'] : 0;
        $features = isset($_POST['features']) ? $_POST['features'] : [];
        $course_ids = isset($_POST['course_ids']) ? $_POST['course_ids'] : [];

        // Проверка обязательных полей
        if (empty($title)) {
            wp_send_json_error(__('Название пакета обязательно для заполнения.', 'cryptoschool'));
        }

        // Создание пакета
        $package_data = [
            'title' => $title,
            'description' => $description,
            'price' => $price,
            'package_type' => $package_type,
            'duration_months' => $duration_months,
            'is_active' => $is_active,
            'creoin_points' => $creoin_points,
            'features' => $features,
            'course_ids' => $course_ids,
        ];

        $package_id = $this->create($package_data);

        if (!$package_id) {
            wp_send_json_error(__('Не удалось создать пакет.', 'cryptoschool'));
        }

        // Получение созданного пакета
        $package = $this->get_by_id($package_id);

        // Подготовка данных для ответа
        $data = [
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
            'created_at' => $package->get_created_at(),
            'updated_at' => $package->get_updated_at(),
        ];

        wp_send_json_success($data);
    }

    /**
     * AJAX-обработчик для обновления пакета
     *
     * @return void
     */
    public function ajax_update_package() {
        // Проверка nonce
        check_ajax_referer('cryptoschool_nonce', 'nonce');

        // Проверка прав доступа
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('У вас нет прав для выполнения этого действия.', 'cryptoschool'));
        }

        // Получение ID пакета
        $package_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($package_id <= 0) {
            wp_send_json_error(__('Некорректный ID пакета.', 'cryptoschool'));
        }

        // Получение данных из запроса
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $description = isset($_POST['description']) ? wp_kses_post($_POST['description']) : '';
        $price = isset($_POST['price']) ? (float) $_POST['price'] : 0;
        $package_type = isset($_POST['package_type']) ? sanitize_text_field($_POST['package_type']) : 'course';
        $duration_months = isset($_POST['duration_months']) ? (int) $_POST['duration_months'] : null;
        $is_active = isset($_POST['is_active']) ? (int) $_POST['is_active'] : 1;
        $creoin_points = isset($_POST['creoin_points']) ? (int) $_POST['creoin_points'] : 0;
        $features = isset($_POST['features']) ? $_POST['features'] : [];
        $course_ids = isset($_POST['course_ids']) ? $_POST['course_ids'] : [];

        // Проверка обязательных полей
        if (empty($title)) {
            wp_send_json_error(__('Название пакета обязательно для заполнения.', 'cryptoschool'));
        }

        // Обновление пакета
        $package_data = [
            'title' => $title,
            'description' => $description,
            'price' => $price,
            'package_type' => $package_type,
            'duration_months' => $duration_months,
            'is_active' => $is_active,
            'creoin_points' => $creoin_points,
            'features' => $features,
            'course_ids' => $course_ids,
        ];

        $result = $this->update($package_id, $package_data);

        if (!$result) {
            wp_send_json_error(__('Не удалось обновить пакет.', 'cryptoschool'));
        }

        // Получение обновленного пакета
        $package = $this->get_by_id($package_id);

        // Подготовка данных для ответа
        $data = [
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
            'created_at' => $package->get_created_at(),
            'updated_at' => $package->get_updated_at(),
        ];

        wp_send_json_success($data);
    }

    /**
     * AJAX-обработчик для удаления пакета
     *
     * @return void
     */
    public function ajax_delete_package() {
        // Проверка nonce
        check_ajax_referer('cryptoschool_nonce', 'nonce');

        // Проверка прав доступа
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('У вас нет прав для выполнения этого действия.', 'cryptoschool'));
        }

        // Получение ID пакета
        $package_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($package_id <= 0) {
            wp_send_json_error(__('Некорректный ID пакета.', 'cryptoschool'));
        }

        // Удаление пакета
        $result = $this->delete($package_id);

        if (!$result) {
            wp_send_json_error(__('Не удалось удалить пакет.', 'cryptoschool'));
        }

        wp_send_json_success();
    }

    /**
     * Шорткод для отображения списка пакетов
     *
     * @param array $atts Атрибуты шорткода
     * @return string
     */
    public function shortcode_packages($atts) {
        $atts = shortcode_atts([
            'limit' => 10,
            'orderby' => 'price',
            'order' => 'ASC',
            'is_active' => 1,
            'package_type' => '',
            'template' => 'default',
        ], $atts, 'cryptoschool_packages');

        // Получение пакетов
        $args = [
            'limit' => (int) $atts['limit'],
            'orderby' => sanitize_text_field($atts['orderby']),
            'order' => sanitize_text_field($atts['order']),
            'is_active' => (int) $atts['is_active'],
        ];

        if (!empty($atts['package_type'])) {
            $args['package_type'] = sanitize_text_field($atts['package_type']);
        }

        $packages = $this->get_all($args);

        // Подключение шаблона
        $template = sanitize_text_field($atts['template']);
        $template_path = plugin_dir_path(dirname(dirname(__FILE__))) . 'public/views/shortcodes/packages-' . $template . '.php';

        if (!file_exists($template_path)) {
            $template_path = plugin_dir_path(dirname(dirname(__FILE__))) . 'public/views/shortcodes/packages-default.php';
        }

        ob_start();
        include $template_path;
        return ob_get_clean();
    }

    /**
     * Шорткод для отображения информации о пакете
     *
     * @param array $atts Атрибуты шорткода
     * @return string
     */
    public function shortcode_package($atts) {
        $atts = shortcode_atts([
            'id' => 0,
            'template' => 'default',
        ], $atts, 'cryptoschool_package');

        // Получение пакета
        $package = null;
        if (!empty($atts['id'])) {
            $package = $this->get_by_id((int) $atts['id']);
        }

        if (!$package) {
            return '';
        }

        // Получение курсов пакета
        $courses = $this->get_courses($package->id);

        // Проверка доступа пользователя к пакету
        $user_id = get_current_user_id();
        $has_access = $user_id ? $this->is_available_for_user($package->id, $user_id) : false;

        // Подключение шаблона
        $template = sanitize_text_field($atts['template']);
        $template_path = plugin_dir_path(dirname(dirname(__FILE__))) . 'public/views/shortcodes/package-' . $template . '.php';

        if (!file_exists($template_path)) {
            $template_path = plugin_dir_path(dirname(dirname(__FILE__))) . 'public/views/shortcodes/package-default.php';
        }

        ob_start();
        include $template_path;
        return ob_get_clean();
    }
}
