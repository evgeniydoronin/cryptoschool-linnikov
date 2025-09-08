<?php
/**
 * Glossary Alphabet Links Widget
 * 
 * Виджет для вывода алфавитных ссылок глоссария
 * 
 * @package CryptoSchool
 */

// Получаем все термины таксономии glossary-letter
$glossary_terms = get_terms(array(
    'taxonomy' => 'glossary-letter',
    'hide_empty' => true,
    'orderby' => 'name',
    'order' => 'ASC'
));

// Определяем текущий термин (если мы на странице таксономии)
$current_term = null;
if (is_tax('glossary-letter')) {
    $current_term = get_queried_object();
}
?>

<div class="categories-list__links">
    <?php if ($glossary_terms && !is_wp_error($glossary_terms)): ?>
        <?php foreach ($glossary_terms as $term): ?>
            <?php 
            $is_active = $current_term && $current_term->term_id === $term->term_id;
            ?>
            <a href="<?php echo esc_url(get_term_link($term)); ?>" 
               class="text categories-list__link<?php echo $is_active ? ' categories-list__link_active' : ''; ?>">
                <?php echo esc_html(strtoupper($term->name)); ?>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
</div>