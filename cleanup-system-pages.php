<?php
/**
 * Скрипт для очистки системных страниц из user_lesson_progress
 * Удаляет записи которые не являются реальными уроками
 */

// Подключение к WordPress
require_once('wp-load.php');

// Проверяем, запущен ли скрипт через браузер
$is_web_request = !empty($_SERVER['HTTP_HOST']);

if ($is_web_request) {
    echo "<pre style='background: #1e1e1e; color: #fff; padding: 20px; font-family: monospace; line-height: 1.5;'>";
}

echo "=== ОЧИСТКА СИСТЕМНЫХ СТРАНИЦ ИЗ user_lesson_progress ===\n\n";

global $wpdb;

// Получаем все записи прогресса уроков
$all_progress = $wpdb->get_results(
    "SELECT id, user_id, lesson_id, is_completed, completed_at 
     FROM {$wpdb->prefix}cryptoschool_user_lesson_progress 
     ORDER BY user_id, completed_at DESC"
);

echo "📊 Всего записей в user_lesson_progress: " . count($all_progress) . "\n\n";

$system_pages = [];
$real_lessons = [];
$to_delete = [];

foreach ($all_progress as $progress) {
    // Сначала пробуем найти по trid (WPML)
    $lesson_id_by_trid = $wpdb->get_var($wpdb->prepare(
        "SELECT element_id FROM {$wpdb->prefix}icl_translations 
         WHERE trid = %d AND element_type = %s AND language_code = %s",
        $progress->lesson_id, 'post_cryptoschool_lesson', apply_filters('wpml_current_language', null)
    ));
    
    $lesson_post = null;
    $lesson_type = "unknown";
    
    if ($lesson_id_by_trid) {
        $lesson_post = get_post($lesson_id_by_trid);
        $lesson_type = "trid->post";
    } else {
        // Fallback: пробуем lesson_id как Post ID
        $lesson_post = get_post($progress->lesson_id);
        $lesson_type = "direct_id";
    }
    
    // Проверяем, является ли это реальным уроком
    $is_real_lesson = ($lesson_post && $lesson_post->post_type === 'cryptoschool_lesson');
    $lesson_title = $lesson_post ? $lesson_post->post_title : "Lesson ID {$progress->lesson_id}";
    
    if ($is_real_lesson) {
        $real_lessons[] = [
            'id' => $progress->id,
            'user_id' => $progress->user_id,
            'lesson_id' => $progress->lesson_id,
            'title' => $lesson_title,
            'type' => $lesson_type
        ];
    } else {
        $system_pages[] = [
            'id' => $progress->id,
            'user_id' => $progress->user_id,
            'lesson_id' => $progress->lesson_id,
            'title' => $lesson_title,
            'post_type' => $lesson_post ? $lesson_post->post_type : 'not_found',
            'type' => $lesson_type,
            'completed_at' => $progress->completed_at
        ];
        $to_delete[] = $progress->id;
    }
}

echo "✅ Реальных уроков: " . count($real_lessons) . "\n";
echo "❌ Системных страниц: " . count($system_pages) . "\n\n";

if (!empty($system_pages)) {
    echo "📋 === СИСТЕМНЫЕ СТРАНИЦЫ КОТОРЫЕ БУДУТ УДАЛЕНЫ ===\n";
    foreach ($system_pages as $page) {
        $date = $page['completed_at'] ? date('d.m.Y', strtotime($page['completed_at'])) : 'No date';
        echo "   ID {$page['id']}: User {$page['user_id']} | Lesson ID {$page['lesson_id']} | {$page['title']} | Type: {$page['post_type']} | Date: $date\n";
    }
    echo "\n";
    
    echo "⚠️  === ПОДТВЕРЖДЕНИЕ ===\n";
    echo "Удалить " . count($system_pages) . " системных страниц из базы данных?\n";
    echo "Это НЕ повлияет на реальный прогресс пользователей по урокам.\n\n";
    
    echo "Продолжить удаление? (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $confirmation = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($confirmation) === 'yes' || strtolower($confirmation) === 'y') {
        echo "\n🧹 === УДАЛЕНИЕ СИСТЕМНЫХ СТРАНИЦ ===\n";
        
        foreach ($to_delete as $record_id) {
            $deleted = $wpdb->delete(
                $wpdb->prefix . 'cryptoschool_user_lesson_progress',
                ['id' => $record_id],
                ['%d']
            );
            
            if ($deleted) {
                echo "   ✅ Удалена запись ID: $record_id\n";
            } else {
                echo "   ❌ Ошибка удаления записи ID: $record_id\n";
            }
        }
        
        echo "\n✅ === РЕЗУЛЬТАТ ===\n";
        $remaining_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}cryptoschool_user_lesson_progress"
        );
        echo "Записей осталось в таблице: $remaining_count\n";
        echo "Из них реальных уроков: " . count($real_lessons) . "\n\n";
        
        // Проверяем результат
        echo "🔍 === ПРОВЕРКА ===\n";
        $remaining_system = $wpdb->get_results(
            "SELECT DISTINCT lesson_id FROM {$wpdb->prefix}cryptoschool_user_lesson_progress"
        );
        
        $still_have_system = 0;
        foreach ($remaining_system as $record) {
            $post = get_post($record->lesson_id);
            if (!$post || $post->post_type !== 'cryptoschool_lesson') {
                $still_have_system++;
            }
        }
        
        if ($still_have_system == 0) {
            echo "🎉 УСПЕШНО! Все системные страницы удалены\n";
            echo "✅ В таблице остались только реальные уроки\n\n";
            
            echo "📋 === СЛЕДУЮЩИЕ ШАГИ ===\n";
            echo "1. Запустите test-real-user-points.php для проверки\n";
            echo "2. Протестируйте прохождение нового урока\n";
            echo "3. Проверьте начисление баллов\n";
        } else {
            echo "⚠️  Внимание: $still_have_system системных записей всё ещё остались\n";
            echo "Возможно потребуется дополнительная очистка\n";
        }
    } else {
        echo "❌ Удаление отменено пользователем\n";
    }
} else {
    echo "🎉 В таблице нет системных страниц!\n";
    echo "✅ Все записи являются реальными уроками\n";
}

echo "\n=== ОЧИСТКА ЗАВЕРШЕНА ===\n";

if ($is_web_request) {
    echo "</pre>";
}
?>