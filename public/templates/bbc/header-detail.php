<?php get_header(); ?>

<!-- Start: breadcrumb -->
<div class="form-search bbc-header-color">
	<div class="breadcrumb">
		<a class="breadcrumb__prev" href="<?php echo esc_url(home_url('/')); ?>"></a>
		<h4 class="breadcrumb__title"><?php echo get_the_title()?></h4>
		<div class="breadcrumb__icon">

			<a class="btn-share wpma-share-link"
			data-title="<?php echo get_the_title()?>"    	
    	data-text="Test"
    	data-url="<?php echo get_permalink()?>"></a>
		</div>
	</div>
	<?php wp_nav_menu([
		'theme_location' => 'header_menu',
		'menu_class' => 'header__menu',
		'container' => 'nav'
	]) ?>
</div>
<!-- End: breadcrumb -->