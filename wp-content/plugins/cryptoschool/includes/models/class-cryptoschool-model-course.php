<?php
/**
 * Модель курса
 *
 * @package CryptoSchool
 * @subpackage Models
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс модели курса
 */
class CryptoSchool_Model_Course extends CryptoSchool_Model {
    /**
     * Заполняемые атрибуты
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'description',
        'thumbnail',
        'difficulty_level',
        'slug',
        'course_order',
        'is_active',
        'completion_points',
        'featured',
    ];

    /**
     * Получение уроков курса
     *
     * @return array
     */
    public function get_lessons() {
        $repository = new CryptoSchool_Repository_Lesson();
        return $repository->get_course_lessons($this->getAttribute('id'));
    }

    /**
     * Получение количества уроков курса
     *
     * @return int
     */
    public function get_lessons_count() {
        global $wpdb;
        $lessons_table = $wpdb->prefix . 'cryptoschool_lessons';

        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$lessons_table} WHERE course_id = %d",
            $this->getAttribute('id')
        );

        return (int) $wpdb->get_var($query);
    }

    /**
     * Получение общего времени прохождения курса (в минутах)
     *
     * @return int
     */
    public function get_total_duration() {
        global $wpdb;
        $lessons_table = $wpdb->prefix . 'cryptoschool_lessons';

        $query = $wpdb->prepare(
            "SELECT SUM(duration) FROM {$lessons_table} WHERE course_id = %d",
            $this->getAttribute('id')
        );

        return (int) $wpdb->get_var($query);
    }

    /**
     * Проверка, доступен ли курс для пользователя
     *
     * @param int $user_id ID пользователя
     * @return bool
     */
    public function is_available_for_user($user_id) {
        // Если пользователь администратор, всегда даем доступ
        if (user_can($user_id, 'administrator')) {
            return true;
        }
        
        $repository = new CryptoSchool_Repository_UserAccess();
        $access = $repository->get_user_course_access($user_id, $this->getAttribute('id'));
        
        // Проверяем не только наличие доступа, но и его статус
        if (empty($access)) {
            return false;
        }
        
        // Проверяем статус доступа
        return $access->getAttribute('status') === 'active';
    }

    /**
     * Получение прогресса пользователя по курсу
     *
     * @param int $user_id ID пользователя
     * @return float Процент прохождения курса (от 0 до 100)
     */
    public function get_user_progress($user_id) {
        global $wpdb;
        $lessons_table = $wpdb->prefix . 'cryptoschool_lessons';
        $progress_table = $wpdb->prefix . 'cryptoschool_user_progress';

        // Получение общего количества уроков в курсе
        $total_lessons_query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$lessons_table} WHERE course_id = %d AND is_active = 1",
            $this->getAttribute('id')
        );
        $total_lessons = (int) $wpdb->get_var($total_lessons_query);

        if ($total_lessons === 0) {
            return 0;
        }

        // Получение количества пройденных уроков
        $completed_lessons_query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$progress_table} p
            INNER JOIN {$lessons_table} l ON p.lesson_id = l.id
            WHERE p.user_id = %d AND p.status = 'completed'
            AND l.course_id = %d AND l.is_active = 1",
            $user_id,
            $this->getAttribute('id')
        );
        $completed_lessons = (int) $wpdb->get_var($completed_lessons_query);

        // Расчет процента прохождения
        return ($completed_lessons / $total_lessons) * 100;
    }

    /**
     * Получение баллов пользователя за курс
     *
     * @param int $user_id ID пользователя
     * @return int
     */
    public function get_user_points($user_id) {
        global $wpdb;
        $lessons_table = $wpdb->prefix . 'cryptoschool_lessons';
        $progress_table = $wpdb->prefix . 'cryptoschool_user_progress';

        $query = $wpdb->prepare(
            "SELECT SUM(p.points) FROM {$progress_table} p
            INNER JOIN {$lessons_table} l ON p.lesson_id = l.id
            WHERE p.user_id = %d AND l.course_id = %d",
            $user_id,
            $this->getAttribute('id')
        );

        return (int) $wpdb->get_var($query);
    }

    /**
     * Получение URL курса
     *
     * @return string
     */
    public function get_url() {
        return home_url('/course/' . $this->getAttribute('slug'));
    }

    /**
     * Получение URL миниатюры курса
     *
     * @param string $size Размер миниатюры (thumbnail, medium, large, full)
     * @return string
     */
    public function get_thumbnail_url($size = 'thumbnail') {
        $thumbnail = $this->getAttribute('thumbnail');
        
        if (empty($thumbnail)) {
            return CRYPTOSCHOOL_PLUGIN_URL . 'assets/images/default-course-thumbnail.jpg';
        }

        $attachment_id = attachment_url_to_postid($thumbnail);
        if ($attachment_id) {
            $image = wp_get_attachment_image_src($attachment_id, $size);
            if ($image) {
                return $image[0];
            }
        }

        return $thumbnail;
    }

    /**
     * Получение уровня сложности курса
     *
     * @return string
     */
    public function get_difficulty_level_label() {
        $levels = [
            'beginner' => __('Начальный', 'cryptoschool'),
            'intermediate' => __('Средний', 'cryptoschool'),
            'advanced' => __('Продвинутый', 'cryptoschool'),
            'expert' => __('Экспертный', 'cryptoschool'),
        ];

        $difficulty_level = $this->getAttribute('difficulty_level');
        return isset($levels[$difficulty_level]) ? $levels[$difficulty_level] : $difficulty_level;
    }

    /**
     * Получение статуса курса
     *
     * @return string
     */
    public function get_status_label() {
        return $this->getAttribute('is_active') ? __('Активен', 'cryptoschool') : __('Неактивен', 'cryptoschool');
    }

    /**
     * Получение форматированной даты создания курса
     *
     * @param string $format Формат даты
     * @return string|null
     */
    public function get_created_at($format = 'd.m.Y') {
        // Отладочный вывод
        error_log('Course get_created_at - Attributes: ' . json_encode($this->attributes));
        
        if (empty($this->getAttribute('created_at'))) {
            error_log('Course get_created_at - created_at is empty');
            return null;
        }
        
        $created_at = $this->getAttribute('created_at');
        error_log('Course get_created_at - created_at value: ' . $created_at);
        
        return date_i18n($format, strtotime($created_at));
    }

    /**
     * Получение форматированной даты обновления курса
     *
     * @param string $format Формат даты
     * @return string|null
     */
    public function get_updated_at($format = 'd.m.Y') {
        // Отладочный вывод
        error_log('Course get_updated_at - Attributes: ' . json_encode($this->attributes));
        
        if (empty($this->getAttribute('updated_at'))) {
            error_log('Course get_updated_at - updated_at is empty');
            return null;
        }
        
        $updated_at = $this->getAttribute('updated_at');
        error_log('Course get_updated_at - updated_at value: ' . $updated_at);
        
        return date_i18n($format, strtotime($updated_at));
    }
}
