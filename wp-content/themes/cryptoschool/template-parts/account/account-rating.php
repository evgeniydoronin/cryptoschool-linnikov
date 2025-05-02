<?php
/**
 * Шаблон блока рейтинга в личном кабинете
 *
 * @package CryptoSchool
 */

// Заглушки для рейтинга
$top_users = array(
    array(
        'rank' => 1,
        'name' => 'danicon01',
        'points' => 5
    ),
    array(
        'rank' => 2,
        'name' => 'cryptomaster',
        'points' => 5
    ),
    array(
        'rank' => 3,
        'name' => 'blockchain_pro',
        'points' => 5
    ),
    array(
        'rank' => 4,
        'name' => 'satoshi_fan',
        'points' => 5
    ),
    array(
        'rank' => 5,
        'name' => 'crypto_trader',
        'points' => 5
    )
);

// Текущий пользователь
$current_user_rank = 6;
$current_user = wp_get_current_user();
$current_user_name = $current_user->display_name;
?>

<div class="account-block palette palette_blurred account-rating">
    <h5 class="account-block__title">
        <span class="icon icon-thunder"></span>
        <span class="text">Рейтинг</span>
    </h5>

    <table class="account-table account-rating-table">
        <thead class="account-table-head">
            <tr>
                <td class="account-table-head__column">Місце</td>
                <td class="account-table-head__column">Імя та призвище</td>
                <td class="account-table-head__column">Creoin</td>
            </tr>
        </thead>
        <tbody class="account-table-body">
            <?php foreach ($top_users as $user) : ?>
                <tr class="account-table-body__item account-table-body__item_plain account-table-body__item_hoverable account-table-body__item_clickable">
                    <td class="account-table-body__column text-small color-primary"><?php echo esc_html($user['rank']); ?></td>
                    <td class="account-table-body__column text-small"><?php echo esc_html($user['name']); ?></td>
                    <td class="account-table-body__column">
                        <span class="text-small">+<?php echo esc_html($user['points']); ?></span>
                        <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/shared/star.svg">
                    </td>
                </tr>
            <?php endforeach; ?>
            
            <?php if ($current_user_rank > 5) : ?>
                <tr class="account-table-body__item account-table-body__item_plain account-table-body__item_hoverable account-table-body__item_clickable account-table-body__item_highlighted">
                    <td class="account-table-body__column text-small color-primary"><?php echo esc_html($current_user_rank); ?></td>
                    <td class="account-table-body__column text-small"><?php echo esc_html($current_user_name); ?></td>
                    <td class="account-table-body__column">
                        <span class="text-small">+5</span>
                        <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/shared/star.svg">
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="account-more">
        <a href="#" class="account-more__link text-small">
            Дивитися всі
            <span class="account-more__icon icon-nav-arrow-right"></span>
        </a>
    </div>
</div>
