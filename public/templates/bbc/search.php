<?php get_header('search');?>

<div id="wpma-template" class="md-content">
	<div>
		<?php
			if (have_posts()) {
			  // Get a template part as a Vue Component
			  get_template_part('component/wpma-list-post');
			} else {
			  get_template_part('no-results', 'search');
			}
		?>
	</div>
</div>

<?php get_footer();?>