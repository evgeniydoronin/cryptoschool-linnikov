<?php
/**
 * Шаблон метабокса переводов WPML
 *
 * @package CryptoSchool
 * @subpackage Admin\Views
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="cryptoschool-wpml-meta-box">
    <?php if (!empty($active_languages)): ?>
        <div class="wpml-languages-info">
            <p><strong><?php _e('Активные языки:', 'cryptoschool'); ?></strong></p>
            
            <ul class="wpml-languages-list">
                <?php foreach ($active_languages as $lang_code => $language): ?>
                    <li class="wpml-language-item <?php echo $lang_code === $current_language ? 'current-language' : ''; ?>">
                        <span class="language-flag">
                            <?php if (isset($language['country_flag_url'])): ?>
                                <img src="<?php echo esc_url($language['country_flag_url']); ?>" 
                                     alt="<?php echo esc_attr($language['display_name'] ?? $language['native_name'] ?? $language['english_name'] ?? $lang_code); ?>" 
                                     width="16" height="12">
                            <?php endif; ?>
                        </span>
                        
                        <span class="language-name">
                            <?php echo esc_html($language['native_name'] ?? $language['display_name'] ?? $language['english_name'] ?? $lang_code); ?>
                            (<?php echo esc_html($lang_code); ?>)
                        </span>
                        
                        <?php if ($lang_code === $current_language): ?>
                            <span class="current-indicator"><?php _e('текущий', 'cryptoschool'); ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div class="wpml-translation-actions">
            <p><strong><?php _e('Действия с переводами:', 'cryptoschool'); ?></strong></p>
            
            <div class="wpml-action-buttons">
                <button type="button" class="button button-small" 
                        onclick="registerCurrentItemStrings()">
                    <?php _e('Зарегистрировать для перевода', 'cryptoschool'); ?>
                </button>
                
                <a href="<?php echo admin_url('admin.php?page=wpml-string-translation/menu/string-translation.php'); ?>" 
                   class="button button-small" target="_blank">
                    <?php _e('Управление переводами', 'cryptoschool'); ?>
                </a>
            </div>
        </div>
        
        <div class="wpml-translation-status">
            <p><strong><?php _e('Статус переводов:', 'cryptoschool'); ?></strong></p>
            
            <div class="translation-status-grid">
                <?php foreach ($active_languages as $lang_code => $language): ?>
                    <?php if ($lang_code !== $current_language): ?>
                        <div class="translation-status-item">
                            <span class="language-code"><?php echo esc_html($lang_code); ?>:</span>
                            <span class="status-indicator status-unknown">
                                <?php _e('Неизвестно', 'cryptoschool'); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
            <p class="description">
                <?php _e('Для получения актуального статуса переводов перейдите в интерфейс WPML.', 'cryptoschool'); ?>
            </p>
        </div>
        
    <?php else: ?>
        <div class="wpml-no-languages">
            <p><?php _e('Активные языки не найдены.', 'cryptoschool'); ?></p>
            <p class="description">
                <?php _e('Настройте языки в WPML для использования функций перевода.', 'cryptoschool'); ?>
            </p>
        </div>
    <?php endif; ?>
</div>

<style>
.cryptoschool-wpml-meta-box {
    font-size: 13px;
}

.wpml-languages-list {
    margin: 10px 0;
    padding: 0;
    list-style: none;
}

.wpml-language-item {
    display: flex;
    align-items: center;
    padding: 5px 0;
    border-bottom: 1px solid #f0f0f1;
}

.wpml-language-item:last-child {
    border-bottom: none;
}

.wpml-language-item.current-language {
    background-color: #f0f6fc;
    padding: 5px 8px;
    border-radius: 3px;
    border-bottom: 1px solid #c3c4c7;
}

.language-flag {
    margin-right: 8px;
    min-width: 20px;
}

.language-name {
    flex: 1;
    font-weight: 500;
}

.current-indicator {
    font-size: 11px;
    color: #0073aa;
    font-weight: bold;
    text-transform: uppercase;
}

.wpml-translation-actions {
    margin: 15px 0;
    padding: 10px 0;
    border-top: 1px solid #f0f0f1;
}

.wpml-action-buttons {
    margin-top: 8px;
}

.wpml-action-buttons .button {
    margin-right: 8px;
    margin-bottom: 5px;
}

.wpml-translation-status {
    margin: 15px 0;
    padding: 10px 0;
    border-top: 1px solid #f0f0f1;
}

.translation-status-grid {
    margin: 8px 0;
}

.translation-status-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 3px 0;
}

.language-code {
    font-weight: 500;
    text-transform: uppercase;
}

.status-indicator {
    font-size: 11px;
    padding: 2px 6px;
    border-radius: 3px;
    text-transform: uppercase;
    font-weight: bold;
}

.status-unknown {
    background-color: #f0f0f1;
    color: #646970;
}

.status-translated {
    background-color: #d1e7dd;
    color: #0f5132;
}

.status-pending {
    background-color: #fff3cd;
    color: #664d03;
}

.status-missing {
    background-color: #f8d7da;
    color: #721c24;
}

.wpml-no-languages {
    text-align: center;
    padding: 20px 0;
    color: #646970;
}

.description {
    font-style: italic;
    color: #646970;
    margin-top: 8px;
}
</style>

<script>
function registerCurrentItemStrings() {
    // Получаем ID текущего элемента
    var postId = jQuery('#post_ID').val();
    var postType = jQuery('#post_type').val();
    
    if (!postId) {
        alert('<?php _e('Сначала сохраните элемент, а затем зарегистрируйте его для перевода.', 'cryptoschool'); ?>');
        return;
    }
    
    var actionType = '';
    if (postType === 'cryptoschool_course') {
        actionType = 'courses';
    } else if (postType === 'cryptoschool_lesson') {
        actionType = 'lessons';
    } else {
        alert('<?php _e('Неподдерживаемый тип элемента.', 'cryptoschool'); ?>');
        return;
    }
    
    // Показываем индикатор загрузки
    var button = event.target;
    var originalText = button.textContent;
    button.textContent = '<?php _e('Регистрация...', 'cryptoschool'); ?>';
    button.disabled = true;
    
    // Отправляем AJAX запрос
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'cryptoschool_register_strings',
            type: actionType,
            nonce: '<?php echo wp_create_nonce('cryptoschool_wpml_nonce'); ?>'
        },
        success: function(response) {
            if (response.success) {
                alert('<?php _e('Строки успешно зарегистрированы для перевода!', 'cryptoschool'); ?>');
            } else {
                alert('<?php _e('Ошибка: ', 'cryptoschool'); ?>' + response.message);
            }
        },
        error: function() {
            alert('<?php _e('Произошла ошибка при регистрации строк.', 'cryptoschool'); ?>');
        },
        complete: function() {
            // Восстанавливаем кнопку
            button.textContent = originalText;
            button.disabled = false;
        }
    });
}
</script>
