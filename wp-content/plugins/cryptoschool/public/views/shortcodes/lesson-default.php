<?php
/**
 * Шаблон для отображения информации об уроке (шорткод)
 *
 * @package CryptoSchool
 * @subpackage Public\Views\Shortcodes
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

// Получение переменных из контекста
$lesson = isset($lesson) ? $lesson : null;
$module = isset($module) ? $module : null;
$course = isset($course) ? $course : null;
$user_progress = isset($user_progress) ? $user_progress : null;
$has_access = isset($has_access) ? $has_access : false;
$prev_lesson = isset($prev_lesson) ? $prev_lesson : null;
$next_lesson = isset($next_lesson) ? $next_lesson : null;
?>

<?php if ($lesson) : ?>
    <div class="cryptoschool-lesson">
        <div class="cryptoschool-lesson-header">
            <div class="cryptoschool-lesson-breadcrumbs">
                <?php if ($course) : ?>
                    <a href="<?php echo esc_url(get_permalink()); ?>?course=<?php echo esc_attr($course->slug); ?>" class="cryptoschool-breadcrumb-item">
                        <?php echo esc_html($course->title); ?>
                    </a>
                    <span class="cryptoschool-breadcrumb-separator">/</span>
                <?php endif; ?>
                <?php if ($module) : ?>
                    <a href="<?php echo esc_url(get_permalink()); ?>?module=<?php echo esc_attr($module->slug); ?>" class="cryptoschool-breadcrumb-item">
                        <?php echo esc_html($module->title); ?>
                    </a>
                    <span class="cryptoschool-breadcrumb-separator">/</span>
                <?php endif; ?>
                <span class="cryptoschool-breadcrumb-item cryptoschool-breadcrumb-current">
                    <?php echo esc_html($lesson->title); ?>
                </span>
            </div>
            <h1 class="cryptoschool-lesson-title"><?php echo esc_html($lesson->title); ?></h1>
            <?php if (isset($lesson->completion_points)) : ?>
                <div class="cryptoschool-lesson-points">
                    <?php printf(
                        __('За прохождение урока: %s баллов', 'cryptoschool'),
                        $lesson->completion_points
                    ); ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!$has_access) : ?>
            <div class="cryptoschool-lesson-access-required">
                <div class="cryptoschool-access-message">
                    <h3><?php _e('Доступ к уроку ограничен', 'cryptoschool'); ?></h3>
                    <p><?php _e('Для доступа к этому уроку необходимо приобрести соответствующий пакет.', 'cryptoschool'); ?></p>
                    <a href="<?php echo esc_url(home_url('/packages/')); ?>" class="cryptoschool-button cryptoschool-button-primary">
                        <?php _e('Приобрести доступ', 'cryptoschool'); ?>
                    </a>
                </div>
            </div>
        <?php else : ?>
            <div class="cryptoschool-lesson-content">
                <?php if (!empty($lesson->video_url)) : ?>
                    <div class="cryptoschool-lesson-video">
                        <?php
                        // Определение типа видео (YouTube, Vimeo, локальное)
                        if (strpos($lesson->video_url, 'youtube.com') !== false || strpos($lesson->video_url, 'youtu.be') !== false) {
                            // YouTube видео
                            $video_id = '';
                            if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $lesson->video_url, $matches)) {
                                $video_id = $matches[1];
                            }
                            if ($video_id) {
                                echo '<div class="cryptoschool-video-wrapper">';
                                echo '<iframe width="100%" height="100%" src="https://www.youtube.com/embed/' . esc_attr($video_id) . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
                                echo '</div>';
                            }
                        } elseif (strpos($lesson->video_url, 'vimeo.com') !== false) {
                            // Vimeo видео
                            $video_id = '';
                            if (preg_match('/vimeo\.com\/(?:video\/)?([0-9]+)/', $lesson->video_url, $matches)) {
                                $video_id = $matches[1];
                            }
                            if ($video_id) {
                                echo '<div class="cryptoschool-video-wrapper">';
                                echo '<iframe width="100%" height="100%" src="https://player.vimeo.com/video/' . esc_attr($video_id) . '" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>';
                                echo '</div>';
                            }
                        } else {
                            // Локальное видео или другой источник
                            echo '<div class="cryptoschool-video-wrapper">';
                            echo '<video width="100%" height="100%" controls>';
                            echo '<source src="' . esc_url($lesson->video_url) . '" type="video/mp4">';
                            echo __('Ваш браузер не поддерживает видео.', 'cryptoschool');
                            echo '</video>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($lesson->content)) : ?>
                    <div class="cryptoschool-lesson-text">
                        <?php echo wp_kses_post($lesson->content); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($lesson->completion_tasks) && !empty($lesson->completion_tasks)) : ?>
                    <div class="cryptoschool-lesson-tasks">
                        <h3><?php _e('Задания для выполнения', 'cryptoschool'); ?></h3>
                        <div class="cryptoschool-tasks-list">
                            <?php
                            $tasks = json_decode($lesson->completion_tasks, true);
                            if (is_array($tasks)) :
                                foreach ($tasks as $task_id => $task) :
                                    $is_completed = false;
                                    if ($user_progress && isset($user_progress['completed_tasks'])) {
                                        $completed_tasks = json_decode($user_progress['completed_tasks'], true);
                                        $is_completed = is_array($completed_tasks) && in_array($task_id, $completed_tasks);
                                    }
                            ?>
                                <div class="cryptoschool-task-item <?php echo $is_completed ? 'cryptoschool-task-completed' : ''; ?>" data-task-id="<?php echo esc_attr($task_id); ?>">
                                    <div class="cryptoschool-task-checkbox">
                                        <input type="checkbox" id="task-<?php echo esc_attr($task_id); ?>" <?php checked($is_completed, true); ?>>
                                        <label for="task-<?php echo esc_attr($task_id); ?>"></label>
                                    </div>
                                    <div class="cryptoschool-task-content">
                                        <div class="cryptoschool-task-title"><?php echo esc_html($task['title']); ?></div>
                                        <?php if (!empty($task['description'])) : ?>
                                            <div class="cryptoschool-task-description"><?php echo wp_kses_post($task['description']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php
                                endforeach;
                            endif;
                            ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="cryptoschool-lesson-actions">
                    <div class="cryptoschool-lesson-navigation">
                        <?php if ($prev_lesson) : ?>
                            <a href="<?php echo esc_url(get_permalink()); ?>?lesson=<?php echo esc_attr($prev_lesson->slug); ?>" class="cryptoschool-button cryptoschool-button-secondary cryptoschool-button-prev">
                                <span class="dashicons dashicons-arrow-left-alt"></span>
                                <?php _e('Предыдущий урок', 'cryptoschool'); ?>
                            </a>
                        <?php endif; ?>
                        <?php if ($next_lesson) : ?>
                            <a href="<?php echo esc_url(get_permalink()); ?>?lesson=<?php echo esc_attr($next_lesson->slug); ?>" class="cryptoschool-button cryptoschool-button-secondary cryptoschool-button-next">
                                <?php _e('Следующий урок', 'cryptoschool'); ?>
                                <span class="dashicons dashicons-arrow-right-alt"></span>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="cryptoschool-lesson-completion">
                        <?php if (isset($user_progress) && isset($user_progress['status']) && $user_progress['status'] === 'completed') : ?>
                            <div class="cryptoschool-completion-status cryptoschool-completion-completed">
                                <span class="dashicons dashicons-yes"></span>
                                <?php _e('Урок пройден', 'cryptoschool'); ?>
                            </div>
                        <?php else : ?>
                            <button class="cryptoschool-button cryptoschool-button-primary cryptoschool-complete-lesson" data-lesson-id="<?php echo esc_attr($lesson->id); ?>">
                                <?php _e('Отметить как пройденный', 'cryptoschool'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="cryptoschool-lesson-support">
                    <h3><?php _e('Нужна помощь?', 'cryptoschool'); ?></h3>
                    <p><?php _e('Если у вас возникли вопросы по этому уроку, вы можете обратиться к специалисту.', 'cryptoschool'); ?></p>
                    <button class="cryptoschool-button cryptoschool-button-secondary cryptoschool-support-button" data-lesson-id="<?php echo esc_attr($lesson->id); ?>">
                        <span class="dashicons dashicons-admin-users"></span>
                        <?php _e('Помощь специалиста', 'cryptoschool'); ?>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php else : ?>
    <div class="cryptoschool-empty-state">
        <p><?php _e('Урок не найден.', 'cryptoschool'); ?></p>
    </div>
<?php endif; ?>
