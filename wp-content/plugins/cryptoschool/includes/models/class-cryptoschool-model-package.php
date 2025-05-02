<?php
/**
 * Модель пакета
 *
 * @package CryptoSchool
 * @subpackage Models
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс модели пакета
 */
class CryptoSchool_Model_Package extends CryptoSchool_Model {
    /**
     * Заполняемые атрибуты
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'description',
        'price',
        'discount_price',
        'package_type',
        'duration_months',
        'is_active',
        'creoin_points',
        'features',
        'course_ids',
    ];

    /**
     * Получение курсов, включенных в пакет
     *
     * @param array $args Дополнительные аргументы
     * @return array
     */
    public function get_courses($args = []) {
        $repository = new CryptoSchool_Repository_Package();
        return $repository->get_package_courses($this->id, $args);
    }

    /**
     * Получение количества курсов, включенных в пакет
     *
     * @param array $args Дополнительные аргументы
     * @return int
     */
    public function get_courses_count($args = []) {
        $courses = $this->get_courses($args);
        return count($courses);
    }

    /**
     * Получение массива ID курсов
     *
     * @return array
     */
    public function get_course_ids() {
        if (empty($this->course_ids)) {
            return [];
        }

        return explode(',', $this->course_ids);
    }

    /**
     * Добавление курса в пакет
     *
     * @param int $course_id ID курса
     * @return bool
     */
    public function add_course($course_id) {
        $repository = new CryptoSchool_Repository_Package();
        return $repository->add_course_to_package($this->id, $course_id);
    }

    /**
     * Удаление курса из пакета
     *
     * @param int $course_id ID курса
     * @return bool
     */
    public function remove_course($course_id) {
        $repository = new CryptoSchool_Repository_Package();
        return $repository->remove_course_from_package($this->id, $course_id);
    }

    /**
     * Обновление курсов в пакете
     *
     * @param array $course_ids Массив ID курсов
     * @return bool
     */
    public function update_courses($course_ids) {
        $repository = new CryptoSchool_Repository_Package();
        return $repository->update_package_courses($this->id, $course_ids);
    }

    /**
     * Получение типа пакета
     *
     * @return string
     */
    public function get_package_type_label() {
        $types = [
            'course' => __('Только обучение', 'cryptoschool'),
            'community' => __('Только приватка', 'cryptoschool'),
            'combined' => __('Обучение + приватка', 'cryptoschool'),
        ];

        return isset($types[$this->package_type]) ? $types[$this->package_type] : $this->package_type;
    }

    /**
     * Получение статуса пакета
     *
     * @return string
     */
    public function get_status_label() {
        return $this->is_active ? __('Активен', 'cryptoschool') : __('Неактивен', 'cryptoschool');
    }

    /**
     * Получение форматированной цены пакета
     *
     * @param string $currency Валюта
     * @return string
     */
    public function get_price_formatted($currency = 'USD') {
        $price = $this->price;
        $currency_symbol = $this->get_currency_symbol($currency);

        return $currency_symbol . number_format($price, 2, '.', ' ');
    }

    /**
     * Получение форматированной скидочной цены пакета
     *
     * @param string $currency Валюта
     * @return string
     */
    public function get_discount_price_formatted($currency = 'USD') {
        if (empty($this->discount_price)) {
            return '';
        }

        $price = $this->discount_price;
        $currency_symbol = $this->get_currency_symbol($currency);

        return $currency_symbol . number_format($price, 2, '.', ' ');
    }

    /**
     * Получение процента скидки
     *
     * @return int
     */
    public function get_discount_percent() {
        if (empty($this->discount_price) || $this->price <= 0) {
            return 0;
        }

        $discount = $this->price - $this->discount_price;
        $percent = ($discount / $this->price) * 100;

        return round($percent);
    }

    /**
     * Получение символа валюты
     *
     * @param string $currency Валюта
     * @return string
     */
    private function get_currency_symbol($currency) {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'UAH' => '₴',
            'RUB' => '₽',
        ];

        return isset($symbols[$currency]) ? $symbols[$currency] : $currency;
    }

    /**
     * Получение форматированного срока действия пакета
     *
     * @return string
     */
    public function get_duration_formatted() {
        if (empty($this->duration_months)) {
            return __('Бессрочно', 'cryptoschool');
        }

        return sprintf(
            _n('%d месяц', '%d месяцев', $this->duration_months, 'cryptoschool'),
            $this->duration_months
        );
    }

    /**
     * Получение особенностей пакета
     *
     * @return array
     */
    public function get_features() {
        if (empty($this->features)) {
            return [];
        }

        return json_decode($this->features, true);
    }

    /**
     * Получение форматированной даты создания пакета
     *
     * @param string $format Формат даты
     * @return string
     */
    public function get_created_at($format = 'd.m.Y') {
        return date_i18n($format, strtotime($this->created_at));
    }

    /**
     * Получение форматированной даты обновления пакета
     *
     * @param string $format Формат даты
     * @return string
     */
    public function get_updated_at($format = 'd.m.Y') {
        return date_i18n($format, strtotime($this->updated_at));
    }

    /**
     * Проверка, включает ли пакет обучение
     *
     * @return bool
     */
    public function includes_course() {
        return $this->package_type === 'course' || $this->package_type === 'combined';
    }

    /**
     * Проверка, включает ли пакет доступ к приватным группам
     *
     * @return bool
     */
    public function includes_community() {
        return $this->package_type === 'community' || $this->package_type === 'combined';
    }

    /**
     * Получение количества пользователей, купивших пакет
     *
     * @return int
     */
    public function get_users_count() {
        global $wpdb;
        $access_table = $wpdb->prefix . 'cryptoschool_user_access';

        $query = $wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM {$access_table} WHERE package_id = %d",
            $this->id
        );

        return (int) $wpdb->get_var($query);
    }

    /**
     * Получение количества активных пользователей пакета
     *
     * @return int
     */
    public function get_active_users_count() {
        global $wpdb;
        $access_table = $wpdb->prefix . 'cryptoschool_user_access';

        $query = $wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM {$access_table} WHERE package_id = %d AND status = 'active'",
            $this->id
        );

        return (int) $wpdb->get_var($query);
    }
}
