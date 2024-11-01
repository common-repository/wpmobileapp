<?php
/**
 * Plugin Name: WPMA
 * Description: Wordpress Mobile Web App
 * Version: 1.0
 * Text Domain: wpma
 * Domain Path: /languages/
 * License: GPLv2 or later
 */

// Blocking access direct to the plugin
defined('ABSPATH') or die('No script kiddies please!');

// Blocking called direct to the plugin
defined('WPINC') or die('No script kiddies please!');

// Init path plugin
defined('DS') ? null : define('DS', DIRECTORY_SEPARATOR);
define('WPMA_BASE', realpath(plugin_dir_path(__FILE__)) . DS);
define('WPMA_URL', plugin_dir_url(__FILE__));

// Init core MVP
require_once WPMA_BASE . 'vendor' . DS . 'autoload.php';
require_once WPMA_BASE . 'includes' . DS . 'wpma-core.php';

// Register Setting
$WPMA->setting->registerGeneralSetting();

// Register Debuger
$WPMA->setting->registerDebuger();

// Event listener
register_activation_hook(__FILE__, [$WPMA->control, 'eventActivatePlugin']);
register_deactivation_hook(__FILE__, [$WPMA->control, 'eventDeactivatePlugin']);

// Init Lang
$WPMA->setting->initLanguage();

// Check token
$WPMA->cloud->checkToken();

// Register global script
add_action('wp_loaded', [$WPMA->setting, 'registerGlobalScript']);

// Register admin script
add_action('admin_enqueue_scripts', [$WPMA->setting, 'registerAdminGeneralScript']);
add_action('admin_enqueue_scripts', [$WPMA->setting, 'registerAdminManagerThemeScript']);

// Register admin style
add_action('admin_enqueue_scripts', [$WPMA->setting, 'registerAdminStyle']);

// Create setting menu plugin
$WPMA->admin->addMenuSetting();

// Register public scripts
add_action('wp_enqueue_scripts', [$WPMA->setting, 'registerPublicScript']);

// Register public style
add_action('wp_enqueue_scripts', [$WPMA->setting, 'registerPublicStyle']);

// Check plugin enable
if ($WPMA->setting->getOption('WPMA_ENABLE_CORE') === ENABLE && WPMA_TOKEN_VALID) {

  // Init Template
  $WPMA->template->initTemplate();

  // Init Customizer Template
  $WPMA->template->initCustomizer();

  // Add shortcode
  $WPMA->setting->registerShortCode();

  // Add rule request url
  $WPMA->control->ruleRequest();

  // Add WPMA on Site
  $WPMA->control->addWPMA();
} else {
  // Remove session template
  unset($_SESSION["WPMA_CURRENT_TEMPLATE"]);

  add_action('admin_notices', [$WPMA->admin, 'showAdminNotification']);
}