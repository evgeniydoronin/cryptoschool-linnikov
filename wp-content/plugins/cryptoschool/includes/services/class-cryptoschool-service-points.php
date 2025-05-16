<?php
/**
 * Сервис для работы с системой начисления баллов
 *
 * @package CryptoSchool
 * @subpackage Services
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс сервиса для работы с системой начисления баллов
 */
class CryptoSchool_Service_Points extends CryptoSchool_Service {
    /**
     * Репозиторий истории баллов
     *
     * @var CryptoSchool_Repository_Points_History
     */
    protected $points_repository;

    /**
     * Репозиторий серий пользователей
     *
     * @var CryptoSchool_Repository_User_Streak
     */
    protected $streak_repository;

    /**
     * Репозиторий уроков
     *
     * @var CryptoSchool_Repository_Lesson
     */
    protected $lesson_repository;

    /**
     * Репозиторий рейтинга пользователей
     *
     * @var CryptoSchool_Repository_User_Leaderboard
     */
    protected $leaderboard_repository;

    /**
     * Конструктор
     *
     * @param CryptoSchool_Loader $loader Экземпляр загрузчика
     */
    public function __construct(CryptoSchool_Loader $loader) {
        parent::__construct($loader);
        $this->points_repository = new CryptoSchool_Repository_Points_History();
        $this->streak_repository = new CryptoSchool_Repository_User_Streak();
        $this->lesson_repository = new CryptoSchool_Repository_Lesson();
        
        // Проверяем существование класса репозитория рейтинга
        if (class_exists('CryptoSchool_Repository_User_Leaderboard')) {
            $this->leaderboard_repository = new CryptoSchool_Repository_User_Leaderboard();
        }
    }

    /**
     * Регистрация хуков и фильтров
     *
     * @return void
     */
    protected function register_hooks() {
        // Хук для начисления баллов при завершении урока
        $this->add_action('cryptoschool_lesson_completed', 'process_lesson_completion', 10, 2);
        
        // Хук для ежедневного обновления серий пользователей
        $this->add_action('cryptoschool_daily_cron', 'process_daily_streaks');
        
        // AJAX-обработчики
        $this->add_action('wp_ajax_cryptoschool_get_user_points', 'ajax_get_user_points');
        $this->add_action('wp_ajax_cryptoschool_get_user_streak', 'ajax_get_user_streak');
        $this->add_action('wp_ajax_cryptoschool_get_points_history', 'ajax_get_points_history');
        
        // Шорткоды
        $this->add_shortcode('cryptoschool_user_points', 'shortcode_user_points');
        $this->add_shortcode('cryptoschool_user_streak', 'shortcode_user_streak');
        $this->add_shortcode('cryptoschool_points_history', 'shortcode_points_history');
    }

    /**
     * Обработка завершения урока
     *
     * @param int $user_id   ID пользователя
     * @param int $lesson_id ID урока
     * @return void
     */
    public function process_lesson_completion($user_id, $lesson_id) {
        // Получение урока
        $lesson = $this->lesson_repository->find($lesson_id);
        if (!$lesson) {
            $this->log_error('Урок не найден', ['lesson_id' => $lesson_id]);
            return;
        }
        
        // Обработка серии и мульти-уроков
        $this->process_streak_and_multi_lessons($user_id, $lesson_id);
        
        // Проверка завершения курса и начисление бонуса
        $this->check_course_completion($user_id, $lesson->course_id);
        
        // Обновление рейтинга пользователя
        $this->update_user_leaderboard($user_id);
    }
    
