<?php
/**
 * Comments System
 * 
 * AJAX система комментариев для блога
 * 
 * @package CryptoSchool
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Регистрация REST API endpoints для комментариев
 */
function cryptoschool_register_comments_api() {
    register_rest_route('cryptoschool/v1', '/comments/(?P<post_id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'cryptoschool_get_comments_api',
        'permission_callback' => '__return_true',
        'args' => array(
            'post_id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param);
                }
            ),
            'page' => array(
                'default' => 1,
                'sanitize_callback' => 'absint'
            ),
            'per_page' => array(
                'default' => 3,
                'sanitize_callback' => 'absint'
            ),
            'sort' => array(
                'default' => 'newest',
                'sanitize_callback' => 'sanitize_text_field'
            )
        )
    ));

    register_rest_route('cryptoschool/v1', '/comments', array(
        'methods' => 'POST',
        'callback' => 'cryptoschool_add_comment_api',
        'permission_callback' => 'is_user_logged_in',
        'args' => array(
            'post_id' => array(
                'required' => true,
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param);
                }
            ),
            'content' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_textarea_field'
            ),
            'parent' => array(
                'default' => 0,
                'sanitize_callback' => 'absint'
            )
        )
    ));

    register_rest_route('cryptoschool/v1', '/comments/(?P<comment_id>\d+)/like', array(
        'methods' => 'POST',
        'callback' => 'cryptoschool_like_comment_api',
        'permission_callback' => 'is_user_logged_in',
        'args' => array(
            'comment_id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param);
                }
            )
        )
    ));
}
add_action('rest_api_init', 'cryptoschool_register_comments_api');

/**
 * Получить комментарии для поста (API endpoint)
 */
function cryptoschool_get_comments_api($request) {
    $post_id = $request['post_id'];
    $page = $request['page'];
    $per_page = $request['per_page'];
    $sort = $request['sort'];

    // Проверяем, что пост существует
    if (!get_post($post_id)) {
        return new WP_Error('post_not_found', 'Post not found', array('status' => 404));
    }

    // Получаем комментарии
    $comments_data = cryptoschool_get_comments_with_replies($post_id, $page, $per_page, $sort);
    
    return rest_ensure_response($comments_data);
}

/**
 * Добавить комментарий (API endpoint)
 */
function cryptoschool_add_comment_api($request) {
    $post_id = $request['post_id'];
    $content = $request['content'];
    $parent = $request['parent'];
    
    // Проверяем nonce
    if (!wp_verify_nonce($request->get_header('X-WP-Nonce'), 'wp_rest')) {
        return new WP_Error('invalid_nonce', 'Invalid nonce', array('status' => 403));
    }

    // Добавляем комментарий
    $comment_data = array(
        'comment_post_ID' => $post_id,
        'comment_content' => $content,
        'comment_parent' => $parent,
        'user_id' => get_current_user_id(),
        'comment_approved' => 1 // Автоматическое одобрение для авторизованных пользователей
    );

    $comment_id = wp_insert_comment($comment_data);
    
    if ($comment_id) {
        $comment = get_comment($comment_id);
        $formatted_comment = cryptoschool_format_comment($comment);
        
        return rest_ensure_response(array(
            'success' => true,
            'comment' => $formatted_comment
        ));
    } else {
        return new WP_Error('comment_failed', 'Failed to add comment', array('status' => 500));
    }
}

/**
 * Лайкнуть комментарий (API endpoint)
 */
function cryptoschool_like_comment_api($request) {
    $comment_id = $request['comment_id'];
    $user_id = get_current_user_id();
    
    // Проверяем, что комментарий существует
    if (!get_comment($comment_id)) {
        return new WP_Error('comment_not_found', 'Comment not found', array('status' => 404));
    }

    // Получаем текущие лайки
    $likes = get_comment_meta($comment_id, '_comment_likes', true);
    if (!is_array($likes)) {
        $likes = array();
    }

    // Проверяем, лайкал ли уже пользователь
    if (in_array($user_id, $likes)) {
        // Убираем лайк
        $likes = array_diff($likes, array($user_id));
        $action = 'unliked';
    } else {
        // Добавляем лайк
        $likes[] = $user_id;
        $action = 'liked';
    }

    // Сохраняем лайки
    update_comment_meta($comment_id, '_comment_likes', $likes);
    
    return rest_ensure_response(array(
        'success' => true,
        'action' => $action,
        'likes_count' => count($likes)
    ));
}

/**
 * Получить комментарии с ответами
 */
function cryptoschool_get_comments_with_replies($post_id, $page = 1, $per_page = 3, $sort = 'newest') {
    // Определяем порядок сортировки
    $order = ($sort === 'oldest') ? 'ASC' : 'DESC';
    
    // Получаем общее количество комментариев
    $total_comments = wp_count_comments($post_id);
    $total = $total_comments->approved;

    // Получаем родительские комментарии
    $comments = get_comments(array(
        'post_id' => $post_id,
        'parent' => 0,
        'status' => 'approve',
        'order' => $order,
        'orderby' => 'comment_date',
        'number' => $per_page,
        'offset' => ($page - 1) * $per_page
    ));

    $formatted_comments = array();
    
    foreach ($comments as $comment) {
        $formatted_comment = cryptoschool_format_comment($comment);
        
        // Получаем ответы для этого комментария
        $replies = get_comments(array(
            'post_id' => $post_id,
            'parent' => $comment->comment_ID,
            'status' => 'approve',
            'order' => 'ASC',
            'orderby' => 'comment_date'
        ));

        $formatted_replies = array();
        foreach ($replies as $reply) {
            $formatted_replies[] = cryptoschool_format_comment($reply);
        }
        
        $formatted_comment['replies'] = $formatted_replies;
        $formatted_comments[] = $formatted_comment;
    }

    return array(
        'comments' => $formatted_comments,
        'total' => $total,
        'loaded' => ($page - 1) * $per_page + count($comments),
        'hasMore' => (($page * $per_page) < $total),
        'currentPage' => $page
    );
}

