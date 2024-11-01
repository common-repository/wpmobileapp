<?php
// Blocking access direct to the plugin
defined('WPMA_BASE') or die('No script kiddies please!');

use Tracy\Debugger;

class WPMA_Setting {
  ///////////// Call WPMACore /////////////
  public function __construct(&$WPMA) {
    $this->WPMA = &$WPMA;
  }

  ///////////// Register Debugger /////////////
  public function registerDebuger() {
    if (!function_exists('wp_get_current_user')) {
      include ABSPATH . "wp-includes/pluggable.php";
    }

    if ($this->getOption('WPMA_ENABLE_DEBUG') === ENABLE && current_user_can('administrator')) {

      // Fix output has been sent
      $sOutput = ob_get_contents();
      if (!empty($sOutput)) {
        ob_end_clean();
      }

      if (session_status() == PHP_SESSION_NONE || !session_id()) {
        session_start();
      }

      Debugger::$maxDepth     = 5; // default: 3
      Debugger::$maxLength    = 650; // default: 150
      Debugger::$showLocation = true;
      Debugger::$strictMode   = true;
      Debugger::enable(Debugger::DEVELOPMENT);
    }

    error_reporting(E_ALL & ~E_NOTICE);

    if (!empty($sOutput)) {
      bdump($sOutput);
    }
  }

