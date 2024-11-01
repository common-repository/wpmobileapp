<?php
// Blocking access direct to the theme
defined('WPMA_BASE') or die('No script kiddies please!');

global $WPMA;
if ($WPMA) {
  new bbcTemplate($WPMA);
}

class bbcTemplate {

  ///////////// Call WPMACore /////////////
  public function __construct(&$WPMA) {
    $this->WPMA = &$WPMA;

    // Register WPMA setting
    $this->registerWPMASetting();

    // Register wpmaData in js
    add_filter('wpma_add_js_data', [$this, 'addJsData'], 10, 1);

    // Setup Theme
    add_action('after_setup_theme', [$this, 'bbcSetup']);  

    // Only setup this template on mobile
    if ($_SESSION['WPMA_CURRENT_DEVICE'] !== 'computer') {
      add_action('wp_enqueue_scripts', [$this, 'registerPublicStyle']);

      add_action('wp_enqueue_scripts', [$this, 'registerPublicScript']);

      // WP Title
      add_filter('wp_title', [$this, 'setTitle'], 10, 3);      
    }  
  }

  public function bbcSetup() {

    // enable support for post thumbnail or feature image on posts and pages
    add_theme_support('post-thumbnails');

    // Add image size for feature post
    add_image_size('feature_post', 900, 300, true);

    // Register Navigation Menus
    register_nav_menus([
      'header_menu' => __('Mobile Header Menu', 'bbc'),
    ]);
  }

  public function registerWPMASetting() {
    // Setting page
    $aSetting['page'] = [
      'wpma-bbc-options' => [
        'title' => __('Setting Template', 'bbc'),
        'icon'  => 'art_track',
        'hash'  => 'bbc-theme',
        'menu'  => 'wpma-theme',
      ],
    ];

    // Setting section
    $aSetting['section'] = [
      'wpma_bbc_effect' => [
        'title' => __('Effect', 'bbc'),
        'page'  => 'wpma-bbc-options',
      ],
      'wpma_bbc_color' => [
        'title' => __('Color', 'bbc'),
        'page'  => 'wpma-bbc-options',
      ],
      'wpma_bbc_custom_post_number' => [
        'title' => __('Custom Posts Number', 'bbc'),
        'page'  => 'wpma-bbc-options',
      ],
    ];

    // Setting option
    $aSetting['option'] = [
      'BBC_SMOOTH_PAGE'  => [
        'title'    => __('Smooth page', 'wpma'),
        'section'  => 'wpma_bbc_effect',
        'type'     => 'int',
        'callback' => 'showInput', // render
        'args'     => [
          'description' => __('Smooth transition page, instantaneous, works better with progress bar effect.', 'bbc'),
          'type'        => 'checkbox',
          'value'       => 0,
        ],
      ],
      'BBC_PROGRESS_BAR' => [
        'title'    => __('Progress bar', 'bbc'),
        'section'  => 'wpma_bbc_effect',
        'type'     => 'int',
        'callback' => 'showInput', // render
        'args'     => [
          'description' => __('Add a progress bar on top of the page.', 'bbc'),
          'type'        => 'checkbox',
          'value'       => 0,
        ],
      ],
      'BBC_CONTENT_COLOR' => [
        'title'    => __('Background Content', 'bbc'),
        'section'  => 'wpma_bbc_color',
        'type'     => 'string',
        'callback' => 'showInput',
        'args'     => [
          'description' => __('Background color content post.', 'bbc'),
          'type'        => 'text',
          'class'       => 'wpma-color-picker',
          'value'       => '#FFFFFF',
        ],
      ],
      'BBC_HEADER_COLOR' => [
        'title'    => __('Header menu', 'bbc'),
        'section'  => 'wpma_bbc_color',
        'type'     => 'string',
        'callback' => 'showInput',
        'args'     => [
          'description' => __('Background color content post.', 'bbc'),
          'type'        => 'text',
          'class'       => 'wpma-color-picker',
          'value'       => '#bb1919',
        ],
      ],
      'BBC_CONTENT_COLOR' => [
        'title'    => __('Content post', 'bbc'),
        'section'  => 'wpma_bbc_color',
        'type'     => 'string',
        'callback' => 'showInput',
        'args'     => [
          'description' => __('Header menu color.', 'bbc'),
          'type'        => 'text',
          'class'       => 'wpma-color-picker',
          'value'       => '#f2f2f2',
        ],
      ],
      'BBC_FOOTER_COLOR' => [
        'title'    => __('Footer bar', 'bbc'),
        'section'  => 'wpma_bbc_color',
        'type'     => 'string',
        'callback' => 'showInput',
        'args'     => [
          'description' => __('Footer bar color.', 'bbc'),
          'type'        => 'text',
          'class'       => 'wpma-color-picker',
          'value'       => '#bb1919',
        ],
      ],
      'BBC_POST_NUMBER_PAGE'               => [
        'title'    => __('Posts number of page', 'bbc'),
        'section'  => 'wpma_bbc_custom_post_number',
        'type'     => 'int',
        'callback' => 'showInput',
        'args'     => [
          'description' => __('Set posts number for page.', 'bbc'),
          'type'        => 'range',
          'value'       => 5,
          'min'         => 1,
          'max'         => 30,
        ],
      ],
      'BBC_POST_NUMBER_RELATED'               => [
        'title'    => __('Posts number of related', 'bbc'),
        'section'  => 'wpma_bbc_custom_post_number',
        'type'     => 'int',
        'callback' => 'showInput',
        'args'     => [
          'description' => __('Set posts number for related.', 'bbc'),
          'type'        => 'range',
          'value'       => 3,
          'min'         => 1,
          'max'         => 10,
        ],
      ],
      'BBC_POST_NUMBER_SEARCH'               => [
        'title'    => __('Posts number of search', 'bbc'),
        'section'  => 'wpma_bbc_custom_post_number',
        'type'     => 'int',
        'callback' => 'showInput',
        'args'     => [
          'description' => __('Set posts number for search.', 'bbc'),
          'type'        => 'range',
          'value'       => 4,
          'min'         => 1,
          'max'         => 30,
        ],
      ],   
    ];

    $this->WPMA->setting->addSettings($aSetting);
  }

