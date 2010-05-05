<?php

define ('PENUB_ERROR_NOTICE',  1);
define ('PENUB_ERROR_WARNING', 2);
define ('PENUB_ERROR_WARNING', 3);
define ('PENUB_ERROR_FATAL',   4);

// Welcome to Kernel

class PenubKernel {

  function __construct() {
  }  
  
}


/**
* Centralized error logger. Demo implementation.
* @TODO: create a more robust implementation.
*/
function penub_error($msg, $severity) {

  echo ($msg);
  
  if ($severity == PENUB_ERROR_FATAL) {
    exit(1);
  }
  
}
