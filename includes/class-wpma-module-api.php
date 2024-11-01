<?php
// Blocking access direct to the plugin
defined('WPMA_BASE') or die('No script kiddies please!');

use Nette\Caching\Cache;

class WPMA_API {
  public $filter, $version;

  ///////////// Call WPMACore /////////////
  public function __construct(&$WPMA) {
    $this->WPMA = &$WPMA;

    $this->version = 'v1';

    // WPMA API URL
    define('WPMA_URL_API', get_home_url() . '/wpma-api/' . $this->version . '/');

    $sVersion = 'v2';

    // WOOCOMMERCE API URL
    define('WC_URL_API', get_home_url() . '/wp-json/wc/' . $sVersion . '/');

    // WPMA API Filter
    $this->filter = [
      // Get post image by size
      'images'           => [
        'thumbnail',
        'medium',
        'medium_large',
        'large',
        'custom' => [
          //width, height
          300, 300,
        ],
      ],

      // Load api from cache
      'allowFormCaching' => 1,

      // Cache all image
      'cache_image'      => 0,

      // Cache permalink
      'cache_permalink'  => 0,
    ];
  }

  ///////////// Hook /////////////
  public function createWPMAUrl($sName, $sType, $aFilter) {
    $aFilter = wp_parse_args($aFilter);

    $sURL = WPMA_URL_API . $sType . '/' . $sName . '?' . build_query($aFilter);

    // Protect API lv1
    if ($this->WPMA->setting->getOption('WPMA_ENABLE_PROTECT_API') === ENABLE) {
      $sHash = md5($sName . serialize(asort($aFilter)));

      $_SESSION[$sHash] = time();
    }

    return $sURL;
  }

  // Working
  public function createWoocommerceUrl($sName, $aFilter) {
    $aFilter = wp_parse_args($aFilter);

    $sURL = WC_URL_API . '/' . $sName . '?' . build_query($aFilter);

    return $sURL;
  }

  ///////////// Parse API /////////////
  public function parseAPI($sName, $sType, $aFilter) {
    $aAPI = apply_filters('wpma_apis', []);

    $aResult = [];

    if (array_key_exists($sName, $aAPI)) {

      // Protect API lv1
      if ($this->WPMA->setting->getOption('WPMA_ENABLE_PROTECT_API') === ENABLE) {

        $sHash = md5($sName . serialize(asort($aFilter)));

        // Check session
        if (!isset($_SESSION[$sHash]) || $_SESSION['WPMA_CURRENT_DEVICE'] === 'computer') {
          $this->WPMA->render->json($aResult);
        }
      }

      // Merge WPMA API Filter with WP Filter
      $this->filter = wp_parse_args($aFilter, $this->filter);

      // Use Cache API
      $sKeyCache  = md5(serialize([$sName, $sType, $this->filter]));
      $iCacheTime = $this->WPMA->setting->getOption('WPMA_CACHE_API');

      if ($iCacheTime > 0 && $this->filter['allowFormCaching']) {
        $aResult = $this->WPMA->cache->load($sKeyCache);
      }

      // Check Result
      if (!boolval($aResult)) {
        $aResult = call_user_func($aAPI[$sName]);

        // Save a cache
        if ($iCacheTime > 0 && $this->filter['allowFormCaching']) {
          $this->WPMA->cache->save($sKeyCache, $aResult, [
            Cache::EXPIRE => $iCacheTime . ' seconds',
          ]);
        }
      }
    }

    switch ($sType) {
    case 'json':
      $this->WPMA->render->json($aResult);
      break;
    }
  }

  ///////////// Register API /////////////
  public function registerAPI($aAPI) {
    // Register api: settings
    $aAPI['settings'] = [$this, 'settingsController'];

    // Register api: post
    $aAPI['post'] = [$this, 'postController'];

    // Register api: posts
    $aAPI['posts'] = [$this, 'postsController'];

    // Register api: pages
    $aAPI['pages'] = [$this, 'pagesController'];

    // Register api: category
    $aAPI['category'] = [$this, 'categoryController'];

    // Register api: related
    $aAPI['related'] = [$this, 'relatedController'];

    return $aAPI;
  }

  ///////////// Controller /////////////
  // Controller: setting
  public function settingsController() {
    $aResult = [];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
      $aResult = $this->getWPMASettings();
    }

