<?php
/**
 * Скрипт для анализа дубликатов в системе баллов
 * Выявляет множественные начисления за один и тот же урок
 */

// Подключение к WordPress
require_once('wp-load.php');

// Проверяем, запущен ли скрипт через браузер
$is_web_request = !empty($_SERVER['HTTP_HOST']);

if ($is_web_request) {
    echo "<pre style='background: #1e1e1e; color: #fff; padding: 20px; font-family: monospace; line-height: 1.5;'>";
}

echo "=== АНАЛИЗ ДУБЛИКАТОВ В СИСТЕМЕ БАЛЛОВ ===\n\n";

global $wpdb;

// Ищем дубликаты баллов за уроки
echo "🔍 === ПОИСК ДУБЛИКАТОВ ЗА УРОКИ ===\n";

$lesson_duplicates = $wpdb->get_results(
    "SELECT user_id, lesson_id, COUNT(*) as duplicate_count, 
            SUM(points) as total_points,
            GROUP_CONCAT(id ORDER BY created_at) as record_ids,
            MIN(created_at) as first_entry,
            MAX(created_at) as last_entry
     FROM {$wpdb->prefix}cryptoschool_points_history 
     WHERE points_type = 'lesson' AND lesson_id IS NOT NULL
     GROUP BY user_id, lesson_id 
     HAVING COUNT(*) > 1
     ORDER BY duplicate_count DESC, user_id, lesson_id"
);

if (empty($lesson_duplicates)) {
    echo "✅ Дубликатов баллов за уроки не найдено\n";
} else {
    echo "❌ Найдено " . count($lesson_duplicates) . " случаев дубликатов:\n\n";
    
    $total_excess_points = 0;
    $total_excess_records = 0;
    
    foreach ($lesson_duplicates as $duplicate) {
        $user_info = get_userdata($duplicate->user_id);
        $username = $user_info ? $user_info->user_login : "User ID {$duplicate->user_id}";
        
        // Определяем урок
        $lesson_post_id = $wpdb->get_var($wpdb->prepare(
            "SELECT element_id FROM {$wpdb->prefix}icl_translations 
             WHERE trid = %d AND element_type = %s AND language_code = %s",
            $duplicate->lesson_id, 'post_cryptoschool_lesson', apply_filters('wpml_current_language', null)
        ));
        
        if (!$lesson_post_id) {
            $lesson_post_id = $duplicate->lesson_id;
        }
        
        $lesson_post = get_post($lesson_post_id);
        $lesson_title = $lesson_post ? $lesson_post->post_title : "Урок ID {$duplicate->lesson_id}";
        
        $excess_records = $duplicate->duplicate_count - 1;
        $excess_points = $excess_records * 5; // Предполагаем 5 баллов за урок
        
        $total_excess_records += $excess_records;
        $total_excess_points += $excess_points;
        
        echo "🚨 Пользователь: $username\n";
        echo "   📚 Урок: $lesson_title (trid: {$duplicate->lesson_id})\n";
        echo "   🔢 Дубликатов: {$duplicate->duplicate_count} записей\n";
        echo "   💰 Общие баллы: {$duplicate->total_points}\n";
        echo "   ❌ Лишние записи: $excess_records\n";
        echo "   ❌ Лишние баллы: $excess_points\n";
        echo "   📅 Период: " . date('d.m.Y H:i', strtotime($duplicate->first_entry)) . 
             " - " . date('d.m.Y H:i', strtotime($duplicate->last_entry)) . "\n";
        echo "   🔗 ID записей: {$duplicate->record_ids}\n\n";
    }
    
    echo "📊 === ИТОГОВАЯ СТАТИСТИКА ===\n";
    echo "❌ Всего лишних записей: $total_excess_records\n";
    echo "❌ Всего лишних баллов: $total_excess_points\n\n";
}

// Ищем дубликаты баллов за завершение курсов
echo "🔍 === ПОИСК ДУБЛИКАТОВ ЗА КУРСЫ ===\n";

$course_duplicates = $wpdb->get_results(
    "SELECT user_id, description, COUNT(*) as duplicate_count,
            SUM(points) as total_points,
            GROUP_CONCAT(id ORDER BY created_at) as record_ids,
            MIN(created_at) as first_entry,
            MAX(created_at) as last_entry
     FROM {$wpdb->prefix}cryptoschool_points_history 
     WHERE points_type = 'course_completion'
     GROUP BY user_id, description
     HAVING COUNT(*) > 1
     ORDER BY duplicate_count DESC, user_id"
);

