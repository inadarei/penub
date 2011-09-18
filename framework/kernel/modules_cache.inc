<?php

interface iPenubModulesCache {
}

/**
 * Factory method to get appropriate modulescache implementation
 */
class PenubModulesCacheFactory {
  /**
   * @param $provider_type
   *  type of modules cache to return. Driven by PENUB_APP_CACHE_PROVIDER setting in environments/__base_config.php
   *  of environments/:environment.php if overridden.
   *
   * @return iPenubModulesCache
   *  an instance of a modules cache provider object
   */
  function getModulesCache($provider_type) {
    switch ($provider_type) {
      
    }
  }
}