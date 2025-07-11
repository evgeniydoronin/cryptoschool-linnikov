<?php
/**
 * Сервис для работы с двухуровневой системой комиссий
 *
 * @package CryptoSchool
 * @subpackage Services
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс сервиса двухуровневой системы комиссий
 */
class CryptoSchool_Service_Commission extends CryptoSchool_Service {

    /**
     * Репозиторий реферальных ссылок
     *
     * @var CryptoSchool_Repository_Referral_Link
     */
    private $referral_link_repository;

    /**
     * Репозиторий реферальных транзакций
     *
     * @var CryptoSchool_Repository_Referral_Transaction
     */
    private $transaction_repository;

    /**
     * Конструктор класса
     *
     * @param CryptoSchool_Loader $loader Экземпляр загрузчика
     */
    public function __construct(CryptoSchool_Loader $loader) {
        parent::__construct($loader);
        $this->referral_link_repository = new CryptoSchool_Repository_Referral_Link();
        $this->transaction_repository = new CryptoSchool_Repository_Referral_Transaction();
    }

    /**
     * Регистрация хуков и фильтров
     *
     * @return void
     */
    protected function register_hooks() {
        // Хук для обработки успешного платежа
        $this->add_action('cryptoschool_payment_completed', 'process_payment_commissions', 10, 2);
        
        // Хук для обновления статуса платежа
        $this->add_action('cryptoschool_payment_status_changed', 'update_commission_status', 10, 3);
    }

    /**
     * Обработка комиссий при успешном платеже
     *
     * @param int   $payment_id ID платежа
     * @param array $payment_data Данные платежа
     * @return void
     */
    public function process_payment_commissions($payment_id, $payment_data) {
        // Получаем реферальные данные из сессии
        $referral_data = $this->get_referral_from_session();
        
        if (!$referral_data) {
            // Нет реферальных данных - ничего не делаем
            return;
        }

        // Получаем реферальную ссылку
        $referral_link = $this->referral_link_repository->find($referral_data['link_id']);
        
        if (!$referral_link) {
            $this->log_error('Реферальная ссылка не найдена', [
                'link_id' => $referral_data['link_id'],
                'payment_id' => $payment_id
            ]);
            return;
        }

        // Применяем скидку к платежу
        $this->apply_discount_to_payment($payment_id, $referral_link, $payment_data);

        // Начисляем комиссию первого уровня
        $this->create_first_level_commission($payment_id, $referral_link, $payment_data);

        // Проверяем и начисляем комиссию второго уровня
        $this->create_second_level_commission($payment_id, $referral_link, $payment_data);

        // Обновляем статистику реферальной ссылки
        $this->update_link_statistics($referral_link->getAttribute('id'), $payment_data['amount']);

        // Очищаем реферальные данные из сессии
        $this->clear_referral_from_session();

        $this->log_info('Комиссии обработаны успешно', [
            'payment_id' => $payment_id,
            'link_id' => $referral_link->getAttribute('id'),
            'amount' => $payment_data['amount']
        ]);
    }

    /**
     * Применение скидки к платежу
     *
     * @param int                                    $payment_id     ID платежа
     * @param CryptoSchool_Model_Referral_Link      $referral_link  Реферальная ссылка
     * @param array                                  $payment_data   Данные платежа
     * @return void
     */
    private function apply_discount_to_payment($payment_id, $referral_link, $payment_data) {
        $discount_percent = $referral_link->get_discount_percent();
        
        if ($discount_percent <= 0) {
            return; // Нет скидки
        }

        $original_amount = $payment_data['amount'];
        $discount_amount = ($original_amount * $discount_percent) / 100;
        $final_amount = $original_amount - $discount_amount;

        // Обновляем сумму платежа в базе данных
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'cryptoschool_payments',
            [
                'original_amount' => $original_amount,
                'discount_percent' => $discount_percent,
                'discount_amount' => $discount_amount,
                'final_amount' => $final_amount,
                'referral_link_id' => $referral_link->getAttribute('id')
            ],
            ['id' => $payment_id],
            ['%f', '%f', '%f', '%f', '%d'],
            ['%d']
        );

