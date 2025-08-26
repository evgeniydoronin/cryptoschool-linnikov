<?php
/**
 * Тестовый скрипт для анализа реального пользователя ID=6
 * Проверяет баллы, историю начислений и текущий прогресс
 */

// Подключение к WordPress
require_once('wp-load.php');

// Проверяем, запущен ли скрипт через браузер
$is_web_request = !empty($_SERVER['HTTP_HOST']);

if ($is_web_request) {
    echo "<pre style='background: #1e1e1e; color: #fff; padding: 20px; font-family: monospace; line-height: 1.5;'>";
}

echo "=== АНАЛИЗ РЕАЛЬНОГО ПОЛЬЗОВАТЕЛЯ ID=6 ===\n\n";

$user_id = 6;

// Получаем информацию о пользователе
$user_info = get_userdata($user_id);
if (!$user_info) {
    die("❌ Пользователь с ID $user_id не найден\n");
}

echo "👤 Пользователь: {$user_info->user_login} ({$user_info->display_name})\n";
echo "📧 Email: {$user_info->user_email}\n";
echo "📅 Регистрация: {$user_info->user_registered}\n\n";

global $wpdb;

// ==================== ТЕКУЩИЕ БАЛЛЫ ====================
echo "🏆 === ТЕКУЩИЕ БАЛЛЫ ===\n";

$total_points = $wpdb->get_var($wpdb->prepare(
    "SELECT total_points FROM {$wpdb->prefix}cryptoschool_user_leaderboard WHERE user_id = %d",
    $user_id
));

if ($total_points === null) {
    echo "❌ Нет записи в таблице рейтинга\n";
    $total_points = 0;
} else {
    echo "✅ Общие баллы: $total_points\n";
}

// Проверяем по истории баллов
$history_total = $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(points) FROM {$wpdb->prefix}cryptoschool_points_history WHERE user_id = %d",
    $user_id
));

if ($history_total === null) {
    echo "❌ Нет истории начисления баллов\n";
    $history_total = 0;
} else {
    echo "📊 Сумма по истории: $history_total\n";
}

if ($total_points != $history_total && $history_total > 0) {
    echo "⚠️  ВНИМАНИЕ: Расхождение в данных! Возможная ошибка в системе.\n";
}

echo "\n";

// ==================== СЕРИЯ ПОЛЬЗОВАТЕЛЯ ====================
echo "🔥 === СЕРИЯ ПОЛЬЗОВАТЕЛЯ ===\n";

$streak_data = $wpdb->get_row($wpdb->prepare(
    "SELECT current_streak, max_streak, last_activity_date, lessons_today, created_at 
     FROM {$wpdb->prefix}cryptoschool_user_streak 
     WHERE user_id = %d",
    $user_id
));

if (!$streak_data) {
    echo "❌ Нет данных о серии\n";
} else {
    echo "🔥 Текущая серия: {$streak_data->current_streak} дней\n";
    echo "🏆 Максимальная серия: {$streak_data->max_streak} дней\n";
    echo "📅 Последняя активность: {$streak_data->last_activity_date}\n";
    echo "📚 Уроков сегодня: {$streak_data->lessons_today}\n";
    echo "⏰ Серия создана: {$streak_data->created_at}\n";
    
    // Проверяем актуальность серии
    $today = current_time('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    if ($streak_data->last_activity_date === $today) {
        echo "✅ Активен сегодня!\n";
    } elseif ($streak_data->last_activity_date === $yesterday) {
        echo "⚠️  Последняя активность вчера (серия может продолжиться)\n";
    } else {
        echo "❌ Серия прервана (последняя активность не вчера/сегодня)\n";
    }
}

echo "\n";

// ==================== ДЕТАЛЬНАЯ ИСТОРИЯ БАЛЛОВ ====================
echo "📈 === ИСТОРИЯ НАЧИСЛЕНИЯ БАЛЛОВ ===\n";

$points_history = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}cryptoschool_points_history 
     WHERE user_id = %d 
     ORDER BY created_at DESC 
     LIMIT 20",
    $user_id
));

if (empty($points_history)) {
    echo "❌ История баллов пуста\n";
} else {
    echo "📊 Последние " . count($points_history) . " начислений:\n\n";
    echo str_pad("ДАТА", 12) . " | " . str_pad("ТИП", 16) . " | " . str_pad("БАЛЛЫ", 6) . " | ОПИСАНИЕ\n";
    echo str_repeat("-", 70) . "\n";
    
    foreach ($points_history as $entry) {
        $date = date('d.m.Y', strtotime($entry->created_at));
        $type = str_pad($entry->points_type, 16);
        $points = str_pad("+" . $entry->points, 6);
        $description = $entry->description ?: 'Нет описания';
        
        echo "$date | $type | $points | $description\n";
    }
}

