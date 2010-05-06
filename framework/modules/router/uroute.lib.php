<?php

class URoute_InvalidPathException extends Exception {}
class URoute_CallbackFileNotFoundException extends Exception {}
class URoute_InvalidCallbackException extends Exception {}
class URoute_InvalidURIParameterException extends Exception {}

interface URoute_Constants {
  const PATTERN_ARGS       = '?(?P<%s>(?:/.+)+)';
  const PATTERN_ARGS_ALPHA = '?(?P<%s>(?:/[-\w]+)+)';
  const PATTERN_WILD_CARD  = '(?P<%s>.*)';
  const PATTERN_ANY        = '(?P<%s>(?:/?[^/]*))';
  const PATTERN_ALPHA      = '(?P<%s>(?:/?[-\w]+))';
  const PATTERN_NUM        = '(?P<%s>\d+)';
  const PATTERN_DIGIT      = '(?P<%s>\d+)';
  const PATTERN_YEAR       = '(?P<%s>\d{4})';
  const PATTERN_MONTH      = '(?P<%s>\d{1,2})';
  const PATTERN_DAY        = '(?P<%s>\d{1,2})';
  const PATTERN_MD5        = '(?P<%s>[a-z0-9]{32})';  
}

class URoute_Callback {
  
  private static function loadFile($file) {
    if (file_exists($file)) {
      if (!in_array($file, get_included_files())) {
        include($file);
      }
    } else {
      throw new URoute_CallbackFileNotFoundException('Controller file not found');
    }
  }
  
  public static function getCallback($callback, $file = null) {

    try {
    
      if ($file) {
        self::loadFile($file);
      }
      
      if (is_array($callback)) {
          
        $method = new ReflectionMethod(array_shift($callback), array_shift($callback));
        
        if ($method->isPublic()) {
          if ($method->isStatic()) {
            $callback = array($method->class, $method->name);
          } else {
            $callback = array(new $method->class, $method->name);
          }
        }
         
      } else if (is_string($callback)) {
        $callback = $callback;
      }
      
      if (is_callable($callback)) {
        return $callback;
      }

      throw new URoute_InvalidCallbackException("Invalid callback");
      
    } catch (Exception $ex) {
      throw $ex;
    }
    
  }
  
}

class URoute_Template implements URoute_Constants {
  
  private static $globalQueryParams = array();
  
  private $template  = null;
  private $params    = array();
  private $callbacks = array();
  
  public function __construct($path) {
    if ($path{0} != '/') {
      $path = '/'. $path;
    }
    $this->template = rtrim($path, '\/');
  }
  
  public function getTemplate() {
    return $this->template;
  }
  
  public function getExpression() {
    $expression = $this->template;
    
    if (preg_match_all('~(?P<match>\{(?P<name>.+?)\})~', $expression, $matches)) {
      $expressions = array_map(array($this, 'pattern'), $matches['name']);
      $expression  = str_replace($matches['match'], $expressions, $expression);
    }
    
    return sprintf('~^%s$~', $expression);
  }
  
  public function pattern($token, $pattern = null) {
    static $patterns = array();
    
    if ($pattern) {
      if (!isset($patterns[$token])) {
        $patterns[$token] = $pattern;
      } 
    } else {
      
      if (isset($patterns[$token])) {
        $pattern = $patterns[$token];
      } else {
        $pattern = self::PATTERN_ANY;
      }
      
      if ((is_string($pattern) && is_callable($pattern)) || is_array($pattern)) {
        $this->callbacks[$token] = $pattern;
        $patterns[$token] = $pattern = self::PATTERN_ANY;
      }
      
      return sprintf($pattern, $token);
       
    }    
  }
  
  public function addQueryParam($name, $pattern = '', $defaultValue = null) {
    if (!$pattern) {
      $pattern = self::PATTERN_ANY;
    }
    $this->params[$name] = (object) array(
      'pattern' => sprintf($pattern, $name),
      'value'   => $defaultValue
    );
  }
  
  public static function addGlobalQueryParam($name, $pattern = '', $defaultValue = null) {
    if (!$pattern) {
      $pattern = self::PATTERN_ANY;
    }
    self::$globalQueryParams[$name] = (object) array(
      'pattern' => sprintf($pattern, $name),
      'value'   => $defaultValue
    );
  }
  
