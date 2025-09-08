<?php
$punkt_taryfa = get_field('punkt_taryfa');
$stoymost_taryfa = get_field('stoymost_taryfa');
$punkt_taryfa_pro = get_field('punkt_taryfa_pro');
$stoymost_taryfa_pro = get_field('stoymost_taryfa_pro');
$punkt_taryfa_premium = get_field('punkt_taryfa_premium');
$stoymost_taryfa_premium = get_field('stoymost_taryfa_premium');
?>
<main>
  <div class="page-background">
    <div class="ratio-wrap page-background__wrap"> <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/decor-light.svg" alt="Page decor"
        class="ratio-wrap__item page-background__img_light"> <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/decor-dark.svg" alt="Page decor"
        class="ratio-wrap__item page-background__img_dark"> </div>
  </div>
  <div class="container container_wide rates__container">
    <div class="breadcrumbs rates__breadcrumbs">
      <?php echo cryptoschool_get_page_breadcrumbs(); ?>
    </div>
    <h4 class="rates__title h4 color-primary">
      <?php echo get_the_title(); ?>
    </h4>
    <div class="rates__list">
      <div class="rate-card rate-card_opened" data-rate-card>
        <div class="rate-card__header" data-rate-card-toggler>
          <h3 class="rate-card__title h3">Basic</h3>
          <div class="rate-card__toggler"></div>
        </div>
        <div class="rate-card__conditions">
          <?php if ($punkt_taryfa): ?>
            <?php $counter = 1; ?>
            <?php foreach ($punkt_taryfa as $item): ?>
              <div class="rate-card__condition<?php echo !$item['punkt']['neaktyvnj'] ? ' rate-card__condition_locked' : ''; ?>">
                <div class="rate-card__condition-number text"><?php echo str_pad($counter, 2, '0', STR_PAD_LEFT); ?></div>
                <div class="rate-card__condition-content text"><?php echo esc_html($item['punkt']['zagolovok']); ?></div>
              </div>
              <?php $counter++; ?>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <div class="rate-card__data">
          <div class="rate-card__price h6"> <?php echo $stoymost_taryfa['czena']; ?> </div>
          <div class="rate-card__rest text-small">
            <?php echo $stoymost_taryfa['tekst']; ?>
          </div>
        </div>
        <div class="rate-card__button">
          <button
            class="button button_filled button_rounded button_centered button_block">
            <span class="button__text"><?php echo $stoymost_taryfa['knopka']; ?></span>
          </button>
        </div>
      </div>

      <div class="rate-card rate-card_opened rate-card_primary" data-rate-card> <img class="rate-card_primary-decor-1"
          src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/rates/active-rate-card-decor-1.png"> <img class="rate-card_primary-decor-2"
          src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/rates/active-rate-card-decor-2.png">
        <div class="rate-card__header" data-rate-card-toggler>
          <h3 class="rate-card__title h3">Pro</h3>
          <div class="rate-card__toggler"></div>
        </div>
        <div class="rate-card__conditions">
          <?php if ($punkt_taryfa_pro): ?>
            <?php $counter = 1; ?>
            <?php foreach ($punkt_taryfa_pro as $item): ?>
              <div class="rate-card__condition<?php echo !$item['punkt']['neaktyvnj'] ? ' rate-card__condition_locked' : ''; ?>">
                <div class="rate-card__condition-number text"><?php echo str_pad($counter, 2, '0', STR_PAD_LEFT); ?></div>
                <div class="rate-card__condition-content text"><?php echo esc_html($item['punkt']['zagolovok']); ?></div>
              </div>
              <?php $counter++; ?>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <div class="rate-card__data">
          <div class="rate-card__price h6"> <?php echo $stoymost_taryfa_pro['czena']; ?> </div>
          <div class="rate-card__rest text-small">
            <?php echo $stoymost_taryfa_pro['tekst']; ?>
          </div>
        </div>
        <div class="rate-card__button">
          <button
            class="button button_filled button_rounded button_centered button_block">
            <span class="button__text"><?php echo $stoymost_taryfa_pro['knopka']; ?></span>
          </button>
        </div>
      </div>

      <div class="rate-card rate-card_opened" data-rate-card>
        <div class="rate-card__header" data-rate-card-toggler>
          <h3 class="rate-card__title h3">Premium</h3>
          <div class="rate-card__toggler"></div>
        </div>
        <div class="rate-card__conditions">
          <?php if ($punkt_taryfa_premium): ?>
            <?php $counter = 1; ?>
            <?php foreach ($punkt_taryfa_premium as $item): ?>
              <div class="rate-card__condition<?php echo !$item['punkt']['neaktyvnj'] ? ' rate-card__condition_locked' : ''; ?>">
                <div class="rate-card__condition-number text"><?php echo str_pad($counter, 2, '0', STR_PAD_LEFT); ?></div>
                <div class="rate-card__condition-content text"><?php echo esc_html($item['punkt']['zagolovok']); ?></div>
              </div>
              <?php $counter++; ?>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <div class="rate-card__data">
          <div class="rate-card__price h6"> <?php echo $stoymost_taryfa_premium['czena']; ?> </div>
          <div class="rate-card__rest text-small">
            <?php echo $stoymost_taryfa_premium['tekst']; ?>
          </div>
        </div>
        <div class="rate-card__button">
          <button
            class="button button_filled button_rounded button_centered button_block">
            <span class="button__text"><?php echo $stoymost_taryfa_premium['knopka']; ?></span>
          </button>
        </div>
      </div>
    </div>
    <?php get_template_part('template-parts/widgets/faq'); ?>
  </div>
</main>