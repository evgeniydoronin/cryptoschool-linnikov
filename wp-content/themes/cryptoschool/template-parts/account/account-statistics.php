<?php
/**
 * Шаблон блока статистики в личном кабинете
 *
 * @package CryptoSchool
 */

// Заглушки для данных статистики
$total_points = 341;
$days_on_project = 304;
$rank = 6;
?>

<div class="account-block palette palette_blurred account-statistics">
    <div class="account-statistics-item">
        <div class="account-statistics-item__icon">
            <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/shared/star.svg" alt="">
        </div>
        <h5 class="account-statistics-item__value h5 color-primary"><?php echo esc_html($total_points); ?></h5>
        <div class="account-statistics-item__description text-small">Отримано поінтів</div>
    </div>

    <div class="account-statistics-item">
        <div class="account-statistics-item__icon">
            <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/dashboard/statistics-calendar.svg" alt="">
        </div>
        <h5 class="account-statistics-item__value h5 color-primary"><?php echo esc_html($days_on_project); ?></h5>
        <div class="account-statistics-item__description text-small">Днів на проєкті</div>
    </div>

    <div class="account-statistics-item">
        <div class="account-statistics-item__icon">
            <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/dashboard/statistics-profit.svg" alt="">
        </div>
        <h5 class="account-statistics-item__value h5 color-primary"><?php echo esc_html($rank); ?></h5>
        <div class="account-statistics-item__description text-small">Місце в рейтингу</div>
    </div>
</div>
