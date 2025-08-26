<?php
/**
 * Template Name: Курс
 *
 * @package CryptoSchool
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

// Если пользователь не авторизован, перенаправляем на страницу входа
if (!is_user_logged_in()) {
    // Функция для генерации URL с учетом текущего языка WPML
    $current_lang = apply_filters('wpml_current_language', null);
    $default_lang = apply_filters('wpml_default_language', null);
    $sign_in_url = ($current_lang && $current_lang !== $default_lang) 
        ? home_url('/' . $current_lang . '/sign-in/') 
        : home_url('/sign-in/');
    wp_redirect($sign_in_url);
    exit;
}

// Вспомогательные функции для работы с курсами (если еще не определены)
if (!function_exists('cryptoschool_get_course_progress')) {
    function cryptoschool_get_course_progress($user_id, $course_id) {
        global $wpdb;
        
        $progress_query = "
            SELECT COALESCE(ROUND(
                SUM(CASE WHEN ulp.is_completed = 1 THEN 1 ELSE NULL END) * 100.0 / COUNT(*)
            ), 0) as progress
            FROM {$wpdb->prefix}cryptoschool_lessons l
            LEFT JOIN {$wpdb->prefix}cryptoschool_user_lesson_progress ulp 
                ON l.id = ulp.lesson_id AND ulp.user_id = %d
            WHERE l.course_id = %d AND l.is_active = 1
        ";
        
        $result = $wpdb->get_var($wpdb->prepare($progress_query, $user_id, $course_id));
        return floatval($result);
    }
}

if (!function_exists('cryptoschool_check_course_access')) {
    function cryptoschool_check_course_access($user_id, $course_id) {
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
            
            $access_query = "
                SELECT COUNT(*) as has_access
                FROM {$wpdb->prefix}cryptoschool_user_access ua
                JOIN {$wpdb->prefix}cryptoschool_packages p ON ua.package_id = p.id
                WHERE ua.user_id = %d 
                AND ua.status = 'active'
                AND JSON_CONTAINS(p.course_ids, %s)
            ";
            
            $result = $wpdb->get_var($wpdb->prepare($access_query, $user_id, '"' . $table_id . '"'));
            if (intval($result) > 0) {
                // Если найден доступ к любой версии курса, предоставляем доступ
                return true;
            }
        }
        
        // Если доступ не найден ни к одной версии курса
        return false;
    }
}

// Получаем ID курса из GET-параметра
$course_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Получаем текущего пользователя
$current_user_id = get_current_user_id();

// Проверяем доступность курса для пользователя
$has_access = cryptoschool_check_course_access($current_user_id, $course_id);

// Если курс недоступен, перенаправляем на страницу курсов
if (!$has_access) {
    wp_redirect(cryptoschool_get_localized_url('/courses/'));
    exit;
}

// Получаем данные курса через Custom Post Types
$course_post = null;

// Сначала пытаемся найти курс по переданному ID как по Post ID
$course_post = get_post($course_id);
if ($course_post && $course_post->post_type === 'cryptoschool_course' && $course_post->post_status === 'publish') {
    // Курс найден по Post ID
} else {
    // Если не найден по Post ID, ищем по _cryptoschool_table_id
    $course_posts = get_posts([
        'post_type' => 'cryptoschool_course',
        'post_status' => 'publish',
        'numberposts' => 1,
        'meta_query' => [
            [
                'key' => '_cryptoschool_table_id',
                'value' => $course_id,
                'compare' => '='
            ]
        ]
    ]);
    
    if (!empty($course_posts)) {
        $course_post = $course_posts[0];
    } else {
        // Курс не найден ни по одному из способов
        wp_redirect(cryptoschool_get_localized_url('/courses/'));
        exit;
    }
}

get_header();

// Получаем уроки курса через ACF поле choose_lesson
if (!function_exists('cryptoschool_get_course_lessons')) {
    /**
     * Получает уроки курса через ACF поле choose_lesson
     *
     * @param int $course_id ID курса (Post ID)
     * @return array Массив объектов WP_Post уроков
     */
    function cryptoschool_get_course_lessons($course_id) {
        // Получаем связанные уроки через ACF поле choose_lesson
        $lesson_data = get_field('choose_lesson', $course_id);
        
        if (empty($lesson_data)) {
            return [];
        }
        
        // Преобразуем в массив ID, если получили объекты или смешанные данные
        $lesson_ids = [];
        if (is_array($lesson_data)) {
            foreach ($lesson_data as $item) {
                if (is_object($item) && isset($item->ID)) {
                    // Если это объект WP_Post
                    $lesson_ids[] = intval($item->ID);
                } elseif (is_numeric($item)) {
                    // Если это уже ID
                    $lesson_ids[] = intval($item);
                } elseif (is_string($item) && is_numeric($item)) {
                    // Если это строковый ID
                    $lesson_ids[] = intval($item);
                }
            }
        } elseif (is_numeric($lesson_data)) {
            // Если получили одиночный ID
            $lesson_ids[] = intval($lesson_data);
        }
        
        if (empty($lesson_ids)) {
            return [];
        }
        
        // Получаем посты уроков по ID
        $lessons = get_posts([
            'post_type' => 'cryptoschool_lesson',
            'post_status' => 'publish',
            'numberposts' => -1,
            'include' => $lesson_ids,
            'orderby' => 'post__in' // Сохраняем порядок из ACF поля
        ]);
        
        return $lessons;
    }
}

