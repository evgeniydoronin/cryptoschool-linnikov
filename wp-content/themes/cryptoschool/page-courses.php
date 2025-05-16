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

// Получаем текущего пользователя
$current_user_id = get_current_user_id();

// Получаем только курсы, доступные пользователю
$course_repository = new CryptoSchool_Repository_Course();
$courses = $course_repository->get_user_courses($current_user_id, [
    'is_active' => 1,
    'orderby' => 'c.course_order',
    'order' => 'ASC'
]);

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

// Получаем активный урок с помощью SQL-запроса
global $wpdb;
$active_lesson_query = "
    WITH user_packages AS (
        -- Получаем пакеты пользователя
        SELECT 
            ua.id AS access_id,
            ua.package_id,
            p.course_ids
        FROM {$wpdb->prefix}cryptoschool_user_access ua
        JOIN {$wpdb->prefix}cryptoschool_packages p ON ua.package_id = p.id
        WHERE ua.user_id = %d AND ua.status = 'active'
    ),
    user_courses AS (
        -- Получаем курсы из пакетов пользователя и сортируем по ID
        SELECT 
            c.id AS course_id,
            c.title AS course_title,
            (
                -- Вычисляем прогресс по курсу
                SELECT COALESCE(ROUND(
                    SUM(CASE WHEN ulp.is_completed = 1 THEN 1 ELSE NULL END) * 100.0 / COUNT(*)
                ), 0)
                FROM {$wpdb->prefix}cryptoschool_lessons l
                LEFT JOIN {$wpdb->prefix}cryptoschool_user_lesson_progress ulp 
                    ON l.id = ulp.lesson_id AND ulp.user_id = %d
                WHERE l.course_id = c.id AND l.is_active = 1
            ) AS progress
        FROM {$wpdb->prefix}cryptoschool_courses c
        JOIN user_packages up ON JSON_CONTAINS(up.course_ids, CONCAT('\"', c.id, '\"'))
        WHERE c.is_active = 1
        ORDER BY c.id ASC
    ),
    active_course AS (
        -- Находим первый незавершенный курс
        SELECT 
            course_id,
            course_title
        FROM user_courses
        WHERE progress < 100
        ORDER BY course_id ASC
        LIMIT 1
    ),
    completed_lessons AS (
        -- Находим все завершенные уроки в активном курсе
        SELECT 
            l.id AS lesson_id,
            l.lesson_order
        FROM {$wpdb->prefix}cryptoschool_lessons l
        JOIN {$wpdb->prefix}cryptoschool_user_lesson_progress ulp 
            ON l.id = ulp.lesson_id AND ulp.user_id = %d
        JOIN active_course ac ON l.course_id = ac.course_id
        WHERE ulp.is_completed = 1
        ORDER BY l.lesson_order DESC
        LIMIT 1
    ),
    next_lesson AS (
        -- Находим следующий урок после последнего завершенного
        SELECT 
            l.id AS lesson_id,
            l.title AS lesson_title,
            l.lesson_order,
            l.completion_points,
            ac.course_id,
            ac.course_title
        FROM {$wpdb->prefix}cryptoschool_lessons l
        JOIN active_course ac ON l.course_id = ac.course_id
        LEFT JOIN completed_lessons cl ON 1=1
        WHERE l.is_active = 1
          AND (
              -- Если есть завершенные уроки, берем следующий по порядку
              (cl.lesson_id IS NOT NULL AND l.lesson_order > cl.lesson_order)
              OR
              -- Если нет завершенных уроков, берем первый урок курса
              (cl.lesson_id IS NULL)
          )
        ORDER BY l.lesson_order ASC
        LIMIT 1
    )
    -- Выводим активный урок
    SELECT * FROM next_lesson;
";

$active_lesson_result = $wpdb->get_row($wpdb->prepare($active_lesson_query, $current_user_id, $current_user_id, $current_user_id));

