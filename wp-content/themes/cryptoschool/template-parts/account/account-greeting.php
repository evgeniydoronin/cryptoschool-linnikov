<?php
/**
 * Шаблон приветствия пользователя в личном кабинете
 *
 * @package CryptoSchool
 */

// Подключение необходимых классов для работы с пакетами и доступами
require_once ABSPATH . 'wp-content/plugins/cryptoschool/includes/models/class-cryptoschool-model.php';
require_once ABSPATH . 'wp-content/plugins/cryptoschool/includes/models/class-cryptoschool-model-package.php';
require_once ABSPATH . 'wp-content/plugins/cryptoschool/includes/models/class-cryptoschool-model-user-access.php';
require_once ABSPATH . 'wp-content/plugins/cryptoschool/includes/repositories/class-cryptoschool-repository.php';
require_once ABSPATH . 'wp-content/plugins/cryptoschool/includes/repositories/class-cryptoschool-repository-package.php';
require_once ABSPATH . 'wp-content/plugins/cryptoschool/includes/repositories/class-cryptoschool-repository-user-access.php';
require_once ABSPATH . 'wp-content/plugins/cryptoschool/includes/services/class-cryptoschool-service.php';
require_once ABSPATH . 'wp-content/plugins/cryptoschool/includes/services/class-cryptoschool-service-package.php';
require_once ABSPATH . 'wp-content/plugins/cryptoschool/includes/services/class-cryptoschool-service-user-access.php';
require_once ABSPATH . 'wp-content/plugins/cryptoschool/includes/class-cryptoschool-loader.php';

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

// Получаем информацию о доступах пользователя через сервисы
$user_accesses = [];
try {
    // Инициализация сервисов
    $loader = new CryptoSchool_Loader();
    $user_access_service = new CryptoSchool_Service_UserAccess($loader);
    $package_service = new CryptoSchool_Service_Package($loader);
    
    // Получение активных доступов пользователя
    $active_accesses = $user_access_service->get_user_accesses($current_user->ID, ['status' => 'active']);
    
    // Преобразуем в формат, совместимый с предыдущим кодом
    foreach ($active_accesses as $access) {
        $package = $package_service->get_by_id($access->package_id);
        $access_obj = (object) [
            'access_end' => $access->access_end,
            'package_title' => $package ? $package->title : 'Неизвестный пакет'
        ];
        $user_accesses[] = $access_obj;
    }
    
    // Сортируем по дате окончания (DESC)
    usort($user_accesses, function($a, $b) {
        if ($a->access_end === null && $b->access_end === null) return 0;
        if ($a->access_end === null) return -1;
        if ($b->access_end === null) return 1;
        return strtotime($b->access_end) - strtotime($a->access_end);
    });
    
} catch (Exception $e) {
    error_log('Ошибка при получении доступов в account-greeting.php: ' . $e->getMessage());
    $user_accesses = [];
}

// Определяем дату окончания подписки и текст для отображения
$expiry_text = '';

if (!empty($user_accesses)) {
    // Проверяем первый доступ (самый важный после сортировки)
    $first_access = $user_accesses[0];
    
    if ($first_access->access_end === null) {
        // Пользователь имеет пожизненный доступ
        $expiry_text = 'Довічний доступ';
    } else {
        // Проверяем, есть ли хотя бы один пожизненный доступ среди всех
        $has_lifetime_access = false;
        foreach ($user_accesses as $access) {
            if ($access->access_end === null) {
                $has_lifetime_access = true;
                break;
            }
        }
        
        if ($has_lifetime_access) {
            $expiry_text = 'Довічний доступ';
        } else {
            // Используем дату окончания первого доступа (самую позднюю после сортировки)
            $expiry_timestamp = strtotime($first_access->access_end);
            $expiry_day = date('j', $expiry_timestamp);
            $expiry_month = date('F', $expiry_timestamp);
            $expiry_year = date('Y', $expiry_timestamp);
            $expiry_month_ua = isset($months[$expiry_month]) ? $months[$expiry_month] : $expiry_month;
            $expiry_text = 'Передплата діє до ' . $expiry_day . ' ' . $expiry_month_ua . ' ' . $expiry_year . ' р.';
        }
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
