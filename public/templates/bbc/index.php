<?php get_header('home'); ?>

<div id="wpma-template" class="md-content">
	<?php
		// Get a template part as a Vue Component
		get_template_part('component/wpma-list-post');
	?>
</div>

<?php get_footer(); ?>