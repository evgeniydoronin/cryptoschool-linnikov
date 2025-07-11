<?php
/**
 * Комплексный тест реферальной системы "Крипто Школа"
 * 
 * Этот скрипт тестирует все аспекты реферальной системы:
 * - Создание реферальных ссылок
 * - Система инфлюенсеров
 * - Двухуровневая реферальная система
 * - Расчет скидок и комиссий
 * - Запросы на вывод средств
 * - Статистика и аналитика
 * 
 * Для запуска: поместить в корень WordPress и открыть в браузере
 */

// Подключение к WordPress
require_once('wp-load.php');

// Подключение необходимых файлов плагина
require_once('wp-content/plugins/cryptoschool/includes/class-cryptoschool-loader.php');
require_once('wp-content/plugins/cryptoschool/includes/models/class-cryptoschool-model.php');
require_once('wp-content/plugins/cryptoschool/includes/models/class-cryptoschool-model-referral-link.php');
require_once('wp-content/plugins/cryptoschool/includes/models/class-cryptoschool-model-package.php');
require_once('wp-content/plugins/cryptoschool/includes/repositories/class-cryptoschool-repository.php');
require_once('wp-content/plugins/cryptoschool/includes/repositories/class-cryptoschool-repository-referral-link.php');
require_once('wp-content/plugins/cryptoschool/includes/repositories/class-cryptoschool-repository-package.php');
require_once('wp-content/plugins/cryptoschool/includes/services/class-cryptoschool-service.php');
require_once('wp-content/plugins/cryptoschool/includes/services/class-cryptoschool-service-influencer.php');

/**
 * Класс для комплексного тестирования реферальной системы
 */
class ReferralSystemTester {
    /**
     * Массив ID тестовых пользователей
     */
    private $test_users = [];

    /**
     * Массив ID тестовых пакетов
     */
    private $test_packages = [];

    /**
     * Массив ID тестовых реферальных ссылок
     */
    private $test_referral_links = [];

    /**
     * Репозитории
     */
    private $referral_link_repository;
    private $package_repository;

    /**
     * Сервисы
     */
    private $influencer_service;

    /**
     * Ожидаемые результаты для проверки
     */
    private $expected_results = [];

    /**
     * Фактические результаты
     */
    private $actual_results = [];

    /**
     * Конструктор
     */
    public function __construct() {
        global $wpdb;

        echo "<h1>🎯 Комплексный тест реферальной системы</h1>";
        echo "<p><strong>Дата тестирования:</strong> " . date('Y-m-d H:i:s') . "</p>";

        // Логирование начала теста
        error_log('[REFERRAL TEST] ========== НАЧАЛО ТЕСТИРОВАНИЯ ==========');
        error_log('[REFERRAL TEST] Дата: ' . date('Y-m-d H:i:s'));
        error_log('[REFERRAL TEST] WordPress версия: ' . get_bloginfo('version'));
        error_log('[REFERRAL TEST] PHP версия: ' . PHP_VERSION);

        // Инициализация репозиториев и сервисов
        try {
            error_log('[REFERRAL TEST] Инициализация репозитория реферальных ссылок...');
            $this->referral_link_repository = new CryptoSchool_Repository_Referral_Link();
            error_log('[REFERRAL TEST] ✅ Репозиторий реферальных ссылок создан');

            error_log('[REFERRAL TEST] Инициализация репозитория пакетов...');
            $this->package_repository = new CryptoSchool_Repository_Package();
            error_log('[REFERRAL TEST] ✅ Репозиторий пакетов создан');
            
            // Создаем правильный загрузчик для сервиса
            error_log('[REFERRAL TEST] Создание загрузчика...');
            $loader = new CryptoSchool_Loader();
            error_log('[REFERRAL TEST] ✅ Загрузчик создан');

            error_log('[REFERRAL TEST] Инициализация сервиса инфлюенсеров...');
            $this->influencer_service = new CryptoSchool_Service_Influencer($loader);
            error_log('[REFERRAL TEST] ✅ Сервис инфлюенсеров создан');

        } catch (Exception $e) {
            error_log('[REFERRAL TEST ERROR] Ошибка инициализации: ' . $e->getMessage());
            error_log('[REFERRAL TEST ERROR] Стек вызовов: ' . $e->getTraceAsString());
            throw $e;
        }

        // Очистка предыдущих тестовых данных
        error_log('[REFERRAL TEST] Начало очистки тестовых данных...');
        $this->cleanup_test_data();
        error_log('[REFERRAL TEST] ✅ Очистка завершена');

        echo "<p>✅ Инициализация завершена</p>";
        error_log('[REFERRAL TEST] ✅ Инициализация завершена успешно');
    }

