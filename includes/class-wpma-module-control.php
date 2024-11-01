<?php
// Blocking access direct to the plugin
defined('WPMA_BASE') or die('No script kiddies please!');

use Nette\Caching\Cache;

class WPMA_Control {

  ///////////// Call WPMACore /////////////
  public function __construct(&$WPMA) {
    $this->WPMA = &$WPMA;
  }

  ///////////// Shortcode /////////////

  // Add link app.js
  public function sortCodeAppjs() {
    wp_enqueue_script('wpma-app');
  }

  // Add link manifest.json
  public function sortCodeManifesh() {
    $args = [
      'id'   => 'wpma-mf',
      'rel'  => 'manifest',
      'href' => get_site_url() . '/manifest.json',
    ];

    $this->WPMA->render->showLink($args);
  }

  ///////////// Rule Request /////////////
  public function ruleRequest() {
    add_action('init', [$this, 'addRewriteRule']);
    add_action('parse_request', [$this, 'parseRequest']);

    add_filter('query_vars', [$this->WPMA->setting, 'registerQueryRequest']);

    // Register API
    add_filter('wpma_apis', [$this->WPMA->api, 'registerAPI']);

    // Config for template in plugin
    if ($_SESSION['WPMA_CURRENT_DEVICE'] !== 'computer' && !empty($_SESSION['WPMA_CURRENT_TEMPLATE'])) {
      add_action('plugins_loaded', [$this->WPMA->setting, 'changeSettingTemplate']);
    }
  }

  public function addRewriteRule() {
    global $wp_rewrite;

    // URL WPMA File on Mobile (not iOS)
    if ($this->WPMA->setting->getOption('WPMA_ENABLE_SW') === ENABLE) {
      add_rewrite_rule('^service-worker.js$', 'index.php?wpma_service_worker', 'top');
      add_rewrite_rule('^manifest.json$', 'index.php?wpma_manifest', 'top');
    }

    // Offline cache for Safari, IE
    if ($this->WPMA->setting->getOption('WPMA_ENABLE_APPCACHE') === ENABLE) {
      add_rewrite_rule('.*?offline\.appcache$', 'index.php?wpma_appcache', 'top');
    }

    // URL WPMA API - /wpma-api/[version]/[content-type]/[api-name]
    $sVersion = $this->WPMA->api->version;
    add_rewrite_rule('^wpma-api/' . $sVersion . '/([^/]+)/([a-z]+)\??([^/]+)?$', 'index.php?wpma_api=$matches[2]&wpma_api_type=$matches[1]&$matches[3]', 'top');

    // Force wpma template
    add_rewrite_rule('(\&|\?)wpma_template$', 'index.php?wpma_template', 'top');

    $wp_rewrite->flush_rules();
  }