// Получаем уроки курса через ACF поле choose_lesson
$lessons_posts = cryptoschool_get_course_lessons($course_post->ID);

// Организуем уроки в группы для отображения
$lesson_groups = [];
global $wpdb;

foreach ($lessons_posts as $lesson_post) {
    $group_id = get_post_meta($lesson_post->ID, 'module_id', true) ?: 0;
    $group_title = get_post_meta($lesson_post->ID, 'module_title', true) ?: __('Уроки курса', 'cryptoschool');
    
    if (!isset($lesson_groups[$group_id])) {
        $lesson_groups[$group_id] = [
            'id' => $group_id,
            'title' => $group_title,
            'lessons_count' => 0,
            'opened' => true, // По умолчанию группа открыта
            'lessons' => []
        ];
    }
    
    // Определяем статус урока для пользователя
    $lesson_status = 'locked'; // По умолчанию урок заблокирован
    $lesson_status_text = __('Недоступний', 'cryptoschool');
    
    // Получаем trid урока для единого прогресса независимо от языка
    $lesson_trid = $wpdb->get_var($wpdb->prepare(
        "SELECT trid FROM {$wpdb->prefix}icl_translations 
         WHERE element_id = %d AND element_type = %s",
        $lesson_post->ID, 'post_cryptoschool_lesson'
    ));
    
    // Если trid не найден (WPML не активен или урок не переведен), используем lesson ID как fallback
    if (!$lesson_trid) {
        $lesson_trid = $lesson_post->ID;
    }
    
    // Получаем прогресс пользователя по уроку (используем trid для новой архитектуры)
    $progress_query = "
        SELECT progress_percent, is_completed 
        FROM {$wpdb->prefix}cryptoschool_user_lesson_progress 
        WHERE user_id = %d AND lesson_id = %d
    ";
    $user_progress = $wpdb->get_row($wpdb->prepare($progress_query, $current_user_id, $lesson_trid));
    
    $lesson_progress = $user_progress ? floatval($user_progress->progress_percent) : 0;
    $is_completed = $user_progress ? boolval($user_progress->is_completed) : false;
    
    // Проверяем, является ли это первым уроком в группе
    $is_first_lesson = (count($lesson_groups[$group_id]['lessons']) === 0);
    
    // Проверяем, завершен ли предыдущий урок
    $prev_lesson_completed = true;
    if (!$is_first_lesson && count($lesson_groups[$group_id]['lessons']) > 0) {
        $last_lesson_index = count($lesson_groups[$group_id]['lessons']) - 1;
        $prev_lesson_id = $lesson_groups[$group_id]['lessons'][$last_lesson_index]['id'];
        
        // Получаем trid предыдущего урока для единого прогресса
        $prev_lesson_trid = $wpdb->get_var($wpdb->prepare(
            "SELECT trid FROM {$wpdb->prefix}icl_translations 
             WHERE element_id = %d AND element_type = %s",
            $prev_lesson_id, 'post_cryptoschool_lesson'
        ));
        
        // Если trid не найден, используем lesson ID как fallback
        if (!$prev_lesson_trid) {
            $prev_lesson_trid = $prev_lesson_id;
        }
        
        $prev_progress_query = "
            SELECT is_completed 
            FROM {$wpdb->prefix}cryptoschool_user_lesson_progress 
            WHERE user_id = %d AND lesson_id = %d
        ";
        $prev_progress = $wpdb->get_var($wpdb->prepare($prev_progress_query, $current_user_id, $prev_lesson_trid));
        $prev_lesson_completed = boolval($prev_progress);
    }
    
    // Первый урок всегда доступен, остальные - только если предыдущий завершен
    if ($is_first_lesson || $prev_lesson_completed) {
        if ($is_completed) {
            $lesson_status = 'done';
            $lesson_status_text = __('Виконаний', 'cryptoschool');
        } elseif ($lesson_progress > 0) {
            $lesson_status = 'in-process';
            $lesson_status_text = __('У процесі', 'cryptoschool');
        } else {
            $lesson_status = 'in-process'; // Доступный урок отображается как "в процессе"
            $lesson_status_text = __('Доступний', 'cryptoschool');
        }
    }
    
    // Добавляем урок в группу
    $lesson_groups[$group_id]['lessons'][] = [
        'id' => $lesson_post->ID, // Используем Post ID для ссылок
        'number' => get_post_meta($lesson_post->ID, 'lesson_order', true) ?: (count($lesson_groups[$group_id]['lessons']) + 1),
        'title' => $lesson_post->post_title,
        'status' => $lesson_status,
        'status_text' => $lesson_status_text
    ];
    
    // Увеличиваем счетчик уроков в группе
    $lesson_groups[$group_id]['lessons_count']++;
}

