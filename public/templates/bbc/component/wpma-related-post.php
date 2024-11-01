<?php
global $WPMA;

// Filter of Posts
$iNumberPost = $WPMA->setting->getOption('BBC_POST_NUMBER_RELATED');

$aFilter     = [
  'allowFormCaching' => 1,
  'cache_image'      => 0,
  'cache_permalink'  => 0,
  'images'          => ['thumbnail'],
  'postid'          => get_the_ID(),
  'posts_per_page'  => $iNumberPost,
  'orderby'         => 'tag',
];

// Create WPMA API url
$sJsonPost = $WPMA->api->createWPMAUrl('related', 'json', $aFilter);
?>

<!-- Start: component related post -->
<wpma-related-post class="wpma-el" name="posts" amount="<?php echo $iNumberPost; ?>" url="<?php echo $sJsonPost; ?>" inline-template>
  <div>
    <div class="title" v-if="posts.length">
    	<hr/>
      <h3 class="title__title"><?php _e('Related stories', 'bbc');?></h3>
    </div>
    <wpma-loading-related v-if="isLoading"></wpma-loading-related>
    <template v-for="post in posts" v-if="posts.length">
	    <div class="post-01 post-01__style-02">
	    	<div class="post-01__media" v-if="post.images" v-bind:style="{'background-image': 'url('+post.images.thumbnail+')'}"></div>

				<div class="post-01__body">
					<h3 class="post-01__title"><a v-bind:href="post.permalink">{{post.post_title}}</a></h3>
					<div class="post-01__info">
						<span v-if="post.time_ago">
	            {{post.time_ago}} ago
	          </span>
						<span class="post-01__cat" v-for="cat in post.category" v-if="post.category">
	            <a v-bind:href="cat.permalink">
	              {{cat.name}}
	            </a>
	          </span>
					</div>
				</div>
			</div>
    </template>
	</div>
</wpma-related-post>
<!-- End: component related post -->