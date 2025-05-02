<?php
/**
 * Шаблон для отображения информации о курсе (шорткод)
 *
 * @package CryptoSchool
 * @subpackage Public\Views\Shortcodes
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

// Получение переменных из контекста
$course = isset($course) ? $course : null;
$lessons = isset($lessons) ? $lessons : array();
$user_progress = isset($user_progress) ? $user_progress : null;
$has_access = isset($has_access) ? $has_access : false;
?>

<?php if ($course) : ?>
    <div class="cryptoschool-course">
        <div class="cryptoschool-course-header">
            <?php if (!empty($course->thumbnail)) : ?>
                <div class="cryptoschool-course-thumbnail">
                    <img src="<?php echo esc_url($course->thumbnail); ?>" alt="<?php echo esc_attr($course->title); ?>">
                </div>
            <?php endif; ?>
            <div class="cryptoschool-course-header-content">
                <h1 class="cryptoschool-course-title"><?php echo esc_html($course->title); ?></h1>
                <?php if (!empty($course->difficulty_level)) : ?>
                    <div class="cryptoschool-course-difficulty">
                        <span class="cryptoschool-difficulty-label"><?php _e('Сложность:', 'cryptoschool'); ?></span>
                        <span class="cryptoschool-difficulty-value cryptoschool-difficulty-<?php echo esc_attr(strtolower($course->difficulty_level)); ?>">
                            <?php echo esc_html($course->difficulty_level); ?>
                        </span>
                    </div>
                <?php endif; ?>
                <?php if ($user_progress) : ?>
                    <div class="cryptoschool-course-progress">
                        <div class="cryptoschool-progress-bar">
                            <div class="cryptoschool-progress-bar-inner" style="width: <?php echo esc_attr($user_progress['percent']); ?>%"></div>
                        </div>
                        <div class="cryptoschool-progress-stats">
                            <span class="cryptoschool-progress-percent"><?php echo esc_html(round($user_progress['percent'])); ?>%</span>
                            <span class="cryptoschool-progress-lessons">
                                <?php printf(
                                    __('%1$s из %2$s уроков пройдено', 'cryptoschool'),
                                    esc_html($user_progress['completed_lessons']),
                                    esc_html($user_progress['total_lessons'])
                                ); ?>
                            </span>
                            <span class="cryptoschool-progress-points">
                                <?php printf(
                                    __('%s баллов заработано', 'cryptoschool'),
                                    esc_html($user_progress['points'])
                                ); ?>
                            </span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($course->description)) : ?>
            <div class="cryptoschool-course-description">
                <?php echo wp_kses_post($course->description); ?>
            </div>
        <?php endif; ?>

        <?php if (!$has_access) : ?>
            <div class="cryptoschool-course-access-required">
                <div class="cryptoschool-access-message">
                    <h3><?php _e('Доступ к курсу ограничен', 'cryptoschool'); ?></h3>
                    <p><?php _e('Для доступа к этому курсу необходимо приобрести соответствующий пакет.', 'cryptoschool'); ?></p>
                    <a href="<?php echo esc_url(home_url('/packages/')); ?>" class="cryptoschool-button cryptoschool-button-primary">
                        <?php _e('Приобрести доступ', 'cryptoschool'); ?>
                    </a>
                </div>
            </div>
        <?php else : ?>
            <div class="cryptoschool-course-lessons">
                <h2><?php _e('Содержание курса', 'cryptoschool'); ?></h2>
                <?php if (!empty($lessons)) : ?>
                    <div class="cryptoschool-lessons-list">
                        <ul class="cryptoschool-lessons-list">
                            <?php foreach ($lessons as $lesson) : ?>
                                <li class="cryptoschool-lesson-item <?php echo isset($lesson->completed) && $lesson->completed ? 'cryptoschool-lesson-completed' : ''; ?>">
                                    <a href="<?php echo esc_url(get_permalink()); ?>?lesson=<?php echo esc_attr($lesson->slug); ?>">
                                        <span class="cryptoschool-lesson-icon dashicons <?php echo isset($lesson->completed) && $lesson->completed ? 'dashicons-yes' : 'dashicons-media-text'; ?>"></span>
                                        <span class="cryptoschool-lesson-title"><?php echo esc_html($lesson->title); ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php else : ?>
                    <div class="cryptoschool-empty-state">
                        <p><?php _e('В этом курсе пока нет уроков.', 'cryptoschool'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
<?php else : ?>
    <div class="cryptoschool-empty-state">
        <p><?php _e('Курс не найден.', 'cryptoschool'); ?></p>
    </div>
<?php endif; ?>
