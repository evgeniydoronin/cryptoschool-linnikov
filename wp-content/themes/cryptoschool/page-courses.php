<?php

/**
 * Template Name: Навчання
 *
 * @package CryptoSchool
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

// Если пользователь не авторизован, перенаправляем на страницу входа
if (!is_user_logged_in()) {
    wp_redirect(site_url('/sign-in/'));
    exit;
}

get_header();

// Вспомогательные функции для работы с курсами
if (!function_exists('cryptoschool_get_course_progress')) {
    /**
     * Получает прогресс пользователя по курсу через ACF поле choose_lesson
     *
     * @param int $user_id ID пользователя
     * @param int $course_id ID курса (Post ID)
     * @return float Прогресс в процентах
     */
    function cryptoschool_get_course_progress($user_id, $course_id) {
        // Получаем уроки курса через ACF поле choose_lesson
        $lessons = cryptoschool_get_course_lessons($course_id);
        
        if (empty($lessons)) {
            return 0;
        }
        
        $total_lessons = count($lessons);
        $completed_lessons = 0;
        
        // Проверяем прогресс по каждому уроку с использованием trid
        global $wpdb;
        foreach ($lessons as $lesson) {
            // Получаем trid урока для единого прогресса независимо от языка
            $lesson_trid = $wpdb->get_var($wpdb->prepare(
                "SELECT trid FROM {$wpdb->prefix}icl_translations 
                 WHERE element_id = %d AND element_type = %s",
                $lesson->ID, 'post_cryptoschool_lesson'
            ));
            
            // Если trid не найден (WPML не активен или урок не переведен), используем lesson ID как fallback
            if (!$lesson_trid) {
                $lesson_trid = $lesson->ID;
            }
            
            // Проверяем прогресс по trid
            $is_completed = $wpdb->get_var($wpdb->prepare(
                "SELECT is_completed FROM {$wpdb->prefix}cryptoschool_user_lesson_progress 
                 WHERE user_id = %d AND lesson_id = %d",
                $user_id, $lesson_trid
            ));
            
            if ($is_completed) {
                $completed_lessons++;
            }
        }
        
        return $total_lessons > 0 ? round(($completed_lessons / $total_lessons) * 100, 2) : 0;
    }
}

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

// Получаем текущего пользователя
$current_user_id = get_current_user_id();

// Получаем курсы пользователя через новую архитектуру Custom Post Types
$courses = [];

// Получаем пакеты пользователя
global $wpdb;
$user_packages_query = "
    SELECT p.course_ids 
    FROM {$wpdb->prefix}cryptoschool_user_access ua
    JOIN {$wpdb->prefix}cryptoschool_packages p ON ua.package_id = p.id
    WHERE ua.user_id = %d AND ua.status = 'active'
";
$user_packages = $wpdb->get_results($wpdb->prepare($user_packages_query, $current_user_id));

// Собираем все ID курсов из пакетов пользователя
$course_ids = [];
foreach ($user_packages as $package) {
    $package_course_ids = json_decode($package->course_ids, true);
    if (is_array($package_course_ids)) {
        $course_ids = array_merge($course_ids, $package_course_ids);
    }
}

// Получаем курсы через Custom Post Types, если есть доступ
if (!empty($course_ids)) {
    $course_ids = array_unique($course_ids);
    
    // Получаем Custom Post Types курсов напрямую по Post ID
    // Также фильтруем по текущему языку WPML
    $courses = get_posts([
        'post_type' => 'cryptoschool_course',
        'post_status' => 'publish',
        'numberposts' => -1,
        'orderby' => 'menu_order',
        'order' => 'ASC',
        'include' => $course_ids,
        'suppress_filters' => false // Включаем WPML фильтры
    ]);
    
    // Дополнительная фильтрация по языку, если WPML активен
    if (function_exists('icl_get_current_language')) {
        $current_language = icl_get_current_language();
        $filtered_courses = [];
        
        foreach ($courses as $course) {
            $course_language = apply_filters('wpml_element_language_code', null, array(
                'element_id' => $course->ID,
                'element_type' => 'post_cryptoschool_course'
            ));
            
            if ($course_language === $current_language) {
                $filtered_courses[] = $course;
            }
        }
        
        $courses = $filtered_courses;
    }
}

