<?php
/**
 * Скрипт для очистки прогресса пользователя
 * Удаляет все данные об уроках, баллах и серии для чистого эксперимента
 */

// Подключение к WordPress
require_once('wp-load.php');

$user_id = 6;

echo "=== ОЧИСТКА ДАННЫХ ПОЛЬЗОВАТЕЛЯ ID=$user_id ===\n\n";

// Проверяем, что пользователь существует
$user_info = get_userdata($user_id);
if (!$user_info) {
    die("❌ Пользователь с ID $user_id не найден\n");
}

echo "👤 Очистка данных для: {$user_info->user_login}\n\n";

global $wpdb;

// Подсчитываем данные до очистки
echo "📊 === ДАННЫЕ ДО ОЧИСТКИ ===\n";

$lessons_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}cryptoschool_user_lesson_progress WHERE user_id = %d",
    $user_id
));

$points_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}cryptoschool_points_history WHERE user_id = %d",
    $user_id
));

$streak_exists = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}cryptoschool_user_streak WHERE user_id = %d",
    $user_id
));

$leaderboard_exists = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}cryptoschool_user_leaderboard WHERE user_id = %d",
    $user_id
));

echo "📚 Записей о прогрессе уроков: $lessons_count\n";
echo "💰 Записей истории баллов: $points_count\n";
echo "🔥 Записей о серии: $streak_exists\n";
echo "🏆 Записей в рейтинге: $leaderboard_exists\n\n";

// Показываем детали уроков перед удалением
if ($lessons_count > 0) {
    echo "📖 === УРОКИ КОТОРЫЕ БУДУТ ОЧИЩЕНЫ ===\n";
    $lessons_details = $wpdb->get_results($wpdb->prepare(
        "SELECT lesson_id, is_completed, progress_percent, completed_at 
         FROM {$wpdb->prefix}cryptoschool_user_lesson_progress 
         WHERE user_id = %d 
         ORDER BY completed_at DESC",
        $user_id
    ));
    
    foreach ($lessons_details as $lesson) {
        $status = $lesson->is_completed ? '✅ Завершен' : '⏳ В процессе';
        $date = $lesson->completed_at ? date('d.m.Y H:i', strtotime($lesson->completed_at)) : 'Не завершен';
        echo "   Урок ID {$lesson->lesson_id}: $status ({$lesson->progress_percent}%) - $date\n";
    }
    echo "\n";
}

// Запрос подтверждения
echo "⚠️  === ВНИМАНИЕ ===\n";
echo "Будут ПОЛНОСТЬЮ УДАЛЕНЫ следующие данные пользователя $user_id:\n";
echo "❌ Весь прогресс по урокам\n";
echo "❌ Вся история баллов\n";
echo "❌ Данные о серии\n";
echo "❌ Позиция в рейтинге\n\n";

echo "Продолжить очистку? (yes/no): ";
$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));
fclose($handle);

if (strtolower($confirmation) !== 'yes' && strtolower($confirmation) !== 'y') {
    echo "❌ Очистка отменена пользователем\n";
    exit;
}

echo "\n🧹 === НАЧИНАЕМ ОЧИСТКУ ===\n";

// 1. Очищаем прогресс уроков
$deleted_lessons = $wpdb->query($wpdb->prepare(
    "DELETE FROM {$wpdb->prefix}cryptoschool_user_lesson_progress WHERE user_id = %d",
    $user_id
));
echo "📚 Удалено записей прогресса уроков: $deleted_lessons\n";

// 2. Очищаем историю баллов
$deleted_points = $wpdb->query($wpdb->prepare(
    "DELETE FROM {$wpdb->prefix}cryptoschool_points_history WHERE user_id = %d",
    $user_id
));
echo "💰 Удалено записей истории баллов: $deleted_points\n";

// 3. Очищаем серию
$deleted_streak = $wpdb->query($wpdb->prepare(
    "DELETE FROM {$wpdb->prefix}cryptoschool_user_streak WHERE user_id = %d",
    $user_id
));
echo "🔥 Удалено записей о серии: $deleted_streak\n";

// 4. Очищаем рейтинг
$deleted_leaderboard = $wpdb->query($wpdb->prepare(
    "DELETE FROM {$wpdb->prefix}cryptoschool_user_leaderboard WHERE user_id = %d",
    $user_id
));
echo "🏆 Удалено записей в рейтинге: $deleted_leaderboard\n\n";

// Проверяем результат
echo "✅ === РЕЗУЛЬТАТ ОЧИСТКИ ===\n";

$remaining_lessons = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}cryptoschool_user_lesson_progress WHERE user_id = %d",
    $user_id
));

$remaining_points = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}cryptoschool_points_history WHERE user_id = %d",
    $user_id
));

$remaining_streak = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}cryptoschool_user_streak WHERE user_id = %d",
    $user_id
));

$remaining_leaderboard = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}cryptoschool_user_leaderboard WHERE user_id = %d",
    $user_id
));

echo "📚 Осталось записей уроков: $remaining_lessons\n";
echo "💰 Осталось записей баллов: $remaining_points\n";
echo "🔥 Осталось записей серии: $remaining_streak\n";
echo "🏆 Осталось записей рейтинга: $remaining_leaderboard\n\n";

if ($remaining_lessons == 0 && $remaining_points == 0 && $remaining_streak == 0 && $remaining_leaderboard == 0) {
    echo "🎉 ОЧИСТКА ЗАВЕРШЕНА УСПЕШНО!\n";
    echo "✅ Пользователь $user_id готов для чистого эксперимента\n\n";
    
    echo "📋 === СЛЕДУЮЩИЕ ШАГИ ===\n";
    echo "1. Войдите в систему под пользователем {$user_info->user_login}\n";
    echo "2. Перейдите на страницу курсов\n";
    echo "3. Пройдите несколько уроков\n";
    echo "4. Запустите test-real-user-points.php для проверки баллов\n";
    echo "5. Проверьте блок daily-progress на странице курсов\n\n";
    
    echo "🔍 Если баллы не начисляются - проблема в системе хуков!\n";
} else {
    echo "❌ ОШИБКА: Не все данные были удалены!\n";
    echo "Возможны проблемы с правами доступа к БД или внешними ключами\n";
}

echo "\n=== ОЧИСТКА ЗАВЕРШЕНА ===\n";
?>