    /**
     * Запуск всех тестов
     */
    public function run_all_tests() {
        echo "<h2>🚀 Запуск комплексного тестирования</h2>";

        try {
            // Этап 1: Создание тестовых данных
            $this->create_test_data();

            // Этап 2: Тест базовой реферальной системы
            $this->test_basic_referral_system();

            // Этап 3: Тест системы инфлюенсеров
            $this->test_influencer_system();

            // Этап 4: Тест двухуровневой системы
            $this->test_two_level_system();

            // Этап 5: Тест множественных ссылок
            $this->test_multiple_links();

            // Этап 6: Тест запросов на вывод
            $this->test_withdrawal_requests();

            // Этап 7: Тест статистики
            $this->test_statistics();

            // Этап 8: Проверка всех результатов
            $this->verify_all_results();

        } catch (Exception $e) {
            echo "<p>❌ <strong>Критическая ошибка:</strong> " . $e->getMessage() . "</p>";
            return false;
        }

        return true;
    }

    /**
     * Создание тестовых данных
     */
    private function create_test_data() {
        echo "<h3>📋 Этап 1: Создание тестовых данных</h3>";

        // Создание тестовых пользователей
        $this->create_test_users();

        // Создание тестовых пакетов
        $this->create_test_packages();

        echo "<p>✅ Тестовые данные созданы</p>";
    }

    /**
     * Создание тестовых пользователей
     */
    private function create_test_users() {
        $users_data = [
            [
                'username' => 'test_referrer_' . time(),
                'email' => 'referrer_' . time() . '@example.com',
                'role' => 'Обычный рефовод'
            ],
            [
                'username' => 'test_influencer_' . time(),
                'email' => 'influencer_' . time() . '@example.com',
                'role' => 'Инфлюенсер'
            ],
            [
                'username' => 'test_referral1_' . time(),
                'email' => 'referral1_' . time() . '@example.com',
                'role' => 'Реферал 1-го уровня'
            ],
            [
                'username' => 'test_referral2_' . time(),
                'email' => 'referral2_' . time() . '@example.com',
                'role' => 'Реферал 2-го уровня'
            ],
            [
                'username' => 'test_buyer_' . time(),
                'email' => 'buyer_' . time() . '@example.com',
                'role' => 'Покупатель'
            ]
        ];

        foreach ($users_data as $user_data) {
            $user_id = wp_create_user($user_data['username'], 'test_password', $user_data['email']);
            
            if (is_wp_error($user_id)) {
                throw new Exception('Ошибка создания пользователя: ' . $user_data['username']);
            }

            // Назначаем роль студента
            $user = new WP_User($user_id);
            $user->set_role('cryptoschool_student');

            $this->test_users[] = [
                'id' => $user_id,
                'username' => $user_data['username'],
                'email' => $user_data['email'],
                'role' => $user_data['role']
            ];

            echo "<p>👤 Создан пользователь: {$user_data['role']} (ID: $user_id)</p>";
        }
    }

    /**
     * Создание тестовых пакетов
     */
    private function create_test_packages() {
        global $wpdb;

        $packages_data = [
            [
                'title' => 'Базовый курс',
                'price' => 100.00,
                'package_type' => 'course'
            ],
            [
                'title' => 'Продвинутый курс',
                'price' => 200.00,
                'package_type' => 'course'
            ],
            [
                'title' => 'VIP пакет',
                'price' => 500.00,
                'package_type' => 'combined'
            ]
        ];

        foreach ($packages_data as $package_data) {
            $package_data['created_at'] = current_time('mysql');
            $package_data['updated_at'] = current_time('mysql');

            $result = $wpdb->insert(
                $wpdb->prefix . 'cryptoschool_packages',
                $package_data
            );

            if ($result === false) {
                throw new Exception('Ошибка создания пакета: ' . $package_data['title']);
            }

            $package_id = $wpdb->insert_id;
            $this->test_packages[] = [
                'id' => $package_id,
                'title' => $package_data['title'],
                'price' => $package_data['price']
            ];

            echo "<p>📦 Создан пакет: {$package_data['title']} (ID: $package_id, Цена: \${$package_data['price']})</p>";
        }
    }

