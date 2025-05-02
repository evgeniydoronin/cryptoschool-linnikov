<?php
/**
 * Template Name: Налаштування
 *
 * @package CryptoSchool
 */

// Если пользователь не авторизован, перенаправляем на страницу входа
if (!is_user_logged_in()) {
    wp_redirect(site_url('/sign-in/'));
    exit;
}

get_header();

// Получаем данные пользователя
$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$user_name = $current_user->display_name;
$user_email = $current_user->user_email;
$user_telegram = get_user_meta($user_id, 'telegram', true);
$user_discord = get_user_meta($user_id, 'discord', true);
$user_crypto_wallet = get_user_meta($user_id, 'crypto_wallet', true) ?: 'XKFDFJ4325LDFEXKFDFJ4325LDFE';
?>

<main>
    <div class="page-background">
        <div class="ratio-wrap page-background__wrap">
            <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/decor-light.svg" alt="Page decor" class="ratio-wrap__item page-background__img_light">
            <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/decor-dark.svg" alt="Page decor" class="ratio-wrap__item page-background__img_dark">
        </div>
    </div>
    
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
                    <div class="account-block palette palette_blurred settings__wrapper">
                        <div class="settings__data">
                            <h6 class="h6 color-primary settings__data-title settings-title">Налаштування</h6>
                            <hr class="settings__data-hr" />
                            <form class="settings__photo settings__photo-mobile" method="post" action="#" enctype="multipart/form-data">
                                <div class="settings__photo-left">
                                    <div class="text settings__photo-subtitle">Фото</div>
                                    <div class="account-menu-info-avatar settings__avatar">
                                        <div class="account-menu-info-avatar__circle">
                                            <?php 
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
                                </div>
                                <div class="settings__photo-right">
                                    <div
                                        class="settings__photo-box"
                                        onclick="document.getElementById('fileInput').click()"
                                    >
                                        <span class="settings__icon-download color-primary icon-download"></span>
                                        <p class="text-small settings__photo-description">
                                            Завантажити фотографію<br />для профілю
                                        </p>
                                        <input
                                            class="settings__photo-input"
                                            type="file"
                                            id="fileInput"
                                            name="profile_photo"
                                            accept="image/*"
                                            onchange="previewImage(event)"
                                        />
                                        <img id="preview" />
                                    </div>
                                    <div class="settings__photo-content">
                                        <div class="settings__photo-texts">
                                            <p class="text-small settings__photo-text">
                                                Розмір обкладинки повинен бути не менший 200х200 px і не
                                                більший ніж 5 Мб. Формат обкладинки JPG, PNG
                                            </p>
                                            <p class="text-small settings__photo-text">
                                                Після завантаження натисніть "Зберегти"
                                            </p>
                                        </div>
                                        <button type="submit" name="update_photo" class="button button_filled button_rounded">
                                            <span class="button__text">Зберегти</span>
                                        </button>
                                    </div>
                                </div>
                            </form>
                            <form class="settings__main" method="post" action="#">
                                <h6 class="h6 color-primary settings__main-title settings-title">Основні</h6>
                                <div class="settings__main-label--wrapper">
                                    <label class="settings__main-label">
                                        <p class="text settings__main-text">Ник</p>
                                        <input
                                            class="settings__main-input" 
                                            type="text"
                                            name="user_name"
                                            placeholder="Ник"
                                            value="<?php echo esc_attr($user_name); ?>"
                                            readonly
                                        />
                                    </label>
                                    <label class="settings__main-label">
                                        <p class="text settings__main-text">E-mail</p>
                                        <input
                                            class="settings__main-input"
                                            type="email"
                                            name="user_email"
                                            placeholder="E-mail"
                                            value="<?php echo esc_attr($user_email); ?>"
                                        />
                                    </label>
                                    <label class="settings__main-label">
                                        <p class="text settings__main-text">Телеграм</p>
                                        <input
                                            class="settings__main-input"
                                            type="text"
                                            name="user_telegram"
                                            placeholder="@username"
                                            value="<?php echo esc_attr($user_telegram); ?>"
                                        />
                                    </label>
                                    <label class="settings__main-label">
                                        <p class="text settings__main-text">Дискорд</p>
                                        <input
                                            class="settings__main-input"
                                            type="text"
                                            name="user_discord"
                                            placeholder="@username"
                                            value="<?php echo esc_attr($user_discord); ?>"
                                        />
                                    </label>
                                </div>
                                <label class="settings__main-label settings__main-label--crypto">
                                    <p class="text settings__main-text">Криптокошелек</p>
                                    <input
                                        class="settings__main-input"
                                        type="text"
                                        name="user_crypto_wallet"
                                        value="<?php echo esc_attr($user_crypto_wallet); ?>"
                                        readonly
                                    />
                                </label>
                                <button type="submit" name="update_profile" class="button button_filled button_rounded">
                                    <span class="button__text">Зберегти</span>
                                </button>
                            </form>
                            <hr class="settings__data-hr" />
                            <form class="settings__safety" method="post" action="#">
                                <h6 class="h6 color-primary settings__safety-title settings-title">Безпека</h6>
                                <div class="settings__safety-input--wrapper">
                                    <input
                                        class="settings__safety-input"
                                        type="password"
                                        name="old_password"
                                        placeholder="Старий пароль"
                                    />
                                    <input
                                        class="settings__safety-input"
                                        type="password"
                                        name="new_password"
                                        placeholder="Новий пароль"
                                    />
                                </div>
                                <input
                                    class="settings__safety-input"
                                    type="password"
                                    name="confirm_password"
                                    placeholder="Повторіть новий пароль"
                                />
                                <button type="submit" name="update_password" class="button button_filled button_rounded">
                                    <span class="button__text">Зберегти</span>
                                </button>
                            </form>
                        </div>
                        <form class="palette palette_blurred palette_active settings__photo settings__photo-desktop" method="post" action="#" enctype="multipart/form-data">
                            <div class="settings__photo-left">
                                <div class="text settings__photo-subtitle">Фото</div>
                                <div class="account-menu-info-avatar">
                                    <div class="account-menu-info-avatar__circle">
                                        <?php 
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
                            </div>
                            <div class="settings__photo-right">
                                <div
                                    class="settings__photo-box"
                                    onclick="document.getElementById('fileInput2').click()"
                                >
                                    <span class="settings__icon-download color-primary icon-download"></span>
                                    <p class="text-small settings__photo-description">
                                        Завантажити фотографію<br />для профілю
                                    </p>
                                    <input
                                        class="settings__photo-input"
                                        type="file"
                                        id="fileInput2"
                                        name="profile_photo"
                                        accept="image/*"
                                        onchange="previewImage2(event)"
                                    />
                                    <img id="preview2" />
                                </div>
                                <p class="text-small settings__photo-text">
                                    Розмір обкладинки повинен бути не менший 200х200 px і не більший
                                    ніж 5 Мб. Формат обкладинки JPG, PNG
                                </p>
                                <p class="text-small settings__photo-text">
                                    Після завантаження натисніть "Зберегти"
                                </p>
                                <button type="submit" name="update_photo" class="button button_filled button_rounded">
                                    <span class="button__text">Зберегти</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.settings__photo-box {
    position: relative;
    width: 100%;
    height: 150px;
    overflow: hidden;
}

.settings__photo-input {
    display: none;
}

#preview, #preview2 {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: none;
    z-index: 1;
}

.settings__icon-download, .settings__photo-description {
    position: relative;
    z-index: 2;
}
</style>

<script>
function previewImage(event) {
    var preview = document.getElementById('preview');
    preview.src = URL.createObjectURL(event.target.files[0]);
    preview.style.display = 'block';
    
    // Скрываем иконку и текст при отображении изображения
    var box = preview.closest('.settings__photo-box');
    var icon = box.querySelector('.settings__icon-download');
    var description = box.querySelector('.settings__photo-description');
    
    if (icon) icon.style.display = 'none';
    if (description) description.style.display = 'none';
}

function previewImage2(event) {
    var preview = document.getElementById('preview2');
    preview.src = URL.createObjectURL(event.target.files[0]);
    preview.style.display = 'block';
    
    // Скрываем иконку и текст при отображении изображения
    var box = preview.closest('.settings__photo-box');
    var icon = box.querySelector('.settings__icon-download');
    var description = box.querySelector('.settings__photo-description');
    
    if (icon) icon.style.display = 'none';
    if (description) description.style.display = 'none';
}
</script>

<?php get_footer(); ?>
