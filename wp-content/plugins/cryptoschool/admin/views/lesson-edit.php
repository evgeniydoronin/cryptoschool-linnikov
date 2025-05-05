<?php
/**
 * Шаблон страницы редактирования урока
 *
 * @package CryptoSchool
 * @subpackage Admin
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

// Переменные для шаблона
$lesson = isset($lesson) ? $lesson : null;
$course = isset($course) ? $course : null;
$course_id = isset($course_id) ? $course_id : 0;
$is_new = isset($is_new) ? $is_new : true;
$page_title = isset($page_title) ? $page_title : __('Урок', 'cryptoschool');

// Значения полей
$lesson_id = $lesson ? $lesson->id : 0;
$title = $lesson ? $lesson->title : '';
$content = $lesson ? $lesson->content : '';
$video_url = $lesson ? $lesson->video_url : '';
$lesson_order = $lesson ? $lesson->lesson_order : 0;
$completion_points = $lesson ? $lesson->completion_points : 5;
$is_active = $lesson ? $lesson->is_active : 1;
?>

<div class="wrap cryptoschool-admin cryptoschool-lesson-edit">
    <h1 class="wp-heading-inline"><?php echo esc_html($page_title); ?></h1>
    
    <?php if ($course): ?>
        <span class="subtitle"><?php printf(__('Курс: %s', 'cryptoschool'), esc_html($course->title)); ?></span>
    <?php endif; ?>
    
    <hr class="wp-header-end">
    
    <?php
    // Вывод сообщений об ошибках и успехе
    if (isset($_GET['message']) && $_GET['message'] === 'success') {
        $message_text = isset($_GET['message_text']) ? urldecode($_GET['message_text']) : __('Урок успешно сохранен.', 'cryptoschool');
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message_text) . '</p></div>';
    }
    ?>
    
    <form id="cryptoschool-lesson-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('cryptoschool_save_lesson', 'cryptoschool_lesson_nonce'); ?>
        <input type="hidden" name="action" value="cryptoschool_save_lesson">
        <input type="hidden" name="lesson_id" value="<?php echo esc_attr($lesson_id); ?>">
        <input type="hidden" name="course_id" value="<?php echo esc_attr($course_id); ?>">
        
        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <div id="titlediv">
                        <div id="titlewrap">
                            <label class="screen-reader-text" id="title-prompt-text" for="title"><?php _e('Название урока', 'cryptoschool'); ?></label>
                            <input type="text" name="title" size="30" id="title" value="<?php echo esc_attr($title); ?>" placeholder="<?php _e('Введите название урока', 'cryptoschool'); ?>" required>
                        </div>
                    </div>
                    
                    <div id="postdivrich" class="postarea wp-editor-expand">
                        <?php
                        wp_editor($content, 'content', [
                            'textarea_name' => 'content',
                            'media_buttons' => true,
                            'textarea_rows' => 20,
                            'editor_height' => 400,
                            'teeny' => false,
                            'dfw' => true,
                            'tinymce' => true,
                            'quicktags' => true,
                        ]);
                        ?>
                    </div>
                </div>
                
                <div id="postbox-container-1" class="postbox-container">
                    <div id="submitdiv" class="postbox">
                        <h2 class="hndle ui-sortable-handle"><span><?php _e('Опубликовать', 'cryptoschool'); ?></span></h2>
                        <div class="inside">
                            <div class="submitbox" id="submitpost">
                                <div id="minor-publishing">
                                    <div id="misc-publishing-actions">
                                        <div class="misc-pub-section">
                                            <label for="lesson-is-active"><?php _e('Статус:', 'cryptoschool'); ?></label>
                                            <select id="lesson-is-active" name="is_active">
                                                <option value="1" <?php selected($is_active, 1); ?>><?php _e('Активен', 'cryptoschool'); ?></option>
                                                <option value="0" <?php selected($is_active, 0); ?>><?php _e('Неактивен', 'cryptoschool'); ?></option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="major-publishing-actions">
                                    <div id="publishing-action">
                                        <input type="submit" name="cryptoschool_save_lesson" id="publish" class="button button-primary button-large" value="<?php echo $is_new ? __('Создать урок', 'cryptoschool') : __('Обновить урок', 'cryptoschool'); ?>">
                                    </div>
                                    <div class="clear"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="lesson-details" class="postbox">
                        <h2 class="hndle ui-sortable-handle"><span><?php _e('Детали урока', 'cryptoschool'); ?></span></h2>
                        <div class="inside">
                            <div class="cryptoschool-admin-form-row">
                                <label for="lesson-video-url"><?php _e('URL видео', 'cryptoschool'); ?></label>
                                <input type="url" id="lesson-video-url" name="video_url" value="<?php echo esc_attr($video_url); ?>" class="widefat">
                                <p class="description"><?php _e('Укажите URL видео (YouTube, Vimeo и т.д.).', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row">
                                <label for="lesson-order"><?php _e('Порядок отображения', 'cryptoschool'); ?></label>
                                <input type="number" id="lesson-order" name="lesson_order" min="0" value="<?php echo esc_attr($lesson_order); ?>" class="widefat">
                            </div>
                            
                            <div class="cryptoschool-admin-form-row">
                                <label for="lesson-completion-points"><?php _e('Баллы за завершение урока', 'cryptoschool'); ?></label>
                                <input type="number" id="lesson-completion-points" name="completion_points" min="0" value="<?php echo esc_attr($completion_points); ?>" class="widefat">
                            </div>
                        </div>
                    </div>
                    
                    <div id="lesson-tasks" class="postbox">
                        <h2 class="hndle ui-sortable-handle"><span><?php _e('Задания урока', 'cryptoschool'); ?></span></h2>
                        <div class="inside">
                            <p class="description"><?php _e('Добавьте задания, которые студент должен выполнить для завершения урока.', 'cryptoschool'); ?></p>
                            
                            <div id="lesson-tasks-container">
                                <?php
                                // Получаем задания урока, если урок существует
                                $tasks = [];
                                if ($lesson_id) {
                                    $task_repository = new CryptoSchool_Repository_Lesson_Task();
                                    $tasks = $task_repository->get_lesson_tasks($lesson_id);
                                }
                                
                                // Если заданий нет, создаем пустой шаблон
                                if (empty($tasks)) {
                                    ?>
                                    <div class="lesson-task-item" data-task-id="0">
                                        <div class="lesson-task-header">
                                            <span class="lesson-task-number">1</span>
                                            <button type="button" class="button button-small lesson-task-remove"><?php _e('Удалить', 'cryptoschool'); ?></button>
                                        </div>
                                        <div class="lesson-task-content">
                                            <input type="hidden" name="task_ids[]" value="0">
                                            <input type="text" name="task_titles[]" class="widefat" placeholder="<?php _e('Введите текст задания', 'cryptoschool'); ?>">
                                        </div>
                                    </div>
                                    <?php
                                } else {
                                    // Выводим существующие задания
                                    foreach ($tasks as $index => $task) {
                                        ?>
                                        <div class="lesson-task-item" data-task-id="<?php echo esc_attr($task->id); ?>">
                                            <div class="lesson-task-header">
                                                <span class="lesson-task-number"><?php echo esc_html($index + 1); ?></span>
                                                <button type="button" class="button button-small lesson-task-remove"><?php _e('Удалить', 'cryptoschool'); ?></button>
                                            </div>
                                            <div class="lesson-task-content">
                                                <input type="hidden" name="task_ids[]" value="<?php echo esc_attr($task->id); ?>">
                                                <input type="text" name="task_titles[]" class="widefat" value="<?php echo esc_attr($task->title); ?>" placeholder="<?php _e('Введите текст задания', 'cryptoschool'); ?>">
                                            </div>
                                        </div>
                                        <?php
                                    }
                                }
                                ?>
                            </div>
                            
                            <div class="lesson-task-actions">
                                <button type="button" id="add-lesson-task" class="button"><?php _e('Добавить задание', 'cryptoschool'); ?></button>
                            </div>
                            
                            <style>
                                #lesson-tasks-container {
                                    margin-bottom: 15px;
                                }
                                .lesson-task-item {
                                    background: #f9f9f9;
                                    border: 1px solid #ddd;
                                    margin-bottom: 10px;
                                    padding: 10px;
                                    border-radius: 3px;
                                }
                                .lesson-task-header {
                                    display: flex;
                                    justify-content: space-between;
                                    align-items: center;
                                    margin-bottom: 10px;
                                }
                                .lesson-task-number {
                                    font-weight: bold;
                                }
                                .lesson-task-content {
                                    margin-bottom: 5px;
                                }
                                .lesson-task-actions {
                                    margin-top: 15px;
                                }
                            </style>
                            
                            <script>
                                jQuery(document).ready(function($) {
                                    // Добавление нового задания
                                    $('#add-lesson-task').on('click', function() {
                                        var taskCount = $('.lesson-task-item').length;
                                        var newTask = `
                                            <div class="lesson-task-item" data-task-id="0">
                                                <div class="lesson-task-header">
                                                    <span class="lesson-task-number">${taskCount + 1}</span>
                                                    <button type="button" class="button button-small lesson-task-remove"><?php _e('Удалить', 'cryptoschool'); ?></button>
                                                </div>
                                                <div class="lesson-task-content">
                                                    <input type="hidden" name="task_ids[]" value="0">
                                                    <input type="text" name="task_titles[]" class="widefat" placeholder="<?php _e('Введите текст задания', 'cryptoschool'); ?>">
                                                </div>
                                            </div>
                                        `;
                                        $('#lesson-tasks-container').append(newTask);
                                        updateTaskNumbers();
                                    });
                                    
                                    // Удаление задания
                                    $(document).on('click', '.lesson-task-remove', function() {
                                        $(this).closest('.lesson-task-item').remove();
                                        updateTaskNumbers();
                                    });
                                    
                                    // Обновление нумерации заданий
                                    function updateTaskNumbers() {
                                        $('.lesson-task-item').each(function(index) {
                                            $(this).find('.lesson-task-number').text(index + 1);
                                        });
                                    }
                                });
                            </script>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