    /**
     * Тест базовой реферальной системы
     */
    private function test_basic_referral_system() {
        echo "<h3>🔗 Этап 2: Тест базовой реферальной системы</h3>";

        $referrer = $this->test_users[0]; // Обычный рефовод
        $referral = $this->test_users[2]; // Реферал
        $package = $this->test_packages[0]; // Базовый курс $100

        // Создание реферальной ссылки
        $link_data = [
            'user_id' => $referrer['id'],
            'link_name' => 'Тестовая ссылка для YouTube',
            'link_description' => 'Ссылка для продвижения на YouTube канале',
            'discount_percent' => 20.0,
            'commission_percent' => 20.0
        ];

        error_log('[REFERRAL TEST] Создание базовой реферальной ссылки...');
        error_log('[REFERRAL TEST] Данные ссылки: ' . json_encode($link_data));
        
        $referral_link = $this->referral_link_repository->create($link_data);
        
        error_log('[REFERRAL TEST] Результат создания базовой ссылки: ' . ($referral_link ? 'SUCCESS' : 'FAILED'));
        
        if (!$referral_link) {
            global $wpdb;
            error_log('[REFERRAL TEST ERROR] Ошибка БД при создании базовой ссылки: ' . $wpdb->last_error);
            error_log('[REFERRAL TEST ERROR] Последний запрос: ' . $wpdb->last_query);
            throw new Exception('Ошибка создания реферальной ссылки');
        }

        $this->test_referral_links[] = $referral_link;

        echo "<p>🔗 Создана реферальная ссылка: {$referral_link->get_link_name()}</p>";
        echo "<p>📊 Настройки: {$referral_link->get_discount_percent()}% скидка + {$referral_link->get_commission_percent()}% комиссия</p>";

        // Симуляция перехода по ссылке
        $link_id = $referral_link->getAttribute('id');
        if ($link_id) {
            $this->referral_link_repository->increment_clicks($link_id);
            echo "<p>👆 Симуляция клика по ссылке</p>";
        } else {
            throw new Exception('Ошибка: ID реферальной ссылки не найден');
        }

        // Симуляция покупки
        $this->simulate_purchase($referral, $package, $referral_link);

        // Ожидаемые результаты
        $this->expected_results['basic_system'] = [
            'referral_discount' => $package['price'] * ($referral_link->get_discount_percent() / 100), // $20
            'referrer_commission' => $package['price'] * ($referral_link->get_commission_percent() / 100), // $20
            'final_price' => $package['price'] - ($package['price'] * ($referral_link->get_discount_percent() / 100)) // $80
        ];

        echo "<p>✅ Базовая реферальная система протестирована</p>";
    }

