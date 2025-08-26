<?php
/**
 * Шаблон боковой навигации личного кабинета
 *
 * @package CryptoSchool
 */

// Получаем текущий URL для определения активной страницы
$current_url = $_SERVER['REQUEST_URI'];
?>

<div class="account-layout-column account-layout-sidebar">
    <div class="account-layout-column-slice account-layout-sidebar__top">
        <div class="account-menu">
            <div class="account-menu-info palette palette_blurred">
                <div class="account-menu-info-avatar">
                    <div class="account-menu-info-avatar__circle">
                        <?php 
                        // Получаем ID текущего пользователя
                        $user_id = get_current_user_id();
                        
                        // Проверяем наличие пользовательского изображения профиля
                        $custom_avatar = get_user_meta($user_id, 'cryptoschool_profile_photo', true);
                        
                        if ($custom_avatar) {
                            echo '<img src="' . esc_url($custom_avatar) . '" alt="User Avatar" class="account-menu-info-avatar__img">';
                        } else {
                            // Если пользовательского изображения нет, используем иконку профиля
                            echo '<span class="icon-profile"></span>';
                        }
                        ?>
                    </div>
                </div>
                <div class="account-menu-info__content">
                    <div class="account-menu-info-lessons">
                        <div class="account-menu-info-lessons-icon">
                            <span class="icon-videos"></span>
                        </div>
                        <div class="account-menu-info-lessons__amount text-small">
                            <?php 
                            // Здесь будет количество уроков
                            echo '12 уроків';
                            ?>
                        </div>
                    </div>
                    <div class="account-menu-info__name text">
                        <?php 
                        // Получаем имя пользователя
                        $current_user = wp_get_current_user();
                        echo esc_html($current_user->display_name);
                        ?>
                    </div>
                </div>
            </div>

            <a href="<?php echo esc_url(cryptoschool_site_url('/dashboard/')); ?>" class="account-menu-item palette palette_blurred palette_hoverable <?php echo (strpos($current_url, '/dashboard/') !== false) ? 'palette_active account-menu-item_active' : ''; ?>">
                <div class="account-menu-item__icon">
                    <span class="icon-dashboard color-primary"></span>
                </div>
                <div class="account-menu-item__name text color-primary">Dashboard</div>
            </a>

            <a href="<?php echo esc_url(cryptoschool_site_url('/courses/')); ?>" class="account-menu-item palette palette_blurred palette_hoverable <?php echo (strpos($current_url, '/courses/') !== false) ? 'palette_active account-menu-item_active' : ''; ?>">
                <div class="account-menu-item__icon">
                    <span class="icon-video-play color-primary"></span>
                </div>
                <div class="account-menu-item__name text color-primary">Навчання</div>
            </a>

            <a href="<?php echo esc_url(cryptoschool_site_url('/rate/')); ?>" class="account-menu-item palette palette_blurred palette_hoverable <?php echo (strpos($current_url, '/rate/') !== false) ? 'palette_active account-menu-item_active' : ''; ?>">
                <div class="account-menu-item__icon">
                    <span class="icon-rate color-primary"></span>
                </div>
                <div class="account-menu-item__name text color-primary">Мій тариф</div>
            </a>

            <a href="<?php echo esc_url(cryptoschool_site_url('/referral/')); ?>" class="account-menu-item palette palette_blurred palette_hoverable <?php echo (strpos($current_url, '/referral/') !== false) ? 'palette_active account-menu-item_active' : ''; ?>">
                <div class="account-menu-item__icon">
                    <span class="icon-referral color-primary"></span>
                </div>
                <div class="account-menu-item__name text color-primary">Реферальна програма</div>
            </a>

            <a href="<?php echo esc_url(cryptoschool_site_url('/settings/')); ?>" class="account-menu-item palette palette_blurred palette_hoverable <?php echo (strpos($current_url, '/settings/') !== false) ? 'palette_active account-menu-item_active' : ''; ?>">
                <div class="account-menu-item__icon">
                    <span class="icon-settings color-primary"></span>
                </div>
                <div class="account-menu-item__name text color-primary">Налаштування</div>
            </a>

            <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="account-menu-item palette palette_blurred palette_hoverable">
                <div class="account-menu-item__icon">
                    <span class="icon-exit color-primary"></span>
                </div>
                <div class="account-menu-item__name text color-primary">Вийти</div>
            </a>
        </div>
    </div>
</div>