    /**
     * Проверка завершения курса и начисление бонуса
     *
     * @param int $user_id   ID пользователя
     * @param int $course_id ID курса
     * @return void
     */
    protected function check_course_completion($user_id, $course_id) {
        // Проверяем, существует ли класс репозитория курсов
        if (!class_exists('CryptoSchool_Repository_Course')) {
            $this->log_error('Класс CryptoSchool_Repository_Course не существует');
            return;
        }
        
        // Получение курса
        $course_repository = new CryptoSchool_Repository_Course();
        $course = $course_repository->find($course_id);
        if (!$course) {
            $this->log_error('Курс не найден', ['course_id' => $course_id]);
            return;
        }
        
        // Получение всех уроков курса
        $lesson_repository = new CryptoSchool_Repository_Lesson();
        $lessons = $lesson_repository->get_course_lessons($course_id);
        if (empty($lessons)) {
            $this->log_error('Уроки курса не найдены', ['course_id' => $course_id]);
            return;
        }
        
        // Получение прогресса пользователя по урокам курса
        $progress_repository = new CryptoSchool_Repository_User_Lesson_Progress();
        $completed_lessons = 0;
        
        foreach ($lessons as $lesson) {
            $progress = $progress_repository->get_user_lesson_progress($user_id, $lesson->id);
            if ($progress && $progress->is_completed) {
                $completed_lessons++;
            }
        }
        
        // Проверка, все ли уроки курса завершены
        if ($completed_lessons === count($lessons)) {
            // Проверка, был ли уже начислен бонус за завершение курса
            $points_history = $this->points_repository->get_user_points_history($user_id, [
                'points_type' => 'course_completion',
            ]);
            
            $already_awarded = false;
            foreach ($points_history as $history) {
                if ($history->description && strpos($history->description, $course->title) !== false) {
                    $already_awarded = true;
                    break;
                }
            }
            
            // Если бонус еще не был начислен, начисляем его
            if (!$already_awarded) {
                $course_completion_points = 50; // Бонус за завершение курса
                $description = sprintf('Бонус за завершение курса "%s"', $course->title);
                
                $this->points_repository->add_course_completion_points(
                    $user_id,
                    $course_completion_points,
                    $description
                );
                
                $this->log_info('Начислен бонус за завершение курса', [
                    'user_id' => $user_id,
                    'course_id' => $course_id,
                    'points' => $course_completion_points
                ]);
            }
        }
    }

    /**
     * Обработка серии и мульти-уроков
     *
     * @param int $user_id   ID пользователя
     * @param int $lesson_id ID урока
     * @return void
     */
    protected function process_streak_and_multi_lessons($user_id, $lesson_id) {
        $today = current_time('Y-m-d');
        
        // Получение или создание записи о серии пользователя
        $streak = $this->streak_repository->get_by_user_id($user_id);
        if (!$streak) {
            // Создание новой записи о серии
            $streak_data = [
                'user_id' => $user_id,
                'current_streak' => 0,
                'max_streak' => 0,
                'last_activity_date' => $today,
                'lessons_today' => 0,
            ];
            
            $streak_id = $this->streak_repository->create_or_update($user_id, $streak_data);
            if (!$streak_id) {
                $this->log_error('Не удалось создать запись о серии', ['user_id' => $user_id]);
                return;
            }
            
            $streak = $this->streak_repository->get_by_user_id($user_id);
        }
        
        // Проверка, является ли это первым уроком за день
        $last_activity_date = $streak->last_activity_date;
        $lessons_today = $streak->lessons_today;
        
        if ($last_activity_date !== $today) {
            // Новый день
            
            // Проверка, был ли пропущен день
            $yesterday = date('Y-m-d', strtotime('-1 day', strtotime($today)));
            
            if ($last_activity_date === $yesterday) {
                // Последовательный день, увеличиваем серию
                $this->streak_repository->increment_streak($user_id);
                
                // Получаем обновленную серию
                $streak = $this->streak_repository->get_by_user_id($user_id);
                
                // Начисление баллов за серию (начиная со второго дня)
                if ($streak->current_streak >= 2) {
                    $streak_points = $streak->get_streak_points();
                    if ($streak_points > 0) {
                        $description = sprintf(
                            'Бонус за %d день непрерывной серии',
                            $streak->current_streak
                        );
                        
                        $this->points_repository->add_streak_points(
                            $user_id,
                            $streak_points,
                            $streak->current_streak,
                            $description
                        );
                    }
                }
            } else {
                // День был пропущен, сбрасываем серию
                $this->streak_repository->reset_streak($user_id);
            }
            
            // Сбрасываем счетчик уроков за день
            $this->streak_repository->reset_lessons_today($user_id);
            $lessons_today = 0;
        }
        
        // Увеличиваем счетчик уроков за день
        $this->streak_repository->increment_lessons_today($user_id);
        $lessons_today++;
        
        // Начисление баллов за прохождение нескольких уроков в день
        $this->process_multi_lesson_points($user_id, $lesson_id, $lessons_today);
    }

