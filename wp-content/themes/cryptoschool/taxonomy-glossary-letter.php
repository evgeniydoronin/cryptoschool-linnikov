<?php get_header(); 
$current_term = get_queried_object(); ?>

<main>
  <div class="page-background">
    <div class="ratio-wrap page-background__wrap"> <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/decor-light.svg" alt="Page decor"
        class="ratio-wrap__item page-background__img_light"> <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/decor-dark.svg" alt="Page decor"
        class="ratio-wrap__item page-background__img_dark"> </div>
  </div>
  <div class="container categories__container">
    <div class="breadcrumbs categories__breadcrumbs"> 
      <?php echo cryptoschool_get_glossary_breadcrumbs(); ?>
    </div>
    <h4 class="h4 color-primary categories__title"></h4>
    <div class="palette palette_blurred categories-list">
      <div class="categories-list__header">
        <div class="palette palette_blurred categories-list__search"> <span class="icon-search"></span> <input
            type="text" class="text" placeholder="Search..."> </div>
        <?php get_template_part('template-parts/widgets/glossary-alphabet-links'); ?>
      </div>
      <div class="categories-list__content" data-page-type="taxonomy" data-current-term="<?php echo $current_term ? $current_term->term_id : 0; ?>">
        <?php
        if ($current_term && !is_wp_error($current_term)):
          // Получаем посты для текущего термина
          $posts_query = new WP_Query(array(
            'post_type' => 'glossary',
            'tax_query' => array(
              array(
                'taxonomy' => 'glossary-letter',
                'field' => 'term_id',
                'terms' => $current_term->term_id
              )
            ),
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
          ));
          
          if ($posts_query->have_posts()):
        ?>
          <div class="categories-list__section">
            <div class="categories-list__section-letter h1"><?php echo esc_html(strtoupper($current_term->name)); ?></div>
            <div class="categories-list__section-content">
              <?php while ($posts_query->have_posts()): $posts_query->the_post(); ?>
                <div class="categories-list__section-row">
                  <h4 class="categories-list__section-row-title h4">
                    <a href="<?php the_permalink(); ?>">
                      <?php the_title(); ?>
                    </a>
                  </h4>
                  <div class="categories-list__section-row-text text">
                    <?php 
                    $excerpt = get_the_excerpt();
                    if (empty($excerpt)) {
                      $excerpt = wp_trim_words(get_the_content(), 30, '...');
                    }
                    echo esc_html($excerpt);
                    ?>
                  </div>
                </div>
              <?php endwhile; ?>
            </div>
          </div>
        <?php 
          endif;
          wp_reset_postdata();
        endif;
        ?>
      </div>
    </div>
  </div>
</main>

<?php get_footer();
