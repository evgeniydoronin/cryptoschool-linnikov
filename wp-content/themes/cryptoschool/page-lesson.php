<?php
/**
 * Template Name: Урок
 *
 * @package CryptoSchool
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

// Инициализируем контроллер урока
$controller = new CryptoSchool_Lesson_Controller();

// Подготавливаем данные для страницы урока
// Контроллер сам выполнит все проверки доступа и обработку форм
$lesson_data = $controller->prepare_lesson_page();

// Подключаем header
get_header();

// Выводим основной шаблон урока с подготовленными данными
get_template_part('template-parts/lesson/lesson-main', null, $lesson_data);

// Подключаем footer
get_footer();

