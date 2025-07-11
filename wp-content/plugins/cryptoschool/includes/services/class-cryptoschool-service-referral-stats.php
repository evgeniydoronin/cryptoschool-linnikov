<?php
/**
 * Сервис для работы с реферальной статистикой
 *
 * @package CryptoSchool
 * @subpackage Services
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс сервиса для работы с реферальной статистикой
 */
class CryptoSchool_Service_Referral_Stats extends CryptoSchool_Service {
    /**
     * Сервис инфлюенсеров
     *
     * @var CryptoSchool_Service_Influencer
     */
    protected $influencer_service;

    /**
     * Сервис запросов на вывод
     *
     * @var CryptoSchool_Service_Withdrawal
     */
    protected $withdrawal_service;

    /**
     * Конструктор
     *
     * @param CryptoSchool_Loader $loader Экземпляр загрузчика
     */
    public function __construct(CryptoSchool_Loader $loader) {
        parent::__construct($loader);
        $this->influencer_service = new CryptoSchool_Service_Influencer($loader);
        $this->withdrawal_service = new CryptoSchool_Service_Withdrawal($loader);
    }

    /**
     * Регистрация хуков и фильтров
     *
     * @return void
     */
    protected function register_hooks() {
        // Регистрация хуков для обновления статистики
        $this->add_action('cryptoschool_referral_link_created', 'update_link_stats');
        $this->add_action('cryptoschool_referral_click', 'update_click_stats');
        $this->add_action('cryptoschool_referral_conversion', 'update_conversion_stats');
        $this->add_action('cryptoschool_commission_earned', 'update_earnings_stats');
    }

    /**
     * Получение общей статистики реферальной системы
     *
     * @return array
     */
    public function get_general_statistics() {
        return [
            'total' => $this->get_total_statistics(),
            'monthly' => $this->get_monthly_statistics(),
            'top_referrers' => $this->get_top_referrers(),
            'top_links' => $this->get_top_referral_links()
        ];
    }

    /**
     * Получение общей статистики за все время
     *
     * @return array
     */
    public function get_total_statistics() {
        // В реальной реализации здесь будут запросы к БД
        // для подсчета реальных данных из таблиц:
        // - wp_cryptoschool_referral_links
        // - wp_cryptoschool_referral_users
        // - wp_cryptoschool_referral_transactions
        // - wp_cryptoschool_withdrawal_requests

        return [
            'referral_links' => 45,
            'referrals' => 128,
            'purchases' => 89,
            'commissions_amount' => 2450.75,
            'paid_amount' => 1890.25
        ];
    }

    /**
     * Получение статистики за последний месяц
     *
     * @return array
     */
    public function get_monthly_statistics() {
        // В реальной реализации здесь будут запросы к БД
        // с фильтрацией по дате (последние 30 дней)

        return [
            'referral_links' => 12,
            'referrals' => 34,
            'purchases' => 28,
            'commissions_amount' => 680.50,
            'withdrawal_requests' => 8
        ];
    }

    /**
     * Получение топ рефоводов
     *
     * @param int $limit Количество рефоводов в топе
     * @return array
     */
    public function get_top_referrers($limit = 10) {
        // В реальной реализации здесь будет запрос к БД
        // для получения пользователей с наибольшим заработком

        return [
            [
                'user_login' => 'top_referrer1',
                'user_email' => 'top1@example.com',
                'total_earned' => 450.75,
                'referrals_count' => 25
            ],
            [
                'user_login' => 'top_referrer2',
                'user_email' => 'top2@example.com',
                'total_earned' => 380.50,
                'referrals_count' => 18
            ],
            [
                'user_login' => 'top_referrer3',
                'user_email' => 'top3@example.com',
                'total_earned' => 295.25,
                'referrals_count' => 15
            ]
        ];
    }

    /**
     * Получение топ реферальных ссылок
     *
     * @param int $limit Количество ссылок в топе
     * @return array
     */
    public function get_top_referral_links($limit = 10) {
        // В реальной реализации здесь будет запрос к БД
        // для получения ссылок с наибольшей эффективностью

        return [
            [
                'link_name' => 'YouTube промо',
                'clicks' => 1250,
                'conversions' => 45,
                'conversion_rate' => 3.6,
                'total_earned' => 890.50
            ],
            [
                'link_name' => 'Telegram канал',
                'clicks' => 980,
                'conversions' => 32,
                'conversion_rate' => 3.3,
                'total_earned' => 650.75
            ],
            [
                'link_name' => 'Instagram Stories',
                'clicks' => 750,
                'conversions' => 18,
                'conversion_rate' => 2.4,
                'total_earned' => 385.25
            ]
        ];
    }

