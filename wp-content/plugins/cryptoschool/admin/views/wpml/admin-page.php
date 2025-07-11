<?php
/**
 * Шаблон административной страницы управления переводами WPML
 *
 * @package CryptoSchool
 * @subpackage Admin\Views
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

$stats = $this->get_translation_stats();
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="notice notice-info">
        <p>
            <?php _e('Эта страница позволяет управлять переводами курсов и уроков через WPML.', 'cryptoschool'); ?>
            <?php _e('Система поддерживает два режима: переводы строк (старый) и Custom Post Types (новый, рекомендуется).', 'cryptoschool'); ?>
        </p>
    </div>

    <div class="notice notice-warning">
        <p>
            <strong><?php _e('🚀 Новая система переводов!', 'cryptoschool'); ?></strong>
            <?php _e('Теперь доступна интеграция через Custom Post Types для полноценной работы с WPML.', 'cryptoschool'); ?>
            <a href="<?php echo plugin_dir_url(dirname(dirname(__FILE__))) . 'migrate-to-posts.php'; ?>" class="button button-primary" style="margin-left: 10px;">
                <?php _e('Миграция в посты', 'cryptoschool'); ?>
            </a>
        </p>
    </div>

    <!-- Статистика -->
    <div class="cryptoschool-wpml-stats">
        <h2><?php _e('Статистика переводов', 'cryptoschool'); ?></h2>
        
        <div class="cryptoschool-stats-grid">
            <div class="cryptoschool-stat-card">
                <h3><?php _e('Курсы', 'cryptoschool'); ?></h3>
                <div class="stat-number"><?php echo esc_html($stats['total_courses'] ?? 0); ?></div>
                <p><?php _e('Всего курсов в системе', 'cryptoschool'); ?></p>
            </div>
            
            <div class="cryptoschool-stat-card">
                <h3><?php _e('Уроки', 'cryptoschool'); ?></h3>
                <div class="stat-number"><?php echo esc_html($stats['total_lessons'] ?? 0); ?></div>
                <p><?php _e('Всего уроков в системе', 'cryptoschool'); ?></p>
            </div>
            
            <div class="cryptoschool-stat-card">
                <h3><?php _e('Языки', 'cryptoschool'); ?></h3>
                <div class="stat-number"><?php echo esc_html($stats['total_languages'] ?? 0); ?></div>
                <p><?php _e('Активных языков', 'cryptoschool'); ?></p>
            </div>
        </div>
    </div>

    <!-- Информация о языках -->
    <div class="cryptoschool-wpml-languages">
        <h2><?php _e('Активные языки', 'cryptoschool'); ?></h2>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Код языка', 'cryptoschool'); ?></th>
                    <th><?php _e('Название', 'cryptoschool'); ?></th>
                    <th><?php _e('Статус', 'cryptoschool'); ?></th>
                    <th><?php _e('Действия', 'cryptoschool'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($active_languages)): ?>
                    <?php foreach ($active_languages as $lang_code => $language): ?>
                        <tr>
                            <td><strong><?php echo esc_html($lang_code); ?></strong></td>
                            <td>
                                <?php echo esc_html($language['native_name'] ?? $language['display_name'] ?? $lang_code); ?>
                                <?php if ($lang_code === $default_language): ?>
                                    <span class="dashicons dashicons-star-filled" title="<?php _e('Язык по умолчанию', 'cryptoschool'); ?>"></span>
                                <?php endif; ?>
                                <?php if ($lang_code === $current_language): ?>
                                    <span class="dashicons dashicons-admin-site" title="<?php _e('Текущий язык', 'cryptoschool'); ?>"></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-active"><?php _e('Активен', 'cryptoschool'); ?></span>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=wpml-string-translation/menu/string-translation.php&context=CryptoSchool'); ?>" 
                                   class="button button-small">
                                    <?php _e('Управление переводами', 'cryptoschool'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">
                            <em><?php _e('Активные языки не найдены.', 'cryptoschool'); ?></em>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Действия с переводами -->
    <div class="cryptoschool-wpml-actions">
        <h2><?php _e('Действия с переводами', 'cryptoschool'); ?></h2>
        
        <div class="cryptoschool-action-cards">
            <div class="cryptoschool-action-card">
                <h3><?php _e('Регистрация строк', 'cryptoschool'); ?></h3>
                <p><?php _e('Зарегистрируйте строки курсов и уроков для перевода в WPML.', 'cryptoschool'); ?></p>
                
                <div class="action-buttons">
                    <button type="button" class="button button-primary" 
                            data-action="register-strings" data-type="courses">
                        <?php _e('Регистрировать курсы', 'cryptoschool'); ?>
                    </button>
                    
                    <button type="button" class="button button-primary" 
                            data-action="register-strings" data-type="lessons">
                        <?php _e('Регистрировать уроки', 'cryptoschool'); ?>
                    </button>
                    
                    <button type="button" class="button button-secondary" 
                            data-action="register-strings" data-type="all">
                        <?php _e('Регистрировать всё', 'cryptoschool'); ?>
                    </button>
                </div>
            </div>
            
            <div class="cryptoschool-action-card">
                <h3><?php _e('Синхронизация', 'cryptoschool'); ?></h3>
                <p><?php _e('Синхронизируйте переводы и обновите кеш.', 'cryptoschool'); ?></p>
                
                <div class="action-buttons">
                    <button type="button" class="button button-secondary" 
                            data-action="sync-translations">
                        <?php _e('Синхронизировать переводы', 'cryptoschool'); ?>
                    </button>
                </div>
            </div>
            
            <div class="cryptoschool-action-card">
                <h3><?php _e('Управление переводами', 'cryptoschool'); ?></h3>
                <p><?php _e('Перейдите к интерфейсу WPML для управления переводами.', 'cryptoschool'); ?></p>
                
                <div class="action-buttons">
                    <a href="<?php echo admin_url('admin.php?page=wpml-string-translation/menu/string-translation.php'); ?>" 
                       class="button button-primary">
                        <?php _e('Переводы строк WPML', 'cryptoschool'); ?>
                    </a>
                    
                    <a href="<?php echo admin_url('admin.php?page=wpml-translation-management/menu/main.php'); ?>" 
                       class="button button-secondary">
                        <?php _e('Управление переводами', 'cryptoschool'); ?>
                    </a>
                </div>
            </div>

            <div class="cryptoschool-action-card" style="border: 2px solid #0073aa;">
                <h3><?php _e('🚀 Custom Post Types (Новое!)', 'cryptoschool'); ?></h3>
                <p><?php _e('Новая система переводов через WordPress посты. Обеспечивает полноценную интеграцию с WPML и удобный интерфейс для длинных текстов.', 'cryptoschool'); ?></p>
                
                <div class="action-buttons">
                    <a href="<?php echo admin_url('edit.php?post_type=cryptoschool_course'); ?>" 
                       class="button button-primary">
                        <?php _e('Курсы (WPML)', 'cryptoschool'); ?>
                    </a>
                    
                    <a href="<?php echo admin_url('edit.php?post_type=cryptoschool_lesson'); ?>" 
                       class="button button-primary">
                        <?php _e('Уроки (WPML)', 'cryptoschool'); ?>
                    </a>
                    
                    <a href="<?php echo admin_url('admin.php?page=wpml-translation-management/menu/main.php'); ?>" 
                       class="button button-secondary">
                        <?php _e('Управление переводами постов', 'cryptoschool'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Быстрые ссылки -->
    <div class="cryptoschool-wpml-quick-links">
        <h2><?php _e('Быстрые ссылки', 'cryptoschool'); ?></h2>
        
        <ul class="quick-links-list">
            <li>
                <a href="<?php echo admin_url('admin.php?page=wpml-string-translation/menu/string-translation.php&context=CryptoSchool%20Courses'); ?>">
                    <span class="dashicons dashicons-book"></span>
                    <?php _e('Переводы курсов', 'cryptoschool'); ?>
                </a>
            </li>
            <li>
                <a href="<?php echo admin_url('admin.php?page=wpml-string-translation/menu/string-translation.php&context=CryptoSchool%20Lessons'); ?>">
                    <span class="dashicons dashicons-media-document"></span>
                    <?php _e('Переводы уроков', 'cryptoschool'); ?>
                </a>
            </li>
            <li>
                <a href="<?php echo admin_url('admin.php?page=wpml-string-translation/menu/string-translation.php&context=CryptoSchool%20Tasks'); ?>">
                    <span class="dashicons dashicons-list-view"></span>
                    <?php _e('Переводы заданий', 'cryptoschool'); ?>
                </a>
            </li>
            <li>
                <a href="<?php echo admin_url('admin.php?page=wpml-translation-management/menu/translations-queue.php'); ?>">
                    <span class="dashicons dashicons-clock"></span>
                    <?php _e('Очередь переводов', 'cryptoschool'); ?>
                </a>
            </li>
        </ul>
    </div>
</div>

<!-- Модальное окно для отображения результатов -->
<div id="cryptoschool-wpml-modal" class="cryptoschool-modal" style="display: none;">
    <div class="cryptoschool-modal-content">
        <div class="cryptoschool-modal-header">
            <h3 id="cryptoschool-modal-title"><?php _e('Результат операции', 'cryptoschool'); ?></h3>
            <span class="cryptoschool-modal-close">&times;</span>
        </div>
        <div class="cryptoschool-modal-body">
            <div id="cryptoschool-modal-message"></div>
        </div>
        <div class="cryptoschool-modal-footer">
            <button type="button" class="button" id="cryptoschool-modal-close-btn">
                <?php _e('Закрыть', 'cryptoschool'); ?>
            </button>
        </div>
    </div>
</div>

<style>
.cryptoschool-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.cryptoschool-stat-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.cryptoschool-stat-card h3 {
    margin: 0 0 10px 0;
    color: #23282d;
}

.stat-number {
    font-size: 2.5em;
    font-weight: bold;
    color: #0073aa;
    margin: 10px 0;
}

.cryptoschool-action-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.cryptoschool-action-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.cryptoschool-action-card h3 {
    margin: 0 0 10px 0;
    color: #23282d;
}

.action-buttons {
    margin-top: 15px;
}

.action-buttons .button {
    margin-right: 10px;
    margin-bottom: 5px;
}

.quick-links-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 10px;
    list-style: none;
    padding: 0;
}

.quick-links-list li a {
    display: flex;
    align-items: center;
    padding: 10px 15px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    text-decoration: none;
    color: #23282d;
    transition: all 0.2s ease;
}

.quick-links-list li a:hover {
    background: #f8f9fa;
    border-color: #0073aa;
}

.quick-links-list li a .dashicons {
    margin-right: 10px;
    color: #0073aa;
}

.status-active {
    color: #46b450;
    font-weight: bold;
}

/* Модальное окно */
.cryptoschool-modal {
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.cryptoschool-modal-content {
    background-color: #fff;
    margin: 10% auto;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    width: 80%;
    max-width: 500px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.cryptoschool-modal-header {
    padding: 15px 20px;
    border-bottom: 1px solid #ccd0d4;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.cryptoschool-modal-header h3 {
    margin: 0;
}

.cryptoschool-modal-close {
    font-size: 24px;
    font-weight: bold;
    cursor: pointer;
    color: #666;
}

.cryptoschool-modal-close:hover {
    color: #000;
}

.cryptoschool-modal-body {
    padding: 20px;
}

.cryptoschool-modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #ccd0d4;
    text-align: right;
}

.loading {
    opacity: 0.6;
    pointer-events: none;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Обработчики для кнопок действий
    $('[data-action="register-strings"]').on('click', function() {
        var $button = $(this);
        var type = $button.data('type');
        
        $button.addClass('loading').prop('disabled', true);
        $button.text('<?php _e('Обработка...', 'cryptoschool'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'cryptoschool_register_strings',
                type: type,
                nonce: '<?php echo wp_create_nonce('cryptoschool_wpml_nonce'); ?>'
            },
            success: function(response) {
                showModal(response.success ? 'success' : 'error', response.message);
            },
            error: function() {
                showModal('error', '<?php _e('Произошла ошибка при выполнении запроса.', 'cryptoschool'); ?>');
            },
            complete: function() {
                $button.removeClass('loading').prop('disabled', false);
                // Восстанавливаем текст кнопки
                switch(type) {
                    case 'courses':
                        $button.text('<?php _e('Регистрировать курсы', 'cryptoschool'); ?>');
                        break;
                    case 'lessons':
                        $button.text('<?php _e('Регистрировать уроки', 'cryptoschool'); ?>');
                        break;
                    case 'all':
                        $button.text('<?php _e('Регистрировать всё', 'cryptoschool'); ?>');
                        break;
                }
            }
        });
    });
    
    $('[data-action="sync-translations"]').on('click', function() {
        var $button = $(this);
        
        $button.addClass('loading').prop('disabled', true);
        $button.text('<?php _e('Синхронизация...', 'cryptoschool'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'cryptoschool_sync_translations',
                nonce: '<?php echo wp_create_nonce('cryptoschool_wpml_nonce'); ?>'
            },
            success: function(response) {
                showModal(response.success ? 'success' : 'error', response.message);
            },
            error: function() {
                showModal('error', '<?php _e('Произошла ошибка при выполнении запроса.', 'cryptoschool'); ?>');
            },
            complete: function() {
                $button.removeClass('loading').prop('disabled', false);
                $button.text('<?php _e('Синхронизировать переводы', 'cryptoschool'); ?>');
            }
        });
    });
    
    // Функция для отображения модального окна
    function showModal(type, message) {
        var $modal = $('#cryptoschool-wpml-modal');
        var $message = $('#cryptoschool-modal-message');
        
        $message.removeClass('notice-success notice-error');
        $message.addClass('notice notice-' + (type === 'success' ? 'success' : 'error'));
        $message.html('<p>' + message + '</p>');
        
        $modal.show();
    }
    
    // Закрытие модального окна
    $('.cryptoschool-modal-close, #cryptoschool-modal-close-btn').on('click', function() {
        $('#cryptoschool-wpml-modal').hide();
    });
    
    // Закрытие модального окна при клике вне его
    $(window).on('click', function(event) {
        if (event.target.id === 'cryptoschool-wpml-modal') {
            $('#cryptoschool-wpml-modal').hide();
        }
    });
});
</script>
