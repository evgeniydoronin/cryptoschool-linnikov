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

get_header();

// Заглушки для данных о тарифе
$subscription_data = array(
    'expiry_date' => '8 січня 2025 р.',
    'registration_date' => '24 грудня 2024 г.',
    'subscription_date' => '24 грудня 2024 г.',
    'tariff_name' => 'Усе включено',
    'creoin_points' => 105
);
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
