<?php
global $WPMA;

// Filter of Posts
$iNumberPost = $WPMA->setting->getOption('BBC_POST_NUMBER_PAGE');

$aFilter     = [
  'allowFormCaching' => 0,
  'cache_image'      => 0,
  'cache_permalink'  => 0,
  'images'           => ['feature_post', 'medium_large', 'thumbnail'],
];

$oCurrentQueried = get_queried_object();

// Check type page
if (is_search()) {

  // Add filter for search page
  $iNumberPost  = $WPMA->setting->getOption('BBC_POST_NUMBER_SEARCH');;
  $aFilter['s'] = get_search_query();

} else if (!empty($oCurrentQueried->taxonomy)) {

  // Add filter by taxonomy page
  $aFilter[$oCurrentQueried->taxonomy] = $oCurrentQueried->term_id;

  $sTitle = $oCurrentQueried->name;

} elseif (is_author()) {

  // Add filter by taxonomy page
  $aFilter['author'] = $oCurrentQueried->ID;

  $sTitle = sprintf(__('Posted by: %s', 'bbc'), '<h3>' . get_the_author() . '</h3>');

} elseif (is_day()) {
  $sTitle = sprintf(__('Day: %s', 'bbc'), '<h3>' . get_the_date() . '</h3>');
} elseif (is_month()) {
  $sTitle = sprintf(__('Month: %s', 'bbc'), '<h3>' . get_the_date('F Y') . '</h3>');
} elseif (is_year()) {
  $sTitle = sprintf(__('Year: %s', 'bbc'), '<h3>' . get_the_date('Y') . '</h3>');
} elseif (is_tax('post_format', 'post-format-aside')) {
  $sTitle = __('Asides', 'bbc');
} elseif (is_tax('post_format', 'post-format-image')) {
  $sTitle = __('Images', 'bbc');
} elseif (is_tax('post_format', 'post-format-video')) {
  $sTitle = __('Videos', 'bbc');
} elseif (is_tax('post_format', 'post-format-quote')) {
  $sTitle = __('Quotes', 'bbc');
} elseif (is_tax('post_format', 'post-format-link')) {
  $sTitle = __('Links', 'bbc');
}

// Title
if ($sTitle) {
  echo '<h5 class="archive__title">' . $sTitle . '</h5>';
}

// Filter of Posts
$aFilter['posts_per_page'] = $iNumberPost;

// Create WPMA API url
$sJsonPost = $WPMA->api->createWPMAUrl('posts', 'json', $aFilter);

?>

<!-- Start: component list post -->
<wpma-list-post class="wpma-el" amount="<?php echo $iNumberPost; ?>" name="posts" url="<?php echo $sJsonPost; ?>" inline-template>
  <div class="post-02 mb-0">
  	<template v-if="posts.length" v-for="(post, index) in posts">

	    <wpma-loading-feature v-if="isLoading"></wpma-loading-feature>

	    <a v-bind:href="post.permalink"><div class="post-02__media" v-if="post.images" v-bind:style="{'background-image': 'url('+post.images.feature_post+')'}"></div></a>

	    <div class="post-02__body" v-bind:href="post.permalink">
	      <h3 class="post-02__title">
	        <a v-bind:href="post.permalink">
	          {{post.post_title}}
	        </a>
	      </h3>
	      <div class="post-02__info">
	        <span v-if="post.time_ago">
	          {{post.time_ago}} ago
	        </span>
	        <span class="post-02__cat" v-for="cat in post.category" v-if="post.category">
	          <a v-bind:href="cat.permalink">
	            {{cat.name}}
	          </a>
	        </span>
	      </div>
	    </div>
	  </template>

	  <!-- component load more -->
	  <wpma-load-more :current-datas="posts" :current-url="url" :amount="amount"></wpma-load-more>
  </div>
</wpma-list-post>
<!-- End: component list post -->