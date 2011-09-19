<?php

/* ============ ATTENTION =============
 *
 * Settings in this configuration file can only take scalar (integer, float, string, boolean) or null values.
 * If you need to create a more complex settings object, you need to encode it as a JSON string
 * and treat it as such. This limitation comes from the limitation of PHP's named constants:
 * http://us3.php.net/define
 *
 */

$penub_config_base = array (

  'PENUB_FRAMEWORK_PATH'      => PENUB_CODE_PATH . '/framework',
  'PENUB_CONTRIB_PATH'     => PENUB_CODE_PATH . '/pcontrib',
  'PENUB_CUSTOM_CODE_PATH' => PENUB_CODE_PATH . '/custom',

  'PENUB_ADDITIONAL_MODULE_LOCATIONS' => '', //JSON-encoded array of additional locations if needed

  'PENUB_FILES_FOLDER_PATH' => PENUB_WWW_PATH . '/files',

  'PENUB_LIB_FOLDER_PATH'   => PENUB_CODE_PATH . '/framework/libraries',

  'PENUB_LOG_ALL'     =>  255,
  'PENUB_LOG_NOTICE'  =>  1,
  'PENUB_LOG_DEBUG'   =>  2,
  'PENUB_LOG_ERROR'   =>  4,
  'PENUB_LOG_INFO'    =>  8,
  'PENUB_LOG_FATAL'   =>  16,
  'PENUB_LOG_PROFILE' =>  32, // Typically used only in production to provide real-time monitoring information.
  'PENUB_LOG_OFF'     =>  0,

  'PENUB_LOG_LEVEL'   =>  255, //Switch to PENUB_LOG_LEVEL_FATAL in production!

  'PENUB_CACHE_NONE'        =>  0,
  'PENUB_CACHE_CONFIG'      =>  1,
  'PENUB_CACHE_NORMAL'      =>  2,
  'PENUB_CACHE_AGGRESSIVE'  =>  3,
  
  'PENUB_CACHE_LEVEL'       =>  1, //Switch to at least PENUB_CACHE_CONFIG in production!

  'PENUB_APP_CACHE_PROVIDER' => 'file', //Valid choices: 'apc', 'file' (coming soon: 'shmop' and 'memcache')

  'PENUB_SHOW_METRICS'      => true, //Switch to False in production!

);

// Listing of timezones: http://www.php.net/manual/en/timezones.php
date_default_timezone_set('America/New_York');
