<?php
/**
 * Тестовый скрипт для проверки системы начисления баллов
 * 
 * Этот скрипт симулирует прохождение уроков в разные дни и проверяет
 * правильность начисления баллов согласно правилам системы.
 */

// Подключение к WordPress
require_once('wp-load.php');

// ⚠️  ВНИМАНИЕ: Этот тестовый файл использует старую архитектуру!
// Система мигрирована на Custom Post Types, но сервис баллов еще не подключен к хукам
// Данный файл НЕ РАБОТАЕТ с актуальной архитектурой

// Подключение WordPress helper функций вместо старых классов
if (file_exists('wp-content/themes/cryptoschool/inc/wpml-helpers.php')) {
    require_once('wp-content/themes/cryptoschool/inc/wpml-helpers.php');
}

echo "❌ ТЕСТОВЫЙ ФАЙЛ УСТАРЕЛ!\n";
echo "Система мигрирована на Custom Post Types, но система баллов не интегрирована.\n";
echo "Используйте test-real-user-points.php для анализа текущего состояния.\n\n";
exit;

/**
 * Класс для тестирования системы начисления баллов
 */
class PointsSystemTester {
    /**
     * ID тестового пользователя
     */
    private $test_user_id;

    /**
     * Массив ID уроков для тестирования
     */
    private $lesson_ids = [];

    /**
     * Ожидаемые значения баллов
     */
    private $expected_total_points = 0;
    private $expected_streak_points = 0;
    private $expected_multi_lesson_points = 0;
    private $expected_course_completion_points = 0;
    private $expected_lesson_points = 0;

    /**
     * Конструктор
     */
    public function __construct() {
        global $wpdb;

        // Создаем тестового пользователя
        $username = 'test_user_' . time();
        $email = 'test_' . time() . '@example.com';
        $this->test_user_id = wp_create_user($username, 'password', $email);
        
        // Назначаем пользователю роль студента
        $user = new WP_User($this->test_user_id);
        $user->set_role('cryptoschool_student');
        
        // Предоставляем доступ к пакету курсов
        $this->grant_package_access();

        // Получаем список уроков для тестирования из Custom Post Types
        $lessons = get_posts([
            'post_type' => 'cryptoschool_lesson',
            'post_status' => 'publish',
            'numberposts' => 15,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ]);
        
        if (empty($lessons)) {
            die("Ошибка: Не найдены уроки для тестирования. Убедитесь, что созданы Custom Post Types уроков.\n");
        }
        
        $this->lesson_ids = array_map(function($lesson) { return $lesson->ID; }, $lessons);

        // Очищаем данные перед тестированием
        $this->clean_test_data();

        echo "Создан тестовый пользователь: $username (ID: {$this->test_user_id})\n";
        echo "Получено " . count($this->lesson_ids) . " уроков для тестирования\n";
    }

