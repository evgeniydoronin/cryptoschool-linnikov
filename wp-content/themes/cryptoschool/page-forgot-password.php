<?php
/**
 * Template Name: Восстановление пароля (Forgot Password)
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
    <div class="palette palette_blurred auth__block forgot-password-block">
      <div class="auth__form">
        <?php
        // Вывод сообщений об ошибках и успехе
        if (isset($_GET['errors'])) {
            $errors = explode(',', $_GET['errors']);
            foreach ($errors as $error) {
                switch ($error) {
                    case 'empty_username':
                        echo '<div class="auth-message auth-message_error">Введите имя пользователя или email.</div>';
                        break;
                    case 'invalid_email':
                        echo '<div class="auth-message auth-message_error">Введите корректный email.</div>';
                        break;
                    case 'invalidcombo':
                        echo '<div class="auth-message auth-message_error">Пользователь с таким именем или email не найден.</div>';
                        break;
                    default:
                        echo '<div class="auth-message auth-message_error">Произошла ошибка. Пожалуйста, попробуйте еще раз.</div>';
                        break;
                }
            }
        } elseif (isset($_GET['checkemail']) && $_GET['checkemail'] == 'confirm') {
            echo '<div class="auth-message auth-message_success">Инструкции по восстановлению пароля отправлены на вашу электронную почту.</div>';
        }
        ?>
        
        <div class="auth__header auth__header_margin-big">
            <a href="<?php echo esc_url(site_url('/sign-in/')); ?>" class="auth__nav">
                <span class="icon-nav-arrow-left auth__nav-arrow"></span>
                <div class="auth__nav-label text-small">Повернутися до входу</div>
            </a>
            <h4 class="h4 auth__title">Забули пароль?</h4>
            <div class="text-small auth__text">
                Не хвилюйтеся, це трапляється з усіма нами. Введіть свій email нижче, щоб відновити пароль
            </div>
        </div>
        
        <form id="lostpasswordform" method="post" action="<?php echo esc_url(wp_lostpassword_url()); ?>">
            <div class="auth__fields auth__fields_margin-big">
                <div class="auth-field">
                    <div class="auth-field__control">
                        <label for="user_login" class="auth-field__label">Email або Нікнейм</label>
                        <input type="text" name="user_login" id="user_login" class="auth-field__input text-small" required>
                    </div>
                </div>
            </div>
            
            <div class="auth__footer">
                <button type="submit" class="auth__submit text">Надіслати</button>
            </div>
        </form>
        
        <!-- <div class="auth__separator auth__separator_margin-big">
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

      </div>
    </div>
  </div>
</main>

<?php get_footer(); ?>
