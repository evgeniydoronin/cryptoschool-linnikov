<?php
/**
 * Template Name: Навчання
 *
 * @package CryptoSchool
 */

// Если пользователь не авторизован, перенаправляем на страницу входа
if (!is_user_logged_in()) {
    wp_redirect(site_url('/sign-in/'));
    exit;
}

get_header();

// Заглушки для курсов (можно заменить на реальные данные из БД)
$courses = array(
    array(
        'id' => 1,
        'title' => 'Основи',
        'status' => 'done',
        'image' => get_template_directory_uri() . '/frontend-source/dist/assets/img/temp/course-card-illustration.png',
        'topics' => array(
            'Знакомство с нами',
            'Що таке крипта',
            'Что такое блокчейн?',
            'Токены и монеты — в чем разница?',
            'Експлорер'
        )
    ),
    array(
        'id' => 2,
        'title' => 'Діскрод і ТГ',
        'status' => 'done',
        'image' => get_template_directory_uri() . '/frontend-source/dist/assets/img/temp/course-card-illustration.png',
        'topics' => array(
            'Введение в Discord и поддержка',
            'Телеграм бот и ветки'
        )
    ),
    array(
        'id' => 3,
        'title' => 'CEX',
        'status' => 'in_progress',
        'image' => get_template_directory_uri() . '/frontend-source/dist/assets/img/temp/course-card-illustration.png',
        'topics' => array(
            'Robota із Біржами',
            'Что такое биржа и как она работает?',
            'Как зарегистрироваться на бирже?',
            'Как купить первую крипту? п2п + кантор',
            'Основы спотовой торговли',
            'Фючерсы',
            'Риск-менеджмент в торговле'
        )
    ),
    array(
        'id' => 4,
        'title' => 'Кошельки',
        'status' => 'locked',
        'image' => get_template_directory_uri() . '/frontend-source/dist/assets/img/temp/course-card-illustration.png',
        'topics' => array(
            'Создание криптокошелька',
            'Безопасность',
            'Вывод токенов на кошелек, перевод между кошельками',
            'Трасті'
        )
    ),
    array(
        'id' => 5,
        'title' => 'DEFI',
        'status' => 'locked',
        'image' => get_template_directory_uri() . '/frontend-source/dist/assets/img/temp/course-card-illustration.png',
        'topics' => array(
            'Что такое DeFi?',
            'Лендінг і позики в крипті',
            'Основы стейкинга',
            'Бріджи и межсетевые операции',
            'Что такое ЛП та фармінг',
            'Хедж',
            'Інструменти в крипті'
        )
    )
);

// Заглушки для последних заданий
$last_tasks = array(
    array(
        'id' => 1,
        'status' => 'orange', // orange, green, red
        'pretitle' => 'Поток Crypto education Factory от Crypto | Sjft | Tools',
        'title' => 'DEX обзор universety tools',
        'subtitle' => 'Відкритий',
        'amount' => '+5'
    ),
    array(
        'id' => 2,
        'status' => 'orange',
        'pretitle' => 'Поток Crypto education Factory от Crypto | Sjft | Tools',
        'title' => 'DEX обзор universety tools',
        'subtitle' => 'Відкритий',
        'amount' => '+5'
    ),
    array(
        'id' => 3,
        'status' => 'orange',
        'pretitle' => 'Поток Crypto education Factory от Crypto | Sjft | Tools',
        'title' => 'DEX обзор universety tools',
        'subtitle' => 'Відкритий',
        'amount' => '+5'
    ),
    array(
        'id' => 4,
        'status' => 'orange',
        'pretitle' => 'Поток Crypto education Factory от Crypto | Sjft | Tools',
        'title' => 'DEX обзор universety tools',
        'subtitle' => 'Відкритий',
        'amount' => '+5'
    ),
    array(
        'id' => 5,
        'status' => 'orange',
        'pretitle' => 'Поток Crypto education Factory от Crypto | Sjft | Tools',
        'title' => 'DEX обзор universety tools',
        'subtitle' => 'Відкритий',
        'amount' => '+5'
    )
);
?>

