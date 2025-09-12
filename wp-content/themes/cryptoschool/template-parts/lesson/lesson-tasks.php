<?php
/**
 * Задания урока
 * 
 * @package CryptoSchool
 */

if (!defined('ABSPATH')) {
    exit;
}

$tasks = $args['tasks'];
$task_progress = $args['task_progress'];
$is_lesson_completed = $args['is_lesson_completed'];
$form_result = $args['form_result'];
$lesson_id = $args['lesson_id'];
?>

<div class="account-block palette palette_blurred completion-form lesson__form">
    <h5 class="account-block__title h6"><?php _e('Підтвердити виконання', 'cryptoschool'); ?></h5>
    
    <hr class="account-block__horizontal-row completion-form__horizontal-row" />
    
    <?php if ($form_result['submitted']) : ?>
        <div class="lesson__message lesson__message_<?php echo $form_result['success'] ? 'success' : 'error'; ?>">
            <?php echo esc_html($form_result['message']); ?>
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
                            <?php checked($task_progress[$task->id], true); ?>
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
