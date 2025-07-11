<?php
/**
 * Сервис для работы с запросами на вывод средств
 *
 * @package CryptoSchool
 * @subpackage Services
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс сервиса для работы с запросами на вывод средств
 */
class CryptoSchool_Service_Withdrawal extends CryptoSchool_Service {
    /**
     * Репозиторий запросов на вывод
     *
     * @var CryptoSchool_Repository_Withdrawal_Request
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
        // $this->repository = new CryptoSchool_Repository_Withdrawal_Request();
    }

    /**
     * Регистрация хуков и фильтров
     *
     * @return void
     */
    protected function register_hooks() {
        // Регистрация хуков для уведомлений администраторов
        $this->add_action('cryptoschool_withdrawal_request_created', 'notify_admin_new_request');
        $this->add_action('cryptoschool_withdrawal_request_approved', 'notify_user_approved');
        $this->add_action('cryptoschool_withdrawal_request_rejected', 'notify_user_rejected');
        $this->add_action('cryptoschool_withdrawal_request_paid', 'notify_user_paid');
    }

    /**
     * Получение всех запросов на вывод
     *
     * @param array $args Аргументы для фильтрации
     * @return array
     */
    public function get_all($args = []) {
        // Пока возвращаем демо-данные
        $requests = $this->get_demo_withdrawal_requests();
        
        // Фильтрация по статусу
        if (!empty($args['status'])) {
            $requests = array_filter($requests, function($request) use ($args) {
                return $request->status === $args['status'];
            });
        }
        
        // Фильтрация по пользователю
        if (!empty($args['user_id'])) {
            $requests = array_filter($requests, function($request) use ($args) {
                return $request->user_id == $args['user_id'];
            });
        }
        
        return array_values($requests);
    }

    /**
     * Получение запроса на вывод по ID
     *
     * @param int $id ID запроса
     * @return object|null
     */
    public function get_by_id($id) {
        $requests = $this->get_all();
        
        foreach ($requests as $request) {
            if ($request->id == $id) {
                return $request;
            }
        }
        
        return null;
    }

    /**
     * Создание запроса на вывод
     *
     * @param int    $user_id         ID пользователя
     * @param float  $amount          Сумма для вывода
     * @param string $crypto_address  Адрес криптокошелька
     * @param string $crypto_currency Криптовалюта
     * @return int|false ID созданного запроса или false в случае ошибки
     */
    public function create_request($user_id, $amount, $crypto_address, $crypto_currency = 'USDT') {
        // Проверка существования пользователя
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            $this->log_error('Попытка создать запрос на вывод для несуществующего пользователя', ['user_id' => $user_id]);
            return false;
        }

        // Валидация суммы
        if ($amount < 100) {
            $this->log_error('Сумма запроса на вывод меньше минимальной', [
                'user_id' => $user_id,
                'amount' => $amount
            ]);
            return false;
        }

        // Проверка баланса пользователя
        $user_balance = $this->get_user_balance($user_id);
        if ($user_balance < $amount) {
            $this->log_error('Недостаточно средств для вывода', [
                'user_id' => $user_id,
                'amount' => $amount,
                'balance' => $user_balance
            ]);
            return false;
        }

        // Валидация адреса кошелька
        if (!$this->validate_crypto_address($crypto_address, $crypto_currency)) {
            $this->log_error('Некорректный адрес криптокошелька', [
                'user_id' => $user_id,
                'crypto_address' => $crypto_address,
                'crypto_currency' => $crypto_currency
            ]);
            return false;
        }

        // В реальной реализации здесь будет создание записи в БД
        // $data = [
        //     'user_id' => $user_id,
        //     'amount' => $amount,
        //     'crypto_address' => $crypto_address,
        //     'crypto_currency' => $crypto_currency,
        //     'status' => 'pending',
        //     'request_date' => current_time('mysql'),
        //     'admin_comment' => ''
        // ];
        // $request_id = $this->repository->create($data);

        $request_id = rand(1000, 9999); // Имитация ID

        $this->log_info('Создан запрос на вывод средств', [
            'request_id' => $request_id,
            'user_id' => $user_id,
            'amount' => $amount,
            'crypto_currency' => $crypto_currency
        ]);

