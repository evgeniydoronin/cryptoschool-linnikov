<?php
/**
 * Сервис для работы с уроками
 *
 * @package CryptoSchool
 * @subpackage Services
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс сервиса для работы с уроками
 */
class CryptoSchool_Service_Lesson extends CryptoSchool_Service {
    /**
     * Репозиторий уроков
     *
     * @var CryptoSchool_Repository_Lesson
     */
    protected $repository;

    /**
     * Конструктор
     *
     * @param CryptoSchool_Loader $loader Экземпляр загрузчика
     */
    public function __construct(CryptoSchool_Loader $loader) {
        parent::__construct($loader);
        $this->repository = new CryptoSchool_Repository_Lesson();
    }

    /**
     * Регистрация хуков и фильтров
     *
     * @return void
     */
    protected function register_hooks() {
        // Регистрация AJAX-обработчиков
        $this->add_action('wp_ajax_cryptoschool_get_lessons', 'ajax_get_lessons');
        $this->add_action('wp_ajax_cryptoschool_create_lesson', 'ajax_create_lesson');
        $this->add_action('wp_ajax_cryptoschool_update_lesson', 'ajax_update_lesson');
        $this->add_action('wp_ajax_cryptoschool_delete_lesson', 'ajax_delete_lesson');
        $this->add_action('wp_ajax_cryptoschool_update_lesson_order', 'ajax_update_lesson_order');
        $this->add_action('wp_ajax_cryptoschool_mark_lesson_completed', 'ajax_mark_lesson_completed');
        
        // Регистрация шорткодов
        $this->add_shortcode('cryptoschool_lesson', 'shortcode_lesson');
    }

    /**
     * Получение всех уроков курса
     *
     * @param int   $course_id ID курса
     * @param array $args      Аргументы для фильтрации и сортировки
     * @return array
     */
    public function get_all($course_id, $args = []) {
        return $this->repository->get_course_lessons($course_id, $args);
    }

    /**
     * Получение количества уроков курса
     *
     * @param int   $course_id ID курса
     * @param array $args      Аргументы для фильтрации
     * @return int
     */
    public function get_count($course_id, $args = []) {
        return $this->repository->get_course_lessons_count($course_id, $args);
    }

    /**
     * Получение урока по ID
     *
     * @param int $id ID урока
     * @return mixed
     */
    public function get_by_id($id) {
        return $this->repository->find($id);
    }

    /**
     * Получение урока по слагу
     *
     * @param string $slug Слаг урока
     * @return mixed
     */
    public function get_by_slug($slug) {
        return $this->repository->get_by_slug($slug);
    }

    /**
     * Создание урока
     *
     * @param array $data Данные урока
     * @return int|false ID созданного урока или false в случае ошибки
     */
    public function create($data) {
        // Генерация слага, если не указан
        if (empty($data['slug']) && !empty($data['title'])) {
            $data['slug'] = $this->repository->generate_unique_slug($data['title'], $data['course_id']);
        }

        // Установка порядка отображения, если не указан
        if (!isset($data['lesson_order'])) {
            $data['lesson_order'] = $this->get_next_order($data['course_id']);
        }

        // Установка дат создания и обновления
        $data['created_at'] = current_time('mysql');
        $data['updated_at'] = current_time('mysql');

        return $this->repository->create($data);
    }

    /**
     * Обновление урока
     *
     * @param int   $id   ID урока
     * @param array $data Данные для обновления
     * @return bool
     */
    public function update($id, $data) {
        // Генерация слага, если изменилось название
        if (empty($data['slug']) && !empty($data['title'])) {
            // Получение урока для получения course_id
            $lesson = $this->get_by_id($id);
            if ($lesson) {
                $data['slug'] = $this->repository->generate_unique_slug($data['title'], $lesson->course_id, $id);
            }
        }

        // Установка даты обновления
        $data['updated_at'] = current_time('mysql');

        return $this->repository->update($id, $data);
    }

    /**
     * Удаление урока
     *
     * @param int $id ID урока
     * @return bool
     */
    public function delete($id) {
        return $this->repository->delete($id);
    }

