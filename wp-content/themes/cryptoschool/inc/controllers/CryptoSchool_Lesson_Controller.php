<?php
/**
 * Контроллер для страницы урока
 * Отвечает за обработку логики урока, проверку доступа и подготовку данных
 *
 * @package CryptoSchool
 */

if (!defined('ABSPATH')) {
    exit;
}

class CryptoSchool_Lesson_Controller {
    
    private $lesson_id;
    private $lesson_trid;
    private $current_user_id;
    private $lesson_post;
    private $course_post;
    
    /**
     * Конструктор контроллера
     */
    public function __construct() {
        $this->lesson_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $this->current_user_id = get_current_user_id();
        $this->init_lesson_data();
    }
    
    /**
     * Инициализация базовых данных урока
     */
    private function init_lesson_data() {
        global $wpdb;
        
        // Получаем trid урока для единого прогресса независимо от языка
        $this->lesson_trid = $wpdb->get_var($wpdb->prepare(
            "SELECT trid FROM {$wpdb->prefix}icl_translations 
             WHERE element_id = %d AND element_type = %s",
            $this->lesson_id, 'post_cryptoschool_lesson'
        ));
        
        // Если trid не найден, используем lesson_id как fallback
        if (!$this->lesson_trid) {
            $this->lesson_trid = $this->lesson_id;
        }
        
        // Получаем данные урока
        $this->lesson_post = get_post($this->lesson_id);
        
        // Находим курс, к которому относится урок
        $course_posts = get_posts([
            'post_type' => 'cryptoschool_course',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query' => [
                [
                    'key' => 'choose_lesson',
                    'value' => '"' . $this->lesson_id . '"',
                    'compare' => 'LIKE'
                ]
            ]
        ]);
        
