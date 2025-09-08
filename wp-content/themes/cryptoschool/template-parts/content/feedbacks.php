<?php
$feedbacks_top = get_field('otzv_verh');
$feedbacks_bottom = get_field('otzv_nyz');
$levaya_kolonka = get_field('levaya_kolonka');
$czentralnaya_kolonka = get_field('czentralnaya_kolonka');
$pravaya_kolonka = get_field('pravaya_kolonka');
?>
<main>
  <div class="page-background">
    <div class="ratio-wrap page-background__wrap"> 
      <img src="<?php echo get_template_directory_uri(); ?>/assets/img/decor-light.svg" alt="Page decor" class="ratio-wrap__item page-background__img_light"> 
      <img src="<?php echo get_template_directory_uri(); ?>/assets/img/decor-dark.svg" alt="Page decor" class="ratio-wrap__item page-background__img_dark"> 
    </div>
  </div>
  <div class="container container_wide feedbacks__container">
    <div class="breadcrumbs feedbacks__breadcrumbs"> 
      <?php echo cryptoschool_get_page_breadcrumbs(); ?>
    </div>
    <h4 class="h4 color-primary feedbacks__title">Відгуки наших студентів</h4>
  </div>
  <div class="feedbacks__block">
    <div class="feedbacks__slider" data-feedbacks-slider data-direction="left">
      <div class="feedbacks__list">
        <?php if ($feedbacks_top): ?>
          <?php foreach ($feedbacks_top as $feedback): 
            $photo = $feedback['foto'];
          ?>
          <div class="palette palette_blurred feedback-palette">
            <div class="feedback-palette__header">
              <div class="feedback-palette__avatar"> 
                <?php if ($photo): ?>
                  <img src="<?php echo esc_url($photo['url']); ?>" alt="<?php echo esc_attr($photo['alt'] ?: $feedback['fyo']); ?>">
                <?php else: ?>
                  <img src="<?php echo get_template_directory_uri(); ?>/assets/img/temp/feedback-palette-avatar.png" alt="<?php echo esc_attr($feedback['fyo']); ?>">
                <?php endif; ?>
              </div>
              <div class="feedback-palette__author text"><?php echo esc_html($feedback['fyo']); ?></div>
            </div>
            <div class="feedback-palette__content text-small">
              <?php echo esc_html($feedback['tekst_otzva']); ?>
            </div>
            <div class="feedback-palette__footer">
              <div class="feedback-palette__date text-small"><?php echo esc_html($feedback['data_otzva']); ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
    <div class="feedbacks__slider" data-feedbacks-slider data-direction="right">
      <div class="feedbacks__list">
        <?php if ($feedbacks_bottom): ?>
          <?php foreach ($feedbacks_bottom as $feedback): 
            $photo = $feedback['foto'];
          ?>
          <div class="palette palette_blurred feedback-palette">
            <div class="feedback-palette__header">
              <div class="feedback-palette__avatar"> 
                <?php if ($photo): ?>
                  <img src="<?php echo esc_url($photo['url']); ?>" alt="<?php echo esc_attr($photo['alt'] ?: $feedback['fyo']); ?>">
                <?php else: ?>
                  <img src="<?php echo get_template_directory_uri(); ?>/assets/img/temp/feedback-palette-avatar.png" alt="<?php echo esc_attr($feedback['fyo']); ?>">
                <?php endif; ?>
              </div>
              <div class="feedback-palette__author text"><?php echo esc_html($feedback['fyo']); ?></div>
            </div>
            <div class="feedback-palette__content text-small">
              <?php echo esc_html($feedback['tekst_otzva']); ?>
            </div>
            <div class="feedback-palette__footer">
              <div class="feedback-palette__date text-small"><?php echo esc_html($feedback['data_otzva']); ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
    <div class="feedbacks__expand"> <button class="button button_filled button_rounded"> <span
          class="button__text">Усі відгуки</span> </button> </div>
  </div>
  <div class="container container_wide feedbacks__container">
    <div class="feedbacks-results palette palette_blurred feedbacks__results">
      <div class="feedbacks-results__title text color-primary"> <span class="icon-fire hide-mobile"></span> <span
          class="icon-thunder hide-desktop hide-tablet"></span>
        <div><?php echo get_field('zagolovok_otzv'); ?></div>
      </div>
      <div class="feedbacks-results__content">
        <div class="feedbacks-results__item">
          <div class="feedbacks-results__item-title h4 color-primary"><?php echo esc_html($levaya_kolonka['chyslo']); ?></div>
          <div class="feedbacks-results__item-description text-small"><?php echo esc_html($levaya_kolonka['tekst']); ?></div>
        </div>
        <div class="feedbacks-results__item">
          <div class="feedbacks-results__item-title h4 color-primary"><?php echo esc_html($czentralnaya_kolonka['chyslo']); ?></div>
          <div class="feedbacks-results__item-description text-small"><?php echo esc_html($czentralnaya_kolonka['tekst']); ?></div>
        </div>
        <div class="feedbacks-results__item">
          <div class="feedbacks-results__item-title h4 color-primary"><?php echo esc_html($pravaya_kolonka['chyslo']); ?></div>
          <div class="feedbacks-results__item-description text-small"><?php echo esc_html($pravaya_kolonka['tekst']); ?></div>
        </div>
      </div>
    </div>
  </div>
</main>