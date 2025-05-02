<?php
/**
 * Template Name: Dashboard
 *
 * @package CryptoSchool
 */

// Если пользователь не авторизован, перенаправляем на страницу входа
if (!is_user_logged_in()) {
    wp_redirect(site_url('/sign-in/'));
    exit;
}

get_header();
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
                    <!-- Блок статистики -->
                    <?php get_template_part('template-parts/account/account-statistics'); ?>
                    
                    <!-- Блок последних заданий -->
                    <?php get_template_part('template-parts/account/account-last-tasks'); ?>
                </div>
            </div>
            
            <div class="account-layout-column account-layout-sidebar">
                <div class="account-layout-column-slice account-layout-sidebar__bottom">
                    <!-- Блок рейтинга -->
                    <?php get_template_part('template-parts/account/account-rating'); ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php get_footer(); ?>