  public function registerPublicStyle() {
    // Style of BBC
    wp_enqueue_style('wpma-template-style', get_template_directory_uri() . '/assets/css/main.css');

    // Custom Color by Setting
    $sHeaderMenuColor = $this->WPMA->setting->getOption('BBC_HEADER_COLOR');
    $sContentPostColor = $this->WPMA->setting->getOption('BBC_CONTENT_COLOR');
    $sFooterBarColor = $this->WPMA->setting->getOption('BBC_FOOTER_COLOR');

    $sCustomCSS = "
    .bbc-header-color{
      background-color: {$sHeaderMenuColor};
    }
    .bbc-content-color{
      background-color: {$sContentPostColor};
    }
    .bbc-footer-color{
      background-color: {$sFooterBarColor};
    }";

    wp_add_inline_style('wpma-template-style', $sCustomCSS);

    if ($this->WPMA->setting->getOption('BBC_PROGRESS_BAR') === ENABLE) {

      // Style Progress
      wp_enqueue_style('bbc-style-progress', get_template_directory_uri() . '/assets/css/nprogress.css');
    }
  }

  public function registerPublicScript() {
    // Script of BBC
    wp_enqueue_script('wpma-template-script', get_template_directory_uri() . '/assets/js/main.js#async', ['wpma-jquery'], null, true);

    add_theme_support('post-thumbnails');

    if ($this->WPMA->setting->getOption('BBC_SMOOTH_PAGE') === ENABLE) {

      // Smooth Animation
      wp_enqueue_script('bbc-script-smooth-page', get_template_directory_uri() . '/assets/js/jquery.smoothState.js', ['wpma-jquery'], '', true);

      add_action('after_body', [$this, 'addDataForSmoothStage']);
    }

    if ($this->WPMA->setting->getOption('BBC_PROGRESS_BAR') === ENABLE) {
      // Progress bar
      wp_enqueue_script('bbc-script-progress', get_template_directory_uri() . '/assets/js/nprogress.js#async', ['wpma-jquery'], '', true);
    }
  }

  public function setTitle($sTitle, $bSep, $sSeplocation) {
    $sGlobalTitle = get_bloginfo('name') . ' - ' . get_bloginfo('description');

    if (empty($sTitle)) {
      $sTitle = $sGlobalTitle;
    } else {
      $sTitle .= ' | ' . $sGlobalTitle;
    }

    return $sTitle;
  }

  public function addDataForSmoothStage() {
    echo '<div id="SmoothPage">';
  }

  public function addJsData($aData) {
    $aData['template'] = [
      'component' => [
        'wpma-loading-related' => [
          'template' => '
            <div class="animated-background related">
    <div class="background-masker related-white-01"></div>
    <div class="background-masker related-white-02"></div>
  </div>',
        ],
      ],
    ];

    return $aData;
  }
}
