<?php
/**
 * Миграция для добавления полей Crypto Pay в таблицу платежей
 *
 * @package CryptoSchool
 * @subpackage Migrations
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс миграции для Crypto Pay
 */
class CryptoSchool_Migration_CryptoPay {

    /**
     * Запуск миграции
     *
     * @return bool
     */
    public static function up() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'cryptoschool_payments';

        // Проверяем существование таблицы
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

        if (!$table_exists) {
            return false;
        }

        // Проверяем, не добавлены ли уже поля
        $column_exists = $wpdb->get_var(
            "SHOW COLUMNS FROM $table_name LIKE 'cryptopay_invoice_id'"
        );

        if ($column_exists) {
            return true; // Миграция уже выполнена
        }

        // Добавляем новые поля для Crypto Pay
        $sql = "ALTER TABLE $table_name
                ADD COLUMN cryptopay_invoice_id VARCHAR(255) UNIQUE AFTER telegram_payment_id,
                ADD COLUMN cryptopay_status VARCHAR(50) AFTER cryptopay_invoice_id,
                ADD COLUMN crypto_currency VARCHAR(10) AFTER currency,
                ADD COLUMN crypto_amount DECIMAL(18,8) AFTER crypto_currency,
                ADD COLUMN exchange_rate DECIMAL(18,8) AFTER crypto_amount,
                ADD COLUMN admin_notified TINYINT(1) DEFAULT 0 AFTER status,
                ADD INDEX idx_cryptopay_invoice (cryptopay_invoice_id)";

        $result = $wpdb->query($sql);

        if ($result === false) {
            error_log('CryptoSchool Migration Error: ' . $wpdb->last_error);
            return false;
        }

        // Сохраняем версию миграции
        update_option('cryptoschool_db_version_cryptopay', '1.0.0');

        return true;
    }

    /**
     * Откат миграции
     *
     * @return bool
     */
    public static function down() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'cryptoschool_payments';

        // Удаляем добавленные поля
        $sql = "ALTER TABLE $table_name
                DROP COLUMN IF EXISTS cryptopay_invoice_id,
                DROP COLUMN IF EXISTS cryptopay_status,
                DROP COLUMN IF EXISTS crypto_currency,
                DROP COLUMN IF EXISTS crypto_amount,
                DROP COLUMN IF EXISTS exchange_rate,
                DROP COLUMN IF EXISTS admin_notified";

        $result = $wpdb->query($sql);

        if ($result === false) {
            error_log('CryptoSchool Migration Rollback Error: ' . $wpdb->last_error);
            return false;
        }

        // Удаляем версию миграции
        delete_option('cryptoschool_db_version_cryptopay');

        return true;
    }
}