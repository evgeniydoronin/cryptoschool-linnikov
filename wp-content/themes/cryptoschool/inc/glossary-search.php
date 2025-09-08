<?php
/**
 * Glossary Search AJAX Handler
 * 
 * Обработчик AJAX поиска по глоссарию
 * 
 * @package CryptoSchool
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Регистрация AJAX обработчиков поиска по глоссарию
 */
function cryptoschool_register_glossary_search_ajax() {
    // For logged in users
    add_action('wp_ajax_cryptoschool_glossary_search', 'cryptoschool_handle_glossary_search');
    // For not logged in users
    add_action('wp_ajax_nopriv_cryptoschool_glossary_search', 'cryptoschool_handle_glossary_search');
}
add_action('init', 'cryptoschool_register_glossary_search_ajax');

/**
 * Обработчик AJAX запроса поиска по глоссарию
 */
function cryptoschool_handle_glossary_search() {
    // Проверка nonce для безопасности
    if (!wp_verify_nonce($_POST['nonce'], 'glossary_search_nonce')) {
        wp_die('Security check failed');
    }
    
    // Получаем параметры поиска
    $search_query = sanitize_text_field($_POST['query']);
    $page_type = sanitize_text_field($_POST['page_type']); // 'archive' или 'taxonomy'
    $current_term_id = isset($_POST['current_term']) ? intval($_POST['current_term']) : 0;
    
    // Минимальная длина запроса
    if (strlen($search_query) < 3) {
        wp_die('Query too short');
    }
    
    // Базовые параметры для WP_Query
    $query_args = array(
        'post_type' => 'glossary',
        'posts_per_page' => -1,
        's' => $search_query,
        'orderby' => 'title',
        'order' => 'ASC'
    );
    
    // Если мы на странице таксономии, фильтруем по текущему терму
    if ($page_type === 'taxonomy' && $current_term_id > 0) {
        $query_args['tax_query'] = array(
            array(
                'taxonomy' => 'glossary-letter',
                'field' => 'term_id',
                'terms' => $current_term_id
            )
        );
    }
    
    // Выполняем поиск
    $search_results = new WP_Query($query_args);
    
    if ($search_results->have_posts()) {
        // Группируем результаты по буквам
        $results_by_letter = array();
        
        while ($search_results->have_posts()) {
            $search_results->the_post();
            
            // Получаем букву для текущего поста
            $post_terms = get_the_terms(get_the_ID(), 'glossary-letter');
            $letter = '#'; // По умолчанию
            
            if ($post_terms && !is_wp_error($post_terms)) {
                $term = array_shift($post_terms);
                $letter = strtoupper($term->name);
            }
            
            // Подготавливаем данные поста
            $post_data = array(
                'id' => get_the_ID(),
                'title' => get_the_title(),
                'permalink' => get_the_permalink(),
                'excerpt' => get_the_excerpt() ?: wp_trim_words(get_the_content(), 30, '...')
            );
            
            // Группируем по букве
            if (!isset($results_by_letter[$letter])) {
                $results_by_letter[$letter] = array();
            }
            $results_by_letter[$letter][] = $post_data;
        }
        
        wp_reset_postdata();
        
        // Генерируем HTML
        $html_output = '';
        
        foreach ($results_by_letter as $letter => $posts) {
            $html_output .= '<div class="categories-list__section">';
            $html_output .= '<div class="categories-list__section-letter h1">' . esc_html($letter) . '</div>';
            $html_output .= '<div class="categories-list__section-content">';
            
            foreach ($posts as $post) {
                $html_output .= '<div class="categories-list__section-row">';
                $html_output .= '<h4 class="categories-list__section-row-title h4">';
                $html_output .= '<a href="' . esc_url($post['permalink']) . '">';
                $html_output .= esc_html($post['title']);
                $html_output .= '</a>';
                $html_output .= '</h4>';
                $html_output .= '<div class="categories-list__section-row-text text">';
                $html_output .= esc_html($post['excerpt']);
                $html_output .= '</div>';
                $html_output .= '</div>';
            }
            
            $html_output .= '</div>';
            $html_output .= '</div>';
        }
        
        // Возвращаем успешный результат
        wp_send_json_success(array(
            'html' => $html_output,
            'found' => $search_results->found_posts
        ));
        
    } else {
        // Определяем текущий язык для переводов
        $current_lang = defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : 'uk';
        
        // Переводы
        $translations = array(
            'uk' => array(
                'no_results' => 'Результати не знайдено для запиту: "%s"'
            ),
            'ru' => array(
                'no_results' => 'Результаты не найдены для запроса: "%s"'
            )
        );
        
        $t = isset($translations[$current_lang]) ? $translations[$current_lang] : $translations['uk'];
        
        // Нет результатов
        $no_results_html = '<div class="categories-list__section">';
        $no_results_html .= '<div class="categories-list__section-content">';
        $no_results_html .= '<div class="categories-list__section-row">';
        $no_results_html .= '<div class="categories-list__section-row-text text">';
        $no_results_html .= sprintf($t['no_results'], esc_html($search_query));
        $no_results_html .= '</div>';
        $no_results_html .= '</div>';
        $no_results_html .= '</div>';
        $no_results_html .= '</div>';
        
        wp_send_json_success(array(
            'html' => $no_results_html,
            'found' => 0
        ));
    }
}

/**
 * Подключение скриптов поиска глоссария
 */
function cryptoschool_enqueue_glossary_search_scripts() {
    // Подключаем только на страницах глоссария
    if (is_post_type_archive('glossary') || is_tax('glossary-letter')) {
        wp_enqueue_script(
            'glossary-search',
            get_template_directory_uri() . '/assets/js/glossary-search.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        // Определяем текущую страницу и термин
        $page_type = 'archive';
        $current_term_id = 0;
        
        if (is_tax('glossary-letter')) {
            $page_type = 'taxonomy';
            $current_term = get_queried_object();
            if ($current_term) {
                $current_term_id = $current_term->term_id;
            }
        }
        
        // Определяем текущий язык для переводов
        $current_lang = defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : 'uk';
        
        // Переводы для JavaScript
        $translations = array(
            'uk' => array(
                'placeholder' => 'Пошук (мін. %d символи)...',
                'searching' => 'Пошук...',
                'error_occurred' => 'Виникла помилка при пошуку. Спробуйте ще раз.',
                'connection_error' => 'Помилка з\'єднання. Перевірте підключення до інтернету.'
            ),
            'ru' => array(
                'placeholder' => 'Поиск (мин. %d символа)...',
                'searching' => 'Поиск...',
                'error_occurred' => 'Произошла ошибка при поиске. Попробуйте еще раз.',
                'connection_error' => 'Ошибка соединения. Проверьте подключение к интернету.'
            )
        );
        
        $t = isset($translations[$current_lang]) ? $translations[$current_lang] : $translations['uk'];
        
        // Локализуем скрипт
        wp_localize_script('glossary-search', 'glossarySearch', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('glossary_search_nonce'),
            'pageType' => $page_type,
            'currentTerm' => $current_term_id,
            'minLength' => 3,
            'translations' => array(
                'placeholder' => sprintf($t['placeholder'], 3),
                'searching' => $t['searching'],
                'errorOccurred' => $t['error_occurred'],
                'connectionError' => $t['connection_error']
            )
        ));
    }
}
add_action('wp_enqueue_scripts', 'cryptoschool_enqueue_glossary_search_scripts');