  // Parse Request
  public function parseRequest($wp) {
    //bdump($wp);

    define('WPMA_CURRENT_URL', home_url($wp->request));
    define('WPMA_CURRENT_DEVICE', $this->WPMA->tool->getDevice());

    // Time to keep a cache
    $iGlobalCacheTime = $this->WPMA->setting->getOption('WPMA_CACHE_GLOBAL');

    // Check for safe, permanently reload issue
    if (!empty($_SESSION['WPMA_CURRENT_DEVICE'])) {
      $sRedirect = __('Redirecting', 'wpma');

      // Reload page to change desktop template
      if ($_SESSION['WPMA_CURRENT_DEVICE'] !== 'computer' && WPMA_CURRENT_DEVICE === 'computer') {
        $_SESSION['WPMA_CURRENT_DEVICE'] = WPMA_CURRENT_DEVICE;
        header("Refresh: 0.2");
        wp_die($sRedirect, $sRedirect);
      }

      // Reload page to change mobile template
      if ($_SESSION['WPMA_CURRENT_DEVICE'] === 'computer' && WPMA_CURRENT_DEVICE !== 'computer') {

        // Hook change template
        do_action('wpma_after_active_template');

        $_SESSION['WPMA_CURRENT_DEVICE'] = WPMA_CURRENT_DEVICE;
        header("Refresh: 0.2");
        wp_die($sRedirect, $sRedirect);
      }
    } else {
      $_SESSION['WPMA_CURRENT_DEVICE'] = WPMA_CURRENT_DEVICE;
    }

    // Print generate content offline.appcache by current page
    if (array_key_exists('wpma_appcache', $wp->query_vars) && $this->WPMA->setting->getOption('WPMA_ENABLE_APPCACHE') === ENABLE) {
      if ($this->WPMA->setting->getOption('WPMA_ENABLE_DEBUG') === DISABLE) {
        ob_end_clean();
      }

      $sContent = '';

      // Use cache
      if ($iGlobalCacheTime > 0) {
        $sKeyCache = md5(WPMA_BASE . 'offline.appcache');
        $sContent  = $this->WPMA->cache->load($sKeyCache);
      }

      // Check cache
      if (empty($sContent)) {
        $sContent = file_get_contents(WPMA_BASE . 'offline.appcache');
        $sContent = $this->WPMA->template->parseAppCacheManifest($sContent);

        // Save cache
        if ($iGlobalCacheTime > 0) {
          $this->WPMA->cache->save($sKeyCache, $sContent, [
            Cache::EXPIRE => $iGlobalCacheTime.' seconds',
          ]);
        }
      }

      header_remove();
      header('Cache-Control: max-age=600', true);
      header('Content-Type: text/cache-manifest', true);

      echo $sContent;
      exit;

    } else

    // Print content service-worker.js in root (rewrite)
    if (array_key_exists('wpma_service_worker', $wp->query_vars)) {

      if ($this->WPMA->setting->getOption('WPMA_ENABLE_DEBUG') === DISABLE) {
        ob_end_clean();
      }

      header_remove();
      $sContent = '';

      // Use cache
      if ($iGlobalCacheTime > 0) {
        $sKeyCache = md5(WPMA_BASE . 'service-worker.js');
        $sContent  = $this->WPMA->cache->load($sKeyCache);
      }

      // Check cache
      if (empty($sContent)) {
        $sContent = file_get_contents(WPMA_BASE . 'service-worker.js');
        $sContent = $this->WPMA->tool->parseShortCodeSetting($sContent);

        // Save cache
        if ($iGlobalCacheTime > 0) {
          $this->WPMA->cache->save($sKeyCache, $sContent, [
            Cache::EXPIRE => $iGlobalCacheTime.' seconds',
          ]);
        }
      }

      // Compression content
      if ($this->WPMA->setting->getOption('WPMA_ENABLE_CONTENT_COMPRESSION') === ENABLE) {
        $sContent = $this->WPMA->render->compression($sContent, 'application/javascript');
      }

      header('Cache-Control: max-age=0, private, must-revalidate', true);
      header('Content-Type: application/javascript;charset=UTF-8', true);

      echo $sContent;
      exit;

    } else

    // Print custom content manifest.json in root (rewrite)
    if (array_key_exists('wpma_manifest', $wp->query_vars)) {

      if ($this->WPMA->setting->getOption('WPMA_ENABLE_DEBUG') === DISABLE) {
        ob_end_clean();
      }

      header_remove();
      $sContent = '';

      // Use cache
      if ($iGlobalCacheTime > 0) {
        $sKeyCache = md5(WPMA_BASE . 'manifest.json');
        $sContent  = $this->WPMA->cache->load($sKeyCache);
      }

      // Check cache
      if (empty($sContent)) {
        $sContent = file_get_contents(WPMA_BASE . 'manifest.json');
        $sContent = $this->WPMA->tool->parseShortCodeSetting($sContent);

        // Save cache
        if ($iGlobalCacheTime > 0) {
          $this->WPMA->cache->save($sKeyCache, $sContent, [
            Cache::EXPIRE => $iGlobalCacheTime.' seconds',
          ]);
        }
      }

      header('Content-Type: application/javascript;charset=UTF-8', true);

      echo $sContent;
      exit;

    } else

    // WPMA API
    if (array_key_exists('wpma_api', $wp->query_vars) && array_key_exists('wpma_api_type', $wp->query_vars)) {
      if ($this->WPMA->setting->getOption('WPMA_ENABLE_DEBUG') === DISABLE) {
        ob_end_clean();
      }

      parse_str($_SERVER['QUERY_STRING'], $aFilter);

      $this->WPMA->api->parseAPI($wp->query_vars['wpma_api'], $wp->query_vars['wpma_api_type'], $aFilter);
    } else

    // Check device type
    if ($_SESSION['WPMA_CURRENT_DEVICE'] !== 'computer') {

      // Check url is not file
      if (!substr(strrchr($_SERVER['REQUEST_URI'], '.'), 1)) {

        if ($this->WPMA->setting->getOption('WPMA_CACHE_APPSHELL') > 0 && $_SERVER['REQUEST_METHOD'] === 'GET') {

          // Get key cache
          $sKeyCache = $this->WPMA->cache->getKey();
          $sContent  = $this->WPMA->cache->load($sKeyCache);

          // Check cache
          if (!empty($sContent)) {

            // Compression content
            if ($this->WPMA->setting->getOption('WPMA_ENABLE_CONTENT_COMPRESSION') === ENABLE) {
              $sContent = $this->WPMA->render->compression($sContent, 'application/javascript');
            }

            echo $sContent;
            exit;
          }
        }

        // Hook when WPMA run template mobile
        do_action('wpma_run_on_mobile', $wp);

        // Enable AppCache (iOS)
        if ($this->WPMA->setting->getOption('WPMA_ENABLE_APPCACHE') === ENABLE && $this->WPMA->tool->detect->isiOS() === true) {
          add_filter('final_output', [$this->WPMA->template, 'parseAppCachePage']);
        }

        // Enable AppShell Cache
        if ($this->WPMA->setting->getOption('WPMA_CACHE_APPSHELL') > 0 && $_SERVER['REQUEST_METHOD'] === 'GET') {
          add_filter('final_output', [$this->WPMA->cache, 'saveCurrentPageCache']);
        }

        // Get render html
        add_action('template_redirect', [$this->WPMA->render, 'startBuffer'], 0);
        add_action('shutdown', [$this->WPMA->render, 'endBuffer'], 0);

        // Parse template
        add_action('template_redirect', [$this->WPMA->template, 'parseTemplate']);
      }
    }
  }

  ///////////// Event listener /////////////
  public function eventActivatePlugin() {
    $this->WPMA->tool->configSwitchTemplate(true);

    // Check token available
    $this->WPMA->cloud->checkToken();

    if (WPMA_TOKEN_VALID === false) {
      $this->WPMA->cloud->generateToken();
    }
  }

  public function eventDeactivatePlugin() {
    $this->WPMA->tool->configSwitchTemplate(false);

    // Remove token
    $this->WPMA->setting->setOption('WPMA_TOKEN', '');

    // Remove cookie / session
    unset($_COOKIE['wpma_mobile_mode']);
    unset($_SESSION["WPMA_CURRENT_TEMPLATE"]);
    unset($_SESSION['WPMA_CURRENT_DEVICE']);
  }

  ///////////// Add WPMA /////////////
  public function addWPMA() {
    if ($this->WPMA->setting->getOption('WPMA_ENABLE_SW') === ENABLE) {
      add_action('wp_head', [$this, 'addManifesh']);
      add_action('wp_footer', [$this, 'addAppJS']);
    }
  }

  public function addAppJS() {
    do_shortcode('[WPMA_APP]');
  }

  public function addManifesh() {
    do_shortcode('[WPMA_MANIFESH]');
  }
}