echo "\n";

// ==================== СТАТИСТИКА ПО ТИПАМ БАЛЛОВ ====================
echo "📊 === СТАТИСТИКА ПО ТИПАМ БАЛЛОВ ===\n";

$points_by_type = $wpdb->get_results($wpdb->prepare(
    "SELECT points_type, SUM(points) as total_points, COUNT(*) as count, 
            MIN(created_at) as first_earned, MAX(created_at) as last_earned
     FROM {$wpdb->prefix}cryptoschool_points_history 
     WHERE user_id = %d 
     GROUP BY points_type 
     ORDER BY total_points DESC",
    $user_id
));

if (empty($points_by_type)) {
    echo "❌ Нет статистики\n";
} else {
    foreach ($points_by_type as $stat) {
        echo "🎯 {$stat->points_type}:\n";
        echo "   💰 Всего баллов: {$stat->total_points}\n";
        echo "   🔢 Количество: {$stat->count} раз\n";
        echo "   📅 Период: " . date('d.m.Y', strtotime($stat->first_earned)) . " - " . date('d.m.Y', strtotime($stat->last_earned)) . "\n";
        echo "   📊 Среднее: " . round($stat->total_points / $stat->count, 2) . " баллов за раз\n\n";
    }
}

// ==================== ПРОГРЕСС УРОКОВ ====================
echo "📚 === ПРОГРЕСС УРОКОВ ===\n";

$completed_lessons = $wpdb->get_results($wpdb->prepare(
    "SELECT lesson_id, progress_percent, is_completed, completed_at 
     FROM {$wpdb->prefix}cryptoschool_user_lesson_progress 
     WHERE user_id = %d AND is_completed = 1
     ORDER BY completed_at DESC 
     LIMIT 10",
    $user_id
));

if (empty($completed_lessons)) {
    echo "❌ Нет завершенных уроков\n";
} else {
    echo "✅ Всего завершенных уроков в БД: " . count($completed_lessons) . "\n";
    echo "📝 Анализируем каждый урок:\n\n";
    
    $real_lessons = 0;
    $fake_lessons = 0;
    
    foreach ($completed_lessons as $lesson) {
        $lesson_post = null;
        $lesson_type = "unknown";
        
        // Сначала пробуем найти по trid (WPML)
        $lesson_id_by_trid = $wpdb->get_var($wpdb->prepare(
            "SELECT element_id FROM {$wpdb->prefix}icl_translations 
             WHERE trid = %d AND element_type = %s AND language_code = %s",
            $lesson->lesson_id, 'post_cryptoschool_lesson', apply_filters('wpml_current_language', null)
        ));
        
        if ($lesson_id_by_trid) {
            $lesson_post = get_post($lesson_id_by_trid);
            $lesson_type = "trid->post";
        } else {
            // Fallback: пробуем lesson_id как Post ID
            $lesson_post = get_post($lesson->lesson_id);
            $lesson_type = "direct_id";
        }
        
        // Проверяем, является ли это реальным уроком
        $is_real_lesson = ($lesson_post && $lesson_post->post_type === 'cryptoschool_lesson');
        $lesson_title = $lesson_post ? $lesson_post->post_title : "Урок ID {$lesson->lesson_id}";
        $completed_date = date('d.m.Y H:i', strtotime($lesson->completed_at));
        
        if ($is_real_lesson) {
            echo "   ✅ РЕАЛЬНЫЙ УРОК: $lesson_title\n";
            $real_lessons++;
        } else {
            echo "   ❌ СИСТЕМНАЯ СТРАНИЦА: $lesson_title\n";
            $fake_lessons++;
        }
        
        echo "      📍 Lesson ID: {$lesson->lesson_id} ($lesson_type)\n";
        echo "      📅 Завершен: $completed_date\n";
        echo "      📊 Прогресс: {$lesson->progress_percent}%\n";
        
        if ($lesson_post) {
            echo "      🏷️  Тип поста: {$lesson_post->post_type}\n";
        }
        
        echo "\n";
    }
    
    echo "📊 ИТОГ:\n";
    echo "✅ Настоящие уроки: $real_lessons\n";
    echo "❌ Системные страницы: $fake_lessons\n\n";
    
    if ($fake_lessons > 0) {
        echo "⚠️  ПРОБЛЕМА: Система засчитывает системные страницы как уроки!\n";
        echo "Функция cryptoschool_get_user_completed_lessons() требует фильтрации\n\n";
    }
}

