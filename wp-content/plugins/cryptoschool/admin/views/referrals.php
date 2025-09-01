<?php
/**
 * Шаблон для страницы управления реферальной системой
 *
 * @package CryptoSchool
 * @subpackage Admin\Views
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

// Данные передаются из контроллера
// $influencers, $withdrawal_requests, $stats уже доступны
?>

<div class="wrap cryptoschool-admin">
    <h1 class="wp-heading-inline"><?php _e('Реферальная система', 'cryptoschool'); ?></h1>
    
    <hr class="wp-header-end">
    
    <div class="notice notice-info">
        <p><?php _e('Здесь вы можете управлять настройками реферальной системы, устанавливать повышенные проценты комиссии для инфлюенсеров и обрабатывать запросы на вывод средств.', 'cryptoschool'); ?></p>
    </div>
    
    <div class="cryptoschool-admin-tabs">
        <ul class="cryptoschool-admin-tabs-nav">
            <li class="active"><a href="#statistics"><?php _e('Статистика', 'cryptoschool'); ?></a></li>
            <li><a href="#referral-links"><?php _e('Реферальные ссылки', 'cryptoschool'); ?></a></li>
            <li><a href="#recent-referrals"><?php _e('Реферальные связи', 'cryptoschool'); ?></a></li>
            <li><a href="#transactions"><?php _e('Транзакции', 'cryptoschool'); ?></a></li>
            <li><a href="#influencers"><?php _e('Инфлюенсеры', 'cryptoschool'); ?></a></li>
            <li><a href="#withdrawal-requests"><?php _e('Запросы на вывод', 'cryptoschool'); ?></a></li>
        </ul>
        
        <div class="cryptoschool-admin-tabs-content">
            <!-- Вкладка "Статистика" -->
            <div id="statistics" class="cryptoschool-admin-tab active">
                <div class="cryptoschool-admin-card">
                    <h2><?php _e('Статистика реферальной системы', 'cryptoschool'); ?></h2>
                    
                    <div class="cryptoschool-admin-card-content">
                        <div class="cryptoschool-admin-stats-grid">
                            <div class="cryptoschool-admin-stats-card">
                                <h3><?php _e('Общая статистика', 'cryptoschool'); ?></h3>
                                <ul>
                                    <li><?php _e('Всего реферальных ссылок:', 'cryptoschool'); ?> <strong><?php echo esc_html($stats['total']['referral_links']); ?></strong></li>
                                    <li><?php _e('Всего рефералов:', 'cryptoschool'); ?> <strong><?php echo esc_html($stats['total']['referrals']); ?></strong></li>
                                    <li><?php _e('Всего покупок через реферальные ссылки:', 'cryptoschool'); ?> <strong><?php echo esc_html($stats['total']['purchases']); ?></strong></li>
                                    <li><?php _e('Общая сумма комиссий:', 'cryptoschool'); ?> <strong>$<?php echo number_format($stats['total']['commissions_amount'], 2); ?></strong></li>
                                    <li><?php _e('Выплачено комиссий:', 'cryptoschool'); ?> <strong>$<?php echo number_format($stats['total']['paid_amount'], 2); ?></strong></li>
                                </ul>
                            </div>
                            
                            <div class="cryptoschool-admin-stats-card">
                                <h3><?php _e('Статистика за последний месяц', 'cryptoschool'); ?></h3>
                                <ul>
                                    <li><?php _e('Новых реферальных ссылок:', 'cryptoschool'); ?> <strong><?php echo esc_html($stats['monthly']['referral_links']); ?></strong></li>
                                    <li><?php _e('Новых рефералов:', 'cryptoschool'); ?> <strong><?php echo esc_html($stats['monthly']['referrals']); ?></strong></li>
                                    <li><?php _e('Покупок через реферальные ссылки:', 'cryptoschool'); ?> <strong><?php echo esc_html($stats['monthly']['purchases']); ?></strong></li>
                                    <li><?php _e('Сумма комиссий:', 'cryptoschool'); ?> <strong>$<?php echo number_format($stats['monthly']['commissions_amount'], 2); ?></strong></li>
                                    <li><?php _e('Запросов на вывод:', 'cryptoschool'); ?> <strong><?php echo esc_html($stats['monthly']['withdrawal_requests']); ?></strong></li>
                                </ul>
                            </div>
                            
                            <div class="cryptoschool-admin-stats-card">
                                <h3><?php _e('Топ рефоводов', 'cryptoschool'); ?></h3>
                                <?php if (!empty($stats['top_referrers'])) : ?>
                                    <ol>
                                        <?php foreach ($stats['top_referrers'] as $referrer) : ?>
                                            <li>
                                                <strong><?php echo esc_html($referrer['user_login']); ?></strong><br>
                                                <small><?php echo esc_html($referrer['user_email']); ?></small><br>
                                                <span class="description">
                                                    <?php printf(__('Заработано: $%s | Рефералов: %d', 'cryptoschool'), 
                                                        number_format($referrer['total_earned'], 2), 
                                                        esc_html($referrer['referrals_count'])
                                                    ); ?>
                                                </span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ol>
                                <?php else : ?>
                                    <p><?php _e('Нет данных для отображения.', 'cryptoschool'); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="cryptoschool-admin-stats-card">
                                <h3><?php _e('Топ реферальных ссылок', 'cryptoschool'); ?></h3>
                                <?php if (!empty($stats['top_links'])) : ?>
                                    <ol>
                                        <?php foreach ($stats['top_links'] as $link) : ?>
                                            <li>
                                                <strong><?php echo esc_html($link['link_name']); ?></strong><br>
                                                <span class="description">
                                                    <?php printf(__('Переходы: %d | Конверсии: %d (%.1f%%) | Заработано: $%s', 'cryptoschool'), 
                                                        esc_html($link['clicks']), 
                                                        esc_html($link['conversions']),
                                                        esc_html($link['conversion_rate']),
                                                        number_format($link['total_earned'], 2)
                                                    ); ?>
                                                </span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ol>
                                <?php else : ?>
                                    <p><?php _e('Нет данных для отображения.', 'cryptoschool'); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Вкладка "Инфлюенсеры" -->
            <div id="influencers" class="cryptoschool-admin-tab">
                <div class="cryptoschool-admin-card">
                    <h2><?php _e('Управление инфлюенсерами', 'cryptoschool'); ?></h2>
                    
                    <div class="cryptoschool-admin-card-content">
                        <p><?php _e('Здесь вы можете установить повышенный процент комиссии для инфлюенсеров (до 50%).', 'cryptoschool'); ?></p>
                        
                        <div class="cryptoschool-admin-form-row">
                            <label for="influencer-search"><?php _e('Поиск пользователя:', 'cryptoschool'); ?></label>
                            <input type="text" id="influencer-search" class="regular-text" placeholder="<?php _e('Введите email или имя пользователя', 'cryptoschool'); ?>">
                            <button type="button" class="button" id="influencer-search-button"><?php _e('Найти', 'cryptoschool'); ?></button>
                        </div>
                        
                        <div id="influencer-search-results" style="display: none;">
                            <h3><?php _e('Результаты поиска', 'cryptoschool'); ?></h3>
                            <div class="cryptoschool-admin-table-container">
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th><?php _e('ID', 'cryptoschool'); ?></th>
                                            <th><?php _e('Имя пользователя', 'cryptoschool'); ?></th>
                                            <th><?php _e('Email', 'cryptoschool'); ?></th>
                                            <th><?php _e('Действия', 'cryptoschool'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody id="influencer-search-results-body">
                                        <!-- Результаты поиска будут добавлены через JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <h3><?php _e('Список инфлюенсеров', 'cryptoschool'); ?></h3>
                        
                        <?php if (empty($influencers)) : ?>
                            <p><?php _e('Инфлюенсеры не найдены.', 'cryptoschool'); ?></p>
                        <?php else : ?>
                            <div class="cryptoschool-admin-table-container">
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th><?php _e('ID', 'cryptoschool'); ?></th>
                                            <th><?php _e('Имя пользователя', 'cryptoschool'); ?></th>
                                            <th><?php _e('Email', 'cryptoschool'); ?></th>
                                            <th><?php _e('Максимальный процент комиссии', 'cryptoschool'); ?></th>
                                            <th><?php _e('Статус', 'cryptoschool'); ?></th>
                                            <th><?php _e('Действия', 'cryptoschool'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($influencers as $influencer) : ?>
                                            <tr>
                                                <td><?php echo esc_html($influencer->id); ?></td>
                                                <td><?php echo esc_html($influencer->user_login); ?></td>
                                                <td><?php echo esc_html($influencer->user_email); ?></td>
                                                <td><?php echo esc_html($influencer->max_commission_percent); ?>%</td>
                                                <td><?php echo $influencer->is_influencer ? __('Активен', 'cryptoschool') : __('Неактивен', 'cryptoschool'); ?></td>
                                                <td>
                                                    <button type="button" class="button edit-influencer" data-id="<?php echo esc_attr($influencer->id); ?>"><?php _e('Редактировать', 'cryptoschool'); ?></button>
                                                    <button type="button" class="button button-link-delete delete-influencer" data-id="<?php echo esc_attr($influencer->id); ?>"><?php _e('Удалить', 'cryptoschool'); ?></button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Вкладка "Запросы на вывод" -->
            <div id="withdrawal-requests" class="cryptoschool-admin-tab">
                <div class="cryptoschool-admin-card">
                    <h2><?php _e('Запросы на вывод средств', 'cryptoschool'); ?></h2>
                    
                    <div class="cryptoschool-admin-card-content">
                        <p><?php _e('Здесь вы можете обрабатывать запросы на вывод средств от рефоводов.', 'cryptoschool'); ?></p>
                        
                        <div class="cryptoschool-admin-form-row">
                            <label for="withdrawal-status-filter"><?php _e('Фильтр по статусу:', 'cryptoschool'); ?></label>
                            <select id="withdrawal-status-filter" class="regular-text">
                                <option value=""><?php _e('Все запросы', 'cryptoschool'); ?></option>
                                <option value="pending"><?php _e('Ожидающие', 'cryptoschool'); ?></option>
                                <option value="approved"><?php _e('Одобренные', 'cryptoschool'); ?></option>
                                <option value="paid"><?php _e('Оплаченные', 'cryptoschool'); ?></option>
                                <option value="rejected"><?php _e('Отклоненные', 'cryptoschool'); ?></option>
                            </select>
                            <button type="button" class="button" id="withdrawal-filter-button"><?php _e('Применить', 'cryptoschool'); ?></button>
                        </div>
                        
                        <?php if (empty($withdrawal_requests)) : ?>
                            <p><?php _e('Запросы на вывод не найдены.', 'cryptoschool'); ?></p>
                        <?php else : ?>
                            <div class="cryptoschool-admin-table-container">
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th><?php _e('ID', 'cryptoschool'); ?></th>
                                            <th><?php _e('Пользователь', 'cryptoschool'); ?></th>
                                            <th><?php _e('Сумма', 'cryptoschool'); ?></th>
                                            <th><?php _e('Криптокошелек', 'cryptoschool'); ?></th>
                                            <th><?php _e('Статус', 'cryptoschool'); ?></th>
                                            <th><?php _e('Дата запроса', 'cryptoschool'); ?></th>
                                            <th><?php _e('Действия', 'cryptoschool'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($withdrawal_requests as $request) : ?>
                                            <tr>
                                                <td><?php echo esc_html($request->id); ?></td>
                                                <td><?php echo esc_html($request->user_login); ?></td>
                                                <td><?php echo esc_html($request->amount); ?> USD</td>
                                                <td><?php echo esc_html($request->crypto_address); ?></td>
                                                <td>
                                                    <?php
                                                    switch ($request->status) {
                                                        case 'pending':
                                                            echo __('Ожидает', 'cryptoschool');
                                                            break;
                                                        case 'approved':
                                                            echo __('Одобрен', 'cryptoschool');
                                                            break;
                                                        case 'paid':
                                                            echo __('Оплачен', 'cryptoschool');
                                                            break;
                                                        case 'rejected':
                                                            echo __('Отклонен', 'cryptoschool');
                                                            break;
                                                        default:
                                                            echo __('Неизвестно', 'cryptoschool');
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo date_i18n(get_option('date_format'), strtotime($request->request_date)); ?></td>
                                                <td>
                                                    <?php if ($request->status === 'pending') : ?>
                                                        <button type="button" class="button approve-withdrawal" data-id="<?php echo esc_attr($request->id); ?>"><?php _e('Одобрить', 'cryptoschool'); ?></button>
                                                        <button type="button" class="button button-link-delete reject-withdrawal" data-id="<?php echo esc_attr($request->id); ?>"><?php _e('Отклонить', 'cryptoschool'); ?></button>
                                                    <?php elseif ($request->status === 'approved') : ?>
                                                        <button type="button" class="button mark-as-paid" data-id="<?php echo esc_attr($request->id); ?>"><?php _e('Отметить как оплаченный', 'cryptoschool'); ?></button>
                                                    <?php else : ?>
                                                        <span><?php _e('Нет доступных действий', 'cryptoschool'); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Вкладка "Статистика" -->
            <div id="statistics" class="cryptoschool-admin-tab">
                <div class="cryptoschool-admin-card">
                    <h2><?php _e('Статистика реферальной системы', 'cryptoschool'); ?></h2>
                    
                    <div class="cryptoschool-admin-card-content">
                        <p><?php _e('Здесь вы можете просмотреть статистику реферальной системы.', 'cryptoschool'); ?></p>
                        
                        <div class="cryptoschool-admin-stats-grid">
                            <div class="cryptoschool-admin-stats-card">
                                <h3><?php _e('Общая статистика', 'cryptoschool'); ?></h3>
                                <ul>
                                    <li><?php _e('Всего реферальных ссылок:', 'cryptoschool'); ?> <strong><?php echo esc_html($stats['total']['referral_links']); ?></strong></li>
                                    <li><?php _e('Всего рефералов:', 'cryptoschool'); ?> <strong><?php echo esc_html($stats['total']['referrals']); ?></strong></li>
                                    <li><?php _e('Всего покупок через реферальные ссылки:', 'cryptoschool'); ?> <strong><?php echo esc_html($stats['total']['purchases']); ?></strong></li>
                                    <li><?php _e('Общая сумма комиссий:', 'cryptoschool'); ?> <strong><?php echo esc_html($stats['total']['commissions_amount']); ?> USD</strong></li>
                                    <li><?php _e('Выплачено комиссий:', 'cryptoschool'); ?> <strong><?php echo esc_html($stats['total']['paid_amount']); ?> USD</strong></li>
                                </ul>
                            </div>
                            
                            <div class="cryptoschool-admin-stats-card">
                                <h3><?php _e('Статистика за последний месяц', 'cryptoschool'); ?></h3>
                                <ul>
                                    <li><?php _e('Новых реферальных ссылок:', 'cryptoschool'); ?> <strong><?php echo esc_html($stats['monthly']['referral_links']); ?></strong></li>
                                    <li><?php _e('Новых рефералов:', 'cryptoschool'); ?> <strong><?php echo esc_html($stats['monthly']['referrals']); ?></strong></li>
                                    <li><?php _e('Покупок через реферальные ссылки:', 'cryptoschool'); ?> <strong><?php echo esc_html($stats['monthly']['purchases']); ?></strong></li>
                                    <li><?php _e('Сумма комиссий:', 'cryptoschool'); ?> <strong><?php echo esc_html($stats['monthly']['commissions_amount']); ?> USD</strong></li>
                                    <li><?php _e('Запросов на вывод:', 'cryptoschool'); ?> <strong><?php echo esc_html($stats['monthly']['withdrawal_requests']); ?></strong></li>
                                </ul>
                            </div>
                            
                            <div class="cryptoschool-admin-stats-card">
                                <h3><?php _e('Топ рефоводов', 'cryptoschool'); ?></h3>
                                <?php if (!empty($stats['top_referrers'])) : ?>
                                    <ol>
                                        <?php foreach ($stats['top_referrers'] as $referrer) : ?>
                                            <li>
                                                <strong><?php echo esc_html($referrer['user_login']); ?></strong><br>
                                                <small><?php echo esc_html($referrer['user_email']); ?></small><br>
                                                <span class="description">
                                                    <?php printf(__('Заработано: %s USD | Рефералов: %d', 'cryptoschool'), 
                                                        esc_html($referrer['total_earned']), 
                                                        esc_html($referrer['referrals_count'])
                                                    ); ?>
                                                </span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ol>
                                <?php else : ?>
                                    <p><?php _e('Нет данных для отображения.', 'cryptoschool'); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="cryptoschool-admin-stats-card">
                                <h3><?php _e('Топ реферальных ссылок', 'cryptoschool'); ?></h3>
                                <?php if (!empty($stats['top_links'])) : ?>
                                    <ol>
                                        <?php foreach ($stats['top_links'] as $link) : ?>
                                            <li>
                                                <strong><?php echo esc_html($link['link_name']); ?></strong><br>
                                                <span class="description">
                                                    <?php printf(__('Переходы: %d | Конверсии: %d (%.1f%%) | Заработано: %s USD', 'cryptoschool'), 
                                                        esc_html($link['clicks']), 
                                                        esc_html($link['conversions']),
                                                        esc_html($link['conversion_rate']),
                                                        esc_html($link['total_earned'])
                                                    ); ?>
                                                </span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ol>
                                <?php else : ?>
                                    <p><?php _e('Нет данных для отображения.', 'cryptoschool'); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Вкладка "Реферальные ссылки" -->
            <div id="referral-links" class="cryptoschool-admin-tab">
                <div class="cryptoschool-admin-card">
                    <h2><?php _e('Все реферальные ссылки', 'cryptoschool'); ?></h2>
                    
                    <div class="cryptoschool-admin-card-content">
                        <?php if (empty($referral_links)) : ?>
                            <p><?php _e('Реферальные ссылки не найдены.', 'cryptoschool'); ?></p>
                        <?php else : ?>
                            <div class="cryptoschool-admin-table-container">
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th><?php _e('Пользователь', 'cryptoschool'); ?></th>
                                            <th><?php _e('Название', 'cryptoschool'); ?></th>
                                            <th><?php _e('Код', 'cryptoschool'); ?></th>
                                            <th><?php _e('Скидка', 'cryptoschool'); ?></th>
                                            <th><?php _e('Комиссия', 'cryptoschool'); ?></th>
                                            <th><?php _e('Клики', 'cryptoschool'); ?></th>
                                            <th><?php _e('Конверсии', 'cryptoschool'); ?></th>
                                            <th><?php _e('Конверсия %', 'cryptoschool'); ?></th>
                                            <th><?php _e('Заработано', 'cryptoschool'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($referral_links as $link) : ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo esc_html($link->user_login); ?></strong><br>
                                                    <small><?php echo esc_html($link->user_email); ?></small>
                                                </td>
                                                <td><?php echo esc_html($link->link_name); ?></td>
                                                <td><code><?php echo esc_html($link->referral_code); ?></code></td>
                                                <td><?php echo esc_html($link->discount_percent); ?>%</td>
                                                <td><?php echo esc_html($link->commission_percent); ?>%</td>
                                                <td><?php echo esc_html($link->clicks_count); ?></td>
                                                <td><?php echo esc_html($link->conversions_count); ?></td>
                                                <td><?php echo esc_html($link->conversion_rate); ?>%</td>
                                                <td><strong>$<?php echo number_format($link->total_earned ?: 0, 2); ?></strong></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Вкладка "Реферальные связи" -->
            <div id="recent-referrals" class="cryptoschool-admin-tab">
                <div class="cryptoschool-admin-card">
                    <h2><?php _e('Последние реферальные связи', 'cryptoschool'); ?></h2>
                    
                    <div class="cryptoschool-admin-card-content">
                        <?php if (empty($recent_referrals)) : ?>
                            <p><?php _e('Реферальные связи не найдены.', 'cryptoschool'); ?></p>
                        <?php else : ?>
                            <div class="cryptoschool-admin-table-container">
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th><?php _e('Реферер', 'cryptoschool'); ?></th>
                                            <th><?php _e('Реферал', 'cryptoschool'); ?></th>
                                            <th><?php _e('Ссылка', 'cryptoschool'); ?></th>
                                            <th><?php _e('Статус', 'cryptoschool'); ?></th>
                                            <th><?php _e('Дата регистрации', 'cryptoschool'); ?></th>
                                            <th><?php _e('Дата покупки', 'cryptoschool'); ?></th>
                                            <th><?php _e('Сумма покупки', 'cryptoschool'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_referrals as $referral) : ?>
                                            <tr>
                                                <td><?php echo esc_html($referral->referrer_login); ?></td>
                                                <td><?php echo esc_html($referral->referred_login); ?></td>
                                                <td>
                                                    <?php echo esc_html($referral->link_name); ?><br>
                                                    <code><?php echo esc_html($referral->referral_code); ?></code>
                                                </td>
                                                <td>
                                                    <span class="<?php echo $referral->status == 'purchased' ? 'text-success' : ''; ?>">
                                                        <?php echo esc_html($referral->status); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo esc_html($referral->registration_date); ?></td>
                                                <td><?php echo esc_html($referral->purchase_date ?: '-'); ?></td>
                                                <td>
                                                    <?php if($referral->purchase_amount) : ?>
                                                        <strong>$<?php echo number_format($referral->purchase_amount, 2); ?></strong>
                                                    <?php else : ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Вкладка "Транзакции" -->
            <div id="transactions" class="cryptoschool-admin-tab">
                <div class="cryptoschool-admin-card">
                    <h2><?php _e('Последние транзакции', 'cryptoschool'); ?></h2>
                    
                    <div class="cryptoschool-admin-card-content">
                        <?php if (empty($recent_transactions)) : ?>
                            <p><?php _e('Транзакции не найдены.', 'cryptoschool'); ?></p>
                        <?php else : ?>
                            <div class="cryptoschool-admin-table-container">
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th><?php _e('Дата', 'cryptoschool'); ?></th>
                                            <th><?php _e('Реферер', 'cryptoschool'); ?></th>
                                            <th><?php _e('Покупатель', 'cryptoschool'); ?></th>
                                            <th><?php _e('Сумма', 'cryptoschool'); ?></th>
                                            <th><?php _e('Тип', 'cryptoschool'); ?></th>
                                            <th><?php _e('Статус', 'cryptoschool'); ?></th>
                                            <th><?php _e('Пакет', 'cryptoschool'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_transactions as $transaction) : ?>
                                            <tr>
                                                <td><?php echo esc_html($transaction->created_at); ?></td>
                                                <td><?php echo esc_html($transaction->referrer_login ?: 'ID: ' . $transaction->referrer_id); ?></td>
                                                <td><?php echo esc_html($transaction->buyer_login ?: ($transaction->user_id ? 'ID: ' . $transaction->user_id : '-')); ?></td>
                                                <td><strong>$<?php echo number_format($transaction->amount, 2); ?></strong></td>
                                                <td>
                                                    <?php 
                                                    $type_labels = array(
                                                        'commission_level_1' => '1-й уровень',
                                                        'commission_level_2' => '2-й уровень',
                                                        'manual_commission' => 'Ручное'
                                                    );
                                                    echo esc_html($type_labels[$transaction->type] ?? $transaction->type);
                                                    ?>
                                                </td>
                                                <td>
                                                    <span class="<?php echo $transaction->status == 'completed' ? 'text-success' : ''; ?>">
                                                        <?php echo esc_html($transaction->status); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo esc_html($transaction->package_name ?: '-'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для редактирования инфлюенсера -->
<div id="edit-influencer-modal" class="cryptoschool-admin-modal" style="display: none;">
    <div class="cryptoschool-admin-modal-content">
        <span class="cryptoschool-admin-modal-close">&times;</span>
        <h2><?php _e('Редактирование инфлюенсера', 'cryptoschool'); ?></h2>
        
        <form id="edit-influencer-form">
            <input type="hidden" id="edit-influencer-id" name="id">
            
            <div class="cryptoschool-admin-form-row">
                <label for="edit-influencer-max-commission"><?php _e('Максимальный процент комиссии:', 'cryptoschool'); ?></label>
                <input type="number" id="edit-influencer-max-commission" name="max_commission_percent" min="20" max="50" step="0.1" class="regular-text">
                <p class="description"><?php _e('Максимальный процент комиссии, который может установить инфлюенсер (от 20% до 50%).', 'cryptoschool'); ?></p>
            </div>
            
            <div class="cryptoschool-admin-form-row">
                <label for="edit-influencer-status"><?php _e('Статус:', 'cryptoschool'); ?></label>
                <select id="edit-influencer-status" name="is_influencer" class="regular-text">
                    <option value="1"><?php _e('Активен', 'cryptoschool'); ?></option>
                    <option value="0"><?php _e('Неактивен', 'cryptoschool'); ?></option>
                </select>
            </div>
            
            <div class="cryptoschool-admin-form-row">
                <label for="edit-influencer-notes"><?php _e('Примечания:', 'cryptoschool'); ?></label>
                <textarea id="edit-influencer-notes" name="admin_notes" class="large-text" rows="3"></textarea>
            </div>
            
            <div class="cryptoschool-admin-form-row">
                <button type="submit" class="button button-primary"><?php _e('Сохранить', 'cryptoschool'); ?></button>
                <button type="button" class="button cryptoschool-admin-modal-cancel"><?php _e('Отмена', 'cryptoschool'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Модальное окно для добавления инфлюенсера -->
<div id="add-influencer-modal" class="cryptoschool-admin-modal" style="display: none;">
    <div class="cryptoschool-admin-modal-content">
        <span class="cryptoschool-admin-modal-close">&times;</span>
        <h2><?php _e('Добавление инфлюенсера', 'cryptoschool'); ?></h2>
        
        <form id="add-influencer-form">
            <input type="hidden" id="add-influencer-user-id" name="user_id">
            
            <div class="cryptoschool-admin-form-row">
                <label for="add-influencer-user-info"><?php _e('Пользователь:', 'cryptoschool'); ?></label>
                <div id="add-influencer-user-info" class="cryptoschool-admin-user-info"></div>
            </div>
            
            <div class="cryptoschool-admin-form-row">
                <label for="add-influencer-max-commission"><?php _e('Максимальный процент комиссии:', 'cryptoschool'); ?></label>
                <input type="number" id="add-influencer-max-commission" name="max_commission_percent" min="20" max="50" step="0.1" value="20" class="regular-text">
                <p class="description"><?php _e('Максимальный процент комиссии, который может установить инфлюенсер (от 20% до 50%).', 'cryptoschool'); ?></p>
            </div>
            
            <div class="cryptoschool-admin-form-row">
                <label for="add-influencer-notes"><?php _e('Примечания:', 'cryptoschool'); ?></label>
                <textarea id="add-influencer-notes" name="admin_notes" class="large-text" rows="3"></textarea>
            </div>
            
            <div class="cryptoschool-admin-form-row">
                <button type="submit" class="button button-primary"><?php _e('Добавить', 'cryptoschool'); ?></button>
                <button type="button" class="button cryptoschool-admin-modal-cancel"><?php _e('Отмена', 'cryptoschool'); ?></button>
            </div>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Переключение вкладок
    $('.cryptoschool-admin-tabs-nav a').on('click', function(e) {
        e.preventDefault();
        
        var target = $(this).attr('href');
        
        $('.cryptoschool-admin-tabs-nav li').removeClass('active');
        $(this).parent().addClass('active');
        
        $('.cryptoschool-admin-tab').removeClass('active');
        $(target).addClass('active');
    });
    
    // Поиск пользователей для добавления инфлюенсера
    $('#influencer-search-button').on('click', function() {
        var search = $('#influencer-search').val();
        
        if (search.length < 3) {
            alert('<?php _e('Введите не менее 3 символов для поиска.', 'cryptoschool'); ?>');
            return;
        }
        
        // Здесь будет AJAX-запрос для поиска пользователей
        // Это заглушка, которая будет заменена на реальный код при реализации функционала
        
        // Пример результатов поиска
        var results = [
            { id: 1, user_login: 'user1', user_email: 'user1@example.com' },
            { id: 2, user_login: 'user2', user_email: 'user2@example.com' }
        ];
        
        var resultsHtml = '';
        
        if (results.length > 0) {
            for (var i = 0; i < results.length; i++) {
                resultsHtml += '<tr>';
                resultsHtml += '<td>' + results[i].id + '</td>';
                resultsHtml += '<td>' + results[i].user_login + '</td>';
                resultsHtml += '<td>' + results[i].user_email + '</td>';
                resultsHtml += '<td><button type="button" class="button add-as-influencer" data-id="' + results[i].id + '" data-login="' + results[i].user_login + '" data-email="' + results[i].user_email + '"><?php _e('Добавить как инфлюенсера', 'cryptoschool'); ?></button></td>';
                resultsHtml += '</tr>';
            }
        } else {
            resultsHtml = '<tr><td colspan="4"><?php _e('Пользователи не найдены.', 'cryptoschool'); ?></td></tr>';
        }
        
        $('#influencer-search-results-body').html(resultsHtml);
        $('#influencer-search-results').show();
    });
    
    // Добавление пользователя как инфлюенсера
    $(document).on('click', '.add-as-influencer', function() {
        var userId = $(this).data('id');
        var userLogin = $(this).data('login');
        var userEmail = $(this).data('email');
        
        $('#add-influencer-user-id').val(userId);
        $('#add-influencer-user-info').html('<strong>' + userLogin + '</strong> (' + userEmail + ')');
        
        $('#add-influencer-modal').show();
    });
    
    // Редактирование инфлюенсера
    $(document).on('click', '.edit-influencer', function() {
        var id = $(this).data('id');
        
        // Здесь будет AJAX-запрос для получения данных инфлюенсера
        // Это заглушка, которая будет заменена на реальный код при реализации функционала
        
        $('#edit-influencer-id').val(id);
        $('#edit-influencer-max-commission').val(30);
        $('#edit-influencer-status').val(1);
        $('#edit-influencer-notes').val('');
        
        $('#edit-influencer-modal').show();
    });
    
    // Удаление инфлюенсера
    $(document).on('click', '.delete-influencer', function() {
        if (confirm('<?php _e('Вы уверены, что хотите удалить этого инфлюенсера?', 'cryptoschool'); ?>')) {
            var id = $(this).data('id');
            
            // Здесь будет AJAX-запрос для удаления инфлюенсера
            // Это заглушка, которая будет заменена на реальный код при реализации функционала
            
            alert('<?php _e('Инфлюенсер успешно удален.', 'cryptoschool'); ?>');
        }
    });
    
    // Обработка запросов на вывод
    $(document).on('click', '.approve-withdrawal', function() {
        if (confirm('<?php _e('Вы уверены, что хотите одобрить этот запрос на вывод?', 'cryptoschool'); ?>')) {
            var id = $(this).data('id');
            
            // Здесь будет AJAX-запрос для одобрения запроса на вывод
            // Это заглушка, которая будет заменена на реальный код при реализации функционала
            
            alert('<?php _e('Запрос на вывод успешно одобрен.', 'cryptoschool'); ?>');
        }
    });
    
    $(document).on('click', '.reject-withdrawal', function() {
        if (confirm('<?php _e('Вы уверены, что хотите отклонить этот запрос на вывод?', 'cryptoschool'); ?>')) {
            var id = $(this).data('id');
            
            // Здесь будет AJAX-запрос для отклонения запроса на вывод
            // Это заглушка, которая будет заменена на реальный код при реализации функционала
            
            alert('<?php _e('Запрос на вывод успешно отклонен.', 'cryptoschool'); ?>');
        }
    });
    
    $(document).on('click', '.mark-as-paid', function() {
        if (confirm('<?php _e('Вы уверены, что хотите отметить этот запрос как оплаченный?', 'cryptoschool'); ?>')) {
            var id = $(this).data('id');
            
            // Здесь будет AJAX-запрос для отметки запроса как оплаченного
            // Это заглушка, которая будет заменена на реальный код при реализации функционала
            
            alert('<?php _e('Запрос на вывод успешно отмечен как оплаченный.', 'cryptoschool'); ?>');
        }
    });
    
    // Фильтрация запросов на вывод
    $('#withdrawal-filter-button').on('click', function() {
        var status = $('#withdrawal-status-filter').val();
        
        // Здесь будет AJAX-запрос для фильтрации запросов на вывод
        // Это заглушка, которая будет заменена на реальный код при реализации функционала
        
        alert('<?php _e('Фильтр применен.', 'cryptoschool'); ?>');
    });
    
    // Закрытие модальных окон
    $('.cryptoschool-admin-modal-close, .cryptoschool-admin-modal-cancel').on('click', function() {
        $('.cryptoschool-admin-modal').hide();
    });
    
    // Отправка формы редактирования инфлюенсера
    $('#edit-influencer-form').on('submit', function(e) {
        e.preventDefault();
        
        // Здесь будет AJAX-запрос для сохранения данных инфлюенсера
        // Это заглушка, которая будет заменена на реальный код при реализации функционала
        
        alert('<?php _e('Данные инфлюенсера успешно сохранены.', 'cryptoschool'); ?>');
        
        $('#edit-influencer-modal').hide();
    });
    
    // Отправка формы добавления инфлюенсера
    $('#add-influencer-form').on('submit', function(e) {
        e.preventDefault();
        
        // Здесь будет AJAX-запрос для добавления инфлюенсера
        // Это заглушка, которая будет заменена на реальный код при реализации функционала
        
        alert('<?php _e('Инфлюенсер успешно добавлен.', 'cryptoschool'); ?>');
        
        $('#add-influencer-modal').hide();
        $('#influencer-search-results').hide();
        $('#influencer-search').val('');
    });
});
</script>

<style>
/* Стили для вкладок */
.cryptoschool-admin-tabs {
    margin-top: 20px;
}

