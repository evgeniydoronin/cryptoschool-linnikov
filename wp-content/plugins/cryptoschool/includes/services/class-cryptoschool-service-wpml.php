<?php
/**
 * Сервис для работы с WPML
 *
 * @package CryptoSchool
 * @subpackage Services
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс сервиса для работы с WPML
 */
class CryptoSchool_Service_WPML {
    
    /**
     * Проверка, активен ли WPML
     *
     * @return bool
     */
    public function is_wpml_active() {
        return function_exists('apply_filters') && 
               apply_filters('wpml_setting', false, 'setup_complete');
    }

    /**
     * Получение текущего языка
     *
     * @return string|null
     */
    public function get_current_language() {
        if (!$this->is_wpml_active()) {
            return null;
        }
        
        return apply_filters('wpml_current_language', null);
    }

    /**
     * Получение языка по умолчанию
     *
     * @return string|null
     */
    public function get_default_language() {
        if (!$this->is_wpml_active()) {
            return null;
        }
        
        return apply_filters('wpml_default_language', null);
    }

    /**
     * Получение всех активных языков
     *
     * @return array
     */
    public function get_active_languages() {
        if (!$this->is_wpml_active()) {
            return [];
        }
        
        $languages = apply_filters('wpml_active_languages', null, 'orderby=id&order=desc');
        
        // Нормализация структуры данных для обеспечения совместимости
        if (is_array($languages)) {
            foreach ($languages as $code => $language) {
                // Убеждаемся, что все необходимые ключи присутствуют
                if (!isset($language['display_name'])) {
                    $languages[$code]['display_name'] = $language['native_name'] ?? $language['english_name'] ?? $code;
                }
                if (!isset($language['native_name'])) {
                    $languages[$code]['native_name'] = $language['display_name'] ?? $language['english_name'] ?? $code;
                }
                if (!isset($language['english_name'])) {
                    $languages[$code]['english_name'] = $language['display_name'] ?? $language['native_name'] ?? $code;
                }
            }
        }
        
        return $languages ?: [];
    }

    /**
     * Регистрация строки для перевода
     *
     * @param string $context Контекст строки
     * @param string $name    Имя строки
     * @param string $value   Значение строки
     * @return void
     */
    public function register_string($context, $name, $value) {
        if (!$this->is_wpml_active() || empty($value)) {
            return;
        }
        
        do_action('wpml_register_single_string', $context, $name, $value);
    }

    /**
     * Получение перевода строки
     *
     * @param string $value   Оригинальное значение
     * @param string $context Контекст строки
     * @param string $name    Имя строки
     * @param string $language_code Код языка (необязательно)
     * @return string
     */
    public function translate_string($value, $context, $name, $language_code = null) {
        if (!$this->is_wpml_active() || empty($value)) {
            return $value;
        }
        
        return apply_filters('wpml_translate_single_string', $value, $context, $name, $language_code);
    }

    /**
     * Массовая регистрация строк курса
     *
     * @param CryptoSchool_Model_Course $course Модель курса
     * @return void
     */
    public function register_course_strings($course) {
        if (!$this->is_wpml_active() || !$course->getAttribute('id')) {
            return;
        }
        
        $course_id = $course->getAttribute('id');
        
        // Регистрация названия курса
        if ($course->getAttribute('title')) {
            $this->register_string(
                'CryptoSchool Courses',
                'Course Title - ' . $course_id,
                $course->getAttribute('title')
            );
        }
        
        // Регистрация описания курса
        if ($course->getAttribute('description')) {
            $this->register_string(
                'CryptoSchool Courses',
                'Course Description - ' . $course_id,
                $course->getAttribute('description')
            );
        }
        
        // Регистрация уровня сложности
        if ($course->getAttribute('difficulty_level')) {
            $this->register_string(
                'CryptoSchool Courses',
                'Course Difficulty - ' . $course_id,
                $course->getAttribute('difficulty_level')
            );
        }
    }

