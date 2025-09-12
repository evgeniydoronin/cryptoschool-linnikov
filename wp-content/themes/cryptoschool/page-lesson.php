<?php
/**
 * Template Name: Урок
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

// Получаем ID урока из GET-параметра
$lesson_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Получаем trid урока для единого прогресса независимо от языка
global $wpdb;
$lesson_trid = $wpdb->get_var($wpdb->prepare(
    "SELECT trid FROM {$wpdb->prefix}icl_translations 
     WHERE element_id = %d AND element_type = %s",
    $lesson_id, 'post_cryptoschool_lesson'
));

// Если trid не найден (WPML не активен или урок не переведен), используем lesson_id как fallback
if (!$lesson_trid) {
    $lesson_trid = $lesson_id;
}

// Получаем текущего пользователя
$current_user_id = get_current_user_id();

// Инициализируем сервис доступности
$loader = new CryptoSchool_Loader();
$accessibility_service = new CryptoSchool_Service_Accessibility($loader);

// Проверяем доступность урока для пользователя
$accessibility_result = $accessibility_service->check_lesson_accessibility($current_user_id, $lesson_id);

// Если урок недоступен, перенаправляем на соответствующую страницу
if (!$accessibility_result['accessible']) {
    wp_redirect($accessibility_result['redirect_url']);
    exit;
}

// Получаем данные урока из новой архитектуры Custom Post Types
$lesson_post = get_post($lesson_id);

if (!$lesson_post || $lesson_post->post_type !== 'cryptoschool_lesson' || $lesson_post->post_status !== 'publish') {
    // Если урок не найден в новой архитектуре, перенаправляем на страницу курсов
    wp_redirect(site_url('/courses/'));
    exit;
}

// Создаем объект урока для совместимости со старым кодом
$lesson_model = (object) [
    'id' => $lesson_post->ID,
    'title' => $lesson_post->post_title,
    'content' => $lesson_post->post_content,
    'video_url' => get_post_meta($lesson_post->ID, 'video_url', true),
    'course_id' => null // Будет определен ниже
];

// Находим курс, к которому относится урок
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
    // Если курс не найден, перенаправляем на страницу курсов
    wp_redirect(site_url('/courses/'));
    exit;
}

$course_post = $course_posts[0];
$course_table_id = get_post_meta($course_post->ID, '_cryptoschool_table_id', true);
if (!$course_table_id) {
    $course_table_id = $course_post->ID;
}

// Создаем объект курса для совместимости
$course_model = (object) [
    'id' => $course_table_id,
    'title' => $course_post->post_title
];

$lesson_model->course_id = $course_table_id;

// Получаем задания урока из ACF
$acf_tasks = get_field('zadaniya_uroka', $lesson_id);
$tasks = [];

if ($acf_tasks && is_array($acf_tasks)) {
    foreach ($acf_tasks as $index => $task) {
        if (isset($task['punkt']) && !empty($task['punkt'])) {
            $tasks[] = (object) [
                'id' => $lesson_trid * 1000 + $index,
                'title' => $task['punkt'],
                'order' => $index
            ];
        }
    }
}

// Получаем все уроки курса для навигации
$course_lessons = get_field('choose_lesson', $course_post->ID);
$current_lesson_index = -1;
$prev_lesson = null;
$next_lesson = null;

if ($course_lessons && is_array($course_lessons)) {
    foreach ($course_lessons as $index => $course_lesson) {
        // Обрабатываем как объект WP_Post или массив
        $lesson_post_id = is_object($course_lesson) ? $course_lesson->ID : (is_array($course_lesson) ? $course_lesson['ID'] : intval($course_lesson));
        
        if ($lesson_post_id == $lesson_id) {
            $current_lesson_index = $index;
            break;
        }
    }
    
    if ($current_lesson_index > 0) {
        $prev_lesson_data = $course_lessons[$current_lesson_index - 1];
        $prev_lesson = is_object($prev_lesson_data) ? $prev_lesson_data : get_post(intval($prev_lesson_data));
    }
    if ($current_lesson_index < count($course_lessons) - 1) {
        $next_lesson_data = $course_lessons[$current_lesson_index + 1];
        $next_lesson = is_object($next_lesson_data) ? $next_lesson_data : get_post(intval($next_lesson_data));
    }
}

// Инициализируем переменные
$user_progress = null;
$user_task_progress = [];
$all_tasks_completed = true;

// Получаем прогресс пользователя по уроку из базы данных
global $wpdb;
$progress_query = "
    SELECT progress_percent, is_completed, completed_at, updated_at
    FROM {$wpdb->prefix}cryptoschool_user_lesson_progress 
    WHERE user_id = %d AND lesson_id = %d
";
$user_progress = $wpdb->get_row($wpdb->prepare($progress_query, $current_user_id, $lesson_trid));

// Обработка отправки формы
$form_submitted = false;
$form_success = false;
$form_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_lesson'])) {
    // Проверка nonce
    if (isset($_POST['lesson_nonce']) && wp_verify_nonce($_POST['lesson_nonce'], 'complete_lesson_' . $lesson_id)) {
        // Получаем отмеченные задания
        $completed_tasks = isset($_POST['completed_tasks']) ? $_POST['completed_tasks'] : [];
        
        // Обновляем прогресс по заданиям
        foreach ($tasks as $task) {
            $is_completed = in_array($task->id, $completed_tasks);
            
            // Проверяем существует ли запись прогресса для данного задания
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}cryptoschool_user_task_progress 
                 WHERE user_id = %d AND lesson_id = %d AND task_id = %s",
                $current_user_id, $lesson_trid, $task->id
            ));
            
            if ($existing) {
                // Обновляем существующую запись
                $wpdb->update(
                    $wpdb->prefix . 'cryptoschool_user_task_progress',
                    [
                        'is_completed' => $is_completed ? 1 : 0,
                        'completed_at' => $is_completed ? current_time('mysql') : null
                    ],
                    [
                        'user_id' => $current_user_id,
                        'lesson_id' => $lesson_trid,
                        'task_id' => $task->id
                    ]
                );
            } else {
                // Создаем новую запись
                $wpdb->insert(
                    $wpdb->prefix . 'cryptoschool_user_task_progress',
                    [
                        'user_id' => $current_user_id,
                        'lesson_id' => $lesson_trid,
                        'task_id' => $task->id,
                        'is_completed' => $is_completed ? 1 : 0,
                        'completed_at' => $is_completed ? current_time('mysql') : null
                    ]
                );
            }
        }
        
        // Если все задания выполнены, отмечаем урок как завершенный
        if (count($completed_tasks) === count($tasks)) {
            // Обновляем или создаем запись о завершении урока
            $existing_progress = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}cryptoschool_user_lesson_progress 
                 WHERE user_id = %d AND lesson_id = %d",
                $current_user_id, $lesson_trid
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
                    ['user_id' => $current_user_id, 'lesson_id' => $lesson_trid]
                );
            } else {
                $wpdb->insert(
                    $wpdb->prefix . 'cryptoschool_user_lesson_progress',
                    [
                        'user_id' => $current_user_id,
                        'lesson_id' => $lesson_trid,
                        'is_completed' => 1,
                        'progress_percent' => 100,
                        'completed_at' => current_time('mysql'),
                        'updated_at' => current_time('mysql')
                    ]
                );
            }
            
            // Вызываем action для начисления баллов
            error_log("DEBUG: Вызываем do_action('cryptoschool_lesson_completed', $current_user_id, $lesson_trid)");
            do_action('cryptoschool_lesson_completed', $current_user_id, $lesson_trid);
            error_log("DEBUG: do_action завершен");
            
            $form_success = true;
            $form_message = __('Урок успешно завершен!', 'cryptoschool');
        } else {
            $progress_percent = count($completed_tasks) > 0 ? round(count($completed_tasks) * 100 / count($tasks)) : 0;
            
            // Обновляем или создаем запись о прогрессе урока
            $existing_progress = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}cryptoschool_user_lesson_progress 
                 WHERE user_id = %d AND lesson_id = %d",
                $current_user_id, $lesson_id
            ));
            
            if ($existing_progress) {
                $wpdb->update(
                    $wpdb->prefix . 'cryptoschool_user_lesson_progress',
                    [
                        'is_completed' => 0,
                        'progress_percent' => $progress_percent,
                        'updated_at' => current_time('mysql')
                    ],
                    ['user_id' => $current_user_id, 'lesson_id' => $lesson_trid]
                );
            } else {
                $wpdb->insert(
                    $wpdb->prefix . 'cryptoschool_user_lesson_progress',
                    [
                        'user_id' => $current_user_id,
                        'lesson_id' => $lesson_trid,
                        'is_completed' => 0,
                        'progress_percent' => $progress_percent,
                        'updated_at' => current_time('mysql')
                    ]
                );
            }
            
            $form_success = true;
            $form_message = __('Прогресс сохранен. Для завершения урока выполните все задания.', 'cryptoschool');
        }
        
        // Обновляем данные прогресса пользователя
        $user_progress = $wpdb->get_row($wpdb->prepare($progress_query, $current_user_id, $lesson_trid));
        
        $form_submitted = true;
    } else {
        $form_submitted = true;
        $form_message = __('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.', 'cryptoschool');
    }
}

// Получаем обновленный прогресс по заданиям
$user_task_progress = [];
foreach ($tasks as $task) {
    $progress = $wpdb->get_var($wpdb->prepare(
        "SELECT is_completed FROM {$wpdb->prefix}cryptoschool_user_task_progress 
         WHERE user_id = %d AND lesson_id = %d AND task_id = %s",
        $current_user_id, $lesson_trid, $task->id
    ));
    $user_task_progress[$task->id] = (bool)$progress;
}

// Определяем, все ли задания выполнены
$all_tasks_completed = true;
foreach ($user_task_progress as $is_completed) {
    if (!$is_completed) {
        $all_tasks_completed = false;
        break;
    }
}

// Определяем, завершен ли урок
$is_lesson_completed = $user_progress ? $user_progress->is_completed : false;

// Теперь, когда все проверки и перенаправления выполнены, подключаем header
get_header();
?>

<main>
    <div class="container container_wide">
        <div class="account-layout">
            <!-- Боковая навигация -->
            <?php get_template_part('template-parts/account/sidebar-navigation'); ?>
            
            <div class="account-layout-column account-layout-center">
                <div class="account-layout-column-slice account-layout-center__top">
                    <!-- Пустой блок для соответствия верстке -->
                </div>

                <div class="account-layout-column-slice account-layout-center-bottom">
                    <!-- Содержимое урока -->
                    <article class="account-block palette palette_blurred account-article">
                        <div class="account-article__header">
                            <div class="account-article__header-column">
                                <h6 class="text-small account-article__pretitle">
                                    <?php 
                                    // Модуль - это курс, выводим название курса со ссылкой на страницу курса
                                    $course_id = isset($course_model->id) ? $course_model->id : (method_exists($course_model, 'getAttribute') ? $course_model->getAttribute('id') : null);
                                    $course_title = isset($course_model->title) ? $course_model->title : (method_exists($course_model, 'getAttribute') ? $course_model->getAttribute('title') : '');
                                    $course_url = cryptoschool_get_localized_url('/course/?id=' . $course_id);
                                    ?>
                                    <a href="<?php echo esc_url($course_url); ?>" class="color-primary">
                                        <?php echo esc_html($course_title); ?>
                                    </a>
                                </h6>
                                <h5 class="h6 color-primary account-article__title">
                                    <?php 
                                    $lesson_title = isset($lesson_model->title) ? $lesson_model->title : (method_exists($lesson_model, 'getAttribute') ? $lesson_model->getAttribute('title') : '');
                                    echo esc_html($lesson_title); 
                                    ?>
                                </h5>
                            </div>
                            <div class="account-article__header-column">
                                <button class="account-article__support">
                                    <span class="icon-telegram"></span>
                                    <span class="text-small"><?php _e('Потрібна допомога', 'cryptoschool'); ?></span>
                                </button>
                            </div>
                        </div>
                        
                        <hr class="account-block__horizontal-row account-article__separator">
                        
                        <div class="account-article-content">
                            <div class="account-article-content__block">
                                <?php 
                                // Устанавливаем глобальный $post для корректной работы the_content()
                                global $post;
                                $original_post = $post;
                                $post = $lesson_post;
                                setup_postdata($post);
                                
                                // Выводим контент урока через стандартную WordPress функцию
                                the_content();
                                
                                // Восстанавливаем оригинальный $post
                                $post = $original_post;
                                wp_reset_postdata();
                                ?>
                            </div>
                        </div>
                    </article>

                    <?php if (!empty($tasks)) : ?>
                        <!-- Задания урока -->
                        <div class="account-block palette palette_blurred completion-form lesson__form">
                            <h5 class="account-block__title h6"><?php _e('Підтвердити виконання', 'cryptoschool'); ?></h5>
                            
                            <hr class="account-block__horizontal-row completion-form__horizontal-row" />
                            
                            <?php if ($form_submitted) : ?>
                                <div class="lesson__message lesson__message_<?php echo $form_success ? 'success' : 'error'; ?>">
                                    <?php echo esc_html($form_message); ?>
                                </div>
                            <?php endif; ?>
                            
                            <form id="lesson-tasks-form" method="post" action="">
                                <?php wp_nonce_field('complete_lesson_' . $lesson_id, 'lesson_nonce'); ?>
                                
                                <div class="completion-form__fields">
                                    <?php foreach ($tasks as $index => $task) : ?>
                                        <div class="completion-form__field">
                                            <span class="checkbox">
                                                <input 
                                                    id="completion-form-<?php echo esc_attr($task->id); ?>" 
                                                    type="checkbox" 
                                                    class="checkbox__input" 
                                                    name="completed_tasks[]" 
                                                    value="<?php echo esc_attr($task->id); ?>" 
                                                    <?php checked($user_task_progress[$task->id], true); ?>
                                                    <?php disabled($is_lesson_completed, true); ?>
                                                >
                                                <label for="completion-form-<?php echo esc_attr($task->id); ?>" class="checkbox__body">
                                                    <span class="icon-checkbox-arrow checkbox__icon"></span>
                                                </label>
                                            </span>
                                            <label for="completion-form-<?php echo esc_attr($task->id); ?>" class="text color-primary">
                                                <?php echo esc_html($task->title); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <button 
                                    type="submit" 
                                    name="complete_lesson" 
                                    class="button button_filled button_rounded button_centered button_block" 
                                    <?php disabled($is_lesson_completed, true); ?>
                                >
                                    <span class="button__text">
                                        <?php echo $is_lesson_completed ? __('Урок завершен', 'cryptoschool') : __('Завдання виконано', 'cryptoschool'); ?>
                                    </span>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <!-- Навигация между уроками -->
                    <div class="bottom-navigation">
                        <?php if ($prev_lesson) : ?>
                            <!-- Если есть предыдущий урок, показываем кнопку "Попередній урок" -->
                            <a href="<?php echo esc_url(cryptoschool_get_localized_url('/lesson/?id=' . $prev_lesson->ID)); ?>" class="bottom-navigation__item bottom-navigation__previous">
                                <div class="bottom-navigation__arrow">
                                    <span class="icon-nav-arrow-left"></span>
                                </div>
                                <div class="bottom-navigation__label text-small"><?php _e('Попередній урок', 'cryptoschool'); ?></div>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($next_lesson) : ?>
                            <!-- Если есть следующий урок, показываем кнопку "Наступний урок" -->
                            <?php if ($is_lesson_completed) : ?>
                                <!-- Если текущий урок пройден, делаем ссылку активной -->
                                <a href="<?php echo esc_url(cryptoschool_get_localized_url('/lesson/?id=' . $next_lesson->ID)); ?>" class="bottom-navigation__item bottom-navigation__next">
                                    <div class="bottom-navigation__label text-small"><?php _e('Наступний урок', 'cryptoschool'); ?></div>
                                    <div class="bottom-navigation__arrow">
                                        <span class="icon-nav-arrow-right"></span>
                                    </div>
                                </a>
                            <?php else : ?>
                                <!-- Если текущий урок не пройден, делаем ссылку неактивной -->
                                <div class="bottom-navigation__item bottom-navigation__next bottom-navigation__item_disabled">
                                    <div class="bottom-navigation__label text-small"><?php _e('Наступний урок', 'cryptoschool'); ?></div>
                                    <div class="bottom-navigation__arrow">
                                        <span class="icon-nav-arrow-right"></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>


<script>
jQuery(document).ready(function($) {
    // Обработка чекбоксов заданий
    const taskCheckboxes = $('.checkbox__input');
    const submitButton = $('#lesson-tasks-form button[type="submit"]');
    
    // Проверяем, завершен ли урок
    const isLessonCompleted = <?php echo $is_lesson_completed ? 'true' : 'false'; ?>;
    
    // Функция проверки, все ли задания выполнены
    function checkAllTasksCompleted() {
        // Если урок уже завершен, не меняем состояние кнопки
        if (isLessonCompleted) {
            return;
        }
        
        let allCompleted = true;
        let totalCheckboxes = 0;
        let checkedCheckboxes = 0;
        
        // Проверяем каждый чекбокс
        taskCheckboxes.each(function() {
            totalCheckboxes++;
            if ($(this).prop('checked')) {
                checkedCheckboxes++;
            } else {
                allCompleted = false;
            }
        });
        
        console.log('Всего заданий: ' + totalCheckboxes + ', Выполнено: ' + checkedCheckboxes);
        
        // Активируем/деактивируем кнопку в зависимости от выполнения всех заданий
        if (allCompleted && totalCheckboxes > 0) {
            submitButton.prop('disabled', false);
            submitButton.removeClass('button_disabled');
        } else {
            submitButton.prop('disabled', true);
            submitButton.addClass('button_disabled');
        }
    }
    
    // Инициализация кнопки при загрузке страницы
    if (!isLessonCompleted) {
        // По умолчанию кнопка неактивна, если не все задания выполнены
        submitButton.prop('disabled', true);
        submitButton.addClass('button_disabled');
    }
    
    // Проверяем при загрузке страницы
    checkAllTasksCompleted();
    
    // Проверяем при изменении чекбоксов
    taskCheckboxes.on('change', function() {
        checkAllTasksCompleted();
    });
});
</script>

<style>
/* Стили для неактивной кнопки */
.button_disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Стили для сообщений */
.lesson__message {
    padding: 10px 15px;
    margin-bottom: 15px;
    border-radius: 5px;
    font-size: 14px;
}

.lesson__message_success {
    background-color: rgba(52, 199, 89, 0.1);
    color: #34c759;
    border: 1px solid rgba(52, 199, 89, 0.2);
}

.lesson__message_error {
    background-color: rgba(255, 59, 48, 0.1);
    color: #ff3b30;
    border: 1px solid rgba(255, 59, 48, 0.2);
}

/* Стили для неактивной кнопки навигации */
.bottom-navigation__item_disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
    background-color: rgba(255, 255, 255, 0.1);
}
</style>

<?php get_footer(); ?>