        // Уведомление администраторов
        do_action('cryptoschool_withdrawal_request_created', $request_id, $user_id, $amount);

        return $request_id;
    }

    /**
     * Одобрение запроса на вывод
     *
     * @param int    $request_id     ID запроса
     * @param string $admin_comment  Комментарий администратора
     * @return bool
     */
    public function approve_request($request_id, $admin_comment = '') {
        $request = $this->get_by_id($request_id);
        if (!$request) {
            $this->log_error('Попытка одобрить несуществующий запрос на вывод', ['request_id' => $request_id]);
            return false;
        }

        if ($request->status !== 'pending') {
            $this->log_error('Попытка одобрить запрос с некорректным статусом', [
                'request_id' => $request_id,
                'current_status' => $request->status
            ]);
            return false;
        }

        // В реальной реализации здесь будет обновление записи в БД
        // $data = [
        //     'status' => 'approved',
        //     'admin_comment' => $admin_comment,
        //     'approved_at' => current_time('mysql')
        // ];
        // $result = $this->repository->update($request_id, $data);

        $this->log_info('Запрос на вывод одобрен', [
            'request_id' => $request_id,
            'user_id' => $request->user_id,
            'amount' => $request->amount
        ]);

        // Уведомление пользователя
        do_action('cryptoschool_withdrawal_request_approved', $request_id, $request->user_id);

        return true;
    }

    /**
     * Отклонение запроса на вывод
     *
     * @param int    $request_id     ID запроса
     * @param string $admin_comment  Комментарий администратора
     * @return bool
     */
    public function reject_request($request_id, $admin_comment = '') {
        $request = $this->get_by_id($request_id);
        if (!$request) {
            $this->log_error('Попытка отклонить несуществующий запрос на вывод', ['request_id' => $request_id]);
            return false;
        }

        if ($request->status !== 'pending') {
            $this->log_error('Попытка отклонить запрос с некорректным статусом', [
                'request_id' => $request_id,
                'current_status' => $request->status
            ]);
            return false;
        }

        // В реальной реализации здесь будет обновление записи в БД
        // $data = [
        //     'status' => 'rejected',
        //     'admin_comment' => $admin_comment,
        //     'rejected_at' => current_time('mysql')
        // ];
        // $result = $this->repository->update($request_id, $data);

        $this->log_info('Запрос на вывод отклонен', [
            'request_id' => $request_id,
            'user_id' => $request->user_id,
            'amount' => $request->amount,
            'reason' => $admin_comment
        ]);

        // Уведомление пользователя
        do_action('cryptoschool_withdrawal_request_rejected', $request_id, $request->user_id, $admin_comment);

        return true;
    }

    /**
     * Отметка запроса как оплаченного
     *
     * @param int    $request_id     ID запроса
     * @param string $transaction_id ID транзакции в блокчейне
     * @param string $admin_comment  Комментарий администратора
     * @return bool
     */
    public function mark_as_paid($request_id, $transaction_id = '', $admin_comment = '') {
        $request = $this->get_by_id($request_id);
        if (!$request) {
            $this->log_error('Попытка отметить как оплаченный несуществующий запрос', ['request_id' => $request_id]);
            return false;
        }

        if ($request->status !== 'approved') {
            $this->log_error('Попытка отметить как оплаченный запрос с некорректным статусом', [
                'request_id' => $request_id,
                'current_status' => $request->status
            ]);
            return false;
        }

        // В реальной реализации здесь будет обновление записи в БД
        // $data = [
        //     'status' => 'paid',
        //     'transaction_id' => $transaction_id,
        //     'admin_comment' => $admin_comment,
        //     'paid_at' => current_time('mysql')
        // ];
        // $result = $this->repository->update($request_id, $data);

        $this->log_info('Запрос на вывод отмечен как оплаченный', [
            'request_id' => $request_id,
            'user_id' => $request->user_id,
            'amount' => $request->amount,
            'transaction_id' => $transaction_id
        ]);

        // Уведомление пользователя
        do_action('cryptoschool_withdrawal_request_paid', $request_id, $request->user_id, $transaction_id);

        return true;
    }

    /**
     * Получение баланса пользователя
     *
     * @param int $user_id ID пользователя
     * @return float
     */
    public function get_user_balance($user_id) {
        // В реальной реализации здесь будет запрос к БД для подсчета баланса
        // из таблицы реферальных транзакций минус уже выведенные суммы
        
        // Пока возвращаем демо-данные
        $demo_balances = [
            1 => 250.75,
            2 => 180.50,
            3 => 95.25,
            10 => 150.00,
            15 => 75.50,
            20 => 200.00
        ];
        
        return isset($demo_balances[$user_id]) ? $demo_balances[$user_id] : 0.0;
    }

    /**
     * Получение истории запросов пользователя
     *
     * @param int $user_id ID пользователя
     * @return array
     */
    public function get_user_requests($user_id) {
        return $this->get_all(['user_id' => $user_id]);
    }

    /**
     * Валидация адреса криптокошелька
     *
     * @param string $address  Адрес кошелька
     * @param string $currency Криптовалюта
     * @return bool
     */
    private function validate_crypto_address($address, $currency) {
        if (empty($address)) {
            return false;
        }

        switch (strtoupper($currency)) {
            case 'USDT':
            case 'TRX':
                // Валидация TRON адреса (начинается с T, длина 34 символа)
                return preg_match('/^T[A-Za-z0-9]{33}$/', $address);
                
            case 'BTC':
                // Валидация Bitcoin адреса
                return preg_match('/^[13][a-km-zA-HJ-NP-Z1-9]{25,34}$/', $address) ||
                       preg_match('/^bc1[a-z0-9]{39,59}$/', $address);
                       
            case 'ETH':
                // Валидация Ethereum адреса
                return preg_match('/^0x[a-fA-F0-9]{40}$/', $address);
                
            default:
                return strlen($address) >= 26 && strlen($address) <= 62;
        }
    }

    /**
     * Получение статистики по запросам на вывод
     *
     * @return array
     */
    public function get_statistics() {
        $requests = $this->get_all();
        
        $stats = [
            'total_requests' => count($requests),
            'pending_requests' => 0,
            'approved_requests' => 0,
            'paid_requests' => 0,
            'rejected_requests' => 0,
            'total_amount' => 0,
            'paid_amount' => 0,
            'pending_amount' => 0
        ];
        
        foreach ($requests as $request) {
            $stats['total_amount'] += $request->amount;
            
            switch ($request->status) {
                case 'pending':
                    $stats['pending_requests']++;
                    $stats['pending_amount'] += $request->amount;
                    break;
                case 'approved':
                    $stats['approved_requests']++;
                    break;
                case 'paid':
                    $stats['paid_requests']++;
                    $stats['paid_amount'] += $request->amount;
                    break;
                case 'rejected':
                    $stats['rejected_requests']++;
                    break;
            }
        }
        
        return $stats;
    }

    /**
     * Уведомление администраторов о новом запросе
     *
     * @param int   $request_id ID запроса
     * @param int   $user_id    ID пользователя
     * @param float $amount     Сумма запроса
     * @return void
     */
    public function notify_admin_new_request($request_id, $user_id, $amount) {
        $user = get_user_by('ID', $user_id);
        $admin_email = get_option('admin_email');
        
        $subject = sprintf(__('[%s] Новый запрос на вывод средств', 'cryptoschool'), get_bloginfo('name'));
        $message = sprintf(
            __("Пользователь %s (%s) создал запрос на вывод средств.\n\nСумма: $%.2f\nID запроса: %d\n\nПерейдите в админ-панель для обработки запроса.", 'cryptoschool'),
            $user->display_name,
            $user->user_email,
            $amount,
            $request_id
        );
        
        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Уведомление пользователя об одобрении запроса
     *
     * @param int $request_id ID запроса
     * @param int $user_id    ID пользователя
     * @return void
     */
    public function notify_user_approved($request_id, $user_id) {
        $user = get_user_by('ID', $user_id);
        $request = $this->get_by_id($request_id);
        
        $subject = sprintf(__('[%s] Запрос на вывод одобрен', 'cryptoschool'), get_bloginfo('name'));
        $message = sprintf(
            __("Ваш запрос на вывод средств одобрен.\n\nСумма: $%.2f\nID запроса: %d\n\nСредства будут переведены в ближайшее время.", 'cryptoschool'),
            $request->amount,
            $request_id
        );
        
        wp_mail($user->user_email, $subject, $message);
    }

    /**
     * Уведомление пользователя об отклонении запроса
     *
     * @param int    $request_id     ID запроса
     * @param int    $user_id        ID пользователя
     * @param string $admin_comment  Комментарий администратора
     * @return void
     */
    public function notify_user_rejected($request_id, $user_id, $admin_comment) {
        $user = get_user_by('ID', $user_id);
        $request = $this->get_by_id($request_id);
        
        $subject = sprintf(__('[%s] Запрос на вывод отклонен', 'cryptoschool'), get_bloginfo('name'));
        $message = sprintf(
            __("Ваш запрос на вывод средств отклонен.\n\nСумма: $%.2f\nID запроса: %d\nПричина: %s", 'cryptoschool'),
            $request->amount,
            $request_id,
            $admin_comment
        );
        
        wp_mail($user->user_email, $subject, $message);
    }

    /**
     * Уведомление пользователя о выплате
     *
     * @param int    $request_id     ID запроса
     * @param int    $user_id        ID пользователя
     * @param string $transaction_id ID транзакции
     * @return void
     */
    public function notify_user_paid($request_id, $user_id, $transaction_id) {
        $user = get_user_by('ID', $user_id);
        $request = $this->get_by_id($request_id);
        
        $subject = sprintf(__('[%s] Средства выплачены', 'cryptoschool'), get_bloginfo('name'));
        $message = sprintf(
            __("Ваш запрос на вывод средств выполнен.\n\nСумма: $%.2f\nID запроса: %d\nID транзакции: %s", 'cryptoschool'),
            $request->amount,
            $request_id,
            $transaction_id
        );
        
        wp_mail($user->user_email, $subject, $message);
    }

    /**
     * Получение демо-данных запросов на вывод
     *
     * @return array
     */
    private function get_demo_withdrawal_requests() {
        return [
            (object) [
                'id' => 1,
                'user_id' => 10,
                'user_login' => 'referrer1',
                'user_email' => 'referrer1@example.com',
                'amount' => 150.00,
                'crypto_address' => 'TQn9Y2khEsLJW1ChVWFMSMeRDow5KcbLSE',
                'crypto_currency' => 'USDT',
                'status' => 'pending',
                'request_date' => '2025-06-15 09:00:00',
                'admin_comment' => ''
            ],
            (object) [
                'id' => 2,
                'user_id' => 15,
                'user_login' => 'referrer2',
                'user_email' => 'referrer2@example.com',
                'amount' => 75.50,
                'crypto_address' => '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa',
                'crypto_currency' => 'BTC',
                'status' => 'approved',
                'request_date' => '2025-06-14 16:45:00',
                'admin_comment' => 'Проверено, готово к выплате'
            ],
            (object) [
                'id' => 3,
                'user_id' => 20,
                'user_login' => 'referrer3',
                'user_email' => 'referrer3@example.com',
                'amount' => 200.00,
                'crypto_address' => 'TQn9Y2khEsLJW1ChVWFMSMeRDow5KcbLSE',
                'crypto_currency' => 'USDT',
                'status' => 'paid',
                'request_date' => '2025-06-10 11:20:00',
                'admin_comment' => 'Выплачено 16.06.2025'
            ],
            (object) [
                'id' => 4,
                'user_id' => 25,
                'user_login' => 'referrer4',
                'user_email' => 'referrer4@example.com',
                'amount' => 50.00,
                'crypto_address' => 'TInvalidAddress123',
                'crypto_currency' => 'USDT',
                'status' => 'rejected',
                'request_date' => '2025-06-12 14:30:00',
                'admin_comment' => 'Некорректный адрес кошелька'
            ]
        ];
    }
}
