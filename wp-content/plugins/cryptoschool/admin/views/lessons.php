<?php
/**
 * Шаблон страницы управления уроками
 *
 * @package CryptoSchool
 * @subpackage Admin
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

// Переменные для шаблона
$course_id = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;
$all_courses = isset($all_courses) ? $all_courses : false;
$course = isset($course) ? $course : null;

// Если не отображаются все курсы и курс не найден
if (!$all_courses && !$course) {
    wp_die(__('Курс не найден.', 'cryptoschool'));
}
?>

<div class="wrap cryptoschool-admin cryptoschool-lessons-page">
    <h1 class="wp-heading-inline">
        <?php if ($all_courses): ?>
            <?php _e('Все уроки', 'cryptoschool'); ?>
        <?php elseif ($course): ?>
            <?php printf(__('Уроки курса: %s', 'cryptoschool'), esc_html($course->title)); ?>
        <?php else: ?>
            <?php _e('Уроки', 'cryptoschool'); ?>
        <?php endif; ?>
    </h1>
    <?php if (!$all_courses && $course): ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=cryptoschool-add-lesson&course_id=' . $course_id)); ?>" class="page-title-action"><?php _e('Добавить урок', 'cryptoschool'); ?></a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=cryptoschool-courses')); ?>" class="page-title-action"><?php _e('Назад к курсам', 'cryptoschool'); ?></a>
    <?php else: ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=cryptoschool-courses')); ?>" class="page-title-action"><?php _e('Назад к курсам', 'cryptoschool'); ?></a>
    <?php endif; ?>
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
            <table class="wp-list-table widefat fixed striped cryptoschool-admin-table cryptoschool-lessons-table">
                <thead>
                    <tr>
                        <th class="column-id"><?php _e('ID', 'cryptoschool'); ?></th>
                        <th class="column-order"><?php _e('Порядок', 'cryptoschool'); ?></th>
                        <?php if ($all_courses): ?>
                        <th class="column-course"><?php _e('Курс', 'cryptoschool'); ?></th>
                        <?php endif; ?>
                        <th class="column-title"><?php _e('Название', 'cryptoschool'); ?></th>
                        <th class="column-video"><?php _e('Видео', 'cryptoschool'); ?></th>
                        <th class="column-points"><?php _e('Баллы', 'cryptoschool'); ?></th>
                        <th class="column-status"><?php _e('Статус', 'cryptoschool'); ?></th>
                        <th class="column-actions"><?php _e('Действия', 'cryptoschool'); ?></th>
                    </tr>
                </thead>
                <tbody id="cryptoschool-lessons-list">
                    <?php if (empty($lessons)) : ?>
                        <tr>
                            <td colspan="<?php echo $all_courses ? 9 : 7; ?>"><?php _e('Уроки не найдены.', 'cryptoschool'); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($lessons as $lesson) : ?>
                            <tr data-id="<?php echo esc_attr($lesson->id); ?>">
                                <td class="column-id"><?php echo esc_html($lesson->id); ?></td>
                                <td class="column-order">
                                    <span class="lesson-order"><?php echo esc_html($lesson->lesson_order); ?></span>
                                </td>
                                <?php if ($all_courses): ?>
                                <td class="column-course">
                                    <?php echo esc_html($lesson->course_title); ?>
                                </td>
                                <?php endif; ?>
                                <td class="column-title">
                                    <strong>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=cryptoschool-edit-lesson&lesson_id=' . $lesson->id)); ?>" class="row-title">
                                            <?php echo esc_html($lesson->title); ?>
                                        </a>
                                    </strong>
                                    <div class="row-actions">
                                        <span class="edit">
                                            <a href="<?php echo esc_url(admin_url('admin.php?page=cryptoschool-edit-lesson&lesson_id=' . $lesson->id)); ?>">
                                                <?php _e('Редактировать', 'cryptoschool'); ?>
                                            </a> | 
                                        </span>
                                        <span class="delete"><a href="#" class="delete-lesson" data-id="<?php echo esc_attr($lesson->id); ?>"><?php _e('Удалить', 'cryptoschool'); ?></a></span>
                                    </div>
                                </td>
                                <td class="column-video">
                                    <?php if (!empty($lesson->video_url)) : ?>
                                        <a href="<?php echo esc_url($lesson->video_url); ?>" target="_blank"><?php _e('Просмотр', 'cryptoschool'); ?></a>
                                    <?php else : ?>
                                        <span class="no-video"><?php _e('Нет видео', 'cryptoschool'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="column-points"><?php echo esc_html($lesson->completion_points); ?></td>
                                <td class="column-status">
                                    <?php if ($lesson->is_active) : ?>
                                        <span class="status-active"><?php _e('Активен', 'cryptoschool'); ?></span>
                                    <?php else : ?>
                                        <span class="status-inactive"><?php _e('Неактивен', 'cryptoschool'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="column-actions">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=cryptoschool-edit-lesson&lesson_id=' . $lesson->id)); ?>" class="dashicons dashicons-edit" title="<?php _e('Редактировать', 'cryptoschool'); ?>"></a>
                                    <a href="#" class="delete-lesson dashicons dashicons-trash" data-id="<?php echo esc_attr($lesson->id); ?>" title="<?php _e('Удалить', 'cryptoschool'); ?>"></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Модальное окно для добавления/редактирования урока -->
<div id="cryptoschool-lesson-modal" class="cryptoschool-admin-modal" style="display: none;">
    <div class="cryptoschool-admin-modal-content cryptoschool-admin-modal-content-large">
        <span class="cryptoschool-admin-modal-close">&times;</span>
        <h2 id="cryptoschool-lesson-modal-title"><?php _e('Добавить урок', 'cryptoschool'); ?></h2>
        
        <form id="cryptoschool-lesson-form">
            <input type="hidden" id="lesson-id" name="id" value="0">
            <input type="hidden" id="lesson-course-id" name="course_id" value="<?php echo esc_attr($course_id); ?>">
            
            <div class="cryptoschool-admin-form-row">
                <label for="lesson-title"><?php _e('Название урока', 'cryptoschool'); ?> <span class="required">*</span></label>
                <input type="text" id="lesson-title" name="title" required>
            </div>
            
            <div class="cryptoschool-admin-form-row">
                <label for="lesson-content"><?php _e('Содержимое урока', 'cryptoschool'); ?></label>
                <?php
                wp_editor('', 'lesson-content', [
                    'textarea_name' => 'content',
                    'media_buttons' => true,
                    'textarea_rows' => 10,
                    'teeny' => false,
                ]);
                ?>
            </div>
            
            <div class="cryptoschool-admin-form-row">
                <label for="lesson-video-url"><?php _e('URL видео', 'cryptoschool'); ?></label>
                <input type="url" id="lesson-video-url" name="video_url">
                <p class="description"><?php _e('Укажите URL видео (YouTube, Vimeo и т.д.).', 'cryptoschool'); ?></p>
            </div>
            
            <div class="cryptoschool-admin-form-row">
                <label for="lesson-order"><?php _e('Порядок отображения', 'cryptoschool'); ?></label>
                <input type="number" id="lesson-order" name="lesson_order" min="0" value="0">
            </div>
            
            <div class="cryptoschool-admin-form-row">
                <label for="lesson-completion-points"><?php _e('Баллы за завершение урока', 'cryptoschool'); ?></label>
                <input type="number" id="lesson-completion-points" name="completion_points" min="0" value="5">
            </div>
            
            <div class="cryptoschool-admin-form-row">
                <label for="lesson-is-active"><?php _e('Статус урока', 'cryptoschool'); ?></label>
                <select id="lesson-is-active" name="is_active">
                    <option value="1"><?php _e('Активен', 'cryptoschool'); ?></option>
                    <option value="0"><?php _e('Неактивен', 'cryptoschool'); ?></option>
                </select>
            </div>
            
            <div class="cryptoschool-admin-form-row">
                <button type="submit" class="button button-primary"><?php _e('Сохранить', 'cryptoschool'); ?></button>
                <button type="button" class="button cryptoschool-modal-cancel"><?php _e('Отмена', 'cryptoschool'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Модальное окно для подтверждения удаления -->
<div id="cryptoschool-delete-modal" class="cryptoschool-admin-modal" style="display: none;">
    <div class="cryptoschool-admin-modal-content">
        <span class="cryptoschool-admin-modal-close">&times;</span>
        <h2><?php _e('Подтверждение удаления', 'cryptoschool'); ?></h2>
        
        <p><?php _e('Вы уверены, что хотите удалить этот урок? Это действие нельзя отменить.', 'cryptoschool'); ?></p>
        
        <div class="cryptoschool-admin-form-row">
            <button type="button" id="cryptoschool-confirm-delete" class="button button-primary" data-id="0"><?php _e('Удалить', 'cryptoschool'); ?></button>
            <button type="button" class="button cryptoschool-modal-cancel"><?php _e('Отмена', 'cryptoschool'); ?></button>
        </div>
    </div>
</div>

<script type="text/javascript">
// Определение объекта cryptoschool_admin если он не определен
if (typeof window.cryptoschool_admin === 'undefined') {
    window.cryptoschool_admin = {
        nonce: '<?php echo wp_create_nonce("cryptoschool_admin_nonce"); ?>'
    };
}

jQuery(document).ready(function($) {
    // Открытие модального окна для добавления урока
    $('.add-new-lesson').on('click', function(e) {
        e.preventDefault();
        $('#cryptoschool-lesson-modal-title').text('<?php _e('Добавить урок', 'cryptoschool'); ?>');
        $('#cryptoschool-lesson-form')[0].reset();
        $('#lesson-id').val(0);
        
        // Очистка редактора
        if (typeof tinyMCE !== 'undefined' && tinyMCE.get('lesson-content')) {
            tinyMCE.get('lesson-content').setContent('');
        }
        
        $('#cryptoschool-lesson-modal').show();
    });
    
    // Открытие модального окна для редактирования урока
    $(document).on('click', '.edit-lesson', function(e) {
        e.preventDefault();
        var lessonId = $(this).data('id');
        
        // Загрузка данных урока
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'cryptoschool_get_lesson',
                nonce: cryptoschool_admin.nonce,
                id: lessonId
            },
            success: function(response) {
                if (response.success) {
                    var lesson = response.data;
                    
                    $('#cryptoschool-lesson-modal-title').text('<?php _e('Редактировать урок', 'cryptoschool'); ?>');
                    $('#lesson-id').val(lesson.id);
                    $('#lesson-course-id').val(lesson.course_id);
                    $('#lesson-title').val(lesson.title);
                    
                    // Установка содержимого в редактор
                    if (typeof tinyMCE !== 'undefined' && tinyMCE.get('lesson-content')) {
                        tinyMCE.get('lesson-content').setContent(lesson.content);
                    } else {
                        $('#lesson-content').val(lesson.content);
                    }
                    
                    $('#lesson-video-url').val(lesson.video_url);
                    $('#lesson-order').val(lesson.lesson_order);
                    $('#lesson-completion-points').val(lesson.completion_points);
                    $('#lesson-is-active').val(lesson.is_active);
                    
                    $('#cryptoschool-lesson-modal').show();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('<?php _e('Произошла ошибка при загрузке данных урока.', 'cryptoschool'); ?>');
            }
        });
    });
    
    // Сохранение урока
    $('#cryptoschool-lesson-form').on('submit', function(e) {
        e.preventDefault();
        
        // Получение содержимого из редактора
        if (typeof tinyMCE !== 'undefined' && tinyMCE.get('lesson-content')) {
            var content = tinyMCE.get('lesson-content').getContent();
            $('#lesson-content').val(content);
        }
        
        // Отладочный вывод для проверки наличия полей формы
        console.log('Поле lesson-id существует:', $('#lesson-id').length > 0);
        console.log('Поле lesson-course-id существует:', $('#lesson-course-id').length > 0);
        console.log('Поле lesson-title существует:', $('#lesson-title').length > 0);
        console.log('Поле lesson-content существует:', $('#lesson-content').length > 0);
        console.log('Поле lesson-video-url существует:', $('#lesson-video-url').length > 0);
        console.log('Поле lesson-order существует:', $('#lesson-order').length > 0);
        console.log('Поле lesson-completion-points существует:', $('#lesson-completion-points').length > 0);
        console.log('Поле lesson-is-active существует:', $('#lesson-is-active').length > 0);
        
        var lessonId = $('#lesson-id').val();
        var courseId = $('#lesson-course-id').val();
        var title = $('#lesson-title').val();
        var content = $('#lesson-content').val();
        var videoUrl = $('#lesson-video-url').val();
        var lessonOrder = $('#lesson-order').val();
        var completionPoints = $('#lesson-completion-points').val();
        var isActive = $('#lesson-is-active').val();
        var action = lessonId > 0 ? 'cryptoschool_update_lesson' : 'cryptoschool_create_lesson';
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: action,
                nonce: cryptoschool_admin.nonce,
                id: lessonId,
                course_id: courseId,
                title: title,
                content: content,
                video_url: videoUrl,
                lesson_order: lessonOrder,
                completion_points: completionPoints,
                is_active: isActive
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('<?php _e('Произошла ошибка при сохранении урока.', 'cryptoschool'); ?>');
            }
        });
    });
    
    // Открытие модального окна для подтверждения удаления
    $(document).on('click', '.delete-lesson', function(e) {
        e.preventDefault();
        var lessonId = $(this).data('id');
        $('#cryptoschool-confirm-delete').data('id', lessonId);
        $('#cryptoschool-delete-modal').show();
    });
    
    // Удаление урока
    $('#cryptoschool-confirm-delete').on('click', function() {
        var lessonId = $(this).data('id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'cryptoschool_delete_lesson',
                nonce: cryptoschool_admin.nonce,
                id: lessonId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('<?php _e('Произошла ошибка при удалении урока.', 'cryptoschool'); ?>');
            }
        });
    });
    
    // Перемещение урока вверх
    $(document).on('click', '.move-up', function(e) {
        e.preventDefault();
        var row = $(this).closest('tr');
        var prevRow = row.prev('tr');
        
        if (prevRow.length) {
            var lessonId = row.data('id');
            var prevLessonId = prevRow.data('id');
            var lessonOrder = parseInt(row.find('.lesson-order').text());
            var prevLessonOrder = parseInt(prevRow.find('.lesson-order').text());
            
            // Обмен порядковыми номерами
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'cryptoschool_update_lesson_order',
                    nonce: cryptoschool_admin.nonce,
                    lesson_orders: [
                        { id: lessonId, lesson_order: prevLessonOrder },
                        { id: prevLessonId, lesson_order: lessonOrder }
                    ]
                },
                success: function(response) {
                    if (response.success) {
                        // Обновление отображения
                        row.find('.lesson-order').text(prevLessonOrder);
                        prevRow.find('.lesson-order').text(lessonOrder);
                        
                        // Перемещение строки
                        row.insertBefore(prevRow);
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    alert('<?php _e('Произошла ошибка при обновлении порядка уроков.', 'cryptoschool'); ?>');
                }
            });
        }
    });
    
    // Перемещение урока вниз
    $(document).on('click', '.move-down', function(e) {
        e.preventDefault();
        var row = $(this).closest('tr');
        var nextRow = row.next('tr');
        
        if (nextRow.length) {
            var lessonId = row.data('id');
            var nextLessonId = nextRow.data('id');
            var lessonOrder = parseInt(row.find('.lesson-order').text());
            var nextLessonOrder = parseInt(nextRow.find('.lesson-order').text());
            
            // Обмен порядковыми номерами
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'cryptoschool_update_lesson_order',
                    nonce: cryptoschool_admin.nonce,
                    lesson_orders: [
                        { id: lessonId, lesson_order: nextLessonOrder },
                        { id: nextLessonId, lesson_order: lessonOrder }
                    ]
                },
                success: function(response) {
                    if (response.success) {
                        // Обновление отображения
                        row.find('.lesson-order').text(nextLessonOrder);
                        nextRow.find('.lesson-order').text(lessonOrder);
                        
                        // Перемещение строки
                        row.insertAfter(nextRow);
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    alert('<?php _e('Произошла ошибка при обновлении порядка уроков.', 'cryptoschool'); ?>');
                }
            });
        }
    });
    
    // Закрытие модальных окон
    $('.cryptoschool-admin-modal-close, .cryptoschool-modal-cancel').on('click', function() {
        $('.cryptoschool-admin-modal').hide();
    });
    
    // Закрытие модальных окон при клике на крестик или кнопку "Отмена"
    // Не закрываем модальные окна при клике вне содержимого, чтобы избежать случайного закрытия
    
    // Фильтрация уроков
    $('#cryptoschool-filter-apply').on('click', function() {
        var status = $('#cryptoschool-filter-status').val();
        var search = $('#cryptoschool-filter-search').val();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'cryptoschool_get_lessons',
                nonce: cryptoschool_admin.nonce,
                course_id: <?php echo esc_js($course_id); ?>,
                is_active: status,
                search: search
            },
            success: function(response) {
                if (response.success) {
                    var lessons = response.data;
                    var html = '';
                    
                    if (lessons.length === 0) {
                        html = '<tr><td colspan="7"><?php _e('Уроки не найдены.', 'cryptoschool'); ?></td></tr>';
                    } else {
                        for (var i = 0; i < lessons.length; i++) {
                            var lesson = lessons[i];
                            html += '<tr data-id="' + lesson.id + '">';
                            html += '<td class="column-id">' + lesson.id + '</td>';
                            html += '<td class="column-order">';
                            html += '<span class="lesson-order">' + lesson.lesson_order + '</span>';
                            html += '</td>';
                            html += '<td class="column-title">';
                            html += '<strong>' + lesson.title + '</strong>';
                            html += '<div class="row-actions">';
                            html += '<span class="edit"><a href="#" class="edit-lesson" data-id="' + lesson.id + '"><?php _e('Редактировать', 'cryptoschool'); ?></a> | </span>';
                            html += '<span class="delete"><a href="#" class="delete-lesson" data-id="' + lesson.id + '"><?php _e('Удалить', 'cryptoschool'); ?></a></span>';
                            html += '</div>';
                            html += '</td>';
                            html += '<td class="column-video">';
                            if (lesson.video_url) {
                                html += '<a href="' + lesson.video_url + '" target="_blank"><?php _e('Просмотр', 'cryptoschool'); ?></a>';
                            } else {
                                html += '<span class="no-video"><?php _e('Нет видео', 'cryptoschool'); ?></span>';
                            }
                            html += '</td>';
                            html += '<td class="column-points">' + lesson.completion_points + '</td>';
                            html += '<td class="column-status">';
                            if (lesson.is_active == 1) {
                                html += '<span class="status-active"><?php _e('Активен', 'cryptoschool'); ?></span>';
                            } else {
                                html += '<span class="status-inactive"><?php _e('Неактивен', 'cryptoschool'); ?></span>';
                            }
                            html += '</td>';
                            html += '<td class="column-actions">';
                            html += '<a href="#" class="edit-lesson dashicons dashicons-edit" data-id="' + lesson.id + '" title="<?php _e('Редактировать', 'cryptoschool'); ?>"></a> ';
                            html += '<a href="#" class="delete-lesson dashicons dashicons-trash" data-id="' + lesson.id + '" title="<?php _e('Удалить', 'cryptoschool'); ?>"></a>';
                            html += '</td>';
                            html += '</tr>';
                        }
                    }
                    
                    $('#cryptoschool-lessons-list').html(html);
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('<?php _e('Произошла ошибка при загрузке уроков.', 'cryptoschool'); ?>');
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