// Сортируем группы уроков
usort($lesson_groups, function($a, $b) {
    return ($a['id'] ?? 0) <=> ($b['id'] ?? 0);
});

// Сортируем уроки внутри каждой группы по номеру
foreach ($lesson_groups as &$group) {
    usort($group['lessons'], function($a, $b) {
        return $a['number'] <=> $b['number'];
    });
}
?>

<main>
    <div class="page-background">
        <div class="ratio-wrap page-background__wrap">
            <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/decor-light.svg" alt="Page decor" class="ratio-wrap__item page-background__img_light">
            <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/decor-dark.svg" alt="Page decor" class="ratio-wrap__item page-background__img_dark">
        </div>
    </div>

    <div class="container container_wide study__container">
        <div class="hide-mobile">
            <!-- Горизонтальная навигация -->
            <?php get_template_part('template-parts/account/horizontal-navigation'); ?>
        </div>

        <h5 class="h5 color-primary study__title"><?php echo esc_html($course_post->post_title); ?></h5>

        <div class="study__modules">
            <?php if (empty($lesson_groups)) : ?>
                <p class="text-small"><?php _e('Уроки не найдены', 'cryptoschool'); ?></p>
            <?php else : ?>
                <?php foreach ($lesson_groups as $group) : ?>
                    <div class="palette palette_blurred study-module <?php echo $group['opened'] ? 'study-module_opened' : ''; ?>">
                        <div class="study-module__summary">
                            <div class="study-module__left">
                                <div class="study-module__number text"><?php echo esc_html($course_post->post_title); ?></div>
                                <div class="study-module__name text color-primary"><?php echo esc_html($group['title']); ?></div>
                            </div>
                            <div class="study-module__right">
                                <div class="study-module__amount text"><?php echo esc_html($group['lessons_count']); ?> уроків</div>
                                <div class="study-module__toggler">
                                    <span class="icon-nav-arrow-right"></span>
                                </div>
                            </div>
                        </div>
                        <div class="study-module__dropdown">
                            <div class="study-module__lessons">
                                <?php if (empty($group['lessons'])) : ?>
                                    <p class="text-small"><?php _e('Уроки не найдены', 'cryptoschool'); ?></p>
                                <?php else : ?>
                                    <?php foreach ($group['lessons'] as $lesson) : ?>
                                        <div class="study-module__lesson study-module__lesson_<?php echo esc_attr($lesson['status']); ?>">
                                            <?php if ($lesson['status'] === 'done') : ?>
                                                <div class="study-module__lesson-check">
                                                    <span class="icon-check-arrow"></span>
                                                </div>
                                            <?php endif; ?>
                                            <div class="study-module__lesson-left">
                                                <div class="study-module__lesson-number text"><?php echo esc_html($lesson['number']); ?></div>
                                                <div class="study-module__lesson-name text">
                                                    <?php if ($lesson['status'] === 'done' || $lesson['status'] === 'in-process') : ?>
                                                        <a href="<?php echo esc_url(cryptoschool_get_localized_url('/lesson/?id=' . $lesson['id'])); ?>" class="study-module__lesson-link">
                                                            <?php echo esc_html($lesson['title']); ?>
                                                        </a>
                                                    <?php else : ?>
                                                        <?php echo esc_html($lesson['title']); ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="study-module__lesson-status text-small"><?php echo esc_html($lesson['status_text']); ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="bottom-navigation">
            <?php
            // Получаем все доступные курсы для пользователя через новую архитектуру
            $user_packages_query = "
                SELECT p.course_ids 
                FROM {$wpdb->prefix}cryptoschool_user_access ua
                JOIN {$wpdb->prefix}cryptoschool_packages p ON ua.package_id = p.id
                WHERE ua.user_id = %d AND ua.status = 'active'
            ";
            $user_packages = $wpdb->get_results($wpdb->prepare($user_packages_query, $current_user_id));

            // Собираем все ID курсов из пакетов пользователя
            $user_course_ids = [];
            foreach ($user_packages as $package) {
                $package_course_ids = json_decode($package->course_ids, true);
                if (is_array($package_course_ids)) {
                    $user_course_ids = array_merge($user_course_ids, $package_course_ids);
                }
            }

            // Получаем курсы через Custom Post Types
            $user_courses = [];
            if (!empty($user_course_ids)) {
                $user_course_ids = array_unique($user_course_ids);
                
                $user_courses = get_posts([
                    'post_type' => 'cryptoschool_course',
                    'post_status' => 'publish',
                    'numberposts' => -1,
                    'orderby' => 'menu_order',
                    'order' => 'ASC',
                    'meta_query' => [
                        [
                            'key' => '_cryptoschool_table_id',
                            'value' => $user_course_ids,
                            'compare' => 'IN'
                        ]
                    ]
                ]);
            }
            
            // Находим индекс текущего курса в массиве
            $current_index = -1;
            foreach ($user_courses as $index => $user_course) {
                $course_table_id = get_post_meta($user_course->ID, '_cryptoschool_table_id', true);
                if (!$course_table_id) {
                    $course_table_id = $user_course->ID;
                }
                
                if ($course_table_id == $course_id) {
                    $current_index = $index;
                    break;
                }
            }
            
            // Определяем предыдущий и следующий курсы
            $prev_course = ($current_index > 0) ? $user_courses[$current_index - 1] : null;
            $next_course = ($current_index < count($user_courses) - 1) ? $user_courses[$current_index + 1] : null;
            ?>
            
            <?php if ($prev_course) : ?>
                <?php 
                $prev_course_id = get_post_meta($prev_course->ID, '_cryptoschool_table_id', true);
                if (!$prev_course_id) {
                    $prev_course_id = $prev_course->ID;
                }
                ?>
                <a href="<?php echo esc_url(cryptoschool_get_localized_url('/course/?id=' . $prev_course_id)); ?>" class="bottom-navigation__item bottom-navigation__previous">
                    <div class="bottom-navigation__arrow">
                        <span class="icon-nav-arrow-left"></span>
                    </div>
                    <div class="bottom-navigation__label text-small">Попередній курс</div>
                </a>
            <?php endif; ?>
            
            <?php if ($next_course) : ?>
                <?php 
                $next_course_id = get_post_meta($next_course->ID, '_cryptoschool_table_id', true);
                if (!$next_course_id) {
                    $next_course_id = $next_course->ID;
                }
                ?>
                <a href="<?php echo esc_url(cryptoschool_get_localized_url('/course/?id=' . $next_course_id)); ?>" class="bottom-navigation__item bottom-navigation__next">
                    <div class="bottom-navigation__label text-small">Наступний курс</div>
                    <div class="bottom-navigation__arrow">
                        <span class="icon-nav-arrow-right"></span>
                    </div>
                </a>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php get_footer(); ?>
