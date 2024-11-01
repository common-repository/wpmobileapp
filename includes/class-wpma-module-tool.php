<?php
// Blocking access direct to the plugin
defined('WPMA_BASE') or die('No script kiddies please!');

class WPMA_Tool {

  ///////////// Call WPMACore /////////////
  public function __construct(&$WPMA) {
    $this->WPMA   = &$WPMA;
    $this->detect = new Mobile_Detect;
  }

  // Check page is customize preview
  public function isCustomizePreview() {
    if (is_customize_preview()) {
      return true;
    }
    return false;
  }

  // Parse Short-code Settings
  public function parseShortCodeSetting($sContent) {
    //Get all option name
    $aSetting = $this->WPMA->setting->option;

    foreach ($aSetting as $sKey => $aValue) {
      // Get option value
      $nValue = $this->WPMA->setting->getOption($sKey);

      // Convert array to string (json)
      $nValue = is_array($nValue) ? json_encode($nValue) : $nValue;

      $nValue   = is_numeric($nValue) ? $nValue : "\"" . addslashes($nValue) . "\"";
      $sContent = str_replace("[$sKey]", $nValue, $sContent);
    }

    //bdump(get_defined_constants(true));

    // Shortcode WPMA_CURRENT_DEVICE
    $sContent = str_replace("[WPMA_CURRENT_DEVICE]", "'" . $_SESSION["WPMA_CURRENT_DEVICE"] . "'", $sContent);

    return $sContent;
  }

  // Remove Customizer Setting of WPTheme
  public function removeWPThemeHook($hook) {
    $aHooks = $this->getListHooks($hook);
    foreach ($aHooks as $aHook) {
      if (strpos($aHook['file'], WP_PATH_TEMPLATE) !== false) {
        remove_action('customize_register', $aHook['function']);
      }
    }
  }

  // Config code switch template in wp-config
  public function configSwitchTemplate($bActive) {
    // Add code in wp-config when activating the plugin
    $sPathConfigWP = ABSPATH . 'wp-config.php';
    if (is_writable($sPathConfigWP)) {
      $sContentConfigWP = file_get_contents($sPathConfigWP);

      if ($bActive && strpos($sContentConfigWP, 'WPMA_CURRENT_TEMPLATE') === false) {
        // Add code
        $sContentConfigWP = preg_replace('/(define\(.*?(\'|")WP_DEBUG(\'|").*?,.*?\);)/i', "$1\n\n" . WPMA_CONFIG_CODE, $sContentConfigWP);
      } elseif($bActive === false) {
        // Remove code
        $sContentConfigWP = preg_replace('/\/\/ \*\* BEGIN - Plugin WPMA \*\* \/\/.*\/\/ \*\* END - Plugin WPMA \*\* \/\//s', '', $sContentConfigWP);
      }

      file_put_contents($sPathConfigWP, $sContentConfigWP);
    }
  }

  public function getThemeData($sID = '', $sData = false) {
    $aTheme       = [];
    $listTemplate = scandir(WPMA_PATH_TEMPLATE);

    foreach ($listTemplate as $sKey => $aValue) {
      if ($aValue === '.' || $aValue === '..') {
        continue;
      }

      if ($sID !== '' && $aValue !== $sID) {
        continue;
      }

      $sPathDataTheme = WPMA_PATH_TEMPLATE . DS . $aValue . DS;

      if (file_exists($sPathDataTheme . 'style.css')) {
        $aCurrentDataTheme = get_file_data($sPathDataTheme . 'style.css', [
          'theme_name'  => 'Theme Name',
          'theme_uri'   => 'Theme URI',
          'author'      => 'Author',
          'author_uri'  => 'Author URI',
          'description' => 'Description',
          'tag'         => 'Tags',
          'version'     => 'Version',
          'license'     => 'License',
          'license_uri' => 'License URI',
          'text_domain' => 'Text Domain',
          'domain_path' => 'Domain Path',
        ]);
        if ($sData !== false) {
          $aTheme[$aValue] = $aCurrentDataTheme[$sData];
        } else {
          $aTheme[$aValue] = $aCurrentDataTheme;
          load_plugin_textdomain($aCurrentDataTheme['text_domain'], false, $sPathDataTheme . $aCurrentDataTheme['domain_path']);
        }
      }
    }

    return $sID !== '' ? $aTheme[$sID] : $aTheme;
  }

