<?php get_header(); ?>

<!-- Start: form search -->
<div class="form-search bbc-header-color">
	<form class="form-search__form" action="/" method="get">
		<a class="form-search__prev" href="<?php echo esc_url(home_url('/')); ?>"></a>
    <input type="text" name="s" id="search" placeholder="<?php _e('Search topics and articles', 'bbc') ?>" value="<?php the_search_query(); ?>" />
	</form>
	<?php wp_nav_menu([
		'theme_location' => 'header_menu',
		'menu_class' => 'header__menu',
		'container' => 'nav'
	]) ?>
</div>
<!-- End: form search -->