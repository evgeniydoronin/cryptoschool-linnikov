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

// Подготовка данных пользователей для JavaScript
$users_data = array();
foreach ($users as $user) {
    $users_data[] = array(
        'id' => $user->ID,
        'name' => $user->display_name,
        'email' => $user->user_email
    );
}

// Подготовка данных пакетов для JavaScript
$packages_data = array();
foreach ($packages as $package) {
    $packages_data[] = array(
        'id' => $package->id,
        'title' => $package->title,
        'type' => $package->package_type
    );
}

// Локализация для JavaScript
wp_enqueue_script('cryptoschool-user-accesses', CRYPTOSCHOOL_PLUGIN_URL . 'admin/js/user-accesses.js', array('jquery'), CRYPTOSCHOOL_VERSION, false);

// Передаем переводы строк и данные в JavaScript
wp_localize_script('cryptoschool-user-accesses', 'cryptoschool_user_accesses', array(
    'users' => $users_data,
    'packages' => $packages_data,
    'nonce' => wp_create_nonce('cryptoschool_admin_nonce'),
    'text_add_access' => __('Добавить доступ', 'cryptoschool'),
    'text_edit_access' => __('Редактировать доступ', 'cryptoschool'),
    'text_no_accesses' => __('Доступы не найдены.', 'cryptoschool'),
    'text_user_not_found' => __('Пользователь не найден', 'cryptoschool'),
    'text_package_not_found' => __('Пакет не найден', 'cryptoschool'),
    'text_lifetime' => __('Пожизненно', 'cryptoschool'),
    'text_active' => __('Активен', 'cryptoschool'),
    'text_expired' => __('Истек', 'cryptoschool'),
    'text_telegram_none' => __('Нет доступа', 'cryptoschool'),
    'text_telegram_invited' => __('Приглашен', 'cryptoschool'),
    'text_telegram_active' => __('Активен', 'cryptoschool'),
    'text_telegram_removed' => __('Удален', 'cryptoschool'),
    'text_edit' => __('Редактировать', 'cryptoschool'),
    'text_delete' => __('Удалить', 'cryptoschool'),
    'text_invite_telegram' => __('Пригласить в Telegram', 'cryptoschool'),
    'text_activate_telegram' => __('Активировать в Telegram', 'cryptoschool'),
    'text_remove_telegram' => __('Удалить из Telegram', 'cryptoschool'),
    'error_loading_access' => __('Произошла ошибка при загрузке данных доступа.', 'cryptoschool'),
    'error_saving_access' => __('Произошла ошибка при сохранении доступа.', 'cryptoschool'),
    'error_deleting_access' => __('Произошла ошибка при удалении доступа.', 'cryptoschool'),
    'error_updating_telegram' => __('Произошла ошибка при обновлении статуса в Telegram.', 'cryptoschool'),
    'error_loading_accesses' => __('Произошла ошибка при загрузке доступов.', 'cryptoschool')
));
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
                            // Находим пакет в массиве $packages
                            $package = null;
                            foreach ($packages as $p) {
                                if ($p->id == $access->package_id) {
                                    $package = $p;
                                    break;
                                }
                            }
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
                                    <a href="#" class="edit-user-access" data-id="<?php echo esc_attr($access->id); ?>" title="<?php _e('Редактировать', 'cryptoschool'); ?>"><span class="dashicons dashicons-edit"></span></a>
                                    <a href="#" class="delete-user-access" data-id="<?php echo esc_attr($access->id); ?>" title="<?php _e('Удалить', 'cryptoschool'); ?>"><span class="dashicons dashicons-trash"></span></a>
                                    <?php if ($package && ($package->package_type === 'community' || $package->package_type === 'combined')) : ?>
                                        <?php if ($access->telegram_status === 'none') : ?>
                                            <a href="#" class="invite-telegram" data-id="<?php echo esc_attr($access->id); ?>" title="<?php _e('Пригласить в Telegram', 'cryptoschool'); ?>"><span class="dashicons dashicons-admin-users"></span></a>
                                        <?php elseif ($access->telegram_status === 'invited') : ?>
                                            <a href="#" class="activate-telegram" data-id="<?php echo esc_attr($access->id); ?>" title="<?php _e('Активировать в Telegram', 'cryptoschool'); ?>"><span class="dashicons dashicons-yes"></span></a>
                                        <?php elseif ($access->telegram_status === 'active') : ?>
                                            <a href="#" class="remove-telegram" data-id="<?php echo esc_attr($access->id); ?>" title="<?php _e('Удалить из Telegram', 'cryptoschool'); ?>"><span class="dashicons dashicons-no"></span></a>
                                        <?php elseif ($access->telegram_status === 'removed') : ?>
                                            <a href="#" class="invite-telegram" data-id="<?php echo esc_attr($access->id); ?>" title="<?php _e('Пригласить в Telegram', 'cryptoschool'); ?>"><span class="dashicons dashicons-admin-users"></span></a>
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
</script>