    /**
     * Массовая регистрация строк урока
     *
     * @param CryptoSchool_Model_Lesson $lesson Модель урока
     * @return void
     */
    public function register_lesson_strings($lesson) {
        if (!$this->is_wpml_active() || !$lesson->getAttribute('id')) {
            return;
        }
        
        $lesson_id = $lesson->getAttribute('id');
        
        // Регистрация названия урока
        if ($lesson->getAttribute('title')) {
            $this->register_string(
                'CryptoSchool Lessons',
                'Lesson Title - ' . $lesson_id,
                $lesson->getAttribute('title')
            );
        }
        
        // Регистрация содержимого урока
        if ($lesson->getAttribute('content')) {
            $this->register_string(
                'CryptoSchool Lessons',
                'Lesson Content - ' . $lesson_id,
                $lesson->getAttribute('content')
            );
        }
        
        // Регистрация заданий из JSON
        if ($lesson->getAttribute('completion_tasks')) {
            $tasks = json_decode($lesson->getAttribute('completion_tasks'), true);
            if (is_array($tasks)) {
                foreach ($tasks as $index => $task) {
                    if (isset($task['description'])) {
                        $this->register_string(
                            'CryptoSchool Lessons',
                            'Lesson Task ' . $lesson_id . ' - ' . $index,
                            $task['description']
                        );
                    }
                    if (isset($task['title'])) {
                        $this->register_string(
                            'CryptoSchool Lessons',
                            'Lesson Task Title ' . $lesson_id . ' - ' . $index,
                            $task['title']
                        );
                    }
                }
            }
        }
    }

    /**
     * Массовая регистрация строк задания
     *
     * @param CryptoSchool_Model_Lesson_Task $task Модель задания
     * @return void
     */
    public function register_task_strings($task) {
        if (!$this->is_wpml_active() || !$task->getAttribute('id')) {
            return;
        }
        
        $task_id = $task->getAttribute('id');
        
        // Регистрация названия задания
        if ($task->getAttribute('title')) {
            $this->register_string(
                'CryptoSchool Tasks',
                'Task Title - ' . $task_id,
                $task->getAttribute('title')
            );
        }
    }

    /**
     * Получение переводов для курса
     *
     * @param CryptoSchool_Model_Course $course Модель курса
     * @param string $language_code Код языка (необязательно)
     * @return void
     */
    public function translate_course_strings($course, $language_code = null) {
        if (!$this->is_wpml_active() || !$course->getAttribute('id')) {
            return;
        }
        
        $course_id = $course->getAttribute('id');
        
        // Получение перевода названия курса
        if ($course->getAttribute('title')) {
            $translated_title = $this->translate_string(
                $course->getAttribute('title'),
                'CryptoSchool Courses',
                'Course Title - ' . $course_id,
                $language_code
            );
            $course->setAttribute('title', $translated_title);
        }
        
        // Получение перевода описания курса
        if ($course->getAttribute('description')) {
            $translated_description = $this->translate_string(
                $course->getAttribute('description'),
                'CryptoSchool Courses',
                'Course Description - ' . $course_id,
                $language_code
            );
            $course->setAttribute('description', $translated_description);
        }
        
        // Получение перевода уровня сложности
        if ($course->getAttribute('difficulty_level')) {
            $translated_difficulty = $this->translate_string(
                $course->getAttribute('difficulty_level'),
                'CryptoSchool Courses',
                'Course Difficulty - ' . $course_id,
                $language_code
            );
            $course->setAttribute('difficulty_level', $translated_difficulty);
        }
    }

