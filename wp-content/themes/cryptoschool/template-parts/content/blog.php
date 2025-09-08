<main>
  <div class="page-background">
    <div class="ratio-wrap page-background__wrap"> <img src="assets/img/decor-light.svg" alt="Page decor"
        class="ratio-wrap__item page-background__img_light"> <img src="assets/img/decor-dark.svg" alt="Page decor"
        class="ratio-wrap__item page-background__img_dark"> </div>
  </div>
  <div class="container container_wide blog__container">
    <div class="breadcrumbs blog__breadcrumbs">
      <?php echo cryptoschool_get_archive_breadcrumbs(); ?>
    </div>
    <h4 class="h4 color-primary blog__title">Наш блог</h4>
    <div class="chips blog__chips">
      <?php echo cryptoschool_render_all_categories(); ?>
    </div>
    <?php 
    // Получаем данные постов для архива
    $posts_data = cryptoschool_render_archive_posts(12);
    ?>
    <div class="blog__articles">
      <?php echo $posts_data['html']; ?>
    </div>
    <?php echo cryptoschool_render_pagination($posts_data['query']); ?>
  </div>
</main>