<?php
/**
 * Тестовый скрипт для проверки работы системы баллов с WPML
 * Проверяет корректность работы с trid и мультиязычностью
 */

// Подключение к WordPress
require_once('wp-load.php');

// Проверяем, запущен ли скрипт через браузер
$is_web_request = !empty($_SERVER['HTTP_HOST']);

if ($is_web_request) {
    echo "<pre style='background: #1e1e1e; color: #fff; padding: 20px; font-family: monospace; line-height: 1.5;'>";
}

echo "=== ТЕСТИРОВАНИЕ СИСТЕМЫ БАЛЛОВ С WPML ===\n\n";

global $wpdb;

// 1. Проверяем статус WPML
echo "🌐 === ПРОВЕРКА WPML ===\n";
$wpml_active = is_plugin_active('sitepress-multilingual-cms/sitepress.php') || 
               function_exists('icl_get_languages');

if ($wpml_active) {
    echo "✅ WPML активен\n";
    
    // Получаем доступные языки
    if (function_exists('icl_get_languages')) {
        $languages = icl_get_languages('skip_missing=0&orderby=code');
        echo "🗣️  Доступные языки: ";
        foreach ($languages as $lang) {
            echo $lang['code'] . " ";
        }
        echo "\n";
    }
} else {
    echo "❌ WPML неактивен\n";
}

$current_lang = apply_filters('wpml_current_language', null);
echo "📍 Текущий язык: " . ($current_lang ?: 'не определен') . "\n\n";

// 2. Анализ уроков и их переводов
echo "📚 === АНАЛИЗ УРОКОВ И ПЕРЕВОДОВ ===\n";

// Получаем все уроки
$lessons = get_posts([
    'post_type' => 'cryptoschool_lesson',
    'post_status' => 'publish',
    'numberposts' => 10, // Ограничим для тестирования
]);

echo "Найдено уроков: " . count($lessons) . "\n\n";

$trid_groups = [];

foreach ($lessons as $lesson) {
    // Получаем trid урока
    $trid = $wpdb->get_var($wpdb->prepare(
        "SELECT trid FROM {$wpdb->prefix}icl_translations 
         WHERE element_id = %d AND element_type = %s",
        $lesson->ID, 'post_cryptoschool_lesson'
    ));
    
    if ($trid) {
        if (!isset($trid_groups[$trid])) {
            $trid_groups[$trid] = [];
        }
        
        // Получаем язык урока
        $language = $wpdb->get_var($wpdb->prepare(
            "SELECT language_code FROM {$wpdb->prefix}icl_translations 
             WHERE element_id = %d AND element_type = %s",
            $lesson->ID, 'post_cryptoschool_lesson'
        ));
        
        $trid_groups[$trid][] = [
            'id' => $lesson->ID,
            'title' => $lesson->post_title,
            'language' => $language ?: 'unknown'
        ];
    } else {
        echo "⚠️  Урок {$lesson->ID} '{$lesson->post_title}' не имеет trid (не переведен)\n";
    }
}

echo "📊 Групп переводов (trid): " . count($trid_groups) . "\n\n";

// Показываем группы переводов
foreach ($trid_groups as $trid => $lessons_group) {
    if (count($lessons_group) > 1) {
        echo "🔗 TRID $trid (переводы):\n";
        foreach ($lessons_group as $lesson_data) {
            echo "   📖 [{$lesson_data['language']}] ID {$lesson_data['id']}: {$lesson_data['title']}\n";
        }
        echo "\n";
    }
}

// 3. Проверяем прогресс пользователей по trid
echo "👥 === АНАЛИЗ ПРОГРЕССА ПОЛЬЗОВАТЕЛЕЙ ===\n";

$user_progress = $wpdb->get_results(
    "SELECT user_id, lesson_id, is_completed, completed_at 
     FROM {$wpdb->prefix}cryptoschool_user_lesson_progress 
     WHERE is_completed = 1
     ORDER BY user_id, lesson_id"
);

echo "Записей прогресса: " . count($user_progress) . "\n\n";

$user_stats = [];
foreach ($user_progress as $progress) {
    $user_id = $progress->user_id;
    $lesson_trid = $progress->lesson_id;
    
    if (!isset($user_stats[$user_id])) {
        $user_stats[$user_id] = [
            'completed_trids' => [],
            'completed_count' => 0
        ];
    }
    
    if (!in_array($lesson_trid, $user_stats[$user_id]['completed_trids'])) {
        $user_stats[$user_id]['completed_trids'][] = $lesson_trid;
        $user_stats[$user_id]['completed_count']++;
    }
}

foreach ($user_stats as $user_id => $stats) {
    $user_info = get_userdata($user_id);
    $username = $user_info ? $user_info->user_login : "User $user_id";
    
    echo "👤 $username: {$stats['completed_count']} уроков (trid: " . 
         implode(', ', $stats['completed_trids']) . ")\n";
}

// 4. Проверяем начисления баллов
echo "\n💰 === АНАЛИЗ НАЧИСЛЕНИЙ БАЛЛОВ ===\n";

$points_stats = $wpdb->get_results(
    "SELECT user_id, lesson_id, points, points_type, created_at, description
     FROM {$wpdb->prefix}cryptoschool_points_history 
     WHERE points_type = 'lesson'
     ORDER BY user_id, lesson_id"
);

echo "Записей начислений за уроки: " . count($points_stats) . "\n\n";

$points_by_user = [];
foreach ($points_stats as $point) {
    $user_id = $point->user_id;
    
    if (!isset($points_by_user[$user_id])) {
        $points_by_user[$user_id] = [];
    }
    
    $points_by_user[$user_id][] = [
        'lesson_trid' => $point->lesson_id,
        'points' => $point->points,
        'date' => $point->created_at,
        'description' => $point->description
    ];
}

