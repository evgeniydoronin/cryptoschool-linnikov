<?php
/**
 * Скрипт для начисления недостающих баллов пользователю ID=6
 * Начисляет баллы за уроки, которые были завершены, но баллы не начислялись
 */

// Подключение к WordPress
require_once('wp-load.php');

// Проверяем, запущен ли скрипт через браузер
$is_web_request = !empty($_SERVER['HTTP_HOST']);

if ($is_web_request) {
    echo "<pre style='background: #1e1e1e; color: #fff; padding: 20px; font-family: monospace; line-height: 1.5;'>";
}

echo "=== НАЧИСЛЕНИЕ НЕДОСТАЮЩИХ БАЛЛОВ ПОЛЬЗОВАТЕЛЮ ID=6 ===\n\n";

$user_id = 6;
global $wpdb;

// Получаем информацию о пользователе
$user_info = get_userdata($user_id);
if (!$user_info) {
    die("❌ Пользователь с ID $user_id не найден\n");
}

echo "👤 Пользователь: {$user_info->user_login} ({$user_info->display_name})\n\n";

// Получаем все завершенные уроки пользователя
$completed_lessons = $wpdb->get_results($wpdb->prepare(
    "SELECT lesson_id, completed_at 
     FROM {$wpdb->prefix}cryptoschool_user_lesson_progress 
     WHERE user_id = %d AND is_completed = 1
     ORDER BY completed_at ASC",
    $user_id
));

if (empty($completed_lessons)) {
    echo "❌ Нет завершенных уроков\n";
    exit;
}

echo "📚 Найдено завершенных уроков: " . count($completed_lessons) . "\n\n";

// Получаем существующие начисления баллов за уроки
$existing_lesson_points = $wpdb->get_results($wpdb->prepare(
    "SELECT lesson_id, created_at FROM {$wpdb->prefix}cryptoschool_points_history 
     WHERE user_id = %d AND points_type = 'lesson'",
    $user_id
));

$awarded_lessons = [];
foreach ($existing_lesson_points as $point) {
    $awarded_lessons[] = $point->lesson_id;
}

echo "💰 Уже начислены баллы за уроки: " . implode(', ', $awarded_lessons) . "\n\n";

$missing_lessons = [];
$total_missing_points = 0;

foreach ($completed_lessons as $lesson) {
    // Проверяем, что это реальный урок
    $lesson_post_id = $wpdb->get_var($wpdb->prepare(
        "SELECT element_id FROM {$wpdb->prefix}icl_translations 
         WHERE trid = %d AND element_type = %s AND language_code = %s",
        $lesson->lesson_id, 'post_cryptoschool_lesson', apply_filters('wpml_current_language', null)
    ));
    
    if (!$lesson_post_id) {
        $lesson_post_id = $lesson->lesson_id; // fallback
    }
    
    $lesson_post = get_post($lesson_post_id);
    if (!$lesson_post || $lesson_post->post_type !== 'cryptoschool_lesson') {
        echo "⚠️  Пропускаем системную страницу: lesson_id {$lesson->lesson_id}\n";
        continue; // Пропускаем системные страницы
    }
    
    // Проверяем, начислялись ли уже баллы за этот урок
    if (!in_array($lesson->lesson_id, $awarded_lessons)) {
        $missing_lessons[] = [
            'lesson_id' => $lesson->lesson_id,
            'lesson_post_id' => $lesson_post_id,
            'title' => $lesson_post->post_title,
            'completed_at' => $lesson->completed_at
        ];
        $total_missing_points += 5; // 5 баллов за урок
    }
}

echo "🔍 === АНАЛИЗ ПРОПУЩЕННЫХ НАЧИСЛЕНИЙ ===\n";
echo "❌ Уроков без начислений: " . count($missing_lessons) . "\n";
echo "💰 Недостающих баллов: $total_missing_points\n\n";

if (empty($missing_lessons)) {
    echo "🎉 Все уроки уже имеют начисления баллов!\n";
    if ($is_web_request) {
        echo "</pre>";
    }
    exit;
}

echo "📋 === УРОКИ БЕЗ НАЧИСЛЕНИЙ ===\n";
foreach ($missing_lessons as $lesson) {
    $date = date('d.m.Y H:i', strtotime($lesson['completed_at']));
    echo "   📚 {$lesson['title']} (trid: {$lesson['lesson_id']}, завершен: $date)\n";
}
echo "\n";

echo "🛠️  === НАЧИСЛЕНИЕ НЕДОСТАЮЩИХ БАЛЛОВ ===\n";

$awarded_points = 0;
$awarded_lessons_count = 0;

foreach ($missing_lessons as $lesson) {
    $lesson_points = 5;
    $description = sprintf('Начисление за урок "%s"', $lesson['title']);
    
    // Начисляем баллы
    $result = $wpdb->insert(
        $wpdb->prefix . 'cryptoschool_points_history',
        [
            'user_id' => $user_id,
            'lesson_id' => $lesson['lesson_id'],
            'points' => $lesson_points,
            'points_type' => 'lesson',
            'description' => $description,
            'created_at' => $lesson['completed_at'] // Используем дату завершения урока
        ]
    );
    
    if ($result) {
        $awarded_points += $lesson_points;
        $awarded_lessons_count++;
        echo "   ✅ Начислено $lesson_points баллов за '{$lesson['title']}'\n";
    } else {
        echo "   ❌ Ошибка начисления за '{$lesson['title']}'\n";
    }
}

echo "\n🔄 === ОБНОВЛЕНИЕ РЕЙТИНГА ===\n";

// Пересчитываем общую сумму баллов
$new_total_points = $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(points) FROM {$wpdb->prefix}cryptoschool_points_history WHERE user_id = %d",
    $user_id
));

// Обновляем запись в таблице рейтинга
$updated = $wpdb->update(
    $wpdb->prefix . 'cryptoschool_user_leaderboard',
    [
        'total_points' => $new_total_points,
        'completed_lessons' => count($completed_lessons),
        'last_updated' => current_time('mysql')
    ],
    ['user_id' => $user_id]
);

if ($updated) {
    echo "✅ Обновлен рейтинг пользователя: $new_total_points баллов\n";
} else {
    echo "❌ Ошибка обновления рейтинга\n";
}

echo "\n🎉 === РЕЗУЛЬТАТ ===\n";
echo "✅ Начислено баллов: $awarded_points\n";
echo "✅ За количество уроков: $awarded_lessons_count\n";
echo "✅ Общие баллы пользователя: $new_total_points\n";

echo "\n📋 === РЕКОМЕНДАЦИИ ===\n";
echo "1. Запустите test-real-user-points.php для проверки\n";
echo "2. Проверьте, что защита от дубликатов теперь работает\n";
echo "3. Протестируйте прохождение нового урока\n";

echo "\n=== НАЧИСЛЕНИЕ ЗАВЕРШЕНО ===\n";

if ($is_web_request) {
    echo "</pre>";
}
?>