<?php

class PenubModuleManager {
  
  var $modules;
  var $modules_conf_file;
  
  function __construct() {
    $search_locations = array( PENUB_KERNEL_PATH  . '/modules', 
                               PENUB_CONTRIB_PATH . '/modules', 
                               PENUB_CUSTOM_CODE_PATH . '/modules');
    
    if (!file_exists(PENUB_RUNTIMECONF_FOLDER_PATH)) {
      mkdir(PENUB_RUNTIMECONF_FOLDER_PATH);
    }
    
    $this->modules_conf_file = PENUB_RUNTIMECONF_FOLDER_PATH . '/modules.conf';
    
    if (file_exists($this->modules_conf_file)) {
      $this->modules = Spyc::YAMLLoad($this->modules_conf_file);
    } else {                          
      $this->findModules($search_locations);
      $yaml = Spyc::YAMLDump($this->modules);
      file_put_contents($this->modules_conf_file, $yaml, LOCK_EX);      
    }
  }
  
  function dispatch($action, $kernel) {
    penub_error($this->modules);
    penub_log($action);
  } 
  
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
            }
              
          }
        }
      }
    }

  }
  
}