<?php
/**
 * Universal Breadcrumbs
 * 
 * Универсальные хлебные крошки для всех страниц сайта
 * 
 * @package CryptoSchool
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Универсальная функция генерации хлебных крошек для любых страниц
 *
 * @param array $options Опции для настройки хлебных крошек
 * @return string HTML код хлебных крошек
 */
if (!function_exists('cryptoschool_get_page_breadcrumbs')) {
    function cryptoschool_get_page_breadcrumbs($options = array()) {
        // Опции по умолчанию
        $defaults = array(
            'show_home' => true,
            'home_text' => '',
            'separator' => '<div class="breadcrumbs__arrow"><span class="icon-nav-arrow-right"></span></div>',
            'current_page_link' => false, // Делать ли текущую страницу ссылкой
        );
        
        $options = array_merge($defaults, $options);
        
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
                'author' => 'Автор',
                'search' => 'Результаты поиска',
                '404' => 'Страница не найдена'
            ),
            'ru' => array(
                'home' => 'Главная', 
                'blog' => 'Блог',
                'category' => 'Категория',
                'tag' => 'Тег',
                'author' => 'Автор',
                'search' => 'Результаты поиска',
                '404' => 'Страница не найдена'
            ),
            'en' => array(
                'home' => 'Home',
                'blog' => 'Blog',
                'category' => 'Category',
                'tag' => 'Tag',
                'author' => 'Author',
                'search' => 'Search Results',
                '404' => 'Page Not Found'
            )
        );
        
        $t = isset($translations[$current_lang]) ? $translations[$current_lang] : $translations['uk'];
        
        // 1. Главная страница (если нужно показывать)
        if ($options['show_home']) {
            // Получаем URL главной страницы с учетом текущего языка
            if (function_exists('apply_filters')) {
                $home_page_id = apply_filters('wpml_object_id', get_option('page_on_front'), 'page', true);
                $home_url = $home_page_id ? get_permalink($home_page_id) : home_url('/');
            } else {
                $home_url = home_url('/');
            }
            
            $home_text = !empty($options['home_text']) ? $options['home_text'] : $t['home'];
            
            $breadcrumbs[] = array(
                'title' => $home_text,
                'url' => $home_url,
                'active' => is_front_page()
            );
        }
        
        // 2. Определяем контекст и добавляем соответствующие крошки
        if (is_front_page()) {
            // Главная страница - ничего не добавляем
        } elseif (is_home()) {
            // Страница блога
            $blog_page_id = get_option('page_for_posts');
            if ($blog_page_id) {
                $translated_blog_page_id = apply_filters('wpml_object_id', $blog_page_id, 'page', true);
                $breadcrumbs[] = array(
                    'title' => get_the_title($translated_blog_page_id),
                    'url' => get_permalink($translated_blog_page_id),
                    'active' => true
                );
            } else {
                $breadcrumbs[] = array(
                    'title' => $t['blog'],
                    'url' => get_post_type_archive_link('post'),
                    'active' => true
                );
            }
        } elseif (is_single()) {
            // Отдельный пост - используем существующую функцию
            return cryptoschool_get_breadcrumbs();
        } elseif (is_page()) {
            // Страница
            global $post;
            $page = $post;
            
            // Создаем иерархию родительских страниц
            $parents = array();
            $current_page_id = $page->ID;
            
            while ($current_page_id) {
                $current_page = get_post($current_page_id);
                if (!$current_page) break;
                
                // Получаем переведенную версию страницы
                if (function_exists('apply_filters')) {
                    $translated_page_id = apply_filters('wpml_object_id', $current_page->ID, 'page', true);
                    $translated_page = get_post($translated_page_id);
                } else {
                    $translated_page = $current_page;
                }
                
                if ($translated_page) {
                    array_unshift($parents, $translated_page);
                }
                
                $current_page_id = $current_page->post_parent;
            }
            
            // Добавляем родительские страницы
            foreach ($parents as $index => $parent_page) {
                $is_current = ($index === count($parents) - 1);
                
                $breadcrumbs[] = array(
                    'title' => $parent_page->post_title,
                    'url' => get_permalink($parent_page->ID),
                    'active' => $is_current && !$options['current_page_link']
                );
            }
        } elseif (is_category() || is_tag() || is_tax()) {
            // Архивы таксономий - используем существующую функцию
            return cryptoschool_get_archive_breadcrumbs();
        } elseif (is_archive()) {
            // Другие архивы - используем существующую функцию
            return cryptoschool_get_archive_breadcrumbs();
        } elseif (is_search()) {
            // Страница поиска
            $breadcrumbs[] = array(
                'title' => $t['search'] . ': ' . get_search_query(),
                'url' => get_search_link(),
                'active' => true
            );
        } elseif (is_404()) {
            // Страница 404
            $breadcrumbs[] = array(
                'title' => $t['404'],
                'url' => '',
                'active' => true
            );
        } else {
            // Для всех остальных случаев пытаемся получить заголовок текущей страницы
            global $wp_query;
            $title = '';
            
            if (is_object($wp_query) && property_exists($wp_query, 'queried_object')) {
                $queried_object = $wp_query->queried_object;
                
                if (isset($queried_object->post_title)) {
                    $title = $queried_object->post_title;
                } elseif (isset($queried_object->name)) {
                    $title = $queried_object->name;
                }
            }
            
            if (empty($title)) {
                $title = get_the_title();
            }
            
            if (!empty($title)) {
                $breadcrumbs[] = array(
                    'title' => $title,
                    'url' => '',
                    'active' => true
                );
            }
        }
        
        // Используем существующую функцию рендеринга
        if (function_exists('cryptoschool_render_breadcrumbs')) {
            return cryptoschool_render_breadcrumbs($breadcrumbs);
        }
        
        return '';
    }
}

