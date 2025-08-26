<?php
// Загружаем WordPress
require_once('wp-load.php');

echo "=== Тест блока 'Останні завдання' (обновленный) ===\n\n";

// Тестируем пользователей
$test_users = [6, 16];

foreach ($test_users as $user_id) {
    echo "=== Пользователь ID: $user_id ===\n";
    
    // Проверяем активный урок
    $active_lesson = cryptoschool_get_user_active_lesson($user_id);
    if ($active_lesson) {
        echo "Активный урок найден:\n";
        echo "- ID урока: " . $active_lesson['lesson_id'] . "\n";
        echo "- Название: " . $active_lesson['lesson_title'] . "\n";
        echo "- Курс: " . $active_lesson['course_title'] . "\n";
        echo "- Баллы: " . $active_lesson['completion_points'] . "\n";
    } else {
        echo "Активный урок не найден\n";
    }
    
    // Проверяем завершенные уроки
    $completed_lessons = cryptoschool_get_user_completed_lessons($user_id, 3);
    echo "\nЗавершенные уроки (" . count($completed_lessons) . "):\n";
    
    if (empty($completed_lessons)) {
        echo "- Нет завершенных уроков\n";
    } else {
        foreach ($completed_lessons as $lesson) {
            echo "- " . $lesson['lesson_title'] . " (" . $lesson['course_title'] . ") - Завершен: " . $lesson['completed_at'] . "\n";
        }
    }
    
    // Проверяем пакеты пользователя
    global $wpdb;
    $packages = $wpdb->get_results($wpdb->prepare(
        "SELECT p.id, p.title, p.course_ids 
         FROM {$wpdb->prefix}cryptoschool_user_access ua
         JOIN {$wpdb->prefix}cryptoschool_packages p ON ua.package_id = p.id
         WHERE ua.user_id = %d AND ua.status = 'active'",
        $user_id
    ));
    
    echo "\nПакеты пользователя:\n";
    if (empty($packages)) {
        echo "- Нет активных пакетов\n";
    } else {
        foreach ($packages as $package) {
            echo "- " . $package->title . " (ID: " . $package->id . ") - Course IDs: " . $package->course_ids . "\n";
        }
    }
    
    echo "\n" . str_repeat("-", 50) . "\n";
}

// Проверяем также прогресс напрямую из БД для пользователя 6
echo "\n=== Прямая проверка БД для пользователя 6 ===\n";
$progress = $wpdb->get_results($wpdb->prepare(
    "SELECT lesson_id, is_completed, progress_percent, completed_at 
     FROM {$wpdb->prefix}cryptoschool_user_lesson_progress 
     WHERE user_id = %d",
    6
));

echo "Записи прогресса в БД:\n";
if (empty($progress)) {
    echo "- Нет записей прогресса\n";
} else {
    foreach ($progress as $p) {
        echo "- Lesson ID (trid): " . $p->lesson_id . ", Завершен: " . ($p->is_completed ? 'Да' : 'Нет') . ", Прогресс: " . $p->progress_percent . "%, Дата: " . $p->completed_at . "\n";
    }
}

// Проверяем курсы Custom Post Types
echo "\n=== Custom Post Types курсы ===\n";
$courses = get_posts([
    'post_type' => 'cryptoschool_course',
    'post_status' => 'publish',
    'numberposts' => -1,
    'orderby' => 'menu_order',
    'order' => 'ASC'
]);

echo "Найдено курсов: " . count($courses) . "\n";
foreach ($courses as $course) {
    $table_id = get_post_meta($course->ID, '_cryptoschool_table_id', true);
    echo "- " . $course->post_title . " (Post ID: " . $course->ID . ", Table ID: " . ($table_id ?: 'НЕТ') . ")\n";
    
    // Проверяем уроки курса
    $lessons = get_field('choose_lesson', $course->ID);
    if ($lessons) {
        echo "  Уроки:\n";
        foreach ($lessons as $lesson) {
            $lesson_id = is_object($lesson) ? $lesson->ID : $lesson;
            $lesson_post = get_post($lesson_id);
            if ($lesson_post) {
                echo "    - " . $lesson_post->post_title . " (ID: " . $lesson_id . ")\n";
            }
        }
    }
}
?>