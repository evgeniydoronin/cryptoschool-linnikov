<?php
/**
 * WPML Helper Functions
 *
 * @package CryptoSchool
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Генерирует URL с учетом текущего языка WPML
 *
 * @param string $path Путь относительно домена (например, '/courses/' или '/lesson/?id=123')
 * @return string Полный URL с языковым префиксом
 */
if (!function_exists('cryptoschool_get_localized_url')) {
    function cryptoschool_get_localized_url($path) {
        $current_lang = apply_filters('wpml_current_language', null);
        $default_lang = apply_filters('wpml_default_language', null);
        
        if ($current_lang && $current_lang !== $default_lang) {
            return home_url('/' . $current_lang . $path);
        }
        
        return home_url($path);
    }
}

/**
 * Генерирует локализованный URL для site_url()
 * Заменяет стандартный site_url() с учетом WPML
 *
 * @param string $path Путь относительно домена
 * @return string Локализованный URL
 */
if (!function_exists('cryptoschool_site_url')) {
    function cryptoschool_site_url($path = '') {
        return cryptoschool_get_localized_url($path);
    }
}

/**
 * Проверяет, активен ли WPML
 *
 * @return bool
 */
if (!function_exists('cryptoschool_is_wpml_active')) {
    function cryptoschool_is_wpml_active() {
        return function_exists('apply_filters') && 
               apply_filters('wpml_setting', false, 'setup_complete');
    }
}

/**
 * Получает текущий язык WPML
 *
 * @return string|null Код текущего языка или null если WPML не активен
 */
if (!function_exists('cryptoschool_get_current_language')) {
    function cryptoschool_get_current_language() {
        if (!cryptoschool_is_wpml_active()) {
            return null;
        }
        
        return apply_filters('wpml_current_language', null);
    }
}

/**
 * Получает язык по умолчанию WPML
 *
 * @return string|null Код языка по умолчанию или null если WPML не активен
 */
if (!function_exists('cryptoschool_get_default_language')) {
    function cryptoschool_get_default_language() {
        if (!cryptoschool_is_wpml_active()) {
            return null;
        }
        
        return apply_filters('wpml_default_language', null);
    }
}

/**
 * Генерирует URL курса с учетом языковой версии и перевода ID
 *
 * @param int $course_id ID курса
 * @return string Локализованный URL курса
 */
if (!function_exists('cryptoschool_get_course_url')) {
    function cryptoschool_get_course_url($course_id) {
        // Получаем переведенный ID курса для текущего языка
        $translated_id = apply_filters('wpml_object_id', $course_id, 'cryptoschool_course', true);
        if (!$translated_id) {
            $translated_id = $course_id; // Fallback к оригинальному ID
        }
        
        return cryptoschool_site_url('/course/?id=' . $translated_id);
    }
}

/**
 * Генерирует URL урока с учетом языковой версии и перевода ID
 *
 * @param int $lesson_id ID урока
 * @return string Локализованный URL урока
 */
if (!function_exists('cryptoschool_get_lesson_url')) {
    function cryptoschool_get_lesson_url($lesson_id) {
        // Получаем переведенный ID урока для текущего языка
        $translated_id = apply_filters('wpml_object_id', $lesson_id, 'cryptoschool_lesson', true);
        if (!$translated_id) {
            $translated_id = $lesson_id; // Fallback к оригинальному ID
        }
        
        return cryptoschool_site_url('/lesson/?id=' . $translated_id);
    }
}

/**
 * Получает все языковые версии курса (включая оригинал)
 *
 * @param int $course_id ID курса (любой языковой версии)
 * @return array Массив ID всех языковых версий курса
 */
if (!function_exists('cryptoschool_get_all_course_language_versions')) {
    function cryptoschool_get_all_course_language_versions($course_id) {
        if (!cryptoschool_is_wpml_active()) {
            return [$course_id];
        }
        
        // Получаем trid курса
        global $wpdb;
        $trid = $wpdb->get_var($wpdb->prepare(
            "SELECT trid FROM {$wpdb->prefix}icl_translations 
             WHERE element_id = %d AND element_type = %s",
            $course_id, 'post_cryptoschool_course'
        ));
        
        if (!$trid) {
            return [$course_id];
        }
        
        // Получаем все переводы по trid
        $translations = $wpdb->get_results($wpdb->prepare(
            "SELECT element_id FROM {$wpdb->prefix}icl_translations 
             WHERE trid = %d AND element_type = %s",
            $trid, 'post_cryptoschool_course'
        ));
        
        $course_ids = [];
        foreach ($translations as $translation) {
            $course_ids[] = intval($translation->element_id);
        }
        
        return $course_ids;
    }
}

/**
 * Получает активный урок для пользователя
 *
 * @param int $user_id ID пользователя
 * @return array|null Данные активного урока или null
 */