  public function getListHooks($hook = '') {
    global $wp_filter;

    if (!empty($wp_filter[$hook]->callbacks)) {
      array_walk($wp_filter[$hook]->callbacks, function ($callbacks, $priority) use (&$hooks) {
        foreach ($callbacks as $id => $callback) {
          $hooks[] = array_merge(['id' => $id], $callback);
        }

      });
    } else {
      return [];
    }

    foreach ($hooks as &$item) {
      // skip if callback does not exist
      if (!is_callable($item['function'])) {
        continue;
      }

      // function name as string or static class method eg. 'Foo::Bar'
      if (is_string($item['function'])) {
        $ref          = strpos($item['function'], '::') ? new ReflectionClass(strstr($item['function'], '::', true)) : new ReflectionFunction($item['function']);
        $item['file'] = $ref->getFileName();

        // array( object, method ), array( string object, method ), array( string object, string 'parent::method' )
      } elseif (is_array($item['function'])) {

        $ref = new ReflectionClass($item['function'][0]);

        // $item['function'][0] is a reference to existing object
        $item['function'] = array(
          is_object($item['function'][0]) ? get_class($item['function'][0]) : $item['function'][0],
          $item['function'][1],
        );
        $item['file'] = $ref->getFileName();
        // closures
      } elseif (is_callable($item['function'])) {
        $ref              = new ReflectionFunction($item['function']);
        $item['function'] = get_class($item['function']);
        $item['file']     = $ref->getFileName();
      }
      unset($item['accepted_args']);
    }
    return $hooks;
  }

  public function getLinkBySlug($slug, $type = 'post') {
    $post = get_page_by_path($slug, OBJECT, $type);
    return get_permalink($post->ID);
  }

  public function arrayToObject($array) {
    if (!is_array($array)) {
      return $array;
    }

    $object = new stdClass();
    if (count($array) > 0) {
      foreach ($array as $name => $value) {
        if (!empty($name) || $name >= 0) {
          $object->$name = $this->arrayToObject($value);
        }
      }
      return $object;
    } else {
      return false;
    }
  }

  public function getDevice() {
    $sDeviceType = ($this->detect->isMobile() ? ($this->detect->isTablet() ? 'tablet' : 'phone') : 'computer');

    // Update device type for customize theme    
    if (is_customize_preview() && $_COOKIE['wpma_mobile_mode'] == 'true') {
      $sDeviceType = 'tablet';
    }

    return $sDeviceType;
  }

  public function getURLinContent($sContent) {
    preg_match_all('/(href|src)=("|\')(.*?)("|\')/smi', $sContent, $aURL);
    $aURL = array_unique($aURL[3]);

    // Had found urls
    $aFoundURL = null;
    if (boolval($aURL)) {
      foreach ($aURL as $iKey => $sURL) {
        if (!empty($sURL[1])) {
          $sCURL = $sURL[1] === '/' ? SCHEME . ':' . $sURL : $sURL;
        }
        // Filter validate url
        if (filter_var($sCURL, FILTER_VALIDATE_URL) !== false) {
          // Get file type (ext)
          preg_match('/\/[^\/\?]+\.([^\?\/]+)(\?[^\?\/]+)?$/', $sURL, $aExt);
          if (!empty($aExt[1])) {
            $aFoundURL[$iKey]['extension'] = $aExt[1];
          } else {
            $aFoundURL[$iKey]['extension'] = 'noext';
          }
          $aFoundURL[$iKey]['URL'] = $sURL;
        }
      }
    }
    return $aFoundURL;
  }