    /**
     * Получение статистики по пользователю
     *
     * @param int $user_id ID пользователя
     * @return array
     */
    public function get_user_statistics($user_id) {
        // В реальной реализации здесь будут запросы к БД
        // для получения статистики конкретного пользователя

        return [
            'total_links' => $this->get_user_links_count($user_id),
            'total_referrals' => $this->get_user_referrals_count($user_id),
            'total_earned' => $this->get_user_total_earned($user_id),
            'available_balance' => $this->withdrawal_service->get_user_balance($user_id),
            'total_withdrawn' => $this->get_user_total_withdrawn($user_id),
            'conversion_rate' => $this->get_user_conversion_rate($user_id),
            'links_stats' => $this->get_user_links_stats($user_id)
        ];
    }

    /**
     * Получение количества ссылок пользователя
     *
     * @param int $user_id ID пользователя
     * @return int
     */
    public function get_user_links_count($user_id) {
        // В реальной реализации здесь будет запрос к БД
        return rand(1, 10);
    }

    /**
     * Получение количества рефералов пользователя
     *
     * @param int $user_id ID пользователя
     * @return int
     */
    public function get_user_referrals_count($user_id) {
        // В реальной реализации здесь будет запрос к БД
        return rand(5, 50);
    }

    /**
     * Получение общего заработка пользователя
     *
     * @param int $user_id ID пользователя
     * @return float
     */
    public function get_user_total_earned($user_id) {
        // В реальной реализации здесь будет запрос к БД
        $demo_earnings = [
            1 => 450.75,
            2 => 380.50,
            3 => 295.25,
            10 => 250.00,
            15 => 180.75,
            20 => 320.50
        ];
        
        return isset($demo_earnings[$user_id]) ? $demo_earnings[$user_id] : 0.0;
    }

    /**
     * Получение общей суммы выведенных средств пользователя
     *
     * @param int $user_id ID пользователя
     * @return float
     */
    public function get_user_total_withdrawn($user_id) {
        // В реальной реализации здесь будет запрос к БД
        $demo_withdrawn = [
            1 => 200.00,
            2 => 150.00,
            3 => 100.00,
            10 => 100.00,
            15 => 105.25,
            20 => 120.50
        ];
        
        return isset($demo_withdrawn[$user_id]) ? $demo_withdrawn[$user_id] : 0.0;
    }

    /**
     * Получение конверсии пользователя
     *
     * @param int $user_id ID пользователя
     * @return float
     */
    public function get_user_conversion_rate($user_id) {
        // В реальной реализации здесь будет расчет на основе данных из БД
        return round(rand(15, 45) / 10, 1); // 1.5% - 4.5%
    }

    /**
     * Получение статистики по ссылкам пользователя
     *
     * @param int $user_id ID пользователя
     * @return array
     */
    public function get_user_links_stats($user_id) {
        // В реальной реализации здесь будет запрос к БД
        return [
            [
                'id' => 1,
                'link_name' => 'YouTube канал',
                'clicks' => 245,
                'conversions' => 12,
                'conversion_rate' => 4.9,
                'total_earned' => 180.50
            ],
            [
                'id' => 2,
                'link_name' => 'Telegram группа',
                'clicks' => 156,
                'conversions' => 8,
                'conversion_rate' => 5.1,
                'total_earned' => 120.25
            ]
        ];
    }

    /**
     * Получение статистики по периодам
     *
     * @param string $period Период (day, week, month, year)
     * @param int    $limit  Количество периодов
     * @return array
     */
    public function get_period_statistics($period = 'month', $limit = 12) {
        // В реальной реализации здесь будут запросы к БД
        // с группировкой по периодам

        $stats = [];
        
        for ($i = $limit - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} {$period}s"));
            $stats[] = [
                'period' => $date,
                'referral_links' => rand(5, 15),
                'referrals' => rand(10, 30),
                'purchases' => rand(5, 20),
                'commissions_amount' => rand(100, 500),
                'withdrawal_requests' => rand(1, 5)
            ];
        }
        
