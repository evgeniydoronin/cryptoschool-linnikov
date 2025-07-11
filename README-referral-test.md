# 🎯 Комплексный тест реферальной системы "Крипто Школа"

## 📋 Описание

Автоматизированный тест для полной проверки функциональности реферальной системы проекта "Крипто Школа". Тест покрывает все основные сценарии использования и проверяет корректность работы всех компонентов системы.

## 🚀 Что тестируется

### ✅ **Основные функции:**
1. **Базовая реферальная система** - создание ссылок, расчет скидок и комиссий
2. **Система инфлюенсеров** - специальные условия для VIP-партнеров
3. **Двухуровневая система** - комиссии для рефоводов 1-го и 2-го уровня
4. **Множественные ссылки** - разные ссылки для разных каналов
5. **Запросы на вывод средств** - система выплат
6. **Статистика и аналитика** - отчеты и метрики

### 🔧 **Технические аспекты:**
- Создание и управление пользователями
- Работа с базой данных
- Валидация данных
- Расчет финансовых операций
- Проверка структуры БД

## 📁 Файлы

- `test-referral-system-full.php` - основной файл теста
- `README-referral-test.md` - данная инструкция

## 🎯 Использование

### 1. **Подготовка:**
```bash
# Убедитесь, что WordPress запущен
# Убедитесь, что плагин cryptoschool активен
# Убедитесь, что база данных настроена
```

### 2. **Запуск теста:**
```bash
# Поместите файл test-referral-system-full.php в корень WordPress
# Откройте в браузере: http://your-site.com/test-referral-system-full.php
```

### 3. **Результат:**
Тест автоматически:
- Создаст тестовых пользователей
- Создаст тестовые пакеты курсов
- Протестирует все сценарии
- Покажет детальный отчет
- Очистит тестовые данные

## 📊 Тестовые сценарии

### **Сценарий 1: Обычный рефовод**
- Создание ссылки: 20% скидка + 20% комиссия
- Покупка курса за $100
- **Ожидаемый результат:** $20 скидка, $20 комиссия, $80 финальная цена

### **Сценарий 2: VIP инфлюенсер**
- Создание ссылки: 0% скидка + 50% комиссия
- Покупка курса за $200
- **Ожидаемый результат:** $0 скидка, $100 комиссия, $200 финальная цена

### **Сценарий 3: Двухуровневая система**
- Покупка через цепочку рефералов за $500
- **Ожидаемый результат:** $75 скидка, $125 комиссия 2-го уровня, $25 комиссия 1-го уровня

### **Сценарий 4: Множественные ссылки**
- 3 ссылки для разных каналов (Telegram, Instagram, блог)
- Проверка статистики по каждой ссылке

## 🔍 Проверяемые данные

### **Пользователи:**
- Обычный рефовод
- VIP инфлюенсер
- Рефералы 1-го и 2-го уровня
- Покупатели

### **Пакеты:**
- Базовый курс ($100)
- Продвинутый курс ($200)
- VIP пакет ($500)

### **Реферальные ссылки:**
- Разные настройки скидок и комиссий
- Статистика кликов и конверсий
- Отслеживание заработка

## 📈 Отчетность

Тест предоставляет:
- **Пошаговый отчет** о каждом действии
- **Проверку расчетов** скидок и комиссий
- **Статистику созданных данных**
- **ID для дальнейшего анализа**
- **Проверку структуры БД**

## ⚠️ Важные замечания

### **Безопасность:**
- Тест создает только тестовые данные
- Автоматическая очистка после завершения
- Не влияет на реальных пользователей

### **Требования:**
- WordPress 6.0+
- PHP 8.0+
- MySQL 8.0+
- Активный плагин cryptoschool
- Настроенная база данных

### **Ограничения:**
- Запускать только в тестовой среде
- Не использовать на продакшене
- Проверить права доступа к БД

## 🎉 Ожидаемый результат

При успешном прохождении всех тестов вы увидите:

```
🎉 ВСЕ ТЕСТЫ ПРОЙДЕНЫ УСПЕШНО!
Реферальная система работает корректно и готова к использованию.
```

## 🐛 Устранение проблем

### **Частые ошибки:**

1. **"Таблица не найдена"**
   - Проверьте, что миграции БД выполнены
   - Убедитесь, что плагин активен

2. **"Класс не найден"**
   - Проверьте пути к файлам плагина
   - Убедитесь, что все файлы на месте

3. **"Ошибка создания пользователя"**
   - Проверьте права доступа к БД
   - Убедитесь, что роль cryptoschool_student существует

## 📞 Поддержка

При возникновении проблем:
1. Проверьте логи WordPress (`wp-content/debug.log`)
2. Убедитесь в корректности настроек БД
3. Проверьте активность плагина cryptoschool

---

**Дата создания:** 16.06.2025  
**Дата последнего тестирования:** 16.06.2025  
**Версия:** 1.0  
**Статус:** ✅ **Протестирован и работает на 100%**

## 🎉 **РЕЗУЛЬТАТЫ ТЕСТИРОВАНИЯ (16.06.2025)**

### ✅ **ВСЕ ТЕСТЫ ПРОЙДЕНЫ УСПЕШНО!**

**Результат последнего запуска:** 🎉 **ТЕСТИРОВАНИЕ ЗАВЕРШЕНО УСПЕШНО!**

#### **Протестированные компоненты:**
- ✅ Репозиторий реферальных ссылок - исправлен и работает
- ✅ Валидация модели - обновлена для инфлюенсеров (до 50%)
- ✅ Интеграция всех сервисов - работает корректно
- ✅ Базовая реферальная система - все расчеты верны
- ✅ Система инфлюенсеров - VIP ссылки с 50% комиссией
- ✅ Двухуровневая система - комиссии 1-го и 2-го уровня
- ✅ Множественные ссылки - разные настройки работают
- ✅ Запросы на вывод - создание и обработка
- ✅ Статистика - все показатели корректны

#### **Исправленные проблемы:**
- ✅ **Критическая ошибка в репозитории** - метод `create()` использовал неправильные данные
- ✅ **Валидация модели** - лимиты обновлены для поддержки инфлюенсеров
- ✅ **Интеграция компонентов** - все сервисы работают без ошибок

**Система готова к использованию в продакшене!**
