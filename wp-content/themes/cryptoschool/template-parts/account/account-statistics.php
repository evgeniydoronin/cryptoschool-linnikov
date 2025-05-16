<?php
/**
 * Шаблон блока статистики в личном кабинете
 *
 * @package CryptoSchool
 */

// Получаем данные пользователя
$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Получаем дату регистрации пользователя
$user_data = get_userdata($user_id);
$registration_date = $user_data->user_registered;

// Вычисляем количество дней с момента регистрации
$registration_timestamp = strtotime($registration_date);
$current_timestamp = current_time('timestamp');
$days_on_project = floor(($current_timestamp - $registration_timestamp) / (60 * 60 * 24));

// Если пользователь зарегистрировался сегодня, устанавливаем значение 1
if ($days_on_project < 1) {
    $days_on_project = 1;
}

// Заглушки для остальных данных статистики
$total_points = 341;
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
