<?php
/**
 * Контроллер для управления уроками в административной части
 *
 * @package CryptoSchool
 * @subpackage Admin\Controllers
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Контроллер для управления уроками
 */
class CryptoSchool_Admin_Lessons_Controller extends CryptoSchool_Admin_Controller {

    /**
     * Сервис для работы с уроками
     *
     * @var CryptoSchool_Service_Lesson
     */
    private $lesson_service;

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
        $this->lesson_service = new CryptoSchool_Service_Lesson($loader);
        $this->course_service = new CryptoSchool_Service_Course($loader);
        
        parent::__construct($loader);
    }

    /**
     * Регистрация хуков
     */
    protected function register_hooks() {
        // Регистрация AJAX-обработчиков
        add_action('wp_ajax_cryptoschool_get_lessons', array($this, 'ajax_get_lessons'));
        add_action('wp_ajax_cryptoschool_get_lesson', array($this, 'ajax_get_lesson'));
        add_action('wp_ajax_cryptoschool_create_lesson', array($this, 'ajax_create_lesson'));
        add_action('wp_ajax_cryptoschool_update_lesson', array($this, 'ajax_update_lesson'));
        add_action('wp_ajax_cryptoschool_delete_lesson', array($this, 'ajax_delete_lesson'));
        add_action('wp_ajax_cryptoschool_update_lesson_order', array($this, 'ajax_update_lesson_order'));
        
        // Добавляем обработчик для сохранения урока через форму
        add_action('admin_post_cryptoschool_save_lesson', array($this, 'handle_save_lesson'));
    }

    /**
     * Отображение страницы уроков
     *
     * @param int $course_id ID курса
     */
    public function display_lessons_page($course_id = 0) {
        // Если ID курса не передан, пытаемся получить его из GET-параметра
        if (!$course_id) {
            $course_id = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;
        }

        // Если ID курса указан, отображаем уроки только этого курса
        if ($course_id) {
            // Получение списка уроков для курса
            $lessons = $this->lesson_service->get_all($course_id);

            // Получение информации о курсе
            $course = $this->course_service->get_by_id($course_id);

            // Отображение страницы
            $this->render_view('lessons', array(
                'lessons' => $lessons,
                'course' => $course,
                'course_id' => $course_id,
                'all_courses' => false
            ));
        } 
        // Если ID курса не указан, отображаем уроки всех курсов
        else {
            // Получение списка всех курсов
            $courses = $this->course_service->get_all();
            
            // Массив для хранения всех уроков
            $all_lessons = [];
            
            // Получение уроков для каждого курса
            foreach ($courses as $course) {
                $lessons = $this->lesson_service->get_all($course->id);
                
                // Добавление информации о курсе к каждому уроку
                foreach ($lessons as $lesson) {
                    $lesson->course_title = $course ? $course->title : '';
                    $all_lessons[] = $lesson;
                }
            }
            
            // Отображение страницы со всеми уроками
            $this->render_view('lessons', array(
                'lessons' => $all_lessons,
                'course' => null,
                'course_id' => 0,
                'all_courses' => true
            ));
        }
    }

    /**
     * Отображение страницы создания нового урока
     */
    public function display_add_lesson_page() {
        // Получение ID курса из GET-параметра
        $course_id = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;
        
        // Проверка существования курса
        if ($course_id) {
            $course = $this->course_service->get_by_id($course_id);
            if (!$course) {
                wp_die(__('Курс не найден.', 'cryptoschool'));
            }
        } else {
            wp_die(__('Не указан ID курса.', 'cryptoschool'));
        }
        
        // Отображение страницы создания урока
        $this->render_view('lesson-edit', array(
            'lesson' => null,
            'course' => $course,
            'course_id' => $course_id,
            'is_new' => true,
            'page_title' => __('Добавить урок', 'cryptoschool')
        ));
    }

    /**
     * Отображение страницы редактирования урока
     */
    public function display_edit_lesson_page() {
        // Получение ID урока из GET-параметра
        $lesson_id = isset($_GET['lesson_id']) ? (int) $_GET['lesson_id'] : 0;
        
        // Проверка существования урока
        if (!$lesson_id) {
            wp_die(__('Не указан ID урока.', 'cryptoschool'));
        }
        
        $lesson = $this->lesson_service->get_by_id($lesson_id);
        if (!$lesson) {
            wp_die(__('Урок не найден.', 'cryptoschool'));
        }
        
        // Получение информации о курсе
        $course = $this->course_service->get_by_id($lesson->course_id);
        
        // Отображение страницы редактирования урока
        $this->render_view('lesson-edit', array(
            'lesson' => $lesson,
            'course' => $course,
            'course_id' => $lesson->course_id,
            'is_new' => false,
            'page_title' => __('Редактировать урок', 'cryptoschool')
        ));
    }

    /**
     * Обработка сохранения урока
     */
    public function handle_save_lesson() {
        // Проверка, был ли отправлен запрос на сохранение урока
        if (!isset($_POST['cryptoschool_save_lesson'])) {
            return;
        }
        
        // Проверка nonce
        check_admin_referer('cryptoschool_save_lesson', 'cryptoschool_lesson_nonce');
        
        // Получение данных урока
        $lesson_id = isset($_POST['lesson_id']) ? (int) $_POST['lesson_id'] : 0;
        $course_id = isset($_POST['course_id']) ? (int) $_POST['course_id'] : 0;
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';
        $video_url = isset($_POST['video_url']) ? esc_url_raw($_POST['video_url']) : '';
        $lesson_order = isset($_POST['lesson_order']) ? (int) $_POST['lesson_order'] : 0;
        $completion_points = isset($_POST['completion_points']) ? (int) $_POST['completion_points'] : 5;
        $is_active = isset($_POST['is_active']) ? (int) $_POST['is_active'] : 1;
        
        // Проверка обязательных полей
        if (!$course_id) {
            wp_die(__('Не указан ID курса.', 'cryptoschool'));
        }
        
        if (empty($title)) {
            wp_die(__('Название урока обязательно для заполнения.', 'cryptoschool'));
        }
        
        // Подготовка данных урока
        $lesson_data = array(
            'course_id' => $course_id,
            'title' => $title,
            'content' => $content,
            'video_url' => $video_url,
            'lesson_order' => $lesson_order,
            'completion_points' => $completion_points,
            'is_active' => $is_active,
        );
        
        // Создание или обновление урока
        if ($lesson_id) {
            $result = $this->lesson_service->update($lesson_id, $lesson_data);
            $message = __('Урок успешно обновлен.', 'cryptoschool');
        } else {
            $lesson_id = $this->lesson_service->create($lesson_data);
            $result = $lesson_id ? true : false;
            $message = __('Урок успешно создан.', 'cryptoschool');
        }
        
        // Проверка результата
        if (!$result) {
            wp_die(__('Не удалось сохранить урок.', 'cryptoschool'));
        }
        
        // Перенаправление на страницу уроков с сообщением об успехе
        wp_redirect(add_query_arg(
            array(
                'page' => 'cryptoschool-lessons',
                'course_id' => $course_id,
                'message' => 'success',
                'message_text' => urlencode($message)
            ),
            admin_url('admin.php')
        ));
        exit;
    }

    /**
     * AJAX: Получение списка уроков
     */
    public function ajax_get_lessons() {
        // Проверка nonce
        if (!$this->verify_ajax_nonce()) {
            $this->send_ajax_error('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.');
            return;
        }

        // Получение параметров фильтрации
        $course_id = isset($_POST['course_id']) ? (int) $_POST['course_id'] : 0;
        $is_active = isset($_POST['is_active']) ? sanitize_text_field($_POST['is_active']) : '';
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

        if (!$course_id) {
            $this->send_ajax_error('Не указан ID курса.');
            return;
        }

        // Получение списка уроков
        $args = array();
        if (!empty($is_active)) {
            $args['is_active'] = $is_active;
        }
        if (!empty($search)) {
            $args['search'] = $search;
        }
        $lessons = $this->lesson_service->get_all($course_id, $args);

        // Отправка ответа
        $this->send_ajax_success($lessons);
    }

    /**
     * AJAX: Получение данных урока
     */
    public function ajax_get_lesson() {
        // Проверка nonce
        if (!$this->verify_ajax_nonce()) {
            $this->send_ajax_error('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.');
            return;
        }

        // Получение ID урока
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if (!$id) {
            $this->send_ajax_error('Не указан ID урока.');
            return;
        }

        // Получение данных урока
        $lesson = $this->lesson_service->get_by_id($id);

        if (!$lesson) {
            $this->send_ajax_error('Урок не найден.');
            return;
        }

        // Подготовка данных для ответа
        $data = [
            'id' => $lesson->id,
            'course_id' => $lesson->course_id,
            'title' => $lesson->title,
            'content' => $lesson->content,
            'video_url' => $lesson->video_url,
            'slug' => $lesson->slug,
            'lesson_order' => $lesson->lesson_order,
            'is_active' => $lesson->is_active,
            'completion_points' => $lesson->completion_points,
            'created_at' => $lesson->created_at,
            'updated_at' => $lesson->updated_at,
        ];

        // Отправка ответа
        $this->send_ajax_success($data);
    }

    /**
     * AJAX: Создание урока
     */
    public function ajax_create_lesson() {
        // Проверка nonce
        if (!$this->verify_ajax_nonce()) {
            $this->send_ajax_error('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.');
            return;
        }

        // Получение данных урока
        $course_id = isset($_POST['course_id']) ? (int) $_POST['course_id'] : 0;
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';
        $video_url = isset($_POST['video_url']) ? esc_url_raw($_POST['video_url']) : '';
        $lesson_order = isset($_POST['lesson_order']) ? (int) $_POST['lesson_order'] : 0;
        $completion_points = isset($_POST['completion_points']) ? (int) $_POST['completion_points'] : 5;
        $is_active = isset($_POST['is_active']) ? (int) $_POST['is_active'] : 1;

        // Проверка обязательных полей
        if (!$course_id) {
            $this->send_ajax_error('Не указан ID курса.');
            return;
        }

        if (empty($title)) {
            $this->send_ajax_error('Название урока обязательно для заполнения.');
            return;
        }

        // Создание урока
        $lesson_data = array(
            'course_id' => $course_id,
            'title' => $title,
            'content' => $content,
            'video_url' => $video_url,
            'lesson_order' => $lesson_order,
            'completion_points' => $completion_points,
            'is_active' => $is_active,
        );

        $lesson_id = $this->lesson_service->create($lesson_data);

        if (!$lesson_id) {
            $this->send_ajax_error('Не удалось создать урок.');
            return;
        }

        // Отправка ответа
        $this->send_ajax_success(array(
            'id' => $lesson_id,
            'message' => 'Урок успешно создан.',
        ));
    }

    /**
     * AJAX: Обновление урока
     */
    public function ajax_update_lesson() {
        // Проверка nonce
        if (!$this->verify_ajax_nonce()) {
            $this->send_ajax_error('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.');
            return;
        }

        // Получение данных урока
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $course_id = isset($_POST['course_id']) ? (int) $_POST['course_id'] : 0;
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';
        $video_url = isset($_POST['video_url']) ? esc_url_raw($_POST['video_url']) : '';
        $lesson_order = isset($_POST['lesson_order']) ? (int) $_POST['lesson_order'] : 0;
        $completion_points = isset($_POST['completion_points']) ? (int) $_POST['completion_points'] : 5;
        $is_active = isset($_POST['is_active']) ? (int) $_POST['is_active'] : 1;

        // Проверка обязательных полей
        if (!$id) {
            $this->send_ajax_error('Не указан ID урока.');
            return;
        }

        if (!$course_id) {
            $this->send_ajax_error('Не указан ID курса.');
            return;
        }

        if (empty($title)) {
            $this->send_ajax_error('Название урока обязательно для заполнения.');
            return;
        }

        // Обновление урока
        $lesson_data = array(
            'course_id' => $course_id,
            'title' => $title,
            'content' => $content,
            'video_url' => $video_url,
            'lesson_order' => $lesson_order,
            'completion_points' => $completion_points,
            'is_active' => $is_active,
        );

        $result = $this->lesson_service->update($id, $lesson_data);

        if (!$result) {
            $this->send_ajax_error('Не удалось обновить урок.');
            return;
        }

        // Отправка ответа
        $this->send_ajax_success(array(
            'message' => 'Урок успешно обновлен.',
        ));
    }

    /**
     * AJAX: Удаление урока
     */
    public function ajax_delete_lesson() {
        // Проверка nonce
        if (!$this->verify_ajax_nonce()) {
            $this->send_ajax_error('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.');
            return;
        }

        // Получение ID урока
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if (!$id) {
            $this->send_ajax_error('Не указан ID урока.');
            return;
        }

        // Удаление урока
        $result = $this->lesson_service->delete($id);

        if (!$result) {
            $this->send_ajax_error('Не удалось удалить урок.');
            return;
        }

        // Отправка ответа
        $this->send_ajax_success(array(
            'message' => 'Урок успешно удален.',
        ));
    }

    /**
     * AJAX: Обновление порядка уроков
     */
    public function ajax_update_lesson_order() {
        // Проверка nonce
        if (!$this->verify_ajax_nonce()) {
            $this->send_ajax_error('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.');
            return;
        }

        // Получение данных о порядке уроков
        $lesson_orders = isset($_POST['lesson_orders']) ? $_POST['lesson_orders'] : array();

        if (empty($lesson_orders) || !is_array($lesson_orders)) {
            $this->send_ajax_error('Не указаны данные о порядке уроков.');
            return;
        }

        // Обновление порядка уроков
        $result = $this->lesson_service->update_order($lesson_orders);

        if (!$result) {
            $this->send_ajax_error('Не удалось обновить порядок уроков.');
            return;
        }

        // Отправка ответа
        $this->send_ajax_success(array(
            'message' => 'Порядок уроков успешно обновлен.',
        ));
    }
}