if (empty($course_duplicates)) {
    echo "✅ Дубликатов баллов за курсы не найдено\n";
} else {
    echo "❌ Найдено " . count($course_duplicates) . " случаев дубликатов курсов:\n\n";
    
    $course_excess_points = 0;
    $course_excess_records = 0;
    
    foreach ($course_duplicates as $duplicate) {
        $user_info = get_userdata($duplicate->user_id);
        $username = $user_info ? $user_info->user_login : "User ID {$duplicate->user_id}";
        
        $excess_records = $duplicate->duplicate_count - 1;
        $excess_points = $excess_records * 50; // Предполагаем 50 баллов за курс
        
        $course_excess_records += $excess_records;
        $course_excess_points += $excess_points;
        
        echo "🚨 Пользователь: $username\n";
        echo "   📚 Курс: {$duplicate->description}\n";
        echo "   🔢 Дубликатов: {$duplicate->duplicate_count} записей\n";
        echo "   💰 Общие баллы: {$duplicate->total_points}\n";
        echo "   ❌ Лишние записи: $excess_records\n";
        echo "   ❌ Лишние баллы: $excess_points\n";
        echo "   📅 Период: " . date('d.m.Y H:i', strtotime($duplicate->first_entry)) . 
             " - " . date('d.m.Y H:i', strtotime($duplicate->last_entry)) . "\n";
        echo "   🔗 ID записей: {$duplicate->record_ids}\n\n";
    }
    
    $total_excess_records += $course_excess_records;
    $total_excess_points += $course_excess_points;
}

// Проверяем общую статистику по пользователям
echo "👥 === АНАЛИЗ ПО ПОЛЬЗОВАТЕЛЯМ ===\n";

$users_with_points = $wpdb->get_results(
    "SELECT user_id, 
            COUNT(*) as total_records,
            SUM(points) as total_points,
            COUNT(CASE WHEN points_type = 'lesson' THEN 1 END) as lesson_records,
            COUNT(CASE WHEN points_type = 'course_completion' THEN 1 END) as course_records,
            COUNT(CASE WHEN points_type = 'streak' THEN 1 END) as streak_records,
            COUNT(CASE WHEN points_type = 'multi_lesson' THEN 1 END) as multi_records
     FROM {$wpdb->prefix}cryptoschool_points_history
     GROUP BY user_id
     ORDER BY total_points DESC"
);

foreach ($users_with_points as $user_stat) {
    $user_info = get_userdata($user_stat->user_id);
    $username = $user_info ? $user_info->user_login : "User ID {$user_stat->user_id}";
    
    // Получаем реальное количество завершенных уроков
    $actual_lessons = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT lesson_id) FROM {$wpdb->prefix}cryptoschool_user_lesson_progress
         WHERE user_id = %d AND is_completed = 1",
        $user_stat->user_id
    ));
    
    $discrepancy = $user_stat->lesson_records - $actual_lessons;
    $status = $discrepancy > 0 ? "❌ ДУБЛИКАТЫ" : "✅ OK";
    
    echo "👤 $username:\n";
    echo "   💰 Общие баллы: {$user_stat->total_points}\n";
    echo "   📊 Всего записей: {$user_stat->total_records}\n";
    echo "   📚 За уроки: {$user_stat->lesson_records} записей (реально: $actual_lessons) $status\n";
    echo "   🏆 За курсы: {$user_stat->course_records} записей\n";
    echo "   🔥 За серии: {$user_stat->streak_records} записей\n";
    echo "   ⚡ Мульти: {$user_stat->multi_records} записей\n";
    
    if ($discrepancy > 0) {
        echo "   ⚠️  Лишних записей за уроки: $discrepancy\n";
    }
    echo "\n";
}

echo "🎯 === ОБЩИЙ ИТОГ ===\n";
if ($total_excess_records > 0) {
    echo "❌ ОБНАРУЖЕНЫ ДУБЛИКАТЫ:\n";
    echo "   📊 Лишних записей: $total_excess_records\n";
    echo "   💰 Лишних баллов: $total_excess_points\n";
    echo "   🛠️  Рекомендация: Запустить fix-duplicate-points.php\n";
} else {
    echo "✅ ДУБЛИКАТОВ НЕ ОБНАРУЖЕНО\n";
    echo "   🎉 Система баллов работает корректно!\n";
}

echo "\n=== АНАЛИЗ ЗАВЕРШЕН ===\n";

if ($is_web_request) {
    echo "</pre>";
}
?>