# АКТУАЛЬНАЯ АРХИТЕКТУРА СИСТЕМЫ (Август 2025)

> ⚠️ **ВАЖНО:** Этот документ описывает ТЕКУЩЕЕ состояние системы после миграции на Custom Post Types

## 🏗️ Основная архитектура

### ✅ **КУРСЫ И УРОКИ**
- **Курсы:** WordPress Custom Post Type `cryptoschool_course`
- **Уроки:** WordPress Custom Post Type `cryptoschool_lesson`
- **Связи:** ACF поле `choose_lesson` в курсах содержит массив связанных уроков
- **Мультиязычность:** WPML с trid для унифицированного прогресса

### ✅ **ПОЛУЧЕНИЕ ДАННЫХ**
```php
// Курсы пользователя (из пакетов)
$packages = $wpdb->get_results("SELECT course_ids FROM cryptoschool_packages...");
$course_ids = json_decode($package->course_ids, true);
$courses = get_posts(['post_type' => 'cryptoschool_course', 'include' => $course_ids]);

// Уроки курса
$lessons = cryptoschool_get_course_lessons($course_id); // из wpml-helpers.php
```

### ✅ **ПРОГРЕСС ПОЛЬЗОВАТЕЛЕЙ**
- **Таблица:** `cryptoschool_user_lesson_progress`
- **lesson_id:** trid (translation ID) для WPML совместимости
- **Функции:** `cryptoschool_get_user_completed_lessons()` в wpml-helpers.php

---

## ⚠️ **ПРОБЛЕМЫ И НЕ РЕАЛИЗОВАНО**

### ❌ **СИСТЕМА БАЛЛОВ**
- **Проблема:** `CryptoSchool_Service_Points` создан, но НЕ подключен к WordPress хукам
- **Результат:** Баллы НЕ начисляются при завершении уроков
- **Нужно:** Добавить `do_action('cryptoschool_lesson_completed', $user_id, $lesson_id)` при завершении урока

### ❌ **ФИЛЬТРАЦИЯ СИСТЕМНЫХ СТРАНИЦ**
- **Проблема:** В `user_lesson_progress` попадают системные страницы (Navigation, Privacy Policy)
- **Результат:** Ложные "завершенные уроки"
- **Нужно:** Фильтровать по `post_type = 'cryptoschool_lesson'`

---

## 📁 **КЛЮЧЕВЫЕ ФАЙЛЫ**

### ✅ **АКТУАЛЬНЫЕ И РАБОТАЮЩИЕ:**
```
wp-content/themes/cryptoschool/page-courses.php - главная логика курсов
wp-content/themes/cryptoschool/inc/wpml-helpers.php - функции получения данных
wp-content/themes/cryptoschool/template-parts/account/account-last-tasks.php - UI последних задач
wp-content/plugins/cryptoschool/includes/models/class-cryptoschool-model-user-streak.php - модель серии
wp-content/plugins/cryptoschool/includes/services/class-cryptoschool-service-points.php - сервис баллов (НЕ подключен!)
```

### ❌ **УСТАРЕВШИЕ/НЕ СУЩЕСТВУЮЩИЕ:**
```
class-cryptoschool-model-lesson.php - НЕ НУЖЕН (Custom Post Types)
class-cryptoschool-model-course.php - НЕ НУЖЕН (Custom Post Types)  
class-cryptoschool-repository-lesson.php - НЕ НУЖЕН (Custom Post Types)
class-cryptoschool-repository-course.php - НЕ НУЖЕН (Custom Post Types)
```

---

## 🔧 **ЧТО НУЖНО ИСПРАВИТЬ**

### 1. **Подключить систему баллов:**
```php
// В page-lesson.php или в обработчике AJAX завершения урока добавить:
do_action('cryptoschool_lesson_completed', $user_id, $lesson_id);
```

### 2. **Зарегистрировать сервис баллов:**
```php
// В functions.php или в инициализации плагина:
$points_service = new CryptoSchool_Service_Points($loader);
```

### 3. **Исправить фильтрацию в wpml-helpers.php:**
```php
// В cryptoschool_get_user_completed_lessons() добавить проверку:
if (!$lesson_post || $lesson_post->post_type !== 'cryptoschool_lesson') {
    continue; // Пропускаем системные страницы
}
```

---

## 🧪 **ТЕСТИРОВАНИЕ**

### ✅ **РАБОТАЮЩИЕ ТЕСТЫ:**
- `test-real-user-points.php` - анализ текущего состояния пользователя
- `test-daily-progress-ui.php` - проверка UI daily progress
- `clean-user-progress.php` - очистка данных для тестов

### ❌ **УСТАРЕВШИЕ ТЕСТЫ:**
- `test-points-system.php` - основан на старой архитектуре, НЕ РАБОТАЕТ

---

## 📋 **РЕКОМЕНДАЦИИ ДЛЯ РАЗРАБОТЧИКОВ**

1. **ВСЕГДА** проверяйте актуальные файлы через `Glob` перед кодированием
2. **НЕ ПОЛАГАЙТЕСЬ** на документацию без проверки кода
3. **ИЗУЧАЙТЕ** `page-courses.php` и `wpml-helpers.php` для понимания архитектуры
4. **ИСПОЛЬЗУЙТЕ** `test-real-user-points.php` для диагностики проблем
5. **ПОМНИТЕ:** Курсы и уроки теперь Custom Post Types, не отдельные таблицы!

---

*Документ обновлен: 20.08.2025*  
*Статус архитектуры: Custom Post Types + WPML + ACF*  
*Основная проблема: Система баллов не подключена к хукам*