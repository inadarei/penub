<?php
/**
* Kernel class is responsible for bootstrapping and orchestrating a request.
*/

require (PENUB_KERNEL_PATH     . '/core/penub.util.inc');
require (PENUB_LIB_FOLDER_PATH . '/spyc/spyc.php');

class PenubKernel {
  
  private $http_server;
  private $http_get;
  private $http_post;
  private $http_files;
  private $http_request;

  private $module_manager;
  
  private $page_load_started;
    
  function __construct() {
    if (PENUB_SHOW_METRICS) {
      $this->page_load_started = microtime(True);
    }
    
    require (PENUB_KERNEL_PATH . '/core/module.manager.inc');    
    $this->module_manager = new PenubModuleManager($this);
  } 
  
  function run() {
    $req = fetch_request_object_from_http($this);
        
    $this->module_manager->dispatch('processHTTPRequest');
    
    if (PENUB_SHOW_METRICS) {
      $this->printMetrics();
    }
  }
  
  private function printMetrics() {
    $endtime = microtime(True);
    
    $metrics[] = sprintf(dt('Page loaded in %.6f seconds'), $endtime - $this->page_load_started);
        
    penub_error($metrics);    
  }
   
  
}
