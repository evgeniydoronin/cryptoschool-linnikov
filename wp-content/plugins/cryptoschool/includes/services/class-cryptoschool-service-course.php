<?php
/**
 * Сервис для работы с курсами
 *
 * @package CryptoSchool
 * @subpackage Services
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс сервиса для работы с курсами
 */
class CryptoSchool_Service_Course extends CryptoSchool_Service {
    /**
     * Репозиторий курсов
     *
     * @var CryptoSchool_Repository_Course
     */
    protected $repository;

    /**
     * Конструктор
     *
     * @param CryptoSchool_Loader $loader Экземпляр загрузчика
     */
    public function __construct(CryptoSchool_Loader $loader) {
        parent::__construct($loader);
        $this->repository = new CryptoSchool_Repository_Course();
    }

    /**
     * Регистрация хуков и фильтров
     *
     * @return void
     */
    protected function register_hooks() {
        // В базовом сервисе нет хуков
    }

    /**
     * Получение всех курсов
     *
     * @param array $args Аргументы для фильтрации и сортировки
     * @return array
     */
    public function get_all($args = []) {
        return $this->repository->get_courses($args);
    }

    /**
     * Получение количества курсов
     *
     * @param array $args Аргументы для фильтрации
     * @return int
     */
    public function get_count($args = []) {
        return $this->repository->get_courses_count($args);
    }

    /**
     * Получение курса по ID
     *
     * @param int $id ID курса
     * @return mixed
     */
    public function get_by_id($id) {
        // Отладочный вывод
        error_log('Service get_by_id - ID: ' . $id);
        
        $course = $this->repository->find($id);
        
        // Отладочный вывод
        error_log('Service get_by_id - Result: ' . ($course ? 'найден' : 'не найден'));
        
        return $course;
    }

    /**
     * Получение курса по слагу
     *
     * @param string $slug Слаг курса
     * @return mixed
     */
    public function get_by_slug($slug) {
        return $this->repository->get_by_slug($slug);
    }

    /**
     * Создание курса
     *
     * @param array $data Данные курса
     * @return int|false ID созданного курса или false в случае ошибки
     */
    public function create($data) {
        // Генерация слага, если не указан
        if (empty($data['slug']) && !empty($data['title'])) {
            $data['slug'] = $this->repository->generate_unique_slug($data['title']);
        }

        // Установка порядка отображения, если не указан
        if (!isset($data['course_order'])) {
            $data['course_order'] = $this->get_next_order();
        }

        // Установка дат создания и обновления
        $data['created_at'] = current_time('mysql');
        $data['updated_at'] = current_time('mysql');

        return $this->repository->create($data);
    }

    /**
     * Обновление курса
     *
     * @param int   $id   ID курса
     * @param array $data Данные для обновления
     * @return bool
     */
    public function update($id, $data) {
        // Генерация слага, если изменилось название
        if (empty($data['slug']) && !empty($data['title'])) {
            $data['slug'] = $this->repository->generate_unique_slug($data['title'], $id);
        }

        // Установка даты обновления
        $data['updated_at'] = current_time('mysql');

        return $this->repository->update($id, $data);
    }

    /**
     * Удаление курса
     *
     * @param int $id ID курса
     * @return bool
     */
    public function delete($id) {
        return $this->repository->delete($id);
    }

    /**
     * Получение следующего порядкового номера для курса
     *
     * @return int
     */
    public function get_next_order() {
        global $wpdb;
        $table_name = $this->repository->get_table_name();

        $query = "SELECT MAX(course_order) FROM {$table_name}";
        $max_order = (int) $wpdb->get_var($query);

        return $max_order + 1;
    }

    /**
     * Обновление порядка курсов
     *
     * @param array $course_orders Массив с ID курсов и их порядком
     * @return bool
     */
    public function update_order($course_orders) {
        return $this->repository->update_order($course_orders);
    }