    return $aResult;
  }

  // Controller: post
  public function postController() {
    $aResult = [];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
      $aResult = $this->getPost();
    }

    return $aResult;
  }

  // Controller: posts
  public function postsController() {
    $aResult = [];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
      $aResult = $this->getPosts();
    }

    return $aResult;
  }

  // Controller: pages
  public function pagesController() {
    $aResult = [];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
      $aResult = $this->getPages();
    }

    return $aResult;
  }

  // Controller: category
  public function categoryController() {
    $aResult = [];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
      $aResult = $this->getCategory();
    }

    return $aResult;
  }

  // Controller: related
  public function relatedController() {
    $aResult = [];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
      $aResult = $this->getRelated();
    }

    return $aResult;
  }

  ///////////// Functions /////////////
  // Get all WPMA Setting or by name
  private function getWPMASettings() {
    $aResult = [];

    $aSettingData = $this->WPMA->setting->option;
    $aSettingID = $this->filter['name'];

    if (!empty($aSettingID)) {
      foreach ($aSettingID as $sID) {
        if(!empty($aSettingData[$sID])){
          $aData[$sID] = $aSettingData[$sID];
        }        
      }
    } else {
      $aData = $aSettingData;
    }

    $aResult = $this->formatSettings($aData);

    return $aResult;
  }

  // Get post by filter
  private function getPost() {
    $aResult = [];

    // Need a post id
    if (empty($this->filter['postid'])) {
      return $aResult;
    }

    $iPostID = intval($this->filter['postid']);
    $aData   = get_post($iPostID, ARRAY_A, $this->filter);

    $aResult = $this->formatPost([$aData]);

    return $aResult[0];
  }

  // Get posts by filter
  private function getPosts() {
    $aData = get_posts($this->filter);

    $aResult = $this->formatPost($aData);

    return $aResult;
  }

  // Get pages by filter
  private function getPages() {
    $aData = get_pages($this->filter);

    $aResult = $this->formatPage($aData);

    return $aResult;
  }

  // Get pages by filter
  private function getCategory() {
    $aData = get_categories($this->filter);

    $aResult = $this->formatCategory($aData, true);

    return $aResult;
  }

  // Get related by filter
  private function getRelated() {
    $aResult = $aQuery = [];

    // Need a post id
    if (empty($this->filter['postid']) || empty($this->filter['posts_per_page'])) {
      return $aResult;
    }

    if ($this->filter['orderby'] === 'tag') {
      $aData = wp_get_post_tags($this->filter['postid']);

      if (!boolval($aData)) {
        return $aResult;
      }

      foreach ($aData as $oTag) {
        $aTag[] = $oTag->term_id;
      }

      $aQuery = [
        'tag__in'             => $aTag,
        'post__not_in'        => [$this->filter['postid']],
        'posts_per_page'      => $this->filter['posts_per_page'],
        'ignore_sticky_posts' => true,
      ];

      if (!empty($this->filter['offset'])) {
        $aQuery['offset'] = $this->filter['offset'];
      }
    }

    $oQuery = new WP_Query($aQuery);

    if (!boolval($oQuery->have_posts())) {
      return $aResult;
    }

    $aPost = $oQuery->posts;

    $aResult = $this->formatPost($aPost);

    return $aResult;
  }

  ///////////////////////////////////
  // Structure of the WPMA Settings
  private function formatSettings($aData) {
    $aResult = [];

    if (boolval($aData)) {
      foreach ($aData as $sID => $aValue) {
        $aResult[] = [
          'name'        => $sID,
          'title'       => $aValue['title'],
          'description' => $aValue['args']['description'],
          'type'        => $aValue['type'],
          'value'       => $aValue['args']['value'],
        ];
      }
    }

    return $aResult;
  }

  // Structure of the WPMA Post
  private function formatPost($aData) {
    $aResult = [];

    if (boolval($aData)) {
      foreach ($aData as $iKey => $oPost) {
        //setup_postdata($aData);

        // Merge WPMA API Filter with WP API Result
        $aPost = array_merge($this->filter, (array) $oPost);
        $oPost = $this->WPMA->tool->arrayToObject($aPost);

        $aResult[$iKey] = $oPost;

        $aResult[$iKey]->permalink = get_permalink($oPost->ID);

        $oCategory                = get_the_category($oPost->ID);
        $aResult[$iKey]->category = $this->formatCategory($oCategory, false);

        $aResult[$iKey]->time_ago = human_time_diff(strtotime($oPost->post_modified_gmt));

        // Add post_image
        $aResult[$iKey] = $this->formatImage($aResult[$iKey], $oPost->ID);
      }
      //wp_reset_postdata();
    }

    return $aResult;
  }

  // Structure of the WPMA Post
  private function formatPage($aData) {
    $aResult = [];
    if (boolval($aData)) {
      foreach ($aData as $iKey => $oPage) {

        // Merge WPMA API Filter with WP API Result
        $aPage = array_merge($this->filter, (array) $oPage);
        $oPage = $this->WPMA->tool->arrayToObject($aPage);

        $aResult[$iKey] = $oPage;
      }
    }

    return $aResult;
  }

  // Structure of the WPMA Post
  private function formatCategory($aData, $bMorePost) {
    $aResult = [];
    if (boolval($aData)) {
      foreach ($aData as $iKey => $oCategory) {

        // Merge WPMA API Filter with WP API Result
        $aCategory = array_merge($this->filter, (array) $oCategory);
        $oCategory = $this->WPMA->tool->arrayToObject($aCategory);

        $aResult[$iKey]            = $oCategory;
        $aResult[$iKey]->permalink = get_category_link($oCategory->cat_ID);

        // Add posts
        if ($bMorePost) {
          $this->filter = wp_parse_args([
            'category' => $oCategory->cat_ID,
          ], $this->filter);
          $aResult[$iKey]->posts = $this->getPosts();
        }
      }
    }

    return $aResult;
  }

  //////////////////////////////////////
  private function formatImage($aResult, $postID) {

    $aResult->images = new stdClass;

    foreach ($this->filter['images'] as $sKey => $sSize) {

      // Set child name for post_image
      $sKey = is_array($sSize) ? $sKey : $sSize;

      $aResult->images->$sKey = image_downsize(get_post_thumbnail_id($postID), $sSize)[0];
      if ($aResult->images->$sKey === null) {
        switch ($sKey) {
        case 'thumbnail':
          $sDefaultImage = 'wpma_thumbnail.jpg';
          break;
        case 'medium_large':
          $sDefaultImage = 'wpma_medium_large.jpg';
          break;
        case 'medium':
          $sDefaultImage = 'wpma_medium.jpg';
          break;
        default:
          $sDefaultImage = 'wpma_large.jpg';
          break;
        }
        $aResult->images->$sKey = WPMA_URL . 'public/assets/imgs/' . $sDefaultImage;
      }
    }

    return $aResult;
  }
}