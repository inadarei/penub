<?php

require (dirname(__FILE__) .'/uroute.lib.php');

class PenubURLRouterService extends URoute_Service {
  
  private $kernel;
  
  public function __construct($endpoint, $kernel) {
    $this->kernel = $kernel;
    parent::__construct($endpoint);
  }
  
  public function service() {
    $routes = $this->kernel->module_manager->dispatch('routes', array());
    foreach ($routes as $path => $route) {
      $route['path'] = $path;
      $this->addRoute($route);
    }
  }
  
  public function error($exception) {
  }
  
}

class PenubURLRouter extends PenubModule implements URoute_Constants {
  function processHTTPRequest() {
    new PenubURLRouterService(
      dirname(PENUB_KERNEL_PATH),
      $this->kernel
    );
  }
}