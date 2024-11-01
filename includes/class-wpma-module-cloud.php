<?php
// Blocking access direct to the plugin
defined('WPMA_BASE') or die('No script kiddies please!');

use Nette\Caching\Cache;

class WPMA_Cloud {

  ///////////// Call WPMACore /////////////
  public function __construct(&$WPMA) {
    $this->WPMA = &$WPMA;
  }

  ///////////// Token /////////////
  public function checkToken() {
    $oOptions = [
      'method'  => 'POST',
      'error'   => true,
      'nobody'  => true,
      'header'  => true,
      'timeout' => 3,
      'follow'  => false,
      'url'     => esc_url_raw(WPMA_SERVER),
      'data'    => [
        'version' => WPMA_VERSION,
        'token'   => $this->WPMA->setting->getOption('WPMA_TOKEN'),
        'go'      => 'checkToken',
        // 'debug'   => true,
      ],
    ];

    // Use cache
    $sKeyCache = md5(serialize($oOptions));
    $aResponse = $this->WPMA->cache->load($sKeyCache);

    // Check cache
    if (empty($aResponse)) {
      $aResponse = $this->WPMA->tool->getContentURL($oOptions);
      
      // Time to keep a cache
      $iGlobalCacheTime = $this->WPMA->setting->getOption('WPMA_CACHE_GLOBAL');

      // Save cache
      if ($iGlobalCacheTime > 0) {
        $this->WPMA->cache->save($sKeyCache, $aResponse, [
          Cache::EXPIRE => $iGlobalCacheTime.' seconds',
        ]);
      }
    }

    if ($oOptions['data']['debug']) {
      bdump($aResponse);
    }

    if (!empty($aResponse['error'])) {
      define('WPMA_TOKEN_VALID', false);
      return false;
    }

    $sToken = $aResponse['content'];

    if (!empty($aResponse['header'])) {
      $sHTTPCode = $aResponse['header']['http_code'];
    }

    $validToken = stripos($sHTTPCode, '200 OK') !== false ? true : false;
    define('WPMA_TOKEN_VALID', $validToken);
  }

  public function generateToken(&$sToken = '') {
    if (WPMA_TOKEN_VALID === false) {
      $oOptions = [
        'method'  => 'POST',
        'error'   => true,
        'header'  => true,
        'timeout' => 3,
        'follow'  => false,
        'url'     => esc_url_raw(WPMA_SERVER),
        'data'    => [
          'version' => WPMA_VERSION,
          'go'      => 'generateToken',
          //'debug'   => true,
        ],
      ];

      $aResponse = $this->WPMA->tool->getContentURL($oOptions);

      if ($oOptions['data']['debug']) {
        bdump($aResponse);
      }

      if (!empty($aResponse['error'])) {
        $sToken = $aResponse['error'];
        return false;
      }

      $sToken = $aResponse['content'];

      // Save new token
      $this->WPMA->setting->setOption('WPMA_TOKEN', $sToken);
    } else {
      $sToken = $this->WPMA->setting->getOption('WPMA_TOKEN');
    }
  }

  ///////////// Cloud Page /////////////
  public function getThemeData($sID = '', $aData = []) {
    $aListTheme = [];

    if (WPMA_TOKEN_VALID) {
      $oOptions = [
        'method' => 'POST',
        'error'  => true,
        'url'    => esc_url_raw(WPMA_SERVER),
        'data'   => [
          'version' => WPMA_VERSION,
          'token'   => $this->WPMA->setting->getOption('WPMA_TOKEN'),
          'go'      => 'getThemeData',
          'id'      => $sID,
          'data'    => serialize($aData),
          // 'debug' => true,
        ],
      ];

      // Use cache
      $sKeyCache = md5(serialize($oOptions));
      $aResponse = $this->WPMA->cache->load($sKeyCache);

      // Check cache
      if (empty($aResponse)) {
        $aResponse = $this->WPMA->tool->getContentURL($oOptions);

        // Time to keep a cache
        $iGlobalCacheTime = $this->WPMA->setting->getOption('WPMA_CACHE_GLOBAL');

        // Save cache
        if ($iGlobalCacheTime > 0) {
          $this->WPMA->cache->save($sKeyCache, $aResponse, [
            Cache::EXPIRE => $iGlobalCacheTime.' seconds',
          ]);
        }
      }

      if ($oOptions['data']['debug']) {
        bdump($aResponse);
      }

      if (!empty($aResponse['error'])) {
        return false;
      }

      $aListTheme = json_decode($aResponse['content'], true);
    }

    return $aListTheme === null ? [] : $aListTheme;
  }

  ///////////// Cloud Function /////////////
  public function downloadTheme($sID) {
    $aResponse = $this->getThemeData($sID, ['download' => 'latest']);
    return $aResponse;
  }

  public function updateTheme($sID) {
    $sCurrentVersion = $this->WPMA->tool->getThemeData($sID, 'version');
    $aResponse       = $this->getThemeData($sID, ['update' => $sCurrentVersion]);
    return $aResponse;
  }

  public function purchaseTheme($sID) {
    $aResponse = $this->getThemeData($sID, ['purchase' => 'url']);
    return $aResponse;
  }
}