    /**
     * Обработка баллов за прохождение нескольких уроков в день
     *
     * @param int $user_id       ID пользователя
     * @param int $lesson_id     ID урока
     * @param int $lessons_today Количество уроков, пройденных сегодня
     * @return void
     */
    protected function process_multi_lesson_points($user_id, $lesson_id, $lessons_today) {
        // Получение серии пользователя
        $streak = $this->streak_repository->get_by_user_id($user_id);
        if (!$streak) {
            return;
        }
        
        // Начисление баллов за прохождение нескольких уроков в день только если это не первый день серии
        // и это второй или последующий урок за день
        $multi_lesson_points = 0;
        
        if ($streak->current_streak > 1 && $lessons_today >= 2) {
            $multi_lesson_points = 5;
        }
        
        if ($multi_lesson_points > 0) {
            $description = sprintf(
                'Бонус за прохождение %d урока за день',
                $lessons_today
            );
            
            $this->points_repository->add_multi_lesson_points(
                $user_id,
                $lesson_id,
                $multi_lesson_points,
                $lessons_today,
                $description
            );
        }
    }

    /**
     * Обработка ежедневных серий пользователей
     *
     * @return void
     */
    public function process_daily_streaks() {
        global $wpdb;
        
        $today = current_time('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day', strtotime($today)));
        
