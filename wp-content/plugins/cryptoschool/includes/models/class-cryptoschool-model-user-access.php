<?php
/**
 * Модель доступа пользователя
 *
 * @package CryptoSchool
 * @subpackage Models
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс модели доступа пользователя
 */
class CryptoSchool_Model_UserAccess extends CryptoSchool_Model {
    /**
     * Заполняемые атрибуты
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'package_id',
        'access_start',
        'access_end',
        'status',
        'telegram_status',
        'telegram_invite_link',
        'telegram_invite_date',
    ];

    /**
     * Получение пользователя
     *
     * @return WP_User|false
     */
    public function get_user() {
        return get_user_by('id', $this->user_id);
    }

    /**
     * Получение пакета
     *
     * @return mixed
     */
    public function get_package() {
        $repository = new CryptoSchool_Repository_Package();
        return $repository->find($this->package_id);
    }

    /**
     * Проверка, истек ли доступ
     *
     * @return bool
     */
    public function is_expired() {
        if ($this->status === 'expired') {
            return true;
        }

        if (empty($this->access_end)) {
            return false;
        }

        return strtotime($this->access_end) < time();
    }

    /**
     * Получение оставшегося времени доступа
     *
     * @return int|null Количество секунд или null, если доступ бессрочный
     */
    public function get_remaining_time() {
        if ($this->status === 'expired' || empty($this->access_end)) {
            return null;
        }

        $remaining = strtotime($this->access_end) - time();
        return $remaining > 0 ? $remaining : 0;
    }

    /**
     * Получение форматированного оставшегося времени доступа
     *
     * @return string
     */
    public function get_remaining_time_formatted() {
        $remaining = $this->get_remaining_time();

        if ($remaining === null) {
            return __('Бессрочно', 'cryptoschool');
        }

        if ($remaining <= 0) {
            return __('Истек', 'cryptoschool');
        }

        $days = floor($remaining / 86400);
        $hours = floor(($remaining % 86400) / 3600);
        $minutes = floor(($remaining % 3600) / 60);

        if ($days > 0) {
            return sprintf(
                _n('%d день', '%d дней', $days, 'cryptoschool'),
                $days
            );
        } elseif ($hours > 0) {
            return sprintf(
                _n('%d час', '%d часов', $hours, 'cryptoschool'),
                $hours
            );
        } else {
            return sprintf(
                _n('%d минута', '%d минут', $minutes, 'cryptoschool'),
                $minutes
            );
        }
    }

    /**
     * Получение форматированной даты начала доступа
     *
     * @param string $format Формат даты
     * @return string
     */
    public function get_access_start_formatted($format = 'd.m.Y') {
        return date_i18n($format, strtotime($this->access_start));
    }

    /**
     * Получение форматированной даты окончания доступа
     *
     * @param string $format Формат даты
     * @return string
     */
    public function get_access_end_formatted($format = 'd.m.Y') {
        if (empty($this->access_end)) {
            return __('Бессрочно', 'cryptoschool');
        }

        return date_i18n($format, strtotime($this->access_end));
    }

    /**
     * Получение статуса доступа
     *
     * @return string
     */
    public function get_status_label() {
        $statuses = [
            'active' => __('Активен', 'cryptoschool'),
            'expired' => __('Истек', 'cryptoschool'),
        ];

        return isset($statuses[$this->status]) ? $statuses[$this->status] : $this->status;
    }

    /**
     * Получение статуса Telegram
     *
     * @return string
     */
    public function get_telegram_status_label() {
        $statuses = [
            'none' => __('Не приглашен', 'cryptoschool'),
            'invited' => __('Приглашен', 'cryptoschool'),
            'active' => __('Активен', 'cryptoschool'),
            'removed' => __('Удален', 'cryptoschool'),
        ];

        return isset($statuses[$this->telegram_status]) ? $statuses[$this->telegram_status] : $this->telegram_status;
    }

    /**
     * Получение форматированной даты создания доступа
     *
     * @param string $format Формат даты
     * @return string
     */
    public function get_created_at($format = 'd.m.Y') {
        return date_i18n($format, strtotime($this->created_at));
    }

    /**
     * Получение форматированной даты обновления доступа
     *
     * @param string $format Формат даты
     * @return string
     */
    public function get_updated_at($format = 'd.m.Y') {
        return date_i18n($format, strtotime($this->updated_at));
    }

    /**
     * Получение форматированной даты приглашения в Telegram
     *
     * @param string $format Формат даты
     * @return string
     */
    public function get_telegram_invite_date_formatted($format = 'd.m.Y') {
        if (empty($this->telegram_invite_date)) {
            return '';
        }

        return date_i18n($format, strtotime($this->telegram_invite_date));
    }

    /**
     * Продление доступа
     *
     * @param int $duration_months Продолжительность в месяцах
     * @return bool
     */
    public function extend($duration_months) {
        $repository = new CryptoSchool_Repository_UserAccess();
        return $repository->extend_user_access($this->id, $duration_months);
    }

    /**
     * Отмена доступа
     *
     * @return bool
     */
    public function cancel() {
        $repository = new CryptoSchool_Repository_UserAccess();
        return $repository->cancel_user_access($this->id);
    }

    /**
     * Обновление статуса Telegram
     *
     * @param string $telegram_status Статус Telegram
     * @param string $invite_link     Ссылка-приглашение
     * @return bool
     */
    public function update_telegram_status($telegram_status, $invite_link = null) {
        $repository = new CryptoSchool_Repository_UserAccess();
        return $repository->update_telegram_status($this->id, $telegram_status, $invite_link);
    }
}