  public function match($uri) {
    
    try {
    
      $uri = rtrim($uri, '\/');

      if (preg_match($this->getExpression(), $uri, $matches)) {
        
        foreach($matches as $k=>$v) {
          if (is_numeric($k)) {
            unset($matches[$k]);
          } else {
            
            if (isset($this->callbacks[$k])) {              
              $callback = URoute_Callback::getCallback($this->callbacks[$k]);
              $value    = call_user_func($callback, $v);
              if ($value) {
                $matches[$k] = $value;
              } else {
                throw new URoute_InvalidURIParameterException('Ivalid parameters detected');
              }
            }
            
            if (strpos($v, '/') !== false) {
              $matches[$k] = explode('/', trim($v, '\/'));
            }
          }
        }
  
        $params = array_merge(self::$globalQueryParams, $this->params);
  
        if (!empty($params)) {
          
          $matched = false;
          
          foreach($params as $name=>$param) {
            
            if (!isset($_GET[$name]) && $param->value) {
              $_GET[$name] = $param->value;
              $matched = true;
            } else if ($param->pattern && isset($_GET[$name])) {
              $result = preg_match(sprintf('~^%s$~', $param->pattern), $_GET[$name]);
              if (!$result && $param->value) {
                $_GET[$name] = $param->value;
                $result = true;
              }
              $matched = $result;
            } else {
              $matched = false;
            }          
            
            if ($matched == false) {
              throw new Exception('Request do not match');
            }
            
          }
          
        }
        
        return $matches;
        
      }
      
    } catch(Exception $ex) {
      throw $ex;
    }
    
  }
  
  public static function regex($pattern) {
    return '(?P<%s>' . $pattern . ')';
  }
  
}

class URoute_Router {
  
  private static $routes  = array();
  private static $methods = array('get', 'post', 'put', 'delete', 'head', 'options');
  
  public static function addRoute($params, URoute_Service $service) {
    
    static $routes = array();
  
    if (!empty($params['path'])) {
      
      $template = new URoute_Template($params['path']);
      
      if (!empty($params['handlers'])) {
        foreach ($params['handlers'] as $key => $pattern) {
           $template->pattern($key, $pattern);
        }
      }
      
      if (isset($params['file'])) {
        $file = trim($params['file'], '\/');
        $params['file'] = sprintf('%s/%s', $service->getDirName(), $file);
      } else {
        $params['file'] = null;
      }
      
      $methods = array_intersect(self::$methods, array_keys($params));

      foreach ($methods as $method) {
        self::$routes[$method][$params['path']] = array(
          'template' => $template,
          'callback' => $params[$method],
          'file'     => $params['file'],
        );
      }
      
    }
    
  }
  
  private static function getRequestMethod() {
    return strtolower($_SERVER['REQUEST_METHOD']);
  }
  
  private static function getRoutes() {
    $method = self::getRequestMethod();
    return isset(self::$routes[$method]) ? self::$routes[$method] : array();
  }
  
  public static function route($uri) {

    $routes = self::getRoutes();
    
    try {
    
      foreach ($routes as $route) {
        
        $params = $route['template']->match($uri);
  
        if (!is_null($params)) {
          $callback = URoute_Callback::getCallback($route['callback'], $route['file']);
          return call_user_func($callback, $params);
        }
        
      }
      
      throw new URoute_InvalidPathException('Invalid path');
      
    } catch (Exception $ex) {
      throw $ex;
    }
    
  }
  
} // end URoute_Router

abstract class URoute_Service implements URoute_Constants {
  
  private $dirname;
  private $host;
  private $path;
  private $endPoint;
  private $requestURI;
  
  public function __construct($endPoint) {
    $this->setHost();
    $this->setEndPoint($endPoint);
    $this->service();
    $this->route();
  }
  
  private function setHost() {
    $host = $_SERVER['SERVER_NAME'];
    if (!preg_match('~^http~', $host, $matches)) {
      $host = 'http://' . $host;
    }
    $this->host = $host;
  }
  
  private function setRequestURI() {
    $uri = $_SERVER['REQUEST_URI'];

    if (array_key_exists('q', $_GET)) {
      $uri = $_GET['q'];
    }
    
    $tokens = parse_url($uri);
    $path   = $tokens['path'];
    
    if ($path{0} != '/') {
      $path = '/'. $path;
    }
    
    $this->requestURI = $path == '/' ? '' : $path;
  }
  
  private function setEndPoint($file) {
    $dirname = dirname($file);
    $path    = '/';

    $this->dirname  = $dirname;
    $this->path     = $path;
    $this->endPoint = $this->host . $this->path;
    
    $this->setRequestURI();
  }
  
  public function addRoute($params) {
    URoute_Router::addRoute($params, $this);
  }
  
  public function route() {
    try {
      URoute_Router::route($this->requestURI);
    } catch(Exception $ex) {
      $this->error($ex);
    }
  }
  
  public function getDirName() {
    return $this->dirname;
  }
  
  protected abstract function service();
  protected abstract function error($exception);
  
}

?>