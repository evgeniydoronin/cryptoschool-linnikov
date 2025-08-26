<?php
/**
 * Скрипт для исправления дубликатов в системе баллов
 * Удаляет лишние записи, оставляя только одну за каждый урок/курс
 */

// Подключение к WordPress
require_once('wp-load.php');

// Проверяем, запущен ли скрипт через браузер
$is_web_request = !empty($_SERVER['HTTP_HOST']);

if ($is_web_request) {
    echo "<pre style='background: #1e1e1e; color: #fff; padding: 20px; font-family: monospace; line-height: 1.5;'>";
}

echo "=== ИСПРАВЛЕНИЕ ДУБЛИКАТОВ В СИСТЕМЕ БАЛЛОВ ===\n\n";

global $wpdb;

// Сначала показываем что будет исправлено
echo "🔍 === АНАЛИЗ ДУБЛИКАТОВ ПЕРЕД ИСПРАВЛЕНИЕМ ===\n";

$lesson_duplicates = $wpdb->get_results(
    "SELECT user_id, lesson_id, COUNT(*) as duplicate_count, 
            SUM(points) as total_points,
            GROUP_CONCAT(id ORDER BY created_at) as record_ids,
            MIN(created_at) as first_entry
     FROM {$wpdb->prefix}cryptoschool_points_history 
     WHERE points_type = 'lesson' AND lesson_id IS NOT NULL
     GROUP BY user_id, lesson_id 
     HAVING COUNT(*) > 1
     ORDER BY user_id, lesson_id"
);

$course_duplicates = $wpdb->get_results(
    "SELECT user_id, description, COUNT(*) as duplicate_count,
            SUM(points) as total_points,
            GROUP_CONCAT(id ORDER BY created_at) as record_ids,
            MIN(created_at) as first_entry
     FROM {$wpdb->prefix}cryptoschool_points_history 
     WHERE points_type = 'course_completion'
     GROUP BY user_id, description
     HAVING COUNT(*) > 1
     ORDER BY user_id"
);

$total_records_to_delete = 0;
$total_points_to_remove = 0;

if (!empty($lesson_duplicates)) {
    echo "❌ Найдены дубликаты баллов за уроки:\n";
    foreach ($lesson_duplicates as $duplicate) {
        $user_info = get_userdata($duplicate->user_id);
        $username = $user_info ? $user_info->user_login : "User ID {$duplicate->user_id}";
        
        $records_to_delete = $duplicate->duplicate_count - 1;
        $points_to_remove = $records_to_delete * 5;
        
        $total_records_to_delete += $records_to_delete;
        $total_points_to_remove += $points_to_remove;
        
        echo "   👤 $username: урок {$duplicate->lesson_id} ({$duplicate->duplicate_count} записей)\n";
        echo "      ❌ Удалим: $records_to_delete записей (-$points_to_remove баллов)\n";
    }
}

if (!empty($course_duplicates)) {
    echo "❌ Найдены дубликаты баллов за курсы:\n";
    foreach ($course_duplicates as $duplicate) {
        $user_info = get_userdata($duplicate->user_id);
        $username = $user_info ? $user_info->user_login : "User ID {$duplicate->user_id}";
        
        $records_to_delete = $duplicate->duplicate_count - 1;
        $points_to_remove = $records_to_delete * 50;
        
        $total_records_to_delete += $records_to_delete;
        $total_points_to_remove += $points_to_remove;
        
        echo "   👤 $username: курс '{$duplicate->description}' ({$duplicate->duplicate_count} записей)\n";
        echo "      ❌ Удалим: $records_to_delete записей (-$points_to_remove баллов)\n";
    }
}

if ($total_records_to_delete == 0) {
    echo "✅ Дубликатов не обнаружено! Нечего исправлять.\n";
    if ($is_web_request) {
        echo "</pre>";
    }
    exit;
}

echo "\n📊 === ИТОГОВАЯ СТАТИСТИКА ===\n";
echo "❌ Всего записей к удалению: $total_records_to_delete\n";
echo "❌ Всего баллов к удалению: $total_points_to_remove\n\n";

// Запрос подтверждения (только для командной строки)
if (!$is_web_request) {
    echo "⚠️  === ПОДТВЕРЖДЕНИЕ ===\n";
    echo "Удалить $total_records_to_delete дублированных записей?\n";
    echo "Это уменьшит баллы пользователей на $total_points_to_remove баллов.\n";
    echo "Продолжить? (yes/no): ";
    
    $handle = fopen("php://stdin", "r");
    $confirmation = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($confirmation) !== 'yes' && strtolower($confirmation) !== 'y') {
        echo "❌ Исправление отменено пользователем\n";
        exit;
    }
}

echo "\n🛠️  === НАЧИНАЕМ ИСПРАВЛЕНИЕ ===\n";

$deleted_records = 0;
$removed_points = 0;

// Исправляем дубликаты уроков
if (!empty($lesson_duplicates)) {
    echo "🔧 Исправляем дубликаты баллов за уроки...\n";
    
    foreach ($lesson_duplicates as $duplicate) {
        $record_ids = explode(',', $duplicate->record_ids);
        $keep_id = array_shift($record_ids); // Оставляем первую запись
        
        $user_info = get_userdata($duplicate->user_id);
        $username = $user_info ? $user_info->user_login : "User ID {$duplicate->user_id}";
        
        foreach ($record_ids as $delete_id) {
            $record = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}cryptoschool_points_history WHERE id = %d",
                $delete_id
            ));
            
            if ($record) {
                $deleted = $wpdb->delete(
                    $wpdb->prefix . 'cryptoschool_points_history',
                    ['id' => $delete_id],
                    ['%d']
                );
                
                if ($deleted) {
                    $deleted_records++;
                    $removed_points += $record->points;
                    echo "   ✅ Удалена запись ID $delete_id ($username, урок {$duplicate->lesson_id}, -{$record->points} баллов)\n";
                } else {
                    echo "   ❌ Ошибка удаления записи ID $delete_id\n";
                }
            }
        }
    }
}

