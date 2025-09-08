<?php get_header(); ?>

<main>
  <div class="page-background">
    <div class="ratio-wrap page-background__wrap"> <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/decor-light.svg" alt="Page decor"
        class="ratio-wrap__item page-background__img_light"> <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/decor-dark.svg" alt="Page decor"
        class="ratio-wrap__item page-background__img_dark"> </div>
  </div>
  <div class="container container_small glossary-article__container">
    <div class="breadcrumbs glossary-article__breadcrumbs"> 
      <?php echo cryptoschool_get_glossary_breadcrumbs(); ?>
    </div>
    <div class="account-block palette palette_blurred">
      <div class="glossary-article__header">
        <h4 class="h4 color-primary"><?php echo get_the_title(); ?></h4>
        <div class="glossary-article__info">
          <div class="glossary-article__level text-small">
            <?php echo get_field('glossary_slozhnost'); ?>
          </div>
          <div class="glossary-article__contacts">
            <?php echo cryptoschool_render_share_links(); ?>
          </div>
        </div>
      </div>
      <div class="account-article-content">
        <?php echo get_the_content(); ?>
      </div>
    </div>
  </div>
</main>

<?php get_footer();
