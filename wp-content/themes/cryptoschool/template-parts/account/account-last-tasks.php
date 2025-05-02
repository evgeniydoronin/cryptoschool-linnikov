<?php
/**
 * Шаблон блока последних заданий в личном кабинете
 *
 * @package CryptoSchool
 */

// Заглушки для последних заданий
$last_tasks = array(
    array(
        'status' => 'open',
        'status_class' => 'status-line-indicator_green',
        'status_text' => 'Відкритий',
        'title' => 'DEX обзор universety tools',
        'course' => 'Поток Crypto education Factory от Crypto | Sjft | Tools',
        'points' => 5
    ),
    array(
        'status' => 'in_progress',
        'status_class' => 'status-line-indicator_orange',
        'status_text' => 'У процесі',
        'title' => 'DEX обзор universety tools',
        'course' => 'Поток Crypto education Factory от Crypto | Sjft | Tools',
        'points' => 5
    ),
    array(
        'status' => 'closed',
        'status_class' => 'status-line-indicator_red',
        'status_text' => 'Закрытый',
        'title' => 'DEX обзор universety tools',
        'course' => 'Поток Crypto education Factory от Crypto | Sjft | Tools',
        'points' => 5
    )
);
?>

<div class="account-block palette palette_blurred">
    <h5 class="account-block__title text">Останні завданя</h5>

    <div class="account-last-tasks__items">
        <?php foreach ($last_tasks as $task) : ?>
            <div class="status-line palette palette_blurred palette_hoverable account-last-tasks-item">
                <div class="status-line-indicator <?php echo esc_attr($task['status_class']); ?>"></div>
                <div class="account-last-tasks-item__body">
                    <div class="account-last-tasks-item__content">
                        <?php if (!empty($task['course'])) : ?>
                            <div class="account-last-tasks-item__pretitle text-small color-primary">
                                <?php echo esc_html($task['course']); ?>
                            </div>
                        <?php endif; ?>
                        <h6 class="account-last-tasks-item__title text"><?php echo esc_html($task['title']); ?></h6>
                        <div class="account-last-tasks-item__subtitle text-small">
                            <?php echo esc_html($task['status_text']); ?>
                        </div>
                    </div>

                    <div class="account-last-tasks-item__details">
                        <div class="text-small account-last-tasks-item__amount">+<?php echo esc_html($task['points']); ?></div>
                        <button class="account-last-tasks-item__link">
                            <span class="icon-play-triangle-right"></span>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="account-more">
        <a href="<?php echo esc_url(site_url('/study/')); ?>" class="account-more__link text-small">
            Дивитися всі
            <span class="account-more__icon icon-nav-arrow-right"></span>
        </a>
    </div>
</div>
