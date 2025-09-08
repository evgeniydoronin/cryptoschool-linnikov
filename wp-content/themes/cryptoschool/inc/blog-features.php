<?php
/**
 * Blog Features
 * 
 * Функции для улучшения функционала блога
 * 
 * @package CryptoSchool
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Рассчитывает примерное время чтения поста
 *
 * @param int $post_id ID поста (необязательно)
 * @return string Время чтения в формате "X мин"
 */
if (!function_exists('cryptoschool_get_reading_time')) {
    function cryptoschool_get_reading_time($post_id = null) {
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        
        $post = get_post($post_id);
        if (!$post) {
            return '5 мин';
        }
        
        // Получаем контент поста
        $content = $post->post_content;
        
        // Удаляем HTML теги и шорткоды
        $content = strip_tags($content);
        $content = strip_shortcodes($content);
        
        // Подсчитываем количество слов (поддержка Unicode/Cyrillic)
        $words = preg_split('/\s+/u', trim($content), -1, PREG_SPLIT_NO_EMPTY);
        $word_count = count($words);
        
        // Средняя скорость чтения - 200 слов в минуту
        $reading_speed = 200;
        
        // Рассчитываем время чтения в минутах
        $reading_time = ceil($word_count / $reading_speed);
        
        // Минимум 1 минута
        if ($reading_time < 1) {
            $reading_time = 1;
        }
        
        return $reading_time . ' мин';
    }
}

/**
 * Шорткод для создания слайдера изображений в постах
 *
 * Использование: [post_slider ids="123,456,789" size="large"]
 *
 * @param array $atts Атрибуты шорткода
 * @return string HTML код слайдера
 */