    /**
     * Получение переводов для урока
     *
     * @param CryptoSchool_Model_Lesson $lesson Модель урока
     * @param string $language_code Код языка (необязательно)
     * @return void
     */
    public function translate_lesson_strings($lesson, $language_code = null) {
        if (!$this->is_wpml_active() || !$lesson->getAttribute('id')) {
            return;
        }
        
        $lesson_id = $lesson->getAttribute('id');
        
        // Получение перевода названия урока
        if ($lesson->getAttribute('title')) {
            $translated_title = $this->translate_string(
                $lesson->getAttribute('title'),
                'CryptoSchool Lessons',
                'Lesson Title - ' . $lesson_id,
                $language_code
            );
            $lesson->setAttribute('title', $translated_title);
        }
        
        // Получение перевода содержимого урока
        if ($lesson->getAttribute('content')) {
            $translated_content = $this->translate_string(
                $lesson->getAttribute('content'),
                'CryptoSchool Lessons',
                'Lesson Content - ' . $lesson_id,
                $language_code
            );
            $lesson->setAttribute('content', $translated_content);
        }
        
        // Перевод заданий в JSON
        if ($lesson->getAttribute('completion_tasks')) {
            $tasks = json_decode($lesson->getAttribute('completion_tasks'), true);
            if (is_array($tasks)) {
                foreach ($tasks as $index => $task) {
                    if (isset($task['description'])) {
                        $tasks[$index]['description'] = $this->translate_string(
                            $task['description'],
                            'CryptoSchool Lessons',
                            'Lesson Task ' . $lesson_id . ' - ' . $index,
                            $language_code
                        );
                    }
                    if (isset($task['title'])) {
                        $tasks[$index]['title'] = $this->translate_string(
                            $task['title'],
                            'CryptoSchool Lessons',
                            'Lesson Task Title ' . $lesson_id . ' - ' . $index,
                            $language_code
                        );
                    }
                }
                $lesson->setAttribute('completion_tasks', json_encode($tasks, JSON_UNESCAPED_UNICODE));
            }
        }
    }

    /**
     * Получение переводов для задания
     *
     * @param CryptoSchool_Model_Lesson_Task $task Модель задания
     * @param string $language_code Код языка (необязательно)
     * @return void
     */
    public function translate_task_strings($task, $language_code = null) {
        if (!$this->is_wpml_active() || !$task->getAttribute('id')) {
            return;
        }
        
        $task_id = $task->getAttribute('id');
        
        // Получение перевода названия задания
        if ($task->getAttribute('title')) {
            $translated_title = $this->translate_string(
                $task->getAttribute('title'),
                'CryptoSchool Tasks',
                'Task Title - ' . $task_id,
                $language_code
            );
            $task->setAttribute('title', $translated_title);
        }
    }

    /**
     * Массовая регистрация строк для всех курсов
     *
     * @return void
     */
    public function register_all_course_strings() {
        if (!$this->is_wpml_active()) {
            return;
        }
        
        // Получаем все курсы через WordPress API
        $courses = get_posts(array(
            'post_type' => 'cryptoschool_course',
            'post_status' => 'any',
            'numberposts' => -1
        ));
        
        foreach ($courses as $course_post) {
            $this->register_course_post_strings($course_post);
        }
    }

    /**
     * Массовая регистрация строк для всех уроков
     *
     * @return void
     */
    public function register_all_lesson_strings() {
        if (!$this->is_wpml_active()) {
            return;
        }
        
        // Получаем все уроки через WordPress API
        $lessons = get_posts(array(
            'post_type' => 'cryptoschool_lesson',
            'post_status' => 'any',
            'numberposts' => -1
        ));
        
        foreach ($lessons as $lesson_post) {
            $this->register_lesson_post_strings($lesson_post);
        }
    }

    /**
     * Переключение языка для текущего запроса
     *
     * @param string $language_code Код языка
     * @return void
     */
    public function switch_language($language_code) {
        if (!$this->is_wpml_active()) {
            return;
        }
        
        do_action('wpml_switch_language', $language_code);
    }

