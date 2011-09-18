<?php

interface iPenubModulesCache {
  function set();
  function get();
  function clear();
}

/**
 * Factory method to get appropriate modules cache implementation
 */
class PenubModulesCacheFactory {

  static private $instances;
  
  /**
   * @param $provider_type
   *  type of modules cache to return. Driven by PENUB_APP_CACHE_PROVIDER setting in environments/__base_config.php
   *  of environments/:environment.php if overridden.
   *
   * @return iPenubModulesCache
   *  an instance of a modules cache provider object
   */
  static function getModulesCache($provider_type) {
    if (empty(self::$instances) || !is_array(self::$instances)) {
      self::$instances = array();
    }

     $allowed_providers = array (
       'apc'   => 'PenubModulesCacheAPCProvider',
       'shmop' => 'PenubModulesCacheShmopProvider',
       'file'  => 'PenubModulesCacheFileProvider'
     );

    if (!array_key_exists($provider_type, $allowed_providers)) {
      throw new PenubException(dt("Invalid module cache provider requested"));
    }

    //-- Singleton
    if (!isset(self::$instances[$provider_type])) {
      $class_name = $allowed_providers[$provider_type];
      self::$instances[$provider_type] = new $class_name;
    }

    return self::$instances[$provider_type];
  }
}

/**
 * Modules Cache Provider that stores cache in APC shared memory
 */
class PenubModulesCacheAPCProvider {

  const MODULES_CACHE_KEY = 'penub_framework_modules_list';

  /**
   * @return mixed
   */
  function get() {
    $cc = apc_fetch(self::MODULES_CACHE_KEY);
    //print_r($cc);
    return unserialize($cc);
  }

  /**
   * @param $modules
   *  list of modules to store in cache
   * @return void
   */
  function set($modules) {
    $ret = apc_store(self::MODULES_CACHE_KEY, json_encode($modules), 10000);
  }

  function clear() {
    apc_delete(self::MODULES_CACHE_KEY);
  }
}

/**
 * Modules Cache Provider that stores cache on the filesystem
 * 
 */
class PenubModulesCacheFileProvider {

  const MODULES_CACHE_KEY = 'penub_framework_modules_list';

  private $module_conf_file;

  function __construct() {
    if (!file_exists(PENUB_RUNTIMECONF_FOLDER_PATH)) {
      mkdir(PENUB_RUNTIMECONF_FOLDER_PATH);
    }

    $this->modules_conf_file = PENUB_RUNTIMECONF_FOLDER_PATH . '/modules.conf';

  }

  /**
   * @return mixed
   */
  function get() {
    $contents = file_get_contents($this->modules_conf_file);
    return json_decode($contents, true);
  }

  /**
   * @param $modules
   *  list of modules to store in cache
   * @return void
   */
  function set($modules) {
    $yaml = json_encode($modules);
    file_put_contents($this->modules_conf_file, $yaml, LOCK_EX);
  }

  function clear() {
    unlink($this->modules_conf_file);
  }
}

/**
 * Modules Cache Provider that stores cache on the file system
 * @TODO needs to be actually implemented. SHMOP is very low-level, so kinda tricky
 */
class PenubModulesCacheShmopProvider {
  function get() {
    //@TODO
    return null;
  }

  function set() {
    //@TODO
  }

  function clear() {
    //@TODO
  }
}