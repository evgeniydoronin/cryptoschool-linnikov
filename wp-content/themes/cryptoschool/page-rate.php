<?php
/**
 * Template Name: Мій тариф
 *
 * @package CryptoSchool
 */

// Если пользователь не авторизован, перенаправляем на страницу входа
if (!is_user_logged_in()) {
    wp_redirect(site_url('/sign-in/'));
    exit;
}

// Подключение необходимых классов для работы с пакетами и доступами
require_once ABSPATH . 'wp-content/plugins/cryptoschool/includes/models/class-cryptoschool-model.php';
require_once ABSPATH . 'wp-content/plugins/cryptoschool/includes/models/class-cryptoschool-model-package.php';
require_once ABSPATH . 'wp-content/plugins/cryptoschool/includes/models/class-cryptoschool-model-user-access.php';
require_once ABSPATH . 'wp-content/plugins/cryptoschool/includes/models/class-cryptoschool-model-points-history.php';
require_once ABSPATH . 'wp-content/plugins/cryptoschool/includes/models/class-cryptoschool-model-user-streak.php';
require_once ABSPATH . 'wp-content/plugins/cryptoschool/includes/models/class-cryptoschool-model-user-lesson-progress.php';
require_once ABSPATH . 'wp-content/plugins/cryptoschool/includes/models/class-cryptoschool-model-user-leaderboard.php';
require_once ABSPATH . 'wp-content/plugins/cryptoschool/includes/repositories/class-cryptoschool-repository.php';
require_once ABSPATH . 'wp-content/plugins/cryptoschool/includes/repositories/class-cryptoschool-repository-package.php';
require_once ABSPATH . 'wp-content/plugins/cryptoschool/includes/repositories/class-cryptoschool-repository-user-access.php';
require_once ABSPATH . 'wp-content/plugins/cryptoschool/includes/repositories/class-cryptoschool-repository-points-history.php';
require_once ABSPATH . 'wp-content/plugins/cryptoschool/includes/repositories/class-cryptoschool-repository-user-streak.php';
require_once ABSPATH . 'wp-content/plugins/cryptoschool/includes/repositories/class-cryptoschool-repository-user-lesson-progress.php';
require_once ABSPATH . 'wp-content/plugins/cryptoschool/includes/repositories/class-cryptoschool-repository-user-leaderboard.php';
require_once ABSPATH . 'wp-content/plugins/cryptoschool/includes/services/class-cryptoschool-service.php';
require_once ABSPATH . 'wp-content/plugins/cryptoschool/includes/services/class-cryptoschool-service-package.php';
require_once ABSPATH . 'wp-content/plugins/cryptoschool/includes/services/class-cryptoschool-service-user-access.php';
require_once ABSPATH . 'wp-content/plugins/cryptoschool/includes/services/class-cryptoschool-service-points.php';
require_once ABSPATH . 'wp-content/plugins/cryptoschool/includes/class-cryptoschool-loader.php';

/**
 * Получение данных активного пакета пользователя
 */
function get_current_user_subscription_data() {
    $current_user_id = get_current_user_id();
    
    if (!$current_user_id) {
        return null;
    }
    
    try {
        // Инициализация сервисов
        $loader = new CryptoSchool_Loader();
        $user_access_service = new CryptoSchool_Service_UserAccess($loader);
        $package_service = new CryptoSchool_Service_Package($loader);
        
        // Получение активных доступов пользователя
        $user_accesses = $user_access_service->get_user_accesses($current_user_id, ['status' => 'active']);
        
        if (empty($user_accesses)) {
            return null;
        }
        
        // Берем первый активный доступ (если их несколько)
        $access = $user_accesses[0];
        $package = $package_service->get_by_id($access->package_id);
        
        if (!$package) {
            return null;
        }
        
        // Получение данных пользователя
        $user = get_userdata($current_user_id);
        $registration_date = $user ? date('j F Y \р.', strtotime($user->user_registered)) : '';
        
        // Форматирование дат
        $access_start = $access->access_start ? date('j F Y \г.', strtotime($access->access_start)) : '';
        $access_end = $access->access_end ? date('j F Y \р.', strtotime($access->access_end)) : 'Безлімітний доступ';
        
        // Получение количества баллов (если есть сервис)
        $points = 0;
        if (class_exists('CryptoSchool_Service_Points')) {
            try {
                $points_service = new CryptoSchool_Service_Points($loader);
                $points = $points_service->get_user_total_points($current_user_id);
            } catch (Exception $e) {
                // Игнорируем ошибки при получении баллов
            }
        }
        
        return [
            'tariff_name' => $package->title,
            'registration_date' => $registration_date,
            'subscription_date' => $access_start,
            'expiry_date' => $access_end,
            'creoin_points' => $points,
            'status' => $access->status,
            'package_type' => $package->package_type ?? '',
            'access_id' => $access->id
        ];
        
    } catch (Exception $e) {
        error_log('Ошибка при получении данных пакета пользователя: ' . $e->getMessage());
        return null;
    }
}

