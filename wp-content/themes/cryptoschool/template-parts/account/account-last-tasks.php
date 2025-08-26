<?php
/**
 * Шаблон блока последних заданий в личном кабинете
 *
 * @package CryptoSchool
 */

// Получаем текущего пользователя
$current_user_id = get_current_user_id();

// Получаем активный урок через новые функции
$active_lesson_result = cryptoschool_get_user_active_lesson($current_user_id);

// Получаем пройденные уроки
$completed_lessons = cryptoschool_get_user_completed_lessons($current_user_id, 3);

// Формируем итоговый массив: сначала активный урок, затем пройденные
$last_tasks = [];

// Добавляем активный урок, если он есть
if ($active_lesson_result) {
    $last_tasks[] = [
        'status' => 'in_progress',
        'status_class' => 'status-line-indicator_orange',
        'status_text' => 'У процесі',
        'title' => $active_lesson_result['lesson_title'],
        'course' => $active_lesson_result['course_title'],
        'points' => $active_lesson_result['completion_points'] ?? 5,
        'lesson_id' => $active_lesson_result['lesson_id']
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
        'title' => $completed['lesson_title'],
        'course' => $completed['course_title'],
        'points' => $completed['completion_points'] ?? 5,
        'lesson_id' => $completed['lesson_id']
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
                            <a href="<?php echo esc_url(cryptoschool_get_lesson_url($task['lesson_id'])); ?>" class="account-last-tasks-item__link">
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
