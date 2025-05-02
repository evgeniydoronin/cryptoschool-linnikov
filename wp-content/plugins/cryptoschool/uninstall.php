<?php
/**
 * Удаление плагина
 *
 * Выполняет действия при удалении плагина
 *
 * @package CryptoSchool
 */

// Если файл вызван напрямую или не через WordPress, прерываем выполнение
if (!defined('WP_UNINSTALL_PLUGIN') || !WP_UNINSTALL_PLUGIN) {
    exit;
}

// Определение констант плагина
define('CRYPTOSCHOOL_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Подключение класса деактиватора
require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/class-cryptoschool-deactivator.php';

// Удаление таблиц и данных плагина
CryptoSchool_Deactivator::uninstall();
