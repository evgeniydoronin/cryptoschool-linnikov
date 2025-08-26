<?php
/**
 * Скрипт для начисления баллов за уроки, пройденные до активации системы баллов
 * Анализирует завершенные уроки и начисляет за них баллы
 */

// Подключение к WordPress
require_once('wp-load.php');

// Проверяем, запущен ли скрипт через браузер
$is_web_request = !empty($_SERVER['HTTP_HOST']);

if ($is_web_request) {
    echo "<pre style='background: #1e1e1e; color: #fff; padding: 20px; font-family: monospace; line-height: 1.5;'>";
}

echo "=== НАЧИСЛЕНИЕ РЕТРОСПЕКТИВНЫХ БАЛЛОВ ===\n\n";

global $wpdb;

// Получаем всех пользователей с завершенными уроками
$users_with_lessons = $wpdb->get_results(
    "SELECT DISTINCT user_id, COUNT(*) as lesson_count 
     FROM {$wpdb->prefix}cryptoschool_user_lesson_progress 
     WHERE is_completed = 1 
     GROUP BY user_id
     ORDER BY lesson_count DESC"
);

if (empty($users_with_lessons)) {
    echo "❌ Нет пользователей с завершенными уроками\n";
    exit;
}

echo "👥 Найдено пользователей с уроками: " . count($users_with_lessons) . "\n\n";