    /**
     * Получение URL для переключения языка
     *
     * @param string $language_code Код языка
     * @param string $url URL (необязательно, по умолчанию текущий URL)
     * @return string
     */
    public function get_language_switch_url($language_code, $url = null) {
        if (!$this->is_wpml_active()) {
            return $url ?: home_url();
        }
        
        if (!$url) {
            $url = home_url($_SERVER['REQUEST_URI']);
        }
        
        return apply_filters('wpml_permalink', $url, $language_code);
    }

    /**
     * Получение информации о языке
     *
     * @param string $language_code Код языка
     * @return array|null
     */
    public function get_language_details($language_code) {
        if (!$this->is_wpml_active()) {
            return null;
        }
        
        $languages = $this->get_active_languages();
        
        return isset($languages[$language_code]) ? $languages[$language_code] : null;
    }

    /**
     * Проверка, является ли язык активным
     *
     * @param string $language_code Код языка
     * @return bool
     */
    public function is_language_active($language_code) {
        if (!$this->is_wpml_active()) {
            return false;
        }
        
        $languages = $this->get_active_languages();
        
        return isset($languages[$language_code]);
    }

    /**
     * Регистрация строк для курса (Custom Post Type)
     *
     * @param WP_Post $course_post Объект поста курса
     * @return void
     */
    public function register_course_post_strings($course_post) {
        if (!$this->is_wpml_active() || !$course_post || $course_post->post_type !== 'cryptoschool_course') {
            return;
        }
        
        $course_id = $course_post->ID;
        
        // Регистрация названия курса
        if (!empty($course_post->post_title)) {
            $this->register_string(
                'CryptoSchool Courses',
                'Course Title - ' . $course_id,
                $course_post->post_title
            );
        }
        
        // Регистрация содержимого курса
        if (!empty($course_post->post_content)) {
            $this->register_string(
                'CryptoSchool Courses',
                'Course Content - ' . $course_id,
                $course_post->post_content
            );
        }
        
        // Регистрация мета-полей
        $difficulty_level = get_post_meta($course_id, 'difficulty_level', true);
        if (!empty($difficulty_level)) {
            $this->register_string(
                'CryptoSchool Courses',
                'Course Difficulty - ' . $course_id,
                $difficulty_level
            );
        }
    }

    /**
     * Регистрация строк для урока (Custom Post Type)
     *
     * @param WP_Post $lesson_post Объект поста урока
     * @return void
     */
    public function register_lesson_post_strings($lesson_post) {
        if (!$this->is_wpml_active() || !$lesson_post || $lesson_post->post_type !== 'cryptoschool_lesson') {
            return;
        }
        
        $lesson_id = $lesson_post->ID;
        
        // Регистрация названия урока
        if (!empty($lesson_post->post_title)) {
            $this->register_string(
                'CryptoSchool Lessons',
                'Lesson Title - ' . $lesson_id,
                $lesson_post->post_title
            );
        }
        
        // Регистрация содержимого урока
        if (!empty($lesson_post->post_content)) {
            $this->register_string(
                'CryptoSchool Lessons',
                'Lesson Content - ' . $lesson_id,
                $lesson_post->post_content
            );
        }
        
        // Регистрация заданий из мета-полей
        $completion_tasks = get_post_meta($lesson_id, 'completion_tasks', true);
        if (!empty($completion_tasks)) {
            $tasks = json_decode($completion_tasks, true);
            if (is_array($tasks)) {
                foreach ($tasks as $index => $task) {
                    if (isset($task['description']) && !empty($task['description'])) {
                        $this->register_string(
                            'CryptoSchool Lessons',
                            'Lesson Task ' . $lesson_id . ' - ' . $index,
                            $task['description']
                        );
                    }
                    if (isset($task['title']) && !empty($task['title'])) {
                        $this->register_string(
                            'CryptoSchool Lessons',
                            'Lesson Task Title ' . $lesson_id . ' - ' . $index,
                            $task['title']
                        );
                    }
                }
            }
        }
    }
}
