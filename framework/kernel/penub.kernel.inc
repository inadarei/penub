<?php
/**
* Kernel class is responsible for bootstrapping and orchestrating an HTTP request.
*/

require (dirname(__FILE__) . '/penub.util.inc');

class PenubKernel {
  
  private $page_load_started;
  private static $module_manager;
    
  public function __construct() {

    if (PENUB_SHOW_METRICS) {
      $this->page_load_started = microtime(True);
    }

  }

  public function run() {
    require (dirname(__FILE__) . '/module.manager.inc');
    self::$module_manager = new PenubModuleManager();
    self::$module_manager->dispatch('process_http_request');

    if (PENUB_SHOW_METRICS) {
      $this->print_metrics();
    }
  }

  public static function module_manager() {
    return self::$module_manager;
  }

  private function print_metrics() {
    $metrics[] = sprintf(dt('Page loaded in %.6f seconds'), microtime(True) - $this->page_load_started);
    penub_error($metrics);    
  }
   
  
}

class PenubException extends Exception {}