    /**
     * Получение следующего порядкового номера для урока
     *
     * @param int $course_id ID курса
     * @return int
     */
    public function get_next_order($course_id) {
        global $wpdb;
        $table_name = $this->repository->get_table_name();

        $query = $wpdb->prepare(
            "SELECT MAX(lesson_order) FROM {$table_name} WHERE course_id = %d",
            $course_id
        );
        $max_order = (int) $wpdb->get_var($query);

        return $max_order + 1;
    }

    /**
     * Обновление порядка уроков
     *
     * @param array $lesson_orders Массив с ID уроков и их порядком
     * @return bool
     */
    public function update_order($lesson_orders) {
        return $this->repository->update_order($lesson_orders);
    }

    /**
     * Получение прогресса пользователя по уроку
     *
     * @param int $lesson_id ID урока
     * @param int $user_id   ID пользователя
     * @return array|null
     */
    public function get_user_progress($lesson_id, $user_id) {
        global $wpdb;
        $progress_table = $wpdb->prefix . 'cryptoschool_user_progress';

        $query = $wpdb->prepare(
            "SELECT * FROM {$progress_table} WHERE user_id = %d AND lesson_id = %d",
            $user_id,
            $lesson_id
        );
        $result = $wpdb->get_row($query, ARRAY_A);

        return $result ? $result : null;
    }

    /**
     * Отметка урока как пройденного
     *
     * @param int $lesson_id ID урока
     * @param int $user_id   ID пользователя
     * @return bool
     */
    public function mark_as_completed($lesson_id, $user_id) {
        global $wpdb;
        $progress_table = $wpdb->prefix . 'cryptoschool_user_progress';

        // Получение урока для получения баллов
        $lesson = $this->get_by_id($lesson_id);
        if (!$lesson) {
            return false;
        }

        // Проверка, есть ли уже запись о прогрессе
        $existing_progress = $this->get_user_progress($lesson_id, $user_id);

        if ($existing_progress) {
            // Обновление существующей записи
            $result = $wpdb->update(
                $progress_table,
                [
                    'status' => 'completed',
                    'completion_date' => current_time('mysql'),
                    'points' => $lesson->completion_points,
                ],
                [
                    'user_id' => $user_id,
                    'lesson_id' => $lesson_id,
                ]
            );
        } else {
            // Создание новой записи
            $result = $wpdb->insert(
                $progress_table,
                [
                    'user_id' => $user_id,
                    'lesson_id' => $lesson_id,
                    'status' => 'completed',
                    'completion_date' => current_time('mysql'),
                    'points' => $lesson->completion_points,
                ]
            );
        }

        // Обновление рейтинга пользователя
        if ($result) {
            $this->update_user_leaderboard($user_id);
        }

        return $result ? true : false;
    }

