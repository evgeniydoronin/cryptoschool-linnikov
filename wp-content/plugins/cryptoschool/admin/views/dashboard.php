<?php
/**
 * Шаблон для отображения дашборда административной части
 *
 * @package CryptoSchool
 * @subpackage Admin\Views
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

// Получение статистики
$courses_count = isset($courses_count) ? $courses_count : 0;
$lessons_count = isset($lessons_count) ? $lessons_count : 0;
$users_count = isset($users_count) ? $users_count : 0;
$packages_count = isset($packages_count) ? $packages_count : 0;
$user_accesses_count = isset($user_accesses_count) ? $user_accesses_count : 0;

// Получение последних курсов
$courses = isset($courses) ? $courses : array();
?>

<div class="wrap cryptoschool-admin">
    <h1><?php _e('Дашборд Крипто Школы', 'cryptoschool'); ?></h1>

    <div class="cryptoschool-dashboard">
        <div class="cryptoschool-dashboard-header">
            <div class="cryptoschool-dashboard-welcome">
                <h2><?php _e('Добро пожаловать в административную панель Крипто Школы!', 'cryptoschool'); ?></h2>
                <p><?php _e('Здесь вы можете управлять курсами, уроками, пакетами и доступами пользователей.', 'cryptoschool'); ?></p>
            </div>
        </div>

        <div class="cryptoschool-dashboard-stats">
            <h3><?php _e('Статистика', 'cryptoschool'); ?></h3>
            <div class="cryptoschool-stats-grid">
                <div class="cryptoschool-stat-card">
                    <div class="cryptoschool-stat-icon dashicons dashicons-welcome-learn-more"></div>
                    <div class="cryptoschool-stat-content">
                        <div class="cryptoschool-stat-value"><?php echo esc_html($courses_count); ?></div>
                        <div class="cryptoschool-stat-label"><?php _e('Курсов', 'cryptoschool'); ?></div>
                    </div>
                </div>
                <div class="cryptoschool-stat-card">
                    <div class="cryptoschool-stat-icon dashicons dashicons-media-text"></div>
                    <div class="cryptoschool-stat-content">
                        <div class="cryptoschool-stat-value"><?php echo esc_html($lessons_count); ?></div>
                        <div class="cryptoschool-stat-label"><?php _e('Уроков', 'cryptoschool'); ?></div>
                    </div>
                </div>
                <div class="cryptoschool-stat-card">
                    <div class="cryptoschool-stat-icon dashicons dashicons-groups"></div>
                    <div class="cryptoschool-stat-content">
                        <div class="cryptoschool-stat-value"><?php echo esc_html($users_count); ?></div>
                        <div class="cryptoschool-stat-label"><?php _e('Пользователей', 'cryptoschool'); ?></div>
                    </div>
                </div>
                <div class="cryptoschool-stat-card">
                    <div class="cryptoschool-stat-icon dashicons dashicons-cart"></div>
                    <div class="cryptoschool-stat-content">
                        <div class="cryptoschool-stat-value"><?php echo esc_html($packages_count); ?></div>
                        <div class="cryptoschool-stat-label"><?php _e('Пакетов', 'cryptoschool'); ?></div>
                    </div>
                </div>
                <div class="cryptoschool-stat-card">
                    <div class="cryptoschool-stat-icon dashicons dashicons-unlock"></div>
                    <div class="cryptoschool-stat-content">
                        <div class="cryptoschool-stat-value"><?php echo esc_html($user_accesses_count); ?></div>
                        <div class="cryptoschool-stat-label"><?php _e('Доступов', 'cryptoschool'); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="cryptoschool-dashboard-recent">
            <h3><?php _e('Последние курсы', 'cryptoschool'); ?></h3>
            <?php if (!empty($courses)) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Название', 'cryptoschool'); ?></th>
                            <th><?php _e('Сложность', 'cryptoschool'); ?></th>
                            <th><?php _e('Уроки', 'cryptoschool'); ?></th>
                            <th><?php _e('Статус', 'cryptoschool'); ?></th>
                            <th><?php _e('Дата создания', 'cryptoschool'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $course) : ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=cryptoschool-lessons&course_id=' . $course->id)); ?>">
                                        <?php echo esc_html($course->title); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html($course->difficulty_level); ?></td>
                                <td><?php echo isset($course->lessons_count) ? esc_html($course->lessons_count) : '0'; ?></td>
                                <td>
                                    <?php if ($course->is_active) : ?>
                                        <span class="cryptoschool-status cryptoschool-status-active"><?php _e('Активен', 'cryptoschool'); ?></span>
                                    <?php else : ?>
                                        <span class="cryptoschool-status cryptoschool-status-inactive"><?php _e('Неактивен', 'cryptoschool'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($course->get_created_at()); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="cryptoschool-empty-state">
                    <p><?php _e('Пока нет созданных курсов.', 'cryptoschool'); ?></p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=cryptoschool-courses')); ?>" class="button button-primary">
                        <?php _e('Создать курс', 'cryptoschool'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="cryptoschool-dashboard-actions">
            <h3><?php _e('Быстрые действия', 'cryptoschool'); ?></h3>
            <div class="cryptoschool-actions-grid">
                <a href="<?php echo esc_url(admin_url('admin.php?page=cryptoschool-courses')); ?>" class="cryptoschool-action-card">
                    <div class="cryptoschool-action-icon dashicons dashicons-welcome-learn-more"></div>
                    <div class="cryptoschool-action-content">
                        <div class="cryptoschool-action-title"><?php _e('Управление курсами', 'cryptoschool'); ?></div>
                        <div class="cryptoschool-action-description"><?php _e('Создание, редактирование и удаление курсов', 'cryptoschool'); ?></div>
                    </div>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=cryptoschool-packages')); ?>" class="cryptoschool-action-card">
                    <div class="cryptoschool-action-icon dashicons dashicons-cart"></div>
                    <div class="cryptoschool-action-content">
                        <div class="cryptoschool-action-title"><?php _e('Управление пакетами', 'cryptoschool'); ?></div>
                        <div class="cryptoschool-action-description"><?php _e('Создание, редактирование и удаление пакетов', 'cryptoschool'); ?></div>
                    </div>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=cryptoschool-user-accesses')); ?>" class="cryptoschool-action-card">
                    <div class="cryptoschool-action-icon dashicons dashicons-unlock"></div>
                    <div class="cryptoschool-action-content">
                        <div class="cryptoschool-action-title"><?php _e('Управление доступами', 'cryptoschool'); ?></div>
                        <div class="cryptoschool-action-description"><?php _e('Управление доступами пользователей', 'cryptoschool'); ?></div>
                    </div>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=cryptoschool-referrals')); ?>" class="cryptoschool-action-card">
                    <div class="cryptoschool-action-icon dashicons dashicons-share"></div>
                    <div class="cryptoschool-action-content">
                        <div class="cryptoschool-action-title"><?php _e('Реферальная система', 'cryptoschool'); ?></div>
                        <div class="cryptoschool-action-description"><?php _e('Управление реферальной системой', 'cryptoschool'); ?></div>
                    </div>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=cryptoschool-settings')); ?>" class="cryptoschool-action-card">
                    <div class="cryptoschool-action-icon dashicons dashicons-admin-settings"></div>
                    <div class="cryptoschool-action-content">
                        <div class="cryptoschool-action-title"><?php _e('Настройки', 'cryptoschool'); ?></div>
                        <div class="cryptoschool-action-description"><?php _e('Настройки плагина', 'cryptoschool'); ?></div>
                    </div>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=cryptoschool-help')); ?>" class="cryptoschool-action-card">
                    <div class="cryptoschool-action-icon dashicons dashicons-editor-help"></div>
                    <div class="cryptoschool-action-content">
                        <div class="cryptoschool-action-title"><?php _e('Помощь', 'cryptoschool'); ?></div>
                        <div class="cryptoschool-action-description"><?php _e('Документация и помощь', 'cryptoschool'); ?></div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>
