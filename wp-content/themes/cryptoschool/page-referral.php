<?php
/**
 * Template Name: Реферальна програма
 *
 * @package CryptoSchool
 */

// Если пользователь не авторизован, перенаправляем на страницу входа
if (!is_user_logged_in()) {
    wp_redirect(site_url('/sign-in/'));
    exit;
}

get_header();

// Заглушки для данных реферальной программы
$referral_data = array(
    'ref_back_percent' => 20,
    'referral_discount_percent' => 20,
    'referral_link' => 'https://cryptoschool.com/ref/user123',
    'total_invited' => 434,
    'total_purchased' => 214,
    'total_payments' => '$1 400',
    'available_for_withdrawal' => '$650'
);

// Заглушки для последних выплат
$last_payments = array(
    array(
        'date' => '24.05.2024',
        'time' => '16:30',
        'amount' => '$230',
        'status' => 'processing',
        'status_text' => 'Виконується',
        'status_class' => 'status-line-indicator_orange',
        'status_color' => 'color-orange',
        'comment' => ''
    ),
    array(
        'date' => '24.05.2024',
        'time' => '16:30',
        'amount' => '$130',
        'status' => 'success',
        'status_text' => 'Успішно',
        'status_class' => 'status-line-indicator_green',
        'status_color' => 'color-success',
        'comment' => ''
    ),
    array(
        'date' => '24.05.2024',
        'time' => '16:30',
        'amount' => '$230',
        'status' => 'error',
        'status_text' => 'Помилка',
        'status_class' => 'status-line-indicator_red',
        'status_color' => 'color-danger',
        'comment' => 'Неправильні реквізити'
    )
);

// Заглушки для рефералов
$referrals = array(
    array(
        'date' => '24.05.2024',
        'time' => '16:30',
        'telegram' => '@ho*****pce',
        'status' => 'success',
        'status_text' => 'Успішно',
        'status_class' => 'status-line-indicator_green',
        'status_color' => 'color-success'
    ),
    array(
        'date' => '24.05.2024',
        'time' => '16:30',
        'telegram' => '@ho*****pce',
        'status' => 'registered',
        'status_text' => 'Зареєстрований',
        'status_class' => 'status-line-indicator_orange',
        'status_color' => ''
    ),
    array(
        'date' => '24.05.2024',
        'time' => '16:30',
        'telegram' => '@ho*****pce',
        'status' => 'registered',
        'status_text' => 'Зареєстрований',
        'status_class' => 'status-line-indicator_orange',
        'status_color' => ''
    )
);
?>

