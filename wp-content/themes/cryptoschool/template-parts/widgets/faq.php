<div class="faq">
  <div class="faq__title h4 color-primary">Відповіді на запитання</div>
  <div class="faq__content">
    <?php
    $faq_query = new WP_Query(array(
      'post_type' => 'faq',
      'posts_per_page' => -1,
      'orderby' => 'menu_order',
      'order' => 'ASC'
    ));

    if ($faq_query->have_posts()):
      while ($faq_query->have_posts()): $faq_query->the_post();
    ?>
        <div class="palette palette_blurred faq-item" data-faq-item>
          <div class="faq-item__header" data-faq-item-toggler>
            <div class="text color-primary"><?php the_title(); ?></div>
            <div class="faq-item__toggler"></div>
          </div>
          <div class="faq-item__content">
            <div class="text-small">
              <?php the_content(); ?>
            </div>
          </div>
        </div>
    <?php
      endwhile;
      wp_reset_postdata();
    endif;
    ?>
  </div>
</div>