<?php
/**
 * Отладочный скрипт для проверки ролей в WordPress
 */

// Загрузка WordPress
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';

// Проверка прав доступа
if (!current_user_can('administrator')) {
    wp_die('Доступ запрещен');
}

echo '<h1>Роли в WordPress</h1>';

// Получение всех ролей из базы данных
$roles_option = get_option('wp_user_roles');
echo '<h2>Роли из опции wp_user_roles:</h2>';
echo '<pre>';
print_r($roles_option);
echo '</pre>';

// Получение всех ролей через WP_Roles
$wp_roles = wp_roles();
echo '<h2>Роли через WP_Roles:</h2>';
echo '<pre>';
print_r($wp_roles);
echo '</pre>';

// Проверка, где создается роль "cryptoschool_student"
echo '<h2>Поиск создания роли "cryptoschool_student" в коде:</h2>';

// Функция для рекурсивного поиска в файлах
function search_in_files($dir, $search_string) {
    $results = array();
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            $sub_results = search_in_files($path, $search_string);
            $results = array_merge($results, $sub_results);
        } else {
            // Проверяем только PHP-файлы
            if (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                $content = file_get_contents($path);
                if (strpos($content, $search_string) !== false) {
                    $results[] = $path;
                }
            }
        }
    }
    
    return $results;
}

// Поиск в директории плагина
$plugin_dir = WP_PLUGIN_DIR . '/cryptoschool';
$search_results = search_in_files($plugin_dir, 'add_role');

echo '<h3>Файлы, содержащие add_role:</h3>';
echo '<ul>';
foreach ($search_results as $file) {
    echo '<li>' . $file . '</li>';
}
echo '</ul>';

// Поиск в директории темы
$theme_dir = get_template_directory();
$search_results = search_in_files($theme_dir, 'add_role');

echo '<h3>Файлы темы, содержащие add_role:</h3>';
echo '<ul>';
foreach ($search_results as $file) {
    echo '<li>' . $file . '</li>';
}
echo '</ul>';

// Получение всех пользователей и их ролей
$users = get_users();
echo '<h2>Пользователи и их роли:</h2>';
echo '<table border="1" cellpadding="5">';
echo '<tr><th>ID</th><th>Имя</th><th>Email</th><th>Роли</th></tr>';
foreach ($users as $user) {
    echo '<tr>';
    echo '<td>' . $user->ID . '</td>';
    echo '<td>' . $user->display_name . '</td>';
    echo '<td>' . $user->user_email . '</td>';
    echo '<td>' . implode(', ', $user->roles) . '</td>';
    echo '</tr>';
}
echo '</table>';
