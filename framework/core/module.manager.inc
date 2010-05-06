<?php

require (dirname(__FILE__) . '/module.class.inc');

class PenubModuleManager {
  
  var $kernel;
  var $modules;
  var $modules_conf_file;
  
  function __construct($kernel) {
  
    $this->kernel = $kernel;
    
    $search_locations = array(
      PENUB_KERNEL_PATH      . '/modules', 
      PENUB_CONTRIB_PATH     . '/modules', 
      PENUB_CUSTOM_CODE_PATH . '/modules'
    );
    
    if (!file_exists(PENUB_RUNTIMECONF_FOLDER_PATH)) {
      mkdir(PENUB_RUNTIMECONF_FOLDER_PATH);
    }
    
    $this->modules_conf_file = PENUB_RUNTIMECONF_FOLDER_PATH . '/modules.conf';
    
    if (file_exists($this->modules_conf_file) && (PENUB_CACHE_LEVEL != PENUB_CACHE_NONE)) {
      $this->modules = Spyc::YAMLLoad($this->modules_conf_file);
    } else {                          
      $this->findModules($search_locations);
      $this->checkEnabledStatusOfModules();
      $this->sortModulesByWeight();
      $yaml = Spyc::YAMLDump($this->modules);
      file_put_contents($this->modules_conf_file, $yaml, LOCK_EX);      
    }
  }
  
  function dispatch($action, $state = NULL) {
    //penub_error($this->modules);
    
    if (is_array($this->modules)) {

      foreach ($this->modules as $mod) {
        if (file_exists($mod['implementation']['filepath'])) {
          $className = $mod['implementation']['class'];
          
          //-- a trick to optimize performance and not using require_once
          if (!class_exists($className)) {
            require ($mod['implementation']['filepath']);
          }
          
          $modObj = new $className();
          $modObj->kernel = $this->kernel;
          
          if (method_exists($modObj, $action)) {
            call_user_func_array(array($modObj, $action), array(&$state));
          }
          
        }        
      }
      
      return $state;
    }
  } 
  
  /**
  * Remove explicitely disabled modules and modules that have broken dependencies.
  * @TODO needs to be implemented.
  */
  private function checkEnabledStatusOfModules() {
  }
  
  /**
  *  Sort modules by weight so that their execution sequence can be controlled if needed.
  *  @TODO needs to be implemented.
  */
  private function sortModulesByWeight() {
    
  }
  
  /**
  * Find all available modules (regardless of enabled/disabled status).
  */
  private function findModules($locations) {
    if (!is_array($locations)) {
      $locations = array($locations);
    }
      
    foreach ($locations as $loc) {
      $subfolders = glob("$loc/*", GLOB_ONLYDIR);
      if (is_array($subfolders)) {
        foreach ($subfolders as $sub) {
          $new_location = $loc . '/' . $sub;
          $this->findModules($sub);          
        }
      }
      
      $modulefiles = glob("$loc/*.module");
      if (is_array($modulefiles)) {
        foreach ($modulefiles as $modfile) {
          if (!is_dir($modfile) && is_readable($modfile)) {
            $modname = basename($modfile, '.module');
            //-- A trick to not use slow require_once
            $hook_config_function = $modname . "_config";
            if (!function_exists($hook_config_function)) {
              require ($modfile);
            }
            
            if (function_exists($hook_config_function)) {
              $this->modules[$modname] = $hook_config_function();
              if (empty($this->modules[$modname]['implementation']['filepath'])) {
                $this->modules[$modname]['implementation']['filepath'] = dirname($modfile) . '/' . strtolower($this->modules[$modname]['implementation']['class']) . '.class.inc';
              }
            }
              
          }
        }
      }
    }

  }
  
}