.cryptoschool-admin-tabs-nav {
    display: flex;
    margin: 0;
    padding: 0;
    list-style: none;
    border-bottom: 1px solid #ccc;
}

.cryptoschool-admin-tabs-nav li {
    margin: 0;
    padding: 0;
}

.cryptoschool-admin-tabs-nav a {
    display: block;
    padding: 10px 15px;
    text-decoration: none;
    color: #555;
    font-weight: 500;
    border: 1px solid transparent;
    border-bottom: none;
    margin-bottom: -1px;
}

.cryptoschool-admin-tabs-nav li.active a {
    background-color: #fff;
    border-color: #ccc;
    border-bottom-color: #fff;
    color: #000;
}

.cryptoschool-admin-tab {
    display: none;
    padding: 20px;
    border: 1px solid #ccc;
    border-top: none;
    background-color: #fff;
}

.cryptoschool-admin-tab.active {
    display: block;
}

/* Стили для карточек */
.cryptoschool-admin-card {
    background-color: #fff;
    border: 1px solid #ccc;
    border-radius: 3px;
    margin-bottom: 20px;
}

.cryptoschool-admin-card h2 {
    margin: 0;
    padding: 15px;
    border-bottom: 1px solid #ccc;
    background-color: #f5f5f5;
}

.cryptoschool-admin-card-content {
    padding: 15px;
}

