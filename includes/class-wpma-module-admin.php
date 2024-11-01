<?php
// Blocking access direct to the plugin
defined('WPMA_BASE') or die('No script kiddies please!');

class WPMA_Admin {

  ///////////// Call WPMACore /////////////
  public function __construct(&$WPMA) {
    $this->WPMA = &$WPMA;
  }

  public function addMenuSetting() {
    if (is_admin()) {      
      add_action('admin_menu', [$this, 'createMenu']);
    }
  }

  ///////////// Menu Admin /////////////
  public function createMenu() {
    // Create top-level token menu
    add_menu_page(__('WPMA', 'wpma'), __('WPMA', 'wpma'), 'manage_options', 'wpma-menus');

    // Create submenu
    add_submenu_page('wpma-menus', __('Settings', 'wpma'), __('Settings', 'wpma'), 'manage_options', 'wpma-menus', [$this, 'parseAdminMenu']);
    //add_submenu_page('wpma-menus', __('Themes', 'wpma'), __('Themes', 'wpma'), 'manage_options', 'wpma-manager-themes', [$this, 'showThemesManager']);

    // Notification
    add_action('admin_notices', [$this, 'showAdminNotification']);

    // Call register settings function
    add_action('admin_init', [$this, 'registerSettings']);
  }

  public function parseAdminMenu() {
    // Order tab setting
    $aMenu = ['wpma-setting', 'wpma-theme', 'wpma-plugin', 'wpma-token'];

    if (WPMA_TOKEN_VALID === false) {
      $this->WPMA->cloud->generateToken();
    }

    $this->WPMA->render->showSettings($aMenu);
  }

  public function registerSettings() {

    // Register section
    $aSectionSetting = $this->WPMA->setting->section;
    foreach ($aSectionSetting as $sKey => $aValue) {
      add_settings_section($sKey, $aValue['title'], null, $aValue['page']);
    }

    // Register setting
    $aSetting = $this->WPMA->setting->option;

    foreach ($aSetting as $sKey => $aValue) {
      // Check Field
      $sCallback = $aValue['callback'];
      if (empty($sCallback)) {
        continue;
      }

      $sTitle        = !empty($aValue['title']) ? $aValue['title'] : '';
      $sSection      = $aValue['section'];
      $aArgs         = $aValue['args'];
      $aArgs['name'] = $sKey;
      $sPage         = $aSectionSetting[$sSection]['page'];

      register_setting('wpma-settings-group', $sKey);

      add_settings_field($sKey, $sTitle, [$this->WPMA->render, $sCallback], $sPage, $sSection, $aArgs);
    }
  }

  ///////////// Ajax /////////////
  public function ajaxOptions() {
    $aFilter = [
      'options' => [
        'flags' => FILTER_FORCE_ARRAY,
        'name'  => FILTER_SANITIZE_ENCODED,
        'value' => FILTER_SANITIZE_ENCODED,
      ],
    ];

    $aValidate = filter_input_array(INPUT_POST, $aFilter);

    if (empty($aValidate)) {
      http_response_code(403);
      return;
    }

    $aOption = $aValidate['options'];
    foreach ($aOption as $sOption => $aValue) {
      $aValue['value'] = stripslashes($aValue['value']);
      $isUpdated       = $this->WPMA->setting->setOption($aValue['name'], $aValue['value']);
    }
  }

  public function ajaxButton() {
    $sAction = filter_input(INPUT_POST, 'go', FILTER_SANITIZE_ENCODED);

    if (empty($sAction)) {
      http_response_code(403);
      return;
    }

    switch ($sAction) {
    case 'runRealtimeServer':
      // Need checkRealtimeServer first
      $this->WPMA->realtime->runRealtimeServer();
      break;
    }
  }

  public function ajaxManagerTheme() {
    $sAction = filter_input(INPUT_POST, 'go', FILTER_SANITIZE_ENCODED);

    if (empty($sAction)) {
      http_response_code(403);
      return;
    }

    $sThemeID = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_ENCODED);

