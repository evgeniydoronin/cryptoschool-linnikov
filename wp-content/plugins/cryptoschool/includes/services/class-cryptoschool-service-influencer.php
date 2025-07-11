<?php
/**
 * Сервис для работы с инфлюенсерами
 *
 * @package CryptoSchool
 * @subpackage Services
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс сервиса для работы с инфлюенсерами
 */
class CryptoSchool_Service_Influencer extends CryptoSchool_Service {
    /**
     * Репозиторий настроек инфлюенсеров
     *
     * @var CryptoSchool_Repository_Influencer_Settings
     */
    protected $repository;

    /**
     * Конструктор
     *
     * @param CryptoSchool_Loader $loader Экземпляр загрузчика
     */
    public function __construct(CryptoSchool_Loader $loader) {
        parent::__construct($loader);
        // Пока используем заглушку, позже подключим реальный репозиторий
        // $this->repository = new CryptoSchool_Repository_Influencer_Settings();
    }

    /**
     * Регистрация хуков и фильтров
     *
     * @return void
     */
    protected function register_hooks() {
        // Пока не регистрируем хуки, так как это административный сервис
    }

    /**
     * Получение всех инфлюенсеров
     *
     * @param array $args Аргументы для фильтрации
     * @return array
     */
    public function get_all($args = []) {
        // Пока возвращаем демо-данные
        return $this->get_demo_influencers();
    }

    /**
     * Получение инфлюенсера по ID пользователя
     *
     * @param int $user_id ID пользователя
     * @return object|null
     */
    public function get_by_user_id($user_id) {
        $influencers = $this->get_all();
        
        foreach ($influencers as $influencer) {
            if ($influencer->id == $user_id) {
                return $influencer;
            }
        }
        
        return null;
    }

    /**
     * Добавление пользователя в инфлюенсеры
     *
     * @param int   $user_id                ID пользователя
     * @param float $max_commission_percent Максимальный процент комиссии
     * @param string $admin_notes           Административные заметки
     * @return bool
     */
    public function add_influencer($user_id, $max_commission_percent = 20, $admin_notes = '') {
        // Проверка существования пользователя
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            $this->log_error('Попытка добавить несуществующего пользователя в инфлюенсеры', ['user_id' => $user_id]);
            return false;
        }

        // Валидация процента комиссии
        if ($max_commission_percent < 20 || $max_commission_percent > 50) {
            $this->log_error('Некорректный процент комиссии для инфлюенсера', [
                'user_id' => $user_id,
                'max_commission_percent' => $max_commission_percent
            ]);
            return false;
        }

        // Проверка, не является ли пользователь уже инфлюенсером
        if ($this->is_influencer($user_id)) {
            $this->log_info('Пользователь уже является инфлюенсером', ['user_id' => $user_id]);
            return false;
        }

        // В реальной реализации здесь будет создание записи в БД
        // $data = [
        //     'user_id' => $user_id,
        //     'max_commission_percent' => $max_commission_percent,
        //     'is_influencer' => 1,
        //     'admin_notes' => $admin_notes,
        //     'created_at' => current_time('mysql'),
        //     'updated_at' => current_time('mysql')
        // ];
        // return $this->repository->create($data);

        $this->log_info('Пользователь добавлен в инфлюенсеры', [
            'user_id' => $user_id,
            'max_commission_percent' => $max_commission_percent
        ]);

