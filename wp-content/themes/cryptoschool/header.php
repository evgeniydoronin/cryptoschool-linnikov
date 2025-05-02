<?php
/**
 * Шаблон заголовка темы
 *
 * @package CryptoSchool
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> data-mode="production">

<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="description" content="Крипто школа">
	<meta name="viewport" content="width=device-width">
	<link rel="icon" type="image/x-icon" href="<?php echo get_template_directory_uri(); ?>/frontend-source/dist/favicon.svg">
	<meta name="app-mode" content="production">
	<style>
		/* @import url(https://fonts.googleapis.com/css?family=Inter+Tight:regular,500,600,700,500italic&display=swap); */
	</style>
	<?php wp_head(); ?>
	<title><?php wp_title('|', true, 'right'); ?><?php bloginfo('name'); ?></title>
</head>

<body <?php body_class('tg-regular'); ?>>
<?php wp_body_open(); ?>

<?php get_template_part('template-parts/header/site-header'); ?>
