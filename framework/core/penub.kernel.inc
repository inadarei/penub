<?php
/**
* Kernel class is responsible for bootstrapping and orchestrating a request.
*/

require (PENUB_KERNEL_PATH     . '/core/penub.util.inc');
require (PENUB_LIB_FOLDER_PATH . '/spyc/spyc.php');


class PenubKernel {
  
  var $http_server;
  var $http_get;
  var $http_post;
  var $http_files;  
  var $http_request;

  var $module_manager;
  
  var $page_load_started;
    
  function __construct() {
    if (PENUB_SHOW_METRICS) {
      $this->page_load_started = microtime(True);
    }
    
    require (PENUB_KERNEL_PATH . '/core/module.manager.inc');    
    $this->module_manager = new PenubModuleManager($this);
  } 
  
  function run() {
    $req = fetch_request_object_from_http();    
    
    $this->http_server  = $req->http_server;  
    $this->http_get     = $req->http_get;     
    $this->http_post    = $req->http_post;    
    $this->http_files   = $req->http_files;   
    $this->http_request = $req->http_request; 
    
    $this->module_manager->dispatch('processHTTPRequest');
    
    if (PENUB_SHOW_METRICS) {
      $this->printMetrics();
    }
  }
  
  private function printMetrics() {
    $endtime = microtime(True);
    
    $metrics[] = sprintf(dt('Page loaded in %d seconds'), $endtime - $this->page_load_started);
        
    penub_error($metrics);    
  }
   
  
}