    switch ($sAction) {

    case 'downloadTheme':

      $aResult = $this->WPMA->cloud->downloadTheme($sThemeID);

      if (!empty($aResult[$sThemeID]['download'])) {

        $aResult['tmp'] = download_url($aResult[$sThemeID]['download']);

        if (empty($aResult['tmp']['errors'])) {
          $oZip             = new ZipArchive;
          $res              = $oZip->open($aResult['tmp']);
          $aResult['unzip'] = $res;
          if ($res === true) {
            $oZip->extractTo(WPMA_PATH_TEMPLATE);
            $oZip->close();
          }
        }
      }
      break;

    case 'updateTheme':

      $aResult = $this->WPMA->cloud->updateTheme($sThemeID);

      // just like download
      if (!empty($aResult[$sThemeID]['update'])) {

        $aResult['tmp'] = download_url($aResult[$sThemeID]['update']);

        if (empty($aResult['tmp']['errors'])) {
          $oZip             = new ZipArchive;
          $res              = $oZip->open($aResult['tmp']);
          $aResult['unzip'] = $res;
          if ($res === true) {
            $oZip->extractTo(WPMA_PATH_TEMPLATE);
            $oZip->close();
          }
        }
      }

      break;
    case 'purchaseTheme':

      $aResult = $this->WPMA->cloud->purchaseTheme($sThemeID);

      if (!empty($aResult['token'])) {
        $this->WPMA->setting->setOption('WPMA_TOKEN', $aResult['token']);
      }
      break;
    }
    $this->WPMA->render->json($aResult);
  }

  ///////////// Show Notication ////////////
  public function showAdminNotification() {
    $screen = get_current_screen();

    if ($screen->id === 'toplevel_page_wpma-menus' || $screen->id === 'wpma-settings_page_wpma-manager-theme') {
      // When disable core
      if ($this->WPMA->setting->getOption('WPMA_ENABLE_CORE') === DISABLE) {
        echo "<div class=\"notice notice-warning is-dismissible\"><p>" . __('WPMA is disable!', 'wpma') . "</p></div>";
      }

      // When not run on ssl
      if (stripos(WPMA_URL, 'https://') === false) {
        echo "<div class=\"notice notice-warning is-dismissible\"><p>" . __('Need setting up SSL on your website for Service Workers!', 'wpma') . "</p></div>";
      }

      // When disable session
      if (session_status() == PHP_SESSION_NONE || !session_id()) {
        echo "<div class=\"notice notice-warning is-dismissible\"><p>" . __('You need to use php session!', 'wpma') . "</p></div>";
      }
    }
  }

  public function showThemesManager() {
    if (WPMA_TOKEN_VALID === false || $this->WPMA->setting->getOption('WPMA_ENABLE_CORE') === DISABLE) {
      return false;
    }

    $aListCloudTheme = $this->WPMA->cloud->getThemeData();
    $aListLocalTheme = $this->WPMA->tool->getThemeData();

    $aListThemes = array_merge($aListLocalTheme, $aListCloudTheme);

    if (!empty($aListThemes[WPMA_CURRENT_TEMPLATE])) {
      $aListThemes[WPMA_CURRENT_TEMPLATE]['active'] = true;
    }
    $iCountThemes = count($aListThemes);

    ?><div class="wrap"><h1 class="wp-heading-inline"><?php echo __('Themes', 'wpma'); ?>
    <span class="title-count theme-count"><?php echo $iCountThemes; ?></span></h1>
    <form class="search-form">
      <label class="screen-reader-text" for="wp-filter-search-input"><?php echo __('Search Installed Themes', 'wpma'); ?></label>
      <input placeholder="<?php echo __('Search installed themes...', 'wpma'); ?>" type="search" aria-describedby="live-search-desc" id="wp-filter-search-input" class="wp-filter-search"/>
    </form>
    <hr class="wp-header-end"/>
    <div class="theme-browser rendered">
    <div class="themes wp-clearfix"><?php

    // Add data next, previous for button, not yet optimized code
    $aKeyTheme = array_keys($aListThemes);

    foreach ($aKeyTheme as $iKey => $aValue) {

      // Set this theme has new version
      if (version_compare($aListThemes[$aValue]['version'], $aListLocalTheme[$aValue]['version'], '>') === true && $aListLocalTheme[$aValue]['version'] !== null) {
        $aListThemes[$aValue]['update'] = true;
      }

      // Set this theme has installed
      if (!empty($aListThemes[$aValue]['cloud']) && !empty($aListLocalTheme[$aValue])) {
        unset($aListThemes[$aValue]['cloud']);
      }

      // Set this theme available to purchase
      if (!empty($aListCloudTheme[$aValue]['purchase']) && !empty($aListLocalTheme[$aValue])) {
        $aListThemes[$aValue]['purchase'] = true;
      }

      // Set url default screenshot
      if (file_exists(WPMA_PATH_TEMPLATE . $aValue . DS . 'screenshot.jpg')) {
        $aListThemes[$aValue]['screenshot'] = WPMA_URL_TEMPLATE . $aValue . '/screenshot.jpg';
      } else {
        $aListThemes[$aValue]['screenshot'] = WPMA_URL . 'public/assets/imgs/screenshot.jpg';
      }

      // Index
      $aListThemes[$aValue]['previous'] = $aKeyTheme[$iKey - 1];
      $aListThemes[$aValue]['next']     = $aKeyTheme[$iKey + 1];
    }

    // html
    foreach ($aListThemes as $sTheme => $aTheme) {
      $sButton = '';
      if ($aTheme['purchase'] && $aTheme['update']) {
        $sButton = "<a class='button update' data-id='" . $sTheme . "' aria-label='" . __('Update', 'wpma') . " " . $aTheme['theme_name'] . "'>" . __('Update', 'wpma') . "</a>";
      } elseif ($aTheme['purchase'] && $aTheme['cloud']) {
        $sButton = "<a class='button download' data-id='" . $sTheme . "' aria-label='" . __('Download', 'wpma') . " " . $aTheme['theme_name'] . "'>" . __('Download', 'wpma') . "</a>";
      } elseif (!$aTheme['purchase'] && $aTheme['cloud']) {
        $sButton = "<a class='button purchase' data-id='" . $sTheme . "' aria-label='" . __('Purchase', 'wpma') . " " . $aTheme['theme_name'] . "'>" . __('Purchase', 'wpma') . "</a>";
      } elseif (!$aTheme['active'] && !$aTheme['cloud']) {
        $sButton = "<a class='button activate' data-id='" . $sTheme . "' aria-label='" . __('Active', 'wpma') . " " . $aTheme['theme_name'] . "'>" . __('Active', 'wpma') . "</a>";
      } elseif ($aTheme['active']) {
        $sButton = "<a class='button customize' data-id='" . $sTheme . "' aria-label='" . __('Customize', 'wpma') . " " . $aTheme['theme_name'] . "'>" . __('Customize', 'wpma') . "</a>";
      }

      ?>
      <div class="theme<?php echo $aTheme['active'] ? ' active' : ''; ?>" id="<?php echo $sTheme; ?>"
      data-name="<?php echo htmlspecialchars($aTheme['theme_name']); ?>"
      data-screenshot="<?php echo $aTheme['screenshot']; ?>"
      data-author="<?php echo htmlspecialchars($aTheme['author']); ?>"
      data-author_uri="<?php echo htmlspecialchars($aTheme['author_uri']); ?>"
      data-version="<?php echo $aTheme['version']; ?>"
      data-description="<?php echo htmlspecialchars($aTheme['description']); ?>"
      data-tag="<?php echo htmlspecialchars($aTheme['tag']); ?>"
      data-is_active="<?php echo $aTheme['active'] ? 1 : 0; ?>"
      data-previous="<?php echo $aTheme['previous']; ?>"
      data-next="<?php echo $aTheme['next']; ?>"
      data-button="<?php echo htmlspecialchars($sButton); ?>">

      <div class="theme-screenshot">
        <img src="<?php echo $aTheme['screenshot']; ?>" alt=""/>
      </div>
      <span class="more-details"><?php echo __('Theme Details', 'wpma'); ?></span>
      <div class="theme-author">
        <?php echo $aTheme['author']; ?>
      </div>
      <div class="theme-id-container">
        <h2 class="theme-name">
        <?php

      if ($aTheme['active'] && !$aTheme['cloud']) {
        ?> <span><?php echo __('Active', 'wpma'); ?>:</span><?php

      }?>
        <?php echo $aTheme['theme_name']; ?>
        </h2>

        <div class="theme-actions">
          <?php echo $sButton; ?>
        </div>
      </div>
    </div>
    <?php }?>

        <div class="theme-overlay"
          data-text-previous="<?php echo __('Show previous theme', 'wpma'); ?>"
          data-text-next="<?php echo __('Show next theme', 'wpma'); ?>"
          data-text-close="<?php echo __('Close details dialog', 'wpma'); ?>"
          data-text-current="<?php echo __('Current Theme', 'wpma'); ?>"
          data-text-version="<?php echo __('Version', 'wpma'); ?>"
          data-text-by="<?php echo __('By', 'wpma'); ?>"
          data-text-tag="<?php echo __('Tag', 'wpma'); ?>"
          data-text-active="<?php echo __('Active', 'wpma'); ?>"
        >
        </div>
      </div>
    </div>
    <p class="no-themes"><?php echo __('No themes found. Try a different search.', 'pwp'); ?> </p>
    </div>
    <?php
}

}