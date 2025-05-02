<?php
/**
 * Шаблон для отображения списка курсов (шорткод)
 *
 * @package CryptoSchool
 * @subpackage Public\Views\Shortcodes
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

// Получение переменных из контекста
$courses = isset($courses) ? $courses : array();
?>

<div class="cryptoschool-courses">
    <div class="cryptoschool-courses-grid">
        <?php if (!empty($courses)) : ?>
            <?php foreach ($courses as $course) : ?>
                <div class="cryptoschool-course-card">
                    <?php if (!empty($course->thumbnail)) : ?>
                        <div class="cryptoschool-course-thumbnail">
                            <img src="<?php echo esc_url($course->thumbnail); ?>" alt="<?php echo esc_attr($course->title); ?>">
                        </div>
                    <?php endif; ?>
                    <div class="cryptoschool-course-content">
                        <h3 class="cryptoschool-course-title">
                            <a href="<?php echo esc_url(get_permalink()); ?>?course=<?php echo esc_attr($course->slug); ?>">
                                <?php echo esc_html($course->title); ?>
                            </a>
                        </h3>
                        <?php if (!empty($course->difficulty_level)) : ?>
                            <div class="cryptoschool-course-difficulty">
                                <span class="cryptoschool-difficulty-label"><?php _e('Сложность:', 'cryptoschool'); ?></span>
                                <span class="cryptoschool-difficulty-value cryptoschool-difficulty-<?php echo esc_attr(strtolower($course->difficulty_level)); ?>">
                                    <?php echo esc_html($course->difficulty_level); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($course->description)) : ?>
                            <div class="cryptoschool-course-description">
                                <?php echo wp_kses_post(wp_trim_words($course->description, 20)); ?>
                            </div>
                        <?php endif; ?>
                        <div class="cryptoschool-course-meta">
                            <div class="cryptoschool-course-lessons">
                                <span class="cryptoschool-meta-icon dashicons dashicons-media-text"></span>
                                <span class="cryptoschool-meta-value">
                                    <?php 
                                    $lessons_count = isset($course->lessons_count) ? $course->lessons_count : 0;
                                    printf(
                                        _n('%s урок', '%s уроков', $lessons_count, 'cryptoschool'),
                                        $lessons_count
                                    ); 
                                    ?>
                                </span>
                            </div>
                        </div>
                        <div class="cryptoschool-course-actions">
                            <a href="<?php echo esc_url(get_permalink()); ?>?course=<?php echo esc_attr($course->slug); ?>" class="cryptoschool-button cryptoschool-button-primary">
                                <?php _e('Подробнее', 'cryptoschool'); ?>
                            </a>
                            <?php if (is_user_logged_in() && isset($course->has_access) && $course->has_access) : ?>
                                <a href="<?php echo esc_url(get_permalink()); ?>?course=<?php echo esc_attr($course->slug); ?>" class="cryptoschool-button cryptoschool-button-secondary">
                                    <?php _e('Начать обучение', 'cryptoschool'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <div class="cryptoschool-empty-state">
                <p><?php _e('Курсы не найдены.', 'cryptoschool'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>