        // Получение всех пользователей с активными сериями
        $streak_table = $wpdb->prefix . 'cryptoschool_user_streak';
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$streak_table} WHERE current_streak > 0 AND last_activity_date < %s",
            $today
        );
        
        $streaks = $wpdb->get_results($query);
        
        foreach ($streaks as $streak) {
            $user_id = $streak->user_id;
            $last_activity_date = $streak->last_activity_date;
            
            // Если последняя активность была вчера, ничего не делаем
            // Серия будет обновлена при следующем прохождении урока
            if ($last_activity_date === $yesterday) {
                continue;
            }
            
            // Если последняя активность была раньше вчерашнего дня, сбрасываем серию
            $this->streak_repository->reset_streak($user_id);
        }
    }

    /**
     * Добавление баллов за прохождение урока
     *
     * @param int    $user_id    ID пользователя
     * @param int    $lesson_id  ID урока
     * @param int    $points     Количество баллов
     * @param string $description Описание начисления
     * @return bool
     */
    public function add_lesson_points($user_id, $lesson_id, $points, $description = '') {
        // Получение урока для формирования описания
        $lesson = $this->lesson_repository->find($lesson_id);
        
        if (!$description && $lesson) {
            $description = sprintf(
                'Завершение урока "%s"',
                $lesson->title
            );
        }
        
        return $this->points_repository->add_lesson_points($user_id, $lesson_id, $points, $description);
    }

    /**
     * Добавление баллов за завершение курса
     *
     * @param int    $user_id    ID пользователя
     * @param int    $course_id  ID курса
     * @param int    $points     Количество баллов
     * @param string $description Описание начисления
     * @return bool
     */
    public function add_course_completion_points($user_id, $course_id, $points, $description = '') {
        // Получение курса для формирования описания
        $course_repository = new CryptoSchool_Repository_Course();
        $course = $course_repository->find($course_id);
        
        if (!$description && $course) {
            $description = sprintf(
                'Завершение курса "%s"',
                $course->title
            );
        }
        
        return $this->points_repository->add_course_completion_points($user_id, $points, $description);
    }

    /**
     * Обновление рейтинга пользователя
     *
     * @param int $user_id ID пользователя
     * @return bool
     */
    public function update_user_leaderboard($user_id) {
        // Проверка наличия репозитория рейтинга
        if (!$this->leaderboard_repository) {
            return false;
        }
        
        // Получение общего количества баллов пользователя
        $total_points = $this->points_repository->get_user_total_points($user_id);
        
        // Получение количества завершенных уроков
        $progress_repository = new CryptoSchool_Repository_User_Lesson_Progress();
        $completed_lessons = $progress_repository->count([
            'user_id' => $user_id,
            'is_completed' => 1
        ]);
        
        // Получение количества дней на проекте
        $user_data = get_userdata($user_id);
        $days_active = floor((time() - strtotime($user_data->user_registered)) / (60 * 60 * 24));
        
        // Обновление рейтинга
        $leaderboard_data = [
            'user_id' => $user_id,
            'total_points' => $total_points,
            'completed_lessons' => $completed_lessons,
            'days_active' => $days_active,
            'last_updated' => current_time('mysql')
        ];
        
        // Проверка существования записи
        $existing = $this->leaderboard_repository->get_by_user_id($user_id);
        
        if ($existing) {
            return $this->leaderboard_repository->update($existing->id, $leaderboard_data);
        } else {
            return $this->leaderboard_repository->create($leaderboard_data) !== false;
        }
    }

    /**
     * Получение общего количества баллов пользователя
     *
     * @param int $user_id ID пользователя
     * @return int
     */
    public function get_user_total_points($user_id) {
        return $this->points_repository->get_user_total_points($user_id);
    }

    /**
     * Получение серии пользователя
     *
     * @param int $user_id ID пользователя
     * @return CryptoSchool_Model_User_Streak|null
     */
    public function get_user_streak($user_id) {
        return $this->streak_repository->get_by_user_id($user_id);
    }

    /**
     * Получение истории баллов пользователя
     *
     * @param int   $user_id ID пользователя
     * @param array $args    Дополнительные аргументы
     * @return array
     */
    public function get_user_points_history($user_id, $args = []) {
        return $this->points_repository->get_user_points_history($user_id, $args);
    }

    /**
     * AJAX-обработчик для получения баллов пользователя
     *
     * @return void
     */
    public function ajax_get_user_points() {
        // Проверка авторизации
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Вы должны быть авторизованы для выполнения этого действия.', 'cryptoschool'));
        }
        
        // Получение ID пользователя
        $user_id = get_current_user_id();
        
        // Получение общего количества баллов
        $total_points = $this->get_user_total_points($user_id);
        
        // Подготовка данных для ответа
        $data = [
            'total_points' => $total_points
        ];
        
        wp_send_json_success($data);
    }

    /**
     * AJAX-обработчик для получения серии пользователя
     *
     * @return void
     */
    public function ajax_get_user_streak() {
        // Проверка авторизации
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Вы должны быть авторизованы для выполнения этого действия.', 'cryptoschool'));
        }
        
        // Получение ID пользователя
        $user_id = get_current_user_id();
        
        // Получение серии пользователя
        $streak = $this->get_user_streak($user_id);
        
        if (!$streak) {
            wp_send_json_error(__('Серия не найдена.', 'cryptoschool'));
        }
        
        // Подготовка данных для ответа
        $data = [
            'current_streak' => $streak->current_streak,
            'max_streak' => $streak->max_streak,
            'last_activity_date' => $streak->last_activity_date,
            'lessons_today' => $streak->lessons_today
        ];
        
        wp_send_json_success($data);
    }

    /**
     * AJAX-обработчик для получения истории баллов
     *
     * @return void
     */
    public function ajax_get_points_history() {
        // Проверка авторизации
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Вы должны быть авторизованы для выполнения этого действия.', 'cryptoschool'));
        }
        
        // Получение ID пользователя
        $user_id = get_current_user_id();
        
        // Получение параметров
        $limit = isset($_POST['limit']) ? (int) $_POST['limit'] : 10;
        $offset = isset($_POST['offset']) ? (int) $_POST['offset'] : 0;
        $points_type = isset($_POST['points_type']) ? sanitize_text_field($_POST['points_type']) : '';
        
        // Получение истории баллов
        $args = [
            'limit' => $limit,
            'offset' => $offset,
            'points_type' => $points_type
        ];
        
        $history = $this->get_user_points_history($user_id, $args);
        
        // Подготовка данных для ответа
        $data = [];
        foreach ($history as $item) {
            $data[] = [
                'id' => $item->id,
                'points' => $item->points,
                'points_type' => $item->points_type,
                'description' => $item->description,
                'created_at' => $item->created_at
            ];
        }
        
        wp_send_json_success($data);
    }

    /**
     * Шорткод для отображения баллов пользователя
     *
     * @param array $atts Атрибуты шорткода
     * @return string
     */
    public function shortcode_user_points($atts) {
        $atts = shortcode_atts([
            'user_id' => 0,
            'template' => 'default',
        ], $atts, 'cryptoschool_user_points');
        
        // Получение ID пользователя
        $user_id = (int) $atts['user_id'];
        if ($user_id <= 0) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return '';
        }
        
        // Получение общего количества баллов
        $total_points = $this->get_user_total_points($user_id);
        
        // Подключение шаблона
        $template = sanitize_text_field($atts['template']);
        $template_path = plugin_dir_path(dirname(dirname(__FILE__))) . 'public/views/shortcodes/user-points-' . $template . '.php';
        
        if (!file_exists($template_path)) {
            $template_path = plugin_dir_path(dirname(dirname(__FILE__))) . 'public/views/shortcodes/user-points-default.php';
        }
        
        ob_start();
        include $template_path;
        return ob_get_clean();
    }

    /**
     * Шорткод для отображения серии пользователя
     *
     * @param array $atts Атрибуты шорткода
     * @return string
     */
    public function shortcode_user_streak($atts) {
        $atts = shortcode_atts([
            'user_id' => 0,
            'template' => 'default',
        ], $atts, 'cryptoschool_user_streak');
        
        // Получение ID пользователя
        $user_id = (int) $atts['user_id'];
        if ($user_id <= 0) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return '';
        }
        
        // Получение серии пользователя
        $streak = $this->get_user_streak($user_id);
        
        if (!$streak) {
            return '';
        }
        
        // Подключение шаблона
        $template = sanitize_text_field($atts['template']);
        $template_path = plugin_dir_path(dirname(dirname(__FILE__))) . 'public/views/shortcodes/user-streak-' . $template . '.php';
        
        if (!file_exists($template_path)) {
            $template_path = plugin_dir_path(dirname(dirname(__FILE__))) . 'public/views/shortcodes/user-streak-default.php';
        }
        
        ob_start();
        include $template_path;
        return ob_get_clean();
    }

    /**
     * Шорткод для отображения истории баллов
     *
     * @param array $atts Атрибуты шорткода
     * @return string
     */
    public function shortcode_points_history($atts) {
        $atts = shortcode_atts([
            'user_id' => 0,
            'limit' => 10,
            'points_type' => '',
            'template' => 'default',
        ], $atts, 'cryptoschool_points_history');
        
        // Получение ID пользователя
        $user_id = (int) $atts['user_id'];
        if ($user_id <= 0) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return '';
        }
        
        // Получение истории баллов
        $args = [
            'limit' => (int) $atts['limit'],
            'points_type' => sanitize_text_field($atts['points_type'])
        ];
        
        $history = $this->get_user_points_history($user_id, $args);
        
        // Подключение шаблона
        $template = sanitize_text_field($atts['template']);
        $template_path = plugin_dir_path(dirname(dirname(__FILE__))) . 'public/views/shortcodes/points-history-' . $template . '.php';
        
        if (!file_exists($template_path)) {
            $template_path = plugin_dir_path(dirname(dirname(__FILE__))) . 'public/views/shortcodes/points-history-default.php';
        }
        
        ob_start();
        include $template_path;
        return ob_get_clean();
    }
}