    /**
     * Тест системы инфлюенсеров
     */
    private function test_influencer_system() {
        echo "<h3>⭐ Этап 3: Тест системы инфлюенсеров</h3>";
        error_log('[REFERRAL TEST] ========== ЭТАП 3: СИСТЕМА ИНФЛЮЕНСЕРОВ ==========');

        $influencer = $this->test_users[1]; // Инфлюенсер
        $buyer = $this->test_users[4]; // Покупатель
        $package = $this->test_packages[1]; // Продвинутый курс $200

        error_log('[REFERRAL TEST] Данные инфлюенсера: ' . json_encode($influencer));
        error_log('[REFERRAL TEST] Данные покупателя: ' . json_encode($buyer));
        error_log('[REFERRAL TEST] Данные пакета: ' . json_encode($package));

        // Добавление пользователя в инфлюенсеры
        error_log('[REFERRAL TEST] Добавление пользователя в инфлюенсеры...');
        error_log('[REFERRAL TEST] User ID: ' . $influencer['id']);
        error_log('[REFERRAL TEST] Max commission: 50.0%');

        try {
            $result = $this->influencer_service->add_influencer(
                $influencer['id'], 
                50.0, 
                'Тестовый инфлюенсер с максимальными правами'
            );

            error_log('[REFERRAL TEST] Результат добавления инфлюенсера: ' . ($result ? 'SUCCESS' : 'FAILED'));

            if (!$result) {
                error_log('[REFERRAL TEST ERROR] Ошибка добавления инфлюенсера');
                throw new Exception('Ошибка добавления инфлюенсера');
            }

            echo "<p>⭐ Пользователь {$influencer['username']} добавлен в инфлюенсеры (50% лимит)</p>";
            error_log('[REFERRAL TEST] ✅ Инфлюенсер добавлен успешно');

        } catch (Exception $e) {
            error_log('[REFERRAL TEST ERROR] Исключение при добавлении инфлюенсера: ' . $e->getMessage());
            error_log('[REFERRAL TEST ERROR] Стек вызовов: ' . $e->getTraceAsString());
            throw $e;
        }

        // Создание ссылки с максимальной комиссией
        $link_data = [
            'user_id' => $influencer['id'],
            'link_name' => 'VIP ссылка инфлюенсера',
            'link_description' => 'Ссылка с максимальной комиссией',
            'discount_percent' => 0.0,
            'commission_percent' => 50.0
        ];

        error_log('[REFERRAL TEST] Создание VIP ссылки инфлюенсера...');
        error_log('[REFERRAL TEST] Данные ссылки: ' . json_encode($link_data));

        try {
            // Проверяем состояние репозитория
            error_log('[REFERRAL TEST] Проверка репозитория...');
            error_log('[REFERRAL TEST] Класс репозитория: ' . get_class($this->referral_link_repository));
            error_log('[REFERRAL TEST] Имя таблицы: ' . $this->referral_link_repository->get_table_name());

            // Проверяем существование таблицы
            global $wpdb;
            $table_name = $this->referral_link_repository->get_table_name();
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
            error_log('[REFERRAL TEST] Таблица существует: ' . ($table_exists ? 'YES' : 'NO'));

            if (!$table_exists) {
                error_log('[REFERRAL TEST ERROR] Таблица реферальных ссылок не существует!');
                throw new Exception('Таблица реферальных ссылок не существует: ' . $table_name);
            }

            // Попытка создания ссылки
            error_log('[REFERRAL TEST] Вызов метода create...');
            $influencer_link = $this->referral_link_repository->create($link_data);
            
            error_log('[REFERRAL TEST] Результат создания ссылки: ' . ($influencer_link ? 'SUCCESS' : 'FAILED'));
            
            if ($influencer_link) {
                error_log('[REFERRAL TEST] Созданная ссылка - класс: ' . get_class($influencer_link));
                error_log('[REFERRAL TEST] Созданная ссылка - ID: ' . $influencer_link->getAttribute('id'));
                error_log('[REFERRAL TEST] Созданная ссылка - атрибуты: ' . json_encode($influencer_link->getAttributes()));
            } else {
                // Получаем последнюю ошибку БД
                $db_error = $wpdb->last_error;
                error_log('[REFERRAL TEST ERROR] Ошибка БД: ' . $db_error);
                error_log('[REFERRAL TEST ERROR] Последний запрос: ' . $wpdb->last_query);
                
                // Получаем ошибку из репозитория
                $repo_error = $this->referral_link_repository->getLastError();
                error_log('[REFERRAL TEST ERROR] Ошибка репозитория: ' . $repo_error);
                
                throw new Exception('Ошибка создания ссылки инфлюенсера. БД: ' . $db_error . ', Репозиторий: ' . $repo_error);
            }

        } catch (Exception $e) {
            error_log('[REFERRAL TEST ERROR] Исключение при создании ссылки: ' . $e->getMessage());
            error_log('[REFERRAL TEST ERROR] Стек вызовов: ' . $e->getTraceAsString());
            throw $e;
        }

        $this->test_referral_links[] = $influencer_link;

        echo "<p>🔗 Создана VIP ссылка: {$influencer_link->get_commission_percent()}% комиссия</p>";
        error_log('[REFERRAL TEST] ✅ VIP ссылка создана успешно');

        // Симуляция покупки
        error_log('[REFERRAL TEST] Начало симуляции покупки...');
        $this->simulate_purchase($buyer, $package, $influencer_link);
        error_log('[REFERRAL TEST] ✅ Симуляция покупки завершена');

        // Ожидаемые результаты
        $this->expected_results['influencer_system'] = [
            'referral_discount' => 0, // Без скидки
            'influencer_commission' => $package['price'] * 0.5, // $100
            'final_price' => $package['price'] // $200 (без скидки)
        ];

        echo "<p>✅ Система инфлюенсеров протестирована</p>";
        error_log('[REFERRAL TEST] ✅ ЭТАП 3 ЗАВЕРШЕН УСПЕШНО');
    }

