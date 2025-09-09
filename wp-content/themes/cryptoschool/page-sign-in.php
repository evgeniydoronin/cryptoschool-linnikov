<?php
/**
 * Template Name: Вход в кабинет (Sign In)
 *
 * @package CryptoSchool
 */

// Если пользователь уже авторизован, перенаправляем на главную страницу
if (is_user_logged_in()) {
    wp_redirect(home_url());
    exit;
}

get_header();
?>

<main>
  <div class="page-background">
    <div class="ratio-wrap page-background__wrap">
      <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/decor-light.svg" alt="Page decor" class="ratio-wrap__item page-background__img_light">
      <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/decor-dark.svg" alt="Page decor" class="ratio-wrap__item page-background__img_dark">
    </div>
  </div>

  <div class="container container_wide auth__container">
    <div class="palette palette_blurred auth__block sign-in-block">
      <div class="auth__form">
        <?php
        // Вывод сообщений об ошибках
        if (isset($_GET['login']) && $_GET['login'] === 'failed') {
            echo '<div class="auth-message auth-message_error">Неверное имя пользователя или пароль.</div>';
        } elseif (isset($_GET['password']) && $_GET['password'] === 'reset') {
            echo '<div class="auth-message auth-message_success">Пароль успешно изменен. Теперь вы можете войти с новым паролем.</div>';
        }
        ?>
        
        <div class="auth__header">
            <h4 class="h4 auth__title">Вхід до кабінету</h4>
            <div class="text-small auth__text">
                Увійдіть, щоб отримати доступ до свого облікового запису
            </div>
        </div>
        
        <form id="login-form" method="post" action="<?php echo esc_url(site_url('wp-login.php', 'login_post')); ?>">
            <?php wp_nonce_field('cryptoschool_login_action', 'cryptoschool_login_nonce'); ?>
            <div class="auth__fields">
                <div class="auth-field">
                    <div class="auth-field__control">
                        <label for="user_login" class="auth-field__label">Email або Нікнейм</label>
                        <input type="text" name="log" id="user_login" class="auth-field__input text-small" required>
                    </div>
                </div>
                
                <div class="auth-field" data-auth-field-protected>
                    <div class="auth-field__control">
                        <label for="user_pass" class="auth-field__label">Пароль</label>
                        <input type="password" name="pwd" id="user_pass" class="auth-field__input text-small" required>
                        <div class="auth-field__icon">
                            <span class="icon-eye-off" data-auth-field-protected-icon></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="auth__additional">
                <div class="auth__checkbox">
                    <span class="checkbox">
                        <input id="rememberme" type="checkbox" class="checkbox__input" name="rememberme" value="forever">
                        <label for="rememberme" class="checkbox__body">
                            <span class="icon-checkbox-arrow checkbox__icon"></span>
                        </label>
                    </span>
                    <label for="rememberme" class="auth_base text-small">Запам'ятай мене</label>
                </div>
                <a href="<?php echo esc_url(site_url('/forgot-password/')); ?>" class="auth_highlight text-small">Забули пароль?</a>
            </div>
            
            <div class="auth__footer">
                <button type="submit" class="auth__submit text">Увійти</button>
                <a href="<?php echo esc_url(site_url('/sign-up/')); ?>" class="auth__other-way auth_base text-small">
                    Не маєте облікового запису? <span class="auth_highlight">Зареєструватися</span>
                </a>
            </div>
            
            <!-- <div class="auth__separator">
                <div class="auth__separator-line"></div>
                <span class="text-small">Або увійдіть за допомогою</span>
                <div class="auth__separator-line"></div>
            </div>
            
            <div class="auth__helpers">
                <a href="#" class="auth__helper" id="facebook-login">
                    <img src="<?php // echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/auth/facebook.svg">
                </a>
                <a href="#" class="auth__helper" id="google-login">
                    <img src="<?php // echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/auth/google.svg">
                </a>
                <a href="#" class="auth__helper" id="apple-login">
                    <span class="icon-apple"></span>
                </a>
            </div> -->
            
            <input type="hidden" name="redirect_to" value="<?php echo esc_url(home_url()); ?>">
        </form>
      </div>
    </div>
  </div>
</main>

<?php get_footer(); ?>
