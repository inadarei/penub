<?php

function penub_load_configuration() {
  $config_loaded = false;

  $base_config_file = PENUB_CONF_PATH. '/environments/__base_config.php';
  $env_config_file  = PENUB_CONF_PATH. '/environments/' . PENUB_ENVIRONMENT . '.php';

  if (file_exists($env_config_file) && file_exists($base_config_file)) {
    require($base_config_file);
    require($env_config_file);

    if (!empty($penub_config_env) && is_array($penub_config_env)) {
      $penub_config_base = $penub_config_base + $penub_config_env;
    } 

    foreach ($penub_config_base as $key => $val) {
      define($key, $val);
    }
  } else {
    die("Environmental configuration file not found! Please make sure '$base_config_file' and '$env_config_file' exist!");
  }

  return $config_loaded;
}