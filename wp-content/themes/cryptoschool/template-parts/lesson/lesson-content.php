<?php
/**
 * Контент урока
 * 
 * @package CryptoSchool
 */

if (!defined('ABSPATH')) {
    exit;
}

$lesson = $args['lesson'];
?>

    <div class="account-article-content">
        <div class="account-article-content__block">
            <?php 
            // Устанавливаем глобальный $post для корректной работы the_content()
            global $post;
            $original_post = $post;
            $post = $lesson->post;
            setup_postdata($post);
            
            // Выводим контент урока через стандартную WordPress функцию
            the_content();
            
            // Восстанавливаем оригинальный $post
            $post = $original_post;
            wp_reset_postdata();
            ?>
        </div>
    </div>
</article>
