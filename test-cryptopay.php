<?php
/**
 * Тестовая страница для Crypto Pay
 *
 * Использование: Откройте http://localhost:8080/test-cryptopay.php
 */

// Загрузка WordPress
require_once('wp-load.php');

// Проверка авторизации
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}

// Получаем пакеты для тестирования
global $wpdb;
$packages = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}cryptoschool_packages WHERE is_active = 1");

// Обработка создания инвойса
$message = '';
$invoice_url = '';

if (isset($_POST['create_invoice'])) {
    // Загружаем сервис Crypto Pay
    if (file_exists(CRYPTOSCHOOL_PLUGIN_DIR . 'includes/services/class-cryptoschool-service-cryptopay.php')) {
        require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/services/class-cryptoschool-service-cryptopay.php';
        require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/services/class-cryptoschool-service.php';
        require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/class-cryptoschool-loader.php';

        $loader = new CryptoSchool_Loader();
        $cryptopay_service = new CryptoSchool_Service_CryptoPay($loader);

        $package_id = intval($_POST['package_id']);
        $user_id = get_current_user_id();

        $result = $cryptopay_service->create_invoice($package_id, $user_id);

        if (is_wp_error($result)) {
            $message = '<div style="color: red;">❌ Ошибка: ' . $result->get_error_message() . '</div>';
        } else {
            $invoice_url = $result['pay_url'];
            $message = '<div style="color: green;">✅ Инвойс создан успешно!</div>';
            $message .= '<div>Сумма: $' . $result['amount'] . ' USD</div>';
            $message .= '<div>Invoice ID: ' . $result['invoice_id'] . '</div>';
        }
    } else {
        $message = '<div style="color: red;">❌ Сервис Crypto Pay не найден</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тест Crypto Pay</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #007cba;
            padding-bottom: 10px;
        }
        .form-group {
            margin: 20px 0;
        }
        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        select, button {
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        select {
            width: 100%;
        }
        button {
            background: #007cba;
            color: white;
            cursor: pointer;
            margin-right: 10px;
        }
        button:hover {
            background: #005a87;
        }
        .message {
            margin: 20px 0;
            padding: 15px;
            background: #f0f8ff;
            border-radius: 5px;
        }
        .pay-button {
            display: inline-block;
            background: #0088cc;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
        }
        .pay-button:hover {
            background: #0066aa;
        }
        .info {
            background: #fffbf0;
            padding: 15px;
            border-left: 4px solid #ffa500;
            margin: 20px 0;
        }
        .settings-info {
            background: #f0f8ff;
            padding: 15px;
            border-left: 4px solid #007cba;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Тестирование Crypto Pay API</h1>

        <div class="info">
            <strong>👤 Текущий пользователь:</strong> <?php echo wp_get_current_user()->display_name; ?> (ID: <?php echo get_current_user_id(); ?>)
        </div>

        <div class="settings-info">
            <strong>⚙️ Настройки:</strong><br>
            <?php
            $testnet_mode = get_option('cryptoschool_cryptopay_testnet_mode', true);
            $api_token = get_option('cryptoschool_cryptopay_api_token', '');
            ?>
            Режим: <?php echo $testnet_mode ? '🧪 Тестовый (@CryptoTestnetBot)' : '💰 Боевой (@CryptoBot)'; ?><br>
            API токен: <?php echo $api_token ? '✅ Установлен' : '❌ Не установлен'; ?><br>
            <a href="<?php echo admin_url('admin.php?page=cryptoschool-settings#payments'); ?>">Перейти к настройкам</a>
        </div>

        <?php if ($message): ?>
            <div class="message">
                <?php echo $message; ?>
                <?php if ($invoice_url): ?>
                    <div>
                        <a href="<?php echo esc_url($invoice_url); ?>" target="_blank" class="pay-button">
                            💳 Перейти к оплате в Telegram
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="package_id">Выберите пакет для тестирования:</label>
                <select name="package_id" id="package_id" required>
                    <option value="">-- Выберите пакет --</option>
                    <?php foreach ($packages as $package): ?>
                        <option value="<?php echo $package->id; ?>">
                            <?php echo esc_html($package->title); ?> - $<?php echo $package->price; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <button type="submit" name="create_invoice">🚀 Создать тестовый инвойс</button>
                <button type="button" onclick="location.reload()">🔄 Обновить страницу</button>
            </div>
        </form>

        <div class="info">
            <strong>📝 Инструкция:</strong>
            <ol>
                <li>Убедитесь, что вы настроили API токен в админке</li>
                <li>Выберите пакет из списка</li>
                <li>Нажмите "Создать тестовый инвойс"</li>
                <li>Перейдите по ссылке для оплаты в Telegram</li>
                <li>После оплаты проверьте логи и базу данных</li>
            </ol>
        </div>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <h3>📋 Последние логи</h3>
            <?php
            $log_file = WP_CONTENT_DIR . '/cryptoschool-logs/payment_' . date('Y-m-d') . '.log';
            if (file_exists($log_file)) {
                $logs = file_get_contents($log_file);
                $log_lines = array_filter(explode("\n", $logs));
                $recent_logs = array_slice($log_lines, -10); // Последние 10 записей

                if (!empty($recent_logs)) {
                    echo '<div style="background: #f9f9f9; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 12px; max-height: 300px; overflow-y: auto;">';
                    foreach ($recent_logs as $log_line) {
                        if (stripos($log_line, 'cryptopay') !== false || stripos($log_line, 'payment') !== false) {
                            echo '<div style="margin-bottom: 5px; word-wrap: break-word;">' . esc_html($log_line) . '</div>';
                        }
                    }
                    echo '</div>';
                } else {
                    echo '<p>Логи не найдены</p>';
                }
            } else {
                echo '<p>Файл логов не найден: ' . esc_html($log_file) . '</p>';
            }
            ?>
        </div>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <h3>📊 Последние платежи</h3>
            <?php
            $recent_payments = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}cryptoschool_payments
                 WHERE user_id = %d
                 ORDER BY created_at DESC
                 LIMIT 5",
                get_current_user_id()
            ));
            ?>
            <?php if ($recent_payments): ?>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr style="background: #f0f0f0;">
                        <th style="padding: 10px; text-align: left;">ID</th>
                        <th style="padding: 10px; text-align: left;">Пакет</th>
                        <th style="padding: 10px; text-align: left;">Сумма</th>
                        <th style="padding: 10px; text-align: left;">Статус</th>
                        <th style="padding: 10px; text-align: left;">Дата</th>
                    </tr>
                    <?php foreach ($recent_payments as $payment): ?>
                        <?php
                        $package = $wpdb->get_row($wpdb->prepare(
                            "SELECT title FROM {$wpdb->prefix}cryptoschool_packages WHERE id = %d",
                            $payment->package_id
                        ));
                        ?>
                        <tr>
                            <td style="padding: 10px; border-bottom: 1px solid #ddd;"><?php echo $payment->id; ?></td>
                            <td style="padding: 10px; border-bottom: 1px solid #ddd;"><?php echo $package ? $package->title : 'N/A'; ?></td>
                            <td style="padding: 10px; border-bottom: 1px solid #ddd;">$<?php echo $payment->amount; ?></td>
                            <td style="padding: 10px; border-bottom: 1px solid #ddd;">
                                <?php
                                $status_colors = [
                                    'pending' => 'orange',
                                    'completed' => 'green',
                                    'failed' => 'red'
                                ];
                                $color = $status_colors[$payment->status] ?? 'gray';
                                ?>
                                <span style="color: <?php echo $color; ?>;">
                                    <?php echo ucfirst($payment->status); ?>
                                </span>
                            </td>
                            <td style="padding: 10px; border-bottom: 1px solid #ddd;">
                                <?php echo date('d.m.Y H:i', strtotime($payment->created_at)); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p>Нет платежей</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>