  ///////////// Register Setting /////////////
  public function registerGeneralSetting() {

    // Code multi theme for wp-config
    define('WPMA_CONFIG_CODE', '// ** BEGIN - Plugin WPMA ** //
if (session_status() == PHP_SESSION_NONE || !session_id()) {
    session_start();
}

if(!empty($_SESSION["WPMA_CURRENT_TEMPLATE"]) && !empty($_SESSION["WPMA_CURRENT_DEVICE"])){
  if($_SESSION["WPMA_CURRENT_DEVICE"] !== "computer"){
    define("STYLESHEETPATH", $_SESSION["WPMA_CURRENT_TEMPLATE"]);
    define("TEMPLATEPATH", $_SESSION["WPMA_CURRENT_TEMPLATE"]);
  }
}
// ** END - Plugin WPMA ** // ');

    // Setting menu
    $this->menu = [
      'wpma-token'   => true,
      'wpma-setting' => true,
      'wpma-theme'   => true,
      'wpma-plugin'  => true,
    ];

    // Setting page
    $this->page = [
      'wpma-token-options'          => [
        'title' => __('Token', 'wpma'),
        'icon'  => 'vpn_key',
        'hash'  => 'token',
        'menu'  => 'wpma-token',
      ],
      'wpma-main-options'           => [
        'title' => __('General', 'wpma'),
        'icon'  => 'settings',
        'hash'  => 'general',
        'menu'  => 'wpma-setting',
      ],
      'wpma-performance-options'    => [
        'title' => __('Performance', 'wpma'),
        'icon'  => 'cached',
        'hash'  => 'performance',
        'menu'  => 'wpma-setting',
      ],
      'wpma-service-worker-options' => [
        'title' => __('Service-Workers', 'wpma'),
        'icon'  => 'router',
        'hash'  => 'serviceworkers',
        'menu'  => 'wpma-setting',
      ],
      'wpma-manifesh-options'       => [
        'title' => __('Manifesh', 'wpma'),
        'icon'  => 'developer_board',
        'hash'  => 'manifesh',
        'menu'  => 'wpma-setting',
      ],
    ];

    // Setting section
    $this->section = [
      // Hidden
      'wpma_system'              => [
        'title' => '',
        'page'  => 'wpma-main-options',
      ],
      'wpma_template'            => [
        'title' => '',
        'page'  => 'wpma-main-options',
      ],
      // Token
      'wpma_token_generate'      => [
        'title' => __('Your Token', 'wpma'),
        'page'  => 'wpma-token-options',
      ],
      // General
      'wpma_maintenance'         => [
        'title' => __('Maintenance', 'wpma'),
        'page'  => 'wpma-main-options',
      ],
      'wpma_utilities'           => [
        'title' => __('Utilities', 'wpma'),
        'page'  => 'wpma-main-options',
      ],
      // Translate
      'wpma_translate'           => [
        'title' => __('Translate', 'wpma'),
        'page'  => 'wpma-main-options',
      ],
      // Performance
      'wpma_cache_time'          => [
        'title' => __('Cache Time', 'wpma'),
        'page'  => 'wpma-performance-options',
      ],
      'wpma_optimization'        => [
        'title' => __('Optimization', 'wpma'),
        'page'  => 'wpma-performance-options',
      ],
      // Service Worker
      'wpma_sw_general'          => [
        'title' => __('General', 'wpma'),
        'page'  => 'wpma-service-worker-options',
      ],
      'wpma_sw_cache'            => [
        'title' => __('Cache URL', 'wpma'),
        'page'  => 'wpma-service-worker-options',
      ],
      // Manifesh
      'wpma_mf_application_name' => [
        'title' => __('Application Name', 'wpma'),
        'page'  => 'wpma-manifesh-options',
      ],
      'wpma_mf_url_setting'      => [
        'title' => __('URL Setting', 'wpma'),
        'page'  => 'wpma-manifesh-options',
      ],
      'wpma_mf_navigation'       => [
        'title' => __('Navigation', 'wpma'),
        'page'  => 'wpma-manifesh-options',
      ],
      'wpma_mf_color'            => [
        'title' => __('Color', 'wpma'),
        'page'  => 'wpma-manifesh-options',
      ],
      'wpma_mf_application_icon' => [
        'title' => __('Application Icon', 'wpma'),
        'page'  => 'wpma-manifesh-options',
      ],
    ];

    // Setting option
    $this->option = [
      // Setting Token
      'WPMA_TOKEN'                      => [
        'title'    => __('Token', 'wpma'),
        'section'  => 'wpma_token_generate',
        'type'     => 'string', //string, int, array
        'callback' => 'showInput', // From WPMA->render
        'args'     => [
          'description' => __('Use tokens under your domain for our services.', 'wpma'),
          'type'        => 'text',
          'value'       => '',
          'readonly'    => true,
          'callback'    => [$this->WPMA->cloud, 'generateToken'],
        ],
      ],
      // Setting Template (hidden)
      'WPMA_TEMPLATE'                   => [
        'title'    => __('Template', 'wpma'),
        'section'  => 'wpma_template',
        'type'     => 'string',
        'callback' => '',
        'args'     => [
          'description' => __('The template for mobile.', 'wpma'),
          'value'       => 'bbc',
        ],
      ],
      // Setting Maintenance
      'WPMA_ENABLE_CORE'                => [
        'title'    => __('Enable WPMA', 'wpma'),
        'section'  => 'wpma_maintenance',
        'type'     => 'int',
        'callback' => 'showInput',
        'args'     => [
          'description' => __('Quick turn on/off WPMA.', 'wpma'),
          'type'        => 'checkbox',
          'value'       => 1,
        ],
      ],
      'WPMA_ENABLE_APPCACHE'            => [
        'title'    => __('Enable AppCache (Safari, IE)', 'wpma'),
        'section'  => 'wpma_maintenance',
        'type'     => 'int',
        'callback' => '',
        'args'     => [
          'description' => __('Allows website to run when offline.', 'wpma'),
          'type'        => 'checkbox',
          'value'       => 0,
        ],
      ],
      // Utilities
      'WPMA_ENABLE_DEBUG'               => [
        'title'    => __('Enable Debug', 'wpma'),
        'section'  => 'wpma_utilities',
        'type'     => 'int',
        'callback' => '',
        'args'     => [
          'description' => __('Debug more log.', 'wpma'),
          'type'        => 'checkbox',
          'value'       => 0,
        ],
      ],
      'WPMA_ENABLE_PROTECT_API'         => [
        'title'    => __('Protect API', 'wpma'),
        'section'  => 'wpma_utilities',
        'type'     => 'int',
        'callback' => 'showInput',
        'args'     => [
          'description' => __('Prevent access WPMA API from outside (WIP).', 'wpma'),
          'type'        => 'checkbox',
          'value'       => 0,
        ],
      ],
      // Setting System (hidden)
      'WPMA_IMPORT_SCRIPTS'             => [
        'title'    => __('Import Scipts', 'wpma'),
        'section'  => 'wpma_system',
        'type'     => 'array',
        'callback' => '',
        'args'     => [
          'description' => __('Import scripts for service-workers.', 'wpma'),
          'value'       => '[]',
        ],
      ],
      // Strings
      'WPMA_STRING_OFFLINE'             => [
        'title'    => __('Offline', 'wpma'),
        'section'  => 'wpma_translate',
        'type'     => 'string',
        'callback' => 'showInput',
        'args'     => [
          'description' => __('Content string when offline, support html string.', 'wpma'),
          'type'        => 'text',
          'value'       => 'Your are offline!',
        ],
      ],
      // Caching
      'WPMA_CACHE_GLOBAL'               => [
        'title'    => __('Global Cache', 'wpma'),
        'section'  => 'wpma_cache_time',
        'type'     => 'int',
        'callback' => 'showInput',
        'args'     => [
          'description' => __('Set Global Cache by seconds.', 'wpma'),
          'type'        => 'range',
          'value'       => 60,
          'min'         => 0,
          'max'         => 3600,
        ],
      ],
      'WPMA_CACHE_APPSHELL'             => [
        'title'    => __('Cache AppShell', 'wpma'),
        'section'  => 'wpma_cache_time',
        'type'     => 'int',
        'callback' => 'showInput',
        'args'     => [
          'description' => __('Set Cache AppShell of Template by seconds.', 'wpma'),
          'type'        => 'range',
          'value'       => 60,
          'min'         => 0,
          'max'         => 3600,
        ],
      ],
      'WPMA_CACHE_API'                  => [
        'title'    => __('Cache API', 'wpma'),
        'section'  => 'wpma_cache_time',
        'type'     => 'int',
        'callback' => 'showInput',
        'args'     => [
          'description' => __('Set WPMA API Cache by seconds.', 'wpma'),
          'type'        => 'range',
          'value'       => 15,
          'min'         => 0,
          'max'         => 3600,
        ],
      ],
      // Optimization
      'WPMA_ENABLE_CONTENT_COMPRESSION' => [
        'title'    => __('Enable Content Compression', 'wpma'),
        'section'  => 'wpma_optimization',
        'type'     => 'int',
        'callback' => 'showInput',
        'args'     => [
          'description' => __('Compression and GZIP content for reduce content size (WIP).', 'wpma'),
          'type'        => 'checkbox',
          'value'       => 0,
        ],
      ],
      // Service-Worker
      'WPMA_ENABLE_SW'                  => [
        'title'    => __('Enable Service-Worker', 'wpma'),
        'section'  => 'wpma_sw_general',
        'type'     => 'int',
        'callback' => 'showInput',
        'args'     => [
          'description' => __('Quick turn on/off Service-Worker.', 'wpma'),
          'type'        => 'checkbox',
          'value'       => 1,
        ],
      ],
      'WPMA_SW_SCOPE'                   => [
        'title'    => __('Scope', 'wpma'),
        'section'  => 'wpma_sw_general',
        'type'     => 'string',
        'callback' => 'showInput',
        'args'     => [
          'description' => __('The scope of the service worker determines which files the service worker controls.', 'wpma'),
          'type'        => 'text',
          'value'       => '/',
        ],
      ],
      // Setting Cache
      'WPMA_ALWAYS_FETCH'               => [
        'title'    => __('Always fetch url', 'wpma'),
        'section'  => 'wpma_sw_cache',
        'type'     => 'int',
        'callback' => 'showInput',
        'args'     => [
          'description' => __('Always fetch url when online.', 'wpma'),
          'type'        => 'checkbox',
          'value'       => 0,
        ],
      ],
      'WPMA_CACHE_MAIN'                 => [
        'title'    => __('Cache Name', 'wpma'),
        'section'  => 'wpma_sw_cache',
        'type'     => 'string',
        'callback' => 'showInput',
        'args'     => [
          'description' => __('The cache name for service-workers.', 'wpma'),
          'type'        => 'text',
          'value'       => 'WPMA',
        ],
      ],
      'WPMA_CACHE_VERSION'              => [
        'title'    => __('Cache Version', 'wpma'),
        'section'  => 'wpma_sw_cache',
        'type'     => 'string',
        'callback' => 'showInput',
        'args'     => [
          'description' => __('Cache version for service-workers.', 'wpma'),
          'type'        => 'text',
          'value'       => 'v1',
        ],
      ],
      'WPMA_OFFLINE_URL'                => [
        'title'    => __('Offline URL', 'wpma'),
        'section'  => 'wpma_sw_cache',
        'type'     => 'string',
        'callback' => 'showInput',
        'args'     => [
          'description' => __('Add one url that you want to make a page that is offline.', 'wpma'),
          'type'        => 'text',
          'value'       => '',
        ],
      ],
      'WPMA_CACHE_URLS'                 => [
        'title'    => __('Cache URLs', 'wpma'),
        'section'  => 'wpma_sw_cache',
        'type'     => 'array',
        'callback' => 'showInput',
        'args'     => [
          'description' => __('Add url you want prepare cache when offline.', 'wpma'),
          'type'        => 'chips',
          'value'       => '["/"]',
        ],
      ],
      'WPMA_IGNORE_URLS'                => [
        'title'    => __('URLs Ignore', 'wpma'),
        'section'  => 'wpma_sw_cache',
        'type'     => 'array',
        'callback' => 'showInput',
        'args'     => [
          'description' => __('Strings from url is cache ignored, support regexp.', 'wpma'),
          'type'        => 'chips',
          'value'       => '["/wp-admin/", "preview=true"]',
        ],
      ],
      // Settings Manifesh
      'WPMA_MF_NAME'                    => [
        'title'    => __('Banner Name', 'wpma'),
        'section'  => 'wpma_mf_application_name',
        'type'     => 'string',
        'callback' => 'showInput',
        'args'     => [
          'description' => __('The name that will be displayed at the notification adds to homescreen.', 'wpma'),
          'type'        => 'text',
          'value'       => 'WPMA',
        ],
      ],
      'WPMA_MF_SHORT_NAME'              => [
        'title'    => __('App Name', 'wpma'),
        'section'  => 'wpma_mf_application_name',
        'type'     => 'string',
        'callback' => 'showInput',
        'args'     => [
          'description' => __('Is the name for the icon in homescreen.', 'wpma'),
          'type'        => 'text',
          'value'       => 'WPMA',
        ],
      ],
      'WPMA_MF_START_URL'               => [
        'title'    => __('Start URL', 'wpma'),
        'section'  => 'wpma_mf_url_setting',
        'type'     => 'string',
        'callback' => 'showInput',
        'args'     => [
          'description' => __('The URL that loads when a user launches the application from a device.', 'wpma'),
          'type'        => 'text',
          'value'       => '/',
        ],
      ],
      'WPMA_MF_SCOPE'                   => [
        'title'    => __('Scope', 'wpma'),
        'section'  => 'wpma_mf_url_setting',
        'type'     => 'string',
        'callback' => 'showInput',
        'args'     => [
          'description' => __('Defines the navigation scope of this web application context.', 'wpma'),
          'type'        => 'text',
          'value'       => '/',
        ],
      ],
      'WPMA_MF_DISPLAY'                 => [
        'title'    => __('Display', 'wpma'),
        'section'  => 'wpma_mf_navigation',
        'type'     => 'string',
        'callback' => 'showSelect',
        'args'     => [
          'description' => __('Defines the preferred display mode for the web application.', 'wpma'),
          'value'       => 'standalone',
          'option'      => [
            'standalone' => __('Standalone', 'wpma'),
            'browser'    => __('Browser', 'wpma'),
            'fullscreen' => __('Fullscreen', 'wpma'),
          ],
        ],
      ],
      'WPMA_MF_ORIENTATION'             => [
        'title'    => __('Orientation', 'wpma'),
        'section'  => 'wpma_mf_navigation',
        'type'     => 'string',
        'callback' => 'showSelect',
        'args'     => [
          'description' => __('Defines the default orientation for all the web application top level browsing contexts.', 'wpma'),
          'value'       => 'portrait',
          'option'      => [
            'portrait'  => __('Portrait', 'wpma'),
            'landscape' => __('Landscape', 'wpma'),
          ],
        ],
      ],
      'WPMA_MF_THEME_COLOR'             => [
        'title'    => __('Theme', 'wpma'),
        'section'  => 'wpma_mf_color',
        'type'     => 'string',
        'callback' => 'showInput',
        'args'     => [
          'description' => __('Defines the default theme color for an application.', 'wpma'),
          'type'        => 'text',
          'class'       => 'wpma-color-picker',
          'value'       => '#FFFFFF',
        ],
      ],
      'WPMA_MF_BACKGROUND_COLOR'        => [
        'title'    => __('Background', 'wpma'),
        'section'  => 'wpma_mf_color',
        'type'     => 'string',
        'callback' => 'showInput',
        'args'     => [
          'description' => __('Defines the expected background color for the web application.', 'wpma'),
          'type'        => 'text',
          'class'       => 'wpma-color-picker',
          'value'       => '#FFFFFF',
        ],
      ],
      'WPMA_MF_ICON_128'                => [
        'title'    => '128x128',
        'section'  => 'wpma_mf_application_icon',
        'type'     => 'string',
        'callback' => 'showImage',
        'args'     => [
          'description' => __('The application icon by size', 'wpma') . ' 128x128',
          'width'       => 128,
          'height'      => 128,
          'class'       => 'wpma-media-upload',
          'value'       => WPMA_URL . 'admin/assets/imgs/image-upload.png',
        ],
      ],
      'WPMA_MF_ICON_144'                => [
        'title'    => '144x144',
        'section'  => 'wpma_mf_application_icon',
        'type'     => 'string',
        'callback' => 'showImage',
        'args'     => [
          'description' => __('The application icon by size', 'wpma') . ' 144x144',
          'width'       => 144,
          'height'      => 144,
          'class'       => 'wpma-media-upload',
          'value'       => WPMA_URL . 'admin/assets/imgs/image-upload.png',
        ],
      ],
      'WPMA_MF_ICON_152'                => [
        'title'    => '152x152',
        'section'  => 'wpma_mf_application_icon',
        'type'     => 'string',
        'callback' => 'showImage',
        'args'     => [
          'description' => __('The application icon by size', 'wpma') . ' 152x152',
          'width'       => 152,
          'height'      => 152,
          'class'       => 'wpma-media-upload',
          'value'       => WPMA_URL . 'admin/assets/imgs/image-upload.png',
        ],
      ],
      'WPMA_MF_ICON_192'                => [
        'title'    => '192x192',
        'section'  => 'wpma_mf_application_icon',
        'type'     => 'string',
        'callback' => 'showImage',
        'args'     => [
          'description' => __('The application icon by size', 'wpma') . ' 192x192',
          'width'       => 192,
          'height'      => 192,
          'class'       => 'wpma-media-upload',
          'value'       => WPMA_URL . 'admin/assets/imgs/image-upload.png',
        ],
      ],
      'WPMA_MF_ICON_256'                => [
        'title'    => '256x256',
        'section'  => 'wpma_mf_application_icon',
        'type'     => 'string',
        'callback' => 'showImage',
        'args'     => [
          'description' => __('The application icon by size', 'wpma') . ' 256x256',
          'width'       => 256,
          'height'      => 256,
          'class'       => 'wpma-media-upload',
          'value'       => WPMA_URL . 'admin/assets/imgs/image-upload.png',
        ],
      ],
      'WPMA_MF_ICON_512'                => [
        'title'    => '512x512',
        'section'  => 'wpma_mf_application_icon',
        'type'     => 'string',
        'callback' => 'showImage',
        'args'     => [
          'description' => __('The application icon by size', 'wpma') . ' 512x512',
          'width'       => 512,
          'height'      => 512,
          'class'       => 'wpma-media-upload',
          'value'       => WPMA_URL . 'admin/assets/imgs/image-upload.png',
        ],
      ],
    ];

    $this->addSettings();

    // set default import script setting
    $aScriptUrls = [
      // Fake DOM for jQuery
      //WPMA_URL . 'public/assets/js/worker-fake-dom.js',
      // jQuery
      //WPMA_URL . 'public/assets/js/jquery.min.js',
      // wpmaAPI
      // WPMA_URL . 'public/assets/js/wpma-public-api.js',
    ];
    $this->WPMA->setting->setOption('WPMA_IMPORT_SCRIPTS', $aScriptUrls);

    // Register wpmaData in js
    add_filter('wpma_add_js_data', [$this, 'addJsData'], 10, 1);
  }

  public function addSettings($aSetting = []) {
    // Add setting form template to general setting
    if (!empty($aSetting['page']) || !empty($aSetting['section']) || !empty($aSetting['option'])) {
      foreach ($aSetting as $sSetting => $aData) {
        $this->$sSetting = array_merge($aData, $this->$sSetting);
      }
    }

    // Get current wp setting or default wpma setting
    foreach ($this->option as $sKey => $aValue) {
      // get value from db
      $nValue                  = get_option($sKey, false);
      $aValue['args']['value'] = is_numeric($nValue) ? (int) $nValue : ($nValue === false ? $aValue['args']['value'] : $nValue);
      $this->option[$sKey]     = $aValue;
    }
  }

  public function addJsData($aData) {
    $aData['translate'] = [
      'offline' => esc_html($this->getOption('WPMA_STRING_OFFLINE')),
    ];

    $aData['core'] = [
      'enable' => $this->getOption('WPMA_ENABLE_CORE'),
      'device' => $_SESSION["WPMA_CURRENT_DEVICE"],
      'userid' => get_current_user_id(),
      'apiurl' => WPMA_URL_API,
    ];

    $aData['serviceWorker'] = [
      // Don't run on iOS
      'enable' => $this->getOption('WPMA_ENABLE_SW') === ENABLE && !$this->WPMA->tool->detect->isiOS() ? 1 : 0,
      'path'   => get_site_url() . '/',
      'scope'  => $this->getOption('WPMA_SW_SCOPE'),
    ];

    $aData['appCache'] = [
      // Only run on iOS
      'enable' => $this->getOption('WPMA_ENABLE_APPCACHE') === ENABLE && $this->WPMA->tool->detect->isiOS() ? 1 : 0,
    ];

    $aData['debug'] = [
      'enable' => $this->getOption('WPMA_ENABLE_DEBUG'),
    ];

    return $aData;
  }

  ////////////////////////////////////////////////////////

  public function changeSettingTemplate() {
    // Save default wp template path
    define('WP_PATH_TEMPLATE', realpath(get_theme_root()));

    add_filter('stylesheet', [$this, 'changeTemplateDefault'], 99);
    add_filter('template', [$this, 'changeTemplateDefault'], 99);

    add_filter('theme_root', [$this, 'changeTemplateRoot']);
    add_filter('theme_root_uri', [$this, 'changeTemplateURI']);

    add_filter('template_directory', [$this, 'changeTemplatePathDefault']);
    add_filter('template_directory_uri', [$this, 'changeTemplateURI']);

    add_filter('stylesheet_directory', [$this, 'changeTemplatePathDefault']);
    add_filter('stylesheet_directory_uri', [$this, 'changeTemplateURI']);
  }

  public function changeTemplateURI($sPathTemplate) {
    if (!defined('WPMA_CURRENT_TEMPLATE')) {
      return $sPathTemplate;
    }

    $sPathTemplate = WPMA_URL . 'public/templates/' . WPMA_CURRENT_TEMPLATE;
    return $sPathTemplate;
  }

  public function changeTemplateRoot($sPathTemplate) {
    if (!defined('WPMA_CURRENT_TEMPLATE')) {
      return $sPathTemplate;
    }

    $sPathTemplate = WPMA_URL . 'public/templates';
    return $sPathTemplate;
  }

  public function changeTemplatePathDefault($sPathTemplate) {
    if (!defined('WPMA_CURRENT_TEMPLATE')) {
      return $sPathTemplate;
    }

    $sPathTemplate = WPMA_PATH_CURRENT_TEMPLATE;
    return $sPathTemplate;
  }

  public function changeTemplateDefault($sTemplateName) {
    if (!defined('WPMA_CURRENT_TEMPLATE')) {
      return $sTemplateName;
    }

    $sTemplateName = WPMA_CURRENT_TEMPLATE;
    return $sTemplateName;
  }

  ///////////// Action /////////////

  public function getOption($sKey) {
    $nValue = empty($sKey) ? false : '';

    switch ($this->option[$sKey]['type']) {
    case 'string':
      $nValue = strval($this->option[$sKey]['args']['value']);
      break;
    case 'int':
      $nValue = intval($this->option[$sKey]['args']['value']);
      break;
    case 'array':
      $nValue = $this->option[$sKey]['args']['value'];
      if (!is_array($nValue)) {
        $nValue = json_decode($nValue, true);
      }
      break;
    default:
      $nValue = $this->option[$sKey]['args']['value'];
      break;
    }

    return $nValue;
  }

  public function setOption($sKey, $nValue) {
    $bResult = false;

    switch ($this->option[$sKey]['type']) {
    case 'string':
      $nValue = strval($nValue);
      break;
    case 'int':
      $nValue = intval($nValue);
      break;
    case 'array':
      @json_decode($nValue);

      if (json_last_error() !== JSON_ERROR_NONE || is_array($nValue)) {
        $nValue = json_encode($nValue, true);
      }
      break;
    }

    //Check for valid options
    if (!empty($this->option[$sKey]['type'])) {
      $bResult = update_option($sKey, $nValue);
    }

    return $bResult;
  }

  ///////////// Internationalizing /////////////
  public function initLanguage() {
    load_plugin_textdomain('wpma', false, basename(WPMA_BASE) . DS . 'languages' . DS);
  }

  ///////////// Register Style /////////////
  public function registerAdminStyle($hook) {
    // Load only on _wpma-
    if (stripos($hook, '_wpma-') === false) {
      return;
    }

    // Style Materialize
    wp_enqueue_style('wpma-style-materialize-icon-admin', esc_url_raw('https://fonts.googleapis.com/icon?family=Material+Icons'), null, null);
    wp_enqueue_style('wpma-style-materialize-admin', WPMA_URL . 'admin/assets/css/materialize.min.css');
    wp_enqueue_style('wpma-style-admin', WPMA_URL . 'admin/assets/css/wpma-admin-style.css');

    // Script Materialize
    wp_enqueue_script('wpma-script-materialize-admin', WPMA_URL . 'admin/assets/js/materialize.min.js', ['wpma-jquery'], '', false);
  }

  ///////////// Register Style /////////////
  public function registerPublicStyle() {
    // Style Public
    wp_enqueue_style('wpma-style-public', WPMA_URL . 'public/assets/css/wpma-public-style.css');
  }

  ///////////// Register Scripts /////////////
  public function registerGlobalScript() {
    // Filter URL script
    add_filter('clean_url', [$this->WPMA->tool, 'parseURLScripts'], 11, 1);

    // Ajax Admin
    add_action('wp_ajax_ajaxOptions', [$this->WPMA->admin, 'ajaxOptions']);
    add_action('wp_ajax_ajaxButton', [$this->WPMA->admin, 'ajaxButton']);
    add_action('wp_ajax_ajaxManagerTheme', [$this->WPMA->admin, 'ajaxManagerTheme']);

    // jQuery
    wp_enqueue_script('wpma-jquery', WPMA_URL . 'public/assets/js/jquery.min.js', [], null, true);

    // JavaScript Cookie
    wp_register_script('js-cookie', WPMA_URL . 'public/assets/js/js.cookie.min.js#async', null, true);
  }

  public function registerAdminGeneralScript($hook) {
    // Load only on ?page=wpma-menus
    if ($hook !== 'toplevel_page_wpma-menus') {
      return;
    }

    wp_enqueue_media();

    wp_register_script('wpma-script-admin-general', WPMA_URL . 'admin/assets/js/wpma-admin-general.js', ['wpma-jquery', 'iris', 'js-cookie'], null, true);

    // Include chip data from setting //

    // WPMA_CACHE_URLS
    $aChipTagOfCacheUrls = [];
    $aCacheUrls          = $this->getOption('WPMA_CACHE_URLS');
    if (is_array($aCacheUrls)) {
      foreach ($aCacheUrls as $aValue) {
        $aChipTagOfCacheUrls[] = ['tag' => $aValue];
      }
    }

    // WPMA_IGNORE_URLS
    $aChipTagOfIgnoreUrls = [];
    $aCacheUrls           = $this->getOption('WPMA_IGNORE_URLS');
    if (is_array($aCacheUrls)) {
      foreach ($aCacheUrls as $aValue) {
        $aChipTagOfIgnoreUrls[] = ['tag' => $aValue];
      }
    }

    // Data for Materialize Chip
    $aSettingData = [
      'WPMA_CACHE_URLS'  => [
        'type'                => 'chips',
        'placeholder'         => __('Enter url', 'wpma'),
        'data'                => $aChipTagOfCacheUrls,
      ],

      'WPMA_IGNORE_URLS' => [
        'type'                => 'chips',
        'placeholder'         => __('Enter string has in url', 'wpma'),
        'data'                => $aChipTagOfIgnoreUrls,
      ],
    ];

    wp_localize_script('wpma-script-admin-general', 'wpmaData', [
      'url'           => [
        'ajax' => admin_url('admin-ajax.php'),
      ],
      'translate'     => [
        'chooseImage' => __('Choose a image', 'wpma'),
        'saveSuccess' => __('Save options success!', 'wpma'),
        'runSuccess'  => __('Run success!', 'wpma'),
      ],
      'settings'      => (object)$aSettingData,
      'serviceWorker' => [
        'enable' => $this->getOption('WPMA_ENABLE_SW') === ENABLE && !$this->WPMA->tool->detect->isiOS() ? 1 : 0,
      ],
    ]);

    wp_enqueue_script('wpma-script-admin-general');
  }

  public function registerAdminManagerThemeScript($hook) {
    // Load only on ?page=wpma-manager-theme
    if ($hook !== "wpma_page_wpma-manager-themes") {
      return;
    }

    // Script Manager
    wp_register_script('wpma-script-admin-manager-template', WPMA_URL . 'admin/assets/js/wpma-admin-manager-template.js', ['wpma-jquery', 'js-cookie'], null, true);

    wp_localize_script('wpma-script-admin-manager-template', 'wpmaData', [
      'url' => [
        'ajax'       => admin_url('admin-ajax.php'),
        'customizer' => admin_url('customize.php'),
      ],
    ]);

    wp_enqueue_script('wpma-script-admin-manager-template');
  }

  public function registerPublicScript() {
    // WPMA APP
    wp_register_script('wpma-app-public', WPMA_URL . 'public/assets/js/wpma-public-app.js', ['wpma-jquery'], null, true);

    $wpmaData = apply_filters('wpma_add_js_data', []);

    wp_localize_script('wpma-app-public', 'wpmaData', $wpmaData);
    wp_enqueue_script('wpma-app-public');

    // WPMA API
    wp_enqueue_script('wpma-api-public', WPMA_URL . 'public/assets/js/wpma-public-api.js', ['wpma-jquery', 'jquery-ui-draggable', 'jquery-touch-punch'], null, true);

    // VueJS
    wp_enqueue_script('wpma-scripts-vue', WPMA_URL . 'public/assets/js/vue.min.js', [], null, true);
  }

  ///////////// Register Shortcode /////////////
  public function registerShortCode() {
    add_shortcode('WPMA_APP', [$this->WPMA->control, 'sortCodeAppjs']);
    add_shortcode('WPMA_MANIFESH', [$this->WPMA->control, 'sortCodeManifesh']);
  }

  ///////////// Register Query Request /////////////
  public function registerQueryRequest($vars) {
    $vars[] = 'wpma_service_worker';
    $vars[] = 'wpma_manifest';
    $vars[] = 'wpma_api';
    $vars[] = 'wpma_api_type';
    $vars[] = 'wpma_template';
    $vars[] = 'wpma_appcache';
    return $vars;
  }
}