<?php
/**
 * Модель реферальной транзакции
 *
 * @package CryptoSchool
 * @subpackage Models
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс модели реферальной транзакции
 */
class CryptoSchool_Model_Referral_Transaction extends CryptoSchool_Model {

    /**
     * Название таблицы
     *
     * @var string
     */
    protected $table_name = 'cryptoschool_referral_transactions';

    /**
     * Поля модели
     *
     * @var array
     */
    protected $fillable = [
        'referrer_id',
        'user_id',
        'payment_id',
        'referral_link_id',
        'referral_level',
        'amount',
        'commission_percent',
        'status',
        'comment'
    ];

    /**
     * Поля с типом данных
     *
     * @var array
     */
    protected $casts = [
        'referrer_id' => 'int',
        'user_id' => 'int',
        'payment_id' => 'int',
        'referral_link_id' => 'int',
        'referral_level' => 'int',
        'amount' => 'float',
        'commission_percent' => 'float'
    ];

    /**
     * Получение ID рефовода
     *
     * @return int
     */
    public function get_referrer_id() {
        return $this->getAttribute('referrer_id');
    }

    /**
     * Установка ID рефовода
     *
     * @param int $referrer_id ID рефовода
     * @return void
     */
    public function set_referrer_id($referrer_id) {
        $this->setAttribute('referrer_id', (int) $referrer_id);
    }

    /**
     * Получение ID пользователя (покупателя)
     *
     * @return int
     */
    public function get_user_id() {
        return $this->getAttribute('user_id');
    }

    /**
     * Установка ID пользователя (покупателя)
     *
     * @param int $user_id ID пользователя
     * @return void
     */
    public function set_user_id($user_id) {
        $this->setAttribute('user_id', (int) $user_id);
    }

    /**
     * Получение ID платежа
     *
     * @return int
     */
    public function get_payment_id() {
        return $this->getAttribute('payment_id');
    }

    /**
     * Установка ID платежа
     *
     * @param int $payment_id ID платежа
     * @return void
     */
    public function set_payment_id($payment_id) {
        $this->setAttribute('payment_id', (int) $payment_id);
    }

    /**
     * Получение ID реферальной ссылки
     *
     * @return int
     */
    public function get_referral_link_id() {
        return $this->getAttribute('referral_link_id');
    }

    /**
     * Установка ID реферальной ссылки
     *
     * @param int $referral_link_id ID реферальной ссылки
     * @return void
     */
    public function set_referral_link_id($referral_link_id) {
        $this->setAttribute('referral_link_id', (int) $referral_link_id);
    }

    /**
     * Получение уровня реферала (1 или 2)
     *
     * @return int
     */
    public function get_referral_level() {
        return $this->getAttribute('referral_level');
    }

    /**
     * Установка уровня реферала
     *
     * @param int $level Уровень реферала (1 или 2)
     * @return void
     */
    public function set_referral_level($level) {
        $this->setAttribute('referral_level', (int) $level);
    }

    /**
     * Получение суммы комиссии
     *
     * @return float
     */
    public function get_amount() {
        return $this->getAttribute('amount');
    }

    /**
     * Установка суммы комиссии
     *
     * @param float $amount Сумма комиссии
     * @return void
     */
    public function set_amount($amount) {
        $this->setAttribute('amount', (float) $amount);
    }

    /**
     * Получение процента комиссии
     *
     * @return float
     */
    public function get_commission_percent() {
        return $this->getAttribute('commission_percent');
    }

    /**
     * Установка процента комиссии
     *
     * @param float $percent Процент комиссии
     * @return void
     */
    public function set_commission_percent($percent) {
        $this->setAttribute('commission_percent', (float) $percent);
    }

    /**
     * Получение статуса транзакции
     *
     * @return string
     */
    public function get_status() {
        return $this->getAttribute('status');
    }

    /**
     * Установка статуса транзакции
     *
     * @param string $status Статус транзакции
     * @return void
     */
    public function set_status($status) {
        $this->setAttribute('status', sanitize_text_field($status));
    }

    /**
     * Получение комментария
     *
     * @return string
     */
    public function get_comment() {
        return $this->getAttribute('comment');
    }

    /**
     * Установка комментария
     *
     * @param string $comment Комментарий
     * @return void
     */
    public function set_comment($comment) {
        $this->setAttribute('comment', sanitize_textarea_field($comment));
    }

    /**
     * Проверка, является ли транзакция первого уровня
     *
     * @return bool
     */
    public function is_first_level() {
        return $this->get_referral_level() === 1;
    }

    /**
     * Проверка, является ли транзакция второго уровня
     *
     * @return bool
     */
    public function is_second_level() {
        return $this->get_referral_level() === 2;
    }

    /**
     * Проверка, завершена ли транзакция
     *
     * @return bool
     */
    public function is_completed() {
        return $this->get_status() === 'completed';
    }

    /**
     * Проверка, ожидает ли транзакция обработки
     *
     * @return bool
     */
    public function is_pending() {
        return $this->get_status() === 'pending';
    }

    /**
     * Получение форматированной суммы
     *
     * @return string
     */
    public function get_formatted_amount() {
        return '$' . number_format($this->get_amount(), 2);
    }

    /**
     * Получение названия уровня
     *
     * @return string
     */
    public function get_level_name() {
        return $this->is_first_level() ? '1-й уровень' : '2-й уровень';
    }

    /**
     * Валидация данных модели
     *
     * @return array Массив ошибок валидации
     */
    public function validate() {
        $errors = [];

        // Проверка обязательных полей
        if (empty($this->get_referrer_id())) {
            $errors[] = 'ID рефовода обязателен';
        }

        if (empty($this->get_user_id())) {
            $errors[] = 'ID пользователя обязателен';
        }

        if (empty($this->get_payment_id())) {
            $errors[] = 'ID платежа обязателен';
        }

        if (empty($this->get_referral_link_id())) {
            $errors[] = 'ID реферальной ссылки обязателен';
        }

        // Проверка уровня реферала
        $level = $this->get_referral_level();
        if (!in_array($level, [1, 2])) {
            $errors[] = 'Уровень реферала должен быть 1 или 2';
        }

        // Проверка суммы
        if ($this->get_amount() < 0) {
            $errors[] = 'Сумма комиссии не может быть отрицательной';
        }

        // Проверка процента комиссии
        $percent = $this->get_commission_percent();
        if ($percent < 0 || $percent > 100) {
            $errors[] = 'Процент комиссии должен быть от 0 до 100';
        }

        // Проверка статуса
        $valid_statuses = ['pending', 'completed', 'cancelled', 'failed'];
        if (!in_array($this->get_status(), $valid_statuses)) {
            $errors[] = 'Недопустимый статус транзакции';
        }

        return $errors;
    }
}
