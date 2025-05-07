<?php
/**
 * Активатор плагина
 *
 * Выполняет действия при активации плагина
 *
 * @package CryptoSchool
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс активатора плагина
 */
class CryptoSchool_Activator {
    /**
     * Активация плагина
     *
     * @return void
     */
    public static function activate() {
        self::create_tables();
        self::create_roles();
        self::set_default_options();
    }

    /**
     * Создание таблиц в базе данных
     *
     * @return void
     */
    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Получение SQL-запросов для создания таблиц
        $sql_queries = self::get_table_schemas($charset_collate);

        // Выполнение SQL-запросов
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        foreach ($sql_queries as $sql) {
            dbDelta($sql);
        }

        // Сохранение версии схемы базы данных
        update_option('cryptoschool_db_version', CRYPTOSCHOOL_VERSION);
    }

    /**
     * Получение SQL-запросов для создания таблиц
     *
     * @param string $charset_collate Кодировка и сравнение
     * @return array
     */
    private static function get_table_schemas($charset_collate) {
        global $wpdb;
        $sql_queries = [];

        // Таблица курсов
        $table_name = $wpdb->prefix . 'cryptoschool_courses';
        $sql_queries[] = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            title text NOT NULL,
            description longtext,
            thumbnail varchar(255),
            difficulty_level varchar(50),
            slug varchar(200) NOT NULL,
            course_order int(11) DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            completion_points int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY slug (slug(191))
        ) $charset_collate;";

        // Таблица уроков
        $table_name = $wpdb->prefix . 'cryptoschool_lessons';
        $sql_queries[] = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            course_id bigint(20) UNSIGNED NOT NULL,
            title text NOT NULL,
            content longtext,
            video_url varchar(255),
            lesson_order int(11) DEFAULT 0,
            slug varchar(200) NOT NULL,
            completion_points int(11) DEFAULT 5,
            completion_tasks longtext,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY course_id (course_id),
            KEY slug (slug(191))
        ) $charset_collate;";

        // Таблица пакетов
        $table_name = $wpdb->prefix . 'cryptoschool_packages';
        $sql_queries[] = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            title text NOT NULL,
            description longtext,
            price decimal(10,2) NOT NULL DEFAULT 0,
            package_type enum('course', 'community', 'combined') NOT NULL,
            duration_months int(11) DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            creoin_points int(11) DEFAULT 0,
            features longtext,
            course_ids longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Таблица заданий урока
        $table_name = $wpdb->prefix . 'cryptoschool_lesson_tasks';
        $sql_queries[] = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            lesson_id bigint(20) UNSIGNED NOT NULL,
            title text COLLATE utf8mb4_unicode_ci NOT NULL,
            task_order int(11) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY lesson_id (lesson_id)
        ) $charset_collate;";

        // Таблица прогресса пользователя по урокам
        $table_name = $wpdb->prefix . 'cryptoschool_user_lesson_progress';
        $sql_queries[] = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            lesson_id bigint(20) UNSIGNED NOT NULL,
            is_completed tinyint(1) NOT NULL DEFAULT 0,
            progress_percent int(11) NOT NULL DEFAULT 0,
            completed_at datetime DEFAULT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_lesson (user_id, lesson_id),
            KEY user_id (user_id),
            KEY lesson_id (lesson_id)
        ) $charset_collate;";

        // Таблица прогресса пользователя по заданиям
        $table_name = $wpdb->prefix . 'cryptoschool_user_task_progress';
        $sql_queries[] = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            lesson_id bigint(20) UNSIGNED NOT NULL,
            task_id bigint(20) UNSIGNED NOT NULL,
            is_completed tinyint(1) NOT NULL DEFAULT 0,
            completed_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_task (user_id, task_id),
            KEY user_id (user_id),
            KEY lesson_id (lesson_id),
            KEY task_id (task_id)
        ) $charset_collate;";

        // Таблица доступов пользователя
        $table_name = $wpdb->prefix . 'cryptoschool_user_access';
        $sql_queries[] = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            package_id bigint(20) UNSIGNED NOT NULL,
            access_start datetime NOT NULL,
            access_end datetime DEFAULT NULL,
            status enum('active', 'expired') DEFAULT 'active',
            telegram_status enum('none', 'invited', 'active', 'removed') DEFAULT 'none',
            telegram_invite_link varchar(255) DEFAULT NULL,
            telegram_invite_date datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY package_id (package_id),
            KEY status (status)
        ) $charset_collate;";

        // Таблица реферальных ссылок
        $table_name = $wpdb->prefix . 'cryptoschool_referral_links';
        $sql_queries[] = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            referral_code varchar(32) NOT NULL,
            discount_percent decimal(5,2) DEFAULT 0,
            commission_percent decimal(5,2) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY referral_code (referral_code),
            KEY user_id (user_id)
        ) $charset_collate;";

        // Таблица реферальных пользователей
        $table_name = $wpdb->prefix . 'cryptoschool_referral_users';
        $sql_queries[] = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            referrer_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            referral_link_id bigint(20) UNSIGNED NOT NULL,
            registration_date datetime DEFAULT CURRENT_TIMESTAMP,
            status enum('registered', 'purchased') DEFAULT 'registered',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id),
            KEY referrer_id (referrer_id),
            KEY referral_link_id (referral_link_id)
        ) $charset_collate;";

        // Таблица платежей
        $table_name = $wpdb->prefix . 'cryptoschool_payments';
        $sql_queries[] = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            email varchar(100) DEFAULT NULL,
            package_id bigint(20) UNSIGNED NOT NULL,
            amount decimal(10,2) NOT NULL,
            currency varchar(10) NOT NULL DEFAULT 'USD',
            payment_method enum('crypto', 'card') NOT NULL,
            payment_gateway varchar(50) DEFAULT NULL,
            transaction_id varchar(64) DEFAULT NULL,
            telegram_payment_id varchar(64) DEFAULT NULL,
            referral_link_id bigint(20) UNSIGNED DEFAULT NULL,
            status enum('pending', 'completed', 'failed') DEFAULT 'pending',
            registration_token varchar(64) DEFAULT NULL,
            registration_status enum('pending', 'completed') DEFAULT 'pending',
            payment_date datetime DEFAULT CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY package_id (package_id),
            KEY referral_link_id (referral_link_id),
            KEY status (status),
            KEY email (email)
        ) $charset_collate;";

        // Таблица реферальных транзакций
        $table_name = $wpdb->prefix . 'cryptoschool_referral_transactions';
        $sql_queries[] = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            referrer_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            payment_id bigint(20) UNSIGNED NOT NULL,
            amount decimal(10,2) NOT NULL,
            status enum('pending', 'processing', 'completed', 'rejected') DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            processed_at datetime DEFAULT NULL,
            comment text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY referrer_id (referrer_id),
            KEY user_id (user_id),
            KEY payment_id (payment_id),
            KEY status (status)
        ) $charset_collate;";

        // Таблица запросов на вывод средств
        $table_name = $wpdb->prefix . 'cryptoschool_withdrawal_requests';
        $sql_queries[] = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            amount decimal(10,2) NOT NULL,
            crypto_address varchar(128) NOT NULL,
            status enum('pending', 'approved', 'paid', 'rejected') DEFAULT 'pending',
            request_date datetime DEFAULT CURRENT_TIMESTAMP,
            payment_date datetime DEFAULT NULL,
            comment text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";

        // Таблица Telegram-групп
        $table_name = $wpdb->prefix . 'cryptoschool_telegram_groups';
        $sql_queries[] = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            group_name varchar(255) NOT NULL,
            group_id varchar(100) NOT NULL,
            group_type enum('main', 'course', 'special') NOT NULL DEFAULT 'main',
            invite_link varchar(255) DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY group_id (group_id)
        ) $charset_collate;";

        // Таблица связи пакетов и Telegram-групп
        $table_name = $wpdb->prefix . 'cryptoschool_package_telegram_groups';
        $sql_queries[] = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            package_id bigint(20) UNSIGNED NOT NULL,
            telegram_group_id bigint(20) UNSIGNED NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY package_group (package_id, telegram_group_id),
            KEY package_id (package_id),
            KEY telegram_group_id (telegram_group_id)
        ) $charset_collate;";

        // Таблица пользователей Telegram
        $table_name = $wpdb->prefix . 'cryptoschool_telegram_users';
        $sql_queries[] = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            telegram_id varchar(100) NOT NULL,
            telegram_username varchar(255) DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY telegram_id (telegram_id),
            KEY user_id (user_id)
        ) $charset_collate;";

        // Таблица доступа пользователей к Telegram-группам
        $table_name = $wpdb->prefix . 'cryptoschool_telegram_user_groups';
        $sql_queries[] = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            telegram_user_id bigint(20) UNSIGNED NOT NULL,
            telegram_group_id bigint(20) UNSIGNED NOT NULL,
            access_start datetime DEFAULT CURRENT_TIMESTAMP,
            access_end datetime DEFAULT NULL,
            status enum('active', 'expired', 'removed') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_group (telegram_user_id, telegram_group_id),
            KEY telegram_user_id (telegram_user_id),
            KEY telegram_group_id (telegram_group_id),
            KEY status (status)
        ) $charset_collate;";

        // Таблица поддержки/тикетов
        $table_name = $wpdb->prefix . 'cryptoschool_support_tickets';
        $sql_queries[] = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            specialist_id bigint(20) UNSIGNED DEFAULT NULL,
            lesson_id bigint(20) UNSIGNED DEFAULT NULL,
            subject varchar(255) NOT NULL,
            message longtext NOT NULL,
            status enum('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY specialist_id (specialist_id),
            KEY lesson_id (lesson_id),
            KEY status (status)
        ) $charset_collate;";

        // Таблица рейтинга пользователей
        $table_name = $wpdb->prefix . 'cryptoschool_user_leaderboard';
        $sql_queries[] = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            total_points int(11) DEFAULT 0,
            rank int(11) DEFAULT 0,
            completed_lessons int(11) DEFAULT 0,
            days_active int(11) DEFAULT 0,
            last_updated datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id),
            KEY rank (rank)
        ) $charset_collate;";

        // Таблица последних активностей
        $table_name = $wpdb->prefix . 'cryptoschool_recent_activities';
        $sql_queries[] = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            activity_type enum('lesson_start', 'lesson_complete', 'course_start', 'course_complete') NOT NULL,
            ref_id bigint(20) UNSIGNED NOT NULL,
            title text NOT NULL,
            status enum('opened', 'completed') DEFAULT 'opened',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY activity_type (activity_type),
            KEY ref_id (ref_id)
        ) $charset_collate;";

        // Таблица настроек инфлюенсеров
        $table_name = $wpdb->prefix . 'cryptoschool_influencer_settings';
        $sql_queries[] = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            max_commission_percent decimal(5,2) DEFAULT 20.00,
            is_influencer tinyint(1) DEFAULT 0,
            admin_notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id)
        ) $charset_collate;";

        // Таблица достижений
        $table_name = $wpdb->prefix . 'cryptoschool_achievements';
        $sql_queries[] = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            title text NOT NULL,
            description text NOT NULL,
            icon varchar(255) DEFAULT NULL,
            points int(11) DEFAULT 0,
            achievement_key varchar(100) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY achievement_key (achievement_key)
        ) $charset_collate;";

        // Таблица пользовательских достижений
        $table_name = $wpdb->prefix . 'cryptoschool_user_achievements';
        $sql_queries[] = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            achievement_id bigint(20) UNSIGNED NOT NULL,
            earned_date datetime DEFAULT CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_achievement (user_id, achievement_id),
            KEY user_id (user_id),
            KEY achievement_id (achievement_id)
        ) $charset_collate;";

        return $sql_queries;
    }

    /**
     * Создание ролей и возможностей
     *
     * @return void
     */
    private static function create_roles() {
        // Добавление роли "Студент"
        add_role(
            'cryptoschool_student',
            __('Студент', 'cryptoschool'),
            [
                'read' => true,
                'cryptoschool_access_courses' => true,
            ]
        );

        // Добавление возможностей администратору
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('cryptoschool_manage_courses');
            $admin->add_cap('cryptoschool_manage_lessons');
            $admin->add_cap('cryptoschool_manage_packages');
            $admin->add_cap('cryptoschool_manage_users');
            $admin->add_cap('cryptoschool_manage_referrals');
            $admin->add_cap('cryptoschool_access_courses');
        }
    }

    /**
     * Установка значений по умолчанию для опций плагина
     *
     * @return void
     */
    private static function set_default_options() {
        // Установка опций плагина
        $default_options = [
            'cryptoschool_referral_base_percent' => 20,
            'cryptoschool_referral_min_withdrawal' => 100,
            'cryptoschool_enable_auto_language' => 1,
        ];

        foreach ($default_options as $option_name => $option_value) {
            if (get_option($option_name) === false) {
                update_option($option_name, $option_value);
            }
        }
    }
}