<main>
    <div class="container container_wide">
        <div class="account-layout">
            <!-- Боковая навигация -->
            <?php get_template_part('template-parts/account/sidebar-navigation'); ?>
            
            <div class="account-layout-column account-layout-center">
                <div class="account-layout-column-slice account-layout-center__top">
                    <!-- Заголовок страницы -->
                    <div class="account-greeting">
                        <div class="account-greeting__left">
                            <h4 class="h4 account-greeting__title">Реферальна програма</h4>
                        </div>
                    </div>
                </div>

                <div class="account-layout-column-slice account-layout-center-bottom">
                    <!-- Конструктор знижок -->
                    <div class="account-block palette palette_blurred account-block_compressed account-discount-constructor">
                        <h5 class="account-block__title text">Конструктор знижок</h5>

                        <hr class="account-block__horizontal-row" />

                        <div class="range account-discount-constructor__range">
                            <div class="range__body">
                                <div class="range__value range__value_left color-primary text">
                                    <span data-left=""><?php echo esc_html($referral_data['ref_back_percent']); ?></span>%
                                </div>

                                <input class="range-slider range__control" type="range" min="0" max="40" step="0.1" value="<?php echo esc_attr($referral_data['ref_back_percent']); ?>" />
                                
                                <div class="range__value range__value_right color-primary text">
                                    <span data-right=""><?php echo esc_html($referral_data['referral_discount_percent']); ?></span>%
                                </div>
                            </div>

                            <div class="range__footer">
                                <div class="range__caption range__caption_left text-small">Ваш REF-BACK</div>
                                <div class="range__tip text-small">
                                    <span data-left=""><?php echo esc_html($referral_data['ref_back_percent']); ?></span>% на <span data-right=""><?php echo esc_html($referral_data['referral_discount_percent']); ?></span>%
                                </div>
                                <div class="range__caption range__caption_right text-small">Знижка реферала</div>
                            </div>
                        </div>

                        <hr class="account-block__horizontal-row account-discount-constructor__separator" />

                        <div class="account-discount-constructor-reference">
                            <label for="discount-constructor-input" class="account-discount-constructor-reference__label text color-primary">Ваше реферальне посилання</label>

                            <label for="discount-constructor-input" class="account-discount-constructor-reference__block palette palette_hide-tablet palette_hide-mobile">
                                <div class="account-discount-constructor-reference__input-block palette palette_hide-desktop">
                                    <input id="discount-constructor-input" placeholder="Ваше посилання" type="text" class="account-discount-constructor-reference__input text-small" value="<?php echo esc_attr($referral_data['referral_link']); ?>" readonly>
                                </div>

                                <button class="button button_filled button_rounded button_small" onclick="copyToClipboard('discount-constructor-input')">
                                    <span class="button__text">Скопіювати</span>
                                </button>
                            </label>
                        </div>
                    </div>

                    <!-- Загальна статистика -->
                    <div class="account-block palette palette_blurred account-block_compressed account-total-statistics">
                        <h5 class="account-block__title text">Загальна статистика</h5>

                        <hr class="account-block__horizontal-row" />

                        <div class="account-total-statistics__content">
                            <div class="account-total-statistics__item">
                                <h5 class="h5 color-primary"><?php echo esc_html($referral_data['total_invited']); ?></h5>
                                <div class="text-small account-total-statistics__description">Запрошено людей</div>
                            </div>

                            <div class="account-total-statistics__item">
                                <h5 class="h5 color-primary"><?php echo esc_html($referral_data['total_purchased']); ?></h5>
                                <div class="text-small account-total-statistics__description">Придбали програму</div>
                            </div>

                            <div class="account-total-statistics__item">
                                <h5 class="h5 color-primary"><?php echo esc_html($referral_data['total_payments']); ?></h5>
                                <div class="text-small account-total-statistics__description">Загальна сума виплат</div>
                            </div>

                            <div class="account-total-statistics__item">
                                <h5 class="h5 color-primary"><?php echo esc_html($referral_data['available_for_withdrawal']); ?></h5>
                                <div class="text-small account-total-statistics__description">Доступно для виведення</div>
                            </div>
                        </div>
                    </div>

                    <!-- Останні виплати -->
                    <div class="account-block palette palette_blurred account-block_compressed">
                        <h5 class="account-block__title text">Останні виплати</h5>
                        
                        <table class="account-table account-table_status account-table_compressed account-last-payments-table">
                            <thead class="account-table-head">
                                <tr>
                                    <td class="account-table-head__column">Дата</td>
                                    <td class="account-table-head__column">Сумма <span class="hide-mobile">виплати</span></td>
                                    <td class="account-table-head__column">Статус <span class="hide-mobile">виплати</span></td>
                                    <td class="account-table-head__column hide-mobile">Коментар</td>
                                </tr>
                            </thead>

                            <tbody class="account-table-body">
                                <?php foreach ($last_payments as $payment) : ?>
                                    <tr>
                                        <td class="status-line palette">
                                            <div class="status-line-indicator <?php echo esc_attr($payment['status_class']); ?>"></div>

                                            <div class="account-table-body__item">
                                                <div class="text color-primary"><span class="hide-mobile"><?php echo esc_html($payment['date']); ?></span> <?php echo esc_html($payment['time']); ?></div>
                                                <div class="text color-primary"><?php echo esc_html($payment['amount']); ?></div>
                                                <div class="text <?php echo esc_attr($payment['status_color']); ?>"><?php echo esc_html($payment['status_text']); ?></div>
                                                <div class="text account-last-payments-table__comment hide-mobile"><?php echo esc_html($payment['comment']); ?></div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Мої реферали -->
                    <div class="account-block palette palette_blurred account-block_compressed">
                        <h5 class="account-block__title text">Мої реферали</h5>
                        
                        <table class="account-table account-table_status account-table_compressed account-my-referrals-table">
                            <thead class="account-table-head">
                                <tr>
                                    <td class="account-table-head__column">Дата</td>
                                    <td class="account-table-head__column">Ник <span class="hide-mobile">Telegram</span></td>
                                    <td class="account-table-head__column">Статус</td>
                                </tr>
                            </thead>

                            <tbody class="account-table-body">
                                <?php foreach ($referrals as $referral) : ?>
                                    <tr>
                                        <td class="status-line palette">
                                            <div class="status-line-indicator <?php echo esc_attr($referral['status_class']); ?>"></div>

                                            <div class="account-table-body__item">
                                                <div class="text color-primary"><span class="hide-mobile"><?php echo esc_html($referral['date']); ?></span> <?php echo esc_html($referral['time']); ?></div>
                                                <div class="text color-primary"><?php echo esc_html($referral['telegram']); ?></div>
                                                <div class="text <?php echo esc_attr($referral['status_color']); ?> <?php echo ($referral['status'] === 'registered') ? 'account-my-referrals-table__status-default' : ''; ?>"><?php echo esc_html($referral['status_text']); ?></div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function copyToClipboard(elementId) {
    var copyText = document.getElementById(elementId);
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    document.execCommand("copy");
    
    // Показываем уведомление об успешном копировании
    alert("Посилання скопійовано: " + copyText.value);
}
</script>

<?php get_footer(); ?>
