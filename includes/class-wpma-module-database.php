<?php
// Blocking access direct to the plugin
defined('WPMA_BASE') or die('No script kiddies please!');

class WPMA_Database {
  public function __construct(&$WPMA) {
    $this->WPMA = &$WPMA;
  }
}