    /**
     * Получение уроков курса
     *
     * @param int   $course_id ID курса
     * @param array $args      Дополнительные аргументы
     * @return array
     */
    public function get_lessons($course_id, $args = []) {
        $lesson_repository = new CryptoSchool_Repository_Lesson();
        return $lesson_repository->get_course_lessons($course_id, $args);
    }

    /**
     * Получение количества уроков курса
     *
     * @param int   $course_id ID курса
     * @param array $args      Дополнительные аргументы
     * @return int
     */
    public function get_lessons_count($course_id, $args = []) {
        $lesson_repository = new CryptoSchool_Repository_Lesson();
        return $lesson_repository->get_course_lessons_count($course_id, $args);
    }

    /**
     * Получение пакетов, включающих курс
     *
     * @param int   $course_id ID курса
     * @param array $args      Дополнительные аргументы
     * @return array
     */
    public function get_packages($course_id, $args = []) {
        $package_repository = new CryptoSchool_Repository_Package();
        return $package_repository->get_packages_with_course($course_id, $args);
    }

    /**
     * Проверка, доступен ли курс пользователю
     *
     * @param int $course_id ID курса
     * @param int $user_id   ID пользователя
     * @return bool
     */
    public function is_available_for_user($course_id, $user_id) {
        $user_access_repository = new CryptoSchool_Repository_UserAccess();
        $access = $user_access_repository->get_user_course_access($user_id, $course_id);
        return $access !== null;
    }

    /**
     * Получение прогресса пользователя по курсу
     *
     * @param int $course_id ID курса
     * @param int $user_id   ID пользователя
     * @return array
     */
    public function get_user_progress($course_id, $user_id) {
        global $wpdb;
        $lessons_table = $wpdb->prefix . 'cryptoschool_lessons';
        $progress_table = $wpdb->prefix . 'cryptoschool_user_progress';

        // Получение общего количества уроков в курсе
        $total_lessons_query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$lessons_table} 
            WHERE course_id = %d AND is_active = 1",
            $course_id
        );
        $total_lessons = (int) $wpdb->get_var($total_lessons_query);

        if ($total_lessons === 0) {
            return [
                'percent' => 0,
                'completed_lessons' => 0,
                'total_lessons' => 0,
                'points' => 0,
            ];
        }

        // Получение количества пройденных уроков
        $completed_lessons_query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$progress_table} p
            INNER JOIN {$lessons_table} l ON p.lesson_id = l.id
            WHERE p.user_id = %d AND p.status = 'completed'
            AND l.course_id = %d AND l.is_active = 1",
            $user_id,
            $course_id
        );
        $completed_lessons = (int) $wpdb->get_var($completed_lessons_query);

        // Получение количества баллов
        $points_query = $wpdb->prepare(
            "SELECT SUM(p.points) FROM {$progress_table} p
            INNER JOIN {$lessons_table} l ON p.lesson_id = l.id
            WHERE p.user_id = %d AND l.course_id = %d",
            $user_id,
            $course_id
        );
        $points = (int) $wpdb->get_var($points_query);

        // Расчет процента прохождения
        $percent = ($completed_lessons / $total_lessons) * 100;

        return [
            'percent' => $percent,
            'completed_lessons' => $completed_lessons,
            'total_lessons' => $total_lessons,
            'points' => $points,
        ];
    }

    /**
     * Получение курсов, доступных пользователю
     *
     * @param int   $user_id ID пользователя
     * @param array $args    Дополнительные аргументы
     * @return array
     */
    public function get_user_courses($user_id, $args = []) {
        return $this->repository->get_user_courses($user_id, $args);
    }

    /**
     * Получение количества курсов, доступных пользователю
     *
     * @param int   $user_id ID пользователя
     * @param array $args    Дополнительные аргументы
     * @return int
     */
    public function get_user_courses_count($user_id, $args = []) {
        return $this->repository->get_user_courses_count($user_id, $args);
    }
}