<main>
    <div class="page-background">
        <div class="ratio-wrap page-background__wrap">
            <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/decor-light.svg" alt="Page decor" class="ratio-wrap__item page-background__img_light">
            <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/decor-dark.svg" alt="Page decor" class="ratio-wrap__item page-background__img_dark">
        </div>
    </div>
    <div class="container container_wide courses__container">
        <!-- Горизонтальная навигация -->
        <?php get_template_part('template-parts/account/horizontal-navigation'); ?>
                    <!-- Блок прогресса обучения -->
                    <div class="study-daily-progress palette palette_blurred account-block courses__progress">
            <div class="study-daily-progress__steps">
                <div class="study-daily-progress__step">
                    <div class="study-daily-progress__reward">
                        <div class="text study-daily-progress__value">+5</div>
                        <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/shared/star.svg" alt="">
                    </div>
                    <div class="text-small study-daily-progress__condition">1 день</div>
                </div>
                <div class="study-daily-progress__step">
                    <div class="study-daily-progress__reward">
                        <div class="text study-daily-progress__value">+5</div>
                        <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/shared/star.svg" alt="">
                    </div>
                    <div class="text-small study-daily-progress__condition">2 день</div>
                </div>
                <div class="study-daily-progress__step">
                    <div class="study-daily-progress__reward">
                        <div class="text study-daily-progress__value">+5</div>
                        <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/shared/star.svg" alt="">
                    </div>
                    <div class="text-small study-daily-progress__condition">3 день</div>
                </div>
                <div class="study-daily-progress__step">
                    <div class="study-daily-progress__reward">
                        <div class="text study-daily-progress__value">+5</div>
                        <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/shared/star.svg" alt="">
                    </div>
                    <div class="text-small study-daily-progress__condition">4 день</div>
                </div>
                <div class="study-daily-progress__step">
                    <div class="study-daily-progress__reward">
                        <div class="text-small study-daily-progress__value">
                            Щоденний<br> відрізок
                        </div>
                        <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/shared/star.svg" alt="">
                    </div>
                    <div class="text-small study-daily-progress__condition">5 день</div>
                </div>
            </div>
            <div class="study-daily-progress__progress">
                <div class="study-daily-progress__track">
                    <div class="study-daily-progress__fill"></div>
                </div>
                <div class="study-daily-progress__points">
                    <div class="study-daily-progress__point study-daily-progress__point_filled">
                        <div class="study-daily-progress__point-circle">
                            <span class="icon-check-arrow"></span>
                        </div>
                    </div>
                    <div class="study-daily-progress__point study-daily-progress__point_filled">
                        <div class="study-daily-progress__point-circle">
                            <span class="icon-check-arrow"></span>
                        </div>
                    </div>
                    <div class="study-daily-progress__point">
                        <div class="study-daily-progress__point-circle">
                            <span class="icon-check-arrow"></span>
                        </div>
                    </div>
                    <div class="study-daily-progress__point">
                        <div class="study-daily-progress__point-circle">
                            <span class="icon-check-arrow"></span>
                        </div>
                    </div>
                    <div class="study-daily-progress__point">
                        <div class="study-daily-progress__point-circle">
                            <span class="icon-check-arrow"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="study-daily-progress__hints">
                <div class="study-daily-progress__hint text-small">Отримайте свою щоденну виногороду вже зараз!</div>
                <div class="study-daily-progress__hint text-small">Практикуйтесь щодня, щоб не втратити відрізок</div>
            </div>
        </div>
        
                    <!-- Блок списка курсов -->
                    <div class="account-block palette palette_blurred courses__block">
            <h6 class="h6 color-primary account-block__title courses__block-title">Наши курси</h6>
            <hr class="account-block__horizontal-row">
            
            <div class="courses__list">
                <?php foreach ($courses as $course) : ?>
                    <div class="course-card <?php echo $course['status'] === 'done' ? 'course-card_done' : ($course['status'] === 'locked' ? 'course-card_locked' : ''); ?>">
                        <div class="course-card__header">
                            <?php if ($course['status'] === 'done') : ?>
                                <div class="text-small course-card__badge">Пройдено</div>
                            <?php endif; ?>
                            <img class="course-card__image" src="<?php echo esc_url($course['image']); ?>">
                        </div>
                        <div class="course-card__body">
                            <div class="h6 course-card__title"><?php echo esc_html($course['title']); ?></div>
                            <ul class="account-list course-card__list">
                                <?php foreach ($course['topics'] as $topic) : ?>
                                    <li><?php echo esc_html($topic); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="course-card__ellipsis text-small">...</div>
                        </div>
                        <div class="course-card__footer">
                            <?php if ($course['status'] === 'locked') : ?>
                                <button class="button button_filled button_rounded button_centered button_block" disabled>
                                    <span class="button__text">Зайти в курс</span>
                                </button>
                            <?php else : ?>
                                <a href="<?php echo esc_url(site_url('/course/?id=' . $course['id'])); ?>" class="button button_filled button_rounded button_centered button_block">
                                    <span class="button__text">Зайти в курс</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
                    <!-- Блок последних заданий -->
                    <div class="account-block palette palette_blurred">
            <h5 class="account-block__title text">Останні завданя</h5>
            <hr class="account-block__horizontal-row">
            <div class="account-block__tabs hide-tablet hide-mobile">
                <a href="#" class="account-block__tab text-small account-block__tab_active">Уcі</a>
                <a href="#" class="account-block__tab text-small">Активні</a>
                <a href="#" class="account-block__tab text-small">Виконані</a>
                <a href="#" class="account-block__tab text-small">На перевірці</a>
                <a href="#" class="account-block__tab text-small">Доопрацювати</a>
            </div>
            <div class="account-last-tasks__items">
                <?php foreach ($last_tasks as $task) : ?>
                    <div class="status-line palette palette_hoverable account-last-tasks-item">
                        <div class="status-line-indicator status-line-indicator_<?php echo esc_attr($task['status']); ?>"></div>
                        <div class="account-last-tasks-item__body">
                            <div class="account-last-tasks-item__content">
                                <div class="account-last-tasks-item__pretitle text-small color-primary">
                                    <?php echo esc_html($task['pretitle']); ?>
                                </div>
                                <h6 class="account-last-tasks-item__title text"><?php echo esc_html($task['title']); ?></h6>
                                <div class="account-last-tasks-item__subtitle text-small">
                                    <?php echo esc_html($task['subtitle']); ?>
                                </div>
                            </div>
                            <div class="account-last-tasks-item__details">
                                <div class="text-small account-last-tasks-item__amount"><?php echo esc_html($task['amount']); ?></div>
                                <button class="account-last-tasks-item__link">
                                    <span class="icon-play-triangle-right"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
                        <button class="account-more">
                            <span class="text-small color-primary">Показати ще</span>
                            <span class="icon-arrow-right-small account-more__icon"></span>
                        </button>
                    </div>
    </div>
</main>

<?php get_footer(); ?>
