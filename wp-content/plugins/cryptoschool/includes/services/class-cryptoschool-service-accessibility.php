<?php
/**
 * Сервис доступности курсов и уроков
 *
 * @package CryptoSchool
 * @subpackage Services
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс сервиса доступности курсов и уроков
 */
class CryptoSchool_Service_Accessibility extends CryptoSchool_Service {
    /**
     * Регистрация хуков и фильтров
     *
     * @return void
     */
    protected function register_hooks() {
        // Пока не регистрируем никаких хуков, так как сервис используется напрямую
    }
    
    /**
     * Проверка доступности курса для пользователя
     * 
     * @param int $user_id ID пользователя
     * @param int $course_id ID курса (может быть Post ID или Table ID)
     * @return array Результат проверки: ['accessible' => bool, 'redirect_url' => string|null]
     */
    public function check_course_accessibility($user_id, $course_id) {
        // Если пользователь администратор, всегда даем доступ
        if (user_can($user_id, 'administrator')) {
            return ['accessible' => true, 'redirect_url' => null];
        }
        
        global $wpdb;
        
        // Получаем все языковые версии курса для проверки доступа
        $all_course_versions = cryptoschool_get_all_course_language_versions($course_id);
        
        // Проверяем доступ для каждой версии курса
        foreach ($all_course_versions as $version_id) {
            // Получаем table_id для версии курса
            $table_id = get_post_meta($version_id, '_cryptoschool_table_id', true);
            if (!$table_id) {
                $table_id = $version_id; // Fallback к Post ID
            }
            
            // Проверяем, доступен ли курс для пользователя
            $access_query = "
                SELECT COUNT(*) as has_access
                FROM {$wpdb->prefix}cryptoschool_user_access ua
                JOIN {$wpdb->prefix}cryptoschool_packages p ON ua.package_id = p.id
                WHERE ua.user_id = %d 
                AND ua.status = 'active'
                AND JSON_CONTAINS(p.course_ids, %s)
            ";
            $has_access = $wpdb->get_var($wpdb->prepare($access_query, $user_id, '"' . $table_id . '"'));
            
            if ($has_access) {
                // Если найден доступ к любой версии курса, предоставляем доступ
                return ['accessible' => true, 'redirect_url' => null];
            }
        }
        
        // Если доступ не найден ни к одной версии курса
        return [
            'accessible' => false, 
            'redirect_url' => site_url('/courses/'),
            'reason' => 'no_access'
        ];
    }
    
    /**
     * Проверка доступности урока для пользователя
     * 
     * @param int $user_id ID пользователя
     * @param int $lesson_id ID урока (Post ID)
     * @return array Результат проверки: ['accessible' => bool, 'redirect_url' => string|null]
     */
    public function check_lesson_accessibility($user_id, $lesson_id) {
        // Если пользователь администратор, всегда даем доступ
        if (user_can($user_id, 'administrator')) {
            return ['accessible' => true, 'redirect_url' => null];
        }
        
        // Получаем урок из новой архитектуры Custom Post Types
        $lesson_post = get_post($lesson_id);
        
        if (!$lesson_post || $lesson_post->post_type !== 'cryptoschool_lesson' || $lesson_post->post_status !== 'publish') {
            return [
                'accessible' => false, 
                'redirect_url' => site_url('/courses/'),
                'reason' => 'lesson_not_found'
            ];
        }
        
        // Получаем курс, к которому относится урок, через ACF поля
        $course_posts = get_posts([
            'post_type' => 'cryptoschool_course',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query' => [
                [
                    'key' => 'choose_lesson',
                    'value' => '"' . $lesson_id . '"',
                    'compare' => 'LIKE'
                ]
            ]
        ]);
        
        if (empty($course_posts)) {
            return [
                'accessible' => false, 
                'redirect_url' => site_url('/courses/'),
                'reason' => 'course_not_found_for_lesson'
            ];
        }
        
        $course_post = $course_posts[0];
        $course_table_id = get_post_meta($course_post->ID, '_cryptoschool_table_id', true);
        if (!$course_table_id) {
            $course_table_id = $course_post->ID;
        }
        
        // Проверяем доступность курса
        $course_accessibility = $this->check_course_accessibility($user_id, $course_table_id);
        
        if (!$course_accessibility['accessible']) {
            return $course_accessibility;
        }
        
        // Получаем все уроки курса из ACF поля choose_lesson
        $course_lesson_data = get_field('choose_lesson', $course_post->ID);
        
        if (empty($course_lesson_data)) {
            return ['accessible' => true, 'redirect_url' => null];
        }
        
        // Преобразуем в массив ID уроков
        $course_lesson_ids = [];
        if (is_array($course_lesson_data)) {
            foreach ($course_lesson_data as $item) {
                if (is_object($item) && isset($item->ID)) {
                    $course_lesson_ids[] = intval($item->ID);
                } elseif (is_numeric($item)) {
                    $course_lesson_ids[] = intval($item);
                }
            }
        }
        
        // Находим индекс текущего урока в списке
        $current_lesson_index = array_search($lesson_id, $course_lesson_ids);
        
        if ($current_lesson_index === false) {
            return [
                'accessible' => false, 
                'redirect_url' => site_url('/courses/'),
                'reason' => 'lesson_not_in_course'
            ];
        }
        
        // Первый урок всегда доступен
        if ($current_lesson_index === 0) {
            return ['accessible' => true, 'redirect_url' => null];
        }
        
        // Для остальных уроков проверяем, завершен ли предыдущий урок
        $prev_lesson_id = $course_lesson_ids[$current_lesson_index - 1];
        
        // Получаем trid предыдущего урока для единого прогресса независимо от языка
        global $wpdb;
        $prev_lesson_trid = $wpdb->get_var($wpdb->prepare(
            "SELECT trid FROM {$wpdb->prefix}icl_translations 
             WHERE element_id = %d AND element_type = %s",
            $prev_lesson_id, 'post_cryptoschool_lesson'
        ));
        
        // Если trid не найден (WPML не активен или урок не переведен), используем lesson_id как fallback
        if (!$prev_lesson_trid) {
            $prev_lesson_trid = $prev_lesson_id;
        }
        
        // Проверяем прогресс предыдущего урока в новой системе, используя trid для единого прогресса
        $prev_lesson_progress_query = "
            SELECT is_completed
            FROM {$wpdb->prefix}cryptoschool_user_lesson_progress 
            WHERE user_id = %d AND lesson_id = %d
        ";
        $prev_lesson_completed = $wpdb->get_var($wpdb->prepare($prev_lesson_progress_query, $user_id, $prev_lesson_trid));
        
        // Если предыдущий урок не завершен, перенаправляем на него
        if (!$prev_lesson_completed) {
            return [
                'accessible' => false, 
                'redirect_url' => cryptoschool_get_lesson_url($prev_lesson_id),
                'reason' => 'previous_lesson_not_completed'
            ];
        }
        
        return ['accessible' => true, 'redirect_url' => null];
    }
}
