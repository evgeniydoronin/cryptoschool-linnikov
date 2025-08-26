<?php
// Загружаем WordPress
require_once('wp-load.php');

echo "=== Тест WPML курсов ===\n\n";

// ID которые проверяем
$uk_course_id = 103;  // Украинская версия
$ru_course_id = 41;   // Русская версия

echo "Проверяем курс UK ID: $uk_course_id\n";
$uk_course = get_post($uk_course_id);
if ($uk_course) {
    echo "- Post Title: " . $uk_course->post_title . "\n";
    echo "- Post Type: " . $uk_course->post_type . "\n";
    $uk_table_id = get_post_meta($uk_course_id, '_cryptoschool_table_id', true);
    echo "- _cryptoschool_table_id: " . ($uk_table_id ?: 'NOT SET') . "\n";
}

echo "\nПроверяем курс RU ID: $ru_course_id\n";
$ru_course = get_post($ru_course_id);
if ($ru_course) {
    echo "- Post Title: " . $ru_course->post_title . "\n";
    echo "- Post Type: " . $ru_course->post_type . "\n";
    $ru_table_id = get_post_meta($ru_course_id, '_cryptoschool_table_id', true);
    echo "- _cryptoschool_table_id: " . ($ru_table_id ?: 'NOT SET') . "\n";
}

// Проверяем WPML связь
echo "\n=== WPML связь ===\n";
$original_uk = apply_filters('wpml_object_id', $uk_course_id, 'cryptoschool_course', false, apply_filters('wpml_default_language', null));
echo "Оригинальный курс для UK ($uk_course_id): " . ($original_uk ?: 'NOT FOUND') . "\n";

$original_ru = apply_filters('wpml_object_id', $ru_course_id, 'cryptoschool_course', false, apply_filters('wpml_default_language', null));
echo "Оригинальный курс для RU ($ru_course_id): " . ($original_ru ?: 'NOT FOUND') . "\n";

// Проверяем trid
global $wpdb;
$uk_trid = $wpdb->get_var($wpdb->prepare(
    "SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id = %d AND element_type = %s",
    $uk_course_id, 'post_cryptoschool_course'
));
echo "\nTRID для UK курса: " . ($uk_trid ?: 'NOT FOUND') . "\n";

$ru_trid = $wpdb->get_var($wpdb->prepare(
    "SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id = %d AND element_type = %s",
    $ru_course_id, 'post_cryptoschool_course'
));
echo "TRID для RU курса: " . ($ru_trid ?: 'NOT FOUND') . "\n";

// Проверяем пакеты
echo "\n=== Проверка пакетов ===\n";
$user_id = 1; // Администратор
$packages_query = "
    SELECT p.id, p.name, p.course_ids 
    FROM {$wpdb->prefix}cryptoschool_packages p
    JOIN {$wpdb->prefix}cryptoschool_user_access ua ON ua.package_id = p.id
    WHERE ua.user_id = %d AND ua.status = 'active'
";
$packages = $wpdb->get_results($wpdb->prepare($packages_query, $user_id));

foreach ($packages as $package) {
    echo "\nПакет: " . $package->name . " (ID: " . $package->id . ")\n";
    echo "Course IDs в пакете: " . $package->course_ids . "\n";
    
    $course_ids = json_decode($package->course_ids, true);
    if (is_array($course_ids)) {
        echo "Распарсенные IDs: " . implode(', ', $course_ids) . "\n";
    }
}

// Тестируем новую функцию получения языковых версий
echo "\n=== Тест языковых версий ===\n";
$uk_versions = cryptoschool_get_all_course_language_versions($uk_course_id);
$ru_versions = cryptoschool_get_all_course_language_versions($ru_course_id);

echo "Все языковые версии UK курса ($uk_course_id): " . implode(', ', $uk_versions) . "\n";
echo "Все языковые версии RU курса ($ru_course_id): " . implode(', ', $ru_versions) . "\n";

// Создаем тестовый пакет для проверки
echo "\n=== Создание тестового пакета ===\n";
$test_package = $wpdb->insert(
    $wpdb->prefix . 'cryptoschool_packages',
    [
        'title' => 'Тестовый пакет',
        'description' => 'Для тестирования',
        'price' => 0,
        'course_ids' => json_encode([$uk_course_id]), // Сохраняем только украинский курс
        'is_active' => 1,
        'created_at' => current_time('mysql')
    ],
    ['%s', '%s', '%f', '%s', '%d', '%s']
);

if ($test_package) {
    $package_id = $wpdb->insert_id;
    echo "Создан тестовый пакет ID: $package_id с курсом $uk_course_id\n";
    
    // Создаем доступ пользователя к пакету
    $test_access = $wpdb->insert(
        $wpdb->prefix . 'cryptoschool_user_access',
        [
            'user_id' => $user_id,
            'package_id' => $package_id,
            'status' => 'active',
            'start_date' => current_time('mysql'),
            'created_at' => current_time('mysql')
        ],
        ['%d', '%d', '%s', '%s', '%s']
    );
    
    if ($test_access) {
        echo "Создан доступ пользователя $user_id к пакету $package_id\n";
    }
}

// Тестируем новую функцию проверки доступа
echo "\n=== Тест новой проверки доступа ===\n";
function test_new_course_access($user_id, $course_id) {
    global $wpdb;
    
    // Получаем все языковые версии курса для проверки доступа
    $all_course_versions = cryptoschool_get_all_course_language_versions($course_id);
    echo "Проверяем доступ для курса $course_id, все версии: " . implode(', ', $all_course_versions) . "\n";
    
    // Проверяем доступ для каждой версии курса
    foreach ($all_course_versions as $version_id) {
        // Получаем table_id для версии курса
        $table_id = get_post_meta($version_id, '_cryptoschool_table_id', true);
        if (!$table_id) {
            $table_id = $version_id; // Fallback к Post ID
        }
        
        echo "  Проверяем версию $version_id (table_id: $table_id)\n";
        
        $access_query = "
            SELECT COUNT(*) as has_access
            FROM {$wpdb->prefix}cryptoschool_user_access ua
            JOIN {$wpdb->prefix}cryptoschool_packages p ON ua.package_id = p.id
            WHERE ua.user_id = %d 
            AND ua.status = 'active'
            AND JSON_CONTAINS(p.course_ids, %s)
        ";
        
        $result = $wpdb->get_var($wpdb->prepare($access_query, $user_id, '"' . $table_id . '"'));
        echo "    Результат запроса: $result\n";
        
        if (intval($result) > 0) {
            echo "    Доступ найден для версии $version_id!\n";
            return true;
        }
    }
    
    return false;
}

echo "\nДоступ к UK курсу (ID: $uk_course_id): " . (test_new_course_access($user_id, $uk_course_id) ? 'YES' : 'NO') . "\n";
echo "Доступ к RU курсу (ID: $ru_course_id): " . (test_new_course_access($user_id, $ru_course_id) ? 'YES' : 'NO') . "\n";

// Очистка тестовых данных
if ($test_package) {
    $wpdb->delete($wpdb->prefix . 'cryptoschool_user_access', ['user_id' => $user_id, 'package_id' => $package_id], ['%d', '%d']);
    $wpdb->delete($wpdb->prefix . 'cryptoschool_packages', ['id' => $package_id], ['%d']);
    echo "\nТестовые данные очищены\n";
}