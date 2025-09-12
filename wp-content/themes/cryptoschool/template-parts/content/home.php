<?php

/**
 * Шаблон основного содержимого главной страницы
 *
 * @package CryptoSchool
 */

$hp_zagolovok = get_field('hp_zagolovok');
$hp_knopky = get_field('hp_knopky');
?>
<main>
  <div class="page-background">
    <div class="ratio-wrap page-background__wrap"> <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/decor-light.svg" alt="Page decor"
        class="ratio-wrap__item page-background__img_light"> <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/decor-dark.svg" alt="Page decor"
        class="ratio-wrap__item page-background__img_dark"> </div>
  </div>
  <div class="home-hero">
    <div class="container container_wide home-hero__container">
      <div class="home-hero-content">
        <h1 class="h1 home-hero-content__title">
          <span class="h2"><?php echo $hp_zagolovok['hp_zagolovok_1']; ?></span>
          <span><?php echo $hp_zagolovok['hp_zagolovok_2']; ?></span>
        </h1>
        <p class="text home-hero-content__description">
          <?php echo get_field('hp_podzagolovok'); ?>
        </p>
        <div class="home-hero-content__buttons">
          <a href="<?php echo $hp_knopky['hp_levaya_knopka']['url']; ?>" class="button button_filled button_big"> <span
              class="button__text button__text_uppercase">
              <?php echo $hp_knopky['hp_levaya_knopka']['title']; ?>
            </span>
          </a>
          <a href="<?php echo $hp_knopky['hp_pravaya_knopka']['url']; ?>" class="button button_outlined button_big"> 
            <span
              class="button__text button__text_black button__text_uppercase">
              <?php echo $hp_knopky['hp_pravaya_knopka']['title']; ?>
            </span> 
          </a>
        </div>
      </div>
      <div class="home-hero__image">
        <div class="ratio-wrap ratio-wrap_contain home-hero__image-wrap"> <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/home-hero-light.png"
            alt="Hero light" class="ratio-wrap__item home-hero__image-item_light"> <img
            src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/home-hero-dark.png" alt="Hero dark" class="ratio-wrap__item home-hero__image-item_dark">
          <div class="home-hero__coins" data-hero-coins>
            <div class="home-hero__coins-layer-1"> <img class="home-hero__coins-4"
                src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/hero/coins/coin4.png" alt="" srcset=""> <img class="home-hero__coins-10"
                src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/hero/coins/coin10.png" alt="" srcset=""> <img class="home-hero__coins-11"
                src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/hero/coins/coin11.png" alt="" srcset=""> </div>
            <div class="home-hero__coins-layer-2"> <img class="home-hero__coins-2"
                src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/hero/coins/coin2.png" alt="" srcset=""> <img class="home-hero__coins-3"
                src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/hero/coins/coin3.png" alt="" srcset=""> <img class="home-hero__coins-7"
                src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/hero/coins/coin7.png" alt="" srcset=""> <img class="home-hero__coins-8"
                src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/hero/coins/coin8.png" alt="" srcset=""> </div>
            <div class="home-hero__coins-layer-3"> <img class="home-hero__coins-1"
                src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/hero/coins/coin1.png" alt="" srcset=""> <img class="home-hero__coins-5"
                src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/hero/coins/coin5.png" alt="" srcset=""> <img class="home-hero__coins-6"
                src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/hero/coins/coin6.png" alt="" srcset=""> <img class="home-hero__coins-9"
                src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/hero/coins/coin9.png" alt="" srcset=""> </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>