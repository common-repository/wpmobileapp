<?php
// Blocking access direct to the plugin
defined('WPMA_BASE') or die('No script kiddies please!');

use Nette\Caching\Cache;

class WPMA_Cache {
  private $store;

  ///////////// Call WPMACore /////////////
  public function __construct(&$WPMA) {
    $this->WPMA = &$WPMA;

    // Check cache dir
    $sPathCache = WPMA_BASE . 'cache';
    if (!realpath($sPathCache)) {
      if (!wp_mkdir_p($sPathCache)) {
        echo "<div class=\"notice notice-warning is-dismissible\"><p>" . __('You need to create a "cache" folder in the WPMA plugin', 'wpma') . "</p></div>";
        return;
      }
    }

    $sPathCache = realpath($sPathCache);
    if ($sPathCache) {
      $oStorage    = new Nette\Caching\Storages\FileStorage($sPathCache);
      $this->store = new Cache($oStorage);
    }
  }

  public function __call($sName, $aArguments) {
    return call_user_func_array([$this->store, $sName], $aArguments);
  }

  public function getKey() {
    // Get current url page
    $sCurrentUrlPage = $this->WPMA->tool->getCurrentURL();

    //Get current user
    $oUser = wp_get_current_user();

    // Hash data
    $sKey = md5(md5($sCurrentUrlPage) . $oUser->ID);

    return $sKey;
  }

  public function saveCurrentPageCache($sContent) {
    // Get key cache
    $sKey = $this->getKey();

    // Get time
    $iCacheTime = $this->WPMA->setting->getOption('WPMA_CACHE_APPSHELL');

    $this->save($sKey, $sContent, [
      Cache::EXPIRE => $iCacheTime . ' seconds',
    ]);

    return $sContent;
  }
}