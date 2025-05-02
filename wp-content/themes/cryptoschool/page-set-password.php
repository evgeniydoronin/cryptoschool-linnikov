<?php
/**
 * Template Name: Установка нового пароля (Set Password)
 *
 * @package CryptoSchool
 */

// Если пользователь уже авторизован, перенаправляем на главную страницу
if (is_user_logged_in()) {
    wp_redirect(home_url());
    exit;
}

// Проверяем наличие ключа и логина в URL
$rp_key = isset($_GET['key']) ? $_GET['key'] : '';
$rp_login = isset($_GET['login']) ? $_GET['login'] : '';

// Если ключ или логин отсутствуют, перенаправляем на страницу восстановления пароля
if (empty($rp_key) || empty($rp_login)) {
    wp_redirect(site_url('/forgot-password/'));
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
    <div class="palette palette_blurred auth__block set-password-block">
      <div class="auth__form">
        <?php
        // Вывод сообщений об ошибках
        if (isset($_GET['error'])) {
            $error = $_GET['error'];
            switch ($error) {
                case 'invalidkey':
                    echo '<div class="auth-message auth-message_error">Недействительный ключ для сброса пароля.</div>';
                    break;
                case 'expiredkey':
                    echo '<div class="auth-message auth-message_error">Срок действия ключа для сброса пароля истек.</div>';
                    break;
                default:
                    echo '<div class="auth-message auth-message_error">Произошла ошибка. Пожалуйста, попробуйте еще раз.</div>';
                    break;
            }
        }
        ?>
        
        <div class="auth__header auth__header_margin-big">
            <h4 class="h4 auth__title">Встановіть пароль</h4>
            <div class="text-small auth__text">
                Ваш попередній пароль було скинуто. Будь ласка, встановіть новий пароль для свого облікового запису.
            </div>
        </div>
        
        <form id="resetpassform" method="post" action="<?php echo esc_url(site_url('wp-login.php?action=resetpass')); ?>">
            <input type="hidden" name="rp_key" value="<?php echo esc_attr($rp_key); ?>">
            <input type="hidden" name="rp_login" value="<?php echo esc_attr($rp_login); ?>">
            
            <div class="auth__fields auth__fields_margin-big">
                <div class="auth-field" data-auth-field-protected>
                    <div class="auth-field__control">
                        <label for="pass1" class="auth-field__label">Новий пароль</label>
                        <input type="password" name="pass1" id="pass1" class="auth-field__input text-small" required>
                        <div class="auth-field__icon">
                            <span class="icon-eye-off" data-auth-field-protected-icon></span>
                        </div>
                    </div>
                </div>
                
                <div class="auth-field" data-auth-field-protected>
                    <div class="auth-field__control">
                        <label for="pass2" class="auth-field__label">Підтвердити пароль</label>
                        <input type="password" name="pass2" id="pass2" class="auth-field__input text-small" required>
                        <div class="auth-field__icon">
                            <span class="icon-eye-off" data-auth-field-protected-icon></span>
                        </div>
                    </div>
                </div>
                
                <div class="auth-field">
                    <div class="text-small auth__text">
                        Підказка: Пароль повинен містити не менше 8 символів. Для підвищення безпеки використовуйте великі та малі літери, цифри та символи, такі як ! " ? $ % ^ &.
                    </div>
                </div>
            </div>
            
            <div class="auth__footer">
                <button type="submit" class="auth__submit text">Зберегти пароль</button>
            </div>
        </form>
      </div>
    </div>
  </div>
</main>

<?php get_footer(); ?>
