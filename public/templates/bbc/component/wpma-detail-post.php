<?php

// Filter of Posts
$iNumberPost = 1;
$aFilter     = [
  'allowFormCaching' => 1,
  'cache_image'      => 0,
  'cache_permalink'  => 0,
  'images'          => ['feature_post'],
  'postid'          => get_the_ID(),
];

// Create WPMA API url
global $WPMA;
$sJsonPost = $WPMA->api->createWPMAUrl('post', 'json', $aFilter);
?>

<!-- Start: component detail post -->
<wpma-detail-post amount="<?php echo $iNumberPost; ?>" class="wpma-el" name="postdetail" url="<?php echo $sJsonPost ?>" inline-template>
	<div>
		<!-- component loading from php (bad for loop) -->
    <?php
      // Get a template part as a Vue Component
      get_template_part('component/wpma-loading-post-detail');
    ?>
	  <div class="post-detail" id="post-<?php the_ID();?>" v-if="!isLoading">	

			<div class="post-detail__media" v-if="postdetail.images" v-bind:style="{'background-image': 'url('+postdetail.images.feature_post+')'}"></div>

			<div class="post-detail__content">
				<h1 class="post-detail__title">{{postdetail.post_title}}</h1>
				<div class="post-detail__info">
					<span v-if="postdetail.time_ago">
	          {{postdetail.time_ago}} ago
	        </span>
	        <span class="post-detail__cat" v-for="cat in postdetail.category" v-if="postdetail.category">
	          <a v-bind:href="cat.permalink">
	            {{cat.name}}
	          </a>
	        </span>
	      </div>
				<div class="post-detail__desc">
					<h2 class="title">{{postdetail.post_excerpt}}</h2>
					{{postdetail.post_content}}				
				</div>

				<!-- Wait for wpma-detail-posts rendered then render wpma-related-post. Use v-if isLoading === false -->
	    	<div v-if="!isLoading">
					<?php
						// Get a template part as a Vue Component
						get_template_part('component/wpma-related-post');
					?>
				</div>
			</div>
	  </div>
	</div>
</wpma-detail-post>
<!-- End: component detail post -->