<?php
/**
 * Автозагрузчик классов плагина
 *
 * Обеспечивает автоматическую загрузку классов плагина
 *
 * @package CryptoSchool
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс автозагрузчика
 */
class CryptoSchool_Autoloader {
    /**
     * Экземпляр класса (Singleton)
     *
     * @var CryptoSchool_Autoloader
     */
    private static $instance = null;

    /**
     * Префикс пространства имен
     *
     * @var string
     */
    private $namespace_prefix = 'CryptoSchool_';

    /**
     * Базовая директория для автозагрузки
     *
     * @var string
     */
    private $base_dir;

    /**
     * Карта соответствия префиксов классов и директорий
     *
     * @var array
     */
    private $class_map = [
        'Model'      => 'models',
        'Repository' => 'repositories',
        'Service'    => 'services',
        'Admin'      => 'admin',
        'Public'     => 'public',
        'API'        => 'api',
        'Hook'       => 'hooks',
        'Helper'     => 'helpers',
    ];

    /**
     * Получение экземпляра класса
     *
     * @return CryptoSchool_Autoloader
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Конструктор класса
     */
    private function __construct() {
        $this->base_dir = CRYPTOSCHOOL_PLUGIN_DIR . 'includes/';
        $this->register();
    }

    /**
     * Регистрация автозагрузчика
     */
    public function register() {
        spl_autoload_register([$this, 'autoload']);
    }

    /**
     * Автозагрузка классов
     *
     * @param string $class_name Имя класса
     * @return void
     */
    public function autoload($class_name) {
        // Проверяем, принадлежит ли класс нашему плагину
        if (strpos($class_name, $this->namespace_prefix) !== 0) {
            return;
        }

        // Удаляем префикс пространства имен
        $class_name = substr($class_name, strlen($this->namespace_prefix));

        // Определяем директорию для поиска класса
        $directory = $this->get_class_directory($class_name);

        // Формируем путь к файлу
        $file_path = $this->base_dir . $directory . '/class-cryptoschool-' . $this->get_file_name($class_name) . '.php';

        // Проверяем существование файла и подключаем его
        if (file_exists($file_path)) {
            require_once $file_path;
        } else {
            // Если файл не найден в поддиректории, пробуем найти его в корне includes
            $file_path = $this->base_dir . 'class-cryptoschool-' . $this->get_file_name($class_name) . '.php';
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }

    /**
     * Определение директории для класса
     *
     * @param string $class_name Имя класса без префикса пространства имен
     * @return string
     */
    private function get_class_directory($class_name) {
        foreach ($this->class_map as $prefix => $directory) {
            if (strpos($class_name, $prefix) === 0) {
                return $directory;
            }
        }

        return '';
    }

    /**
     * Преобразование имени класса в имя файла
     *
     * @param string $class_name Имя класса без префикса пространства имен
     * @return string
     */
    private function get_file_name($class_name) {
        // Удаляем префикс типа класса (Model, Repository и т.д.)
        foreach ($this->class_map as $prefix => $directory) {
            if (strpos($class_name, $prefix) === 0) {
                $class_name = substr($class_name, strlen($prefix));
                break;
            }
        }

        // Преобразуем CamelCase в kebab-case
        $file_name = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $class_name));

        return $file_name;
    }
}

// Инициализация автозагрузчика
CryptoSchool_Autoloader::get_instance();
