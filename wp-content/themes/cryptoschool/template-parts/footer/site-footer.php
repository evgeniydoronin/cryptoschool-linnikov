<?php
/**
 * Шаблон подвала сайта
 *
 * @package CryptoSchool
 */
?>
<footer class="footer section-space">
  <div class="container container_wide footer__container">
    <div class="footer__copyright text-small">All rights reserved &copy; <span
        class="hide-tablet hide-mobile">KryptoSchool</span> <?php echo date('Y'); ?>.</div>
    <div class="footer__payment"> <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/footer/mono.svg" class="footer__payment-item"> <img
        src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/footer/apple-pay.svg" class="footer__payment-item"> <img
        src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/footer/mastercard.svg" class="footer__payment-item"> <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/footer/visa.svg"
        class="footer__payment-item"> <img src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/footer/bitcoin.svg" class="footer__payment-item"> <img
        src="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/assets/img/footer/ethereum.svg" class="footer__payment-item"> </div>
    <div class="scroll-up footer__scroll-up">
      <div class="scroll-up__text text-small">На верх</div>
      <div class="scroll-up__icon"> <span class="icon-dropdown-arrow"></span> </div>
    </div>
  </div>
</footer>
