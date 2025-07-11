<?php
/**
 * Модель реферальной ссылки
 *
 * @package CryptoSchool
 * @subpackage Models
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс модели реферальной ссылки
 */
class CryptoSchool_Model_Referral_Link extends CryptoSchool_Model {
    /**
     * Заполняемые атрибуты
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'user_id',
        'referral_code',
        'link_name',
        'link_description',
        'discount_percent',
        'commission_percent',
        'clicks_count',
        'conversions_count',
        'total_earned',
        'is_active',
        'created_at',
        'updated_at'
    ];

    /**
     * Получение ID пользователя-владельца ссылки
     *
     * @return int
     */
    public function get_user_id() {
        return (int) $this->getAttribute('user_id');
    }

    /**
     * Получение реферального кода
     *
     * @return string
     */
    public function get_referral_code() {
        return $this->getAttribute('referral_code');
    }

    /**
     * Получение названия ссылки
     *
     * @return string
     */
    public function get_link_name() {
        return $this->getAttribute('link_name') ?: 'Реферальная ссылка';
    }

    /**
     * Получение описания ссылки
     *
     * @return string
     */
    public function get_link_description() {
        return $this->getAttribute('link_description') ?: '';
    }

    /**
     * Получение процента скидки для реферала
     *
     * @return float
     */
    public function get_discount_percent() {
        return (float) $this->getAttribute('discount_percent');
    }

    /**
     * Получение процента комиссии для рефовода
     *
     * @return float
     */
    public function get_commission_percent() {
        return (float) $this->getAttribute('commission_percent');
    }

    /**
     * Получение количества переходов по ссылке
     *
     * @return int
     */
    public function get_clicks_count() {
        return (int) $this->getAttribute('clicks_count');
    }

    /**
     * Получение количества конверсий
     *
     * @return int
     */
    public function get_conversions_count() {
        return (int) $this->getAttribute('conversions_count');
    }

    /**
     * Получение общей суммы заработка по ссылке
     *
     * @return float
     */
    public function get_total_earned() {
        return (float) $this->getAttribute('total_earned');
    }

    /**
     * Проверка, активна ли ссылка
     *
     * @return bool
     */
    public function is_active() {
        return (bool) $this->getAttribute('is_active');
    }

    /**
     * Получение полной реферальной ссылки
     *
     * @return string
     */
    public function get_full_url() {
        $base_url = home_url('/ref/');
        return $base_url . $this->get_referral_code();
    }

    /**
     * Получение конверсии в процентах
     *
     * @return float
     */
    public function get_conversion_rate() {
        $clicks = $this->get_clicks_count();
        if ($clicks === 0) {
            return 0.0;
        }
        
        $conversions = $this->get_conversions_count();
        return round(($conversions / $clicks) * 100, 2);
    }

    /**
     * Получение суммы процентов (скидка + комиссия)
     *
     * @return float
     */
    public function get_total_percent() {
        return $this->get_discount_percent() + $this->get_commission_percent();
    }

    /**
     * Проверка, не превышает ли сумма процентов максимально допустимую
     *
     * @param float $max_percent Максимально допустимый процент
     * @return bool
     */
    public function is_within_limit($max_percent = 40.0) {
        return $this->get_total_percent() <= $max_percent;
    }

    /**
     * Увеличение счетчика переходов
     *
     * @return void
     */
    public function increment_clicks() {
        $current_clicks = $this->get_clicks_count();
        $this->setAttribute('clicks_count', $current_clicks + 1);
    }

    /**
     * Увеличение счетчика конверсий
     *
     * @return void
     */
    public function increment_conversions() {
        $current_conversions = $this->get_conversions_count();
        $this->setAttribute('conversions_count', $current_conversions + 1);
    }

    /**
     * Добавление к общей сумме заработка
     *
     * @param float $amount Сумма для добавления
     * @return void
     */
    public function add_earnings($amount) {
        $current_earnings = $this->get_total_earned();
        $this->setAttribute('total_earned', $current_earnings + $amount);
    }

    /**
     * Активация ссылки
     *
     * @return void
     */
    public function activate() {
        $this->setAttribute('is_active', 1);
    }

    /**
     * Деактивация ссылки
     *
     * @return void
     */
    public function deactivate() {
        $this->setAttribute('is_active', 0);
    }

    /**
     * Получение отформатированной даты создания
     *
     * @return string
     */
    public function get_created_at() {
        $created_at = $this->getAttribute('created_at');
        if (!$created_at) {
            return '';
        }
        
        return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($created_at));
    }

    /**
     * Получение отформатированной даты обновления
     *
     * @return string
     */
    public function get_updated_at() {
        $updated_at = $this->getAttribute('updated_at');
        if (!$updated_at) {
            return '';
        }
        
        return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($updated_at));
    }

    /**
     * Валидация данных модели
     *
     * @return array Массив ошибок валидации
     */
    public function validate() {
        $errors = [];

        // Проверка обязательных полей
        if (empty($this->getAttribute('user_id'))) {
            $errors[] = 'ID пользователя обязательно для заполнения';
        }

        if (empty($this->getAttribute('referral_code'))) {
            $errors[] = 'Реферальный код обязателен для заполнения';
        }

        // Проверка процентов
        $discount = $this->get_discount_percent();
        $commission = $this->get_commission_percent();

        if ($discount < 0 || $discount > 40) {
            $errors[] = 'Процент скидки должен быть от 0 до 40%';
        }

        if ($commission < 0 || $commission > 50) {
            $errors[] = 'Процент комиссии должен быть от 0 до 50%';
        }

        // Для инфлюенсеров разрешаем до 50% комиссии (без скидки)
        if ($discount > 0 && $commission > 40) {
            $errors[] = 'При наличии скидки комиссия не может превышать 40%';
        } elseif ($discount == 0 && $commission > 50) {
            $errors[] = 'Комиссия без скидки не может превышать 50%';
        } elseif (($discount + $commission) > 50) {
            $errors[] = 'Сумма скидки и комиссии не может превышать 50%';
        }

        // Проверка счетчиков
        if ($this->get_clicks_count() < 0) {
            $errors[] = 'Количество переходов не может быть отрицательным';
        }

        if ($this->get_conversions_count() < 0) {
            $errors[] = 'Количество конверсий не может быть отрицательным';
        }

        if ($this->get_conversions_count() > $this->get_clicks_count()) {
            $errors[] = 'Количество конверсий не может превышать количество переходов';
        }

        return $errors;
    }

    /**
     * Проверка, валидна ли модель
     *
     * @return bool
     */
    public function is_valid() {
        return empty($this->validate());
    }
}
