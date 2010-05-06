<?php

class PenubModuleManager {
  
  function __construct() {
    
  }
  
  function dispatch($action, $kernel) {
   penub_log($action);
  } 
  
  private function findModules() {
    $search_locations = array();
  }
  
}