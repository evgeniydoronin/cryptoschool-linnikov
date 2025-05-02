<?php
/**
 * Класс для работы с профилем пользователя
 *
 * @package CryptoSchool
 * @subpackage CryptoSchool/public
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс для работы с профилем пользователя
 */
class CryptoSchool_Public_Profile {
    /**
     * Загрузчик для регистрации хуков
     *
     * @var CryptoSchool_Loader
     */
    private $loader;

    /**
     * Конструктор класса
     *
     * @param CryptoSchool_Loader $loader Загрузчик для регистрации хуков
     */
    public function __construct($loader) {
        $this->loader = $loader;
        $this->register_hooks();
    }

    /**
     * Регистрация хуков
     */
    private function register_hooks() {
        // Обработка загрузки изображения профиля
        $this->loader->add_action('init', $this, 'handle_profile_photo_upload');
        
        // Обработка обновления профиля
        $this->loader->add_action('init', $this, 'handle_profile_update');
        
        // Обработка обновления пароля
        $this->loader->add_action('init', $this, 'handle_password_update');
    }

    /**
     * Обработка загрузки изображения профиля
     */
    public function handle_profile_photo_upload() {
        // Проверяем, авторизован ли пользователь
        if (!is_user_logged_in()) {
            return;
        }

        // Проверяем, была ли отправлена форма загрузки фото
        if (isset($_POST['update_photo']) && isset($_FILES['profile_photo']) && !empty($_FILES['profile_photo']['name'])) {
            // Получаем ID текущего пользователя
            $user_id = get_current_user_id();
            
            // Проверяем тип файла
            $file_type = wp_check_filetype($_FILES['profile_photo']['name']);
            $allowed_types = array('jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png');
            
            if (!in_array($file_type['type'], $allowed_types)) {
                // Добавляем сообщение об ошибке
                add_action('wp_footer', function() {
                    echo '<script>
                        document.addEventListener("DOMContentLoaded", function() {
                            alert("Неверный формат файла. Разрешены только JPG и PNG.");
                        });
                    </script>';
                });
                return;
            }
            
            // Проверяем размер файла (5 МБ = 5 * 1024 * 1024 = 5242880 байт)
            if ($_FILES['profile_photo']['size'] > 5242880) {
                // Добавляем сообщение об ошибке
                add_action('wp_footer', function() {
                    echo '<script>
                        document.addEventListener("DOMContentLoaded", function() {
                            alert("Размер файла превышает 5 МБ.");
                        });
                    </script>';
                });
                return;
            }
            
            // Загружаем файл в медиабиблиотеку WordPress
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            
            // Загружаем файл
            $attachment_id = media_handle_upload('profile_photo', 0);
            
            if (is_wp_error($attachment_id)) {
                // Добавляем сообщение об ошибке
                add_action('wp_footer', function() use ($attachment_id) {
                    echo '<script>
                        document.addEventListener("DOMContentLoaded", function() {
                            alert("Ошибка загрузки файла: ' . esc_js($attachment_id->get_error_message()) . '");
                        });
                    </script>';
                });
                return;
            }
            
            // Получаем URL загруженного изображения
            $attachment_url = wp_get_attachment_url($attachment_id);
            
            // Обновляем аватар пользователя
            update_user_meta($user_id, 'cryptoschool_profile_photo', $attachment_url);
            
            // Добавляем сообщение об успешной загрузке
            add_action('wp_footer', function() {
                echo '<script>
                    document.addEventListener("DOMContentLoaded", function() {
                        alert("Фото профиля успешно обновлено.");
                    });
                </script>';
            });
            
            // Перенаправляем на ту же страницу для обновления отображения
            wp_redirect($_SERVER['REQUEST_URI']);
            exit;
        }
    }

    /**
     * Обработка обновления профиля
     */
    public function handle_profile_update() {
        // Проверяем, авторизован ли пользователь
        if (!is_user_logged_in()) {
            return;
        }

        // Проверяем, была ли отправлена форма обновления профиля
        if (isset($_POST['update_profile'])) {
            // Получаем ID текущего пользователя
            $user_id = get_current_user_id();
            
            // Имя пользователя (ник) нельзя изменить после регистрации
            // Поэтому мы не обрабатываем поле user_name
            
            // Обновляем email
            if (isset($_POST['user_email']) && !empty($_POST['user_email']) && is_email($_POST['user_email'])) {
                $user_data = array(
                    'ID' => $user_id,
                    'user_email' => sanitize_email($_POST['user_email'])
                );
                wp_update_user($user_data);
            }
            
            // Обновляем Telegram
            if (isset($_POST['user_telegram'])) {
                update_user_meta($user_id, 'telegram', sanitize_text_field($_POST['user_telegram']));
            }
            
            // Обновляем Discord
            if (isset($_POST['user_discord'])) {
                update_user_meta($user_id, 'discord', sanitize_text_field($_POST['user_discord']));
            }
            
            // Добавляем сообщение об успешном обновлении
            add_action('wp_footer', function() {
                echo '<script>
                    document.addEventListener("DOMContentLoaded", function() {
                        alert("Профиль успешно обновлен.");
                    });
                </script>';
            });
            
            // Перенаправляем на ту же страницу для обновления отображения
            wp_redirect($_SERVER['REQUEST_URI']);
            exit;
        }
    }

    /**
     * Обработка обновления пароля
     */
    public function handle_password_update() {
        // Проверяем, авторизован ли пользователь
        if (!is_user_logged_in()) {
            return;
        }

        // Проверяем, была ли отправлена форма обновления пароля
        if (isset($_POST['update_password'])) {
            // Получаем ID текущего пользователя
            $user_id = get_current_user_id();
            
            // Получаем данные из формы
            $old_password = isset($_POST['old_password']) ? $_POST['old_password'] : '';
            $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
            $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
            
            // Проверяем, что все поля заполнены
            if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
                // Добавляем сообщение об ошибке
                add_action('wp_footer', function() {
                    echo '<script>
                        document.addEventListener("DOMContentLoaded", function() {
                            alert("Все поля должны быть заполнены.");
                        });
                    </script>';
                });
                return;
            }
            
            // Проверяем, что новый пароль и подтверждение совпадают
            if ($new_password !== $confirm_password) {
                // Добавляем сообщение об ошибке
                add_action('wp_footer', function() {
                    echo '<script>
                        document.addEventListener("DOMContentLoaded", function() {
                            alert("Новый пароль и подтверждение не совпадают.");
                        });
                    </script>';
                });
                return;
            }
            
            // Проверяем старый пароль
            $user = get_user_by('id', $user_id);
            if (!$user || !wp_check_password($old_password, $user->data->user_pass, $user->ID)) {
                // Добавляем сообщение об ошибке
                add_action('wp_footer', function() {
                    echo '<script>
                        document.addEventListener("DOMContentLoaded", function() {
                            alert("Неверный старый пароль.");
                        });
                    </script>';
                });
                return;
            }
            
            // Обновляем пароль
            wp_set_password($new_password, $user_id);
            
            // Добавляем сообщение об успешном обновлении
            add_action('wp_footer', function() {
                echo '<script>
                    document.addEventListener("DOMContentLoaded", function() {
                        alert("Пароль успешно обновлен. Пожалуйста, войдите снова.");
                        window.location.href = "' . esc_url(site_url('/sign-in/')) . '";
                    });
                </script>';
            });
            
            // Перенаправляем на страницу входа
            wp_redirect(site_url('/sign-in/'));
            exit;
        }
    }
}
