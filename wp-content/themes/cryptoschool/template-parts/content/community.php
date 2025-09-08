<?php
$zagolovok_blokov_komunyty = get_field('zagolovok_blokov_komunyty');
$levj_blok = get_field('levj_blok');
$czentralnj_blok = get_field('czentralnj_blok');
$pravj_blok = get_field('pravj_blok');
?>
<main>
  <div class="page-background">
    <div class="ratio-wrap page-background__wrap"> <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/decor-light.svg" alt="Page decor"
        class="ratio-wrap__item page-background__img_light"> <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/decor-dark.svg" alt="Page decor"
        class="ratio-wrap__item page-background__img_dark"> </div>
  </div>
  <div class="container container_wide community__container">
    <div class="community__hero">
      <div class="community__hero-content">
        <h1 class="h0 color-primary community__hero-title">
          <?php echo get_the_title(); ?>
        </h1>
        <?php echo get_the_content(); ?>
      </div>
      <div class="community__hero-decor community__hero-nft-1"> <img
          src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/community/community-hero-nft-1.png"> </div>
      <div class="community__hero-decor community__hero-nft-2"> <img
          src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/community/community-hero-nft-2.png"> </div>
      <div class="community__hero-decor community__hero-glass-1"> <img
          src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/community/community-hero-glass-1.png"> </div>
      <div class="community__hero-decor community__hero-glass-2"> <img
          src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/community/community-hero-glass-2.png"> </div>
    </div>
    <div class="community__flow"> <img class="community__flow_light"
        src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/community/community-flow-light.png"> <img class="community__flow_dark"
        src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/community/community-flow-dark.png"> </div>
    <div class="community-chats community__chats">
      <h4 class="h4 color-primary community-chats__title">
        <?php echo $zagolovok_blokov_komunyty; ?>
      </h4>
      <div class="community-chats__cards">
        <div class="community-chats__card"> <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/community/chats-decor-1.png"
            class="community-chats-decor-1">
          <div class="community-chats__card-icon"> 
            <span class="icon-telegram"></span> 
          </div>
          <h6 class="h6 community-chats__card-title">
            <?php echo $levj_blok['zagolovok']; ?>
          </h6>
          <div class="text-small community-chats__card-text">
            <?php echo $levj_blok['tekst']; ?>
          </div>
          <button class="button button_white button_rounded button_centered button_block">
            <span
              class="button__text"><?php echo $levj_blok['knopka']['title']; ?></span>
          </button>
        </div>
        <div class="community-chats__card">
          <div class="community-chats__card-icon"> <span class="icon-discord"></span> </div>
          <h6 class="h6 community-chats__card-title">
            <?php echo $czentralnj_blok['zagolovok']; ?>
          </h6>
          <div class="text-small community-chats__card-text">
            <?php echo $czentralnj_blok['tekst']; ?>
          </div>
          <button class="button button_white button_rounded button_centered button_block">
            <span
              class="button__text"><?php echo $czentralnj_blok['knopka']['title']; ?></span>
          </button>
        </div>
        <div class="community-chats__card"> <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/community/chats-decor-2.png"
            class="community-chats-decor-2">
          <div class="community-chats__card-icon"> <span class="icon-link"></span> </div>
          <h6 class="h6 community-chats__card-title">
            <?php echo $pravj_blok['zagolovok']; ?>
          </h6>
          <div class="text-small community-chats__card-text">
            <?php echo $pravj_blok['tekst']; ?>
          </div>
          <button class="button button_white button_rounded button_centered button_block">
            <span
              class="button__text"><?php echo $pravj_blok['knopka']['title']; ?></span>
          </button>
        </div>
      </div>
    </div>
    <?php get_template_part('template-parts/widgets/faq'); ?>
  </div>
</main>