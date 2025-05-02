<?php
/**
 * Template Name: Курс
 *
 * @package CryptoSchool
 */

// Если пользователь не авторизован, перенаправляем на страницу входа
if (!is_user_logged_in()) {
    wp_redirect(site_url('/sign-in/'));
    exit;
}

get_header();

// Получаем ID курса из GET-параметра
$course_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Заглушки для курсов (можно заменить на реальные данные из БД)
$courses = array(
    1 => array(
        'id' => 1,
        'title' => 'Основи Crypto Education',
        'modules' => array(
            array(
                'id' => 1,
                'title' => 'Основи',
                'number' => 1,
                'lessons_count' => 5,
                'opened' => true,
                'lessons' => array(
                    array(
                        'id' => 1,
                        'number' => 1,
                        'title' => 'Знакомство с нами',
                        'status' => 'done',
                        'status_text' => 'виконаний'
                    ),
                    array(
                        'id' => 2,
                        'number' => 2,
                        'title' => 'Що таке крипта',
                        'status' => 'done',
                        'status_text' => 'виконаний'
                    ),
                    array(
                        'id' => 3,
                        'number' => 3,
                        'title' => 'Что такое блокчейн?',
                        'status' => 'in-process',
                        'status_text' => 'У процесі'
                    ),
                    array(
                        'id' => 4,
                        'number' => 4,
                        'title' => 'Токены и монеты — в чем разница?',
                        'status' => 'locked',
                        'status_text' => 'Недоступний'
                    ),
                    array(
                        'id' => 5,
                        'number' => 5,
                        'title' => 'Експлорер',
                        'status' => 'locked',
                        'status_text' => 'Недоступний'
                    )
                )
            )
        )
    ),
    2 => array(
        'id' => 2,
        'title' => 'Діскрод і ТГ',
        'modules' => array(
            array(
                'id' => 2,
                'title' => 'Діскрод і ТГ',
                'number' => 1,
                'lessons_count' => 2,
                'opened' => true,
                'lessons' => array(
                    array(
                        'id' => 6,
                        'number' => 1,
                        'title' => 'Введение в Discord и поддержка',
                        'status' => 'done',
                        'status_text' => 'виконаний'
                    ),
                    array(
                        'id' => 7,
                        'number' => 2,
                        'title' => 'Телеграм бот и ветки',
                        'status' => 'done',
                        'status_text' => 'виконаний'
                    )
                )
            )
        )
    ),
    3 => array(
        'id' => 3,
        'title' => 'CEX',
        'modules' => array(
            array(
                'id' => 3,
                'title' => 'CEX',
                'number' => 1,
                'lessons_count' => 7,
                'opened' => true,
                'lessons' => array(
                    array(
                        'id' => 8,
                        'number' => 1,
                        'title' => 'Robota із Біржами',
                        'status' => 'done',
                        'status_text' => 'виконаний'
                    ),
                    array(
                        'id' => 9,
                        'number' => 2,
                        'title' => 'Что такое биржа и как она работает?',
                        'status' => 'done',
                        'status_text' => 'виконаний'
                    ),
                    array(
                        'id' => 10,
                        'number' => 3,
                        'title' => 'Как зарегистрироваться на бирже?',
                        'status' => 'in-process',
                        'status_text' => 'У процесі'
                    ),
                    array(
                        'id' => 11,
                        'number' => 4,
                        'title' => 'Как купить первую крипту? п2п + кантор',
                        'status' => 'locked',
                        'status_text' => 'Недоступний'
                    ),
                    array(
                        'id' => 12,
                        'number' => 5,
                        'title' => 'Основы спотовой торговли',
                        'status' => 'locked',
                        'status_text' => 'Недоступний'
                    ),
                    array(
                        'id' => 13,
                        'number' => 6,
                        'title' => 'Фючерсы',
                        'status' => 'locked',
                        'status_text' => 'Недоступний'
                    ),
                    array(
                        'id' => 14,
                        'number' => 7,
                        'title' => 'Риск-менеджмент в торговле',
                        'status' => 'locked',
                        'status_text' => 'Недоступний'
                    )
                )
            )
        )
    ),
    4 => array(
        'id' => 4,
        'title' => 'Кошельки',
        'modules' => array(
            array(
                'id' => 4,
                'title' => 'Кошельки',
                'number' => 1,
                'lessons_count' => 4,
                'opened' => true,
                'lessons' => array(
                    array(
                        'id' => 15,
                        'number' => 1,
                        'title' => 'Создание криптокошелька',
                        'status' => 'locked',
                        'status_text' => 'Недоступний'
                    ),
                    array(
                        'id' => 16,
                        'number' => 2,
                        'title' => 'Безопасность',
                        'status' => 'locked',
                        'status_text' => 'Недоступний'
                    ),
                    array(
                        'id' => 17,
                        'number' => 3,
                        'title' => 'Вывод токенов на кошелек, перевод между кошельками',
                        'status' => 'locked',
                        'status_text' => 'Недоступний'
                    ),
                    array(
                        'id' => 18,
                        'number' => 4,
                        'title' => 'Трасті',
                        'status' => 'locked',
                        'status_text' => 'Недоступний'
                    )
                )
            )
        )
    ),
    5 => array(
        'id' => 5,
        'title' => 'DEFI',
        'modules' => array(
            array(
                'id' => 5,
                'title' => 'DEFI',
                'number' => 1,
                'lessons_count' => 7,
                'opened' => true,
                'lessons' => array(
                    array(
                        'id' => 19,
                        'number' => 1,
                        'title' => 'Что такое DeFi?',
                        'status' => 'locked',
                        'status_text' => 'Недоступний'
                    ),
                    array(
                        'id' => 20,
                        'number' => 2,
                        'title' => 'Лендінг і позики в крипті',
                        'status' => 'locked',
                        'status_text' => 'Недоступний'
                    ),
                    array(
                        'id' => 21,
                        'number' => 3,
                        'title' => 'Основы стейкинга',
                        'status' => 'locked',
                        'status_text' => 'Недоступний'
                    ),
                    array(
                        'id' => 22,
                        'number' => 4,
                        'title' => 'Бріджи и межсетевые операции',
                        'status' => 'locked',
                        'status_text' => 'Недоступний'
                    ),
                    array(
                        'id' => 23,
                        'number' => 5,
                        'title' => 'Что такое ЛП та фармінг',
                        'status' => 'locked',
                        'status_text' => 'Недоступний'
                    ),
                    array(
                        'id' => 24,
                        'number' => 6,
                        'title' => 'Хедж',
                        'status' => 'locked',
                        'status_text' => 'Недоступний'
                    ),
                    array(
                        'id' => 25,
                        'number' => 7,
                        'title' => 'Інструменти в крипті',
                        'status' => 'locked',
                        'status_text' => 'Недоступний'
                    )
                )
            )
        )
    )
);