if (!function_exists('cryptoschool_get_user_active_lesson')) {
    function cryptoschool_get_user_active_lesson($user_id) {
        global $wpdb;
        
        // Получаем пакеты пользователя
        $packages_query = $wpdb->prepare(
            "SELECT p.course_ids 
             FROM {$wpdb->prefix}cryptoschool_user_access ua
             JOIN {$wpdb->prefix}cryptoschool_packages p ON ua.package_id = p.id
             WHERE ua.user_id = %d AND ua.status = 'active'",
            $user_id
        );
        $packages = $wpdb->get_results($packages_query);
        
        if (empty($packages)) {
            return null;
        }
        
        // Собираем все course_ids из пакетов
        $course_ids = [];
        foreach ($packages as $package) {
            $ids = json_decode($package->course_ids, true);
            if (is_array($ids)) {
                $course_ids = array_merge($course_ids, $ids);
            }
        }
        
        if (empty($course_ids)) {
            return null;
        }
        
        // Получаем курсы пользователя через Custom Post Types
        // Сначала пытаемся найти по table_id
        $courses = get_posts([
            'post_type' => 'cryptoschool_course',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query' => [
                [
                    'key' => '_cryptoschool_table_id',
                    'value' => $course_ids,
                    'compare' => 'IN'
                ]
            ],
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ]);
        
        // Если не найдены по table_id, ищем по Post ID
        if (empty($courses)) {
            $courses = get_posts([
                'post_type' => 'cryptoschool_course',
                'post_status' => 'publish',
                'numberposts' => -1,
                'include' => $course_ids,
                'orderby' => 'menu_order',
                'order' => 'ASC'
            ]);
        }
        
        // Ищем первый незавершенный курс
        foreach ($courses as $course) {
            $course_table_id = get_post_meta($course->ID, '_cryptoschool_table_id', true);
            if (!$course_table_id) {
                $course_table_id = $course->ID;
            }
            
            // Получаем уроки курса
            $lessons = get_field('choose_lesson', $course->ID);
            if (!$lessons) {
                continue;
            }
            
            // Находим первый незавершенный урок
            foreach ($lessons as $lesson) {
                $lesson_id = is_object($lesson) ? $lesson->ID : $lesson;
                
                // Получаем trid урока
                $lesson_trid = $wpdb->get_var($wpdb->prepare(
                    "SELECT trid FROM {$wpdb->prefix}icl_translations 
                     WHERE element_id = %d AND element_type = %s",
                    $lesson_id, 'post_cryptoschool_lesson'
                ));
                
                if (!$lesson_trid) {
                    $lesson_trid = $lesson_id;
                }
                
                // Проверяем, завершен ли урок
                $is_completed = $wpdb->get_var($wpdb->prepare(
                    "SELECT is_completed FROM {$wpdb->prefix}cryptoschool_user_lesson_progress 
                     WHERE user_id = %d AND lesson_id = %d",
                    $user_id, $lesson_trid
                ));
                
                if (!$is_completed) {
                    // Нашли активный урок
                    $lesson_post = get_post($lesson_id);
                    return [
                        'lesson_id' => $lesson_id,
                        'lesson_trid' => $lesson_trid,
                        'lesson_title' => $lesson_post->post_title,
                        'course_id' => $course->ID,
                        'course_title' => $course->post_title,
                        'completion_points' => get_post_meta($lesson_id, 'completion_points', true) ?: 5
                    ];
                }
            }
        }
        
        return null;
    }
}

/**
 * Получает завершенные уроки пользователя
 *
 * @param int $user_id ID пользователя
 * @param int $limit Количество уроков для возврата
 * @return array Массив данных завершенных уроков
 */
if (!function_exists('cryptoschool_get_user_completed_lessons')) {
    function cryptoschool_get_user_completed_lessons($user_id, $limit = 5) {
        global $wpdb;
        
        // Получаем завершенные уроки
        $completed_lessons = $wpdb->get_results($wpdb->prepare(
            "SELECT lesson_id, completed_at 
             FROM {$wpdb->prefix}cryptoschool_user_lesson_progress 
             WHERE user_id = %d AND is_completed = 1
             ORDER BY completed_at DESC
             LIMIT %d",
            $user_id, $limit
        ));
        
        $result = [];
        
        foreach ($completed_lessons as $progress) {
            // Получаем урок по trid
            $lesson_id = $wpdb->get_var($wpdb->prepare(
                "SELECT element_id FROM {$wpdb->prefix}icl_translations 
                 WHERE trid = %d AND element_type = %s AND language_code = %s",
                $progress->lesson_id, 'post_cryptoschool_lesson', apply_filters('wpml_current_language', null)
            ));
            
            if (!$lesson_id) {
                // Fallback: используем trid как ID, но проверяем, что это урок
                $lesson_id = $progress->lesson_id;
            }
            
            $lesson_post = get_post($lesson_id);
            if (!$lesson_post || $lesson_post->post_type !== 'cryptoschool_lesson') {
                // Пропускаем, если это не урок или системная страница WordPress
                continue;
            }
            
            // Находим курс этого урока
            $course_id = null;
            $course_title = '';
            
            // Ищем курс через ACF поля
            $courses = get_posts([
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
            
            if (!empty($courses)) {
                $course = $courses[0];
                $course_id = $course->ID;
                $course_title = $course->post_title;
            }
            
            $result[] = [
                'lesson_id' => $lesson_id,
                'lesson_title' => $lesson_post->post_title,
                'course_id' => $course_id,
                'course_title' => $course_title,
                'completed_at' => $progress->completed_at,
                'completion_points' => get_post_meta($lesson_id, 'completion_points', true) ?: 5
            ];
        }
        
        return $result;
    }
}
