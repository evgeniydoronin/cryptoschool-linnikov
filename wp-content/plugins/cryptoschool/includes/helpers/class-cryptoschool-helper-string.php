<?php
/**
 * Хелпер для работы со строками
 *
 * @package CryptoSchool
 * @subpackage Helpers
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс хелпера для работы со строками
 */
class CryptoSchool_Helper_String {
    /**
     * Транслитерация кириллицы в латиницу
     *
     * @param string $string Строка для транслитерации
     * @return string
     */
    public static function transliterate($string) {
        $converter = array(
            'а' => 'a',   'б' => 'b',   'в' => 'v',   'г' => 'g',   'д' => 'd',   'е' => 'e',
            'ё' => 'e',   'ж' => 'zh',  'з' => 'z',   'и' => 'i',   'й' => 'y',   'к' => 'k',
            'л' => 'l',   'м' => 'm',   'н' => 'n',   'о' => 'o',   'п' => 'p',   'р' => 'r',
            'с' => 's',   'т' => 't',   'у' => 'u',   'ф' => 'f',   'х' => 'h',   'ц' => 'ts',
            'ч' => 'ch',  'ш' => 'sh',  'щ' => 'sch', 'ъ' => '',    'ы' => 'y',   'ь' => '',
            'э' => 'e',   'ю' => 'yu',  'я' => 'ya',
            
            'А' => 'A',   'Б' => 'B',   'В' => 'V',   'Г' => 'G',   'Д' => 'D',   'Е' => 'E',
            'Ё' => 'E',   'Ж' => 'Zh',  'З' => 'Z',   'И' => 'I',   'Й' => 'Y',   'К' => 'K',
            'Л' => 'L',   'М' => 'M',   'Н' => 'N',   'О' => 'O',   'П' => 'P',   'Р' => 'R',
            'С' => 'S',   'Т' => 'T',   'У' => 'U',   'Ф' => 'F',   'Х' => 'H',   'Ц' => 'Ts',
            'Ч' => 'Ch',  'Ш' => 'Sh',  'Щ' => 'Sch', 'Ъ' => '',    'Ы' => 'Y',   'Ь' => '',
            'Э' => 'E',   'Ю' => 'Yu',  'Я' => 'Ya',
            
            // Украинские символы
            'є' => 'ye',  'і' => 'i',   'ї' => 'yi',  'ґ' => 'g',
            'Є' => 'Ye',  'І' => 'I',   'Ї' => 'Yi',  'Ґ' => 'G'
        );
        
        // Отладочный вывод
        error_log('Helper String transliterate - Original string: ' . $string);
        
        // Транслитерация
        $string = strtr($string, $converter);
        
        // Отладочный вывод
        error_log('Helper String transliterate - Transliterated string: ' . $string);
        
        return $string;
    }

    /**
     * Генерация уникального слага
     *
     * @param string $title      Название для слага
     * @param string $table_name Имя таблицы для проверки уникальности
     * @param int    $id         ID записи (для обновления)
     * @return string
     */
    public static function generate_unique_slug($title, $table_name, $id = 0) {
        global $wpdb;
        
        // Отладочный вывод
        error_log('Helper String generate_unique_slug - Original title: ' . $title);
        
        // Транслитерация кириллицы в латиницу
        $transliterated_title = self::transliterate($title);
        
        // Отладочный вывод
        error_log('Helper String generate_unique_slug - Transliterated title: ' . $transliterated_title);
        
        // Создание слага
        $slug = sanitize_title($transliterated_title);
        $original_slug = $slug;
        
        // Отладочный вывод
        error_log('Helper String generate_unique_slug - Initial slug: ' . $slug);
        
        $i = 1;

        while (true) {
            $query = $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} WHERE slug = %s AND id != %d",
                $slug,
                $id
            );

            $count = (int) $wpdb->get_var($query);

            if ($count === 0) {
                break;
            }

            $slug = $original_slug . '-' . $i;
            $i++;
        }
        
        // Отладочный вывод
        error_log('Helper String generate_unique_slug - Final slug: ' . $slug);

        return $slug;
    }
}
