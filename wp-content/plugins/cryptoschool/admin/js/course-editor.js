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
            title: cryptoschool_admin.media_title || 'Выберите изображение курса',
            button: {
                text: cryptoschool_admin.media_select || 'Выбрать'
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
        $('#cryptoschool-course-modal-title').text(cryptoschool_admin.add_course_title || 'Добавить курс');
        $('#cryptoschool-course-form')[0].reset();
        $('#course-id').val(0);
        $('#course-thumbnail-preview').html('');
        $('#course-thumbnail-remove').hide();
        
        // Очистка редактора TinyMCE
        if (typeof tinyMCE !== 'undefined' && tinyMCE.get('course-description')) {
            tinyMCE.get('course-description').setContent('');
        }
        
        $('#cryptoschool-course-modal').show();
    });
    
    // Открытие модального окна для редактирования курса
    $(document).on('click', '.edit-course', function(e) {
        e.preventDefault();
        var courseId = $(this).data('id');
        
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
                nonce: cryptoschool_admin.nonce,
                id: parseInt(courseId, 10) // Явно преобразуем в целое число
            },
            success: function(response) {
                if (response.success) {
                    var course = response.data;
                    
                    $('#cryptoschool-course-modal-title').text(cryptoschool_admin.edit_course_title || 'Редактировать курс');
                    $('#course-id').val(course.id);
                    $('#course-title').val(course.title || '');
                    
                    // Установка содержимого в редактор TinyMCE
                    if (typeof tinyMCE !== 'undefined' && tinyMCE.get('course-description')) {
                        tinyMCE.get('course-description').setContent(course.description || '');
                    } else {
                        $('#course-description').val(course.description || '');
                    }
                    
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
                    alert(response.data || cryptoschool_admin.error_message);
                }
            },
            error: function() {
                alert(cryptoschool_admin.error_message || 'Произошла ошибка при загрузке данных курса.');
            }
        });
    });
    
    // Сохранение курса
    $('#cryptoschool-course-form').on('submit', function(e) {
        e.preventDefault();
        
        var courseId = $('#course-id').val();
        var title = $('#course-title').val();
        
        // Получение содержимого из редактора TinyMCE
        var description = '';
        if (typeof tinyMCE !== 'undefined' && tinyMCE.get('course-description')) {
            description = tinyMCE.get('course-description').getContent();
        } else {
            description = $('#course-description').val();
        }
        
        var difficulty_level = $('#course-difficulty').val();
        var thumbnail = $('#course-thumbnail').val();
        var completion_points = $('#course-completion-points').val();
        var is_active = $('#course-is-active').val();
        
        var action = courseId > 0 ? 'cryptoschool_update_course' : 'cryptoschool_create_course';
        
        // Проверка обязательных полей
        if (!title) {
            alert(cryptoschool_admin.title_required || 'Название курса обязательно для заполнения.');
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: action,
                nonce: cryptoschool_admin.nonce,
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
                    alert(response.data || cryptoschool_admin.error_message);
                }
            },
            error: function() {
                alert(cryptoschool_admin.error_message || 'Произошла ошибка при сохранении курса.');
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
                nonce: cryptoschool_admin.nonce,
                id: courseId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data || cryptoschool_admin.error_message);
                }
            },
            error: function() {
                alert(cryptoschool_admin.error_message || 'Произошла ошибка при удалении курса.');
            }
        });
    });
    
    // Закрытие модальных окон
    $('.cryptoschool-admin-modal-close, .cryptoschool-modal-cancel').on('click', function() {
        $('.cryptoschool-admin-modal').hide();
    });
    
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
                nonce: cryptoschool_admin.nonce,
                is_active: status,
                search: search
            },
            success: function(response) {
                if (response.success) {
                    var courses = response.data;
                    var html = '';
                    
                    if (courses.length === 0) {
                        html = '<tr><td colspan="7">Курсы не найдены.</td></tr>';
                    } else {
                        for (var i = 0; i < courses.length; i++) {
                            var course = courses[i];
                            html += '<tr data-id="' + course.id + '">';
                            html += '<td class="column-id">' + course.id + '</td>';
                            html += '<td class="column-title">';
                            html += '<strong>' + course.title + '</strong>';
                            html += '<div class="row-actions">';
                            html += '<span class="edit"><a href="#" class="edit-course" data-id="' + course.id + '">Редактировать</a> | </span>';
                            html += '<span class="lessons"><a href="' + ajaxurl.replace('admin-ajax.php', 'admin.php?page=cryptoschool-lessons&course_id=' + course.id) + '">Уроки</a> | </span>';
                            html += '<span class="delete"><a href="#" class="delete-course" data-id="' + course.id + '">Удалить</a></span>';
                            html += '</div>';
                            html += '</td>';
                            html += '<td class="column-difficulty">' + course.difficulty_level + '</td>';
                            html += '<td class="column-lessons">' + (course.lessons_count || 0) + '</td>';
                            html += '<td class="column-status">';
                            if (course.is_active == 1) {
                                html += '<span class="status-active">Активен</span>';
                            } else {
                                html += '<span class="status-inactive">Неактивен</span>';
                            }
                            html += '</td>';
                            html += '<td class="column-actions">';
                            html += '<a href="' + ajaxurl.replace('admin-ajax.php', 'admin.php?page=cryptoschool-lessons&course_id=' + course.id) + '" class="button button-small">Уроки</a> ';
                            html += '<a href="#" class="edit-course dashicons dashicons-edit" data-id="' + course.id + '" title="Редактировать"></a> ';
                            html += '<a href="#" class="delete-course dashicons dashicons-trash" data-id="' + course.id + '" title="Удалить"></a>';
                            html += '</td>';
                            html += '</tr>';
                        }
                    }
                    
                    $('#cryptoschool-courses-list').html(html);
                } else {
                    alert(response.data || cryptoschool_admin.error_message);
                }
            },
            error: function() {
                alert(cryptoschool_admin.error_message || 'Произошла ошибка при загрузке курсов.');
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
