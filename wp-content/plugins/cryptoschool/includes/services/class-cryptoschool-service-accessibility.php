<?php
/**
 * Сервис доступности курсов и уроков
 *
 * @package CryptoSchool
 * @subpackage Services
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс сервиса доступности курсов и уроков
 */
class CryptoSchool_Service_Accessibility extends CryptoSchool_Service {
    /**
     * Регистрация хуков и фильтров
     *
     * @return void
     */
    protected function register_hooks() {
        // Пока не регистрируем никаких хуков, так как сервис используется напрямую
    }
    
    /**
     * Проверка доступности курса для пользователя
     * 
     * @param int $user_id ID пользователя
     * @param int $course_id ID курса
     * @return array Результат проверки: ['accessible' => bool, 'redirect_url' => string|null]
     */
    public function check_course_accessibility($user_id, $course_id) {
        // Если пользователь администратор, всегда даем доступ
        if (user_can($user_id, 'administrator')) {
            return ['accessible' => true, 'redirect_url' => null];
        }
        
        // Получаем репозитории
        $course_repository = new CryptoSchool_Repository_Course();
        
        // Получаем модель курса
        $course_model = $course_repository->find($course_id);
        if (!$course_model) {
            // Если курс не найден, перенаправляем на страницу курсов
            return [
                'accessible' => false, 
                'redirect_url' => site_url('/courses/'),
                'reason' => 'course_not_found'
            ];
        }
        
        // Проверяем, доступен ли курс для пользователя (есть ли у него доступ к пакету с этим курсом)
        $is_available = $course_model->is_available_for_user($user_id);
        if (!$is_available) {
            // Если курс недоступен, перенаправляем на страницу курсов
            return [
                'accessible' => false, 
                'redirect_url' => site_url('/courses/'),
                'reason' => 'no_access'
            ];
        }
        
        // Получаем все курсы, доступные пользователю
        $user_courses = $course_repository->get_user_courses($user_id, [
            'is_active' => 1,
            'orderby' => 'c.course_order',
            'order' => 'ASC'
        ]);
        
        // Проверяем, завершены ли все предыдущие курсы
        $previous_course_completed = true;
        foreach ($user_courses as $user_course) {
            $current_course_id = $user_course->getAttribute('id');
            
            // Если дошли до проверяемого курса, прерываем цикл
            if ($current_course_id == $course_id) {
                break;
            }
            
            // Проверяем, завершен ли текущий курс
            $progress = $user_course->get_user_progress($user_id);
            $is_completed = ($progress >= 100);
            
            // Если текущий курс не завершен, запоминаем это и прерываем цикл
            if (!$is_completed) {
                $previous_course_completed = false;
                $last_available_course_id = $current_course_id;
                break;
            }
        }
        
        // Если предыдущие курсы не завершены, перенаправляем на последний доступный курс
        if (!$previous_course_completed) {
            return [
                'accessible' => false, 
                'redirect_url' => site_url('/course/?id=' . $last_available_course_id),
                'reason' => 'previous_course_not_completed'
            ];
        }
        
        // Если все проверки пройдены, курс доступен
        return ['accessible' => true, 'redirect_url' => null];
    }
    
    /**
     * Проверка доступности урока для пользователя
     * 
     * @param int $user_id ID пользователя
     * @param int $lesson_id ID урока
     * @return array Результат проверки: ['accessible' => bool, 'redirect_url' => string|null]
     */
    public function check_lesson_accessibility($user_id, $lesson_id) {
        // Если пользователь администратор, всегда даем доступ
        if (user_can($user_id, 'administrator')) {
            return ['accessible' => true, 'redirect_url' => null];
        }
        
        // Получаем репозитории
        $lesson_repository = new CryptoSchool_Repository_Lesson();
        
        // Получаем модель урока
        $lesson_model = $lesson_repository->find($lesson_id);
        if (!$lesson_model) {
            // Если урок не найден, перенаправляем на страницу курсов
            return [
                'accessible' => false, 
                'redirect_url' => site_url('/courses/'),
                'reason' => 'lesson_not_found'
            ];
        }
        
        // Получаем ID курса, к которому относится урок
        $course_id = $lesson_model->getAttribute('course_id');
        
        // Проверяем доступность курса
        $course_accessibility = $this->check_course_accessibility($user_id, $course_id);
        if (!$course_accessibility['accessible']) {
            // Если курс недоступен, возвращаем результат проверки курса
            return $course_accessibility;
        }
        
        // Получаем все уроки курса
        $course_lessons = $lesson_repository->get_course_lessons($course_id, [
            'orderby' => 'lesson_order',
            'order' => 'ASC',
            'is_active' => 1
        ]);
        
        // Находим текущий урок в списке
        $current_lesson_index = -1;
        foreach ($course_lessons as $index => $course_lesson) {
            if ($course_lesson->getAttribute('id') == $lesson_id) {
                $current_lesson_index = $index;
                break;
            }
        }
        
        // Если это не первый урок, проверяем, завершен ли предыдущий
        if ($current_lesson_index > 0) {
            $user_lesson_progress_repository = new CryptoSchool_Repository_User_Lesson_Progress();
            $prev_lesson_id = $course_lessons[$current_lesson_index - 1]->getAttribute('id');
            $prev_lesson_progress = $user_lesson_progress_repository->get_user_lesson_progress($user_id, $prev_lesson_id);
            $prev_lesson_completed = $prev_lesson_progress ? $prev_lesson_progress->getAttribute('is_completed') : false;
            
            // Если предыдущий урок не завершен, перенаправляем на него
            if (!$prev_lesson_completed) {
                return [
                    'accessible' => false, 
                    'redirect_url' => site_url('/lesson/?id=' . $prev_lesson_id),
                    'reason' => 'previous_lesson_not_completed'
                ];
            }
        }
        
        // Если все проверки пройдены, урок доступен
        return ['accessible' => true, 'redirect_url' => null];
    }
}