  // Custom request by CURL
  public function getContentURL($aOptions) {

    // Default settings
    $aOptions['method']     = !empty($aOptions['method']) ? $aOptions['method'] : 'GET';
    $aOptions['url']        = !empty($aOptions['url']) ? $aOptions['url'] : '';
    $aOptions['data']       = !empty($aOptions['data']) ? $aOptions['data'] : null;
    $aOptions['timeout']    = !empty($aOptions['timeout']) ? $aOptions['timeout'] : 30;
    $aOptions['nobody']     = !empty($aOptions['nobody']) ? $aOptions['nobody'] : false;
    $aOptions['header']     = !empty($aOptions['header']) ? $aOptions['header'] : false;
    $aOptions['httpheader'] = !empty($aOptions['httpheader']) ? $aOptions['httpheader'] : [];
    $aOptions['cookie']     = !empty($aOptions['cookie']) ? $aOptions['cookie'] : '';
    $aOptions['ckfile']     = !empty($aOptions['ckfile']) ? $aOptions['ckfile'] : '';
    $aOptions['session']    = !empty($aOptions['session']) ? $aOptions['session'] : false;
    $aOptions['follow']     = !empty($aOptions['follow']) ? $aOptions['follow'] : true;
    $aOptions['local']      = !empty($aOptions['local']) ? $aOptions['local'] : false;
    $aOptions['error']      = !empty($aOptions['error']) ? $aOptions['error'] : true;
    $aOptions['file']       = !empty($aOptions['file']) ? $aOptions['file'] : null;
    $aOptions['fresh']      = !empty($aOptions['fresh']) ? $aOptions['fresh'] : false;
    $aOptions['referer']    = !empty($aOptions['referer']) ? $aOptions['referer'] : get_home_url();
    $aOptions['ssl']        = !empty($aOptions['ssl']) ? $aOptions['ssl'] : true;
    $aOptions['proxy']      = !empty($aOptions['proxy']) ? $aOptions['proxy'] : '';

    if ($aOptions['url'] === '') {
      return ['error' => 'url_incorrect'];
    }

    if ($aOptions['local'] === true) {
      return ['content' => file_get_contents($aOptions['url']), 'header' => []];
    }

    // Init CURL
    $oCURL = curl_init();
    curl_setopt($oCURL, CURLOPT_VERBOSE, 1);
    curl_setopt($oCURL, CURLOPT_NOSIGNAL, 1);
    curl_setopt($oCURL, CURLOPT_ENCODING, 'gzip, deflate');
    curl_setopt($oCURL, CURLOPT_MAXREDIRS, 10);
    curl_setopt($oCURL, CURLOPT_TCP_NODELAY, 1);
    curl_setopt($oCURL, CURLOPT_AUTOREFERER, 0);
    curl_setopt($oCURL, CURLOPT_REFERER, $aOptions['referer']);
    curl_setopt($oCURL, CURLOPT_FRESH_CONNECT, $aOptions['fresh']);
    curl_setopt($oCURL, CURLOPT_BINARYTRANSFER, 1);
    curl_setopt($oCURL, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($oCURL, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($oCURL, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($oCURL, CURLOPT_NOBODY, $aOptions['nobody']);
    curl_setopt($oCURL, CURLOPT_HEADER, $aOptions['header']);
    curl_setopt($oCURL, CURLOPT_TIMEOUT, $aOptions['timeout']);
    curl_setopt($oCURL, CURLOPT_FOLLOWLOCATION, $aOptions['follow']);
    curl_setopt($oCURL, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT'] . ' Wordpress: ' . get_home_url());

    $aOptions['url'] = ($aOptions['url'][1] === '/' && !empty($_SERVER['REQUEST_SCHEME'])) ? $_SERVER['REQUEST_SCHEME'] . ':' . $aOptions['url'] : $aOptions['url'];
    curl_setopt($oCURL, CURLOPT_URL, $aOptions['url']);

    // Header
    $aHeaders   = $aOptions['httpheader'];
    $aHeaders[] = 'Connection: close';
    $aHeaders[] = 'Accept-Encoding: gzip, deflate';
    $aHeaders[] = 'Content-Encoding: gzip';
    $aHeaders[] = 'Referer: ' . $aOptions['url'];
    curl_setopt($oCURL, CURLOPT_HTTPHEADER, $aHeaders);

    // File
    if ($aOptions['file'] !== null) {
      curl_setopt($oCURL, CURLOPT_FILE, $aOptions['file']);
    }

    // ADD PROXY
    if ($aOptions['proxy'] !== '') {
      curl_setopt($oCURL, CURLOPT_PROXY, $aOptions['proxy']);
    }

    $ckwFile = $ckrFile = '';

    // SAVE COOKIE
    if ($aOptions['ckfile'] !== '') {
      $ckwFile = WPMA_BASE . 'admin' . DS . 'cache' . DS . 'ckw' . md5($aOptions['ckfile']) . '.txt';
      curl_setopt($oCURL, CURLOPT_COOKIEJAR, $ckwFile);
      curl_setopt($oCURL, CURLOPT_COOKIEFILE, $ckwFile);
    }

    // ADD COOKIE
    if ($aOptions['cookie'] !== '') {
      $ckrFile = WPMA_BASE . 'admin' . DS . 'cache' . DS . 'ckr' . md5($aOptions['cookie']) . '.txt';
      if (file_exists($ckrFile)) {
        $sCookie = file_get_contents($ckrFile);
        curl_setopt($oCURL, CURLOPT_COOKIE, $sCookie);
      } elseif ($aOptions['error'] === true) {
        return ['error' => 'Cookie not exists'];
      }
    }

    // SSL
    $sHttps = false;
    if (stripos($aOptions['url'], 'https://') !== false && $aOptions['ssl'] === true) {
      $sHttps = true;
      // Note: need remove CURLOPT_SSL_VERIFYPEER and CURLOPT_SSL_VERIFYHOST for safe
      curl_setopt($oCURL, CURLOPT_SSL_VERIFYPEER, 0);
      curl_setopt($oCURL, CURLOPT_SSL_VERIFYHOST, 0);
      curl_setopt($oCURL, CURLOPT_CAINFO, WPMA_BASE . 'includes' . DS . 'cacert.pem');
    }

    // POST
    if ($aOptions['method'] === 'POST' && boolval($aOptions['data'])) {
      curl_setopt($oCURL, CURLOPT_POST, 1);
      curl_setopt($oCURL, CURLOPT_POSTFIELDS, http_build_query($aOptions['data']));
    }

    // AUTH
    if (!empty($aOptions['httpauth'])) {
      curl_setopt($oCURL, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST | CURLAUTH_BASIC);
      curl_setopt($oCURL, CURLOPT_USERPWD, $aOptions['httpauth']);
    }

    // SESSION
    if ($aOptions['session'] === true) {
      curl_setopt($oCURL, CURLOPT_COOKIE, session_name() . '=' . session_id() . '; 1; path=/; domain=' . $_SERVER['HTTP_HOST'] . '; ' . !empty($_SERVER['HTTPS']) ? true : false . ';' . $sHttps);
      session_write_close();
    }

    $sContent = curl_exec($oCURL);

    if (curl_errno($oCURL) && $aOptions['error'] === true) {
      return ['error' => 'Unable load page: ' . curl_error($oCURL)];
    }

    $sHeader = '';
    if ($aOptions['header'] === true) {
      $sHeaderSize = curl_getinfo($oCURL, CURLINFO_HEADER_SIZE);
      $sHeader     = substr($sContent, 0, $sHeaderSize);
      $sContent    = str_replace($sHeader, '', $sContent);
    }

    curl_close($oCURL);
    if ($aOptions['session'] === true) {
      unset($_COOKIE['PHPSESSID']);
    }

    $sContent = boolval($sContent) ? $sContent : false;

    // save cookie
    if (file_exists($ckwFile)) {
      $ckrFile = TAC_BASE . 'admin' . DS . 'cache' . DS . 'ckr' . md5($aOptions['ckfile']) . '.txt';
      $sCookie = $this->cookiejarToString(file_get_contents($ckwFile));
      file_put_contents($ckrFile, $sCookie);
    }

    return ['content' => $sContent, 'header' => $this->getHeadersFromResponse($sHeader)];
  }

  public function getHeadersFromResponse($sResponse) {
    $aHeaders = [];

    $sHeader = substr($sResponse, 0, strpos($sResponse, "\r\n\r\n"));

    foreach (explode("\r\n", $sHeader) as $i => $sLine) {
      if ($i === 0) {
        $aHeaders['http_code'] = $sLine;
      } else {
        list($sKey, $nValue) = explode(': ', $sLine);

        $aHeaders[$sKey] = $nValue;
      }
    }

    return $aHeaders;
  }

  // Async load
  public function parseURLScripts($sUrl) {
    // Get all #[setting] in url
    preg_match_all('/(.*?)\#([^#]+)/', $sUrl, $aMatch);

    $sAttr = '';
    foreach ($aMatch[2] as $sMatch) {
      $sPattern     = '/=(.*?)$/';
      $sReplacement = "='$1'";
      $sMatch       = preg_replace($sPattern, $sReplacement, $sMatch, -1, $iCount);

      // etc: async -> async='async'
      if ($iCount === 0) {
        $sMatch .= "='$sMatch'";
      }

      $sAttr .= $sMatch . ' ';
    }

    // Original URL
    if ($sAttr !== '') {
      $sUrl = trim($aMatch[1][0] . "' $sAttr");

      // Remove quote
      if (substr($sUrl, -1) === "'") {
        $sUrl = substr($sUrl, 0, -1);
      }
    }

    return $sUrl;
  }

  public function recursiveArraySearch($needle, $haystack) {
    foreach ($haystack as $key => $value) {
      $current_key = $key;
      if ($needle === $value or (is_array($value) && $this->recursiveArraySearch($needle, $value) !== false)) {
        return $current_key;
      }
    }
    return false;
  }

  public function getCurrentURL() {
    $pageURL = 'http';
    if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
    $pageURL .= "://";
    $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
    return $pageURL;
  }

  // HTML Minifier
  public function minifyHTML($sContent) {

    return $sContent;
  }

  // CSS Minifier
  public function minifyCSS($sContent) {

    return $sContent;
  }

  // JavaScript Minifier
  public function minifyJS($sContent) {
    
    return $sContent;
  }
}