/**
 * Вспомогательная функция для получения хлебных крошек конкретной страницы по ID
 *
 * @param int $page_id ID страницы
 * @param array $options Опции для настройки хлебных крошек
 * @return string HTML код хлебных крошек
 */
if (!function_exists('cryptoschool_get_page_breadcrumbs_by_id')) {
    function cryptoschool_get_page_breadcrumbs_by_id($page_id, $options = array()) {
        $page = get_post($page_id);
        if (!$page) {
            return '';
        }
        
        // Временно устанавливаем глобальные переменные
        global $post, $wp_query;
        $original_post = $post;
        $original_wp_query = $wp_query;
        
        $post = $page;
        
        // Создаем временный WP_Query объект
        $wp_query = new WP_Query(array(
            'page_id' => $page_id,
            'posts_per_page' => 1
        ));
        
        $result = cryptoschool_get_page_breadcrumbs($options);
        
        // Восстанавливаем оригинальные переменные
        $post = $original_post;
        $wp_query = $original_wp_query;
        
        return $result;
    }
}

/**
 * Упрощенная функция для быстрого вывода хлебных крошек
 *
 * @param bool $echo Выводить ли результат сразу (true) или вернуть как строку (false)
 * @param array $options Опции для настройки хлебных крошек
 * @return string|void HTML код хлебных крошек или void если $echo = true
 */
/**
 * Функция генерации хлебных крошек для глоссария
 *
 * @param array $options Опции для настройки хлебных крошек
 * @return string HTML код хлебных крошек
 */
