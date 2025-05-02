<?php
/**
 * Контроллер для управления курсами в административной части
 *
 * @package CryptoSchool
 * @subpackage Admin\Controllers
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Контроллер для управления курсами
 */
class CryptoSchool_Admin_Courses_Controller extends CryptoSchool_Admin_Controller {

    /**
     * Сервис для работы с курсами
     *
     * @var CryptoSchool_Service_Course
     */
    private $course_service;

    /**
     * Конструктор класса
     *
     * @param CryptoSchool_Loader $loader Экземпляр загрузчика плагина
     */
    public function __construct($loader) {
        $this->course_service = new CryptoSchool_Service_Course($loader);
        
        parent::__construct($loader);
    }

    /**
     * Регистрация хуков
     */
    protected function register_hooks() {
        // Регистрация AJAX-обработчиков
        add_action('wp_ajax_cryptoschool_get_courses', array($this, 'ajax_get_courses'));
        add_action('wp_ajax_cryptoschool_get_course', array($this, 'ajax_get_course'));
        add_action('wp_ajax_cryptoschool_create_course', array($this, 'ajax_create_course'));
        add_action('wp_ajax_cryptoschool_update_course', array($this, 'ajax_update_course'));
        add_action('wp_ajax_cryptoschool_delete_course', array($this, 'ajax_delete_course'));
        add_action('wp_ajax_cryptoschool_update_course_order', array($this, 'ajax_update_course_order'));
    }

    /**
     * Отображение страницы курсов
     */
    public function display_courses_page() {
        // Получение списка курсов
        $courses = $this->course_service->get_all();
        
        // Добавление дополнительной информации к каждому курсу
        foreach ($courses as $course) {
            // Добавление количества уроков
            $course_id = $course->getAttribute('id');
            $lessons_count = $this->course_service->get_lessons_count($course_id);
            
            // Сохранение данных в объекте курса
            $course->lessons_count = $lessons_count;
            
            // Отладочный вывод
            error_log('Course ID: ' . $course_id . ', Lessons: ' . $lessons_count);
        }

        // Отображение страницы
        $this->render_view('courses', array(
            'courses' => $courses
        ));
    }

    /**
     * AJAX: Получение списка курсов
     */
    public function ajax_get_courses() {
        // Проверка nonce
        if (!$this->verify_ajax_nonce()) {
            $this->send_ajax_error('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.');
            return;
        }

        // Получение параметров фильтрации
        $args = [];
        if (isset($_POST['is_active']) && $_POST['is_active'] !== '') {
            $args['is_active'] = (int) $_POST['is_active'];
        }
        if (isset($_POST['search']) && $_POST['search'] !== '') {
            $args['search'] = sanitize_text_field($_POST['search']);
        }
        if (isset($_POST['orderby'])) {
            $args['orderby'] = sanitize_text_field($_POST['orderby']);
        }
        if (isset($_POST['order'])) {
            $args['order'] = sanitize_text_field($_POST['order']);
        }

        // Получение списка курсов
        $courses = $this->course_service->get_all($args);

        // Отправка ответа
        $this->send_ajax_success($courses);
    }

    /**
     * AJAX: Получение данных курса
     */
    public function ajax_get_course() {
        // Проверка nonce
        if (!$this->verify_ajax_nonce()) {
            $this->send_ajax_error('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.');
            return;
        }

        // Получение ID курса
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        // Отладочный вывод
        error_log('AJAX get_course - ID: ' . $id);
        error_log('AJAX get_course - POST data: ' . json_encode($_POST));

        if (!$id) {
            $this->send_ajax_error('Не указан ID курса.');
            return;
        }

        // Получение данных курса
        $course = $this->course_service->get_by_id($id);

        // Отладочный вывод
        error_log('AJAX get_course - Course: ' . ($course ? 'найден' : 'не найден'));

        if (!$course) {
            $this->send_ajax_error('Курс не найден.');
            return;
        }

        // Подготовка данных для ответа
        $data = [
            'id' => $course->getAttribute('id'),
            'title' => $course->getAttribute('title'),
            'description' => $course->getAttribute('description'),
            'thumbnail' => $course->getAttribute('thumbnail'),
            'difficulty_level' => $course->getAttribute('difficulty_level'),
            'slug' => $course->getAttribute('slug'),
            'course_order' => $course->getAttribute('course_order'),
            'is_active' => $course->getAttribute('is_active'),
            'completion_points' => $course->getAttribute('completion_points'),
            'lessons_count' => $this->course_service->get_lessons_count($course->getAttribute('id')),
            'created_at' => method_exists($course, 'get_created_at') ? $course->get_created_at() : null,
            'updated_at' => method_exists($course, 'get_updated_at') ? $course->get_updated_at() : null,
        ];

        // Отладочный вывод
        error_log('AJAX get_course - Data: ' . json_encode($data));

        // Отправка ответа
        $this->send_ajax_success($data);
    }

