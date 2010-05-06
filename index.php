<?php

if (file_exists(dirname(__FILE__) . '/config.php')) {
  require ('config.php');
}
else {
  die("Configuration file not found! Please make sure there's config.php in the root folder.");
}

require (PENUB_KERNEL_PATH . '/core/penub.kernel.inc');


$kernel = new PenubKernel();
$req = fetch_request_object_from_http();
$kernel->run($req);