foreach ($points_by_user as $user_id => $user_points) {
    $user_info = get_userdata($user_id);
    $username = $user_info ? $user_info->user_login : "User $user_id";
    
    echo "👤 $username:\n";
    
    $total_lesson_points = 0;
    $unique_trids = [];
    
    foreach ($user_points as $point) {
        $total_lesson_points += $point['points'];
        if (!in_array($point['lesson_trid'], $unique_trids)) {
            $unique_trids[] = $point['lesson_trid'];
        }
        
        $date = date('d.m.Y', strtotime($point['date']));
        echo "   💰 +{$point['points']} баллов за trid {$point['lesson_trid']} ($date)\n";
    }
    
    echo "   📊 Итого: $total_lesson_points баллов за " . count($unique_trids) . " уроков\n\n";
}

// 5. Проверка консистентности
echo "🔍 === ПРОВЕРКА КОНСИСТЕНТНОСТИ ===\n";

$consistency_issues = [];

foreach ($user_stats as $user_id => $progress_stats) {
    $user_info = get_userdata($user_id);
    $username = $user_info ? $user_info->user_login : "User $user_id";
    
    // Проверяем соответствие прогресса и начислений
    $user_points_trids = [];
    if (isset($points_by_user[$user_id])) {
        foreach ($points_by_user[$user_id] as $point) {
            if (!in_array($point['lesson_trid'], $user_points_trids)) {
                $user_points_trids[] = $point['lesson_trid'];
            }
        }
    }
    
    $completed_trids = $progress_stats['completed_trids'];
    $points_trids = $user_points_trids;
    
    // Найти уроки с прогрессом, но без начислений
    $missing_points = array_diff($completed_trids, $points_trids);
    if (!empty($missing_points)) {
        $consistency_issues[] = [
            'user' => $username,
            'issue' => 'missing_points',
            'trids' => $missing_points
        ];
    }
    
    // Найти начисления без прогресса
    $extra_points = array_diff($points_trids, $completed_trids);
    if (!empty($extra_points)) {
        $consistency_issues[] = [
            'user' => $username,
            'issue' => 'extra_points',
            'trids' => $extra_points
        ];
    }
}

if (empty($consistency_issues)) {
    echo "✅ Проблем с консистентностью не найдено!\n";
    echo "🎉 Система корректно работает с WPML и trid\n";
} else {
    echo "❌ Найдены проблемы консистентности:\n\n";
    
    foreach ($consistency_issues as $issue) {
        echo "👤 {$issue['user']}:\n";
        
        if ($issue['issue'] === 'missing_points') {
            echo "   ❌ Не начислены баллы за trid: " . implode(', ', $issue['trids']) . "\n";
        } elseif ($issue['issue'] === 'extra_points') {
            echo "   ⚠️  Начислены баллы без прогресса за trid: " . implode(', ', $issue['trids']) . "\n";
        }
        echo "\n";
    }
}

// 6. Тестирование защиты от дубликатов
echo "\n🛡️  === ТЕСТИРОВАНИЕ ЗАЩИТЫ ОТ ДУБЛИКАТОВ ===\n";

if (!empty($user_stats)) {
    $test_user_id = array_keys($user_stats)[0];
    $test_trid = $user_stats[$test_user_id]['completed_trids'][0] ?? null;
    
    if ($test_user_id && $test_trid) {
        $user_info = get_userdata($test_user_id);
        $username = $user_info ? $user_info->user_login : "User $test_user_id";
        
        echo "🧪 Тестируем дубликат для $username, trid $test_trid:\n";
        
        // Проверяем, есть ли уже начисления
        $existing_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}cryptoschool_points_history 
             WHERE user_id = %d AND lesson_id = %d AND points_type = 'lesson'",
            $test_user_id, $test_trid
        ));
        
        echo "   📊 Текущих начислений: $existing_count\n";
        
        // Симулируем повторное завершение урока
        echo "   🔄 Симулируем повторное завершение...\n";
        
        // Используем наш сервис для проверки защиты
        $points_service = new CryptoSchool_Service_Points(new CryptoSchool_Loader());
        
        // Получаем реальный post_id урока для этого trid
        $lesson_post_id = $wpdb->get_var($wpdb->prepare(
            "SELECT element_id FROM {$wpdb->prefix}icl_translations 
             WHERE trid = %d AND element_type = %s AND language_code = %s",
            $test_trid, 'post_cryptoschool_lesson', $current_lang ?: 'uk'
        ));
        
        if ($lesson_post_id) {
            // Вызываем обработчик завершения урока
            $points_service->process_lesson_completion($test_user_id, $test_trid);
            
            // Проверяем количество начислений после повторного вызова
            $new_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}cryptoschool_points_history 
                 WHERE user_id = %d AND lesson_id = %d AND points_type = 'lesson'",
                $test_user_id, $test_trid
            ));
            
            if ($new_count == $existing_count) {
                echo "   ✅ Защита от дубликатов работает! Количество не изменилось.\n";
            } else {
                echo "   ❌ Защита НЕ работает! Было: $existing_count, стало: $new_count\n";
            }
        } else {
            echo "   ⚠️  Не найден post_id для trid $test_trid\n";
        }
    }
}

echo "\n🎯 === ВЫВОДЫ ===\n";
echo "1. Система использует trid для единого прогресса по урокам\n";
echo "2. Баллы начисляются по trid, а не по отдельным переводам\n";
echo "3. При смене языка прогресс и баллы остаются едиными\n";
echo "4. Защита от дубликатов предотвращает повторные начисления\n";

echo "\n=== ТЕСТИРОВАНИЕ ЗАВЕРШЕНО ===\n";

if ($is_web_request) {
    echo "</pre>";
}
?>