if (!function_exists('cryptoschool_slider_shortcode')) {
    function cryptoschool_slider_shortcode($atts) {
        // Атрибуты по умолчанию
        $atts = shortcode_atts(array(
            'ids' => '',
            'size' => 'large',
        ), $atts, 'post_slider');
        
        // Проверяем, что указаны ID изображений
        if (empty($atts['ids'])) {
            return '<div class="shortcode-error"><p>Для слайдера необходимо указать ID изображений.<br>Пример: <code>[post_slider ids="123,456,789"]</code></p></div>';
        }
        
        // Разбираем строку ID в массив
        $raw_ids = explode(',', $atts['ids']);
        $image_ids = [];
        $parse_debug = [];
        
        foreach ($raw_ids as $raw_id) {
            $cleaned_id = strip_tags(trim($raw_id));
            $parse_debug[] = "Исходное значение: '$raw_id' -> после очистки: '$cleaned_id'";
            
            if (is_numeric($cleaned_id) && intval($cleaned_id) > 0) {
                $image_ids[] = intval($cleaned_id);
                $parse_debug[] = "✓ ID $cleaned_id принят";
            } else {
                $parse_debug[] = "✗ ID '$cleaned_id' отклонен (не число или <= 0)";
            }
        }
        
        if (empty($image_ids)) {
            $error_message = '<p>Не найдены корректные ID изображений для слайдера.</p>';
            
            // Показываем отладочную информацию администраторам
            if (current_user_can('administrator') && !empty($parse_debug)) {
                $error_message .= '<details style="margin-top: 10px;"><summary>Отладка парсинга ID</summary><ul>';
                foreach ($parse_debug as $debug) {
                    $error_message .= '<li>' . esc_html($debug) . '</li>';
                }
                $error_message .= '</ul></details>';
            }
            
            return '<div class="shortcode-error">' . $error_message . '</div>';
        }
        
        // Генерируем уникальный ID для слайдера
        $slider_id = 'post-slider-' . uniqid();
        
        // Начинаем формировать HTML
        $html = '<div class="slider account-article-content__block blog-article-layout__slider" data-slider="' . esc_attr($slider_id) . '">';
        $html .= '<div class="slider__slides" data-slider-for="' . esc_attr($slider_id) . '" data-slider-slides>';
        
        // Добавляем слайды с изображениями
        $valid_images = 0;
        $debug_info = [];
        
        foreach ($image_ids as $image_id) {
            // $image_id уже является числом
            
            // Проверяем, существует ли вложение
            $attachment = get_post($image_id);
            if (!$attachment || $attachment->post_type !== 'attachment') {
                $debug_info[] = "ID $image_id: вложение не найдено";
                continue;
            }
            
            // Проверяем, является ли вложение изображением
            if (!wp_attachment_is_image($image_id)) {
                $debug_info[] = "ID $image_id: файл не является изображением";
                continue;
            }
            
            // Получаем URL изображения вместо полного HTML
            $image_url = wp_get_attachment_image_url($image_id, $atts['size']);
            if ($image_url) {
                // Генерируем простой img тег без лишних атрибутов
                $html .= '<div class="slider__slide"> <img class="slider__image" src="' . esc_url($image_url) . '"> </div>';
                $valid_images++;
                $debug_info[] = "ID $image_id: ✓ успешно добавлен";
            } else {
                $debug_info[] = "ID $image_id: ошибка получения URL изображения";
            }
        }
        
        // Если нет валидных изображений
        if ($valid_images === 0) {
            $error_message = '<p>Ни одно из указанных изображений не найдено. Проверьте ID изображений.</p>';
            
            // Показываем отладочную информацию администраторам
            if (current_user_can('administrator') && !empty($debug_info)) {
                $error_message .= '<details style="margin-top: 10px;"><summary>Отладочная информация</summary><ul>';
                foreach ($debug_info as $info) {
                    $error_message .= '<li>' . esc_html($info) . '</li>';
                }
                $error_message .= '</ul></details>';
            }
            
            return '<div class="shortcode-error">' . $error_message . '</div>';
        }
        
        $html .= '</div>';
        
        // Добавляем элементы управления только если больше одного изображения
        if ($valid_images > 1) {
            $html .= '<div class="slider__controls">';
            $html .= '<div class="slider-control slider-control-left" data-slider-for="' . esc_attr($slider_id) . '" data-slider-control-left> <span class="icon-nav-arrow-left"></span> </div>';
            $html .= '<div class="slider-control slider-control-right" data-slider-for="' . esc_attr($slider_id) . '" data-slider-control-right> <span class="icon-nav-arrow-right"></span> </div>';
            $html .= '</div>';
            
            // Добавляем навигационные точки
            $html .= '<div class="slider__nav">';
            $nav_index = 0;
            foreach ($image_ids as $image_id) {
                // $image_id уже является числом
                if (wp_get_attachment_image_url($image_id, 'thumbnail')) {
                    $active_class = ($nav_index === 0) ? ' slider__nav-item_active' : '';
                    $html .= '<div class="slider__nav-item' . $active_class . '" data-slider-for="' . esc_attr($slider_id) . '" data-slider-nav-item="' . $nav_index . '"></div>';
                    $nav_index++;
                }
            }
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        // Очищаем HTML от нежелательных тегов и атрибутов
        return cryptoschool_clean_slider_html($html);
    }
}

/**
 * Очищает HTML слайдера от нежелательных тегов и атрибутов
 *
 * @param string $html Исходный HTML
 * @return string Очищенный HTML
 */
if (!function_exists('cryptoschool_clean_slider_html')) {
    function cryptoschool_clean_slider_html($html) {
        // Убираем теги <em> и </em>
        $html = str_replace(['<em>', '</em>'], '', $html);
        
        // Убираем атрибут decoding
        $html = preg_replace('/\s+decoding=["\'][^"\']*["\']/', '', $html);
        
        // Убираем пустые значения в data-атрибутах
        $html = preg_replace('/\s+(data-[a-zA-Z-]+)=""/', ' $1', $html);
        
        // Нормализуем пробелы
        $html = preg_replace('/\s+/', ' ', $html);
        
        return $html;
    }
}

// Регистрируем шорткод
add_shortcode('post_slider', 'cryptoschool_slider_shortcode');

/**
 * Дополнительная очистка контента от WordPress фильтров для слайдеров
 */
function cryptoschool_final_slider_cleanup($content) {
    // Если контент содержит наши слайдеры
    if (strpos($content, 'post-slider-') !== false) {
        // Убираем теги <em> которые WordPress мог добавить
        $content = str_replace(['<em>', '</em>'], '', $content);
        
        // Убираем атрибуты decoding
        $content = preg_replace('/\s+decoding=["\'][^"\']*["\']/', '', $content);
    }
    
    return $content;
}
add_filter('the_content', 'cryptoschool_final_slider_cleanup', 999);

/**
 * Генерирует ссылки для шеринга поста в социальных сетях
 *
 * @param int $post_id ID поста (необязательно)
 * @return array Массив со ссылками для шеринга
 */
if (!function_exists('cryptoschool_get_share_links')) {
    function cryptoschool_get_share_links($post_id = null) {
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        
        $post_url = urlencode(get_permalink($post_id));
        $post_title = urlencode(get_the_title($post_id));
        
        return array(
            'telegram' => 'https://t.me/share/url?url=' . $post_url . '&text=' . $post_title,
            'twitter' => 'https://twitter.com/intent/tweet?url=' . $post_url . '&text=' . $post_title,
            'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . $post_url,
            'linkedin' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $post_url,
            'copy' => get_permalink($post_id)
        );
    }
}

/**
 * Выводит HTML для кнопок шеринга поста
 *
 * @param int $post_id ID поста (необязательно)
 * @param array $networks Массив социальных сетей для отображения (необязательно)
 * @return string HTML код кнопок шеринга
 */
if (!function_exists('cryptoschool_render_share_links')) {
    function cryptoschool_render_share_links($post_id = null, $networks = array()) {
        if (empty($networks)) {
            $networks = array('telegram', 'twitter', 'facebook', 'copy', 'link');
        }
        
        $share_links = cryptoschool_get_share_links($post_id);
        $share_links['link'] = $share_links['linkedin']; // Используем LinkedIn URL для общей ссылки
        
        $icons_map = array(
            'telegram' => 'icon-telegram',
            'twitter' => 'icon-twitter', 
            // 'facebook' => 'icon-facebook', // Используем Discord как заглушку для Facebook
            // 'linkedin' => 'icon-linkedin', // Используем icon-link для LinkedIn
            // 'link' => 'icon-link', // Общая кнопка "поделиться"
            // 'copy' => 'icon-link' // Используем icon-link для копирования
        );
        
        $labels_map = array(
            'telegram' => 'Share on Telegram',
            'twitter' => 'Share on Twitter',
            'facebook' => 'Share on Facebook',
            'linkedin' => 'Share on LinkedIn',
            // 'link' => 'Share',
            // 'copy' => 'Copy link'
        );
        
        $html = '';
        foreach ($networks as $network) {
            if (isset($share_links[$network]) && isset($icons_map[$network])) {
                $url = esc_url($share_links[$network]);
                $icon = esc_attr($icons_map[$network]);
                $label = esc_attr($labels_map[$network]);
                
                if ($network === 'copy') {
                    $html .= '<button class="blog-article-layout__content-share-link social-media-link" onclick="cryptoschoolCopyToClipboard(\'' . esc_js($share_links['copy']) . '\')" aria-label="' . $label . '" title="' . $label . '">';
                    $html .= '<span class="' . $icon . '"></span>';
                    $html .= '</button>';
                } else {
                    $html .= '<a href="' . $url . '" class="blog-article-layout__content-share-link social-media-link" target="_blank" rel="noopener noreferrer" aria-label="' . $label . '" title="' . $label . '">';
                    $html .= '<span class="' . $icon . '"></span>';
                    $html .= '</a>';
                }
            }
        }
        
        return $html;
    }
}

/**
 * Генерирует хлебные крошки для постов с поддержкой WPML
 *
 * @param int $post_id ID поста (необязательно)
 * @return string HTML код хлебных крошек
 */
if (!function_exists('cryptoschool_get_breadcrumbs')) {
    function cryptoschool_get_breadcrumbs($post_id = null) {
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        
        $breadcrumbs = array();
        
        // Получаем текущий язык
        $current_lang = defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : 'uk';
        
        // Переводы для разных языков
        $translations = array(
            'uk' => array(
                'home' => 'Головна',
                'blog' => 'Блог'
            ),
            'ru' => array(
                'home' => 'Главная', 
                'blog' => 'Блог'
            ),
            'en' => array(
                'home' => 'Home',
                'blog' => 'Blog'
            )
        );
        
        $t = isset($translations[$current_lang]) ? $translations[$current_lang] : $translations['uk'];
        
        // 1. Главная страница
        // Получаем URL главной страницы с учетом текущего языка
        if (function_exists('apply_filters')) {
            $home_page_id = apply_filters('wpml_object_id', get_option('page_on_front'), 'page', true);
            $home_url = $home_page_id ? get_permalink($home_page_id) : home_url('/');
        } else {
            $home_url = home_url('/');
        }
        
        $breadcrumbs[] = array(
            'title' => $t['home'],
            'url' => $home_url,
            'active' => false
        );
        
        // 2. Страница блога
        $blog_page_id = get_option('page_for_posts');
        if ($blog_page_id) {
            // Получаем переведенную версию страницы блога
            $translated_blog_page_id = apply_filters('wpml_object_id', $blog_page_id, 'page', true);
            $blog_url = get_permalink($translated_blog_page_id);
        } else {
            // Если страница блога не задана, используем архив постов
            $blog_url = get_post_type_archive_link('post');
        }
        
        $breadcrumbs[] = array(
            'title' => $t['blog'],
            'url' => $blog_url,
            'active' => false
        );
        
        // 3. Основная категория поста (опционально)
        $categories = get_the_category($post_id);
        if (!empty($categories)) {
            $main_category = $categories[0]; // Берем первую категорию как основную
            
            // Получаем переведенную версию категории
            $translated_category_id = apply_filters('wpml_object_id', $main_category->term_id, 'category', true);
            $translated_category = get_category($translated_category_id);
            
            if ($translated_category && !is_wp_error($translated_category)) {
                $breadcrumbs[] = array(
                    'title' => $translated_category->name,
                    'url' => get_category_link($translated_category->term_id),
                    'active' => false
                );
            }
        }
        
        // 4. Текущий пост
        $breadcrumbs[] = array(
            'title' => get_the_title($post_id),
            'url' => get_permalink($post_id),
            'active' => true
        );
        
        return cryptoschool_render_breadcrumbs($breadcrumbs);
    }
}

/**
 * Отображает хлебные крошки в HTML формате
 *
 * @param array $breadcrumbs Массив хлебных крошек
 * @return string HTML код
 */
if (!function_exists('cryptoschool_render_breadcrumbs')) {
    function cryptoschool_render_breadcrumbs($breadcrumbs) {
        if (empty($breadcrumbs)) {
            return '';
        }
        
        $html = '';
        $total = count($breadcrumbs);
        
        foreach ($breadcrumbs as $index => $crumb) {
            $is_last = ($index === $total - 1);
            $active_class = $crumb['active'] ? ' breadcrumbs__link_active' : '';
            
            if ($crumb['active'] || $is_last) {
                // Активная ссылка (текущая страница)
                $html .= '<span class="breadcrumbs__link' . $active_class . '">';
                $html .= esc_html($crumb['title']);
                $html .= '</span>';
            } else {
                // Обычная ссылка
                $html .= '<a href="' . esc_url($crumb['url']) . '" class="breadcrumbs__link">';
                $html .= esc_html($crumb['title']);
                $html .= '</a>';
            }
            
            // Добавляем стрелку между элементами (кроме последнего)
            if (!$is_last) {
                $html .= '<div class="breadcrumbs__arrow">';
                $html .= '<span class="icon-nav-arrow-right"></span>';
                $html .= '</div>';
            }
        }
        
        return $html;
    }
}

/**
 * Получает последние посты для слайдера рекомендаций
 *
 * @param int $limit Количество постов для получения (по умолчанию 4)
 * @param int $exclude_post_id ID поста для исключения из выборки (обычно текущий пост)
 * @return array Массив объектов постов
 */
if (!function_exists('cryptoschool_get_recent_posts')) {
    function cryptoschool_get_recent_posts($limit = 4, $exclude_post_id = null) {
        // Если ID не передан, используем текущий пост
        if (!$exclude_post_id) {
            $exclude_post_id = get_the_ID();
        }
        
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
            'post__not_in' => array($exclude_post_id), // Исключаем текущий пост
        );
        
        // Если активен WPML, получаем посты только для текущего языка
        if (function_exists('apply_filters')) {
            $current_lang = apply_filters('wpml_current_language', null);
            if ($current_lang) {
                // Добавляем параметры для WPML
                $args['suppress_filters'] = false;
            }
        }
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            return $query->posts;
        }
        
        return array();
    }
}

