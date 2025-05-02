<?php
/**
 * Шаблон шапки сайта
 *
 * @package CryptoSchool
 */
?>
<header id="header" class="header">
  <div class="header__top">
    <div class="container container_wide header__container header__container_top">
      <div class="header__top-lang-area" data-portal-dest="header-lang"></div>
      <div class="header__top-theme-switch-area" data-portal-dest="header-theme-switch"></div>
    </div>
  </div>
  <div class="container container_wide header__container header__container_bottom">
    <div class="header__logo"> <a href="<?php echo esc_url(home_url('/')); ?>" class="logo logo_header">
        <figure> <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/logo.svg" class="logo__img" alt="Logo">
          <figcaption aria-hidden="true">Main page</figcaption>
        </figure>
      </a> </div>
    <div class="header__lang-area" data-portal-src="header-lang">
      <div class="languages header__lang"> <a href="" class="languages__item languages__item_active">UA</a> <a href=""
          class="languages__item">RU</a> </div>
    </div>
    <div class="header__menu portal-menu" data-drawer="main-menu"> <button type="button"
        class="portal-menu__toggle-btn burger-btn" data-drawer-toggle="main-menu"></button>
      <menu class="portal-menu__menu portal-menu__menu_desc"> <a href="<?php echo esc_url(home_url('/courses/')); ?>"
          class="portal-menu__item portal-menu__item_active">Навчання</a> <a href=""
          class="portal-menu__item">Вартість</a> <a href="" class="portal-menu__item">Ком'юніті</a> <a href=""
          class="portal-menu__item">Відгуки</a>
        <div class="portal-menu__group"> <a href="" class="portal-menu__main-group-item portal-menu__item">Інфо</a>
          <span class="portal-menu__group-icon icon-dropdown-arrow"></span>
          <div class="portal-menu__group-drop-down">
            <div class="portal-menu__group-drop-down-inner"> <a href=""
                class="portal-menu__item portal-menu__item_submenu">Блог</a> <a href=""
                class="portal-menu__item portal-menu__item_submenu">Словник Криптана</a> <a href=""
                class="portal-menu__item portal-menu__item_submenu">Криптовалюты</a> </div>
          </div>
        </div> <a href="" class="portal-menu__item">Навчання</a>
      </menu>
      <menu class="portal-menu__menu portal-menu__menu_mob" data-elem="drawer.panel"> <a href="<?php echo esc_url(home_url('/courses/')); ?>"
          class="portal-menu__item portal-menu__item_active">Навчання</a> <a href=""
          class="portal-menu__item">Вартість</a> <a href="" class="portal-menu__item">Ком'юніті</a> <a href=""
          class="portal-menu__item">Відгуки</a> <a href="" class="portal-menu__item">Блог</a> <a href=""
          class="portal-menu__item">Словник Криптана</a> <a href="" class="portal-menu__item">Криптовалюты</a> <a
          href="" class="portal-menu__item">Навчання</a> </menu>
    </div>
    <div class="header__social-area">
      <div class="social-media header__social"> <a href="" class="social-media__item"><span
            class="icon-telegram"></span></a> <span class="social-media__separator"></span> <a href=""
          class="social-media__item"><span class="icon-instagram"></span></a> <span
          class="social-media__separator"></span> <a href="" class="social-media__item"><span
            class="icon-youtube-filled"></span></a> <span class="social-media__separator"></span> <a href=""
          class="social-media__item"><span class="icon-twitter"></span></a> </div>
    </div>
    <div class="header__theme-switch-area" data-portal-src="header-theme-switch">
      <div class="theme-switch header__theme-switch">
        <div class="theme-switch__inner"> <span class="theme-switch__icon icon-sun"></span>
          <div class="theme-switch__switch">
            <div class="theme-switch__thumb"></div>
          </div> <span class="theme-switch__icon icon-moon"></span>
        </div>
      </div>
    </div>

    <?php if (!is_user_logged_in()) : ?>
    <!-- Неавторизованный пользователь -->
    <div class="header__cabinet-tools">
      <a href="<?php echo esc_url(site_url('/sign-in/')); ?>" class="button button_small button_outlined header__login">
        <span class="button__icon icon-profile-filled header__enter-btn"></span>
        <span class="button__label">Вход</span>
      </a>
    </div>
    <?php else : ?>
    <!-- Авторизованный пользователь -->
    <div class="header__cabinet-tools">
      <div class="cabinet-menu__toggle" data-drawer-toggle="cabinet-menu">
        <span class="icon-thin-arrow-down cabinet-menu__toggle-icon"></span>
        <div class="account-menu-info-avatar cabinet-menu__avatar">
          <div class="account-menu-info-avatar__circle">
            <span class="icon-profile"></span>
          </div>
        </div>
      </div>
    </div>

    <div class="cabinet-menu header__cabinet-menu" data-drawer="cabinet-menu">
      <div class="cabinet-menu__drop-down" data-elem="drawer.panel">
        <div class="account-menu account-menu_as-drop-down">
          <a href="<?php echo esc_url(home_url('/dashboard/')); ?>" class="palette palette_blurred palette_hoverable palette_active account-menu-item account-menu-item_drop-down account-menu-item_active">
            <div class="account-menu-item__icon">
              <span class="icon-dashboard"></span>
            </div>
            <div class="account-menu-item__name text">Dashboard</div>
          </a>
          <a href="<?php echo esc_url(home_url('/courses/')); ?>" class="palette palette_blurred palette_hoverable account-menu-item account-menu-item_drop-down">
            <div class="account-menu-item__icon">
              <span class="icon-video-play"></span>
            </div>
            <div class="account-menu-item__name text">Навчання</div>
          </a>
          <a href="<?php echo esc_url(home_url('/rate/')); ?>" class="palette palette_blurred palette_hoverable account-menu-item account-menu-item_drop-down">
            <div class="account-menu-item__icon">
              <span class="icon-rate"></span>
            </div>
            <div class="account-menu-item__name text">Мій тариф</div>
          </a>
          <a href="<?php echo esc_url(home_url('/referral/')); ?>" class="palette palette_blurred palette_hoverable account-menu-item account-menu-item_drop-down">
            <div class="account-menu-item__icon">
              <span class="icon-referral"></span>
            </div>
            <div class="account-menu-item__name text">Реферальна програма</div>
          </a>
          <a href="<?php echo esc_url(home_url('/settings/')); ?>" class="palette palette_blurred palette_hoverable account-menu-item account-menu-item_drop-down">
            <div class="account-menu-item__icon">
              <span class="icon-settings"></span>
            </div>
            <div class="account-menu-item__name text">Налаштування</div>
          </a>
          <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="palette palette_blurred palette_hoverable account-menu-item account-menu-item_drop-down">
            <div class="account-menu-item__icon">
              <span class="icon-exit"></span>
            </div>
            <div class="account-menu-item__name text">Вийти</div>
          </a>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div>
</header>
