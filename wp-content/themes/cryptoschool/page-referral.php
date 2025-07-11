<?php

/**
 * Template Name: Реферальна програма
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
    <div class="page-background">
        <div class="ratio-wrap page-background__wrap"> <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/decor-light.svg" alt="Page decor"
                class="ratio-wrap__item page-background__img_light"> <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/decor-dark.svg" alt="Page decor"
                class="ratio-wrap__item page-background__img_dark"> </div>
    </div>
    <div class="container container_wide">
        <div class="account-layout">
            <!-- Боковая навигация -->
            <?php get_template_part('template-parts/account/sidebar-navigation'); ?>

            <div class="account-layout-column account-layout-center">
                <div class="account-layout-column-slice account-layout-center__top">
                    <!-- Заголовок страницы -->
                    <?php 
                    get_template_part('template-parts/account/account-greeting', null, array(
                        'title' => 'Реферальна програма',
                        'show_user_name' => false
                    )); 
                    ?>
                </div>

                <div class="account-layout-column-slice account-layout-center-bottom">
                    <!-- Индикатор загрузки -->
                    <div id="referral-loading" class="account-block palette palette_blurred" style="text-align: center; padding: 40px;">
                        <p>Загрузка данных реферальной программы...</p>
                    </div>

                    <!-- Основной контент (скрыт до загрузки данных) -->
                    <div data-tabs class="tabs" style="display: none;">
                        <div class="tabs__header">
                            <div data-tabs-slider class="tabs__slider swiper">
                                <!-- Контейнер для динамических табов -->
                                <div class="tabs__links swiper-wrapper">
                                    <!-- Табы будут добавлены через JavaScript -->
                                </div>
                            </div>
                            <!-- Кнопка "Новий код" будет добавлена через JavaScript -->
                        </div>
                        <!-- Контейнер для динамических страниц табов -->
                        <div class="tabs__pages">
                            <!-- Страницы табов будут добавлены через JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    function copyToClipboard(elementId) {
        var copyText = document.getElementById(elementId);
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        document.execCommand("copy");

        // Показываем уведомление об успешном копировании
        alert("Посилання скопійовано: " + copyText.value);
    }
</script>

<?php get_footer(); ?>
