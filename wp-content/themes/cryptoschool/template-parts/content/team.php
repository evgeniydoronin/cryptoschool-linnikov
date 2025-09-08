<?php
$team_members = get_field('nasha_komanda');
if ($team_members): ?>
<main>
  <div class="page-background">
    <div class="ratio-wrap page-background__wrap"> 
      <img src="<?php echo get_template_directory_uri(); ?>/assets/img/decor-light.svg" alt="Page decor" class="ratio-wrap__item page-background__img_light"> 
      <img src="<?php echo get_template_directory_uri(); ?>/assets/img/decor-dark.svg" alt="Page decor" class="ratio-wrap__item page-background__img_dark"> 
    </div>
  </div>
  <div class="container container_wide team__container">
    <div class="breadcrumbs team__breadcrumbs"> 
      <?php echo cryptoschool_get_page_breadcrumbs(); ?>
    </div>
    
    <?php 
    $first_member = $team_members[0];
    $photo = $first_member['foto'];
    ?>
    <div class="account-block palette palette_blurred team-member team-member_expanded team__expanded-member">
      <div class="team-member__image"> 
        <?php if ($photo): ?>
          <img src="<?php echo esc_url($photo['url']); ?>" alt="<?php echo esc_attr($photo['alt'] ?: $first_member['ymya_famylyya']); ?>">
        <?php endif; ?>
      </div>
      <div class="team-member__body">
        <div class="team-member__headline">
          <h5 class="team-member__title text"><?php echo esc_html($first_member['ymya_famylyya']); ?></h5>
          <h6 class="team-member__subtitle text-small"><?php echo esc_html($first_member['dolzhnost']); ?></h6>
        </div>
        <hr class="team-member__separator">
        <div class="team-member__contacts"> 
          <?php if ($first_member['sslka_na_telegram']): ?>
            <a href="<?php echo esc_url($first_member['sslka_na_telegram']); ?>" class="team-member__contact social-media-link" target="_blank"> 
              <span class="icon-telegram"></span> 
            </a> 
          <?php endif; ?>
          <?php if ($first_member['sslka_na_instagram']): ?>
            <a href="<?php echo esc_url($first_member['sslka_na_instagram']); ?>" class="team-member__contact social-media-link" target="_blank"> 
              <span class="icon-instagram"></span> 
            </a> 
          <?php endif; ?>
          <?php if ($first_member['sslka_na_youtube']): ?>
            <a href="<?php echo esc_url($first_member['sslka_na_youtube']); ?>" class="team-member__contact social-media-link" target="_blank"> 
              <span class="icon-youtube-filled"></span> 
            </a> 
          <?php endif; ?>
          <?php if ($first_member['sslka_na_twitter']): ?>
            <a href="<?php echo esc_url($first_member['sslka_na_twitter']); ?>" class="team-member__contact social-media-link" target="_blank"> 
              <span class="icon-twitter"></span> 
            </a> 
          <?php endif; ?>
        </div>
        <?php if ($first_member['email']): ?>
          <hr class="team-member__separator"> 
          <a href="mailto:<?php echo esc_attr($first_member['email']); ?>" class="team-member__mail text-small"><?php echo esc_html($first_member['email']); ?></a>
        <?php endif; ?>
      </div>
    </div>
    
    <h4 class="h4 color-primary team__title">Наша команда</h4>
    <div class="team__cards">
      <?php 
      foreach (array_slice($team_members, 1) as $member): 
        $photo = $member['foto'];
      ?>
      <div class="account-block palette palette_blurred team-member">
        <div class="team-member__image"> 
          <?php if ($photo): ?>
            <img src="<?php echo esc_url($photo['url']); ?>" alt="<?php echo esc_attr($photo['alt'] ?: $member['ymya_famylyya']); ?>">
          <?php endif; ?>
        </div>
        <div class="team-member__body">
          <div class="team-member__headline">
            <h5 class="team-member__title text"><?php echo esc_html($member['ymya_famylyya']); ?></h5>
            <h6 class="team-member__subtitle text-small"><?php echo esc_html($member['dolzhnost']); ?></h6>
          </div>
          <hr class="team-member__separator">
          <div class="team-member__contacts"> 
            <?php if ($member['sslka_na_telegram']): ?>
              <a href="<?php echo esc_url($member['sslka_na_telegram']); ?>" class="team-member__contact social-media-link" target="_blank"> 
                <span class="icon-telegram"></span> 
              </a> 
            <?php endif; ?>
            <?php if ($member['sslka_na_instagram']): ?>
              <a href="<?php echo esc_url($member['sslka_na_instagram']); ?>" class="team-member__contact social-media-link" target="_blank"> 
                <span class="icon-instagram"></span> 
              </a> 
            <?php endif; ?>
            <?php if ($member['sslka_na_youtube']): ?>
              <a href="<?php echo esc_url($member['sslka_na_youtube']); ?>" class="team-member__contact social-media-link" target="_blank"> 
                <span class="icon-youtube-filled"></span> 
              </a> 
            <?php endif; ?>
            <?php if ($member['sslka_na_twitter']): ?>
              <a href="<?php echo esc_url($member['sslka_na_twitter']); ?>" class="team-member__contact social-media-link" target="_blank"> 
                <span class="icon-twitter"></span> 
              </a> 
            <?php endif; ?>
          </div>
          <?php if ($member['email']): ?>
            <hr class="team-member__separator"> 
            <a href="mailto:<?php echo esc_attr($member['email']); ?>" class="team-member__mail text-small"><?php echo esc_html($member['email']); ?></a>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</main>
<?php endif; ?>