<?php
/**
 * Шаблон приветствия пользователя в личном кабинете
 *
 * @package CryptoSchool
 */

// Получаем параметры, переданные через get_template_part
$args = wp_parse_args($args ?? array(), array(
    'title' => null,
    'show_user_name' => true
));

// Получаем данные пользователя
$current_user = wp_get_current_user();
$user_name = $current_user->display_name;

// Определяем заголовок
if ($args['title']) {
    $greeting_title = $args['title'];
} elseif ($args['show_user_name']) {
    $greeting_title = 'Привіт, ' . $user_name;
} else {
    $greeting_title = 'Особистий кабінет';
}

// Получаем текущую дату с днем недели
$timestamp = current_time('timestamp');
$weekday = date('l', $timestamp);
$day = date('d', $timestamp);
$month = date('F', $timestamp);

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

// Преобразуем месяц на украинский
$months = array(
    'January' => 'Січня',
    'February' => 'Лютого',
    'March' => 'Березня',
    'April' => 'Квітня',
    'May' => 'Травня',
    'June' => 'Червня',
    'July' => 'Липня',
    'August' => 'Серпня',
    'September' => 'Вересня',
    'October' => 'Жовтня',
    'November' => 'Листопада',
    'December' => 'Грудня'
);

$weekday_ua = isset($weekdays[$weekday]) ? $weekdays[$weekday] : $weekday;
$month_ua = isset($months[$month]) ? $months[$month] : $month;
$current_date = $day . ' ' . $month_ua;

// Получаем информацию о доступах пользователя
global $wpdb;
$access_table = $wpdb->prefix . 'cryptoschool_user_access';
$package_table = $wpdb->prefix . 'cryptoschool_packages';

// Запрос для получения активных доступов пользователя
$query = $wpdb->prepare(
    "SELECT ua.*, p.title as package_title 
    FROM {$access_table} ua
    JOIN {$package_table} p ON ua.package_id = p.id
    WHERE ua.user_id = %d AND ua.status = 'active'
    ORDER BY ua.access_end DESC",
    $current_user->ID
);

$user_accesses = $wpdb->get_results($query);

// Определяем дату окончания подписки и текст для отображения
$expiry_date = '';
$expiry_text = '';
$has_lifetime_access = false;

if (!empty($user_accesses)) {
    foreach ($user_accesses as $access) {
        if ($access->access_end === null) {
            // Пользователь имеет пожизненный доступ
            $has_lifetime_access = true;
            break;
        } elseif (empty($expiry_date) || strtotime($access->access_end) > strtotime($expiry_date)) {
            // Выбираем самую позднюю дату окончания
            $expiry_date = $access->access_end;
        }
    }
    
    if ($has_lifetime_access) {
        $expiry_text = 'Довічний доступ';
    } else {
        // Форматируем дату в нужном формате (день месяц год)
        $expiry_timestamp = strtotime($expiry_date);
        $expiry_day = date('j', $expiry_timestamp);
        $expiry_month = date('F', $expiry_timestamp);
        $expiry_year = date('Y', $expiry_timestamp);
        $expiry_month_ua = isset($months[$expiry_month]) ? $months[$expiry_month] : $expiry_month;
        $expiry_text = 'Передплата діє до ' . $expiry_day . ' ' . $expiry_month_ua . ' ' . $expiry_year . ' р.';
    }
} else {
    // Если у пользователя нет активных доступов
    $expiry_text = 'Немає активної підписки';
}
?>

<div class="account-greeting">
    <div class="account-greeting__left">
        <div class="account-greeting__date text"><?php echo esc_html($weekday_ua); ?>, <?php echo esc_html($current_date); ?></div>
        <h5 class="account-greeting__name h5 color-primary"><?php echo esc_html($greeting_title); ?></h5>
    </div>
    
    <div class="account-greeting-payment palette palette_blurred">
        <div class="account-greeting-payment__title text-small color-primary">
            <?php echo esc_html($expiry_text); ?>
        </div>
        <a href="<?php echo esc_url(site_url('/rate/')); ?>" class="account-greeting-payment__button">
            <span class="text-small">Продлити</span>
        </a>
    </div>
</div>
