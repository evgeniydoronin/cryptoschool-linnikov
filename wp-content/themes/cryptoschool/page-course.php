<?php
/**
 * Template Name: Курс
 *
 * @package CryptoSchool
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

// Если пользователь не авторизован, перенаправляем на страницу входа
if (!is_user_logged_in()) {
    wp_redirect(site_url('/sign-in/'));
    exit;
}

get_header();

// Получаем ID курса из GET-параметра
$course_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Получаем текущего пользователя
$current_user_id = get_current_user_id();

// Получаем данные курса из базы данных
$course_repository = new CryptoSchool_Repository_Course();
$course_model = $course_repository->find($course_id);

// Если курс не найден, перенаправляем на страницу списка курсов
if (!$course_model) {
    wp_redirect(site_url('/courses/'));
    exit;
}

// Проверяем доступность курса для пользователя
$is_available = $course_model->is_available_for_user($current_user_id);
if (!$is_available) {
    wp_redirect(site_url('/courses/'));
    exit;
}

// Получаем уроки курса
$lessons = $course_model->get_lessons();

// Группируем уроки по модулям
$modules = [];
foreach ($lessons as $lesson) {
    $module_id = $lesson->getAttribute('module_id');
    $module_title = $lesson->getAttribute('module_title') ?: __('Модуль без названия', 'cryptoschool');
    $module_number = $lesson->getAttribute('module_order') ?: 1;
    
    if (!isset($modules[$module_id])) {
        $modules[$module_id] = [
            'id' => $module_id,
            'title' => $module_title,
            'number' => $module_number,
            'lessons_count' => 0,
            'opened' => true, // По умолчанию модуль открыт
            'lessons' => []
        ];
    }
    
    // Определяем статус урока для пользователя
    $lesson_status = 'locked'; // По умолчанию урок заблокирован
    $lesson_status_text = __('Недоступний', 'cryptoschool');
    
    // Получаем прогресс пользователя по уроку
    $user_lesson_progress_repository = new CryptoSchool_Repository_User_Lesson_Progress();
    $user_progress = $user_lesson_progress_repository->get_user_lesson_progress($current_user_id, $lesson->getAttribute('id'));
    
    $lesson_progress = $user_progress ? $user_progress->getAttribute('progress_percent') : 0;
    $is_completed = $user_progress ? $user_progress->getAttribute('is_completed') : false;
    
    // Проверяем, является ли это первым уроком в модуле
    $is_first_lesson = false;
    if (count($modules[$module_id]['lessons']) === 0) {
        $is_first_lesson = true;
    }
    
    // Проверяем, завершен ли предыдущий урок
    $prev_lesson_completed = true;
    if (!$is_first_lesson && count($modules[$module_id]['lessons']) > 0) {
        $last_lesson_index = count($modules[$module_id]['lessons']) - 1;
        $prev_lesson_id = $modules[$module_id]['lessons'][$last_lesson_index]['id'];
        $prev_lesson_progress = $user_lesson_progress_repository->get_user_lesson_progress($current_user_id, $prev_lesson_id);
        $prev_lesson_completed = $prev_lesson_progress ? $prev_lesson_progress->getAttribute('is_completed') : false;
    }
    
    // Первый урок всегда доступен, остальные - только если предыдущий завершен
    if ($is_first_lesson || $prev_lesson_completed) {
        if ($is_completed) {
            $lesson_status = 'done';
            $lesson_status_text = __('виконаний', 'cryptoschool');
        } elseif ($lesson_progress > 0) {
            $lesson_status = 'in-process';
            $lesson_status_text = __('У процесі', 'cryptoschool');
        } else {
            $lesson_status = 'in-process'; // Доступный урок отображается как "в процессе"
            $lesson_status_text = __('Доступний', 'cryptoschool');
        }
    }
    
    // Добавляем урок в модуль
    $modules[$module_id]['lessons'][] = [
        'id' => $lesson->getAttribute('id'),
        'number' => $lesson->getAttribute('lesson_order') ?: count($modules[$module_id]['lessons']) + 1,
        'title' => $lesson->getAttribute('title'),
        'status' => $lesson_status,
        'status_text' => $lesson_status_text
    ];
    
    // Увеличиваем счетчик уроков в модуле
    $modules[$module_id]['lessons_count']++;
}

// Сортируем модули по номеру
usort($modules, function($a, $b) {
    return $a['number'] <=> $b['number'];
});

// Сортируем уроки внутри каждого модуля по номеру
foreach ($modules as &$module) {
    usort($module['lessons'], function($a, $b) {
        return $a['number'] <=> $b['number'];
    });
}
?>

<main>
    <div class="page-background">
        <div class="ratio-wrap page-background__wrap">
            <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/decor-light.svg" alt="Page decor" class="ratio-wrap__item page-background__img_light">
            <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/decor-dark.svg" alt="Page decor" class="ratio-wrap__item page-background__img_dark">
        </div>
    </div>

    <div class="container container_wide study__container">
        <div class="hide-mobile">
            <!-- Горизонтальная навигация -->
            <?php get_template_part('template-parts/account/horizontal-navigation'); ?>
        </div>

        <h5 class="h5 color-primary study__title"><?php echo esc_html($course_model->getAttribute('title')); ?></h5>

        <div class="study__modules">
            <?php if (empty($modules)) : ?>
                <p class="text-small"><?php _e('Модули не найдены', 'cryptoschool'); ?></p>
            <?php else : ?>
                <?php foreach ($modules as $module) : ?>
                    <div class="palette palette_blurred study-module <?php echo $module['opened'] ? 'study-module_opened' : ''; ?>">
                        <div class="study-module__summary">
                            <div class="study-module__left">
                                <div class="study-module__number text">Модуль <?php echo esc_html($module['number']); ?></div>
                                <div class="study-module__name text color-primary"><?php echo esc_html($module['title']); ?></div>
                            </div>
                            <div class="study-module__right">
                                <div class="study-module__amount text"><?php echo esc_html($module['lessons_count']); ?> уроків</div>
                                <div class="study-module__toggler">
                                    <span class="icon-nav-arrow-right"></span>
                                </div>
                            </div>
                        </div>
                        <div class="study-module__dropdown">
                            <div class="study-module__lessons">
                                <?php if (empty($module['lessons'])) : ?>
                                    <p class="text-small"><?php _e('Уроки не найдены', 'cryptoschool'); ?></p>
                                <?php else : ?>
                                    <?php foreach ($module['lessons'] as $lesson) : ?>
                                        <div class="study-module__lesson study-module__lesson_<?php echo esc_attr($lesson['status']); ?>">
                                            <?php if ($lesson['status'] === 'done') : ?>
                                                <div class="study-module__lesson-check">
                                                    <span class="icon-check-arrow"></span>
                                                </div>
                                            <?php endif; ?>
                                            <div class="study-module__lesson-left">
                                                <div class="study-module__lesson-number text"><?php echo esc_html($lesson['number']); ?></div>
                                                <div class="study-module__lesson-name text">
                                                    <?php if ($lesson['status'] === 'done' || $lesson['status'] === 'in-process') : ?>
                                                        <a href="<?php echo esc_url(site_url('/lesson/?id=' . $lesson['id'])); ?>" class="study-module__lesson-link">
                                                            <?php echo esc_html($lesson['title']); ?>
                                                        </a>
                                                    <?php else : ?>
                                                        <?php echo esc_html($lesson['title']); ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="study-module__lesson-status text-small"><?php echo esc_html($lesson['status_text']); ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="bottom-navigation">
            <?php
            // Получаем все доступные курсы для пользователя
            $user_courses = $course_repository->get_user_courses($current_user_id, [
                'is_active' => 1,
                'orderby' => 'course_order',
                'order' => 'ASC'
            ]);
            
            // Находим индекс текущего курса в массиве
            $current_index = -1;
            foreach ($user_courses as $index => $user_course) {
                if ($user_course->getAttribute('id') == $course_id) {
                    $current_index = $index;
                    break;
                }
            }
            
            // Определяем предыдущий и следующий курсы
            $prev_course = ($current_index > 0) ? $user_courses[$current_index - 1] : null;
            $next_course = ($current_index < count($user_courses) - 1) ? $user_courses[$current_index + 1] : null;
            ?>
            
            <?php if ($prev_course) : ?>
                <a href="<?php echo esc_url(site_url('/course/?id=' . $prev_course->getAttribute('id'))); ?>" class="bottom-navigation__item bottom-navigation__previous">
                    <div class="bottom-navigation__arrow">
                        <span class="icon-nav-arrow-left"></span>
                    </div>
                    <div class="bottom-navigation__label text-small">Попередній модуль</div>
                </a>
            <?php endif; ?>
            
            <?php if ($next_course) : ?>
                <a href="<?php echo esc_url(site_url('/course/?id=' . $next_course->getAttribute('id'))); ?>" class="bottom-navigation__item bottom-navigation__next">
                    <div class="bottom-navigation__label text-small">Наступний модуль</div>
                    <div class="bottom-navigation__arrow">
                        <span class="icon-nav-arrow-right"></span>
                    </div>
                </a>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php get_footer(); ?>
