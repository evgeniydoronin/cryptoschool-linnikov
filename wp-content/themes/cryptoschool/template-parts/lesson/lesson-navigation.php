<?php
/**
 * Навигация между уроками
 * 
 * @package CryptoSchool
 */

if (!defined('ABSPATH')) {
    exit;
}

$navigation = $args['navigation'];
$is_lesson_completed = $args['is_lesson_completed'];
$prev_lesson = $navigation['prev_lesson'];
$next_lesson = $navigation['next_lesson'];
?>

<div class="bottom-navigation">
    <?php if ($prev_lesson) : ?>
        <!-- Если есть предыдущий урок, показываем кнопку "Попередній урок" -->
        <a href="<?php echo esc_url(cryptoschool_get_localized_url('/lesson/?id=' . $prev_lesson->ID)); ?>" class="bottom-navigation__item bottom-navigation__previous">
            <div class="bottom-navigation__arrow">
                <span class="icon-nav-arrow-left"></span>
            </div>
            <div class="bottom-navigation__label text-small"><?php _e('Попередній урок', 'cryptoschool'); ?></div>
        </a>
    <?php endif; ?>
    
    <?php if ($next_lesson) : ?>
        <!-- Если есть следующий урок, показываем кнопку "Наступний урок" -->
        <?php if ($is_lesson_completed) : ?>
            <!-- Если текущий урок пройден, делаем ссылку активной -->
            <a href="<?php echo esc_url(cryptoschool_get_localized_url('/lesson/?id=' . $next_lesson->ID)); ?>" class="bottom-navigation__item bottom-navigation__next">
                <div class="bottom-navigation__label text-small"><?php _e('Наступний урок', 'cryptoschool'); ?></div>
                <div class="bottom-navigation__arrow">
                    <span class="icon-nav-arrow-right"></span>
                </div>
            </a>
        <?php else : ?>
            <!-- Если текущий урок не пройден, делаем ссылку неактивной -->
            <div class="bottom-navigation__item bottom-navigation__next bottom-navigation__item_disabled">
                <div class="bottom-navigation__label text-small"><?php _e('Наступний урок', 'cryptoschool'); ?></div>
                <div class="bottom-navigation__arrow">
                    <span class="icon-nav-arrow-right"></span>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
