<?php
/**
 * Основной шаблон страницы урока
 * 
 * @package CryptoSchool
 */

if (!defined('ABSPATH')) {
    exit;
}

// Получаем данные из аргументов
$lesson = $args['lesson'];
$course = $args['course'];
$tasks = $args['tasks'];
$navigation = $args['navigation'];
$user_progress = $args['user_progress'];
$task_progress = $args['task_progress'];
$is_lesson_completed = $args['is_lesson_completed'];
$form_result = $args['form_result'];
$lesson_id = $args['lesson_id'];
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
                    <!-- Заголовок урока -->
                    <?php get_template_part('template-parts/lesson/lesson-header', null, [
                        'lesson' => $lesson,
                        'course' => $course
                    ]); ?>

                    <!-- Контент урока -->
                    <?php get_template_part('template-parts/lesson/lesson-content', null, [
                        'lesson' => $lesson
                    ]); ?>

                    <?php if (!empty($tasks)) : ?>
                        <!-- Задания урока -->
                        <?php get_template_part('template-parts/lesson/lesson-tasks', null, [
                            'tasks' => $tasks,
                            'task_progress' => $task_progress,
                            'is_lesson_completed' => $is_lesson_completed,
                            'form_result' => $form_result,
                            'lesson_id' => $lesson_id
                        ]); ?>
                    <?php endif; ?>

                    <!-- Навигация между уроками -->
                    <?php get_template_part('template-parts/lesson/lesson-navigation', null, [
                        'navigation' => $navigation,
                        'is_lesson_completed' => $is_lesson_completed
                    ]); ?>
                </div>
            </div>
        </div>
    </div>
</main>
