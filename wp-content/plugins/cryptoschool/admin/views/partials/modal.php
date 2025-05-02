<?php
/**
 * Шаблон модального окна
 *
 * @package CryptoSchool
 * @subpackage Admin
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Параметры:
 * $modal_id - ID модального окна
 * $modal_title - Заголовок модального окна
 * $modal_content - Содержимое модального окна
 * $modal_size - Размер модального окна (small, medium, large)
 */

// Установка значений по умолчанию
$modal_id = isset($modal_id) ? $modal_id : 'cryptoschool-modal';
$modal_title = isset($modal_title) ? $modal_title : '';
$modal_content = isset($modal_content) ? $modal_content : '';
$modal_size = isset($modal_size) ? $modal_size : '';

// Определение класса размера
$size_class = '';
if ($modal_size === 'large') {
    $size_class = 'cryptoschool-admin-modal-content-large';
} elseif ($modal_size === 'small') {
    $size_class = 'cryptoschool-admin-modal-content-small';
}
?>

<div id="<?php echo esc_attr($modal_id); ?>" class="cryptoschool-admin-modal" style="display: none;">
    <div class="cryptoschool-admin-modal-content <?php echo esc_attr($size_class); ?>">
        <span class="cryptoschool-admin-modal-close">&times;</span>
        <?php if (!empty($modal_title)) : ?>
            <h2 id="<?php echo esc_attr($modal_id); ?>-title"><?php echo esc_html($modal_title); ?></h2>
        <?php endif; ?>
        
        <?php echo $modal_content; ?>
    </div>
</div>