        return true;
    }

    /**
     * Обновление настроек инфлюенсера
     *
     * @param int   $user_id                ID пользователя
     * @param float $max_commission_percent Максимальный процент комиссии
     * @param bool  $is_influencer          Статус инфлюенсера
     * @param string $admin_notes           Административные заметки
     * @return bool
     */
    public function update_influencer($user_id, $max_commission_percent, $is_influencer = true, $admin_notes = '') {
        // Проверка существования пользователя
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            $this->log_error('Попытка обновить настройки несуществующего пользователя', ['user_id' => $user_id]);
            return false;
        }

        // Валидация процента комиссии
        if ($max_commission_percent < 20 || $max_commission_percent > 50) {
            $this->log_error('Некорректный процент комиссии для инфлюенсера', [
                'user_id' => $user_id,
                'max_commission_percent' => $max_commission_percent
            ]);
            return false;
        }

        // В реальной реализации здесь будет обновление записи в БД
        // $data = [
        //     'max_commission_percent' => $max_commission_percent,
        //     'is_influencer' => $is_influencer ? 1 : 0,
        //     'admin_notes' => $admin_notes,
        //     'updated_at' => current_time('mysql')
        // ];
        // return $this->repository->update_by_user_id($user_id, $data);

        $this->log_info('Настройки инфлюенсера обновлены', [
            'user_id' => $user_id,
            'max_commission_percent' => $max_commission_percent,
            'is_influencer' => $is_influencer
        ]);

        return true;
    }

    /**
     * Удаление пользователя из инфлюенсеров
     *
     * @param int $user_id ID пользователя
     * @return bool
     */
    public function remove_influencer($user_id) {
        // Проверка существования пользователя
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            $this->log_error('Попытка удалить несуществующего пользователя из инфлюенсеров', ['user_id' => $user_id]);
            return false;
        }

        // В реальной реализации здесь будет удаление записи из БД
        // return $this->repository->delete_by_user_id($user_id);

        $this->log_info('Пользователь удален из инфлюенсеров', ['user_id' => $user_id]);

        return true;
    }

    /**
     * Проверка, является ли пользователь инфлюенсером
     *
     * @param int $user_id ID пользователя
     * @return bool
     */
    public function is_influencer($user_id) {
        $influencer = $this->get_by_user_id($user_id);
        return $influencer !== null && $influencer->is_influencer;
    }

    /**
     * Получение максимального процента комиссии для пользователя
     *
     * @param int $user_id ID пользователя
     * @return float
     */
    public function get_max_commission_percent($user_id) {
        $influencer = $this->get_by_user_id($user_id);
        
        if ($influencer && $influencer->is_influencer) {
            return (float) $influencer->max_commission_percent;
        }
        
        // Базовый максимальный процент для обычных пользователей
        return 40.0;
    }

    /**
     * Поиск пользователей для добавления в инфлюенсеры
     *
     * @param string $search Поисковый запрос
     * @param int    $limit  Лимит результатов
     * @return array
     */
    public function search_users($search, $limit = 10) {
        if (strlen($search) < 3) {
            return [];
        }

        // Поиск пользователей в WordPress
        $users = get_users([
            'search' => '*' . $search . '*',
            'search_columns' => ['user_login', 'user_email', 'display_name'],
            'number' => $limit,
            'fields' => ['ID', 'user_login', 'user_email', 'display_name']
        ]);

        // Фильтрация пользователей, которые еще не являются инфлюенсерами
        $results = [];
        foreach ($users as $user) {
            if (!$this->is_influencer($user->ID)) {
                $results[] = [
                    'id' => $user->ID,
                    'user_login' => $user->user_login,
                    'user_email' => $user->user_email,
                    'display_name' => $user->display_name
                ];
            }
        }

        return $results;
    }

    /**
     * Получение статистики по инфлюенсерам
     *
     * @return array
     */
    public function get_statistics() {
        $influencers = $this->get_all();
        
        $stats = [
            'total_influencers' => count($influencers),
            'active_influencers' => 0,
            'average_commission' => 0,
            'max_commission' => 0,
            'min_commission' => 50
        ];

        $total_commission = 0;
        
        foreach ($influencers as $influencer) {
            if ($influencer->is_influencer) {
                $stats['active_influencers']++;
                $total_commission += $influencer->max_commission_percent;
                
                if ($influencer->max_commission_percent > $stats['max_commission']) {
                    $stats['max_commission'] = $influencer->max_commission_percent;
                }
                
                if ($influencer->max_commission_percent < $stats['min_commission']) {
                    $stats['min_commission'] = $influencer->max_commission_percent;
                }
            }
        }

        if ($stats['active_influencers'] > 0) {
            $stats['average_commission'] = round($total_commission / $stats['active_influencers'], 1);
        }

        return $stats;
    }

    /**
     * Получение демо-данных инфлюенсеров
     *
     * @return array
     */
    private function get_demo_influencers() {
        return [
            (object) [
                'id' => 1,
                'user_login' => 'influencer1',
                'user_email' => 'influencer1@example.com',
                'display_name' => 'Инфлюенсер 1',
                'max_commission_percent' => 35,
                'is_influencer' => true,
                'admin_notes' => 'YouTube блогер с 50K подписчиков',
                'created_at' => '2025-06-01 10:00:00'
            ],
            (object) [
                'id' => 2,
                'user_login' => 'influencer2',
                'user_email' => 'influencer2@example.com',
                'display_name' => 'Инфлюенсер 2',
                'max_commission_percent' => 50,
                'is_influencer' => true,
                'admin_notes' => 'Telegram канал с 100K подписчиков',
                'created_at' => '2025-06-05 14:30:00'
            ],
            (object) [
                'id' => 3,
                'user_login' => 'influencer3',
                'user_email' => 'influencer3@example.com',
                'display_name' => 'Инфлюенсер 3',
                'max_commission_percent' => 25,
                'is_influencer' => false,
                'admin_notes' => 'Временно отключен',
                'created_at' => '2025-06-10 09:15:00'
            ]
        ];
    }
}
