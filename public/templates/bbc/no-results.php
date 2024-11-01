<?php if (is_home() && current_user_can('publish_posts')) { ?> 
	<h6 class="md-text-center"><?php printf(__('Ready to publish your first post? <a href="%1$s">Get started here</a>.', 'bbc'), esc_url(admin_url('post-new.php'))); ?></h6>
<?php } elseif (is_search()) { ?> 
	<h6 class="md-text-center"><?php _e('Sorry, but nothing matched your search terms. Please try again with some different keywords.', 'bbc'); ?></h6>
<?php } else { ?> 
	<h6 class="md-text-center"><?php _e('It seems we can&rsquo;t find what you&rsquo;re looking for. Perhaps searching can help.', 'bbc'); ?></h6>
<?php } ?> 