    /**
     * Обновление рейтинга пользователя
     *
     * @param int $user_id ID пользователя
     * @return bool
     */
    public function update_user_leaderboard($user_id) {
        global $wpdb;
        $leaderboard_table = $wpdb->prefix . 'cryptoschool_user_leaderboard';
        $progress_table = $wpdb->prefix . 'cryptoschool_user_progress';

        // Получение общего количества баллов
        $total_points_query = $wpdb->prepare(
            "SELECT SUM(points) FROM {$progress_table} WHERE user_id = %d",
            $user_id
        );
        $total_points = (int) $wpdb->get_var($total_points_query);

        // Получение количества завершенных уроков
        $completed_lessons_query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$progress_table} WHERE user_id = %d AND status = 'completed'",
            $user_id
        );
        $completed_lessons = (int) $wpdb->get_var($completed_lessons_query);

        // Получение количества дней на проекте
        $user_data = get_userdata($user_id);
        $days_active = floor((time() - strtotime($user_data->user_registered)) / (60 * 60 * 24));

        // Проверка, есть ли уже запись в таблице рейтинга
        $existing_record = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$leaderboard_table} WHERE user_id = %d",
                $user_id
            ),
            ARRAY_A
        );

        if ($existing_record) {
            // Обновление существующей записи
            $result = $wpdb->update(
                $leaderboard_table,
                [
                    'total_points' => $total_points,
                    'completed_lessons' => $completed_lessons,
                    'days_active' => $days_active,
                    'last_updated' => current_time('mysql'),
                ],
                [
                    'user_id' => $user_id,
                ]
            );
        } else {
            // Создание новой записи
            $result = $wpdb->insert(
                $leaderboard_table,
                [
                    'user_id' => $user_id,
                    'total_points' => $total_points,
                    'completed_lessons' => $completed_lessons,
                    'days_active' => $days_active,
                    'last_updated' => current_time('mysql'),
                ]
            );
        }

        // Обновление рангов всех пользователей
        $this->update_leaderboard_ranks();

        return $result ? true : false;
    }

    /**
     * Обновление рангов в таблице рейтинга
     *
     * @return bool
     */
    public function update_leaderboard_ranks() {
        global $wpdb;
        $leaderboard_table = $wpdb->prefix . 'cryptoschool_user_leaderboard';

        // Получение всех пользователей, отсортированных по баллам
        $users = $wpdb->get_results(
            "SELECT id, user_id FROM {$leaderboard_table} ORDER BY total_points DESC, completed_lessons DESC",
            ARRAY_A
        );

        // Обновление рангов
        foreach ($users as $index => $user) {
            $wpdb->update(
                $leaderboard_table,
                ['rank' => $index + 1],
                ['id' => $user['id']]
            );
        }

        return true;
    }

    /**
     * Получение следующего урока
     *
     * @param int $lesson_id ID текущего урока
     * @return mixed
     */
    public function get_next_lesson($lesson_id) {
        $lesson = $this->get_by_id($lesson_id);
        if (!$lesson) {
            return null;
        }

        return $this->repository->get_next_lesson($lesson_id);
    }

    /**
     * Получение предыдущего урока
     *
     * @param int $lesson_id ID текущего урока
     * @return mixed
     */
    public function get_previous_lesson($lesson_id) {
        $lesson = $this->get_by_id($lesson_id);
        if (!$lesson) {
            return null;
        }

        return $this->repository->get_previous_lesson($lesson_id);
    }

    /**
     * AJAX-обработчик для получения уроков
     *
     * @return void
     */
    public function ajax_get_lessons() {
        // Проверка nonce
        check_ajax_referer('cryptoschool_admin_nonce', 'nonce');

        // Проверка прав доступа
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('У вас нет прав для выполнения этого действия.', 'cryptoschool'));
        }

        // Получение параметров
        $course_id = isset($_POST['course_id']) ? (int) $_POST['course_id'] : 0;
        if ($course_id <= 0) {
            wp_send_json_error(__('Некорректный ID курса.', 'cryptoschool'));
        }

        $args = [];
        if (isset($_POST['is_active'])) {
            $args['is_active'] = (int) $_POST['is_active'];
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

        // Получение уроков
        $lessons = $this->get_all($course_id, $args);

        // Подготовка данных для ответа
        $data = [];
        foreach ($lessons as $lesson) {
            $data[] = [
                'id' => $lesson->id,
                'course_id' => $lesson->course_id,
                'title' => $lesson->title,
                'content' => $lesson->content,
                'video_url' => $lesson->video_url,
                'slug' => $lesson->slug,
                'lesson_order' => $lesson->lesson_order,
                'is_active' => $lesson->is_active,
                'completion_points' => $lesson->completion_points,
                'created_at' => $lesson->get_created_at(),
                'updated_at' => $lesson->get_updated_at(),
            ];
        }

        wp_send_json_success($data);
    }

    /**
     * AJAX-обработчик для создания урока
     *
     * @return void
     */
    public function ajax_create_lesson() {
        // Проверка nonce
        check_ajax_referer('cryptoschool_admin_nonce', 'nonce');

        // Проверка прав доступа
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('У вас нет прав для выполнения этого действия.', 'cryptoschool'));
        }

        // Получение данных из запроса
        $course_id = isset($_POST['course_id']) ? (int) $_POST['course_id'] : 0;
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';
        $video_url = isset($_POST['video_url']) ? esc_url_raw($_POST['video_url']) : '';
        $is_active = isset($_POST['is_active']) ? (int) $_POST['is_active'] : 1;
        $completion_points = isset($_POST['completion_points']) ? (int) $_POST['completion_points'] : 5;

        // Проверка обязательных полей
        if (empty($title)) {
            wp_send_json_error(__('Название урока обязательно для заполнения.', 'cryptoschool'));
        }

        if ($course_id <= 0) {
            wp_send_json_error(__('Некорректный ID курса.', 'cryptoschool'));
        }

        // Создание урока
        $lesson_data = [
            'course_id' => $course_id,
            'title' => $title,
            'content' => $content,
            'video_url' => $video_url,
            'is_active' => $is_active,
            'completion_points' => $completion_points,
        ];

        $lesson_id = $this->create($lesson_data);

        if (!$lesson_id) {
            wp_send_json_error(__('Не удалось создать урок.', 'cryptoschool'));
        }

        // Получение созданного урока
        $lesson = $this->get_by_id($lesson_id);

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
            'created_at' => $lesson->get_created_at(),
            'updated_at' => $lesson->get_updated_at(),
        ];

        wp_send_json_success($data);
    }

    /**
     * AJAX-обработчик для обновления урока
     *
     * @return void
     */
    public function ajax_update_lesson() {
        // Проверка nonce
        check_ajax_referer('cryptoschool_admin_nonce', 'nonce');

        // Проверка прав доступа
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('У вас нет прав для выполнения этого действия.', 'cryptoschool'));
        }

        // Получение ID урока
        $lesson_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($lesson_id <= 0) {
            wp_send_json_error(__('Некорректный ID урока.', 'cryptoschool'));
        }

        // Получение данных из запроса
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';
        $video_url = isset($_POST['video_url']) ? esc_url_raw($_POST['video_url']) : '';
        $is_active = isset($_POST['is_active']) ? (int) $_POST['is_active'] : 1;
        $completion_points = isset($_POST['completion_points']) ? (int) $_POST['completion_points'] : 5;

        // Проверка обязательных полей
        if (empty($title)) {
            wp_send_json_error(__('Название урока обязательно для заполнения.', 'cryptoschool'));
        }

        // Обновление урока
        $lesson_data = [
            'title' => $title,
            'content' => $content,
            'video_url' => $video_url,
            'is_active' => $is_active,
            'completion_points' => $completion_points,
        ];

        $result = $this->update($lesson_id, $lesson_data);

        if (!$result) {
            wp_send_json_error(__('Не удалось обновить урок.', 'cryptoschool'));
        }

        // Получение обновленного урока
        $lesson = $this->get_by_id($lesson_id);

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
            'created_at' => $lesson->get_created_at(),
            'updated_at' => $lesson->get_updated_at(),
        ];

        wp_send_json_success($data);
    }

    /**
     * AJAX-обработчик для удаления урока
     *
     * @return void
     */
    public function ajax_delete_lesson() {
        // Проверка nonce
        check_ajax_referer('cryptoschool_admin_nonce', 'nonce');

        // Проверка прав доступа
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('У вас нет прав для выполнения этого действия.', 'cryptoschool'));
        }

        // Получение ID урока
        $lesson_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($lesson_id <= 0) {
            wp_send_json_error(__('Некорректный ID урока.', 'cryptoschool'));
        }

        // Удаление урока
        $result = $this->delete($lesson_id);

        if (!$result) {
            wp_send_json_error(__('Не удалось удалить урок.', 'cryptoschool'));
        }

        wp_send_json_success();
    }

    /**
     * AJAX-обработчик для обновления порядка уроков
     *
     * @return void
     */
    public function ajax_update_lesson_order() {
        // Проверка nonce
        check_ajax_referer('cryptoschool_admin_nonce', 'nonce');

        // Проверка прав доступа
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('У вас нет прав для выполнения этого действия.', 'cryptoschool'));
        }

        // Получение данных из запроса
        $lesson_orders = isset($_POST['lesson_orders']) ? $_POST['lesson_orders'] : [];
        if (empty($lesson_orders) || !is_array($lesson_orders)) {
            wp_send_json_error(__('Некорректные данные порядка уроков.', 'cryptoschool'));
        }

        // Обновление порядка уроков
        $result = $this->update_order($lesson_orders);

        if (!$result) {
            wp_send_json_error(__('Не удалось обновить порядок уроков.', 'cryptoschool'));
        }

        wp_send_json_success();
    }

    /**
     * AJAX-обработчик для отметки урока как пройденного
     *
     * @return void
     */
    public function ajax_mark_lesson_completed() {
        // Проверка nonce
        check_ajax_referer('cryptoschool_admin_nonce', 'nonce');

        // Проверка авторизации
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Вы должны быть авторизованы для выполнения этого действия.', 'cryptoschool'));
        }

        // Получение ID урока
        $lesson_id = isset($_POST['lesson_id']) ? (int) $_POST['lesson_id'] : 0;
        if ($lesson_id <= 0) {
            wp_send_json_error(__('Некорректный ID урока.', 'cryptoschool'));
        }

        // Получение ID пользователя
        $user_id = get_current_user_id();

        // Отметка урока как пройденного
        $result = $this->mark_as_completed($lesson_id, $user_id);

        if (!$result) {
            wp_send_json_error(__('Не удалось отметить урок как пройденный.', 'cryptoschool'));
        }

        // Получение урока
        $lesson = $this->get_by_id($lesson_id);

        // Получение следующего урока
        $next_lesson = $this->get_next_lesson($lesson_id);

        // Подготовка данных для ответа
        $data = [
            'lesson_id' => $lesson_id,
            'points' => $lesson->completion_points,
            'next_lesson' => $next_lesson ? [
                'id' => $next_lesson->id,
                'title' => $next_lesson->title,
                'slug' => $next_lesson->slug,
            ] : null,
        ];

        wp_send_json_success($data);
    }

    /**
     * Шорткод для отображения информации об уроке
     *
     * @param array $atts Атрибуты шорткода
     * @return string
     */
    public function shortcode_lesson($atts) {
        $atts = shortcode_atts([
            'id' => 0,
            'slug' => '',
            'template' => 'default',
        ], $atts, 'cryptoschool_lesson');

        // Получение урока
        $lesson = null;
        if (!empty($atts['id'])) {
            $lesson = $this->get_by_id((int) $atts['id']);
        } elseif (!empty($atts['slug'])) {
            $lesson = $this->get_by_slug(sanitize_text_field($atts['slug']));
        }

        if (!$lesson) {
            return '';
        }

        // Получение курса
        $course_repository = new CryptoSchool_Repository_Course();
        $course = $course_repository->find($lesson->course_id);

        if (!$course) {
            return '';
        }

        // Получение прогресса пользователя
        $user_id = get_current_user_id();
        $user_progress = $user_id ? $this->get_user_progress($lesson->id, $user_id) : null;

        // Проверка доступа пользователя к курсу
        $course_service = new CryptoSchool_Service_Course($this->loader);
        $has_access = $user_id ? $course_service->is_available_for_user($course->id, $user_id) : false;

        // Получение следующего и предыдущего уроков
        $next_lesson = $this->get_next_lesson($lesson->id);
        $previous_lesson = $this->get_previous_lesson($lesson->id);

        // Подключение шаблона
        $template = sanitize_text_field($atts['template']);
        $template_path = plugin_dir_path(dirname(dirname(__FILE__))) . 'public/views/shortcodes/lesson-' . $template . '.php';

        if (!file_exists($template_path)) {
            $template_path = plugin_dir_path(dirname(dirname(__FILE__))) . 'public/views/shortcodes/lesson-default.php';
        }

        ob_start();
        include $template_path;
        return ob_get_clean();
    }
}
