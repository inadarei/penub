<?php

$config_loaded = false;
require ('app.cfg.php');

$env_config_file = PENUB_CONF_PATH. '/environments/' . PENUB_ENVIRONMENT . '.php';
if (file_exists($env_config_file)) {
  require($env_config_file);
  $config_loaded = true;
}

if (!$config_loaded) {
  die("Environmental configuration file not found! Please make sure $env_config_file exists!");
}

require ( PENUB_KERNEL_PATH . '/core/penub.kernel.inc');


penub_log("Request processing launched");

$kernel = new PenubKernel();
$kernel->run();