// Получаем пройденные уроки
$completed_lessons_query = "
    SELECT 
        l.id AS lesson_id,
        l.title AS lesson_title,
        c.id AS course_id,
        c.title AS course_title,
        ulp.completed_at,
        l.completion_points
    FROM {$wpdb->prefix}cryptoschool_lessons l
    JOIN {$wpdb->prefix}cryptoschool_courses c ON l.course_id = c.id
    JOIN {$wpdb->prefix}cryptoschool_user_lesson_progress ulp 
        ON l.id = ulp.lesson_id AND ulp.user_id = %d
    WHERE ulp.is_completed = 1
    ORDER BY ulp.completed_at DESC
    LIMIT 5;
";

$completed_lessons = $wpdb->get_results($wpdb->prepare($completed_lessons_query, $current_user_id));

// Формируем итоговый массив: сначала активный урок, затем пройденные
$last_tasks = [];

// Добавляем активный урок, если он есть
if ($active_lesson_result) {
    $last_tasks[] = [
        'id' => $active_lesson_result->lesson_id,
        'status' => 'orange', // активный урок - оранжевый
        'pretitle' => $active_lesson_result->course_title,
        'title' => $active_lesson_result->lesson_title,
        'subtitle' => 'У процесі',
        'amount' => '+' . ($active_lesson_result->completion_points ?? 5)
    ];
}

// Добавляем пройденные уроки (максимум 4, если есть активный урок)
$max_completed = $active_lesson_result ? 4 : 5;
$completed_count = 0;