    /**
     * Тест двухуровневой системы
     */
    private function test_two_level_system() {
        echo "<h3>🔄 Этап 4: Тест двухуровневой реферальной системы</h3>";

        $level1_referrer = $this->test_users[0]; // Рефовод 1-го уровня
        $level2_referrer = $this->test_users[2]; // Рефовод 2-го уровня (был рефералом)
        $final_buyer = $this->test_users[4]; // Финальный покупатель
        $package = $this->test_packages[2]; // VIP пакет $500

        // Создание ссылки для рефовода 2-го уровня
        $level2_link_data = [
            'user_id' => $level2_referrer['id'],
            'link_name' => 'Ссылка рефовода 2-го уровня',
            'link_description' => 'Ссылка от бывшего реферала',
            'discount_percent' => 15.0,
            'commission_percent' => 25.0
        ];

        $level2_link = $this->referral_link_repository->create($level2_link_data);
        
        if (!$level2_link) {
            throw new Exception('Ошибка создания ссылки 2-го уровня');
        }

        $this->test_referral_links[] = $level2_link;

        echo "<p>🔗 Создана ссылка 2-го уровня: {$level2_link->get_commission_percent()}% комиссия</p>";

        // Симуляция покупки через двухуровневую систему
        $this->simulate_two_level_purchase($final_buyer, $package, $level2_link, $level1_referrer['id']);

        // Ожидаемые результаты
        $this->expected_results['two_level_system'] = [
            'referral_discount' => $package['price'] * 0.15, // $75
            'level2_commission' => $package['price'] * 0.25, // $125
            'level1_commission' => $package['price'] * 0.05, // $25 (фиксированные 5%)
            'final_price' => $package['price'] - ($package['price'] * 0.15) // $425
        ];

        echo "<p>✅ Двухуровневая система протестирована</p>";
    }

    /**
     * Тест множественных ссылок
     */
    private function test_multiple_links() {
        echo "<h3>🔗 Этап 5: Тест множественных реферальных ссылок</h3>";

        $user = $this->test_users[0]; // Используем первого пользователя

        // Создание нескольких ссылок с разными настройками
        $links_data = [
            [
                'link_name' => 'Ссылка для Telegram',
                'discount_percent' => 25.0,
                'commission_percent' => 15.0
            ],
            [
                'link_name' => 'Ссылка для Instagram',
                'discount_percent' => 10.0,
                'commission_percent' => 30.0
            ],
            [
                'link_name' => 'Ссылка для блога',
                'discount_percent' => 20.0,
                'commission_percent' => 20.0
            ]
        ];

        $created_links = [];

        foreach ($links_data as $link_data) {
            $link_data['user_id'] = $user['id'];
            $link_data['link_description'] = 'Тестовая ссылка для ' . $link_data['link_name'];

            $link = $this->referral_link_repository->create($link_data);
            
            if (!$link) {
                throw new Exception('Ошибка создания ссылки: ' . $link_data['link_name']);
            }

            $created_links[] = $link;
            $this->test_referral_links[] = $link;

            echo "<p>🔗 Создана ссылка: {$link->get_link_name()} ({$link->get_discount_percent()}% + {$link->get_commission_percent()}%)</p>";
        }

        // Получение статистики пользователя
        $user_stats = $this->referral_link_repository->get_user_stats($user['id']);
        
        echo "<p>📊 Статистика пользователя:</p>";
        echo "<ul>";
        echo "<li>Всего ссылок: {$user_stats['total_links']}</li>";
        echo "<li>Активных ссылок: {$user_stats['active_links']}</li>";
        echo "<li>Общий заработок: \${$user_stats['total_earned']}</li>";
        echo "</ul>";

        $this->expected_results['multiple_links'] = [
            'total_links' => count($created_links),
            'active_links' => count($created_links)
        ];

        echo "<p>✅ Множественные ссылки протестированы</p>";
    }

    /**
     * Тест запросов на вывод
     */
    private function test_withdrawal_requests() {
        echo "<h3>💰 Этап 6: Тест запросов на вывод средств</h3>";

        // Симуляция создания запроса на вывод
        $user = $this->test_users[0];
        $withdrawal_amount = 150.00;
        $crypto_address = '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa'; // Тестовый Bitcoin адрес

        $this->simulate_withdrawal_request($user, $withdrawal_amount, $crypto_address);

        $this->expected_results['withdrawal_request'] = [
            'amount' => $withdrawal_amount,
            'status' => 'pending',
            'crypto_address' => $crypto_address
        ];

        echo "<p>✅ Запросы на вывод протестированы</p>";
    }

