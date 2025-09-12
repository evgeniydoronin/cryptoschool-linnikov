<?php
/**
 * Заголовок урока
 * 
 * @package CryptoSchool
 */

if (!defined('ABSPATH')) {
    exit;
}

$lesson = $args['lesson'];
$course = $args['course'];
?>

<article class="account-block palette palette_blurred account-article">
    <div class="account-article__header">
        <div class="account-article__header-column">
            <h6 class="text-small account-article__pretitle">
                <?php 
                $course_url = cryptoschool_get_localized_url('/course/?id=' . $course->id);
                ?>
                <a href="<?php echo esc_url($course_url); ?>" class="color-primary">
                    <?php echo esc_html($course->title); ?>
                </a>
            </h6>
            <h5 class="h6 color-primary account-article__title">
                <?php echo esc_html($lesson->title); ?>
            </h5>
        </div>
        <div class="account-article__header-column">
            <button class="account-article__support">
                <span class="icon-telegram"></span>
                <span class="text-small"><?php _e('Потрібна допомога', 'cryptoschool'); ?></span>
            </button>
        </div>
    </div>
    
    <hr class="account-block__horizontal-row account-article__separator">