foreach ($users_with_lessons as $user_data) {
    $user_id = $user_data->user_id;
    $user_info = get_userdata($user_id);
    
    if (!$user_info) {
        echo "⚠️  Пользователь ID $user_id не существует, пропускаем\n";
        continue;
    }
    
    echo "👤 Обрабатываем пользователя: {$user_info->user_login} (ID: $user_id)\n";
    echo "📚 Завершенных уроков: {$user_data->lesson_count}\n";
    
    // Проверяем, есть ли уже баллы за уроки у этого пользователя
    $existing_points = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}cryptoschool_points_history 
         WHERE user_id = %d AND points_type = 'lesson'",
        $user_id
    ));
    
    if ($existing_points > 0) {
        echo "✅ У пользователя уже есть $existing_points записей баллов за уроки, пропускаем\n\n";
        continue;
    }
    
    // Получаем все завершенные уроки пользователя
    $completed_lessons = $wpdb->get_results($wpdb->prepare(
        "SELECT lesson_id, completed_at 
         FROM {$wpdb->prefix}cryptoschool_user_lesson_progress 
         WHERE user_id = %d AND is_completed = 1
         ORDER BY completed_at ASC",
        $user_id
    ));
    
    $processed_lessons = 0;
    $total_points = 0;
    $lessons_by_date = [];
    
    // Группируем уроки по датам
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
            continue; // Пропускаем системные страницы
        }
        
        $date = date('Y-m-d', strtotime($lesson->completed_at));
        if (!isset($lessons_by_date[$date])) {
            $lessons_by_date[$date] = [];
        }
        $lessons_by_date[$date][] = $lesson;
        $processed_lessons++;
    }
    
    echo "🔍 Реальных уроков для обработки: $processed_lessons\n";
    
    if ($processed_lessons == 0) {
        echo "⚠️  Нет реальных уроков для обработки\n\n";
        continue;
    }
    
    // Сортируем даты
    ksort($lessons_by_date);
    
    $current_streak = 0;
    $max_streak = 0;
    $last_date = null;
    
    foreach ($lessons_by_date as $date => $lessons) {
        // Проверяем серию
        if ($last_date) {
            $yesterday = date('Y-m-d', strtotime($last_date . ' +1 day'));
            if ($date == $yesterday) {
                $current_streak++;
            } else {
                $current_streak = 1; // Сброс серии
            }
        } else {
            $current_streak = 1; // Первый день
        }
        
        if ($current_streak > $max_streak) {
            $max_streak = $current_streak;
        }
        
        $lessons_today = count($lessons);
        echo "   📅 $date: $lessons_today уроков (серия: $current_streak дней)\n";
        
        foreach ($lessons as $lesson_index => $lesson) {
            // Получаем информацию об уроке
            $lesson_post_id = $wpdb->get_var($wpdb->prepare(
                "SELECT element_id FROM {$wpdb->prefix}icl_translations 
                 WHERE trid = %d AND element_type = %s AND language_code = %s",
                $lesson->lesson_id, 'post_cryptoschool_lesson', apply_filters('wpml_current_language', null)
            ));
            
            if (!$lesson_post_id) {
                $lesson_post_id = $lesson->lesson_id;
            }
            
            $lesson_post = get_post($lesson_post_id);
            $lesson_title = $lesson_post ? $lesson_post->post_title : "Урок ID {$lesson->lesson_id}";
            
            // Начисляем базовые баллы за урок (5)
            $lesson_points = 5;
            $total_points += $lesson_points;
            
            // Записываем в историю баллов
            $wpdb->insert(
                $wpdb->prefix . 'cryptoschool_points_history',
                [
                    'user_id' => $user_id,
                    'lesson_id' => $lesson->lesson_id,
                    'points' => $lesson_points,
                    'points_type' => 'lesson',
                    'description' => sprintf('Ретроспективное начисление за урок "%s"', $lesson_title),
                    'created_at' => $lesson->completed_at
                ]
            );
            
            // Начисляем баллы за серию (если серия >= 2 и это первый урок дня)
            if ($current_streak >= 2 && $lesson_index == 0) {
                $streak_points = 5;
                $total_points += $streak_points;
                
                $wpdb->insert(
                    $wpdb->prefix . 'cryptoschool_points_history',
                    [
                        'user_id' => $user_id,
                        'lesson_id' => null,
                        'points' => $streak_points,
                        'points_type' => 'streak',
                        'streak_day' => $current_streak,
                        'description' => sprintf('Ретроспективный бонус за %d день серии', $current_streak),
                        'created_at' => $lesson->completed_at
                    ]
                );
            }
            
            // Начисляем баллы за мульти-уроки (если серия >= 2 и это не первый урок дня)
            if ($current_streak >= 2 && $lesson_index > 0) {
                $multi_points = 5;
                $total_points += $multi_points;
                
                $wpdb->insert(
                    $wpdb->prefix . 'cryptoschool_points_history',
                    [
                        'user_id' => $user_id,
                        'lesson_id' => $lesson->lesson_id,
                        'points' => $multi_points,
                        'points_type' => 'multi_lesson',
                        'lesson_number_today' => $lesson_index + 1,
                        'description' => sprintf('Ретроспективный бонус за %d-й урок за день', $lesson_index + 1),
                        'created_at' => $lesson->completed_at
                    ]
                );
            }
        }
        
        $last_date = $date;
    }
    
    // Создаем или обновляем запись о серии
    $wpdb->replace(
        $wpdb->prefix . 'cryptoschool_user_streak',
        [
            'user_id' => $user_id,
            'current_streak' => 0, // Сбрасываем, так как это старые уроки
            'max_streak' => $max_streak,
            'last_activity_date' => $last_date,
            'lessons_today' => 0,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ]
    );
    
    // Создаем или обновляем запись в рейтинге
    $wpdb->replace(
        $wpdb->prefix . 'cryptoschool_user_leaderboard',
        [
            'user_id' => $user_id,
            'total_points' => $total_points,
            'user_rank' => 0, // Будет пересчитан позже
            'completed_lessons' => $processed_lessons,
            'days_active' => count($lessons_by_date),
            'last_updated' => current_time('mysql')
        ]
    );
    
    echo "✅ Начислено баллов: $total_points\n";
    echo "🏆 Максимальная серия: $max_streak дней\n";
    echo "📊 Дней активности: " . count($lessons_by_date) . "\n\n";
}

// Пересчитываем рейтинги
echo "🔄 === ПЕРЕСЧЕТ РЕЙТИНГОВ ===\n";

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

echo "✅ Обновлены рейтинги для " . count($users_with_points) . " пользователей\n\n";

echo "🎉 === ИТОГОВАЯ СТАТИСТИКА ===\n";

$total_points_awarded = $wpdb->get_var(
    "SELECT SUM(points) FROM {$wpdb->prefix}cryptoschool_points_history"
);

$total_users_with_points = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->prefix}cryptoschool_user_leaderboard WHERE total_points > 0"
);

$total_history_records = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->prefix}cryptoschool_points_history"
);

echo "💰 Всего начислено баллов: $total_points_awarded\n";
echo "👥 Пользователей с баллами: $total_users_with_points\n";
echo "📊 Записей в истории: $total_history_records\n";

echo "\n=== РЕТРОСПЕКТИВНОЕ НАЧИСЛЕНИЕ ЗАВЕРШЕНО ===\n";

if ($is_web_request) {
    echo "</pre>";
}
?>