/**
 * Форматировать комментарий для вывода
 */
function cryptoschool_format_comment($comment) {
    $user = get_user_by('ID', $comment->user_id);
    $likes = get_comment_meta($comment->comment_ID, '_comment_likes', true);
    $likes_count = is_array($likes) ? count($likes) : 0;
    $user_liked = is_user_logged_in() && is_array($likes) && in_array(get_current_user_id(), $likes);
    
    // Получаем аватар пользователя
    $avatar_url = get_avatar_url($comment->user_id, array('size' => 40));
    
    // Форматируем время
    $time_diff = human_time_diff(strtotime($comment->comment_date), current_time('timestamp'));
    
    return array(
        'id' => $comment->comment_ID,
        'author' => $user ? $user->display_name : $comment->comment_author,
        'avatar' => $avatar_url,
        'text' => $comment->comment_content,
        'date' => $time_diff,
        'likes' => $likes_count,
        'userLiked' => $user_liked,
        'canReply' => is_user_logged_in(),
        'canLike' => is_user_logged_in() && get_current_user_id() != $comment->user_id
    );
}

/**
 * Добавляем поддержку комментариев для постов
 */
function cryptoschool_enable_comments_support() {
    add_post_type_support('post', 'comments');
    
    // Включаем комментарии по умолчанию для новых постов
    add_filter('wp_insert_post_data', function($data) {
        if ($data['post_type'] === 'post') {
            $data['comment_status'] = 'open';
        }
        return $data;
    });
}
add_action('init', 'cryptoschool_enable_comments_support');

/**
 * Включаем комментарии для существующих постов
 */
function cryptoschool_enable_existing_posts_comments() {
    global $wpdb;
    
    // Включаем комментарии для всех постов
    $wpdb->query("UPDATE {$wpdb->posts} SET comment_status = 'open' WHERE post_type = 'post' AND post_status = 'publish'");
}
// Раскомментируйте следующую строку, если нужно включить комментарии для существующих постов
// add_action('admin_init', 'cryptoschool_enable_existing_posts_comments');

/**
 * Подключаем скрипты комментариев
 */
function cryptoschool_enqueue_comments_scripts() {
    if (is_single() && comments_open()) {
        wp_enqueue_script(
            'cryptoschool-comments',
            get_template_directory_uri() . '/assets/js/comments-system.js',
            array('jquery'),
            filemtime(get_template_directory() . '/assets/js/comments-system.js'),
            true
        );

        // Передаем данные в JavaScript
        wp_localize_script('cryptoschool-comments', 'cryptoschoolComments', array(
            'apiUrl' => rest_url('cryptoschool/v1/'),
            'postId' => get_the_ID(),
            'nonce' => wp_create_nonce('wp_rest'),
            'isLoggedIn' => is_user_logged_in(),
            'currentUserId' => get_current_user_id()
        ));
    }
}
add_action('wp_enqueue_scripts', 'cryptoschool_enqueue_comments_scripts');

/**
 * Выводит блок комментариев для поста
 */
function cryptoschool_render_comments_section() {
    if (!comments_open() && get_comments_number() == 0) {
        return;
    }

    $post_id = get_the_ID();
    $comments_count = wp_count_comments($post_id);
    $total_comments = $comments_count->approved;
    
    ?>
    <div class="blog-article-comments palette palette_hide-mobile" data-post-id="<?php echo esc_attr($post_id); ?>">
        <div class="blog-article-comments__header">
            <div class="blog-article-comments__title text-small">
                <span class="comments-count"><?php echo $total_comments; ?></span> Comments
            </div>
            <div class="blog-article-comments__settings">
                <div class="blog-article-comments__settings-item">
                    <label>Sort by</label>
                    <button class="blog-article-comments__sort-button" data-comments-sorter>
                        <span data-comments-sorter-label>Newest</span>
                        <span class="icon-sort blog-article-comments__sort-button-icon"></span>
                    </button>
                </div>
            </div>
        </div>

        <?php if (is_user_logged_in()) : ?>
            <div class="blog-article-comments__compose">
                <?php echo get_avatar(get_current_user_id(), 40, '', '', array('class' => 'blog-article-comments__avatar')); ?>
                <div class="blog-article-comments__textbox">
                    <textarea placeholder="Add a comment..." class="blog-article-comments__textarea" id="new-comment-text"></textarea>
                    <div class="blog-article-comments__publish">
                        <label for="comments-upload" class="blog-article-comments__publish-upload">
                            <input id="comments-upload" type="file" accept="image/*,video/*">
                            <span class="icon-picture"></span>
                            <div>Фото или видео</div>
                        </label>
                        <button class="blog-article-comments__send" id="submit-comment">Post</button>
                    </div>
                </div>
            </div>
        <?php else : ?>
            <div class="blog-article-comments__login-notice">
                <p><a href="<?php echo wp_login_url(get_permalink()); ?>">Войдите</a>, чтобы оставить комментарий.</p>
            </div>
        <?php endif; ?>

        <div class="blog-article-comments__list" id="comments-list">
            <!-- Комментарии будут загружены через AJAX -->
            <div class="comments-loading">Загрузка комментариев...</div>
        </div>

        <button class="blog-article-comments__load" id="load-more-comments" style="display: none;">
            Load <span class="remaining-count">0</span> more comments
        </button>
    </div>
    <?php
}