// Отладочная информация (закомментирована)
/*
try {
    // Получаем детали первого курса, если он есть
    $first_course = !empty($courses) ? $courses[0] : null;
    $first_course_details = null;
    $first_course_lessons = [];
    $is_available = false;
    $progress = 0;
    
    if ($first_course) {
        $first_course_details = [
            'id' => $first_course->getAttribute('id'),
            'title' => $first_course->getAttribute('title'),
            'description' => $first_course->getAttribute('description'),
            'thumbnail' => $first_course->getAttribute('thumbnail'),
            'is_active' => $first_course->getAttribute('is_active'),
            'all_attributes' => $first_course->getAttributes()
        ];
        
        // Проверяем доступность курса для пользователя
        try {
            $is_available = $first_course->is_available_for_user($current_user_id);
        } catch (Exception $e) {
            $is_available = 'Error: ' . $e->getMessage();
        }
        
        // Получаем прогресс пользователя по курсу
        try {
            $progress = $is_available ? $first_course->get_user_progress($current_user_id) : 0;
        } catch (Exception $e) {
            $progress = 'Error: ' . $e->getMessage();
        }
        
        // Получаем уроки курса
        try {
            $lessons = $first_course->get_lessons();
            $first_course_lessons = !empty($lessons) ? array_map(function($lesson) {
                return [
                    'id' => $lesson->getAttribute('id'),
                    'title' => $lesson->getAttribute('title')
                ];
            }, $lessons) : [];
        } catch (Exception $e) {
            $first_course_lessons = ['Error' => $e->getMessage()];
        }
    }
    
    dd([
        'repository' => get_class($course_repository),
        'table_name' => $course_repository->get_table_name(),
        'courses_count' => count($courses),
        'current_user_id' => $current_user_id,
        'first_course_details' => $first_course_details,
        'is_available' => $is_available,
        'progress' => $progress,
        'first_course_lessons' => $first_course_lessons
    ]);
} catch (Exception $e) {
    dd([
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
*/

// Получаем активный урок через новые функции
$active_lesson_result = cryptoschool_get_user_active_lesson($current_user_id);

// Получаем пройденные уроки
$completed_lessons = cryptoschool_get_user_completed_lessons($current_user_id, 5);

// Формируем итоговый массив: сначала активный урок, затем пройденные
$last_tasks = [];

// Добавляем активный урок, если он есть
if ($active_lesson_result) {
    $last_tasks[] = [
        'id' => $active_lesson_result['lesson_id'],
        'status' => 'orange', // активный урок - оранжевый
        'pretitle' => $active_lesson_result['course_title'],
        'title' => $active_lesson_result['lesson_title'],
        'subtitle' => 'У процесі',
        'amount' => '+' . ($active_lesson_result['completion_points'] ?? 5)
    ];
}

// Добавляем пройденные уроки (максимум 4, если есть активный урок)
$max_completed = $active_lesson_result ? 4 : 5;
$completed_count = 0;

foreach ($completed_lessons as $completed) {
    if ($completed_count >= $max_completed) break;
    
    $last_tasks[] = [
        'id' => $completed['lesson_id'],
        'status' => 'green', // пройденный урок - зеленый
        'pretitle' => $completed['course_title'],
        'title' => $completed['lesson_title'],
        'subtitle' => 'Виконаний',
        'amount' => '+' . ($completed['completion_points'] ?? 5)
    ];
    
    $completed_count++;
}
?>