// Исправляем дубликаты курсов
if (!empty($course_duplicates)) {
    echo "🔧 Исправляем дубликаты баллов за курсы...\n";
    
    foreach ($course_duplicates as $duplicate) {
        $record_ids = explode(',', $duplicate->record_ids);
        $keep_id = array_shift($record_ids); // Оставляем первую запись
        
        $user_info = get_userdata($duplicate->user_id);
        $username = $user_info ? $user_info->user_login : "User ID {$duplicate->user_id}";
        
        foreach ($record_ids as $delete_id) {
            $record = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}cryptoschool_points_history WHERE id = %d",
                $delete_id
            ));
            
            if ($record) {
                $deleted = $wpdb->delete(
                    $wpdb->prefix . 'cryptoschool_points_history',
                    ['id' => $delete_id],
                    ['%d']
                );
                
                if ($deleted) {
                    $deleted_records++;
                    $removed_points += $record->points;
                    echo "   ✅ Удалена запись ID $delete_id ($username, курс, -{$record->points} баллов)\n";
                } else {
                    echo "   ❌ Ошибка удаления записи ID $delete_id\n";
                }
            }
        }
    }
}

echo "\n🔄 === ОБНОВЛЕНИЕ РЕЙТИНГОВ ===\n";

// Пересчитываем баллы пользователей
$users_to_update = $wpdb->get_results(
    "SELECT user_id, SUM(points) as new_total_points
     FROM {$wpdb->prefix}cryptoschool_points_history
     GROUP BY user_id"
);

foreach ($users_to_update as $user_data) {
    // Обновляем общую сумму баллов в leaderboard
    $updated = $wpdb->update(
        $wpdb->prefix . 'cryptoschool_user_leaderboard',
        ['total_points' => $user_data->new_total_points],
        ['user_id' => $user_data->user_id]
    );
    
    if ($updated) {
        $user_info = get_userdata($user_data->user_id);
        $username = $user_info ? $user_info->user_login : "User ID {$user_data->user_id}";
        echo "   ✅ Обновлены баллы для $username: {$user_data->new_total_points}\n";
    }
}

// Пересчитываем рейтинги
$users_with_points = $wpdb->get_results(
    "SELECT user_id, total_points FROM {$wpdb->prefix}cryptoschool_user_leaderboard 
     ORDER BY total_points DESC, completed_lessons DESC"
);

$rank = 1;
foreach ($users_with_points as $user) {
    $wpdb->update(
        $wpdb->prefix . 'cryptoschool_user_leaderboard',
        ['user_rank' => $rank],
        ['user_id' => $user->user_id]
    );
    $rank++;
}

echo "   ✅ Пересчитаны рейтинги для " . count($users_with_points) . " пользователей\n";

echo "\n🎉 === РЕЗУЛЬТАТ ИСПРАВЛЕНИЯ ===\n";
echo "✅ Удалено дублированных записей: $deleted_records\n";
echo "✅ Удалено лишних баллов: $removed_points\n";
echo "✅ Рейтинги пересчитаны\n";

// Проверяем результат
echo "\n🔍 === ПРОВЕРКА РЕЗУЛЬТАТА ===\n";

$remaining_duplicates = $wpdb->get_var(
    "SELECT COUNT(*) FROM (
        SELECT user_id, lesson_id, COUNT(*) as cnt
        FROM {$wpdb->prefix}cryptoschool_points_history 
        WHERE points_type = 'lesson' AND lesson_id IS NOT NULL
        GROUP BY user_id, lesson_id 
        HAVING COUNT(*) > 1
    ) as dups"
);

$remaining_course_duplicates = $wpdb->get_var(
    "SELECT COUNT(*) FROM (
        SELECT user_id, description, COUNT(*) as cnt
        FROM {$wpdb->prefix}cryptoschool_points_history 
        WHERE points_type = 'course_completion'
        GROUP BY user_id, description
        HAVING COUNT(*) > 1
    ) as dups"
);

if ($remaining_duplicates == 0 && $remaining_course_duplicates == 0) {
    echo "🎉 ДУБЛИКАТЫ УСПЕШНО ИСПРАВЛЕНЫ!\n";
    echo "✅ Система баллов теперь корректна\n";
} else {
    echo "⚠️  Остались дубликаты:\n";
    echo "   📚 Уроков: $remaining_duplicates\n";
    echo "   🏆 Курсов: $remaining_course_duplicates\n";
    echo "   🔧 Возможно потребуется повторный запуск\n";
}

echo "\n📋 === РЕКОМЕНДАЦИИ ===\n";
echo "1. Запустите test-real-user-points.php для проверки\n";
echo "2. Убедитесь, что новые дубликаты не создаются\n";
echo "3. Проверьте работу системы баллов на новых уроках\n";

echo "\n=== ИСПРАВЛЕНИЕ ЗАВЕРШЕНО ===\n";

if ($is_web_request) {
    echo "</pre>";
}
?>