    /**
     * AJAX: Создание курса
     */
    public function ajax_create_course() {
        // Проверка nonce
        if (!$this->verify_ajax_nonce()) {
            $this->send_ajax_error('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.');
            return;
        }

        // Получение данных курса
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $description = isset($_POST['description']) ? wp_kses_post($_POST['description']) : '';
        $thumbnail = isset($_POST['thumbnail']) ? esc_url_raw($_POST['thumbnail']) : '';
        $difficulty_level = isset($_POST['difficulty_level']) ? sanitize_text_field($_POST['difficulty_level']) : '';
        $course_order = isset($_POST['course_order']) ? (int) $_POST['course_order'] : 0;
        $is_active = isset($_POST['is_active']) ? (int) $_POST['is_active'] : 1;
        $completion_points = isset($_POST['completion_points']) ? (int) $_POST['completion_points'] : 0;

        // Проверка обязательных полей
        if (empty($title)) {
            $this->send_ajax_error('Название курса обязательно для заполнения.');
            return;
        }

        // Создание курса
        $course_data = array(
            'title' => $title,
            'description' => $description,
            'thumbnail' => $thumbnail,
            'difficulty_level' => $difficulty_level,
            'course_order' => $course_order,
            'is_active' => $is_active,
            'completion_points' => $completion_points,
        );

        $course_id = $this->course_service->create($course_data);

        if (!$course_id) {
            $this->send_ajax_error('Не удалось создать курс.');
            return;
        }

        // Отправка ответа
        $this->send_ajax_success(array(
            'id' => $course_id,
            'message' => 'Курс успешно создан.',
        ));
    }

    /**
     * AJAX: Обновление курса
     */
    public function ajax_update_course() {
        // Проверка nonce
        if (!$this->verify_ajax_nonce()) {
            $this->send_ajax_error('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.');
            return;
        }

        // Получение данных курса
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $description = isset($_POST['description']) ? wp_kses_post($_POST['description']) : '';
        $thumbnail = isset($_POST['thumbnail']) ? esc_url_raw($_POST['thumbnail']) : '';
        $difficulty_level = isset($_POST['difficulty_level']) ? sanitize_text_field($_POST['difficulty_level']) : '';
        $course_order = isset($_POST['course_order']) ? (int) $_POST['course_order'] : 0;
        $is_active = isset($_POST['is_active']) ? (int) $_POST['is_active'] : 1;
        $completion_points = isset($_POST['completion_points']) ? (int) $_POST['completion_points'] : 0;

        // Проверка обязательных полей
        if (!$id) {
            $this->send_ajax_error('Не указан ID курса.');
            return;
        }

        if (empty($title)) {
            $this->send_ajax_error('Название курса обязательно для заполнения.');
            return;
        }

        // Обновление курса
        $course_data = array(
            'title' => $title,
            'description' => $description,
            'thumbnail' => $thumbnail,
            'difficulty_level' => $difficulty_level,
            'course_order' => $course_order,
            'is_active' => $is_active,
            'completion_points' => $completion_points,
        );

        $result = $this->course_service->update($id, $course_data);

        if (!$result) {
            $this->send_ajax_error('Не удалось обновить курс.');
            return;
        }

        // Отправка ответа
        $this->send_ajax_success(array(
            'message' => 'Курс успешно обновлен.',
        ));
    }

    /**
     * AJAX: Удаление курса
     */
    public function ajax_delete_course() {
        // Проверка nonce
        if (!$this->verify_ajax_nonce()) {
            $this->send_ajax_error('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.');
            return;
        }

        // Получение ID курса
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if (!$id) {
            $this->send_ajax_error('Не указан ID курса.');
            return;
        }

        // Удаление курса
        $result = $this->course_service->delete($id);

        if (!$result) {
            $this->send_ajax_error('Не удалось удалить курс.');
            return;
        }

        // Отправка ответа
        $this->send_ajax_success(array(
            'message' => 'Курс успешно удален.',
        ));
    }

    /**
     * AJAX: Обновление порядка курсов
     */
    public function ajax_update_course_order() {
        // Проверка nonce
        if (!$this->verify_ajax_nonce()) {
            $this->send_ajax_error('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.');
            return;
        }

        // Получение данных о порядке курсов
        $course_orders = isset($_POST['course_orders']) ? $_POST['course_orders'] : array();

        if (empty($course_orders) || !is_array($course_orders)) {
            $this->send_ajax_error('Не указаны данные о порядке курсов.');
            return;
        }

        // Обновление порядка курсов
        $result = $this->course_service->update_order($course_orders);

        if (!$result) {
            $this->send_ajax_error('Не удалось обновить порядок курсов.');
            return;
        }

        // Отправка ответа
        $this->send_ajax_success(array(
            'message' => 'Порядок курсов успешно обновлен.',
        ));
    }
}