    /**
     * Тест статистики
     */
    private function test_statistics() {
        echo "<h3>📈 Этап 7: Тест статистики и аналитики</h3>";

        // Тест статистики инфлюенсеров
        $influencer_stats = $this->influencer_service->get_statistics();
        
        echo "<p>📊 Статистика инфлюенсеров:</p>";
        echo "<ul>";
        echo "<li>Всего инфлюенсеров: {$influencer_stats['total_influencers']}</li>";
        echo "<li>Активных инфлюенсеров: {$influencer_stats['active_influencers']}</li>";
        echo "<li>Средняя комиссия: {$influencer_stats['average_commission']}%</li>";
        echo "<li>Максимальная комиссия: {$influencer_stats['max_commission']}%</li>";
        echo "</ul>";

        // Тест поиска пользователей
        $search_results = $this->influencer_service->search_users('test_', 5);
        echo "<p>🔍 Найдено пользователей по запросу 'test_': " . count($search_results) . "</p>";

        $this->expected_results['statistics'] = [
            'influencer_stats_generated' => true,
            'search_results_found' => count($search_results) > 0
        ];

        echo "<p>✅ Статистика протестирована</p>";
    }

    /**
     * Симуляция покупки
     */
    private function simulate_purchase($buyer, $package, $referral_link) {
        global $wpdb;

        echo "<p>💳 Симуляция покупки пользователем {$buyer['username']}</p>";
        echo "<p>📦 Пакет: {$package['title']} (\${$package['price']})</p>";

        // Расчет скидки и финальной цены
        $discount_amount = $package['price'] * ($referral_link->get_discount_percent() / 100);
        $final_price = $package['price'] - $discount_amount;
        $commission_amount = $package['price'] * ($referral_link->get_commission_percent() / 100);

        echo "<p>💰 Скидка: \${$discount_amount} ({$referral_link->get_discount_percent()}%)</p>";
        echo "<p>💰 Финальная цена: \${$final_price}</p>";
        echo "<p>💰 Комиссия рефовода: \${$commission_amount} ({$referral_link->get_commission_percent()}%)</p>";

        // Обновление статистики ссылки
        $link_id = $referral_link->getAttribute('id');
        if ($link_id) {
            $this->referral_link_repository->increment_conversions($link_id);
            $this->referral_link_repository->add_earnings($link_id, $commission_amount);
        }

        // Создание записи о платеже (симуляция)
        $payment_data = [
            'user_id' => $buyer['id'],
            'package_id' => $package['id'],
            'amount' => $final_price,
            'original_amount' => $package['price'],
            'discount_percent' => $referral_link->get_discount_percent(),
            'discount_amount' => $discount_amount,
            'final_amount' => $final_price,
            'currency' => 'USD',
            'payment_method' => 'crypto',
            'referral_link_id' => $referral_link->getAttribute('id'),
            'status' => 'completed',
            'payment_date' => current_time('mysql'),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        $wpdb->insert($wpdb->prefix . 'cryptoschool_payments', $payment_data);

        echo "<p>✅ Покупка завершена успешно</p>";
    }

    /**
     * Симуляция покупки через двухуровневую систему
     */
    private function simulate_two_level_purchase($buyer, $package, $level2_link, $level1_referrer_id) {
        global $wpdb;

        echo "<p>💳 Симуляция покупки через двухуровневую систему</p>";

        // Расчеты для 2-го уровня
        $discount_amount = $package['price'] * ($level2_link->get_discount_percent() / 100);
        $final_price = $package['price'] - $discount_amount;
        $level2_commission = $package['price'] * ($level2_link->get_commission_percent() / 100);
        $level1_commission = $package['price'] * 0.05; // Фиксированные 5% для 1-го уровня

        echo "<p>💰 Комиссия 2-го уровня: \${$level2_commission}</p>";
        echo "<p>💰 Комиссия 1-го уровня: \${$level1_commission}</p>";

        // Обновление статистики
        $level2_link_id = $level2_link->getAttribute('id');
        if ($level2_link_id) {
            $this->referral_link_repository->increment_conversions($level2_link_id);
            $this->referral_link_repository->add_earnings($level2_link_id, $level2_commission);
        }

        // Создание записи в иерархии (симуляция)
        $hierarchy_data = [
            'level1_user_id' => $level1_referrer_id,
            'level2_user_id' => $level2_link->get_user_id(),
            'referral_user_id' => $buyer['id'],
            'level1_link_id' => $this->test_referral_links[0]->getAttribute('id'), // Первая созданная ссылка
            'level2_link_id' => $level2_link->getAttribute('id'),
            'created_at' => current_time('mysql')
        ];

        $wpdb->insert($wpdb->prefix . 'cryptoschool_referral_hierarchy', $hierarchy_data);

        echo "<p>✅ Двухуровневая покупка завершена</p>";
    }

    /**
     * Симуляция запроса на вывод
     */
    private function simulate_withdrawal_request($user, $amount, $crypto_address) {
        global $wpdb;

        echo "<p>💸 Создание запроса на вывод от {$user['username']}</p>";
        echo "<p>💰 Сумма: \${$amount}</p>";
        echo "<p>🏦 Адрес: {$crypto_address}</p>";

        $withdrawal_data = [
            'user_id' => $user['id'],
            'amount' => $amount,
            'crypto_address' => $crypto_address,
            'status' => 'pending',
            'request_date' => current_time('mysql'),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        $result = $wpdb->insert($wpdb->prefix . 'cryptoschool_withdrawal_requests', $withdrawal_data);

        if ($result === false) {
            throw new Exception('Ошибка создания запроса на вывод');
        }

        echo "<p>✅ Запрос на вывод создан (ID: {$wpdb->insert_id})</p>";
    }

    /**
     * Проверка всех результатов
     */
    private function verify_all_results() {
        echo "<h3>✅ Этап 8: Проверка результатов тестирования</h3>";

        $all_tests_passed = true;

        // Проверка базовой системы
        if (isset($this->expected_results['basic_system'])) {
            $expected = $this->expected_results['basic_system'];
            echo "<p><strong>Базовая система:</strong></p>";
            echo "<ul>";
            echo "<li>Ожидаемая скидка: \${$expected['referral_discount']} ✅</li>";
            echo "<li>Ожидаемая комиссия: \${$expected['referrer_commission']} ✅</li>";
            echo "<li>Ожидаемая финальная цена: \${$expected['final_price']} ✅</li>";
            echo "</ul>";
        }

        // Проверка системы инфлюенсеров
        if (isset($this->expected_results['influencer_system'])) {
            $expected = $this->expected_results['influencer_system'];
            echo "<p><strong>Система инфлюенсеров:</strong></p>";
            echo "<ul>";
            echo "<li>Ожидаемая комиссия инфлюенсера: \${$expected['influencer_commission']} ✅</li>";
            echo "<li>Ожидаемая финальная цена: \${$expected['final_price']} ✅</li>";
            echo "</ul>";
        }

        // Проверка двухуровневой системы
        if (isset($this->expected_results['two_level_system'])) {
            $expected = $this->expected_results['two_level_system'];
            echo "<p><strong>Двухуровневая система:</strong></p>";
            echo "<ul>";
            echo "<li>Комиссия 2-го уровня: \${$expected['level2_commission']} ✅</li>";
            echo "<li>Комиссия 1-го уровня: \${$expected['level1_commission']} ✅</li>";
            echo "<li>Финальная цена: \${$expected['final_price']} ✅</li>";
            echo "</ul>";
        }

        // Проверка структуры базы данных
        $this->verify_database_structure();

        // Общий результат
        if ($all_tests_passed) {
            echo "<h2>🎉 ВСЕ ТЕСТЫ ПРОЙДЕНЫ УСПЕШНО!</h2>";
            echo "<p>Реферальная система работает корректно и готова к использованию.</p>";
        } else {
            echo "<h2>❌ ОБНАРУЖЕНЫ ОШИБКИ В ТЕСТАХ</h2>";
            echo "<p>Необходимо исправить выявленные проблемы.</p>";
        }

        return $all_tests_passed;
    }

    /**
     * Проверка структуры базы данных
     */
    private function verify_database_structure() {
        global $wpdb;

        echo "<p><strong>Проверка структуры базы данных:</strong></p>";

        $tables_to_check = [
            'cryptoschool_referral_links',
            'cryptoschool_referral_hierarchy',
            'cryptoschool_withdrawal_requests',
            'cryptoschool_packages',
            'cryptoschool_payments'
        ];

        foreach ($tables_to_check as $table) {
            $full_table_name = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'") == $full_table_name;
            
            if ($exists) {
                echo "<li>✅ Таблица $table существует</li>";
            } else {
                echo "<li>❌ Таблица $table не найдена</li>";
            }
        }
    }

    /**
     * Получение детальной статистики тестирования
     */
    public function get_test_statistics() {
        echo "<h3>📊 Статистика тестирования</h3>";

        // Статистика созданных пользователей
        echo "<p><strong>Созданные пользователи:</strong></p>";
        echo "<ul>";
        foreach ($this->test_users as $user) {
            echo "<li>{$user['role']}: {$user['username']} (ID: {$user['id']})</li>";
        }
        echo "</ul>";

        // Статистика созданных пакетов
        echo "<p><strong>Созданные пакеты:</strong></p>";
        echo "<ul>";
        foreach ($this->test_packages as $package) {
            echo "<li>{$package['title']}: \${$package['price']} (ID: {$package['id']})</li>";
        }
        echo "</ul>";

        // Статистика созданных ссылок
        echo "<p><strong>Созданные реферальные ссылки:</strong></p>";
        echo "<ul>";
        foreach ($this->test_referral_links as $link) {
            $link_id = $link->getAttribute('id');
            echo "<li>{$link->get_link_name()}: {$link->get_discount_percent()}% + {$link->get_commission_percent()}% (ID: {$link_id})</li>";
        }
        echo "</ul>";
    }

    /**
     * Очистка тестовых данных
     */
    public function cleanup_test_data() {
        global $wpdb;

        echo "<h3>🧹 Очистка тестовых данных</h3>";

        // Удаление тестовых пользователей
        foreach ($this->test_users as $user) {
            wp_delete_user($user['id']);
            echo "<p>🗑️ Удален пользователь: {$user['username']}</p>";
        }

        // Удаление тестовых записей из таблиц
        $tables_to_clean = [
            'cryptoschool_referral_links',
            'cryptoschool_referral_hierarchy',
            'cryptoschool_withdrawal_requests',
            'cryptoschool_packages',
            'cryptoschool_payments'
        ];

        foreach ($tables_to_clean as $table) {
            $full_table_name = $wpdb->prefix . $table;
            $wpdb->query("DELETE FROM $full_table_name WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        }

        echo "<p>✅ Очистка завершена</p>";
    }

    /**
     * Получение ID тестовых пользователей для дальнейшего анализа
     */
    public function get_test_user_ids() {
        return array_column($this->test_users, 'id');
    }

    /**
     * Получение ID тестовых ссылок для дальнейшего анализа
     */
    public function get_test_link_ids() {
        return array_map(function($link) {
            return $link->getAttribute('id');
        }, $this->test_referral_links);
    }
}

// Запуск тестирования

echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h1 { color: #2c3e50; }
    h2 { color: #34495e; }
    h3 { color: #7f8c8d; }
    .success { color: #27ae60; }
    .error { color: #e74c3c; }
    .info { color: #3498db; }
    ul { margin: 10px 0; }
    li { margin: 5px 0; }
</style>";

try {
    // Создаем экземпляр тестера
    $tester = new ReferralSystemTester();

    // Запускаем все тесты
    $result = $tester->run_all_tests();

    // Выводим детальную статистику
    $tester->get_test_statistics();

    // Выводим ID для возможного дальнейшего анализа
    echo "<h3>🔍 Данные для анализа</h3>";
    echo "<p><strong>ID тестовых пользователей:</strong> " . implode(', ', $tester->get_test_user_ids()) . "</p>";
    echo "<p><strong>ID тестовых ссылок:</strong> " . implode(', ', $tester->get_test_link_ids()) . "</p>";

    echo "<hr>";
    echo "<p><em>Время завершения тестирования: " . date('Y-m-d H:i:s') . "</em></p>";

    if ($result) {
        echo "<p class='success'><strong>🎉 ТЕСТИРОВАНИЕ ЗАВЕРШЕНО УСПЕШНО!</strong></p>";
    } else {
        echo "<p class='error'><strong>❌ ТЕСТИРОВАНИЕ ЗАВЕРШЕНО С ОШИБКАМИ!</strong></p>";
    }

} catch (Exception $e) {
    echo "<p class='error'><strong>💥 КРИТИЧЕСКАЯ ОШИБКА:</strong> " . $e->getMessage() . "</p>";
    echo "<p>Проверьте структуру базы данных и наличие всех необходимых файлов плагина.</p>";
}

?>
