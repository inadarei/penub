<?php

require_once (dirname(__FILE__) . '/PenubTestCase.class.inc');

class PenubModuleManagerTest extends PenubTestCase {

  public function setUp() {
    parent::setUp();

    require_once (PENUB_FRAMEWORK_PATH . '/kernel/module.manager.inc');
  }

  public function test_modules_cache_provider_factory() {

    $modules_cache = PenubModulesCacheFactory::getModulesCache('apc');
    if (is_object($modules_cache)) {
      $modules_cache = get_class($modules_cache);
    }
    $this->assertEquals("PenubModulesCacheAPCProvider", $modules_cache, "Test if APC is Implemented");

    $modules_cache = PenubModulesCacheFactory::getModulesCache('file');
    if (is_object($modules_cache)) {
      $modules_cache = get_class($modules_cache);
    }
    $this->assertEquals("PenubModulesCacheFileProvider", $modules_cache, "Test if File is Implemented");

    try {
      $modules_cache = PenubModulesCacheFactory::getModulesCache('nonexistant_provider');
      $this->fail("Non existant providers are not allowed");
    }
    catch (PenubException $ex) {
      // We expect exception to fire for this test
      $this->assertTrue(true);
    }

  }


  public function test_modules_cache_provider() {

    $modules = Array (
      'router' => array (
        'info' => array (
          'title' => 'Default Routing Library',
          'description' => 'Default implementation of URL routing',
          'version' => '1.0 beta files ftw',
          'weight' => '0', //-- 0 is default
          'dependencies' => array(),
        ),
        'implementation' => array (
          'class' => 'PenubURLRouter',
          'filepath' => 'router/PenubURLRouter.class.inc',
        )
      )
    );

    ini_set('apc.enabled', true);
    ini_set('apc.enable_cli', true);
    $cache = PenubModulesCacheFactory::getModulesCache('apc');
    $cache->set($modules);
    $back = $cache->get();

    print_r($back);

  }
}