        return $stats;
    }

    /**
     * Получение статистики по конверсиям
     *
     * @return array
     */
    public function get_conversion_statistics() {
        // В реальной реализации здесь будут запросы к БД
        // для расчета конверсий по различным критериям

        return [
            'overall_conversion_rate' => 3.2,
            'conversion_by_source' => [
                'youtube' => 4.1,
                'telegram' => 3.8,
                'instagram' => 2.9,
                'twitter' => 2.1,
                'direct' => 5.2
            ],
            'conversion_by_month' => $this->get_monthly_conversion_rates(),
            'conversion_funnel' => [
                'clicks' => 10000,
                'registrations' => 850,
                'purchases' => 320,
                'click_to_registration' => 8.5,
                'registration_to_purchase' => 37.6,
                'click_to_purchase' => 3.2
            ]
        ];
    }

    /**
     * Получение конверсий по месяцам
     *
     * @return array
     */
    private function get_monthly_conversion_rates() {
        $rates = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-{$i} months"));
            $rates[$month] = round(rand(20, 50) / 10, 1);
        }
        
        return $rates;
    }

    /**
     * Получение статистики по географии
     *
     * @return array
     */
    public function get_geography_statistics() {
        // В реальной реализации здесь будут запросы к БД
        // с анализом IP-адресов или данных пользователей

        return [
            'countries' => [
                'Ukraine' => ['referrals' => 45, 'conversions' => 18, 'earnings' => 890.50],
                'Russia' => ['referrals' => 38, 'conversions' => 15, 'earnings' => 720.25],
                'Belarus' => ['referrals' => 22, 'conversions' => 9, 'earnings' => 450.75],
                'Kazakhstan' => ['referrals' => 18, 'conversions' => 7, 'earnings' => 350.00],
                'Moldova' => ['referrals' => 5, 'conversions' => 2, 'earnings' => 120.25]
            ],
            'cities' => [
                'Kyiv' => ['referrals' => 25, 'conversions' => 12],
                'Moscow' => ['referrals' => 20, 'conversions' => 8],
                'Minsk' => ['referrals' => 15, 'conversions' => 6],
                'Almaty' => ['referrals' => 12, 'conversions' => 5],
                'Odesa' => ['referrals' => 10, 'conversions' => 4]
            ]
        ];
    }

    /**
     * Обновление статистики при создании ссылки
     *
     * @param int $link_id ID ссылки
     * @param int $user_id ID пользователя
     * @return void
     */
    public function update_link_stats($link_id, $user_id) {
        $this->log_info('Обновление статистики при создании ссылки', [
            'link_id' => $link_id,
            'user_id' => $user_id
        ]);

        // В реальной реализации здесь будет обновление счетчиков
    }

    /**
     * Обновление статистики при клике по ссылке
     *
     * @param int    $link_id      ID ссылки
     * @param string $ip_address   IP-адрес
     * @param string $user_agent   User Agent
     * @param string $referrer     Реферер
     * @return void
     */
    public function update_click_stats($link_id, $ip_address, $user_agent, $referrer = '') {
        $this->log_info('Обновление статистики при клике по ссылке', [
            'link_id' => $link_id,
            'ip_address' => $ip_address
        ]);

        // В реальной реализации здесь будет:
        // 1. Увеличение счетчика кликов для ссылки
        // 2. Сохранение данных о клике для аналитики
        // 3. Определение географии по IP
        // 4. Анализ источника трафика
    }

    /**
     * Обновление статистики при конверсии
     *
     * @param int   $link_id    ID ссылки
     * @param int   $user_id    ID пользователя
     * @param float $amount     Сумма покупки
     * @return void
     */
    public function update_conversion_stats($link_id, $user_id, $amount) {
        $this->log_info('Обновление статистики при конверсии', [
            'link_id' => $link_id,
            'user_id' => $user_id,
            'amount' => $amount
        ]);

        // В реальной реализации здесь будет:
        // 1. Увеличение счетчика конверсий для ссылки
        // 2. Обновление суммы заработка
        // 3. Пересчет конверсии
    }

    /**
     * Обновление статистики при начислении комиссии
     *
     * @param int   $user_id         ID пользователя
     * @param float $commission      Сумма комиссии
     * @param int   $referral_level  Уровень реферала (1 или 2)
     * @return void
     */
    public function update_earnings_stats($user_id, $commission, $referral_level) {
        $this->log_info('Обновление статистики при начислении комиссии', [
            'user_id' => $user_id,
            'commission' => $commission,
            'referral_level' => $referral_level
        ]);

        // В реальной реализации здесь будет:
        // 1. Обновление общего заработка пользователя
        // 2. Обновление баланса для вывода
        // 3. Обновление статистики по уровням
    }

    /**
     * Экспорт статистики в CSV
     *
     * @param array $data   Данные для экспорта
     * @param array $headers Заголовки колонок
     * @return string Путь к созданному файлу
     */
    public function export_to_csv($data, $headers) {
        $upload_dir = wp_upload_dir();
        $filename = 'referral_stats_' . date('Y-m-d_H-i-s') . '.csv';
        $filepath = $upload_dir['path'] . '/' . $filename;

        $file = fopen($filepath, 'w');
        
        // Запись заголовков
        fputcsv($file, $headers);
        
        // Запись данных
        foreach ($data as $row) {
            fputcsv($file, $row);
        }
        
        fclose($file);

        $this->log_info('Экспорт статистики в CSV', [
            'filename' => $filename,
            'rows_count' => count($data)
        ]);

        return $filepath;
    }

    /**
     * Очистка старых данных статистики
     *
     * @param int $days_to_keep Количество дней для хранения
     * @return int Количество удаленных записей
     */
    public function cleanup_old_stats($days_to_keep = 365) {
        // В реальной реализации здесь будет удаление старых записей
        // из таблиц статистики для оптимизации производительности

        $this->log_info('Очистка старых данных статистики', [
            'days_to_keep' => $days_to_keep
        ]);

        return 0; // Количество удаленных записей
    }
}
