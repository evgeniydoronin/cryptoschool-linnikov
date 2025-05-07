<?php
/**
 * Деактиватор плагина
 *
 * Выполняет действия при деактивации плагина
 *
 * @package CryptoSchool
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс деактиватора плагина
 */
class CryptoSchool_Deactivator {
    /**
     * Деактивация плагина
     *
     * @return void
     */
    public static function deactivate() {
        // Очистка временных данных
        self::clear_transients();

        // Удаление запланированных задач
        self::clear_scheduled_events();

        // Сохранение флага деактивации
        update_option('cryptoschool_deactivated', true);
    }

    /**
     * Очистка временных данных
     *
     * @return void
     */
    private static function clear_transients() {
        delete_transient('cryptoschool_activation_redirect');
        // Добавьте здесь другие временные данные, которые нужно очистить
    }

    /**
     * Удаление запланированных задач
     *
     * @return void
     */
    private static function clear_scheduled_events() {
        wp_clear_scheduled_hook('cryptoschool_daily_maintenance');
        wp_clear_scheduled_hook('cryptoschool_check_expired_access');
        // Добавьте здесь другие запланированные задачи, которые нужно удалить
    }

    /**
     * Удаление таблиц и данных плагина
     * 
     * Этот метод не вызывается автоматически при деактивации плагина,
     * а используется только при полном удалении плагина
     *
     * @return void
     */
    public static function uninstall() {
        global $wpdb;

        // Удаление таблиц
        $tables = [
            'cryptoschool_courses',
            'cryptoschool_modules',
            'cryptoschool_lessons',
            'cryptoschool_packages',
            'cryptoschool_lesson_tasks', // Таблица заданий урока
            'cryptoschool_user_lesson_progress', // Таблица прогресса по урокам
            'cryptoschool_user_task_progress', // Таблица прогресса по заданиям
            'cryptoschool_user_access',
            'cryptoschool_referral_links',
            'cryptoschool_referral_users',
            'cryptoschool_payments',
            'cryptoschool_referral_transactions',
            'cryptoschool_withdrawal_requests',
            'cryptoschool_telegram_groups',
            'cryptoschool_package_telegram_groups',
            'cryptoschool_telegram_users',
            'cryptoschool_telegram_user_groups',
            'cryptoschool_support_tickets',
            'cryptoschool_user_leaderboard',
            'cryptoschool_recent_activities',
            'cryptoschool_influencer_settings',
            'cryptoschool_achievements',
            'cryptoschool_user_achievements'
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
        }

        // Удаление опций
        $options = [
            'cryptoschool_db_version',
            'cryptoschool_referral_base_percent',
            'cryptoschool_referral_min_withdrawal',
            'cryptoschool_enable_auto_language',
            'cryptoschool_deactivated',
            'cryptoschool_telegram_bot_token',
            'cryptoschool_telegram_webhook_url',
            'cryptoschool_payment_gateway_crypto',
            'cryptoschool_payment_gateway_fiat',
            'cryptoschool_auto_delete_expired_access',
            'cryptoschool_leaderboard_update_frequency',
            'cryptoschool_support_notification_email'
        ];

        foreach ($options as $option) {
            delete_option($option);
        }

        // Удаление ролей и возможностей
        self::remove_roles_and_capabilities();
    }

    /**
     * Удаление ролей и возможностей
     *
     * @return void
     */
    private static function remove_roles_and_capabilities() {
        // Удаление роли "Студент"
        remove_role('cryptoschool_student');

        // Удаление возможностей у администратора
        $admin = get_role('administrator');
        if ($admin) {
            $admin->remove_cap('cryptoschool_manage_courses');
            $admin->remove_cap('cryptoschool_manage_modules');
            $admin->remove_cap('cryptoschool_manage_lessons');
            $admin->remove_cap('cryptoschool_manage_packages');
            $admin->remove_cap('cryptoschool_manage_users');
            $admin->remove_cap('cryptoschool_manage_referrals');
            $admin->remove_cap('cryptoschool_access_courses');
        }
    }
}
