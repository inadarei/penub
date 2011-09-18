<?php

define ('PENUB_WWW_PATH', realpath('.')); //not the case for unit-tests run from other locations!
require ('app.cfg.php');
require ('bootstrap.php');

penub_load_configuration();

require ( PENUB_FRAMEWORK_PATH . '/kernel/penub.kernel.inc');
$kernel = new PenubKernel;
$kernel->run();
