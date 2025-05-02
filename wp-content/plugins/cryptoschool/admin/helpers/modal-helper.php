<?php
/**
 * Функции-помощники для работы с модальными окнами
 *
 * @package CryptoSchool
 * @subpackage Admin
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Отображает модальное окно
 *
 * @param string $modal_id ID модального окна
 * @param string $modal_title Заголовок модального окна
 * @param string $modal_content Содержимое модального окна
 * @param string $modal_size Размер модального окна (small, medium, large)
 */
function cryptoschool_render_modal($modal_id, $modal_title = '', $modal_content = '', $modal_size = '') {
    // Подключаем шаблон модального окна
    include CRYPTOSCHOOL_PLUGIN_DIR . 'admin/views/partials/modal.php';
}

/**
 * Отображает модальное окно подтверждения удаления
 *
 * @param string $modal_id ID модального окна
 * @param string $entity_name Название сущности (курс, модуль, урок и т.д.)
 * @param string $confirm_button_id ID кнопки подтверждения
 */
function cryptoschool_render_delete_modal($modal_id, $entity_name, $confirm_button_id = 'cryptoschool-confirm-delete') {
    $modal_title = __('Подтверждение удаления', 'cryptoschool');
    
    $modal_content = '
        <p>' . sprintf(__('Вы уверены, что хотите удалить этот %s? Это действие нельзя отменить.', 'cryptoschool'), $entity_name) . '</p>
        
        <div class="cryptoschool-admin-form-row">
            <button type="button" id="' . esc_attr($confirm_button_id) . '" class="button button-primary" data-id="0">' . __('Удалить', 'cryptoschool') . '</button>
            <button type="button" class="button cryptoschool-modal-cancel">' . __('Отмена', 'cryptoschool') . '</button>
        </div>
    ';
    
    cryptoschool_render_modal($modal_id, $modal_title, $modal_content);
}

/**
 * Возвращает JavaScript-код для работы с модальными окнами
 *
 * @return string JavaScript-код
 */
function cryptoschool_get_modal_js() {
    ob_start();
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Закрытие модальных окон
        $('.cryptoschool-admin-modal-close, .cryptoschool-modal-cancel').on('click', function() {
            $('.cryptoschool-admin-modal').hide();
        });
        
        // Не закрываем модальные окна при клике вне содержимого, чтобы избежать случайного закрытия
    });
    </script>
    <?php
    return ob_get_clean();
}