<main>
    <div class="page-background">
        <div class="ratio-wrap page-background__wrap">
            <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/decor-light.svg" alt="Page decor" class="ratio-wrap__item page-background__img_light">
            <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/decor-dark.svg" alt="Page decor" class="ratio-wrap__item page-background__img_dark">
        </div>
    </div>
    <div class="container container_wide courses__container">
        <!-- Горизонтальная навигация -->
        <?php get_template_part('template-parts/account/horizontal-navigation'); ?>
        <!-- Блок прогресса обучения -->
        <?php
        // Получаем серию пользователя из базы данных
        $user_streak_query = $wpdb->prepare(
            "SELECT current_streak, max_streak, last_activity_date, lessons_today 
             FROM {$wpdb->prefix}cryptoschool_user_streak 
             WHERE user_id = %d",
            $current_user_id
        );
        $user_streak = $wpdb->get_row($user_streak_query);
        
        // Если нет записи о серии, создаем значения по умолчанию
        if (!$user_streak) {
            $user_streak = (object) [
                'current_streak' => 0,
                'max_streak' => 0,
                'last_activity_date' => null,
                'lessons_today' => 0
            ];
        }
        
        $current_streak = $user_streak->current_streak;
        $max_streak = $user_streak->max_streak;
        $lessons_today = $user_streak->lessons_today;
        $last_activity_date = $user_streak->last_activity_date;
        
        // Определяем, какой сегодня день относительно последней активности
        $today = current_time('Y-m-d');
        $is_today_active = ($last_activity_date === $today && $lessons_today > 0);
        
        // Получаем общие баллы пользователя
        $total_points = $wpdb->get_var($wpdb->prepare(
            "SELECT total_points FROM {$wpdb->prefix}cryptoschool_user_leaderboard WHERE user_id = %d",
            $current_user_id
        ));

        // Получаем баллы за текущий день
        $today_points = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(points) FROM {$wpdb->prefix}cryptoschool_points_history 
             WHERE user_id = %d AND DATE(created_at) = %s",
            $current_user_id, $today
        ));

        // Значения по умолчанию если нет данных
        $total_points = $total_points ?: 0;
        $today_points = $today_points ?: 0;
        ?>
        <div class="study-daily-progress palette palette_blurred account-block courses__progress">
            <div class="study-daily-progress__steps">
                <?php for ($day = 1; $day <= 5; $day++) : ?>
                <div class="study-daily-progress__step">
                    <div class="study-daily-progress__reward">
                        <?php if ($day == 5) : ?>
                            <div class="text-small study-daily-progress__value">
                                Щоденний<br> відрізок
                            </div>
                        <?php else : ?>
                            <div class="text study-daily-progress__value">
                                <?php echo $day == 1 ? '0' : '+5'; ?>
                            </div>
                        <?php endif; ?>
                        <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/shared/star.svg" alt="">
                    </div>
                    <div class="text-small study-daily-progress__condition"><?php echo $day; ?> день</div>
                </div>
                <?php endfor; ?>
            </div>
            <div class="study-daily-progress__progress">
                <div class="study-daily-progress__track">
                    <div class="study-daily-progress__fill" style="width: <?php echo min(100, ($current_streak / 5) * 100); ?>%"></div>
                </div>
                <div class="study-daily-progress__points">
                    <?php for ($point = 1; $point <= 5; $point++) : ?>
                        <?php 
                        $is_filled = ($current_streak >= $point) || ($point == 1 && $is_today_active);
                        $point_class = $is_filled ? 'study-daily-progress__point study-daily-progress__point_filled' : 'study-daily-progress__point';
                        ?>
                        <div class="<?php echo $point_class; ?>">
                            <div class="study-daily-progress__point-circle">
                                <span class="icon-check-arrow"></span>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
            <div class="study-daily-progress__hints">
                <!-- Блок с баллами -->
                
                <div class="study-daily-progress__hint text-small">💰 Загальні бали: <?php echo $total_points; ?></div>
                <?php if ($today_points > 0) : ?>
                    <div class="study-daily-progress__hint text-small">⚡ Бали за сьогодні: <?php echo $today_points; ?></div>
                <?php endif; ?>
                
                <?php if ($current_streak == 0 && !$is_today_active) : ?>
                    <div class="study-daily-progress__hint text-small">Почніть свою серію сьогодні!</div>
                    <div class="study-daily-progress__hint text-small">Пройдіть перший урок, щоб почати заробляти бали</div>
                <?php elseif ($current_streak == 0 && $is_today_active) : ?>
                    <div class="study-daily-progress__hint text-small">Гарний початок! Продовжуйте завтра!</div>
                    <div class="study-daily-progress__hint text-small">Пройшли сьогодні: <?php echo $lessons_today; ?> урок<?php echo $lessons_today > 1 ? 'и' : ''; ?></div>
                <?php elseif ($current_streak >= 1 && $current_streak < 5) : ?>
                    <div class="study-daily-progress__hint text-small">Серія: <?php echo $current_streak; ?> день! Не втрачайте темп!</div>
                    <div class="study-daily-progress__hint text-small">
                        <?php if ($is_today_active) : ?>
                            Сьогодні пройдено: <?php echo $lessons_today; ?> урок<?php echo $lessons_today > 1 ? 'и' : ''; ?>
                        <?php else : ?>
                            Пройдіть урок сьогодні, щоб продовжити серію
                        <?php endif; ?>
                    </div>
                <?php else : ?>
                    <div class="study-daily-progress__hint text-small">🔥 Щоденна серія досягнута!</div>
                    <div class="study-daily-progress__hint text-small">Максимальна серія: <?php echo $max_streak; ?> днів</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Блок списка курсов -->
        <div class="account-block palette palette_blurred courses__block">
            <h6 class="h6 color-primary account-block__title courses__block-title">Наши курси</h6>
            <hr class="account-block__horizontal-row">

            <div class="courses__list">
                <?php if (empty($courses)) : ?>
                    <p class="text-small">Курсы не найдены</p>
                <?php else : ?>
                    <?php
                    // Переменная для отслеживания, завершен ли предыдущий курс
                    $previous_course_completed = true;

                    foreach ($courses as $course) :
                        // Получаем ID курса из Custom Post Type
                        $course_id = get_post_meta($course->ID, '_cryptoschool_table_id', true);
                        if (!$course_id) {
                            $course_id = $course->ID; // Fallback к WordPress ID
                        }

                        // Определяем статус курса для пользователя (пользователь уже имеет доступ, так как курс получен из его пакетов)
                        $is_available = true;
                        
                        // Получаем прогресс пользователя по курсу
                        $progress = cryptoschool_get_course_progress($current_user_id, $course_id);

                        // Определяем статус на основе прогресса, доступности и завершения предыдущего курса
                        if (!$previous_course_completed) {
                            // Если предыдущий курс не завершен, этот курс заблокирован
                            $status = 'locked';
                        } else {
                            // Иначе определяем статус на основе доступности и прогресса
                            $status = !$is_available ? 'locked' : ($progress >= 100 ? 'done' : 'in_progress');

                            // Обновляем статус завершения предыдущего курса для следующей итерации
                            $previous_course_completed = ($status === 'done');
                        }

                        // Получаем уроки курса для отображения в списке тем
                        $lessons = cryptoschool_get_course_lessons($course_id);

                        // Получаем URL изображения курса
                        $image_url = get_the_post_thumbnail_url($course->ID, 'medium');
                        if (empty($image_url)) {
                            $image_url = get_template_directory_uri() . '/frontend-source/dist/assets/img/temp/course-card-illustration.png';
                        }
                    ?>
                        <div class="course-card <?php echo $status === 'done' ? 'course-card_done' : ($status === 'locked' ? 'course-card_locked' : ''); ?>">
                            <div class="course-card__header">
                                <?php if ($status === 'done') : ?>
                                    <div class="text-small course-card__badge">Пройдено</div>
                                <?php endif; ?>
                                <img class="course-card__image" src="<?php echo esc_url($image_url); ?>">
                            </div>
                            <div class="course-card__body">
                                <div class="h6 course-card__title"><?php echo esc_html($course->post_title); ?></div>
                                <ul class="account-list course-card__list">
                                    <?php
                                    // Выводим до 5 уроков в качестве тем курса
                                    $topics_count = 0;
                                    if (!empty($lessons)) :
                                        foreach ($lessons as $lesson) :
                                            if ($topics_count >= 5) break; // Ограничиваем количество тем
                                        ?>
                                            <li><?php echo esc_html($lesson->post_title); ?></li>
                                        <?php
                                            $topics_count++;
                                        endforeach;
                                    endif;
                                    ?>
                                </ul>
                                <?php if (!empty($lessons) && count($lessons) > 5) : ?>
                                    <div class="course-card__ellipsis text-small">...</div>
                                <?php endif; ?>
                            </div>
                            <div class="course-card__footer">
                                <?php if ($status === 'locked') : ?>
                                    <button class="button button_filled button_rounded button_centered button_block" disabled>
                                        <span class="button__text">Зайти в курс</span>
                                    </button>
                                <?php else : ?>
                                    <a href="<?php echo esc_url(cryptoschool_get_course_url($course_id)); ?>" class="button button_filled button_rounded button_centered button_block">
                                        <span class="button__text">Зайти в курс</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Блок последних заданий -->
        <div class="account-block palette palette_blurred">
            <h5 class="account-block__title text">Останні завданя</h5>
            <hr class="account-block__horizontal-row">
            <!-- <div class="account-block__tabs hide-tablet hide-mobile">
                <a href="#" class="account-block__tab text-small account-block__tab_active">Уcі</a>
                <a href="#" class="account-block__tab text-small">Активні</a>
                <a href="#" class="account-block__tab text-small">Виконані</a>
                <a href="#" class="account-block__tab text-small">На перевірці</a>
                <a href="#" class="account-block__tab text-small">Доопрацювати</a>
            </div> -->
            <div class="account-last-tasks__items">
                <?php if (empty($last_tasks)) : ?>
                    <p class="text-small">У вас пока нет пройденных уроков</p>
                <?php else : ?>
                    <?php foreach ($last_tasks as $task) : ?>
                        <div class="status-line palette palette_hoverable account-last-tasks-item">
                            <div class="status-line-indicator status-line-indicator_<?php echo esc_attr($task['status']); ?>"></div>
                            <div class="account-last-tasks-item__body">
                                <div class="account-last-tasks-item__content">
                                    <div class="account-last-tasks-item__pretitle text-small color-primary">
                                        <?php echo esc_html($task['pretitle']); ?>
                                    </div>
                                    <h6 class="account-last-tasks-item__title text"><?php echo esc_html($task['title']); ?></h6>
                                    <div class="account-last-tasks-item__subtitle text-small">
                                        <?php echo esc_html($task['subtitle']); ?>
                                    </div>
                                </div>
                                <div class="account-last-tasks-item__details">
                                    <div class="text-small account-last-tasks-item__amount"><?php echo esc_html($task['amount']); ?></div>
                                    <a href="<?php echo esc_url(cryptoschool_get_lesson_url($task['id'])); ?>" class="account-last-tasks-item__link">
                                        <span class="icon-play-triangle-right"></span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <!-- <button class="account-more">
                <span class="text-small color-primary">Показати ще</span>
                <span class="icon-arrow-right-small account-more__icon"></span>
            </button> -->
        </div>
    </div>
</main>

<?php get_footer(); ?>