// Проверяем, существует ли курс с указанным ID
if (!isset($courses[$course_id])) {
    wp_redirect(site_url('/courses/'));
    exit;
}

// Получаем данные курса
$course = $courses[$course_id];
?>

<main>
    <div class="page-background">
        <div class="ratio-wrap page-background__wrap">
            <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/decor-light.svg" alt="Page decor" class="ratio-wrap__item page-background__img_light">
            <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/decor-dark.svg" alt="Page decor" class="ratio-wrap__item page-background__img_dark">
        </div>
    </div>

    <div class="container container_wide study__container">
        <div class="hide-mobile">
            <!-- Горизонтальная навигация -->
            <?php get_template_part('template-parts/account/horizontal-navigation'); ?>
        </div>

        <h5 class="h5 color-primary study__title"><?php echo esc_html($course['title']); ?></h5>

        <div class="study__modules">
            <?php foreach ($course['modules'] as $module) : ?>
                <div class="palette palette_blurred study-module <?php echo $module['opened'] ? 'study-module_opened' : ''; ?>">
                    <div class="study-module__summary">
                        <div class="study-module__left">
                            <div class="study-module__number text">Модуль <?php echo esc_html($module['number']); ?></div>
                            <div class="study-module__name text color-primary"><?php echo esc_html($module['title']); ?></div>
                        </div>
                        <div class="study-module__right">
                            <div class="study-module__amount text"><?php echo esc_html($module['lessons_count']); ?> уроків</div>
                            <div class="study-module__toggler">
                                <span class="icon-nav-arrow-right"></span>
                            </div>
                        </div>
                    </div>
                    <div class="study-module__dropdown">
                        <div class="study-module__lessons">
                            <?php foreach ($module['lessons'] as $lesson) : ?>
                                <div class="study-module__lesson study-module__lesson_<?php echo esc_attr($lesson['status']); ?>">
                                    <?php if ($lesson['status'] === 'done') : ?>
                                        <div class="study-module__lesson-check">
                                            <span class="icon-check-arrow"></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="study-module__lesson-left">
                                        <div class="study-module__lesson-number text"><?php echo esc_html($lesson['number']); ?></div>
                                        <div class="study-module__lesson-name text"><?php echo esc_html($lesson['title']); ?></div>
                                    </div>
                                    <div class="study-module__lesson-status text-small"><?php echo esc_html($lesson['status_text']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="bottom-navigation">
            <?php
            // Определяем предыдущий и следующий курсы
            $prev_course_id = $course_id > 1 ? $course_id - 1 : null;
            $next_course_id = $course_id < count($courses) ? $course_id + 1 : null;
            ?>
            <?php if ($prev_course_id) : ?>
                <a href="<?php echo esc_url(site_url('/course/?id=' . $prev_course_id)); ?>" class="bottom-navigation__item bottom-navigation__previous">
                    <div class="bottom-navigation__arrow">
                        <span class="icon-nav-arrow-left"></span>
                    </div>
                    <div class="bottom-navigation__label text-small">Попередній модуль</div>
                </a>
            <?php endif; ?>
            <?php if ($next_course_id) : ?>
                <a href="<?php echo esc_url(site_url('/course/?id=' . $next_course_id)); ?>" class="bottom-navigation__item bottom-navigation__next">
                    <div class="bottom-navigation__label text-small">Наступний модуль</div>
                    <div class="bottom-navigation__arrow">
                        <span class="icon-nav-arrow-right"></span>
                    </div>
                </a>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php get_footer(); ?>
