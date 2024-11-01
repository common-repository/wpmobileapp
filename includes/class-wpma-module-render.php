<?php
// Blocking access direct to the plugin
defined('WPMA_BASE') or die('No script kiddies please!');

class WPMA_Render {

  ///////////// Call WPMACore /////////////
  public function __construct(&$WPMA) {
    $this->WPMA = &$WPMA;
  }

  ///////////// Show UI ////////////
  public function showButton($args) {
    if (!empty($args['callback'])) {
      call_user_func_array($args['callback'], [ & $args['value']]);
      unset($args['callback']);
    }

    // Config Attr
    foreach ($args as $sName => $nValue) {
      if ($sName === 'description') {
        $sDescription = $nValue;
      } elseif ($sName === 'icon') {
        $sIcon = $nValue;
      } elseif ($sName === 'value') {
        $sButton = $nValue;
      } elseif ($sName === 'btnclass') {
        $sAttr .= "class=\"" . esc_attr($nValue) . "\" ";
      } else {
        $sAttr .= "$sName=\"" . esc_attr($nValue) . "\" ";
      }
    }

    $sHTML = <<<HTML
    <i class="material-icons tooltipped prefix" data-position="top" data-delay="50" data-tooltip="$sDescription">help</i>
    <a $sAttr><i class="material-icons left">$sIcon</i>$sButton</a>
HTML;

    echo $sHTML;
  }

  public function showInput($args) {
    if (!empty($args['callback'])) {
      call_user_func_array($args['callback'], [ & $args['value']]);
      unset($args['callback']);
    }

    $sDescription = $sType = $sAttr = $bReadonly = $sCheked = '';

    // Config Attr
    foreach ($args as $sName => $nValue) {
      if ($sName === 'type') {
        $sType = $nValue;
      }

      if ($sName === 'description') {
        $sDescription = $nValue;
      } elseif ($sName === 'readonly') {
        $bReadonly = $nValue === true ? 'readonly' : '';
      } elseif ($sName === 'type' && $sType === 'checkbox') {
        $sCheked = checked($args['value'], 1, false);
        $sAttr .= "type=\"checkbox\" ";
      } elseif ($sName === 'type' && $sType === 'chips') {
        $sAttr .= "type=\"hidden\" ";
      } else {
        $sAttr .= "$sName=\"" . esc_attr($nValue) . "\" ";
      }
    }

    ?>
    <div class="input-field<?php echo $sType == 'checkbox' ? '-cstm' : ''; ?>">
      <i class="material-icons tooltipped prefix" data-position="top" data-delay="50" data-tooltip="<?php echo $sDescription; ?>">help</i>
    <?php if ($sType == 'checkbox') {?>
      <div class="switch">
        <label>
            <input <?php echo $sAttr; ?> <?php echo $bReadonly; ?> <?php echo $sCheked; ?>/>
          <span class="lever"></span>
        </label>
      </div>
      <?php } else if ($sType == 'chips') {?>
      <input <?php echo $sAttr; ?> <?php echo $bReadonly; ?>/>
      <div id="<?php echo $args['name']; ?>" class="chips chips-initial chips-placeholder chips-autocomplete"></div>
      <?php } else {?>
        <input <?php echo $sAttr; ?> <?php echo $bReadonly; ?>/>
      <?php }?>
    </div>
    <?php
}

  public function showSelect($args) {
    $sOptions = '';
    foreach ($args['option'] as $sValue => $sName) {
      $sSelect = $args['value'] == $sValue ? 'selected' : '';
      $sOptions .= "<option value=\"$sValue\" $sSelect>$sName</option>";
    }

    // Config Attr
    foreach ($args as $sName => $nValue) {
      if ($sName === 'description') {
        $sDescription = $nValue;
      } else {
        $sAttr .= "$sName=\"" . esc_attr($nValue) . "\" ";
      }
    }

    ?>
    <div class="input-field">
      <i class="material-icons tooltipped prefix" data-position="top" data-delay="50" data-tooltip="<?php echo $sDescription; ?>">help</i>
      <select class="wpma-select" name="<?php echo $args['name']; ?>" <?php echo $sAttr; ?>><?php echo $sOptions; ?></select>
    </div>
    <?php
}

  public function showImage($args) {

    // Config Attr
    foreach ($args as $sName => $nValue) {
      if ($sName === 'description') {
        $sDescription = $nValue;
      } elseif ($sName === 'value') {
        $sAttr .= "src=\"" . esc_attr($nValue) . "\" ";
      } else {
        $sAttr .= "$sName=\"" . esc_attr($nValue) . "\" ";
      }
    }

    $sValue = $args['value'] !== '' ? 'value="' . $args['value'] . '"' : '';

    ?>
    <img data="<?php echo $args['name']; ?>" <?php echo $sAttr; ?>>
    <input type="hidden" name="<?php echo $args['name']; ?>" id="<?php echo $args['name']; ?>" <?php echo $sValue; ?>/>
    <p class="description"><?php echo $sDescription; ?></p>
    <?php
}

  public function showLink($args) {
    $sID  = $args['id'] !== '' ? 'id="' . $args['id'] . '"' : '';
    $sRel = $args['rel'] !== '' ? 'rel="' . $args['rel'] . '"' : '';
    echo "<link $sID $sRel href=\"{$args['href']}\">";
  }

