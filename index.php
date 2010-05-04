<?php

if (file_exists(dirname(__FILE__) . '/config.php') {
  require_once ('config.php');
}
else {
  define('PENUB_KERNEL_PATH', 'penub');
}

require_once (PENUB_KERNEL_PATH . '/core/penub.kernel.php');
