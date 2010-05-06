<?php

class PenubURLRouter extends PenubModule {
  function processHTTPRequest(&$state) {
    printf('<pre>%s<pre>', print_r($this, true));
    penub_log('router called!');
  }
}