/**
 * Генерирует HTML карточки поста для слайдера рекомендаций
 *
 * @param WP_Post $post Объект поста
 * @return string HTML код карточки поста
 */
if (!function_exists('cryptoschool_render_blog_card')) {
    function cryptoschool_render_blog_card($post) {
        if (!$post) {
            return '';
        }
        
        $post_id = $post->ID;
        $title = get_the_title($post_id);
        $permalink = get_permalink($post_id);
        $excerpt = wp_trim_words(get_the_excerpt($post_id), 20, '...');
        
        // Получаем миниатюру поста
        $thumbnail_html = '';
        if (has_post_thumbnail($post_id)) {
            $thumbnail_url = get_the_post_thumbnail_url($post_id, 'large');
            $thumbnail_html = '<img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr($title) . '">';
        } else {
            // Используем placeholder изображение
            $placeholder_url = get_template_directory_uri() . '/frontend-source/dist/assets/img/temp/blog-article-placeholder.png';
            $thumbnail_html = '<img src="' . esc_url($placeholder_url) . '" alt="' . esc_attr($title) . '">';
        }
        
        $html = '<div class="blog-article-card">';
        $html .= '<div class="blog-article-card__image">' . $thumbnail_html . '</div>';
        $html .= '<div class="blog-article-card__body">';
        $html .= '<div class="blog-article-card__title text">' . esc_html($title) . '</div>';
        $html .= '<div class="blog-article-card__text text-small">' . esc_html($excerpt) . '</div>';
        $html .= '</div>';
        $html .= '<div class="blog-article-card__footer">';
        $html .= '<a href="' . esc_url($permalink) . '" class="blog-article-card__button text-small">Читать статью</a>';
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
}

/**
 * Генерирует HTML слайдера рекомендаций с последними постами
 *
 * @param int $limit Количество постов (по умолчанию 4)
 * @return string HTML код слайдера
 */
if (!function_exists('cryptoschool_render_recommendations_slider')) {
    function cryptoschool_render_recommendations_slider($limit = 4) {
        $recent_posts = cryptoschool_get_recent_posts($limit);
        
        if (empty($recent_posts)) {
            return '<div class="slider blog-article-page__recommendations" data-slider="recommendations-slider"><div class="slider__slides" data-slider-for="recommendations-slider" data-slider-slides"><p>Нет доступных рекомендаций.</p></div></div>';
        }
        
        $html = '<div class="slider blog-article-page__recommendations" data-slider="recommendations-slider">';
        $html .= '<div class="slider__slides" data-slider-for="recommendations-slider" data-slider-slides>';
        
        foreach ($recent_posts as $post) {
            $html .= cryptoschool_render_blog_card($post);
        }
        
        $html .= '</div></div>';
        
        return $html;
    }
}

/**
 * Генерирует хлебные крошки для архивных страниц (категории, теги, общий архив блога)
 *
 * @return string HTML код хлебных крошек
 */
if (!function_exists('cryptoschool_get_archive_breadcrumbs')) {
    function cryptoschool_get_archive_breadcrumbs() {
        $breadcrumbs = array();
        
        // Получаем текущий язык
        $current_lang = defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : 'uk';
        
        // Переводы для разных языков
        $translations = array(
            'uk' => array(
                'home' => 'Головна',
                'blog' => 'Блог',
                'category' => 'Категория',
                'tag' => 'Тег',
                'author' => 'Автор'
            ),
            'ru' => array(
                'home' => 'Главная', 
                'blog' => 'Блог',
                'category' => 'Категория',
                'tag' => 'Тег',
                'author' => 'Автор'
            ),
            'en' => array(
                'home' => 'Home',
                'blog' => 'Blog',
                'category' => 'Category',
                'tag' => 'Tag',
                'author' => 'Author'
            )
        );
        
        $t = isset($translations[$current_lang]) ? $translations[$current_lang] : $translations['uk'];
        
        // 1. Главная страница
        if (function_exists('apply_filters')) {
            $home_page_id = apply_filters('wpml_object_id', get_option('page_on_front'), 'page', true);
            $home_url = $home_page_id ? get_permalink($home_page_id) : home_url('/');
        } else {
            $home_url = home_url('/');
        }
        
        $breadcrumbs[] = array(
            'title' => $t['home'],
            'url' => $home_url,
            'active' => false
        );
        
        // 2. Страница блога
        $blog_page_id = get_option('page_for_posts');
        if ($blog_page_id) {
            $translated_blog_page_id = apply_filters('wpml_object_id', $blog_page_id, 'page', true);
            $blog_url = get_permalink($translated_blog_page_id);
        } else {
            $blog_url = get_post_type_archive_link('post');
        }
        
        // Определяем, нужна ли ссылка на блог (если мы не на главной странице блога)
        $blog_active = false;
        $add_blog_link = true;
        
        // 3. Определяем тип архива и добавляем соответствующие крошки
        if (is_category()) {
            // Архив категории
            $category = get_queried_object();
            if ($category) {
                // Получаем переведенную версию категории
                if (function_exists('apply_filters')) {
                    $translated_category_id = apply_filters('wpml_object_id', $category->term_id, 'category', true);
                    $translated_category = get_category($translated_category_id);
                } else {
                    $translated_category = $category;
                }
                
                if ($translated_category && !is_wp_error($translated_category)) {
                    $breadcrumbs[] = array(
                        'title' => $t['blog'],
                        'url' => $blog_url,
                        'active' => false
                    );
                    
                    $breadcrumbs[] = array(
                        'title' => $translated_category->name,
                        'url' => get_category_link($translated_category->term_id),
                        'active' => true
                    );
                    $add_blog_link = false;
                }
            }
        } elseif (is_tag()) {
            // Архив тега
            $tag = get_queried_object();
            if ($tag) {
                // Получаем переведенную версию тега
                if (function_exists('apply_filters')) {
                    $translated_tag_id = apply_filters('wpml_object_id', $tag->term_id, 'post_tag', true);
                    $translated_tag = get_tag($translated_tag_id);
                } else {
                    $translated_tag = $tag;
                }
                
                if ($translated_tag && !is_wp_error($translated_tag)) {
                    $breadcrumbs[] = array(
                        'title' => $t['blog'],
                        'url' => $blog_url,
                        'active' => false
                    );
                    
                    $breadcrumbs[] = array(
                        'title' => $translated_tag->name,
                        'url' => get_tag_link($translated_tag->term_id),
                        'active' => true
                    );
                    $add_blog_link = false;
                }
            }
        } elseif (is_author()) {
            // Архив автора
            $author = get_queried_object();
            if ($author) {
                $breadcrumbs[] = array(
                    'title' => $t['blog'],
                    'url' => $blog_url,
                    'active' => false
                );
                
                $breadcrumbs[] = array(
                    'title' => $t['author'] . ': ' . $author->display_name,
                    'url' => get_author_posts_url($author->ID),
                    'active' => true
                );
                $add_blog_link = false;
            }
        } elseif (is_home() || is_archive()) {
            // Общий архив блога или главная страница блога
            $blog_active = true;
        }
        
        // Добавляем ссылку на блог если нужно
        if ($add_blog_link) {
            $breadcrumbs[] = array(
                'title' => $t['blog'],
                'url' => $blog_url,
                'active' => $blog_active
            );
        }
        
        return cryptoschool_render_breadcrumbs($breadcrumbs);
    }
}

/**
 * Получает все категории с постами, отсортированные по имени
 *
 * @return array Массив объектов категорий
 */
if (!function_exists('cryptoschool_get_all_categories_with_posts')) {
    function cryptoschool_get_all_categories_with_posts() {
        $args = array(
            'taxonomy' => 'category',
            'hide_empty' => true, // Только категории с постами
            'orderby' => 'name',
            'order' => 'ASC',
        );
        
        // Если активен WPML, получаем категории для текущего языка
        if (function_exists('apply_filters')) {
            $current_lang = apply_filters('wpml_current_language', null);
            if ($current_lang) {
                // Добавляем параметры для WPML
                $args['suppress_filters'] = false;
            }
        }
        
        $categories = get_categories($args);
        
        if (!empty($categories) && !is_wp_error($categories)) {
            return $categories;
        }
        
        return array();
    }
}

/**
 * Генерирует HTML для всех категорий с постами
 *
 * @return string HTML код категорий
 */
if (!function_exists('cryptoschool_render_all_categories')) {
    function cryptoschool_render_all_categories() {
        $categories = cryptoschool_get_all_categories_with_posts();
        
        if (empty($categories)) {
            return '';
        }
        
        $html = '';
        
        foreach ($categories as $category) {
            // Получаем переведенную версию категории для текущего языка
            if (function_exists('apply_filters')) {
                $translated_category_id = apply_filters('wpml_object_id', $category->term_id, 'category', true);
                $translated_category = get_category($translated_category_id);
            } else {
                $translated_category = $category;
            }
            
            if ($translated_category && !is_wp_error($translated_category)) {
                $category_link = get_category_link($translated_category->term_id);
                
                $html .= '<a href="' . esc_url($category_link) . '" class="chip">';
                $html .= '<div class="text chip__text">';
                $html .= esc_html($translated_category->name);
                $html .= '</div>';
                $html .= '</a>';
            }
        }
        
        return $html;
    }
}

/**
 * Получает посты для архивной страницы с учетом текущего контекста
 *
 * @param int $posts_per_page Количество постов на странице (по умолчанию 12)
 * @return WP_Query Объект запроса WordPress
 */
if (!function_exists('cryptoschool_get_archive_posts')) {
    function cryptoschool_get_archive_posts($posts_per_page = 12) {
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $posts_per_page,
            'orderby' => 'date',
            'order' => 'DESC',
            'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
        );
        
        // Если мы находимся в архиве категории, добавляем фильтр по категории
        if (is_category()) {
            $category = get_queried_object();
            if ($category && !is_wp_error($category)) {
                $args['cat'] = $category->term_id;
            }
        }
        
        // Если мы находимся в архиве тега, добавляем фильтр по тегу
        if (is_tag()) {
            $tag = get_queried_object();
            if ($tag && !is_wp_error($tag)) {
                $args['tag_id'] = $tag->term_id;
            }
        }
        
        // Если активен WPML, добавляем поддержку языка
        if (function_exists('apply_filters')) {
            $current_lang = apply_filters('wpml_current_language', null);
            if ($current_lang) {
                $args['suppress_filters'] = false;
            }
        }
        
        return new WP_Query($args);
    }
}