  public function showBadges($args) {
    ?>
    <ul class="collapsible" data-collapsible="accordion">
      <li>
        <div class="collapsible-header">
          <i class="material-icons">filter_drama</i>
          First
          <span class="new badge">4</span></div>
        <div class="collapsible-body"><p>Lorem ipsum dolor sit amet.</p></div>
      </li>
      <li>
        <div class="collapsible-header">
          <i class="material-icons">place</i>
          Second
          <span class="badge">1</span></div>
        <div class="collapsible-body"><p>Lorem ipsum dolor sit amet.</p></div>
      </li>
    </ul>
    <?php
}

  ///////////// Show WPMA Admin Page ////////////
  public function showSettings($aMenu) {
    if (empty($aMenu)) {
      echo '<h2 class="center-align">' . __('Misconfiguration', 'wpma') . '</h2>';
      return;
    }

    // Get setting menu
    $aMenuSetting = $this->WPMA->setting->menu;

    // Get setting page
    $aPage = $this->WPMA->setting->page;

    // Remove menu if disable
    foreach ($aPage as $sPage => $aOption) {
      if (array_search($aOption['menu'], $aMenu) === false || $aMenuSetting[$aOption['menu']] === false) {
        unset($aPage[$sPage]);
      }
    }

    // Sort by $aMenu order
    uasort($aPage, function ($a, $b) use ($aMenu) {
      foreach ($aMenu as $sMenu) {
        if ($a['menu'] == $sMenu) {
          return 0;
          break;
        }

        if ($b['menu'] == $sMenu) {
          return 1;
          break;
        }
      }
    });

    $sClassActive = 'class="active"';

    ?>
    <div class="wrap">
      <?php settings_fields('wpma-settings-group');?>
      <!-- notice -->
      <div class="row">
        <div class="col s12">
          <?php settings_errors();?>
        </div>
       </div>
      <!-- panel -->
      <div class="row">
        <div class="col s1"></div>
        <div class="col s10">
          <ul class="tabs">
            <?php foreach ($aPage as $sPage => $aData) {?>
            <li class="tab col">
              <a href="#<?php echo $aData['hash']; ?>" <?php echo '#' . $aData['hash'] === $_COOKIE['wpma_tab_setting'] ? $sClassActive : ''; ?>><i class="material-icons"><?php echo $aData['icon']; ?></i><?php echo $aData['title']; ?></a>
            </li>
            <?php }?>
          </ul>
          <div class="tabs-content">
            <form action="options.php" method="post" id="wpmaOptionsForm">
              <?php foreach ($aPage as $sPage => $aData) {?>
              <div id="<?php echo $aData['hash']; ?>">
                <?php do_settings_sections($sPage);?>
              </div>
              <?php }?>
              <button class="waves-effect waves-light btn"><?php echo __('Save Settings', 'wpma'); ?></button>
            </form>
          </div>
        </div>
        <div class="col s1"></div>
      </div>
    </div>
    <?php
}

  ///////////// Show Data ////////////

  public function addHeadForServiceWorker() {
    echo '<meta name="theme-color" content="' . $this->WPMA->setting->getOption('WPMA_MF_THEME_COLOR') . '">';
    echo '<meta name="background-color" content="' . $this->WPMA->setting->getOption('WPMA_MF_BACKGROUND_COLOR') . '">';
  }

  public function setETagHeader($sURL, $sContent = null) {
    if ($sContent !== null) {
      header('ETag: "' . md5($sContent) . '"');
    } else {
      header('ETag: "' . md5(current_time('timestamp') . $sURL) . '"');
    }

    // header('Last-Modified: ' . date('r', current_time('timestamp')));
    // header('Expires: '. date('r', current_time('timestamp') + 15));
  }

  // Convert to JSON
  public function json($aData) {
    $sContent = json_encode($aData, true);

    header_remove();
    header('Cache-Control: max-age=0, must-revalidate', true);
    header('Content-Type: application/json;charset=UTF-8', true);

    echo $sContent;

    exit;
  }

  // Start capture content output
  public function startBuffer() {
    if ($this->WPMA->setting->getOption('WPMA_ENABLE_CONTENT_COMPRESSION') === ENABLE) {
      ob_start('ob_gzhandler');
    } else {
      ob_start();
    }
  }

  // Start capture content output
  public function endBuffer() {
    $sOutputFinal = '';

    $aLevels = ob_get_level();
    for ($i = 0; $i < $aLevels; $i++) {
      $sOutputFinal .= ob_get_clean();
    }
    
    // Apply any filters to the final output
    echo apply_filters('final_output', $sOutputFinal, 'text/html');
  }

  // Content optimization
  public function compression($sContent, $sType) {

    if (strpos($sType, 'text/html') !== false) {
      $sContent = $this->WPMA->tool->minifyHTML($sContent);

    } elseif (strpos($sType, 'text/css') !== false) {
      $sContent = $this->WPMA->tool->minifyCSS($sContent);

    } elseif (strpos($sType, 'application/javascript') !== false) {
      $sContent = $this->WPMA->tool->minifyJS($sContent);
    }

    bdump('Before optimization: ' . strlen($sContent) . ' byte');
    $sContent = gzencode($sContent, 6);
    bdump('After optimization: ' . strlen($sContent) . ' byte');

    header('Accept-Encoding: gzip, deflate', true);
    header('Content-Encoding: gzip', true);

    return $sContent;
  }
}