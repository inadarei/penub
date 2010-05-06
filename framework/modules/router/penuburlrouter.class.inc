<?php

class PenubURLRouter extends PenubModule {

    function __construct(&$kernel) {
      parent::__construct($kernel);
    }
    
    function processHTTPRequest(&$state) {
      penub_log('router called!');
    }
}