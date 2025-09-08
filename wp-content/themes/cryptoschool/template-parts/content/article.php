<main>
  <div class="page-background">
    <div class="ratio-wrap page-background__wrap"> <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/decor-light.svg" alt="Page decor"
        class="ratio-wrap__item page-background__img_light"> <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/decor-dark.svg" alt="Page decor"
        class="ratio-wrap__item page-background__img_dark"> </div>
  </div>
  <div class="container container_wide blog-article-page__container">
    <div class="breadcrumbs blog-article-page__breadcrumbs">
      <?php echo cryptoschool_get_breadcrumbs(); ?>
    </div>
    <div
      class="blog-article-layout palette palette_blurred palette_hide-tablet palette_hide-mobile blog-article-page__layout">
      <div class="blog-article-layout__center palette palette_hide-desktop">
        <div class="blog-article-layout__illustration">
          <?php if (has_post_thumbnail()) : ?> 
            <?php the_post_thumbnail('large', ['alt' => get_the_title()]); ?>
          <?php else : ?>
            <img src="<?php echo get_template_directory_uri(); ?>/assets/img/temp/blog-article-layout-placeholder.png" alt="<?php echo esc_attr(get_the_title()); ?>">
          <?php endif; ?>
        </div>
        <h4 class="h4 color-primary blog-article-layout__title">
          <?php echo get_the_title(); ?>?
        </h4>
        <div class="blog-article-info">
          <div class="blog-article-info__header">
            <div class="text color-primary">Редакторска група</div>
            <div class="blog-article-info__details">
              <div class="blog-article-info__detail"> <span class="icon-calender"></span>
                <div class="text-small"><?php echo get_the_date('d.m.Y'); ?></div>
              </div>
              <div class="blog-article-info__detail"> <span class="icon-clock"></span>
                <div class="text-small"><?php echo cryptoschool_get_reading_time(); ?></div>
              </div>
            </div>
          </div>
          <div class="blog-article-info__content">
            <?php if( have_rows('redaktorska_grupa') ): ?>
              <?php while( have_rows('redaktorska_grupa') ): the_row(); 
                $author_name = get_sub_field('fyo');
                $author_role = get_sub_field('kto');
                $author_photo = get_sub_field('foto');
              ?>
                <div class="blog-article-info__author">
                  <div class="blog-article-info__author-avatar">
                    <?php if( $author_photo ): ?>
                      <?php echo wp_get_attachment_image($author_photo['ID'], 'thumbnail', false, ['alt' => esc_attr($author_name)]); ?>
                    <?php else: ?>
                      <img src="<?php echo get_template_directory_uri(); ?>/assets/img/temp/blog-article-author-placeholder.png" alt="<?php echo esc_attr($author_name); ?>">
                    <?php endif; ?>
                  </div>
                  <div class="blog-article-info__author-data">
                    <div class="text color-primary"><?php echo esc_html($author_name); ?></div>
                    <div class="text-small blog-article-info__author-subtitle"><?php echo esc_html($author_role); ?></div>
                  </div>
                </div>
              <?php endwhile; ?>
            <?php else: ?>
              <!-- Fallback: показываем автора поста если репитер пустой -->
              <div class="blog-article-info__author">
                <div class="blog-article-info__author-avatar">
                  <img src="<?php echo get_template_directory_uri(); ?>/assets/img/temp/blog-article-author-placeholder.png" alt="">
                </div>
                <div class="blog-article-info__author-data">
                  <div class="text color-primary">Редакторська група</div>
                  <div class="text-small blog-article-info__author-subtitle">Автор</div>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <div class="account-article-content blog-article-layout__content">
          <?php the_content(); ?>
        </div>
        <div class="blog-article-layout__content-share">
          <h6 class="h6 color-primary">Поділитися</h6>
          <div class="blog-article-layout__content-share-links">
            <?php echo cryptoschool_render_share_links(); ?>
          </div>
        </div>
        <?php cryptoschool_render_comments_section(); ?>
      </div>
      <div class="blog-article-layout__sidebar">
        <div class="blog-article-layout__contents palette">
          <h6 class="text color-primary">Содержание</h6>
          <?php echo get_field('soderzhanye_posta'); ?>
        </div>
        <div class="blog-article-layout__categories">
          <?php echo cryptoschool_render_all_categories(); ?>
        </div>
        <div class="blog-article-layout__share palette">
          <h6 class="text color-primary">Поділитися</h6>
          <div class="blog-article-layout__share-links">
            <?php echo str_replace('blog-article-layout__content-share-link', 'blog-article-layout__share-link', cryptoschool_render_share_links()); ?>
          </div>
        </div>
      </div>
    </div>
    <div class="blog-article-page__recommendations-header">
      <h4 class="h4 color-primary blog-article-page__recommendations-title">Наш блог</h4>
      <div class="blog-article-page__recommendations-slider-controls">
        <div class="slider-control slider-control-left" data-slider-for="recommendations-slider"
          data-slider-control-left> <span class="icon-nav-arrow-left"></span> </div>
        <div class="slider-control slider-control-right" data-slider-for="recommendations-slider"
          data-slider-control-right> <span class="icon-nav-arrow-right"></span> </div>
      </div>
    </div>
    <?php echo cryptoschool_render_recommendations_slider(4); ?>
  </div>
</main>