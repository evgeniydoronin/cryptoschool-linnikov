<?php
/**
 * Шаблон приветствия пользователя в личном кабинете
 *
 * @package CryptoSchool
 */

// Получаем данные пользователя
$current_user = wp_get_current_user();
$user_name = $current_user->display_name;

// Получаем текущую дату с днем недели
setlocale(LC_TIME, 'uk_UA.UTF-8');
$weekday = date_i18n('l', current_time('timestamp'));
$current_date = date_i18n('d F', current_time('timestamp'));

// Преобразуем день недели на украинский
$weekdays = array(
    'Monday' => 'Понеділок',
    'Tuesday' => 'Вівторок',
    'Wednesday' => 'Середа',
    'Thursday' => 'Четвер',
    'Friday' => 'П\'ятниця',
    'Saturday' => 'Субота',
    'Sunday' => 'Неділя'
);
$weekday_ua = isset($weekdays[$weekday]) ? $weekdays[$weekday] : $weekday;

// Заглушка для даты окончания подписки
$expiry_date = '8 січня 2025 р.';
?>

<div class="account-greeting">
    <div class="account-greeting__left">
        <div class="account-greeting__date text"><?php echo esc_html($weekday_ua); ?>, <?php echo esc_html($current_date); ?></div>
        <h5 class="account-greeting__name h5 color-primary">Привіт, <?php echo esc_html($user_name); ?></h5>
    </div>
    
    <div class="account-greeting-payment palette palette_blurred">
        <div class="account-greeting-payment__title text-small color-primary">
            Передплата діє до <?php echo esc_html($expiry_date); ?>
        </div>
        <a href="<?php echo esc_url(site_url('/rate/')); ?>" class="account-greeting-payment__button">
            <span class="text-small">Продлити</span>
        </a>
    </div>
</div>
