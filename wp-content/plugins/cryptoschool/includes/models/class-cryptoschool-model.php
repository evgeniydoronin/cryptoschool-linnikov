<?php
/**
 * Базовый класс модели
 *
 * Предоставляет базовую функциональность для всех моделей плагина
 *
 * @package CryptoSchool
 * @subpackage Models
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Базовый класс модели
 */
abstract class CryptoSchool_Model {
    /**
     * Атрибуты модели
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Заполняемые атрибуты
     *
     * @var array
     */
    protected $fillable = [];

    /**
     * Защищенные атрибуты (не могут быть заполнены массово)
     *
     * @var array
     */
    protected $guarded = []; // Убираем 'id' из защищенных атрибутов

    /**
     * Конструктор класса
     *
     * @param array $attributes Атрибуты модели
     */
    public function __construct(array $attributes = []) {
        // Отладочный вывод
        error_log('Model constructor - Class: ' . get_class($this));
        error_log('Model constructor - Data: ' . json_encode($attributes));
        
        $this->fill($attributes);
        
        // Отладочный вывод
        error_log('Model constructor - Initialized properties: ' . json_encode(get_object_vars($this)));
    }

    /**
     * Заполнение модели атрибутами
     *
     * @param array $attributes Атрибуты для заполнения
     * @return CryptoSchool_Model
     */
    public function fill(array $attributes) {
        // Отладочный вывод
        error_log('Model fill - Class: ' . get_class($this));
        error_log('Model fill - Attributes: ' . json_encode($attributes));
        
        foreach ($attributes as $key => $value) {
            // Отладочный вывод
            error_log('Model fill - Processing key: ' . $key . ', value: ' . (is_array($value) ? json_encode($value) : $value));
            error_log('Model fill - Is fillable: ' . ($this->isFillable($key) ? 'yes' : 'no'));
            
            // Всегда заполнять ID, независимо от fillable
            if ($key === 'id' || $this->isFillable($key)) {
                $this->setAttribute($key, $value);
                error_log('Model fill - Set attribute: ' . $key . ' = ' . (is_array($value) ? json_encode($value) : $value));
            } else {
                error_log('Model fill - Skipped attribute: ' . $key);
            }
        }
        
        // Отладочный вывод
        error_log('Model fill - Final attributes: ' . json_encode($this->attributes));

        return $this;
    }

    /**
     * Проверка, может ли атрибут быть заполнен массово
     *
     * @param string $key Ключ атрибута
     * @return bool
     */
    protected function isFillable($key) {
        // Отладочный вывод
        error_log('Model isFillable - Key: ' . $key);
        error_log('Model isFillable - Fillable: ' . json_encode($this->fillable));
        error_log('Model isFillable - Guarded: ' . json_encode($this->guarded));
        
        // ID всегда должен быть заполняемым
        if ($key === 'id') {
            return true;
        }
        
        // Если fillable пуст, то все атрибуты, кроме guarded, могут быть заполнены
        if (empty($this->fillable)) {
            return !in_array($key, $this->guarded);
        }
        
        // Иначе только атрибуты из fillable могут быть заполнены
        return in_array($key, $this->fillable);
    }

    /**
     * Установка значения атрибута
     *
     * @param string $key   Ключ атрибута
     * @param mixed  $value Значение атрибута
     * @return void
     */
    public function setAttribute($key, $value) {
        // Отладочный вывод
        error_log('Model setAttribute - Key: ' . $key . ', Value: ' . (is_array($value) ? json_encode($value) : $value));
        
        $this->attributes[$key] = $value;
        
        // Отладочный вывод
        error_log('Model setAttribute - Attributes after set: ' . json_encode($this->attributes));
    }

    /**
     * Получение значения атрибута
     *
     * @param string $key Ключ атрибута
     * @return mixed
     */
    public function getAttribute($key) {
        // Отладочный вывод
        error_log('Model getAttribute - Key: ' . $key);
        error_log('Model getAttribute - Attributes: ' . json_encode($this->attributes));
        
        if (!isset($this->attributes[$key])) {
            error_log('Model getAttribute - Key not found: ' . $key);
            return null;
        }
        
        $value = $this->attributes[$key];
        
        error_log('Model getAttribute - Value: ' . (is_array($value) ? json_encode($value) : $value));
        
        return $value;
    }

    /**
     * Получение всех атрибутов модели
     *
     * @return array
     */
    public function getAttributes() {
        // Отладочный вывод
        error_log('Model getAttributes - Class: ' . get_class($this));
        error_log('Model getAttributes - Attributes: ' . json_encode($this->attributes));
        
        return $this->attributes;
    }

    /**
     * Магический метод для получения атрибутов
     *
     * @param string $key Ключ атрибута
     * @return mixed
     */
    public function __get($key) {
        return $this->getAttribute($key);
    }

    /**
     * Магический метод для установки атрибутов
     *
     * @param string $key   Ключ атрибута
     * @param mixed  $value Значение атрибута
     * @return void
     */
    public function __set($key, $value) {
        $this->setAttribute($key, $value);
    }

    /**
     * Магический метод для проверки существования атрибута
     *
     * @param string $key Ключ атрибута
     * @return bool
     */
    public function __isset($key) {
        return isset($this->attributes[$key]);
    }

    /**
     * Магический метод для удаления атрибута
     *
     * @param string $key Ключ атрибута
     * @return void
     */
    public function __unset($key) {
        unset($this->attributes[$key]);
    }

    /**
     * Преобразование модели в массив
     *
     * @return array
     */
    public function toArray() {
        return $this->attributes;
    }

    /**
     * Преобразование модели в JSON
     *
     * @param int $options Опции JSON
     * @return string
     */
    public function toJson($options = 0) {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Магический метод для преобразования модели в строку
     *
     * @return string
     */
    public function __toString() {
        return $this->toJson();
    }
}