// ==================== ДОСТУПЫ К ПАКЕТАМ ====================
echo "📦 === ДОСТУПЫ К ПАКЕТАМ ===\n";

$user_packages = $wpdb->get_results($wpdb->prepare(
    "SELECT p.title, p.course_ids, ua.access_start, ua.access_end, ua.status 
     FROM {$wpdb->prefix}cryptoschool_user_access ua
     JOIN {$wpdb->prefix}cryptoschool_packages p ON ua.package_id = p.id
     WHERE ua.user_id = %d
     ORDER BY ua.created_at DESC",
    $user_id
));

if (empty($user_packages)) {
    echo "❌ Нет доступов к пакетам\n";
} else {
    $all_course_ids = [];
    foreach ($user_packages as $package) {
        $status_icon = $package->status === 'active' ? '✅' : '❌';
        echo "$status_icon {$package->title}\n";
        echo "   📅 Период: {$package->access_start} - {$package->access_end}\n";
        echo "   📚 Course IDs: {$package->course_ids}\n";
        
        // Парсим course_ids из JSON
        $package_course_ids = json_decode($package->course_ids, true);
        if (is_array($package_course_ids)) {
            $all_course_ids = array_merge($all_course_ids, $package_course_ids);
            echo "   📋 Расшифровка: [" . implode(', ', $package_course_ids) . "]\n";
        }
        echo "\n";
    }
    
    // Показываем реальные курсы
    if (!empty($all_course_ids)) {
        echo "📚 === КУРСЫ ПОЛЬЗОВАТЕЛЯ (CUSTOM POST TYPES) ===\n";
        $all_course_ids = array_unique($all_course_ids);
        
        foreach ($all_course_ids as $course_id) {
            $course_post = get_post($course_id);
            if ($course_post && $course_post->post_type === 'cryptoschool_course') {
                echo "✅ Курс ID $course_id: {$course_post->post_title}\n";
                
                // Получаем уроки этого курса
                $lesson_data = get_field('choose_lesson', $course_id);
                if (!empty($lesson_data)) {
                    echo "   📖 Уроки курса:\n";
                    
                    if (is_array($lesson_data)) {
                        foreach ($lesson_data as $item) {
                            $lesson_id = is_object($item) ? $item->ID : $item;
                            $lesson_post = get_post($lesson_id);
                            if ($lesson_post) {
                                echo "      - ID {$lesson_id}: {$lesson_post->post_title}\n";
                            }
                        }
                    }
                } else {
                    echo "   ❌ Нет связанных уроков\n";
                }
                echo "\n";
            } else {
                echo "❌ Курс ID $course_id не найден или не является Custom Post Type\n\n";
            }
        }
    }
}

// ==================== АНАЛИЗ И ВЫВОДЫ ====================
echo "🔍 === АНАЛИЗ И ВЫВОДЫ ===\n";

if ($total_points > 0) {
    echo "✅ Пользователь активен в системе баллов\n";
    
    // Анализируем активность
    if (!empty($points_history)) {
        $first_points = end($points_history);
        $last_points = reset($points_history);
        
        $days_active = (strtotime($last_points->created_at) - strtotime($first_points->created_at)) / (60 * 60 * 24);
        $days_active = max(1, $days_active); // минимум 1 день
        
        $avg_points_per_day = round($total_points / $days_active, 2);
        
        echo "📊 Период активности: " . round($days_active) . " дней\n";
        echo "📈 Средние баллы в день: $avg_points_per_day\n";
        
        // Оценка эффективности
        if ($avg_points_per_day >= 10) {
            echo "🔥 Высокая активность! Регулярно поддерживает серию\n";
        } elseif ($avg_points_per_day >= 5) {
            echo "👍 Средняя активность. Учится, но возможны пропуски\n";
        } else {
            echo "⚠️  Низкая активность. Много пропусков или редкие занятия\n";
        }
    }
    
    if ($streak_data && $streak_data->max_streak > 0) {
        echo "🏆 Максимальная серия: {$streak_data->max_streak} дней ";
        if ($streak_data->max_streak >= 7) {
            echo "(Отличный результат!)\n";
        } elseif ($streak_data->max_streak >= 3) {
            echo "(Хороший результат)\n";
        } else {
            echo "(Есть потенциал для улучшения)\n";
        }
    }
} else {
    echo "❌ Пользователь не активен в системе баллов\n";
    echo "💡 Возможные причины:\n";
    echo "   - Не проходил уроки\n";
    echo "   - Нет доступа к курсам\n";
    echo "   - Техническая ошибка в начислении\n";
}

echo "\n=== АНАЛИЗ ЗАВЕРШЕН ===\n";

if ($is_web_request) {
    echo "</pre>";
}
?>