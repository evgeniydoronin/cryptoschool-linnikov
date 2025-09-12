<?php
/**
 * Template Name: Регистрация (Sign Up)
 *
 * @package CryptoSchool
 */

// Логируем загрузку страницы регистрации
if (class_exists('CryptoSchool_Logger')) {
    $logger = CryptoSchool_Logger::get_instance();
    $logger->info('Загрузка страницы регистрации (sign-up)', [
        'timestamp' => date('Y-m-d H:i:s'),
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'undefined',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'undefined',
        'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'undefined',
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'undefined',
        'is_user_logged_in' => is_user_logged_in(),
        'current_user_id' => get_current_user_id()
    ]);
}

// Если пользователь уже авторизован, перенаправляем на главную страницу
if (is_user_logged_in()) {
    if (class_exists('CryptoSchool_Logger')) {
        $logger->info('Пользователь уже авторизован, перенаправление на главную', [
            'user_id' => get_current_user_id(),
            'redirect_url' => home_url()
        ]);
    }
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
            $error_messages = [];
            
            if (isset($_GET['errors'])) {
                $error_codes = explode(',', $_GET['errors']);
                foreach ($error_codes as $error_code) {
                    switch ($error_code) {
                        case 'password_contains_username':
                            $error_messages[] = 'Пароль не должен содержать имя пользователя';
                            break;
                        case 'password_contains_email':
                            $error_messages[] = 'Пароль не должен содержать часть email адреса';
                            break;
                        case 'password_too_weak':
                            $error_messages[] = 'Пароль слишком слабый. Используйте минимум 3 из 4 типов символов';
                            break;
                        case 'password_too_short':
                            $error_messages[] = 'Пароль должен содержать не менее 8 символов';
                            break;
                        case 'password_mismatch':
                            $error_messages[] = 'Пароли не совпадают';
                            break;
                        case 'terms_not_accepted':
                            $error_messages[] = 'Необходимо согласиться с условиями использования';
                            break;
                        case 'invalid_email':
                            $error_messages[] = 'Некорректный email адрес';
                            break;
                        case 'username_too_short':
                            $error_messages[] = 'Имя пользователя слишком короткое';
                            break;
                        default:
                            $error_messages[] = 'Ошибка валидации данных';
                            break;
                    }
                }
            }
            
            if (!empty($error_messages)) {
                echo '<div class="auth-message auth-message_error">';
                echo '<strong>Исправьте следующие ошибки:</strong><br>';
                foreach ($error_messages as $message) {
                    echo '• ' . esc_html($message) . '<br>';
                }
                echo '</div>';
            } else {
                echo '<div class="auth-message auth-message_error">Ошибка регистрации. Пожалуйста, проверьте введенные данные.</div>';
            }
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
                    <button type="submit" class="auth__submit text" id="register-submit-btn" disabled>Створити обліковий запис</button>
                    <div id="password-hint" class="auth__password-hint text-small" style="display: none; color: #ff6b6b; margin-top: 8px;">
                        Пароль должен содержать минимум 3 из 4 типов символов: строчные буквы, заглавные буквы, цифры, специальные символы. Пароль не должен содержать имя пользователя или часть email адреса.
                    </div>
                    <a href="<?php echo esc_url(site_url('/sign-in/')); ?>" class="auth__other-way auth_base text-small">
                        Вже маєте обліковий запис? <span class="auth_highlight">Увійдіть</span>
                    </a>
                </div>
                
                <!-- Скрытое поле для перенаправления после регистрации -->
                <input type="hidden" name="redirect_to" value="<?php echo esc_url(home_url('/?registration=success')); ?>">

                <!-- JavaScript для логирования отправки формы -->
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const registerForm = document.getElementById('register-form');
                    if (registerForm) {
                        registerForm.addEventListener('submit', function(e) {
                            // Логируем отправку формы регистрации
                            console.log('Форма регистрации отправлена', {
                                timestamp: new Date().toISOString(),
                                userAgent: navigator.userAgent,
                                formData: {
                                    hasUserLogin: !!document.getElementById('user_login').value,
                                    hasUserEmail: !!document.getElementById('user_email').value,
                                    hasUserPass: !!document.getElementById('user_pass').value,
                                    hasUserPhone: !!document.getElementById('user_phone').value,
                                    agreeChecked: document.getElementById('agree').checked
                                }
                            });

                            // Отправляем данные на сервер для логирования через AJAX
                            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: new URLSearchParams({
                                    action: 'cryptoschool_log_registration_attempt',
                                    nonce: '<?php echo wp_create_nonce('cryptoschool_log_registration'); ?>',
                                    user_login: document.getElementById('user_login').value.substring(0, 50), // Ограничиваем длину
                                    user_email: document.getElementById('user_email').value,
                                    has_phone: !!document.getElementById('user_phone').value,
                                    agree_checked: document.getElementById('agree').checked
                                })
                            }).catch(function(error) {
                                console.warn('Не удалось отправить лог регистрации:', error);
                            });
                        });
                    }
                });
                </script>

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
