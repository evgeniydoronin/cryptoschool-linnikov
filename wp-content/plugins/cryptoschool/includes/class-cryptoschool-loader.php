<?php
/**
 * Класс загрузчика плагина
 *
 * Отвечает за регистрацию всех хуков и фильтров плагина
 *
 * @package CryptoSchool
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс загрузчика плагина
 */
class CryptoSchool_Loader {
    /**
     * Массив действий, зарегистрированных в WordPress
     *
     * @var array
     */
    protected $actions;

    /**
     * Массив фильтров, зарегистрированных в WordPress
     *
     * @var array
     */
    protected $filters;

    /**
     * Массив шорткодов, зарегистрированных в WordPress
     *
     * @var array
     */
    protected $shortcodes;

    /**
     * Инициализация коллекций, используемых для хранения хуков и фильтров
     */
    public function __construct() {
        $this->actions = array();
        $this->filters = array();
        $this->shortcodes = array();
    }

    /**
     * Добавление нового действия в коллекцию для регистрации в WordPress
     *
     * @param string $hook          Имя хука WordPress, к которому привязывается функция
     * @param object $component     Экземпляр объекта, в котором определена функция
     * @param string $callback      Имя функции в $component
     * @param int    $priority      Приоритет, по умолчанию 10
     * @param int    $accepted_args Количество аргументов, которые принимает функция, по умолчанию 1
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Добавление нового фильтра в коллекцию для регистрации в WordPress
     *
     * @param string $hook          Имя хука WordPress, к которому привязывается функция
     * @param object $component     Экземпляр объекта, в котором определена функция
     * @param string $callback      Имя функции в $component
     * @param int    $priority      Приоритет, по умолчанию 10
     * @param int    $accepted_args Количество аргументов, которые принимает функция, по умолчанию 1
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Добавление нового шорткода в коллекцию для регистрации в WordPress
     *
     * @param string $tag      Тег шорткода
     * @param object $component Экземпляр объекта, в котором определена функция
     * @param string $callback  Имя функции в $component
     */
    public function add_shortcode($tag, $component, $callback) {
        $this->shortcodes = $this->add_shortcode_internal($this->shortcodes, $tag, $component, $callback);
    }

    /**
     * Вспомогательный метод для добавления хука в коллекцию
     *
     * @param array  $hooks         Коллекция хуков (действий или фильтров)
     * @param string $hook          Имя хука WordPress, к которому привязывается функция
     * @param object $component     Экземпляр объекта, в котором определена функция
     * @param string $callback      Имя функции в $component
     * @param int    $priority      Приоритет
     * @param int    $accepted_args Количество аргументов, которые принимает функция
     * @return array                Коллекция хуков
     */
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );

        return $hooks;
    }

    /**
     * Вспомогательный метод для добавления шорткода в коллекцию
     *
     * @param array  $shortcodes Коллекция шорткодов
     * @param string $tag        Тег шорткода
     * @param object $component  Экземпляр объекта, в котором определена функция
     * @param string $callback   Имя функции в $component
     * @return array             Коллекция шорткодов
     */
    private function add_shortcode_internal($shortcodes, $tag, $component, $callback) {
        $shortcodes[] = array(
            'tag'       => $tag,
            'component' => $component,
            'callback'  => $callback
        );

        return $shortcodes;
    }

    /**
     * Регистрация хуков и фильтров в WordPress
     */
    public function run() {
        // Регистрация действий
        foreach ($this->actions as $hook) {
            add_action(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }

        // Регистрация фильтров
        foreach ($this->filters as $hook) {
            add_filter(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }

        // Регистрация шорткодов
        foreach ($this->shortcodes as $shortcode) {
            add_shortcode(
                $shortcode['tag'],
                array($shortcode['component'], $shortcode['callback'])
            );
        }
    }
}
