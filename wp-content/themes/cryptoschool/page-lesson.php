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

get_header();

// Получаем ID урока из GET-параметра
$lesson_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Получаем текущего пользователя
$current_user_id = get_current_user_id();

// Получаем данные урока из базы данных
$lesson_repository = new CryptoSchool_Repository_Lesson();
$lesson_model = $lesson_repository->find($lesson_id);

// Если урок не найден, перенаправляем на страницу списка курсов
if (!$lesson_model) {
    wp_redirect(site_url('/courses/'));
    exit;
}

// Получаем данные курса
$course_repository = new CryptoSchool_Repository_Course();
$course_model = $course_repository->find($lesson_model->getAttribute('course_id'));

// Если курс не найден, перенаправляем на страницу списка курсов
if (!$course_model) {
    wp_redirect(site_url('/courses/'));
    exit;
}

// Проверяем доступность курса для пользователя
$is_course_available = $course_model->is_available_for_user($current_user_id);
if (!$is_course_available) {
    wp_redirect(site_url('/courses/'));
    exit;
}

// Получаем прогресс пользователя по уроку
$user_lesson_progress_repository = new CryptoSchool_Repository_User_Lesson_Progress();
$user_progress = $user_lesson_progress_repository->get_user_lesson_progress($current_user_id, $lesson_id);

// Получаем задания урока
$task_repository = new CryptoSchool_Repository_Lesson_Task();
$tasks = $task_repository->get_lesson_tasks($lesson_id);

// Получаем прогресс пользователя по заданиям
$user_task_progress_repository = new CryptoSchool_Repository_User_Task_Progress();
$user_task_progress = [];
foreach ($tasks as $task) {
    $progress = $user_task_progress_repository->get_user_task_progress($current_user_id, $task->id);
    $user_task_progress[$task->id] = $progress ? $progress->is_completed : false;
}

// Определяем, все ли задания выполнены
$all_tasks_completed = true;
foreach ($user_task_progress as $is_completed) {
    if (!$is_completed) {
        $all_tasks_completed = false;
        break;
    }
}

// Получаем предыдущий и следующий уроки
$prev_lesson = $lesson_repository->get_previous_lesson($lesson_id);
$next_lesson = $lesson_repository->get_next_lesson($lesson_id);

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
            $user_task_progress_repository->update_progress($current_user_id, $lesson_id, $task->id, $is_completed);
        }
        
        // Если все задания выполнены, отмечаем урок как завершенный
        if (count($completed_tasks) === count($tasks)) {
            $user_lesson_progress_repository->update_progress($current_user_id, $lesson_id, true, 100);
            $form_success = true;
            $form_message = __('Урок успешно завершен!', 'cryptoschool');
            
            // Обновляем прогресс пользователя
            $user_progress = $user_lesson_progress_repository->get_user_lesson_progress($current_user_id, $lesson_id);
        } else {
            $progress_percent = count($completed_tasks) * 100 / count($tasks);
            $user_lesson_progress_repository->update_progress($current_user_id, $lesson_id, false, $progress_percent);
            $form_success = true;
            $form_message = __('Прогресс сохранен. Для завершения урока выполните все задания.', 'cryptoschool');
            
            // Обновляем прогресс пользователя
            $user_progress = $user_lesson_progress_repository->get_user_lesson_progress($current_user_id, $lesson_id);
        }
        
        $form_submitted = true;
    } else {
        $form_submitted = true;
        $form_message = __('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.', 'cryptoschool');
    }
}