get_header();

// Получение реальных данных о подписке пользователя
$real_subscription_data = get_current_user_subscription_data();

// Заглушки для данных о тарифе (используются если нет реальных данных)
$subscription_data = array(
    'expiry_date' => '8 січня 2025 р.',
    'registration_date' => '24 грудня 2024 г.',
    'subscription_date' => '24 грудня 2024 г.',
    'tariff_name' => 'Усе включено',
    'creoin_points' => 105
);

// Если есть реальные данные, используем их
if ($real_subscription_data) {
    $subscription_data = $real_subscription_data;
}
?>

<main>
    <div class="container container_wide">
        <div class="account-layout">
            <!-- Боковая навигация -->
            <?php get_template_part('template-parts/account/sidebar-navigation'); ?>
            
            <div class="account-layout-column account-layout-center">
                <div class="account-layout-column-slice account-layout-center__top">
                    <!-- Приветствие пользователя -->
                    <?php get_template_part('template-parts/account/account-greeting'); ?>
                </div>

                <div class="account-layout-column-slice account-layout-center-bottom">
                    <div class="account-block palette palette_blurred account-rate">
                        <h5 class="account-block__title h6">Мій тариф</h5>

                        <hr class="account-block__horizontal-row">

                        <div class="account-rate-content">
                            <div class="account-rate-content__left">
                                <div class="account-rate__block palette palette_blurred palette_hide-mobile account-rate-palette">
                                    <div class="account-rate__property text-small">Дата закінчення передплати</div>
                                    <div class="account-rate__value text"><?php echo esc_html($subscription_data['expiry_date']); ?></div>
                                </div>

                                <div class="account-rate__column-left">
                                    <div class="account-rate__block">
                                        <div class="account-rate__property text-small">Дата реєстрації</div>
                                        <div class="account-rate__value text"><?php echo esc_html($subscription_data['registration_date']); ?></div>
                                    </div>
                    
                                    <div class="account-rate__block">
                                        <div class="account-rate__property text-small">Дата підписки на тариф</div>
                                        <div class="account-rate__value text"><?php echo esc_html($subscription_data['subscription_date']); ?></div>
                                    </div>
                                </div>
                                
                                <div class="account-rate__buttons">
                                    <a href="#" class="button button_filled button_rounded">
                                        <span class="button__text">Продлити</span>
                                    </a>
                    
                                    <a href="#" class="button button_outlined button_rounded">
                                        <span class="button__text">Скасувати підписку</span>
                                    </a>
                                </div>
                            </div>

                            <div class="account-rate-content__right">
                                <div class="account-rate__column-right">
                                    <div class="account-rate__block">
                                        <div class="account-rate__property text-small">Тариф</div>
                                        <div class="account-rate__value text"><?php echo esc_html($subscription_data['tariff_name']); ?></div>
                                    </div>
                    
                                    <div class="account-rate__block">
                                        <div class="account-rate__property text-small">Кількість Creoin</div>
                                        <div class="account-rate__value text"><?php echo esc_html($subscription_data['creoin_points']); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php get_footer(); ?>