foreach ($completed_lessons as $completed) {
    if ($completed_count >= $max_completed) break;
    
    $last_tasks[] = [
        'id' => $completed->lesson_id,
        'status' => 'green', // пройденный урок - зеленый
        'pretitle' => $completed->course_title,
        'title' => $completed->lesson_title,
        'subtitle' => 'Виконаний',
        'amount' => '+' . ($completed->completion_points ?? 5)
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
        <div class="study-daily-progress palette palette_blurred account-block courses__progress">
            <div class="study-daily-progress__steps">
                <div class="study-daily-progress__step">
                    <div class="study-daily-progress__reward">
                        <div class="text study-daily-progress__value">+5</div>
                        <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/shared/star.svg" alt="">
                    </div>
                    <div class="text-small study-daily-progress__condition">1 день</div>
                </div>
                <div class="study-daily-progress__step">
                    <div class="study-daily-progress__reward">
                        <div class="text study-daily-progress__value">+5</div>
                        <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/shared/star.svg" alt="">
                    </div>
                    <div class="text-small study-daily-progress__condition">2 день</div>
                </div>
                <div class="study-daily-progress__step">
                    <div class="study-daily-progress__reward">
                        <div class="text study-daily-progress__value">+5</div>
                        <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/shared/star.svg" alt="">
                    </div>
                    <div class="text-small study-daily-progress__condition">3 день</div>
                </div>
                <div class="study-daily-progress__step">
                    <div class="study-daily-progress__reward">
                        <div class="text study-daily-progress__value">+5</div>
                        <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/shared/star.svg" alt="">
                    </div>
                    <div class="text-small study-daily-progress__condition">4 день</div>
                </div>
                <div class="study-daily-progress__step">
                    <div class="study-daily-progress__reward">
                        <div class="text-small study-daily-progress__value">
                            Щоденний<br> відрізок
                        </div>
                        <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/shared/star.svg" alt="">
                    </div>
                    <div class="text-small study-daily-progress__condition">5 день</div>
                </div>
            </div>
            <div class="study-daily-progress__progress">
                <div class="study-daily-progress__track">
                    <div class="study-daily-progress__fill"></div>
                </div>
                <div class="study-daily-progress__points">
                    <div class="study-daily-progress__point study-daily-progress__point_filled">
                        <div class="study-daily-progress__point-circle">
                            <span class="icon-check-arrow"></span>
                        </div>
                    </div>
                    <div class="study-daily-progress__point study-daily-progress__point_filled">
                        <div class="study-daily-progress__point-circle">
                            <span class="icon-check-arrow"></span>
                        </div>
                    </div>
                    <div class="study-daily-progress__point">
                        <div class="study-daily-progress__point-circle">
                            <span class="icon-check-arrow"></span>
                        </div>
                    </div>
                    <div class="study-daily-progress__point">
                        <div class="study-daily-progress__point-circle">
                            <span class="icon-check-arrow"></span>
                        </div>
                    </div>
                    <div class="study-daily-progress__point">
                        <div class="study-daily-progress__point-circle">
                            <span class="icon-check-arrow"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="study-daily-progress__hints">
                <div class="study-daily-progress__hint text-small">Отримайте свою щоденну виногороду вже зараз!</div>
                <div class="study-daily-progress__hint text-small">Практикуйтесь щодня, щоб не втратити відрізок</div>
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
                    // Отладочная информация
                    echo '<div style="background-color: #fff; color: #000; padding: 10px; margin-bottom: 20px; border-radius: 5px;">';
                    echo '<h3>Отладочная информация</h3>';
                    echo '<p>Количество курсов: ' . count($courses) . '</p>';
                    echo '<p>ID текущего пользователя: ' . $current_user_id . '</p>';

                    // Проверяем таблицу доступов
                    global $wpdb;
                    $access_table = $wpdb->prefix . 'cryptoschool_user_access';
                    $packages_table = $wpdb->prefix . 'cryptoschool_packages';

                    $query = $wpdb->prepare(
                        "SELECT a.*, p.course_ids FROM {$access_table} a
                        INNER JOIN {$packages_table} p ON a.package_id = p.id
                        WHERE a.user_id = %d AND a.status = 'active'",
                        $current_user_id
                    );

                    $accesses = $wpdb->get_results($query);

                    echo '<p>Количество активных доступов: ' . count($accesses) . '</p>';

                    if (!empty($accesses)) {
                        echo '<ul>';
                        foreach ($accesses as $access) {
                            echo '<li>Доступ ID: ' . $access->id . ', Пакет ID: ' . $access->package_id . ', Курсы: ' . $access->course_ids . '</li>';
                        }
                        echo '</ul>';
                    }

                    echo '</div>';

                    // Переменная для отслеживания, завершен ли предыдущий курс
                    $previous_course_completed = true;

                    foreach ($courses as $course) :
                        // Получаем ID курса
                        $course_id = $course->getAttribute('id');

                        // Определяем статус курса для пользователя
                        $is_available = $course->is_available_for_user($current_user_id);
                        $progress = $is_available ? $course->get_user_progress($current_user_id) : 0;

                        // Отладочная информация для каждого курса
                        echo '<div style="background-color: #fff; color: #000; padding: 10px; margin-bottom: 10px; border-radius: 5px;">';
                        echo '<p>Курс ID: ' . $course_id . ', Название: ' . $course->getAttribute('title') . '</p>';
                        echo '<p>Доступен: ' . ($is_available ? 'Да' : 'Нет') . ', Прогресс: ' . $progress . '%</p>';
                        echo '</div>';

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
                        $lessons = $course->get_lessons();

                        // Получаем URL изображения курса
                        $image_url = $course->get_thumbnail_url('medium');
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
                                <div class="h6 course-card__title"><?php echo esc_html($course->getAttribute('title')); ?></div>
                                <ul class="account-list course-card__list">
                                    <?php
                                    // Выводим до 5 уроков в качестве тем курса
                                    $topics_count = 0;
                                    foreach ($lessons as $lesson) :
                                        if ($topics_count >= 5) break; // Ограничиваем количество тем
                                    ?>
                                        <li><?php echo esc_html($lesson->getAttribute('title')); ?></li>
                                    <?php
                                        $topics_count++;
                                    endforeach;
                                    ?>
                                </ul>
                                <?php if (count($lessons) > 5) : ?>
                                    <div class="course-card__ellipsis text-small">...</div>
                                <?php endif; ?>
                            </div>
                            <div class="course-card__footer">
                                <?php if ($status === 'locked') : ?>
                                    <button class="button button_filled button_rounded button_centered button_block" disabled>
                                        <span class="button__text">Зайти в курс</span>
                                    </button>
                                <?php else : ?>
                                    <a href="<?php echo esc_url(site_url('/course/?id=' . $course_id)); ?>" class="button button_filled button_rounded button_centered button_block">
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
                                    <a href="<?php echo esc_url(site_url('/lesson/?id=' . $task['id'])); ?>" class="account-last-tasks-item__link">
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
