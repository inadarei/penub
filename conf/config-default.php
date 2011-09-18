<?php

$databases = array (
  'default' =>
    array (
      'default' =>
      array (
        'driver' => 'mysql',
        'database' => 'beta_penub_com',
        'username' => 'root',
        'password' => '',
        'host' => 'localhost',
        'port' => '',
      ),
    ),
);

define ('PENUB_KERNEL_PATH', 'framework');
define ('PENUB_CONTRIB_PATH', 'pcontrib');
define ('PENUB_CUSTOM_CODE_PATH', 'custom');

define ('PENUB_FILES_FOLDER_PATH', 'files');
define ('PENUB_LIB_FOLDER_PATH', PENUB_KERNEL_PATH . '/libraries');
define ('PENUB_RUNTIMECONF_FOLDER_PATH', PENUB_FILES_FOLDER_PATH . '/runtimeconf');


define ('PENUB_LOG_LEVEL_NOTICE',  1);
define ('PENUB_LOG_LEVEL_DEBUG',   2);
define ('PENUB_LOG_LEVEL_INFO',    3);
define ('PENUB_LOG_LEVEL_CRUCIAL', 4);
define ('PENUB_DFLT_LOG_LEVEL',    PENUB_LOG_LEVEL_DEBUG); //Switch to PENUB_LOG_LEVEL_CRUCIAL in production!

define ('PENUB_CACHE_NONE',       0);
define ('PENUB_CACHE_CONFIG',     1);
define ('PENUB_CACHE_NORMAL',     2);
define ('PENUB_CACHE_AGGRESSIVE', 3);
define ('PENUB_CACHE_LEVEL',    PENUB_CACHE_NONE); //Switch to at least PENUB_CACHE_CONFIG in production!

define ('PENUB_SHOW_METRICS', True); //Switch to False in production!