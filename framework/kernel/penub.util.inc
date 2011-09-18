<?php

define ('PENUB_ERROR_NOTICE',  1);
define ('PENUB_ERROR_WARNING', 2);
define ('PENUB_ERROR_SEVERE',  3);
define ('PENUB_ERROR_FATAL',   4);

/**
* @File A collection of utility functions (non-OO) for Penub framework. These functions are supposed to work
* even before bootstrap or without bootstrap, that's why they live "outside" the framework.
*/


/**
* Low-level translation function.
* @TODO implement this stub declaration.
*/
function dt($msg) {
  return $msg;
}

/**
* Centralized error reporter (to end-user). Demo implementation.
* @TODO: create a more robust implementation.
*/
function penub_error($msg, $severity = PENUB_ERROR_WARNING) {

  echo ('<pre>' . print_r($msg, true) . '</pre>');
  
  if ($severity == PENUB_ERROR_FATAL) {
    exit(1);
  }
  
}

/**
*  Centralized logger. Logs to Apache log.
*  @TODO: create a more robust implementation.
*/
function penub_log($msg, $level = PENUB_LOG_DEBUG) {
  if ($level >= PENUB_LOG_LEVEL) {
    // Let's try to support complex structures like objects and arrays as well.    
    error_log(print_r($msg, true));
  }  
}

/** Give anybody access to the singleton kernel object */
function penub_kernel() {
  global $kernel;
  
  return $kernel;
}