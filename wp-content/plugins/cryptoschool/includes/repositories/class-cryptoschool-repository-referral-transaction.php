<?php
/**
 * Репозиторий для работы с реферальными транзакциями
 *
 * @package CryptoSchool
 * @subpackage Repositories
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс репозитория реферальных транзакций
 */
class CryptoSchool_Repository_Referral_Transaction extends CryptoSchool_Repository {

    /**
     * Название таблицы
     *
     * @var string
     */
    protected $table_name = 'cryptoschool_referral_transactions';

    /**
     * Класс модели
     *
     * @var string
     */
    protected $model_class = 'CryptoSchool_Model_Referral_Transaction';

    /**
     * Создание новой реферальной транзакции
     *
     * @param array $data Данные транзакции
     * @return CryptoSchool_Model_Referral_Transaction|false
     */
    public function create($data) {
        // Подготавливаем данные
        $prepared_data = [
            'referrer_id'        => (int) $data['referrer_id'],
            'user_id'            => (int) $data['user_id'],
            'payment_id'         => (int) $data['payment_id'],
            'referral_link_id'   => (int) $data['referral_link_id'],
            'referral_level'     => (int) $data['referral_level'],
            'amount'             => (float) $data['amount'],
            'commission_percent' => (float) $data['commission_percent'],
            'status'             => sanitize_text_field($data['status'] ?? 'pending'),
            'comment'            => sanitize_textarea_field($data['comment'] ?? ''),
            'created_at'         => current_time('mysql'),
            'updated_at'         => current_time('mysql')
        ];

        // Используем родительский метод create
        return parent::create($prepared_data);
    }

    /**
     * Получение транзакций по ID рефовода
     *
     * @param int $referrer_id ID рефовода
     * @param int $level       Уровень реферала (опционально)
     * @return array
     */
    public function get_by_referrer($referrer_id, $level = null) {
        $conditions = ['referrer_id' => (int) $referrer_id];
        
        if ($level !== null) {
            $conditions['referral_level'] = (int) $level;
        }
        
        return $this->where($conditions);
    }

    /**
     * Получение транзакций по ID платежа
     *
     * @param int $payment_id ID платежа
     * @return array
     */
    public function get_by_payment($payment_id) {
        return $this->where(['payment_id' => (int) $payment_id]);
    }

    /**
     * Получение транзакций по ID реферальной ссылки
     *
     * @param int $link_id ID реферальной ссылки
     * @return array
     */
    public function get_by_link($link_id) {
        return $this->where(['referral_link_id' => (int) $link_id]);
    }

    /**
     * Получение транзакций по статусу
     *
     * @param string $status Статус транзакции
     * @return array
     */
    public function get_by_status($status) {
        return $this->where(['status' => sanitize_text_field($status)]);
    }

    /**
     * Получение завершенных транзакций рефовода
     *
     * @param int $referrer_id ID рефовода
     * @return array
     */
    public function get_completed_by_referrer($referrer_id) {
        return $this->where([
            'referrer_id' => (int) $referrer_id,
            'status' => 'completed'
        ]);
    }

    /**
     * Обновление статуса транзакции
     *
     * @param int    $transaction_id ID транзакции
     * @param string $status         Новый статус
     * @param string $comment        Комментарий (опционально)
     * @return bool
     */
    public function update_status($transaction_id, $status, $comment = '') {
        $data = [
            'status' => sanitize_text_field($status),
            'updated_at' => current_time('mysql')
        ];

        if (!empty($comment)) {
            $data['comment'] = sanitize_textarea_field($comment);
        }

        return $this->update($transaction_id, $data);
    }

    /**
     * Проверка существования транзакции для платежа
     *
     * @param int $payment_id     ID платежа
     * @param int $referrer_id    ID рефовода
     * @param int $referral_level Уровень реферала
     * @return bool
     */
    public function transaction_exists($payment_id, $referrer_id, $referral_level) {
        $transactions = $this->where([
            'payment_id' => (int) $payment_id,
            'referrer_id' => (int) $referrer_id,
            'referral_level' => (int) $referral_level
        ]);

        return count($transactions) > 0;
    }

    /**
     * Получение общей суммы завершенных транзакций рефовода
     *
     * @param int $referrer_id ID рефовода
     * @return float
     */
    public function get_total_completed_amount($referrer_id) {
        $transactions = $this->get_completed_by_referrer($referrer_id);
        $total = 0.0;

        foreach ($transactions as $transaction) {
            $total += $transaction->get_amount();
        }

        return $total;
    }

    /**
     * Получение количества транзакций по уровням
     *
     * @param int $referrer_id ID рефовода
     * @return array
     */
    public function get_level_counts($referrer_id) {
        $level1_transactions = $this->get_by_referrer($referrer_id, 1);
        $level2_transactions = $this->get_by_referrer($referrer_id, 2);

        return [
            'level_1' => count($level1_transactions),
            'level_2' => count($level2_transactions),
            'total' => count($level1_transactions) + count($level2_transactions)
        ];
    }

    /**
     * Получение суммы заработка по уровням
     *
     * @param int $referrer_id ID рефовода
     * @return array
     */
    public function get_level_amounts($referrer_id) {
        $level1_transactions = $this->where([
            'referrer_id' => $referrer_id,
            'referral_level' => 1,
            'status' => 'completed'
        ]);

        $level2_transactions = $this->where([
            'referrer_id' => $referrer_id,
            'referral_level' => 2,
            'status' => 'completed'
        ]);

        $level1_amount = 0.0;
        foreach ($level1_transactions as $transaction) {
            $level1_amount += $transaction->get_amount();
        }

        $level2_amount = 0.0;
        foreach ($level2_transactions as $transaction) {
            $level2_amount += $transaction->get_amount();
        }

        return [
            'level_1' => $level1_amount,
            'level_2' => $level2_amount,
            'total' => $level1_amount + $level2_amount
        ];
    }

    /**
     * Получение последних транзакций рефовода
     *
     * @param int $referrer_id ID рефовода
     * @param int $limit       Количество записей
     * @return array
     */
    public function get_recent_transactions($referrer_id, $limit = 10) {
        // Используем базовый метод для получения всех транзакций рефовода
        $all_transactions = $this->get_by_referrer($referrer_id);

        // Сортируем по дате создания (новые первыми) и ограничиваем количество
        usort($all_transactions, function($a, $b) {
            return strtotime($b->getAttribute('created_at')) - strtotime($a->getAttribute('created_at'));
        });

        return array_slice($all_transactions, 0, $limit);
    }

    /**
     * Получение статистики для отображения
     *
     * @param int $referrer_id ID рефовода
     * @return array
     */
    public function get_display_stats($referrer_id) {
        $amounts = $this->get_level_amounts($referrer_id);
        $counts = $this->get_level_counts($referrer_id);

        return [
            'level_1' => [
                'transactions_count' => $counts['level_1'],
                'total_amount' => $amounts['level_1'],
                'completed_amount' => $amounts['level_1'], // Уже фильтруем по completed
                'pending_amount' => 0.0 // Можно добавить отдельный расчет если нужно
            ],
            'level_2' => [
                'transactions_count' => $counts['level_2'],
                'total_amount' => $amounts['level_2'],
                'completed_amount' => $amounts['level_2'],
                'pending_amount' => 0.0
            ],
            'total' => [
                'transactions_count' => $counts['total'],
                'total_amount' => $amounts['total'],
                'completed_amount' => $amounts['total'],
                'pending_amount' => 0.0
            ]
        ];
    }
}
