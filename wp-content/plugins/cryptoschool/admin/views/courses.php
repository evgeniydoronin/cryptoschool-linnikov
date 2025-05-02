<?php
/**
 * Шаблон страницы управления курсами
 *
 * @package CryptoSchool
 * @subpackage Admin
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap cryptoschool-admin cryptoschool-courses-page">
    <h1 class="wp-heading-inline"><?php _e('Управление курсами', 'cryptoschool'); ?></h1>
    <a href="#" class="page-title-action add-new-course"><?php _e('Добавить курс', 'cryptoschool'); ?></a>
    <hr class="wp-header-end">

    <div class="cryptoschool-admin-notices"></div>

    <div class="cryptoschool-admin-content">
        <?php /* Временно скрыт блок фильтров
        <div class="cryptoschool-admin-filters">
            <div class="cryptoschool-filter-group">
                <label for="cryptoschool-filter-status"><?php _e('Статус:', 'cryptoschool'); ?></label>
                <select id="cryptoschool-filter-status" class="cryptoschool-filter">
                    <option value=""><?php _e('Все', 'cryptoschool'); ?></option>
                    <option value="1"><?php _e('Активные', 'cryptoschool'); ?></option>
                    <option value="0"><?php _e('Неактивные', 'cryptoschool'); ?></option>
                </select>
            </div>
            <div class="cryptoschool-filter-group">
                <label for="cryptoschool-filter-search"><?php _e('Поиск:', 'cryptoschool'); ?></label>
                <input type="text" id="cryptoschool-filter-search" class="cryptoschool-filter" placeholder="<?php _e('Поиск по названию...', 'cryptoschool'); ?>">
            </div>
            <div class="cryptoschool-filter-group">
                <button id="cryptoschool-filter-apply" class="button"><?php _e('Применить', 'cryptoschool'); ?></button>
                <button id="cryptoschool-filter-reset" class="button"><?php _e('Сбросить', 'cryptoschool'); ?></button>
            </div>
        </div>
        */ ?>

        <div class="cryptoschool-admin-table-container">
            <table class="wp-list-table widefat fixed striped cryptoschool-admin-table cryptoschool-courses-table">
                <thead>
                    <tr>
                        <th class="column-id"><?php _e('ID', 'cryptoschool'); ?></th>
                        <th class="column-title"><?php _e('Название', 'cryptoschool'); ?></th>
                        <th class="column-difficulty"><?php _e('Сложность', 'cryptoschool'); ?></th>
                        <th class="column-lessons"><?php _e('Уроки', 'cryptoschool'); ?></th>
                        <th class="column-status"><?php _e('Статус', 'cryptoschool'); ?></th>
                        <th class="column-actions"><?php _e('Действия', 'cryptoschool'); ?></th>
                    </tr>
                </thead>
                <tbody id="cryptoschool-courses-list">
                    <?php if (empty($courses)) : ?>
                        <tr>
                            <td colspan="7"><?php _e('Курсы не найдены.', 'cryptoschool'); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($courses as $course) : ?>
                            <tr data-id="<?php echo esc_attr((int)$course->getAttribute('id')); ?>">
                                <td class="column-id"><?php echo esc_html((int)$course->getAttribute('id')); ?></td>
                                <td class="column-title">
                                    <strong><?php echo esc_html($course->getAttribute('title')); ?></strong>
                                    <div class="row-actions">
                                        <span class="edit"><a href="#" class="edit-course" data-id="<?php echo esc_attr((int)$course->getAttribute('id')); ?>"><?php _e('Редактировать', 'cryptoschool'); ?></a> | </span>
                                        <span class="lessons"><a href="<?php echo esc_url(admin_url('admin.php?page=cryptoschool-lessons&course_id=' . (int)$course->getAttribute('id'))); ?>"><?php _e('Уроки', 'cryptoschool'); ?></a> | </span>
                                        <span class="delete"><a href="#" class="delete-course" data-id="<?php echo esc_attr((int)$course->getAttribute('id')); ?>"><?php _e('Удалить', 'cryptoschool'); ?></a></span>
                                    </div>
                                </td>
                                <td class="column-difficulty"><?php echo esc_html($course->getAttribute('difficulty_level')); ?></td>
                                <td class="column-lessons"><?php echo esc_html($course->get_lessons_count() ?? 0); ?></td>
                                <td class="column-status">
                                    <?php if ($course->getAttribute('is_active')) : ?>
                                        <span class="status-active"><?php _e('Активен', 'cryptoschool'); ?></span>
                                    <?php else : ?>
                                        <span class="status-inactive"><?php _e('Неактивен', 'cryptoschool'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="column-actions">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=cryptoschool-lessons&course_id=' . (int)$course->getAttribute('id'))); ?>" class="button button-small"><?php _e('Уроки', 'cryptoschool'); ?></a>
                                    <a href="#" class="edit-course dashicons dashicons-edit" data-id="<?php echo esc_attr((int)$course->getAttribute('id')); ?>" title="<?php _e('Редактировать', 'cryptoschool'); ?>"></a>
                                    <a href="#" class="delete-course dashicons dashicons-trash" data-id="<?php echo esc_attr((int)$course->getAttribute('id')); ?>" title="<?php _e('Удалить', 'cryptoschool'); ?>"></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// Начинаем буферизацию вывода для редактора
ob_start();
wp_editor(
    '', 
    'course-description', 
    array(
        'textarea_name' => 'description',
        'media_buttons' => true,
        'textarea_rows' => 10,
        'teeny' => false,
        'quicktags' => true,
        'tinymce' => true,
    )
); 
$editor_content = ob_get_clean();

// Модальное окно для добавления/редактирования курса
$course_modal_content = '
<form id="cryptoschool-course-form">
    <input type="hidden" id="course-id" name="id" value="0">
    
    <div class="cryptoschool-admin-form-row">
        <label for="course-title">' . __('Название курса', 'cryptoschool') . ' <span class="required">*</span></label>
        <input type="text" id="course-title" name="title" required>
    </div>
    
    <div class="cryptoschool-admin-form-row">
        <label for="course-description">' . __('Описание курса', 'cryptoschool') . '</label>
        <div id="course-description-editor-container">
            ' . $editor_content . '
        </div>
    </div>
    
    <div class="cryptoschool-admin-form-row">
        <label for="course-difficulty">' . __('Уровень сложности', 'cryptoschool') . '</label>
        <select id="course-difficulty" name="difficulty_level">
            <option value="beginner">' . __('Начинающий', 'cryptoschool') . '</option>
            <option value="intermediate">' . __('Средний', 'cryptoschool') . '</option>
            <option value="advanced">' . __('Продвинутый', 'cryptoschool') . '</option>
        </select>
    </div>
    
    <div class="cryptoschool-admin-form-row">
        <label for="course-thumbnail">' . __('Изображение курса', 'cryptoschool') . '</label>
        <div class="cryptoschool-media-upload">
            <input type="hidden" id="course-thumbnail" name="thumbnail" value="">
            <div id="course-thumbnail-preview" class="cryptoschool-thumbnail-preview"></div>
            <button type="button" id="course-thumbnail-upload" class="button">' . __('Выбрать изображение', 'cryptoschool') . '</button>
            <button type="button" id="course-thumbnail-remove" class="button" style="display: none;">' . __('Удалить изображение', 'cryptoschool') . '</button>
        </div>
    </div>
    
    <div class="cryptoschool-admin-form-row">
        <label for="course-completion-points">' . __('Баллы за завершение курса', 'cryptoschool') . '</label>
        <input type="number" id="course-completion-points" name="completion_points" min="0" value="0">
    </div>
    
    <div class="cryptoschool-admin-form-row">
        <label for="course-is-active">' . __('Статус курса', 'cryptoschool') . '</label>
        <select id="course-is-active" name="is_active">
            <option value="1">' . __('Активен', 'cryptoschool') . '</option>
            <option value="0">' . __('Неактивен', 'cryptoschool') . '</option>
        </select>
    </div>
    
    <div class="cryptoschool-admin-form-row">
        <button type="submit" class="button button-primary">' . __('Сохранить', 'cryptoschool') . '</button>
        <button type="button" class="button cryptoschool-modal-cancel">' . __('Отмена', 'cryptoschool') . '</button>
    </div>
</form>
';

// Отображение модального окна для добавления/редактирования курса
cryptoschool_render_modal(
    'cryptoschool-course-modal',
    __('Добавить курс', 'cryptoschool'),
    $course_modal_content
);

// Отображение модального окна для подтверждения удаления
cryptoschool_render_delete_modal(
    'cryptoschool-delete-modal',
    __('курс', 'cryptoschool')
);
?>

<script type="text/javascript">
// Определение объекта cryptoschool_admin, если он не определен
if (typeof window.cryptoschool_admin === 'undefined') {
    window.cryptoschool_admin = {
        ajax_url: ajaxurl,
        nonce: '<?php echo wp_create_nonce("cryptoschool_admin_nonce"); ?>',
        media_title: '<?php echo esc_js(__("Выберите изображение", "cryptoschool")); ?>',
        media_button: '<?php echo esc_js(__("Использовать это изображение", "cryptoschool")); ?>',
        media_select: '<?php echo esc_js(__("Выбрать изображение", "cryptoschool")); ?>',
        media_change: '<?php echo esc_js(__("Изменить изображение", "cryptoschool")); ?>',
        confirm_default: '<?php echo esc_js(__("Вы уверены?", "cryptoschool")); ?>',
        confirm_delete: '<?php echo esc_js(__("Вы уверены, что хотите удалить этот элемент? Это действие нельзя отменить.", "cryptoschool")); ?>',
        error_message: '<?php echo esc_js(__("Произошла ошибка. Пожалуйста, попробуйте еще раз.", "cryptoschool")); ?>',
        success_message: '<?php echo esc_js(__("Успешно сохранено!", "cryptoschool")); ?>',
        add_course_title: '<?php echo esc_js(__("Добавить курс", "cryptoschool")); ?>',
        edit_course_title: '<?php echo esc_js(__("Редактировать курс", "cryptoschool")); ?>',
        title_required: '<?php echo esc_js(__("Название курса обязательно для заполнения.", "cryptoschool")); ?>'
    };
}

</script>

<?php
// Подключаем скрипт для работы с редактором курсов
wp_enqueue_script(
    'cryptoschool-course-editor',
    plugin_dir_url(dirname(__FILE__)) . 'js/course-editor.js',
    array('jquery', 'wp-util', 'media-upload'),
    CRYPTOSCHOOL_VERSION,
    true
);
?>