/**
 * Генерирует HTML для карточек постов в архиве
 *
 * @param int $posts_per_page Количество постов на странице (по умолчанию 12)
 * @return array Массив с ключами 'html' и 'query'
 */
if (!function_exists('cryptoschool_render_archive_posts')) {
    function cryptoschool_render_archive_posts($posts_per_page = 12) {
        $posts_query = cryptoschool_get_archive_posts($posts_per_page);
        
        if (!$posts_query->have_posts()) {
            return array(
                'html' => '<p class="text">Посты не найдены.</p>',
                'query' => $posts_query
            );
        }
        
        $html = '';
        
        while ($posts_query->have_posts()) {
            $posts_query->the_post();
            $html .= cryptoschool_render_blog_card(get_post());
        }
        
        wp_reset_postdata();
        
        return array(
            'html' => $html,
            'query' => $posts_query
        );
    }
}

/**
 * Генерирует HTML для пагинации архивных страниц
 *
 * @param WP_Query $query Объект запроса WordPress (необязательно, если не передан - используется глобальный)
 * @return string HTML код пагинации
 */
if (!function_exists('cryptoschool_render_pagination')) {
    function cryptoschool_render_pagination($query = null) {
        global $wp_query;
        
        // Если запрос не передан, используем глобальный
        if (!$query) {
            $query = $wp_query;
        }
        
        // Получаем информацию о пагинации
        $current_page = max(1, get_query_var('paged'));
        $total_pages = $query->max_num_pages;
        
        // Если всего одна страница или меньше, не показываем пагинацию
        if ($total_pages <= 1) {
            return '';
        }
        
        $html = '<div class="pagination blog__pagination">';
        
        // Кнопка "Предыдущая страница"
        if ($current_page > 1) {
            $prev_url = get_pagenum_link($current_page - 1);
            $html .= '<a href="' . esc_url($prev_url) . '" class="pagination__item pagination__item_control pagination__item_control-left">';
            $html .= '<span class="icon-nav-arrow-left"></span>';
            $html .= '</a>';
        }
        
        // Генерируем номера страниц
        $range = 2; // Количество страниц до и после текущей
        $start_page = max(1, $current_page - $range);
        $end_page = min($total_pages, $current_page + $range);
        
        // Первая страница (если нужно)
        if ($start_page > 1) {
            $html .= '<a href="' . esc_url(get_pagenum_link(1)) . '" class="pagination__item">1</a>';
            
            if ($start_page > 2) {
                $html .= '<span class="pagination__item">...</span>';
            }
        }
        
        // Основные страницы
        for ($i = $start_page; $i <= $end_page; $i++) {
            $active_class = ($i == $current_page) ? ' pagination__item_active' : '';
            
            if ($i == $current_page) {
                $html .= '<span class="pagination__item' . $active_class . '">' . $i . '</span>';
            } else {
                $html .= '<a href="' . esc_url(get_pagenum_link($i)) . '" class="pagination__item">' . $i . '</a>';
            }
        }
        
        // Последняя страница (если нужно)
        if ($end_page < $total_pages) {
            if ($end_page < $total_pages - 1) {
                $html .= '<span class="pagination__item">...</span>';
            }
            
            $html .= '<a href="' . esc_url(get_pagenum_link($total_pages)) . '" class="pagination__item">' . $total_pages . '</a>';
        }
        
        // Кнопка "Следующая страница"
        if ($current_page < $total_pages) {
            $next_url = get_pagenum_link($current_page + 1);
            $html .= '<a href="' . esc_url($next_url) . '" class="pagination__item pagination__item_control pagination__item_control-right">';
            $html .= '<span class="icon-nav-arrow-right"></span>';
            $html .= '</a>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
}

/**
 * ==========================================
 * ДОКУМЕНТАЦИЯ ПО ИСПОЛЬЗОВАНИЮ ШОРТКОДА
 * ==========================================
 * 
 * Шорткод [post_slider] позволяет добавить слайдер изображений в любое место контента поста.
 * 
 * БАЗОВОЕ ИСПОЛЬЗОВАНИЕ:
 * [post_slider ids="123,456,789"]
 * 
 * РАСШИРЕННОЕ ИСПОЛЬЗОВАНИЕ:
 * [post_slider ids="123,456,789" size="large"]
 * 
 * АТРИБУТЫ:
 * - ids (обязательный): Список ID изображений через запятую. Пример: "123,456,789"
 * - size (опциональный): Размер изображений. Доступные размеры: thumbnail, medium, large, full. По умолчанию: large
 * 
 * КАК ПОЛУЧИТЬ ID ИЗОБРАЖЕНИЙ:
 * 1. В редакторе поста нажмите "Добавить медиафайл"
 * 2. Выберите нужные изображения из медиабиблиотеки
 * 3. В правой панели будет указан ID каждого изображения
 * 4. Скопируйте ID и вставьте в шорткод через запятую
 * 
 * ПРИМЕРЫ:
 * [post_slider ids="123"]                          - Один слайд
 * [post_slider ids="123,456"]                      - Два слайда
 * [post_slider ids="123,456,789" size="medium"]    - Три слайда среднего размера
 * 
 * ОСОБЕННОСТИ:
 * - Если указан только один ID, навигация не отображается
 * - Неправильные ID игнорируются
 * - Если не найдено ни одного изображения, показывается сообщение об ошибке
 * - Каждый слайдер имеет уникальный ID для корректной работы нескольких слайдеров на одной странице
 */