<?php
// Blocking access direct to the plugin
defined('WPMA_BASE') or die('No script kiddies please!');

// Init path MVP
define('WPMA_INCLUDES', WPMA_BASE . 'includes' . DS);

define('WPMA_PATH_TEMPLATE', WPMA_BASE . 'public' . DS . 'templates' . DS);
define('WPMA_URL_TEMPLATE', WPMA_URL . 'public/templates/');

define('WPMA_SERVER', 'https://beta.wpmobiapp.com/WPMA_Cloud.php');
define('WPMA_VERSION', '1.0');

//
define('ENABLE', 1);
define('DISABLE', 0);

// Require MODULE
require_once WPMA_INCLUDES . 'class-wpma-module-admin.php';
require_once WPMA_INCLUDES . 'class-wpma-module-api.php';
require_once WPMA_INCLUDES . 'class-wpma-module-cloud.php';
require_once WPMA_INCLUDES . 'class-wpma-module-control.php';
require_once WPMA_INCLUDES . 'class-wpma-module-cache.php';
require_once WPMA_INCLUDES . 'class-wpma-module-database.php';
require_once WPMA_INCLUDES . 'class-wpma-module-render.php';
require_once WPMA_INCLUDES . 'class-wpma-module-setting.php';
require_once WPMA_INCLUDES . 'class-wpma-module-template.php';
require_once WPMA_INCLUDES . 'class-wpma-module-tool.php';

$WPMA = new WPMACore;

class WPMACore {
  public function __construct() {
    $this->admin    = new WPMA_Admin($this);
    $this->api      = new WPMA_API($this);
    $this->cloud    = new WPMA_Cloud($this);
    $this->control  = new WPMA_Control($this);
    $this->cache    = new WPMA_Cache($this);
    $this->database = new WPMA_Database($this);
    $this->render   = new WPMA_Render($this);
    $this->setting  = new WPMA_Setting($this);
    $this->template = new WPMA_Template($this);
    $this->tool     = new WPMA_Tool($this);
  }
}