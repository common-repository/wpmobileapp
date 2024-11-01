<?php
// Blocking access direct to the plugin
defined('WPMA_BASE') or die('No script kiddies please!');

class WPMA_Template {

  ///////////// Call WPMACore /////////////
  public function __construct(&$WPMA) {
    $this->WPMA = &$WPMA;
  }

  ///////////// Init Template /////////////
  public function initTemplate() {

    // Defaut template
    if (!defined('WPMA_CURRENT_TEMPLATE')) {
      define('WPMA_CURRENT_TEMPLATE', $this->WPMA->setting->getOption('WPMA_TEMPLATE'));
    }

    if (WPMA_CURRENT_TEMPLATE !== false) {
      define('WPMA_PATH_CURRENT_TEMPLATE', WPMA_PATH_TEMPLATE . WPMA_CURRENT_TEMPLATE . DS);
      define('WPMA_URL_CURRENT_TEMPLATE', WPMA_URL_TEMPLATE . WPMA_CURRENT_TEMPLATE . '/');

      $_SESSION["WPMA_CURRENT_TEMPLATE"] = WPMA_PATH_CURRENT_TEMPLATE;
    }

    // Fix path template desktop
    if(is_admin()){
      unset($_SESSION["WPMA_CURRENT_TEMPLATE"]);
    }

    $sFileTemplate = realpath(WPMA_PATH_CURRENT_TEMPLATE . 'wpma-template.php');
    if (file_exists($sFileTemplate)) {
      require_once $sFileTemplate;
    }
  }

  ///////////// Customizer Template /////////////
  public function initCustomizer() {
    $sFileCustomizer = WPMA_PATH_CURRENT_TEMPLATE . 'customizer.php';
    if (file_exists($sFileCustomizer)) {
      add_action('customize_controls_enqueue_scripts', [$this, 'customizerNavScripts']);

      // Remove some customizer of other theme
      add_action('after_setup_theme', [$this, 'removeWPThemeCustomizer'], 1);
      require_once $sFileCustomizer;
    }
  }

  public function removeWPThemeCustomizer() {
    $this->WPMA->tool->removeWPThemeHook('customize_register');
  }

  public function customizerNavScripts() {
    // Script Customizer Nav
    wp_register_script('wpma-scripts-customize-nav', WPMA_URL . 'admin/assets/js/wpma-admin-customize.js', ['wpma-jquery', 'js-cookie'], false, true);

    wp_localize_script('wpma-scripts-customize-nav', 'wpma', [
      'switchmobile'  => __('Switch to Mobile Theme', 'wpma'),
      'switchdesktop' => __('Switch to Desktop Theme', 'wpma'),
    ]);

    wp_enqueue_script('wpma-scripts-customize-nav');
  }

  ///////////// Translate Template /////////////

  public function translateTemplate($sMessage) {
    return __($sMessage, WPMA_CURRENT_TEMPLATE);
  }

  public function translateAdminPage($sMessage) {
    return __($sMessage, 'wpma');
  }

  ///////////// Parse Template /////////////
  public function parseTemplate() {

    if (!defined('WPMA_PATH_CURRENT_TEMPLATE')) {
      die('Need select template first');
    }

    // Script Customizer Live Preview
    if (is_customize_preview() && file_exists(WPMA_URL_CURRENT_TEMPLATE . 'assets/js/customize-preview.js')) {
      wp_enqueue_script('wpma-scripts-customize-preview', WPMA_URL_CURRENT_TEMPLATE . 'assets/js/customize-preview.js', ['wpma-jquery', 'customize-preview'], null, true);
    }

    // Custome head from service worker (temporary do not delete meta same name)
    add_action('wp_head', [$this->WPMA->render, 'addHeadForServiceWorker']);

    // Script control
    wp_enqueue_script('wpma-script-public');

    // CSS Core
    wp_enqueue_style('wpma-style-public');
  }

  ///////////// AppCache iOS ///////////
  public function parseAppCachePage($sContent) {
    // Add manifest attr in html tag
    $sPattern     = '/<html(.*?)>/i';
    $sReplacement = '<html manifest="offline.appcache"$1>';
    $sContent     = preg_replace($sPattern, $sReplacement, $sContent);

    // Create a cache for offline.appcache
    $sKeyCache = md5(WPMA_CURRENT_URL);
    $this->WPMA->cache->save($sKeyCache, $sContent);
    
    return $sContent;
  }

  public function parseAppCacheManifest($sContentManifest) {
    $aURLCache = [];

    $sPageURL = str_replace('/offline.appcache', '', WPMA_CURRENT_URL);

    $sKeyCache = md5($sPageURL);
    $sContent  = $this->WPMA->cache->load($sKeyCache);

    // Check cache
    if (!empty($sContent)) {

      // Generate CACHE MANIFEST
      $aURL = $this->WPMA->tool->getURLinContent($sContent);

      if (empty($aURL)) {
        return false;
      }

      $aSupportType = ['css', 'js', 'jpg', 'png'];
      foreach ($aURL as $iKey => $aData) {
        if (in_array($aData['extension'], $aSupportType)) {
          $aURLCache[] = $aData['URL'];
        }
      }
    } else {
      return false;
    }

    // Date of content
    $sContentManifest = str_replace("[DATE]", current_time('Y-m-d') . ' / ' . count($aURLCache) . ' urls', $sContentManifest);

    // URL for cache
    $sContentManifest = str_replace("[URL_CACHE]", implode("\n", $aURLCache), $sContentManifest);

    // URL for offline
    $aURL             = $this->WPMA->setting->getOption('WPMA_OFFLINE_URL');
    $sURL             = !empty($aURL[0]) ? $aURL[0] : '';
    $sURL             = filter_var($sURL, FILTER_VALIDATE_URL) ? $sURL : get_home_url() . $sURL;
    $sContentManifest = str_replace("[URL_OFFLINE]", $sPageURL . ' ' . $sURL, $sContentManifest);

    return $sContentManifest;
  }
}
