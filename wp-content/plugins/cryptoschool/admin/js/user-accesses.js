jQuery(document).ready(function($) {
    // Инициализация при загрузке страницы
    populateUserSelect();
    populatePackageSelect();
    
    // Форматирование даты и времени для input datetime-local
    function formatDateTimeForInput(dateString) {
        if (!dateString) return '';
        var date = new Date(dateString);
        return date.getFullYear() + '-' + 
               ('0' + (date.getMonth() + 1)).slice(-2) + '-' + 
               ('0' + date.getDate()).slice(-2) + 'T' + 
               ('0' + date.getHours()).slice(-2) + ':' + 
               ('0' + date.getMinutes()).slice(-2);
    }
    
    // Заполнение выпадающих списков пользователей и пакетов
    function populateUserSelect() {
        var $userSelect = $('#user-access-user');
        $userSelect.empty();
        $userSelect.append('<option value="">' + 'Выберите пользователя' + '</option>');
        
        if (cryptoschool_user_accesses.users && cryptoschool_user_accesses.users.length > 0) {
            $.each(cryptoschool_user_accesses.users, function(index, user) {
                $userSelect.append('<option value="' + user.id + '">' + user.name + ' (' + user.email + ')</option>');
            });
        }
    }
    
    function populatePackageSelect() {
        var $packageSelect = $('#user-access-package');
        $packageSelect.empty();
        $packageSelect.append('<option value="">' + 'Выберите пакет' + '</option>');
        
        if (cryptoschool_user_accesses.packages && cryptoschool_user_accesses.packages.length > 0) {
            $.each(cryptoschool_user_accesses.packages, function(index, package) {
                $packageSelect.append('<option value="' + package.id + '">' + package.title + '</option>');
            });
        }
    }
    
    // Открытие модального окна для добавления доступа
    $('.add-new-user-access').on('click', function(e) {
        e.preventDefault();
        $('#cryptoschool-user-access-modal-title').text(cryptoschool_user_accesses.text_add_access);
        $('#cryptoschool-user-access-form')[0].reset();
        $('#user-access-id').val(0);
        
        // Заполнение выпадающих списков
        populateUserSelect();
        populatePackageSelect();
        
        // Установка текущей даты и времени
        var now = new Date();
        var formattedNow = formatDateTimeForInput(now);
        $('#user-access-start').val(formattedNow);
        
        $('#cryptoschool-user-access-modal').show();
    });
    
    // Открытие модального окна для редактирования доступа
    $(document).on('click', '.edit-user-access', function(e) {
        e.preventDefault();
        var accessId = $(this).data('id');
        
        // Заполнение выпадающих списков
        populateUserSelect();
        populatePackageSelect();
        
        // Загрузка данных доступа
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'cryptoschool_get_user_access',
                nonce: cryptoschool_admin.nonce,
                id: accessId
            },
            success: function(response) {
                if (response.success) {
                    var access = response.data;
                    
                    $('#cryptoschool-user-access-modal-title').text(cryptoschool_user_accesses.text_edit_access);
                    $('#user-access-id').val(access.id);
                    $('#user-access-user').val(access.user_id);
                    $('#user-access-package').val(access.package_id);
                    $('#user-access-start').val(formatDateTimeForInput(access.access_start));
                    
                    // Расчет продолжительности из даты окончания
                    if (access.access_end) {
                        var startDate = new Date(access.access_start);
                        var endDate = new Date(access.access_end);
                        var diffMonths = (endDate.getFullYear() - startDate.getFullYear()) * 12 + (endDate.getMonth() - startDate.getMonth());
                        $('#user-access-duration').val(diffMonths);
                    } else {
                        $('#user-access-duration').val('');
                    }
                    
                    $('#user-access-status').val(access.status);
                    $('#user-access-telegram').val(access.telegram_status);
                    
                    $('#cryptoschool-user-access-modal').show();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert(cryptoschool_user_accesses.error_loading_access);
            }
        });
    });
    
    // Сохранение доступа
    $('#cryptoschool-user-access-form').on('submit', function(e) {
        e.preventDefault();
        
        console.log('Форма отправлена');
        
        var accessId = $('#user-access-id').val();
        var userId = $('#user-access-user').val();
        var packageId = $('#user-access-package').val();
        var accessStart = $('#user-access-start').val();
        var durationMonths = $('#user-access-duration').val();
        var status = $('#user-access-status').val();
        var telegramStatus = $('#user-access-telegram').val();
        
        var action = accessId > 0 ? 'cryptoschool_update_user_access' : 'cryptoschool_create_user_access';
        
        console.log('ID доступа:', accessId);
        console.log('ID пользователя:', userId);
        console.log('ID пакета:', packageId);
        console.log('Дата начала:', accessStart);
        console.log('Срок действия:', durationMonths);
        console.log('Статус:', status);
        console.log('Статус в Telegram:', telegramStatus);
        console.log('Действие:', action);
        console.log('Nonce:', cryptoschool_admin.nonce);
        console.log('AJAX URL:', ajaxurl);
        
        // Проверка, определена ли переменная ajaxurl
        if (typeof ajaxurl === 'undefined') {
            console.error('Ошибка: переменная ajaxurl не определена');
            alert('Ошибка: переменная ajaxurl не определена');
            return;
        }
        
        // Проверка обязательных полей
        if (!userId) {
            alert('Пожалуйста, выберите пользователя.');
            return;
        }
        
        if (!packageId) {
            alert('Пожалуйста, выберите пакет.');
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: action,
                nonce: cryptoschool_admin.nonce,
                id: accessId,
                user_id: userId,
                package_id: packageId,
                access_start: accessStart,
                duration_months: durationMonths,
                status: status,
                telegram_status: telegramStatus
            },
            success: function(response) {
                console.log('Ответ сервера:', response);
                if (response.success) {
                    location.reload();
                } else {
                    console.error('Ошибка сервера:', response.data);
                    alert(response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX ошибка:', status, error);
                console.error('Ответ сервера:', xhr.responseText);
                alert('Произошла ошибка при сохранении доступа. Пожалуйста, попробуйте еще раз.\n\nДетали ошибки: ' + error);
            }
        });
    });
    
    // Открытие модального окна для подтверждения удаления
    $(document).on('click', '.delete-user-access', function(e) {
        e.preventDefault();
        var accessId = $(this).data('id');
        $('#cryptoschool-confirm-delete').data('id', accessId);
        $('#cryptoschool-delete-modal').show();
    });
    
    // Удаление доступа
    $('#cryptoschool-confirm-delete').on('click', function() {
        var accessId = $(this).data('id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'cryptoschool_delete_user_access',
                nonce: cryptoschool_admin.nonce,
                id: accessId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert(cryptoschool_user_accesses.error_deleting_access);
            }
        });
    });
    
    // Обновление статуса в Telegram
    $(document).on('click', '.invite-telegram, .activate-telegram, .remove-telegram', function(e) {
        e.preventDefault();
        var accessId = $(this).data('id');
        var newStatus = '';
        
        if ($(this).hasClass('invite-telegram')) {
            newStatus = 'invited';
        } else if ($(this).hasClass('activate-telegram')) {
            newStatus = 'active';
        } else if ($(this).hasClass('remove-telegram')) {
            newStatus = 'removed';
        }
        
        if (newStatus) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'cryptoschool_update_telegram_status',
                    nonce: cryptoschool_admin.nonce,
                    id: accessId,
                    telegram_status: newStatus
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    alert(cryptoschool_user_accesses.error_updating_telegram);
                }
            });
        }
    });
    
    // Закрытие модальных окон
    $('.cryptoschool-admin-modal-close, .cryptoschool-modal-cancel').on('click', function() {
        $('.cryptoschool-admin-modal').hide();
    });
    
    // Закрытие модальных окон при клике вне содержимого
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('cryptoschool-admin-modal')) {
            $('.cryptoschool-admin-modal').hide();
        }
    });
    
    // Фильтрация доступов
    $('#cryptoschool-filter-apply').on('click', function() {
        var userId = $('#cryptoschool-filter-user').val();
        var packageId = $('#cryptoschool-filter-package').val();
        var status = $('#cryptoschool-filter-status').val();
        var telegramStatus = $('#cryptoschool-filter-telegram').val();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'cryptoschool_get_user_accesses',
                nonce: cryptoschool_admin.nonce,
                user_id: userId,
                package_id: packageId,
                status: status,
                telegram_status: telegramStatus
            },
            success: function(response) {
                if (response.success) {
                    var accesses = response.data;
                    var html = '';
                    
                    if (accesses.length === 0) {
                        html = '<tr><td colspan="8">' + cryptoschool_user_accesses.text_no_accesses + '</td></tr>';
                    } else {
                        for (var i = 0; i < accesses.length; i++) {
                            var access = accesses[i];
                            html += '<tr data-id="' + access.id + '">';
                            html += '<td class="column-id">' + access.id + '</td>';
                            html += '<td class="column-user">';
                            
                            if (access.user_name) {
                                html += '<strong>' + access.user_name + '</strong>';
                                html += '<div class="row-actions">';
                                html += '<span class="email">' + access.user_email + '</span>';
                                html += '</div>';
                            } else {
                                html += '<span class="user-not-found">' + cryptoschool_user_accesses.text_user_not_found + '</span>';
                            }
                            
                            html += '</td>';
                            html += '<td class="column-package">';
                            
                            if (access.package_title) {
                                html += access.package_title;
                            } else {
                                html += '<span class="package-not-found">' + cryptoschool_user_accesses.text_package_not_found + '</span>';
                            }
                            
                            html += '</td>';
                            html += '<td class="column-start">' + new Date(access.access_start).toLocaleString() + '</td>';
                            html += '<td class="column-end">';
                            
                            if (access.access_end) {
                                html += new Date(access.access_end).toLocaleString();
                            } else {
                                html += '<span class="lifetime">' + cryptoschool_user_accesses.text_lifetime + '</span>';
                            }
                            
                            html += '</td>';
                            html += '<td class="column-status">';
                            
                            if (access.status === 'active') {
                                html += '<span class="status-active">' + cryptoschool_user_accesses.text_active + '</span>';
                            } else {
                                html += '<span class="status-expired">' + cryptoschool_user_accesses.text_expired + '</span>';
                            }
                            
                            html += '</td>';
                            html += '<td class="column-telegram">';
                            
                            switch (access.telegram_status) {
                                case 'none':
                                    html += '<span class="telegram-none">' + cryptoschool_user_accesses.text_telegram_none + '</span>';
                                    break;
                                case 'invited':
                                    html += '<span class="telegram-invited">' + cryptoschool_user_accesses.text_telegram_invited + '</span>';
                                    break;
                                case 'active':
                                    html += '<span class="telegram-active">' + cryptoschool_user_accesses.text_telegram_active + '</span>';
                                    break;
                                case 'removed':
                                    html += '<span class="telegram-removed">' + cryptoschool_user_accesses.text_telegram_removed + '</span>';
                                    break;
                                default:
                                    html += access.telegram_status;
                            }
                            
                            html += '</td>';
                            html += '<td class="column-actions">';
                            html += '<a href="#" class="edit-user-access" data-id="' + access.id + '" title="' + cryptoschool_user_accesses.text_edit + '"><span class="dashicons dashicons-edit"></span></a> ';
                            html += '<a href="#" class="delete-user-access" data-id="' + access.id + '" title="' + cryptoschool_user_accesses.text_delete + '"><span class="dashicons dashicons-trash"></span></a> ';
                            
                            if (access.package_type === 'community' || access.package_type === 'combined') {
                                if (access.telegram_status === 'none') {
                                    html += '<a href="#" class="invite-telegram" data-id="' + access.id + '" title="' + cryptoschool_user_accesses.text_invite_telegram + '"><span class="dashicons dashicons-admin-users"></span></a>';
                                } else if (access.telegram_status === 'invited') {
                                    html += '<a href="#" class="activate-telegram" data-id="' + access.id + '" title="' + cryptoschool_user_accesses.text_activate_telegram + '"><span class="dashicons dashicons-yes"></span></a>';
                                } else if (access.telegram_status === 'active') {
                                    html += '<a href="#" class="remove-telegram" data-id="' + access.id + '" title="' + cryptoschool_user_accesses.text_remove_telegram + '"><span class="dashicons dashicons-no"></span></a>';
                                } else if (access.telegram_status === 'removed') {
                                    html += '<a href="#" class="invite-telegram" data-id="' + access.id + '" title="' + cryptoschool_user_accesses.text_invite_telegram + '"><span class="dashicons dashicons-admin-users"></span></a>';
                                }
                            }
                            
                            html += '</td>';
                            html += '</tr>';
                        }
                    }
                    
                    $('#cryptoschool-user-accesses-list').html(html);
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert(cryptoschool_user_accesses.error_loading_accesses);
            }
        });
    });
    
    // Сброс фильтров
    $('#cryptoschool-filter-reset').on('click', function() {
        $('#cryptoschool-filter-user').val('');
        $('#cryptoschool-filter-package').val('');
        $('#cryptoschool-filter-status').val('');
        $('#cryptoschool-filter-telegram').val('');
        $('#cryptoschool-filter-apply').click();
    });
});