    /**
     * Очистка тестовых данных
     */
    private function clean_test_data() {
        global $wpdb;
        
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}cryptoschool_user_streak WHERE user_id = %d", $this->test_user_id));
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}cryptoschool_points_history WHERE user_id = %d", $this->test_user_id));
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}cryptoschool_user_leaderboard WHERE user_id = %d", $this->test_user_id));
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}cryptoschool_user_lesson_progress WHERE user_id = %d", $this->test_user_id));
        
        echo "Тестовые данные очищены\n";
    }

    /**
     * Симуляция прохождения урока в определенную дату
     */
    public function simulate_lesson_completion($lesson_index, $date) {
        global $wpdb;
        
        if (!isset($this->lesson_ids[$lesson_index])) {
            echo "Ошибка: Урок с индексом $lesson_index не найден\n";
            return false;
        }
        
        $lesson_id = $this->lesson_ids[$lesson_index];
        
        // Сохраняем текущую дату
        $current_date = current_time('mysql');
        
        // Устанавливаем нужную дату для тестирования
        // Примечание: это не работает напрямую в MySQL, но мы можем обойти это,
        // манипулируя датами в нашем коде
        
        // Получаем урок
        $lesson_repository = new CryptoSchool_Repository_Lesson();
        $lesson = $lesson_repository->find($lesson_id);
        
        if (!$lesson) {
            echo "Ошибка: Урок с ID $lesson_id не найден\n";
            return false;
        }
        
        // Получаем или создаем запись о серии пользователя
        $streak_repository = new CryptoSchool_Repository_User_Streak();
        $streak = $streak_repository->get_by_user_id($this->test_user_id);
        
        if (!$streak) {
            $streak = new CryptoSchool_Model_User_Streak();
            $streak->user_id = $this->test_user_id;
            $streak->current_streak = 0;
            $streak->max_streak = 0;
            $streak->last_activity_date = '0000-00-00';
            $streak->lessons_today = 0;
            $streak->created_at = $date . ' 00:00:00';
            $streak->updated_at = $date . ' 00:00:00';
            $streak_repository->create_or_update($streak->user_id, [
                'user_id' => $streak->user_id,
                'current_streak' => $streak->current_streak,
                'max_streak' => $streak->max_streak,
                'last_activity_date' => $streak->last_activity_date,
                'lessons_today' => $streak->lessons_today,
                'created_at' => $streak->created_at,
                'updated_at' => $streak->updated_at
            ]);
        }
        
        // Манипулируем датой последней активности для симуляции
        $last_activity_date = $streak->last_activity_date;
        
        // Проверяем, является ли текущий день новым днем
        if ($date > $last_activity_date) {
            // Новый день
            
            // Проверка, был ли пропущен день
            $yesterday = date('Y-m-d', strtotime($date . ' -1 day'));
            
            if ($last_activity_date != '0000-00-00' && $last_activity_date < $yesterday) {
                // Пропущен день, сбрасываем серию
                $streak->current_streak = 0;
            } else if ($last_activity_date != '0000-00-00') {
                // День не пропущен, увеличиваем серию
                $streak->current_streak++;
            }
            
            // Обновляем максимальную серию
            if ($streak->current_streak > $streak->max_streak) {
                $streak->max_streak = $streak->current_streak;
            }
            
            // Сбрасываем счетчик уроков за день
            $streak->lessons_today = 0;
        }
        
        // Увеличиваем счетчик уроков за день
        $streak->lessons_today++;
        $lessons_today = $streak->lessons_today;
        
        // Обновляем дату последней активности
        $streak->last_activity_date = $date;
        $streak->updated_at = $date . ' 00:00:00';
        $streak_repository->create_or_update($streak->user_id, [
            'current_streak' => $streak->current_streak,
            'max_streak' => $streak->max_streak,
            'last_activity_date' => $streak->last_activity_date,
            'lessons_today' => $streak->lessons_today,
            'updated_at' => $streak->updated_at
        ]);
        
        // Создаем запись о прохождении урока
        $progress_repository = new CryptoSchool_Repository_User_Lesson_Progress();
        $progress = new CryptoSchool_Model_User_Lesson_Progress();
        $progress->user_id = $this->test_user_id;
        $progress->lesson_id = $lesson_id;
        $progress->is_completed = 1;
        $progress->progress_percent = 100;
        $progress->completed_at = $date . ' 00:00:00';
        $progress->updated_at = $date . ' 00:00:00';
        $progress_repository->create([
            'user_id' => $progress->user_id,
            'lesson_id' => $progress->lesson_id,
            'is_completed' => $progress->is_completed,
            'progress_percent' => $progress->progress_percent,
            'completed_at' => $progress->completed_at,
            'updated_at' => $progress->updated_at
        ]);
        
        // Начисляем баллы за урок
        $lesson_points = $lesson->completion_points ?? 5;
        $this->add_points(
            $lesson_points,
            'lesson',
            $lesson_id,
            null,
            null,
            sprintf('Завершение урока "%s"', $lesson->title),
            $date . ' 00:00:00'
        );
        $this->expected_lesson_points += $lesson_points;
        $this->expected_total_points += $lesson_points;
        
        // Начисляем баллы за серию (если это первый урок за день)
        if ($lessons_today === 1 && $streak->current_streak > 0) {
            $streak_day = $streak->current_streak;
            $streak_points = $this->calculate_streak_points($streak_day);
            
            if ($streak_points > 0) {
                $this->add_points(
                    $streak_points,
                    'streak',
                    null,
                    $streak_day,
                    null,
                    sprintf('Бонус за %d день серии', $streak_day),
                    $date . ' 00:00:00'
                );
                $this->expected_streak_points += $streak_points;
                $this->expected_total_points += $streak_points;
            }
        }
        
        // Начисляем баллы за прохождение нескольких уроков в день
        $multi_lesson_points = $this->calculate_multi_lesson_points($streak->current_streak, $lessons_today);
        
        if ($multi_lesson_points > 0) {
            $this->add_points(
                $multi_lesson_points,
                'multi_lesson',
                $lesson_id,
                null,
                $lessons_today,
                sprintf('Бонус за %d урок за день', $lessons_today),
                $date . ' 00:00:00'
            );
            $this->expected_multi_lesson_points += $multi_lesson_points;
            $this->expected_total_points += $multi_lesson_points;
        }
        
        // Проверяем, завершен ли курс
        // Для простоты тестирования, считаем курс завершенным, если это последний урок в нашем списке
        if ($lesson_index === count($this->lesson_ids) - 1) {
            $course_completion_points = 50; // Бонус за завершение курса
            
            $this->add_points(
                $course_completion_points,
                'course_completion',
                null,
                null,
                null,
                'Бонус за завершение курса',
                $date . ' 00:00:00'
            );
            $this->expected_course_completion_points += $course_completion_points;
            $this->expected_total_points += $course_completion_points;
        }
        
        echo "  Урок #" . ($lesson_index + 1) . " (ID: $lesson_id) пройден\n";
        echo "  Текущая серия: {$streak->current_streak} дней\n";
        echo "  Уроков сегодня: {$streak->lessons_today}\n";
        
        return true;
    }

    /**
     * Расчет баллов за серию
     */
    private function calculate_streak_points($streak_day) {
        // Если это первый день серии, баллы не начисляются
        if ($streak_day <= 1) {
            return 0;
        }
        
        // За второй и последующие дни начисляется по 5 баллов
        return 5;
    }

    /**
     * Расчет баллов за прохождение нескольких уроков в день
     */
    private function calculate_multi_lesson_points($streak_day, $lesson_number) {
        // Если это первый урок за день или первый день серии, баллы не начисляются
        if ($lesson_number <= 1 || $streak_day <= 1) {
            return 0;
        }
        
        // За второй и последующие уроки начисляется по 5 баллов
        return 5;
    }

    /**
     * Добавление баллов
     */
    private function add_points($points, $points_type, $lesson_id = null, $streak_day = null, $lesson_number_today = null, $description = '', $created_at = null) {
        global $wpdb;
        
        if ($created_at === null) {
            $created_at = current_time('mysql');
        }
        
        // Добавляем запись в историю баллов
        $points_history_table = $wpdb->prefix . 'cryptoschool_points_history';
        $wpdb->insert(
            $points_history_table,
            [
                'user_id' => $this->test_user_id,
                'lesson_id' => $lesson_id,
                'points' => $points,
                'points_type' => $points_type,
                'streak_day' => $streak_day,
                'lesson_number_today' => $lesson_number_today,
                'description' => $description,
                'created_at' => $created_at
            ]
        );
        
        // Обновляем общее количество баллов в таблице рейтинга
        $leaderboard_table = $wpdb->prefix . 'cryptoschool_user_leaderboard';
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $leaderboard_table WHERE user_id = %d",
            $this->test_user_id
        ));
        
        if ($exists) {
            $wpdb->query($wpdb->prepare(
                "UPDATE $leaderboard_table SET total_points = total_points + %d, last_updated = %s WHERE user_id = %d",
                $points, $created_at, $this->test_user_id
            ));
        } else {
            $wpdb->insert(
                $leaderboard_table,
                [
                    'user_id' => $this->test_user_id,
                    'total_points' => $points,
                    'user_rank' => 0,
                    'completed_lessons' => 0,
                    'days_active' => 0,
                    'last_updated' => $created_at
                ]
            );
        }
    }

    /**
     * Проверка начисленных баллов
     */
    public function check_points() {
        global $wpdb;
        
        // Получаем общее количество баллов
        $leaderboard_table = $wpdb->prefix . 'cryptoschool_user_leaderboard';
        $total_points = $wpdb->get_var($wpdb->prepare(
            "SELECT total_points FROM $leaderboard_table WHERE user_id = %d",
            $this->test_user_id
        ));
        
        if ($total_points === null) {
            $total_points = 0;
        }
        
        // Получаем баллы по типам
        $points_history_table = $wpdb->prefix . 'cryptoschool_points_history';
        
        $lesson_points = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(points) FROM $points_history_table WHERE user_id = %d AND points_type = 'lesson'",
            $this->test_user_id
        ));
        
        $streak_points = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(points) FROM $points_history_table WHERE user_id = %d AND points_type = 'streak'",
            $this->test_user_id
        ));
        
        $multi_lesson_points = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(points) FROM $points_history_table WHERE user_id = %d AND points_type = 'multi_lesson'",
            $this->test_user_id
        ));
        
        $course_completion_points = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(points) FROM $points_history_table WHERE user_id = %d AND points_type = 'course_completion'",
            $this->test_user_id
        ));
        
        if ($lesson_points === null) $lesson_points = 0;
        if ($streak_points === null) $streak_points = 0;
        if ($multi_lesson_points === null) $multi_lesson_points = 0;
        if ($course_completion_points === null) $course_completion_points = 0;
        
        // Проверяем соответствие ожидаемым значениям
        $total_ok = ($total_points == $this->expected_total_points);
        $lesson_ok = ($lesson_points == $this->expected_lesson_points);
        $streak_ok = ($streak_points == $this->expected_streak_points);
        $multi_lesson_ok = ($multi_lesson_points == $this->expected_multi_lesson_points);
        $course_completion_ok = ($course_completion_points == $this->expected_course_completion_points);
        
        echo "\nРезультаты проверки:\n";
        echo "  Общие баллы: " . ($total_ok ? "OK" : "ОШИБКА") . 
             " (ожидалось: {$this->expected_total_points}, получено: {$total_points})\n";
        echo "  Баллы за уроки: " . ($lesson_ok ? "OK" : "ОШИБКА") . 
             " (ожидалось: {$this->expected_lesson_points}, получено: {$lesson_points})\n";
        echo "  Баллы за серию: " . ($streak_ok ? "OK" : "ОШИБКА") . 
             " (ожидалось: {$this->expected_streak_points}, получено: {$streak_points})\n";
        echo "  Баллы за мульти-уроки: " . ($multi_lesson_ok ? "OK" : "ОШИБКА") . 
             " (ожидалось: {$this->expected_multi_lesson_points}, получено: {$multi_lesson_points})\n";
        echo "  Баллы за завершение курса: " . ($course_completion_ok ? "OK" : "ОШИБКА") . 
             " (ожидалось: {$this->expected_course_completion_points}, получено: {$course_completion_points})\n";
        
        return $total_ok && $lesson_ok && $streak_ok && $multi_lesson_ok && $course_completion_ok;
    }

    /**
     * Запуск тестирования по заданному расписанию
     */
    public function run_test_schedule($test_schedule) {
        echo "Начинаем тестирование системы начисления баллов...\n";
        
        foreach ($test_schedule as $date => $lesson_indexes) {
            echo "\nСимуляция даты: $date\n";
            
            foreach ($lesson_indexes as $lesson_index) {
                $this->simulate_lesson_completion($lesson_index, $date);
            }
        }
        
        $result = $this->check_points();
        
        echo "\nТестирование " . ($result ? "успешно завершено" : "завершено с ошибками") . ".\n";
        
        return $result;
    }

    /**
     * Получение информации о тестовом пользователе
     */
    public function get_test_user_id() {
        return $this->test_user_id;
    }

    /**
     * Предоставление доступа к пакету курсов
     */
    private function grant_package_access() {
        global $wpdb;
        
        // Получаем ID пакета (в примере используется пакет с ID 1)
        $package_id = 1;
        
        // Проверяем, существует ли пакет
        $package_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}cryptoschool_packages WHERE id = %d",
            $package_id
        ));
        
        if (!$package_exists) {
            echo "Предупреждение: Пакет с ID $package_id не найден. Доступ не предоставлен.\n";
            return;
        }
        
        // Текущая дата
        $current_date = current_time('mysql');
        
        // Дата окончания доступа (1 год)
        $access_end = date('Y-m-d H:i:s', strtotime('+1 year', strtotime($current_date)));
        
        // Добавляем запись в таблицу доступов
        $wpdb->insert(
            $wpdb->prefix . 'cryptoschool_user_access',
            [
                'user_id' => $this->test_user_id,
                'package_id' => $package_id,
                'access_start' => $current_date,
                'access_end' => $access_end,
                'status' => 'active',
                'telegram_status' => 'none',
                'created_at' => $current_date,
                'updated_at' => $current_date
            ]
        );
        
        echo "Предоставлен доступ к пакету #$package_id до $access_end\n";
    }
    
    /**
     * Получение детальной истории начисления баллов
     */
    public function get_points_history() {
        global $wpdb;
        
        $points_history_table = $wpdb->prefix . 'cryptoschool_points_history';
        $history = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $points_history_table WHERE user_id = %d ORDER BY created_at",
            $this->test_user_id
        ));
        
        echo "\nИстория начисления баллов:\n";
        
        foreach ($history as $entry) {
            echo "  " . date('Y-m-d', strtotime($entry->created_at)) . 
                 " | " . str_pad($entry->points_type, 16, ' ', STR_PAD_RIGHT) . 
                 " | " . str_pad($entry->points, 3, ' ', STR_PAD_LEFT) . " баллов" . 
                 " | " . $entry->description . "\n";
        }
    }
}

