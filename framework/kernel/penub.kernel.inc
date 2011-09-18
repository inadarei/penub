<?php
/**
* Kernel class is responsible for bootstrapping and orchestrating an HTTP request.
*/

require (dirname(__FILE__) . '/penub.util.inc');

class PenubKernel {
  
  private $page_load_started;
  private $module_manager;
    
  function __construct() {

    if (PENUB_SHOW_METRICS) {
      $this->page_load_started = microtime(True);
    }

  }

  function run() {
    require (dirname(__FILE__) . '/module.manager.inc');
    $this->module_manager = new PenubModuleManager();
    $this->module_manager->dispatch('process_http_request');

    if (PENUB_SHOW_METRICS) {
      $this->print_metrics();
    }
  }

  public function get_module_manager() {
    return $this->module_manager;
  }

  public function print_metrics() {
    $endtime = microtime(True);
    
    $metrics[] = sprintf(dt('Page loaded in %.6f seconds'), $endtime - $this->page_load_started);
        
    penub_error($metrics);    
  }
   
  
}

class PenubException extends Exception {}