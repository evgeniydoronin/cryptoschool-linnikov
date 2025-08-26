<?php
/**
 * Шаблон страницы управления пакетами
 *
 * @package CryptoSchool
 * @subpackage Admin
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap cryptoschool-admin cryptoschool-packages-page">
    <h1 class="wp-heading-inline"><?php _e('Управление пакетами', 'cryptoschool'); ?></h1>
    <a href="#" class="page-title-action add-new-package"><?php _e('Добавить пакет', 'cryptoschool'); ?></a>
    <hr class="wp-header-end">

    <div class="cryptoschool-admin-notices"></div>

    <div class="cryptoschool-admin-content">
        <?php /* Временно скрыт блок фильтров
        <div class="cryptoschool-admin-filters">
            <div class="cryptoschool-filter-group">
                <label for="cryptoschool-filter-type"><?php _e('Тип:', 'cryptoschool'); ?></label>
                <select id="cryptoschool-filter-type" class="cryptoschool-filter">
                    <option value=""><?php _e('Все', 'cryptoschool'); ?></option>
                    <option value="course"><?php _e('Только обучение', 'cryptoschool'); ?></option>
                    <option value="community"><?php _e('Только приватка', 'cryptoschool'); ?></option>
                    <option value="combined"><?php _e('Комбинированный', 'cryptoschool'); ?></option>
                </select>
            </div>
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
            <table class="wp-list-table widefat fixed striped cryptoschool-admin-table cryptoschool-packages-table">
                <thead>
                    <tr>
                        <th class="column-id"><?php _e('ID', 'cryptoschool'); ?></th>
                        <th class="column-title"><?php _e('Название', 'cryptoschool'); ?></th>
                        <th class="column-type"><?php _e('Тип', 'cryptoschool'); ?></th>
                        <th class="column-price"><?php _e('Цена', 'cryptoschool'); ?></th>
                        <th class="column-duration"><?php _e('Срок', 'cryptoschool'); ?></th>
                        <th class="column-status"><?php _e('Статус', 'cryptoschool'); ?></th>
                        <th class="column-actions"><?php _e('Действия', 'cryptoschool'); ?></th>
                    </tr>
                </thead>
                <tbody id="cryptoschool-packages-list">
                    <?php if (empty($packages)) : ?>
                        <tr>
                            <td colspan="7"><?php _e('Пакеты не найдены.', 'cryptoschool'); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($packages as $package) : ?>
                            <tr data-id="<?php echo esc_attr($package->id); ?>">
                                <td class="column-id"><?php echo esc_html($package->id); ?></td>
                                <td class="column-title">
                                    <strong><?php echo esc_html($package->title); ?></strong>
                                    <div class="row-actions">
                                        <span class="edit"><a href="#" class="edit-package" data-id="<?php echo esc_attr($package->id); ?>"><?php _e('Редактировать', 'cryptoschool'); ?></a> | </span>
                                        <span class="delete"><a href="#" class="delete-package" data-id="<?php echo esc_attr($package->id); ?>"><?php _e('Удалить', 'cryptoschool'); ?></a></span>
                                    </div>
                                </td>
                                <td class="column-type">
                                    <?php
                                    switch ($package->package_type) {
                                        case 'course':
                                            _e('Только обучение', 'cryptoschool');
                                            break;
                                        case 'community':
                                            _e('Только приватка', 'cryptoschool');
                                            break;
                                        case 'combined':
                                            _e('Комбинированный', 'cryptoschool');
                                            break;
                                        default:
                                            echo esc_html($package->package_type);
                                    }
                                    ?>
                                </td>
                                <td class="column-price"><?php echo esc_html($package->price); ?> USD</td>
                                <td class="column-duration">
                                    <?php
                                    if ($package->duration_months === null) {
                                        _e('Пожизненно', 'cryptoschool');
                                    } else {
                                        echo esc_html($package->duration_months) . ' ' . _n('месяц', 'месяцев', $package->duration_months, 'cryptoschool');
                                    }
                                    ?>
                                </td>
                                <td class="column-status">
                                    <?php if ($package->is_active) : ?>
                                        <span class="status-active"><?php _e('Активен', 'cryptoschool'); ?></span>
                                    <?php else : ?>
                                        <span class="status-inactive"><?php _e('Неактивен', 'cryptoschool'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="column-actions">
                                    <a href="#" class="edit-package" data-id="<?php echo esc_attr($package->id); ?>"><span class="dashicons dashicons-edit"></span></a>
                                    <a href="#" class="delete-package" data-id="<?php echo esc_attr($package->id); ?>"><span class="dashicons dashicons-trash"></span></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Модальное окно для добавления/редактирования пакета -->
<div id="cryptoschool-package-modal" class="cryptoschool-admin-modal" style="display: none;">
    <div class="cryptoschool-admin-modal-content cryptoschool-admin-modal-content-large">
        <span class="cryptoschool-admin-modal-close">&times;</span>
        <h2 id="cryptoschool-package-modal-title"><?php _e('Добавить пакет', 'cryptoschool'); ?></h2>
        
        <form id="cryptoschool-package-form">
            <input type="hidden" id="package-id" name="id" value="0">
            
            <div class="cryptoschool-admin-form-row">
                <label for="package-title"><?php _e('Название пакета', 'cryptoschool'); ?> <span class="required">*</span></label>
                <input type="text" id="package-title" name="title" required>
            </div>
            
            <div class="cryptoschool-admin-form-row">
                <label for="package-description"><?php _e('Описание пакета', 'cryptoschool'); ?></label>
                <textarea id="package-description" name="description" rows="5"></textarea>
            </div>
            
            <div class="cryptoschool-admin-form-row">
                <label for="package-type"><?php _e('Тип пакета', 'cryptoschool'); ?> <span class="required">*</span></label>
                <select id="package-type" name="package_type" required>
                    <option value="course"><?php _e('Только обучение', 'cryptoschool'); ?></option>
                    <option value="community"><?php _e('Только приватка', 'cryptoschool'); ?></option>
                    <option value="combined"><?php _e('Комбинированный', 'cryptoschool'); ?></option>
                </select>
            </div>
            
            <div class="cryptoschool-admin-form-row">
                <label for="package-price"><?php _e('Цена (USD)', 'cryptoschool'); ?> <span class="required">*</span></label>
                <input type="number" id="package-price" name="price" min="0" step="0.01" required>
            </div>
            
            <div class="cryptoschool-admin-form-row">
                <label for="package-duration"><?php _e('Срок действия (месяцев)', 'cryptoschool'); ?></label>
                <input type="number" id="package-duration" name="duration_months" min="0">
                <p class="description"><?php _e('Оставьте пустым для пожизненного доступа.', 'cryptoschool'); ?></p>
            </div>
            
            <div class="cryptoschool-admin-form-row">
                <label for="package-creoin-points"><?php _e('Creoin баллы (Пользователи получают Creoin баллы за различные действия на платформе: например, за прохождение уроков, выполнение заданий)', 'cryptoschool'); ?></label>
                <input type="number" id="package-creoin-points" name="creoin_points" min="0" value="0">
            </div>
            
            <div class="cryptoschool-admin-form-row">
                <label for="package-features"><?php _e('Особенности пакета', 'cryptoschool'); ?></label>
                <textarea id="package-features" name="features" rows="5" placeholder="<?php _e('Введите особенности пакета, по одной на строку', 'cryptoschool'); ?>"></textarea>
            </div>
            
            <div class="cryptoschool-admin-form-row">
                <label><?php _e('Включенные курсы', 'cryptoschool'); ?></label>
                <div id="package-courses-container" class="cryptoschool-checkbox-list">
                    <?php if (!empty($courses)) : ?>
                        <?php foreach ($courses as $course) : ?>
                            <div class="cryptoschool-checkbox-item">
                                <label>
                                    <input type="checkbox" name="course_ids[]" value="<?php echo esc_attr($course->ID); ?>">
                                    <?php echo esc_html($course->post_title); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p><?php _e('Нет доступных курсов.', 'cryptoschool'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="cryptoschool-admin-form-row">
                <label for="package-is-active"><?php _e('Статус пакета', 'cryptoschool'); ?></label>
                <select id="package-is-active" name="is_active">
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
        
        <p><?php _e('Вы уверены, что хотите удалить этот пакет? Это действие нельзя отменить.', 'cryptoschool'); ?></p>
        
        <div class="cryptoschool-admin-form-row">
            <button type="button" id="cryptoschool-confirm-delete" class="button button-primary" data-id="0"><?php _e('Удалить', 'cryptoschool'); ?></button>
            <button type="button" class="button cryptoschool-modal-cancel"><?php _e('Отмена', 'cryptoschool'); ?></button>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Открытие модального окна для добавления пакета
    $('.add-new-package').on('click', function(e) {
        e.preventDefault();
        $('#cryptoschool-package-modal-title').text('<?php _e('Добавить пакет', 'cryptoschool'); ?>');
        $('#cryptoschool-package-form')[0].reset();
        $('#package-id').val(0);
        
        // Сброс выбранных курсов
        $('#package-courses-container input[type="checkbox"]').prop('checked', false);
        
        $('#cryptoschool-package-modal').show();
    });
    
    // Открытие модального окна для редактирования пакета
    $(document).on('click', '.edit-package', function(e) {
        e.preventDefault();
        var packageId = $(this).data('id');
        
        // Загрузка данных пакета
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'cryptoschool_get_package',
                nonce: cryptoschool_admin.nonce,
                id: packageId
            },
            success: function(response) {
                if (response.success) {
                    var package = response.data;
                    
                    $('#cryptoschool-package-modal-title').text('<?php _e('Редактировать пакет', 'cryptoschool'); ?>');
                    $('#package-id').val(package.id);
                    $('#package-title').val(package.title);
                    $('#package-description').val(package.description);
                    $('#package-type').val(package.package_type);
                    $('#package-price').val(package.price);
                    $('#package-duration').val(package.duration_months);
                    $('#package-creoin-points').val(package.creoin_points);
                    $('#package-features').val(package.features_text);
                    $('#package-is-active').val(package.is_active);
                    
                    // Установка выбранных курсов
                    $('#package-courses-container input[type="checkbox"]').prop('checked', false);
                    if (package.course_ids && package.course_ids.length > 0) {
                        package.course_ids.forEach(function(courseId) {
                            $('#package-courses-container input[value="' + courseId + '"]').prop('checked', true);
                        });
                    }
                    
                    $('#cryptoschool-package-modal').show();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('<?php _e('Произошла ошибка при загрузке данных пакета.', 'cryptoschool'); ?>');
            }
        });
    });
    
    // Сохранение пакета
    $('#cryptoschool-package-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        var packageId = $('#package-id').val();
        var action = packageId > 0 ? 'cryptoschool_update_package' : 'cryptoschool_create_package';
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: formData + '&action=' + action + '&nonce=' + cryptoschool_admin.nonce,
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('<?php _e('Произошла ошибка при сохранении пакета.', 'cryptoschool'); ?>');
            }
        });
    });
    
    // Открытие модального окна для подтверждения удаления
    $(document).on('click', '.delete-package', function(e) {
        e.preventDefault();
        var packageId = $(this).data('id');
        $('#cryptoschool-confirm-delete').data('id', packageId);
        $('#cryptoschool-delete-modal').show();
    });
    
    // Удаление пакета
    $('#cryptoschool-confirm-delete').on('click', function() {
        var packageId = $(this).data('id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'cryptoschool_delete_package',
                nonce: cryptoschool_admin.nonce,
                id: packageId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('<?php _e('Произошла ошибка при удалении пакета.', 'cryptoschool'); ?>');
            }
        });
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
    
    // Фильтрация пакетов
    $('#cryptoschool-filter-apply').on('click', function() {
        var type = $('#cryptoschool-filter-type').val();
        var status = $('#cryptoschool-filter-status').val();
        var search = $('#cryptoschool-filter-search').val();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'cryptoschool_get_packages',
                nonce: cryptoschool_admin.nonce,
                package_type: type,
                is_active: status,
                search: search
            },
            success: function(response) {
                if (response.success) {
                    var packages = response.data;
                    var html = '';
                    
                    if (packages.length === 0) {
                        html = '<tr><td colspan="7"><?php _e('Пакеты не найдены.', 'cryptoschool'); ?></td></tr>';
                    } else {
                        for (var i = 0; i < packages.length; i++) {
                            var package = packages[i];
                            html += '<tr data-id="' + package.id + '">';
                            html += '<td class="column-id">' + package.id + '</td>';
                            html += '<td class="column-title">';
                            html += '<strong>' + package.title + '</strong>';
                            html += '<div class="row-actions">';
                            html += '<span class="edit"><a href="#" class="edit-package" data-id="' + package.id + '"><?php _e('Редактировать', 'cryptoschool'); ?></a> | </span>';
                            html += '<span class="delete"><a href="#" class="delete-package" data-id="' + package.id + '"><?php _e('Удалить', 'cryptoschool'); ?></a></span>';
                            html += '</div>';
                            html += '</td>';
                            html += '<td class="column-type">';
                            
                            switch (package.package_type) {
                                case 'course':
                                    html += '<?php _e('Только обучение', 'cryptoschool'); ?>';
                                    break;
                                case 'community':
                                    html += '<?php _e('Только приватка', 'cryptoschool'); ?>';
                                    break;
                                case 'combined':
                                    html += '<?php _e('Комбинированный', 'cryptoschool'); ?>';
                                    break;
                                default:
                                    html += package.package_type;
                            }
                            
                            html += '</td>';
                            html += '<td class="column-price">' + package.price + ' USD</td>';
                            html += '<td class="column-duration">';
                            
                            if (package.duration_months === null) {
                                html += '<?php _e('Пожизненно', 'cryptoschool'); ?>';
                            } else {
                                html += package.duration_months + ' ';
                                if (package.duration_months == 1) {
                                    html += '<?php _e('месяц', 'cryptoschool'); ?>';
                                } else if (package.duration_months > 1 && package.duration_months < 5) {
                                    html += '<?php _e('месяца', 'cryptoschool'); ?>';
                                } else {
                                    html += '<?php _e('месяцев', 'cryptoschool'); ?>';
                                }
                            }
                            
                            html += '</td>';
                            html += '<td class="column-status">';
                            if (package.is_active == 1) {
                                html += '<span class="status-active"><?php _e('Активен', 'cryptoschool'); ?></span>';
                            } else {
                                html += '<span class="status-inactive"><?php _e('Неактивен', 'cryptoschool'); ?></span>';
                            }
                            html += '</td>';
                            html += '<td class="column-actions">';
                            html += '<a href="#" class="edit-package" data-id="' + package.id + '"><span class="dashicons dashicons-edit"></span></a> ';
                            html += '<a href="#" class="delete-package" data-id="' + package.id + '"><span class="dashicons dashicons-trash"></span></a>';
                            html += '</td>';
                            html += '</tr>';
                        }
                    }
                    
                    $('#cryptoschool-packages-list').html(html);
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('<?php _e('Произошла ошибка при загрузке пакетов.', 'cryptoschool'); ?>');
            }
        });
    });
    
    // Сброс фильтров
    $('#cryptoschool-filter-reset').on('click', function() {
        $('#cryptoschool-filter-type').val('');
        $('#cryptoschool-filter-status').val('');
        $('#cryptoschool-filter-search').val('');
        $('#cryptoschool-filter-apply').click();
    });
});
</script>