        $this->course_post = !empty($course_posts) ? $course_posts[0] : null;
    }
    
    /**
     * Проверка авторизации пользователя
     */
    public function check_authentication() {
        if (!is_user_logged_in()) {
            wp_redirect(site_url('/sign-in/'));
            exit;
        }
    }
    
    /**
     * Проверка доступности урока для пользователя
     */
    public function check_lesson_accessibility() {
        $loader = new CryptoSchool_Loader();
        $accessibility_service = new CryptoSchool_Service_Accessibility($loader);
        
        $accessibility_result = $accessibility_service->check_lesson_accessibility(
            $this->current_user_id, 
            $this->lesson_id
        );
        
        if (!$accessibility_result['accessible']) {
            wp_redirect($accessibility_result['redirect_url']);
            exit;
        }
    }
    
    /**
     * Валидация урока
     */
    public function validate_lesson() {
        if (!$this->lesson_post || 
            $this->lesson_post->post_type !== 'cryptoschool_lesson' || 
            $this->lesson_post->post_status !== 'publish') {
            wp_redirect(site_url('/courses/'));
            exit;
        }
        
        if (!$this->course_post) {
            wp_redirect(site_url('/courses/'));
            exit;
        }
    }
    
    /**
     * Получение данных урока
     */
    public function get_lesson_data() {
        $course_table_id = get_post_meta($this->course_post->ID, '_cryptoschool_table_id', true);
        if (!$course_table_id) {
            $course_table_id = $this->course_post->ID;
        }
        
        return (object) [
            'id' => $this->lesson_post->ID,
            'title' => $this->lesson_post->post_title,
            'content' => $this->lesson_post->post_content,
            'video_url' => get_post_meta($this->lesson_post->ID, 'video_url', true),
            'course_id' => $course_table_id,
            'post' => $this->lesson_post
        ];
    }
    
    /**
     * Получение данных курса
     */
    public function get_course_data() {
        $course_table_id = get_post_meta($this->course_post->ID, '_cryptoschool_table_id', true);
        if (!$course_table_id) {
            $course_table_id = $this->course_post->ID;
        }
        
        return (object) [
            'id' => $course_table_id,
            'title' => $this->course_post->post_title,
            'post' => $this->course_post
        ];
    }
    
    /**
     * Получение заданий урока
     */
    public function get_lesson_tasks() {
        $acf_tasks = get_field('zadaniya_uroka', $this->lesson_id);
        $tasks = [];
        
        if ($acf_tasks && is_array($acf_tasks)) {
            foreach ($acf_tasks as $index => $task) {
                if (isset($task['punkt']) && !empty($task['punkt'])) {
                    $tasks[] = (object) [
                        'id' => $this->lesson_trid * 1000 + $index,
                        'title' => $task['punkt'],
                        'order' => $index
                    ];
                }
            }
        }
        
        return $tasks;
    }
    
    /**
     * Получение навигации по урокам курса
     */
    public function get_lesson_navigation() {
        $course_lessons = get_field('choose_lesson', $this->course_post->ID);
        $current_lesson_index = -1;
        $prev_lesson = null;
        $next_lesson = null;
        
        if ($course_lessons && is_array($course_lessons)) {
            foreach ($course_lessons as $index => $course_lesson) {
                $lesson_post_id = is_object($course_lesson) ? 
                    $course_lesson->ID : 
                    (is_array($course_lesson) ? $course_lesson['ID'] : intval($course_lesson));
                
                if ($lesson_post_id == $this->lesson_id) {
                    $current_lesson_index = $index;
                    break;
                }
            }
            
            if ($current_lesson_index > 0) {
                $prev_lesson_data = $course_lessons[$current_lesson_index - 1];
                $prev_lesson = is_object($prev_lesson_data) ? 
                    $prev_lesson_data : 
                    get_post(intval($prev_lesson_data));
            }
            
            if ($current_lesson_index < count($course_lessons) - 1) {
                $next_lesson_data = $course_lessons[$current_lesson_index + 1];
                $next_lesson = is_object($next_lesson_data) ? 
                    $next_lesson_data : 
                    get_post(intval($next_lesson_data));
            }
        }
        
        return [
            'prev_lesson' => $prev_lesson,
            'next_lesson' => $next_lesson,
            'current_index' => $current_lesson_index
        ];
    }
    
    /**
     * Обработка отправки формы заданий
     */
    public function handle_form_submission() {
        $form_result = [
            'submitted' => false,
            'success' => false,
            'message' => ''
        ];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_lesson'])) {
            $form_result['submitted'] = true;
            
            // Проверка nonce
            if (!isset($_POST['lesson_nonce']) || 
                !wp_verify_nonce($_POST['lesson_nonce'], 'complete_lesson_' . $this->lesson_id)) {
                $form_result['message'] = __('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.', 'cryptoschool');
                return $form_result;
            }
            
            $tasks = $this->get_lesson_tasks();
            $completed_tasks = isset($_POST['completed_tasks']) ? $_POST['completed_tasks'] : [];
            
            // Обновляем прогресс по заданиям
            $this->update_task_progress($tasks, $completed_tasks);
            
            // Проверяем завершение урока
            if (count($completed_tasks) === count($tasks)) {
                $this->complete_lesson();
                $form_result['success'] = true;
                $form_result['message'] = __('Урок успешно завершен!', 'cryptoschool');
            } else {
                $this->update_lesson_progress($tasks, $completed_tasks);
                $form_result['success'] = true;
                $form_result['message'] = __('Прогресс сохранен. Для завершения урока выполните все задания.', 'cryptoschool');
            }
        }
        
        return $form_result;
    }
    
    /**
     * Обновление прогресса по заданиям
     */
    private function update_task_progress($tasks, $completed_tasks) {
        global $wpdb;
        
        foreach ($tasks as $task) {
            $is_completed = in_array($task->id, $completed_tasks);
            
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}cryptoschool_user_task_progress 
                 WHERE user_id = %d AND lesson_id = %d AND task_id = %s",
                $this->current_user_id, $this->lesson_trid, $task->id
            ));
            
            if ($existing) {
                $wpdb->update(
                    $wpdb->prefix . 'cryptoschool_user_task_progress',
                    [
                        'is_completed' => $is_completed ? 1 : 0,
                        'completed_at' => $is_completed ? current_time('mysql') : null
                    ],
                    [
                        'user_id' => $this->current_user_id,
                        'lesson_id' => $this->lesson_trid,
                        'task_id' => $task->id
                    ]
                );
            } else {
                $wpdb->insert(
                    $wpdb->prefix . 'cryptoschool_user_task_progress',
                    [
                        'user_id' => $this->current_user_id,
                        'lesson_id' => $this->lesson_trid,
                        'task_id' => $task->id,
                        'is_completed' => $is_completed ? 1 : 0,
                        'completed_at' => $is_completed ? current_time('mysql') : null
                    ]
                );
            }
        }
    }
    
    /**
     * Завершение урока
     */
    private function complete_lesson() {
        global $wpdb;
        
        $existing_progress = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}cryptoschool_user_lesson_progress 
             WHERE user_id = %d AND lesson_id = %d",
            $this->current_user_id, $this->lesson_trid
        ));
        
        if ($existing_progress) {
            $wpdb->update(
                $wpdb->prefix . 'cryptoschool_user_lesson_progress',
                [
                    'is_completed' => 1,
                    'progress_percent' => 100,
                    'completed_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ],
                ['user_id' => $this->current_user_id, 'lesson_id' => $this->lesson_trid]
            );
        } else {
            $wpdb->insert(
                $wpdb->prefix . 'cryptoschool_user_lesson_progress',
                [
                    'user_id' => $this->current_user_id,
                    'lesson_id' => $this->lesson_trid,
                    'is_completed' => 1,
                    'progress_percent' => 100,
                    'completed_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ]
            );
        }
        
        // Вызываем action для начисления баллов
        do_action('cryptoschool_lesson_completed', $this->current_user_id, $this->lesson_trid);
    }
    
    /**
     * Обновление прогресса урока
     */
    private function update_lesson_progress($tasks, $completed_tasks) {
        global $wpdb;
        
        $progress_percent = count($completed_tasks) > 0 ? 
            round(count($completed_tasks) * 100 / count($tasks)) : 0;
        
        $existing_progress = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}cryptoschool_user_lesson_progress 
             WHERE user_id = %d AND lesson_id = %d",
            $this->current_user_id, $this->lesson_trid
        ));
        
        if ($existing_progress) {
            $wpdb->update(
                $wpdb->prefix . 'cryptoschool_user_lesson_progress',
                [
                    'is_completed' => 0,
                    'progress_percent' => $progress_percent,
                    'updated_at' => current_time('mysql')
                ],
                ['user_id' => $this->current_user_id, 'lesson_id' => $this->lesson_trid]
            );
        } else {
            $wpdb->insert(
                $wpdb->prefix . 'cryptoschool_user_lesson_progress',
                [
                    'user_id' => $this->current_user_id,
                    'lesson_id' => $this->lesson_trid,
                    'is_completed' => 0,
                    'progress_percent' => $progress_percent,
                    'updated_at' => current_time('mysql')
                ]
            );
        }
    }
    
    /**
     * Получение прогресса пользователя по уроку
     */
    public function get_user_progress() {
        global $wpdb;
        
        $progress_query = "
            SELECT progress_percent, is_completed, completed_at, updated_at
            FROM {$wpdb->prefix}cryptoschool_user_lesson_progress 
            WHERE user_id = %d AND lesson_id = %d
        ";
        
        return $wpdb->get_row($wpdb->prepare(
            $progress_query, 
            $this->current_user_id, 
            $this->lesson_trid
        ));
    }
    
    /**
     * Получение прогресса по заданиям
     */
    public function get_task_progress($tasks) {
        global $wpdb;
        
        $user_task_progress = [];
        foreach ($tasks as $task) {
            $progress = $wpdb->get_var($wpdb->prepare(
                "SELECT is_completed FROM {$wpdb->prefix}cryptoschool_user_task_progress 
                 WHERE user_id = %d AND lesson_id = %d AND task_id = %s",
                $this->current_user_id, $this->lesson_trid, $task->id
            ));
            $user_task_progress[$task->id] = (bool)$progress;
        }
        
        return $user_task_progress;
    }
    
    /**
     * Подготовка всех данных для страницы урока
     */
    public function prepare_lesson_page() {
        // Проверки доступа
        $this->check_authentication();
        $this->check_lesson_accessibility();
        $this->validate_lesson();
        
        // Обработка формы
        $form_result = $this->handle_form_submission();
        
        // Получение данных
        $lesson_data = $this->get_lesson_data();
        $course_data = $this->get_course_data();
        $tasks = $this->get_lesson_tasks();
        $navigation = $this->get_lesson_navigation();
        $user_progress = $this->get_user_progress();
        $task_progress = $this->get_task_progress($tasks);
        
        // Определяем статус завершения
        $is_lesson_completed = $user_progress ? $user_progress->is_completed : false;
        
        return [
            'lesson' => $lesson_data,
            'course' => $course_data,
            'tasks' => $tasks,
            'navigation' => $navigation,
            'user_progress' => $user_progress,
            'task_progress' => $task_progress,
            'is_lesson_completed' => $is_lesson_completed,
            'form_result' => $form_result,
            'lesson_id' => $this->lesson_id,
            'lesson_trid' => $this->lesson_trid
        ];
    }
}