// Получаем обновленный прогресс по заданиям
$user_task_progress = [];
foreach ($tasks as $task) {
    $progress = $user_task_progress_repository->get_user_task_progress($current_user_id, $task->id);
    $user_task_progress[$task->id] = $progress ? $progress->is_completed : false;
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
                                    // Получаем название модуля
                                    $module_title = $lesson_model->getAttribute('module_title') ?: __('Модуль', 'cryptoschool');
                                    $module_number = $lesson_model->getAttribute('module_order') ?: 1;
                                    echo esc_html($module_title . ' ' . $module_number); 
                                    ?>
                                </h6>
                                <h5 class="h6 color-primary account-article__title">
                                    <?php echo esc_html($lesson_model->getAttribute('title')); ?>
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
                            <?php if ($lesson_model->getAttribute('video_url')) : ?>
                                <div class="account-article-content__block account-article-content__images">
                                    <div class="video-container">
                                        <?php
                                        // Получаем ID видео из URL
                                        $video_url = $lesson_model->getAttribute('video_url');
                                        $video_id = '';
                                        
                                        // YouTube
                                        if (strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false) {
                                            if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $video_url, $matches)) {
                                                $video_id = $matches[1];
                                                echo '<iframe width="100%" height="100%" src="https://www.youtube.com/embed/' . esc_attr($video_id) . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
                                            }
                                        }
                                        // Vimeo
                                        elseif (strpos($video_url, 'vimeo.com') !== false) {
                                            if (preg_match('/vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/(?:[^\/]*)\/videos\/|album\/(?:\d+)\/video\/|)(\d+)(?:$|\/|\?)/', $video_url, $matches)) {
                                                $video_id = $matches[1];
                                                echo '<iframe src="https://player.vimeo.com/video/' . esc_attr($video_id) . '" width="100%" height="100%" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>';
                                            }
                                        }
                                        // Другие видео-сервисы
                                        else {
                                            echo '<div class="video-fallback">';
                                            echo '<a href="' . esc_url($video_url) . '" target="_blank" class="button button_filled button_rounded">';
                                            echo '<span class="button__text">' . __('Смотреть видео', 'cryptoschool') . '</span>';
                                            echo '</a>';
                                            echo '</div>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="account-article-content__block">
                                <?php echo wp_kses_post($lesson_model->getAttribute('content')); ?>
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
                            <a href="<?php echo esc_url(site_url('/lesson/?id=' . $prev_lesson->getAttribute('id'))); ?>" class="bottom-navigation__item bottom-navigation__previous">
                                <div class="bottom-navigation__arrow">
                                    <span class="icon-nav-arrow-left"></span>
                                </div>
                                <div class="bottom-navigation__label text-small"><?php _e('Попередній урок', 'cryptoschool'); ?></div>
                            </a>
                        <?php else: ?>
                            <a href="<?php echo esc_url(site_url('/course/?id=' . $course_model->getAttribute('id'))); ?>" class="bottom-navigation__item bottom-navigation__previous hide-mobile">
                                <div class="bottom-navigation__arrow">
                                    <span class="icon-nav-arrow-left"></span>
                                </div>
                                <div class="bottom-navigation__label text-small"><?php _e('До курсу', 'cryptoschool'); ?></div>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($next_lesson) : ?>
                            <a href="<?php echo esc_url(site_url('/lesson/?id=' . $next_lesson->getAttribute('id'))); ?>" class="bottom-navigation__item bottom-navigation__next">
                                <div class="bottom-navigation__label text-small"><?php _e('Наступний урок', 'cryptoschool'); ?></div>
                                <div class="bottom-navigation__arrow">
                                    <span class="icon-nav-arrow-right"></span>
                                </div>
                            </a>
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
    
    // Функция проверки, все ли задания выполнены
    function checkAllTasksCompleted() {
        let allCompleted = true;
        taskCheckboxes.each(function() {
            if (!$(this).prop('checked')) {
                allCompleted = false;
                return false; // Прерываем цикл
            }
        });
        
        // Если урок уже завершен, кнопка всегда неактивна
        if (submitButton.prop('disabled')) {
            return;
        }
        
        // Активируем/деактивируем кнопку в зависимости от выполнения всех заданий
        if (allCompleted) {
            submitButton.prop('disabled', false);
        } else {
            submitButton.prop('disabled', true);
        }
    }
    
    // Проверяем при загрузке страницы
    checkAllTasksCompleted();
    
    // Проверяем при изменении чекбоксов
    taskCheckboxes.on('change', function() {
        checkAllTasksCompleted();
    });
});
</script>

<?php get_footer(); ?>
