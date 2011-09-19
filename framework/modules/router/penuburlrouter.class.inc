<?php

require (dirname(__FILE__) .'/uroute.lib.php');


class PenubURLRouter extends PenubModule implements URoute_Constants {
  function process_http_request() {
    new PenubURLRouterService(
      dirname(PENUB_FRAMEWORK_PATH)
    );
  }
}


class PenubURLRouterService extends URoute_Service {
  
  public function __construct($endpoint) {
    parent::__construct($endpoint);
  }
  
  public function service() {
    $kernel = penub_kernel();
    $routes = PenubKernel::module_manager()->dispatch('routes', array());
    foreach ($routes as $path => $route) {
      $route['path'] = $path;
      $this->addRoute($route);
    }
  }
  
  public function error($exception) {
  }
  
}

