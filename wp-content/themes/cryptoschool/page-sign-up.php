<?php
/**
 * Template Name: Регистрация (Sign Up)
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
    <div class="palette palette_blurred auth__block sign-up-block">
      <div class="auth__form">
        <?php
        // Вывод сообщений об ошибках
        if (isset($_GET['register']) && $_GET['register'] === 'failed') {
            echo '<div class="auth-message auth-message_error">Ошибка регистрации. Пожалуйста, проверьте введенные данные.</div>';
        } elseif (isset($_GET['register']) && $_GET['register'] === 'email_exists') {
            echo '<div class="auth-message auth-message_error">Пользователь с таким email уже существует.</div>';
        } elseif (isset($_GET['register']) && $_GET['register'] === 'username_exists') {
            echo '<div class="auth-message auth-message_error">Пользователь с таким именем уже существует.</div>';
        }
        
        // Проверяем, включена ли регистрация пользователей
        if (get_option('users_can_register')) :
        ?>
            <form id="register-form" method="post" action="<?php echo esc_url(site_url('wp-login.php?action=register', 'login_post')); ?>" novalidate="novalidate">
                <?php wp_nonce_field('cryptoschool_register_action', 'cryptoschool_register_nonce'); ?>
                <div class="auth__header">
                    <h4 class="h4 auth__title">Зареєструватися</h4>
                    <div class="text-small auth__text">
                        Давайте налаштуємо вас, щоб ви могли отримати доступ до свого особистого кабінету.
                    </div>
                </div>
                
                <div class="auth__fields">
                    <div class="auth-field">
                        <div class="auth-field__control">
                            <label for="user_login" class="auth-field__label">Нікнейм</label>
                            <input type="text" name="user_login" id="user_login" class="auth-field__input text-small" required>
                        </div>
                    </div>
                    
                    <div class="auth__fields-row">
                        <div class="auth-field">
                            <div class="auth-field__control">
                                <label for="user_email" class="auth-field__label">Email</label>
                                <input type="email" name="user_email" id="user_email" class="auth-field__input text-small" required>
                            </div>
                        </div>
                        <div class="auth-field">
                            <div class="auth-field__control">
                                <label for="user_phone" class="auth-field__label">Номер телефону</label>
                                <input type="tel" name="user_phone" id="user_phone" class="auth-field__input text-small">
                            </div>
                        </div>
                    </div>
                    
                    <div class="auth-field" data-auth-field-protected>
                        <div class="auth-field__control">
                            <label for="user_pass" class="auth-field__label">Пароль</label>
                            <input type="password" name="user_pass" id="user_pass" class="auth-field__input text-small" required>
                            <div class="auth-field__icon">
                                <span class="icon-eye-off" data-auth-field-protected-icon></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="auth-field" data-auth-field-protected>
                        <div class="auth-field__control">
                            <label for="user_pass2" class="auth-field__label">Підтвердити пароль</label>
                            <input type="password" name="user_pass2" id="user_pass2" class="auth-field__input text-small" required>
                            <div class="auth-field__icon">
                                <span class="icon-eye-off" data-auth-field-protected-icon></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="auth__additional">
                    <div class="auth__checkbox">
                        <span class="checkbox">
                            <input id="agree" type="checkbox" class="checkbox__input" name="agree" required>
                            <label for="agree" class="checkbox__body">
                                <span class="icon-checkbox-arrow checkbox__icon"></span>
                            </label>
                        </span>
                        <label for="agree" class="auth_base text-small">
                            Я погоджуюся з усіма <a href="#" class="auth_highlight">Умовами</a> та <a href="#" class="auth_highlight">Політикою конфіденційності</a>
                        </label>
                    </div>
                </div>
                
                <div class="auth__footer">
                    <button type="submit" class="auth__submit text">Створити обліковий запис</button>
                    <a href="<?php echo esc_url(site_url('/sign-in/')); ?>" class="auth__other-way auth_base text-small">
                        Вже маєте обліковий запис? <span class="auth_highlight">Увійдіть</span>
                    </a>
                </div>
                
                <!-- Скрытое поле для перенаправления после регистрации -->
                <input type="hidden" name="redirect_to" value="<?php echo esc_url(home_url('/?registration=success')); ?>">
                
                <!-- <div class="auth__separator">
                    <div class="auth__separator-line"></div>
                    <span class="text-small">Або увійдіть за допомогою</span>
                    <div class="auth__separator-line"></div>
                </div>
                
                <div class="auth__helpers">
                    <a href="#" class="auth__helper" id="facebook-register">
                        <img src="<?php // echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/auth/facebook.svg">
                    </a>
                    <a href="#" class="auth__helper" id="google-register">
                        <img src="<?php // echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/auth/google.svg">
                    </a>
                    <a href="#" class="auth__helper" id="apple-register">
                        <span class="icon-apple"></span>
                    </a>
                </div> -->
            </form>
        <?php else : ?>
            <div class="auth-message auth-message_error">Регистрация новых пользователей отключена администратором.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>

<?php get_footer(); ?>
