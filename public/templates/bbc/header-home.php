<?php get_header(); ?>

<!-- Start: header app -->
<header class="header bbc-header-color">
	<div class="header__top">
		<div class="header__logo"><a href="<?php echo esc_url(home_url('/')); ?>" title="<?php bloginfo('description'); ?>" rel="home"><?php bloginfo('name'); ?></a></div>
		<div class="header__iconsearch"><a rel="search" href="/?s"></a></div>
	</div>
	<?php wp_nav_menu([
		'theme_location' => 'header_menu',
		'menu_class' => 'header__menu',
		'container' => 'nav'
	]) ?>
</header>
<!-- End: header app -->