if (!function_exists('cryptoschool_get_glossary_breadcrumbs')) {
    function cryptoschool_get_glossary_breadcrumbs($options = array()) {
        // Опции по умолчанию
        $defaults = array(
            'show_home' => true,
            'separator' => '<div class="breadcrumbs__arrow"><span class="icon-nav-arrow-right"></span></div>',
            'current_page_link' => false,
        );
        
        $options = array_merge($defaults, $options);
        
        $breadcrumbs = array();
        
        // Получаем текущий язык
        $current_lang = defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : 'uk';
        
        // Переводы для разных языков
        $translations = array(
            'uk' => array(
                'home' => 'Головна',
                'glossary' => 'Глосарій'
            ),
            'ru' => array(
                'home' => 'Главная',
                'glossary' => 'Глоссарий'
            ),
            'en' => array(
                'home' => 'Home',
                'glossary' => 'Glossary'
            )
        );
        
        $t = isset($translations[$current_lang]) ? $translations[$current_lang] : $translations['uk'];
        
        // 1. Главная страница
        if ($options['show_home']) {
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
        }
        
        // 2. Ссылка на архив глоссария
        $glossary_archive_url = get_post_type_archive_link('glossary');
        
        // Определяем контекст и добавляем соответствующие крошки
        if (is_post_type_archive('glossary')) {
            // Архив глоссария - текущая страница
            $breadcrumbs[] = array(
                'title' => $t['glossary'],
                'url' => $glossary_archive_url,
                'active' => true
            );
        } elseif (is_tax('glossary-letter')) {
            // Страница таксономии (буквы) глоссария
            $term = get_queried_object();
            
            // Ссылка на глоссарий
            $breadcrumbs[] = array(
                'title' => $t['glossary'],
                'url' => $glossary_archive_url,
                'active' => false
            );
            
            // Текущая буква
            $breadcrumbs[] = array(
                'title' => strtoupper($term->name),
                'url' => get_term_link($term),
                'active' => true
            );
        } elseif (is_singular('glossary')) {
            // Отдельный пост глоссария
            $post = get_queried_object();
            
            // Ссылка на глоссарий
            $breadcrumbs[] = array(
                'title' => $t['glossary'],
                'url' => $glossary_archive_url,
                'active' => false
            );
            
            // Получаем букву из таксономии
            $terms = get_the_terms($post->ID, 'glossary-letter');
            if ($terms && !is_wp_error($terms)) {
                $term = array_shift($terms);
                $breadcrumbs[] = array(
                    'title' => strtoupper($term->name),
                    'url' => get_term_link($term),
                    'active' => false
                );
            }
            
            // Заголовок текущего поста
            $breadcrumbs[] = array(
                'title' => $post->post_title,
                'url' => get_permalink($post->ID),
                'active' => true
            );
        }
        
        // Рендеринг хлебных крошек
        return cryptoschool_render_breadcrumbs($breadcrumbs, $options);
    }
}

/**
 * Рендерит массив хлебных крошек в HTML
 *
 * @param array $breadcrumbs Массив хлебных крошек
 * @param array $options Опции рендеринга
 * @return string HTML код хлебных крошек
 */
if (!function_exists('cryptoschool_render_breadcrumbs')) {
    function cryptoschool_render_breadcrumbs($breadcrumbs, $options = array()) {
        if (empty($breadcrumbs)) {
            return '';
        }
        
        $defaults = array(
            'separator' => '<div class="breadcrumbs__arrow"><span class="icon-nav-arrow-right"></span></div>',
        );
        
        $options = array_merge($defaults, $options);
        
        $output = '';
        $total = count($breadcrumbs);
        
        foreach ($breadcrumbs as $index => $crumb) {
            $is_last = ($index === $total - 1);
            
            if ($crumb['active'] || empty($crumb['url'])) {
                $output .= '<span class="breadcrumbs__current">' . esc_html($crumb['title']) . '</span>';
            } else {
                $output .= '<a href="' . esc_url($crumb['url']) . '" class="breadcrumbs__link">' . esc_html($crumb['title']) . '</a>';
            }
            
            if (!$is_last) {
                $output .= $options['separator'];
            }
        }
        
        return $output;
    }
}

if (!function_exists('cryptoschool_breadcrumbs')) {
    function cryptoschool_breadcrumbs($echo = true, $options = array()) {
        $breadcrumbs = cryptoschool_get_page_breadcrumbs($options);
        
        if ($echo) {
            echo $breadcrumbs;
        } else {
            return $breadcrumbs;
        }
    }
}