/* Стили для форм */
.cryptoschool-admin-form-row {
    margin-bottom: 15px;
}

.cryptoschool-admin-form-row label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

/* Стили для модальных окон */
.cryptoschool-admin-modal {
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.4);
}

.cryptoschool-admin-modal-content {
    background-color: #fefefe;
    margin: 10% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 50%;
    max-width: 600px;
    border-radius: 3px;
    position: relative;
}

.cryptoschool-admin-modal-close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.cryptoschool-admin-modal-close:hover,
.cryptoschool-admin-modal-close:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
}

/* Стили для статистики */
.cryptoschool-admin-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.cryptoschool-admin-stats-card {
    background-color: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 3px;
    padding: 15px;
}

.cryptoschool-admin-stats-card h3 {
    margin-top: 0;
    border-bottom: 1px solid #ddd;
    padding-bottom: 10px;
}

.cryptoschool-admin-stats-card ul {
    margin: 0;
    padding: 0;
    list-style: none;
}

.cryptoschool-admin-stats-card li {
    margin-bottom: 5px;
}

/* Стили для таблиц */
.cryptoschool-admin-table-container {
    margin-top: 15px;
    margin-bottom: 15px;
    overflow-x: auto;
}

/* Стили для информации о пользователе */
.cryptoschool-admin-user-info {
    padding: 10px;
    background-color: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 3px;
    margin-bottom: 15px;
}
</style>
