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
        <textarea id="course-description" name="description" rows="5"></textarea>
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
        success_message: '<?php echo esc_js(__("Успешно сохранено!", "cryptoschool")); ?>'
    };
}

jQuery(document).ready(function($) {
    // Инициализация медиа-загрузчика WordPress
    var mediaUploader;
    
    $('#course-thumbnail-upload').on('click', function(e) {
        e.preventDefault();
        
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        
        mediaUploader = wp.media({
            title: '<?php _e('Выберите изображение курса', 'cryptoschool'); ?>',
            button: {
                text: '<?php _e('Выбрать', 'cryptoschool'); ?>'
            },
            multiple: false
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#course-thumbnail').val(attachment.url);
            $('#course-thumbnail-preview').html('<img src="' + attachment.url + '" alt="">');
            $('#course-thumbnail-remove').show();
        });
        
        mediaUploader.open();
    });
    
    $('#course-thumbnail-remove').on('click', function(e) {
        e.preventDefault();
        $('#course-thumbnail').val('');
        $('#course-thumbnail-preview').html('');
        $(this).hide();
    });
    
    // Открытие модального окна для добавления курса
    $('.add-new-course').on('click', function(e) {
        e.preventDefault();
        $('#cryptoschool-course-modal-title').text('<?php _e('Добавить курс', 'cryptoschool'); ?>');
        $('#cryptoschool-course-form')[0].reset();
        $('#course-id').val(0);
        $('#course-thumbnail-preview').html('');
        $('#course-thumbnail-remove').hide();
        $('#cryptoschool-course-modal').show();
    });
    
    // Открытие модального окна для редактирования курса
    $(document).on('click', '.edit-course', function(e) {
        e.preventDefault();
        var courseId = $(this).data('id');
        
        console.log('Редактирование курса с ID:', courseId); // Добавляем отладочный вывод
        console.log('Тип ID:', typeof courseId); // Проверяем тип данных
        
        // Проверка, что ID курса является числом и больше 0
        if (!courseId || isNaN(courseId) || courseId <= 0) {
            alert('Некорректный ID курса: ' + courseId);
            return;
        }
        
        // Загрузка данных курса
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'cryptoschool_get_course',
                nonce: window.cryptoschool_admin ? window.cryptoschool_admin.nonce : '<?php echo wp_create_nonce('cryptoschool_admin_nonce'); ?>',
                id: parseInt(courseId, 10) // Явно преобразуем в целое число
            },
            success: function(response) {
                console.log('Ответ сервера:', response); // Добавляем отладочный вывод
                
                if (response.success) {
                    var course = response.data;
                    
                    $('#cryptoschool-course-modal-title').text('<?php _e('Редактировать курс', 'cryptoschool'); ?>');
                    $('#course-id').val(course.id);
                    $('#course-title').val(course.title || '');
                    $('#course-description').val(course.description || '');
                    $('#course-difficulty').val(course.difficulty_level || 'beginner');
                    $('#course-thumbnail').val(course.thumbnail || '');
                    $('#course-completion-points').val(course.completion_points || 0);
                    $('#course-is-active').val(course.is_active !== undefined ? course.is_active : 1);
                    
                    if (course.thumbnail) {
                        $('#course-thumbnail-preview').html('<img src="' + course.thumbnail + '" alt="">');
                        $('#course-thumbnail-remove').show();
                    } else {
                        $('#course-thumbnail-preview').html('');
                        $('#course-thumbnail-remove').hide();
                    }
                    
                    $('#cryptoschool-course-modal').show();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('<?php _e('Произошла ошибка при загрузке данных курса.', 'cryptoschool'); ?>');
            }
        });
    });
    
    // Сохранение курса
    $('#cryptoschool-course-form').on('submit', function(e) {
        e.preventDefault();
        
        var courseId = $('#course-id').val();
        var title = $('#course-title').val();
        var description = $('#course-description').val();
        var difficulty_level = $('#course-difficulty').val();
        var thumbnail = $('#course-thumbnail').val();
        var completion_points = $('#course-completion-points').val();
        var is_active = $('#course-is-active').val();
        
        var action = courseId > 0 ? 'cryptoschool_update_course' : 'cryptoschool_create_course';
        
        // Проверка обязательных полей
        if (!title) {
            alert('<?php _e('Название курса обязательно для заполнения.', 'cryptoschool'); ?>');
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: action,
                nonce: window.cryptoschool_admin ? window.cryptoschool_admin.nonce : '<?php echo wp_create_nonce('cryptoschool_admin_nonce'); ?>',
                id: courseId,
                title: title,
                description: description,
                difficulty_level: difficulty_level,
                thumbnail: thumbnail,
                completion_points: completion_points,
                is_active: is_active
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('<?php _e('Произошла ошибка при сохранении курса.', 'cryptoschool'); ?>');
            }
        });
    });
    
    // Открытие модального окна для подтверждения удаления
    $(document).on('click', '.delete-course', function(e) {
        e.preventDefault();
        var courseId = $(this).data('id');
        $('#cryptoschool-confirm-delete').data('id', courseId);
        $('#cryptoschool-delete-modal').show();
    });
    
    // Удаление курса
    $('#cryptoschool-confirm-delete').on('click', function() {
        var courseId = $(this).data('id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'cryptoschool_delete_course',
                nonce: window.cryptoschool_admin ? window.cryptoschool_admin.nonce : '<?php echo wp_create_nonce('cryptoschool_admin_nonce'); ?>',
                id: courseId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('<?php _e('Произошла ошибка при удалении курса.', 'cryptoschool'); ?>');
            }
        });
    });
    
    // Закрытие модальных окон
    $('.cryptoschool-admin-modal-close, .cryptoschool-modal-cancel').on('click', function() {
        $('.cryptoschool-admin-modal').hide();
    });
    
    // Закрытие модальных окон при клике на крестик или кнопку "Отмена"
    // Не закрываем модальные окна при клике вне содержимого, чтобы избежать случайного закрытия
    
    // Фильтрация курсов
    $('#cryptoschool-filter-apply').on('click', function() {
        var status = $('#cryptoschool-filter-status').val();
        var search = $('#cryptoschool-filter-search').val();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'cryptoschool_get_courses',
                nonce: window.cryptoschool_admin ? window.cryptoschool_admin.nonce : '<?php echo wp_create_nonce('cryptoschool_admin_nonce'); ?>',
                is_active: status,
                search: search
            },
            success: function(response) {
                if (response.success) {
                    var courses = response.data;
                    var html = '';
                    
                    if (courses.length === 0) {
                        html = '<tr><td colspan="7"><?php _e('Курсы не найдены.', 'cryptoschool'); ?></td></tr>';
                    } else {
                        for (var i = 0; i < courses.length; i++) {
                            var course = courses[i];
                            html += '<tr data-id="' + course.id + '">';
                            html += '<td class="column-id">' + course.id + '</td>';
                            html += '<td class="column-title">';
                            html += '<strong>' + course.title + '</strong>';
                            html += '<div class="row-actions">';
                            html += '<span class="edit"><a href="#" class="edit-course" data-id="' + course.id + '"><?php _e('Редактировать', 'cryptoschool'); ?></a> | </span>';
                            html += '<span class="lessons"><a href="<?php echo esc_url(admin_url('admin.php?page=cryptoschool-lessons&course_id=')); ?>' + course.id + '"><?php _e('Уроки', 'cryptoschool'); ?></a> | </span>';
                            html += '<span class="delete"><a href="#" class="delete-course" data-id="' + course.id + '"><?php _e('Удалить', 'cryptoschool'); ?></a></span>';
                            html += '</div>';
                            html += '</td>';
                            html += '<td class="column-difficulty">' + course.difficulty_level + '</td>';
                            html += '<td class="column-lessons">' + (course.lessons_count || 0) + '</td>';
                            html += '<td class="column-status">';
                            if (course.is_active == 1) {
                                html += '<span class="status-active"><?php _e('Активен', 'cryptoschool'); ?></span>';
                            } else {
                                html += '<span class="status-inactive"><?php _e('Неактивен', 'cryptoschool'); ?></span>';
                            }
                            html += '</td>';
                            html += '<td class="column-actions">';
                            html += '<a href="<?php echo esc_url(admin_url('admin.php?page=cryptoschool-lessons&course_id=')); ?>' + course.id + '" class="button button-small"><?php _e('Уроки', 'cryptoschool'); ?></a> ';
                            html += '<a href="#" class="edit-course dashicons dashicons-edit" data-id="' + course.id + '" title="<?php _e('Редактировать', 'cryptoschool'); ?>"></a> ';
                            html += '<a href="#" class="delete-course dashicons dashicons-trash" data-id="' + course.id + '" title="<?php _e('Удалить', 'cryptoschool'); ?>"></a>';
                            html += '</td>';
                            html += '</tr>';
                        }
                    }
                    
                    $('#cryptoschool-courses-list').html(html);
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('<?php _e('Произошла ошибка при загрузке курсов.', 'cryptoschool'); ?>');
            }
        });
    });
    
    // Сброс фильтров
    $('#cryptoschool-filter-reset').on('click', function() {
        $('#cryptoschool-filter-status').val('');
        $('#cryptoschool-filter-search').val('');
        $('#cryptoschool-filter-apply').click();
    });
});
</script>
