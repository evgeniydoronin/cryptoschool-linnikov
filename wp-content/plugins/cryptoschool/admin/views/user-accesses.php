<?php
/**
 * Шаблон страницы управления доступами пользователей
 *
 * @package CryptoSchool
 * @subpackage Admin
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap cryptoschool-admin cryptoschool-user-accesses-page">
    <h1 class="wp-heading-inline"><?php _e('Управление доступами пользователей', 'cryptoschool'); ?></h1>
    <a href="#" class="page-title-action add-new-user-access"><?php _e('Добавить доступ', 'cryptoschool'); ?></a>
    <hr class="wp-header-end">

    <div class="cryptoschool-admin-notices"></div>

    <div class="cryptoschool-admin-content">
        <?php /* Временно скрыт блок фильтров
        <div class="cryptoschool-admin-filters">
            <div class="cryptoschool-filter-group">
                <label for="cryptoschool-filter-user"><?php _e('Пользователь:', 'cryptoschool'); ?></label>
                <select id="cryptoschool-filter-user" class="cryptoschool-filter">
                    <option value=""><?php _e('Все пользователи', 'cryptoschool'); ?></option>
                    <?php foreach ($users as $user) : ?>
                        <option value="<?php echo esc_attr($user->ID); ?>"><?php echo esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')'; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="cryptoschool-filter-group">
                <label for="cryptoschool-filter-package"><?php _e('Пакет:', 'cryptoschool'); ?></label>
                <select id="cryptoschool-filter-package" class="cryptoschool-filter">
                    <option value=""><?php _e('Все пакеты', 'cryptoschool'); ?></option>
                    <?php foreach ($packages as $package) : ?>
                        <option value="<?php echo esc_attr($package->id); ?>"><?php echo esc_html($package->title); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="cryptoschool-filter-group">
                <label for="cryptoschool-filter-status"><?php _e('Статус:', 'cryptoschool'); ?></label>
                <select id="cryptoschool-filter-status" class="cryptoschool-filter">
                    <option value=""><?php _e('Все', 'cryptoschool'); ?></option>
                    <option value="active"><?php _e('Активные', 'cryptoschool'); ?></option>
                    <option value="expired"><?php _e('Истекшие', 'cryptoschool'); ?></option>
                </select>
            </div>
            <div class="cryptoschool-filter-group">
                <label for="cryptoschool-filter-telegram"><?php _e('Telegram:', 'cryptoschool'); ?></label>
                <select id="cryptoschool-filter-telegram" class="cryptoschool-filter">
                    <option value=""><?php _e('Все', 'cryptoschool'); ?></option>
                    <option value="none"><?php _e('Нет доступа', 'cryptoschool'); ?></option>
                    <option value="invited"><?php _e('Приглашен', 'cryptoschool'); ?></option>
                    <option value="active"><?php _e('Активен', 'cryptoschool'); ?></option>
                    <option value="removed"><?php _e('Удален', 'cryptoschool'); ?></option>
                </select>
            </div>
            <div class="cryptoschool-filter-group">
                <button id="cryptoschool-filter-apply" class="button"><?php _e('Применить', 'cryptoschool'); ?></button>
                <button id="cryptoschool-filter-reset" class="button"><?php _e('Сбросить', 'cryptoschool'); ?></button>
            </div>
        </div>
        */ ?>

        <div class="cryptoschool-admin-table-container">
            <table class="wp-list-table widefat fixed striped cryptoschool-admin-table cryptoschool-user-accesses-table">
                <thead>
                    <tr>
                        <th class="column-id"><?php _e('ID', 'cryptoschool'); ?></th>
                        <th class="column-user"><?php _e('Пользователь', 'cryptoschool'); ?></th>
                        <th class="column-package"><?php _e('Пакет', 'cryptoschool'); ?></th>
                        <th class="column-start"><?php _e('Начало', 'cryptoschool'); ?></th>
                        <th class="column-end"><?php _e('Окончание', 'cryptoschool'); ?></th>
                        <th class="column-status"><?php _e('Статус', 'cryptoschool'); ?></th>
                        <th class="column-telegram"><?php _e('Telegram', 'cryptoschool'); ?></th>
                        <th class="column-actions"><?php _e('Действия', 'cryptoschool'); ?></th>
                    </tr>
                </thead>
                <tbody id="cryptoschool-user-accesses-list">
                    <?php if (empty($user_accesses)) : ?>
                        <tr>
                            <td colspan="8"><?php _e('Доступы не найдены.', 'cryptoschool'); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($user_accesses as $access) : ?>
                            <?php
                            $user = get_userdata($access->user_id);
                            $package_service = new CryptoSchool_Service_Package($this->loader);
                            $package = $package_service->get_by_id($access->package_id);
                            ?>
                            <tr data-id="<?php echo esc_attr($access->id); ?>">
                                <td class="column-id"><?php echo esc_html($access->id); ?></td>
                                <td class="column-user">
                                    <?php if ($user) : ?>
                                        <strong><?php echo esc_html($user->display_name); ?></strong>
                                        <div class="row-actions">
                                            <span class="email"><?php echo esc_html($user->user_email); ?></span>
                                        </div>
                                    <?php else : ?>
                                        <span class="user-not-found"><?php _e('Пользователь не найден', 'cryptoschool'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="column-package">
                                    <?php if ($package) : ?>
                                        <?php echo esc_html($package->title); ?>
                                    <?php else : ?>
                                        <span class="package-not-found"><?php _e('Пакет не найден', 'cryptoschool'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="column-start">
                                    <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($access->access_start))); ?>
                                </td>
                                <td class="column-end">
                                    <?php if ($access->access_end) : ?>
                                        <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($access->access_end))); ?>
                                    <?php else : ?>
                                        <span class="lifetime"><?php _e('Пожизненно', 'cryptoschool'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="column-status">
                                    <?php if ($access->status === 'active') : ?>
                                        <span class="status-active"><?php _e('Активен', 'cryptoschool'); ?></span>
                                    <?php else : ?>
                                        <span class="status-expired"><?php _e('Истек', 'cryptoschool'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="column-telegram">
                                    <?php
                                    switch ($access->telegram_status) {
                                        case 'none':
                                            echo '<span class="telegram-none">' . __('Нет доступа', 'cryptoschool') . '</span>';
                                            break;
                                        case 'invited':
                                            echo '<span class="telegram-invited">' . __('Приглашен', 'cryptoschool') . '</span>';
                                            break;
                                        case 'active':
                                            echo '<span class="telegram-active">' . __('Активен', 'cryptoschool') . '</span>';
                                            break;
                                        case 'removed':
                                            echo '<span class="telegram-removed">' . __('Удален', 'cryptoschool') . '</span>';
                                            break;
                                        default:
                                            echo esc_html($access->telegram_status);
                                    }
                                    ?>
                                </td>
                                <td class="column-actions">
                                    <a href="#" class="button button-small edit-user-access" data-id="<?php echo esc_attr($access->id); ?>"><?php _e('Редактировать', 'cryptoschool'); ?></a>
                                    <a href="#" class="button button-small delete-user-access" data-id="<?php echo esc_attr($access->id); ?>"><?php _e('Удалить', 'cryptoschool'); ?></a>
                                    <?php if ($package && ($package->package_type === 'community' || $package->package_type === 'combined')) : ?>
                                        <?php if ($access->telegram_status === 'none') : ?>
                                            <a href="#" class="button button-small invite-telegram" data-id="<?php echo esc_attr($access->id); ?>"><?php _e('Пригласить в Telegram', 'cryptoschool'); ?></a>
                                        <?php elseif ($access->telegram_status === 'invited') : ?>
                                            <a href="#" class="button button-small activate-telegram" data-id="<?php echo esc_attr($access->id); ?>"><?php _e('Активировать в Telegram', 'cryptoschool'); ?></a>
                                        <?php elseif ($access->telegram_status === 'active') : ?>
                                            <a href="#" class="button button-small remove-telegram" data-id="<?php echo esc_attr($access->id); ?>"><?php _e('Удалить из Telegram', 'cryptoschool'); ?></a>
                                        <?php elseif ($access->telegram_status === 'removed') : ?>
                                            <a href="#" class="button button-small invite-telegram" data-id="<?php echo esc_attr($access->id); ?>"><?php _e('Пригласить в Telegram', 'cryptoschool'); ?></a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Модальное окно для добавления/редактирования доступа -->
<div id="cryptoschool-user-access-modal" class="cryptoschool-admin-modal" style="display: none;">
    <div class="cryptoschool-admin-modal-content">
        <span class="cryptoschool-admin-modal-close">&times;</span>
        <h2 id="cryptoschool-user-access-modal-title"><?php _e('Добавить доступ', 'cryptoschool'); ?></h2>
        
        <form id="cryptoschool-user-access-form">
            <input type="hidden" id="user-access-id" name="id" value="0">
            
            <div class="cryptoschool-admin-form-row">
                <label for="user-access-user"><?php _e('Пользователь', 'cryptoschool'); ?> <span class="required">*</span></label>
                <select id="user-access-user" name="user_id" required>
                    <option value=""><?php _e('Выберите пользователя', 'cryptoschool'); ?></option>
                    <?php foreach ($users as $user) : ?>
                        <option value="<?php echo esc_attr($user->ID); ?>"><?php echo esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')'; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="cryptoschool-admin-form-row">
                <label for="user-access-package"><?php _e('Пакет', 'cryptoschool'); ?> <span class="required">*</span></label>
                <select id="user-access-package" name="package_id" required>
                    <option value=""><?php _e('Выберите пакет', 'cryptoschool'); ?></option>
                    <?php foreach ($packages as $package) : ?>
                        <option value="<?php echo esc_attr($package->id); ?>"><?php echo esc_html($package->title); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="cryptoschool-admin-form-row">
                <label for="user-access-start"><?php _e('Дата начала', 'cryptoschool'); ?> <span class="required">*</span></label>
                <input type="datetime-local" id="user-access-start" name="access_start" required>
            </div>
            
            <div class="cryptoschool-admin-form-row">
                <label for="user-access-duration"><?php _e('Срок действия (месяцев)', 'cryptoschool'); ?></label>
                <input type="number" id="user-access-duration" name="duration_months" min="0">
                <p class="description"><?php _e('Оставьте пустым для пожизненного доступа.', 'cryptoschool'); ?></p>
            </div>
            
            <div class="cryptoschool-admin-form-row">
                <label for="user-access-status"><?php _e('Статус', 'cryptoschool'); ?></label>
                <select id="user-access-status" name="status">
                    <option value="active"><?php _e('Активен', 'cryptoschool'); ?></option>
                    <option value="expired"><?php _e('Истек', 'cryptoschool'); ?></option>
                </select>
            </div>
            
            <div class="cryptoschool-admin-form-row">
                <label for="user-access-telegram"><?php _e('Статус в Telegram', 'cryptoschool'); ?></label>
                <select id="user-access-telegram" name="telegram_status">
                    <option value="none"><?php _e('Нет доступа', 'cryptoschool'); ?></option>
                    <option value="invited"><?php _e('Приглашен', 'cryptoschool'); ?></option>
                    <option value="active"><?php _e('Активен', 'cryptoschool'); ?></option>
                    <option value="removed"><?php _e('Удален', 'cryptoschool'); ?></option>
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
        
        <p><?php _e('Вы уверены, что хотите удалить этот доступ? Это действие нельзя отменить.', 'cryptoschool'); ?></p>
        
        <div class="cryptoschool-admin-form-row">
            <button type="button" id="cryptoschool-confirm-delete" class="button button-primary" data-id="0"><?php _e('Удалить', 'cryptoschool'); ?></button>
            <button type="button" class="button cryptoschool-modal-cancel"><?php _e('Отмена', 'cryptoschool'); ?></button>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
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
    
    // Открытие модального окна для добавления доступа
    $('.add-new-user-access').on('click', function(e) {
        e.preventDefault();
        $('#cryptoschool-user-access-modal-title').text('<?php _e('Добавить доступ', 'cryptoschool'); ?>');
        $('#cryptoschool-user-access-form')[0].reset();
        $('#user-access-id').val(0);
        
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
                    
                    $('#cryptoschool-user-access-modal-title').text('<?php _e('Редактировать доступ', 'cryptoschool'); ?>');
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
                alert('<?php _e('Произошла ошибка при загрузке данных доступа.', 'cryptoschool'); ?>');
            }
        });
    });
    
    // Сохранение доступа
    $('#cryptoschool-user-access-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        var accessId = $('#user-access-id').val();
        var action = accessId > 0 ? 'cryptoschool_update_user_access' : 'cryptoschool_create_user_access';
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: action,
                nonce: cryptoschool_admin.nonce,
                ...formData
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('<?php _e('Произошла ошибка при сохранении доступа.', 'cryptoschool'); ?>');
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
                alert('<?php _e('Произошла ошибка при удалении доступа.', 'cryptoschool'); ?>');
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
                    alert('<?php _e('Произошла ошибка при обновлении статуса в Telegram.', 'cryptoschool'); ?>');
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
                        html = '<tr><td colspan="8"><?php _e('Доступы не найдены.', 'cryptoschool'); ?></td></tr>';
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
                                html += '<span class="user-not-found"><?php _e('Пользователь не найден', 'cryptoschool'); ?></span>';
                            }
                            
                            html += '</td>';
                            html += '<td class="column-package">';
                            
                            if (access.package_title) {
                                html += access.package_title;
                            } else {
                                html += '<span class="package-not-found"><?php _e('Пакет не найден', 'cryptoschool'); ?></span>';
                            }
                            
                            html += '</td>';
                            html += '<td class="column-start">' + new Date(access.access_start).toLocaleString() + '</td>';
                            html += '<td class="column-end">';
                            
                            if (access.access_end) {
                                html += new Date(access.access_end).toLocaleString();
                            } else {
                                html += '<span class="lifetime"><?php _e('Пожизненно', 'cryptoschool'); ?></span>';
                            }
                            
                            html += '</td>';
                            html += '<td class="column-status">';
                            
                            if (access.status === 'active') {
                                html += '<span class="status-active"><?php _e('Активен', 'cryptoschool'); ?></span>';
                            } else {
                                html += '<span class="status-expired"><?php _e('Истек', 'cryptoschool'); ?></span>';
                            }
                            
                            html += '</td>';
                            html += '<td class="column-telegram">';
                            
                            switch (access.telegram_status) {
                                case 'none':
                                    html += '<span class="telegram-none"><?php _e('Нет доступа', 'cryptoschool'); ?></span>';
                                    break;
                                case 'invited':
                                    html += '<span class="telegram-invited"><?php _e('Приглашен', 'cryptoschool'); ?></span>';
                                    break;
                                case 'active':
                                    html += '<span class="telegram-active"><?php _e('Активен', 'cryptoschool'); ?></span>';
                                    break;
                                case 'removed':
                                    html += '<span class="telegram-removed"><?php _e('Удален', 'cryptoschool'); ?></span>';
                                    break;
                                default:
                                    html += access.telegram_status;
                            }
                            
                            html += '</td>';
                            html += '<td class="column-actions">';
                            html += '<a href="#" class="button button-small edit-user-access" data-id="' + access.id + '"><?php _e('Редактировать', 'cryptoschool'); ?></a> ';
                            html += '<a href="#" class="button button-small delete-user-access" data-id="' + access.id + '"><?php _e('Удалить', 'cryptoschool'); ?></a> ';
                            
                            if (access.package_type === 'community' || access.package_type === 'combined') {
                                if (access.telegram_status === 'none') {
                                    html += '<a href="#" class="button button-small invite-telegram" data-id="' + access.id + '"><?php _e('Пригласить в Telegram', 'cryptoschool'); ?></a>';
                                } else if (access.telegram_status === 'invited') {
                                    html += '<a href="#" class="button button-small activate-telegram" data-id="' + access.id + '"><?php _e('Активировать в Telegram', 'cryptoschool'); ?></a>';
                                } else if (access.telegram_status === 'active') {
                                    html += '<a href="#" class="button button-small remove-telegram" data-id="' + access.id + '"><?php _e('Удалить из Telegram', 'cryptoschool'); ?></a>';
                                } else if (access.telegram_status === 'removed') {
                                    html += '<a href="#" class="button button-small invite-telegram" data-id="' + access.id + '"><?php _e('Пригласить в Telegram', 'cryptoschool'); ?></a>';
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
                alert('<?php _e('Произошла ошибка при загрузке доступов.', 'cryptoschool'); ?>');
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
