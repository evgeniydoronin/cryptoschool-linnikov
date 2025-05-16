<?php
/**
 * Шаблон блока последних заданий в личном кабинете
 *
 * @package CryptoSchool
 */

// Получаем текущего пользователя
$current_user_id = get_current_user_id();

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
    LIMIT 3;
";

$completed_lessons = $wpdb->get_results($wpdb->prepare($completed_lessons_query, $current_user_id));

// Формируем итоговый массив: сначала активный урок, затем пройденные
$last_tasks = [];

// Добавляем активный урок, если он есть
if ($active_lesson_result) {
    $last_tasks[] = [
        'status' => 'in_progress',
        'status_class' => 'status-line-indicator_orange',
        'status_text' => 'У процесі',
        'title' => $active_lesson_result->lesson_title,
        'course' => $active_lesson_result->course_title,
        'points' => $active_lesson_result->completion_points ?? 5,
        'lesson_id' => $active_lesson_result->lesson_id
    ];
}

// Добавляем пройденные уроки (максимум 2, если есть активный урок)
$max_completed = $active_lesson_result ? 2 : 3;
$completed_count = 0;

foreach ($completed_lessons as $completed) {
    if ($completed_count >= $max_completed) break;
    
    $last_tasks[] = [
        'status' => 'open',
        'status_class' => 'status-line-indicator_green',
        'status_text' => 'Виконаний',
        'title' => $completed->lesson_title,
        'course' => $completed->course_title,
        'points' => $completed->completion_points ?? 5,
        'lesson_id' => $completed->lesson_id
    ];
    
    $completed_count++;
}

// Если нет ни активных, ни пройденных уроков, добавляем заглушку
if (empty($last_tasks)) {
    $last_tasks[] = [
        'status' => 'closed',
        'status_class' => 'status-line-indicator_red',
        'status_text' => 'Немає активних уроків',
        'title' => 'Почніть навчання',
        'course' => 'Перейдіть на сторінку курсів',
        'points' => 0
    ];
}
?>

<div class="account-block palette palette_blurred">
    <h5 class="account-block__title text">Останні завданя</h5>

    <div class="account-last-tasks__items">
        <?php foreach ($last_tasks as $task) : ?>
            <div class="status-line palette palette_blurred palette_hoverable account-last-tasks-item">
                <div class="status-line-indicator <?php echo esc_attr($task['status_class']); ?>"></div>
                <div class="account-last-tasks-item__body">
                    <div class="account-last-tasks-item__content">
                        <?php if (!empty($task['course'])) : ?>
                            <div class="account-last-tasks-item__pretitle text-small color-primary">
                                <?php echo esc_html($task['course']); ?>
                            </div>
                        <?php endif; ?>
                        <h6 class="account-last-tasks-item__title text"><?php echo esc_html($task['title']); ?></h6>
                        <div class="account-last-tasks-item__subtitle text-small">
                            <?php echo esc_html($task['status_text']); ?>
                        </div>
                    </div>

                    <div class="account-last-tasks-item__details">
                        <div class="text-small account-last-tasks-item__amount">+<?php echo esc_html($task['points']); ?></div>
                        <?php if (isset($task['lesson_id'])) : ?>
                            <a href="<?php echo esc_url(site_url('/lesson/?id=' . $task['lesson_id'])); ?>" class="account-last-tasks-item__link">
                                <span class="icon-play-triangle-right"></span>
                            </a>
                        <?php else : ?>
                            <button class="account-last-tasks-item__link">
                                <span class="icon-play-triangle-right"></span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="account-more">
        <a href="<?php echo esc_url(site_url('/courses/')); ?>" class="account-more__link text-small">
            Дивитися всі
            <span class="account-more__icon icon-nav-arrow-right"></span>
        </a>
    </div>
</div>