        $this->log_info('Скидка применена к платежу', [
            'payment_id' => $payment_id,
            'original_amount' => $original_amount,
            'discount_percent' => $discount_percent,
            'discount_amount' => $discount_amount,
            'final_amount' => $final_amount
        ]);
    }

    /**
     * Создание комиссии первого уровня
     *
     * @param int                                    $payment_id     ID платежа
     * @param CryptoSchool_Model_Referral_Link      $referral_link  Реферальная ссылка
     * @param array                                  $payment_data   Данные платежа
     * @return void
     */
    private function create_first_level_commission($payment_id, $referral_link, $payment_data) {
        $commission_percent = $referral_link->get_commission_percent();
        $commission_amount = ($payment_data['amount'] * $commission_percent) / 100;

        $transaction_data = [
            'referrer_id' => $referral_link->get_user_id(),
            'user_id' => $payment_data['user_id'],
            'payment_id' => $payment_id,
            'referral_link_id' => $referral_link->getAttribute('id'),
            'referral_level' => 1,
            'amount' => $commission_amount,
            'commission_percent' => $commission_percent,
            'status' => 'completed',
            'comment' => 'Комиссия 1-го уровня'
        ];

        $transaction = $this->transaction_repository->create($transaction_data);

        if ($transaction) {
            $this->log_info('Создана комиссия 1-го уровня', [
                'transaction_id' => $transaction->getAttribute('id'),
                'referrer_id' => $referral_link->get_user_id(),
                'amount' => $commission_amount,
                'percent' => $commission_percent
            ]);
        } else {
            $this->log_error('Ошибка создания комиссии 1-го уровня', [
                'payment_id' => $payment_id,
                'referrer_id' => $referral_link->get_user_id()
            ]);
        }
    }

    /**
     * Создание комиссии второго уровня
     *
     * @param int                                    $payment_id     ID платежа
     * @param CryptoSchool_Model_Referral_Link      $referral_link  Реферальная ссылка
     * @param array                                  $payment_data   Данные платежа
     * @return void
     */
    private function create_second_level_commission($payment_id, $referral_link, $payment_data) {
        // Ищем рефовода второго уровня
        $second_level_referrer = $this->find_second_level_referrer($referral_link->get_user_id());
        
        if (!$second_level_referrer) {
            // Нет рефовода второго уровня
            return;
        }

        // Фиксированная комиссия 5% для второго уровня
        $commission_percent = 5.0;
        $commission_amount = ($payment_data['amount'] * $commission_percent) / 100;

        $transaction_data = [
            'referrer_id' => $second_level_referrer['referrer_id'],
            'user_id' => $payment_data['user_id'],
            'payment_id' => $payment_id,
            'referral_link_id' => $second_level_referrer['referral_link_id'],
            'referral_level' => 2,
            'amount' => $commission_amount,
            'commission_percent' => $commission_percent,
            'status' => 'completed',
            'comment' => 'Комиссия 2-го уровня'
        ];

        $transaction = $this->transaction_repository->create($transaction_data);

        if ($transaction) {
            $this->log_info('Создана комиссия 2-го уровня', [
                'transaction_id' => $transaction->getAttribute('id'),
                'referrer_id' => $second_level_referrer['referrer_id'],
                'amount' => $commission_amount,
                'percent' => $commission_percent
            ]);
        } else {
            $this->log_error('Ошибка создания комиссии 2-го уровня', [
                'payment_id' => $payment_id,
                'referrer_id' => $second_level_referrer['referrer_id']
            ]);
        }
    }

    /**
     * Поиск рефовода второго уровня
     *
     * @param int $first_level_referrer_id ID рефовода первого уровня
     * @return array|null
     */
    private function find_second_level_referrer($first_level_referrer_id) {
        global $wpdb;

        // Ищем, кто привел рефовода первого уровня
        $query = $wpdb->prepare(
            "SELECT ru.referrer_id, ru.referral_link_id 
             FROM {$wpdb->prefix}cryptoschool_referral_users ru 
             WHERE ru.user_id = %d 
             ORDER BY ru.registration_date DESC 
             LIMIT 1",
            (int) $first_level_referrer_id
        );

        $result = $wpdb->get_row($query, ARRAY_A);

        return $result ?: null;
    }

    /**
     * Обновление статистики реферальной ссылки
     *
     * @param int   $link_id ID реферальной ссылки
     * @param float $amount  Сумма платежа
     * @return void
     */
    private function update_link_statistics($link_id, $amount) {
        global $wpdb;

        // Увеличиваем счетчик конверсий и общую сумму заработка
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}cryptoschool_referral_links 
             SET conversions_count = conversions_count + 1,
                 total_earned = total_earned + %f,
                 updated_at = %s
             WHERE id = %d",
            $amount,
            current_time('mysql'),
            (int) $link_id
        ));

        $this->log_info('Обновлена статистика реферальной ссылки', [
            'link_id' => $link_id,
            'amount' => $amount
        ]);
    }

    /**
     * Обновление статуса комиссий при изменении статуса платежа
     *
     * @param int    $payment_id  ID платежа
     * @param string $old_status  Старый статус
     * @param string $new_status  Новый статус
     * @return void
     */
    public function update_commission_status($payment_id, $old_status, $new_status) {
        // Получаем все транзакции для этого платежа
        $transactions = $this->get_transactions_by_payment($payment_id);

        foreach ($transactions as $transaction) {
            $new_transaction_status = $this->map_payment_status_to_transaction_status($new_status);
            
            if ($transaction->get_status() !== $new_transaction_status) {
                $this->update_transaction_status(
                    $transaction->getAttribute('id'), 
                    $new_transaction_status,
                    "Статус изменен в связи с изменением статуса платежа: {$old_status} -> {$new_status}"
                );
            }
        }
    }

    /**
     * Получение транзакций по ID платежа
     *
     * @param int $payment_id ID платежа
     * @return array
     */
    private function get_transactions_by_payment($payment_id) {
        return $this->transaction_repository->get_by_payment($payment_id);
    }

    /**
     * Обновление статуса транзакции
     *
     * @param int    $transaction_id ID транзакции
     * @param string $status         Новый статус
     * @param string $comment        Комментарий
     * @return void
     */
    private function update_transaction_status($transaction_id, $status, $comment = '') {
        $this->transaction_repository->update_status($transaction_id, $status, $comment);
    }

    /**
     * Маппинг статуса платежа в статус транзакции
     *
     * @param string $payment_status Статус платежа
     * @return string
     */
    private function map_payment_status_to_transaction_status($payment_status) {
        switch ($payment_status) {
            case 'completed':
            case 'paid':
                return 'completed';
            case 'pending':
            case 'processing':
                return 'pending';
            case 'cancelled':
            case 'refunded':
                return 'cancelled';
            case 'failed':
                return 'failed';
            default:
                return 'pending';
        }
    }

    /**
     * Получение реферальных данных из сессии
     *
     * @return array|null
     */
    private function get_referral_from_session() {
        if (!session_id()) {
            session_start();
        }

        if (!isset($_SESSION['cryptoschool_referral'])) {
            return null;
        }

        $referral_data = $_SESSION['cryptoschool_referral'];

        // Проверяем, не истек ли срок действия (30 дней)
        if ((time() - $referral_data['time']) > (30 * 24 * 60 * 60)) {
            unset($_SESSION['cryptoschool_referral']);
            return null;
        }

        return $referral_data;
    }

    /**
     * Очистка реферальных данных из сессии
     *
     * @return void
     */
    private function clear_referral_from_session() {
        if (!session_id()) {
            session_start();
        }

        unset($_SESSION['cryptoschool_referral']);
    }

    /**
     * Получение статистики комиссий пользователя
     *
     * @param int $user_id ID пользователя
     * @return array
     */
    public function get_user_commission_stats($user_id) {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT 
                referral_level,
                COUNT(*) as transactions_count,
                SUM(amount) as total_amount,
                SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as completed_amount,
                SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount
             FROM {$wpdb->prefix}cryptoschool_referral_transactions 
             WHERE referrer_id = %d 
             GROUP BY referral_level",
            (int) $user_id
        );

        $results = $wpdb->get_results($query, ARRAY_A);

        $stats = [
            'level_1' => [
                'transactions_count' => 0,
                'total_amount' => 0.0,
                'completed_amount' => 0.0,
                'pending_amount' => 0.0
            ],
            'level_2' => [
                'transactions_count' => 0,
                'total_amount' => 0.0,
                'completed_amount' => 0.0,
                'pending_amount' => 0.0
            ]
        ];

        foreach ($results as $result) {
            $level_key = 'level_' . $result['referral_level'];
            if (isset($stats[$level_key])) {
                $stats[$level_key] = [
                    'transactions_count' => (int) $result['transactions_count'],
                    'total_amount' => (float) $result['total_amount'],
                    'completed_amount' => (float) $result['completed_amount'],
                    'pending_amount' => (float) $result['pending_amount']
                ];
            }
        }

        // Добавляем общую статистику
        $stats['total'] = [
            'transactions_count' => $stats['level_1']['transactions_count'] + $stats['level_2']['transactions_count'],
            'total_amount' => $stats['level_1']['total_amount'] + $stats['level_2']['total_amount'],
            'completed_amount' => $stats['level_1']['completed_amount'] + $stats['level_2']['completed_amount'],
            'pending_amount' => $stats['level_1']['pending_amount'] + $stats['level_2']['pending_amount']
        ];

        return $stats;
    }

    /**
     * Получение доступной для вывода суммы
     *
     * @param int $user_id ID пользователя
     * @return float
     */
    public function get_available_for_withdrawal($user_id) {
        global $wpdb;

        // Получаем сумму завершенных транзакций
        $completed_query = $wpdb->prepare(
            "SELECT SUM(amount) as total 
             FROM {$wpdb->prefix}cryptoschool_referral_transactions 
             WHERE referrer_id = %d AND status = 'completed'",
            (int) $user_id
        );

        $completed_amount = (float) $wpdb->get_var($completed_query);

        // Получаем сумму уже выведенных средств
        $withdrawn_query = $wpdb->prepare(
            "SELECT SUM(amount) as total 
             FROM {$wpdb->prefix}cryptoschool_withdrawal_requests 
             WHERE user_id = %d AND status IN ('approved', 'paid')",
            (int) $user_id
        );

        $withdrawn_amount = (float) $wpdb->get_var($withdrawn_query);

        return max(0, $completed_amount - $withdrawn_amount);
    }
}
