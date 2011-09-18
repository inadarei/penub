<?php

/**
 * Abstract parent for Penub test classes.
 */
abstract class PenubTestCase extends PHPUnit_Framework_TestCase {

  protected $couchdb_url;
  protected $couchdb_user;
  protected $couchdb_pass;

  public function setUp() {
    $this->couchdb_url  = isset($GLOBALS['couchdb_url'])  ? $GLOBALS['couchdb_url']  : 'http://127.0.0.1:5984';
    $this->couchdb_user = isset($GLOBALS['couchdb_user']) ? $GLOBALS['couchdb_user'] : 'admin';
    $this->couchdb_pass = isset($GLOBALS['couchdb_pass']) ? $GLOBALS['couchdb_pass'] : 'admin';

    $www_loc = isset($GLOBALS['www_location']) ? $GLOBALS['www_location'] : '../../www';
    if (!empty($www_loc)) {
      $www_loc = realpath(dirname(__FILE__) . "/$www_loc");
    }

    if (!file_exists(  $www_loc . "/app.cfg.php")) {
      throw new Exception ("Unable to locate app.cfg.php. Please fix setting in phpunitConfig.xml ");
    }

    if (!defined('PENUB_WWW_PATH')) { // avoid attempts of repeated definitions
      define ('PENUB_WWW_PATH', $www_loc);
    }

    if (!defined('PENUB_FRAMEWORK_PATH')) { // avoid multiple parsing due to multiple test runs
      require_once ($www_loc . '/app.cfg.php');
      require_once ($www_loc . '/bootstrap.php');
      penub_load_configuration();
    }
    require_once ( PENUB_FRAMEWORK_PATH . '/kernel/penub.kernel.inc');
    
  }

}
