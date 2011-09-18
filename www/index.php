<?php

phpinfo();
die();


$config_file = realpath(dirname(__FILE__) . '/../config.php');
if (file_exists($config_file)) {
  require ($config_file);
}
else {
  die("Configuration file not found! Please make sure there's config.php in the root folder.");
}

require ( PENUB_KERNEL_PATH . '/core/penub.kernel.inc');


penub_log("Request processing launched");

$kernel = new PenubKernel();
$kernel->run();