// Запуск тестирования

// Создаем экземпляр тестера
$tester = new PointsSystemTester();

// Определяем тестовый сценарий
// Ключи - даты, значения - массивы индексов уроков (начиная с 0)
$test_schedule = [
    '2025-05-01' => [0, 1],         // День 1: 2 урока
    '2025-05-03' => [2, 3],         // День 3: 2 урока (пропуск дня 2)
    '2025-05-05' => [4, 5, 6],      // День 5: 3 урока (пропуск дня 4)
    '2025-05-06' => [7, 8],         // День 6: 2 урока
    '2025-05-08' => [9],            // День 8: 1 урок (пропуск дня 7)
    '2025-05-09' => [10],           // День 9: 1 урок
    '2025-05-10' => [11],           // День 10: 1 урок
    '2025-05-11' => [12],           // День 11: 1 урок
    '2025-05-12' => [13],           // День 12: 1 урок
    '2025-05-13' => [14]            // День 13: 1 урок (последний)
];

// Запускаем тестирование
$tester->run_test_schedule($test_schedule);

// Выводим детальную историю начисления баллов
$tester->get_points_history();

// Выводим ID тестового пользователя для возможного дальнейшего анализа
echo "\nID тестового пользователя: " . $tester->get_test_user_id() . "\n";
