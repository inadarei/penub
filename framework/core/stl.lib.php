<?php

class STL_CodeBlocks {

  private static $blocks;
  
  public static function add($key, $block) {
    self::$blocks[$key][] = $block;
    return sizeof(self::$blocks[$key]) - 1;
  }
  
  public static function get($key, $id) {
    if (!empty(self::$blocks[$key][$id])) {
      return self::$blocks[$key][$id];
    }
    return null;
  }
  
  public static function remove($key, $id) {
    if (!empty(self::$blocks[$key][$id])) {
      unset(self::$blocks[$key][$id]);
    }
  }
  
  public static function get_blocks() {
    return self::$blocks;
  }
  
}

class STL_ParseExtension {
  
  private static $regexs = array(
  
    'ext' => '~
      {\s*(\w+):(\w+)                 # match outmost opening tag at least with one attribute
        (
          (?:                         # do not capture this match
            \s+                       # at least one white space
            \w+                       # capture attribute name 
            \s*                       # any number of white spaces
            =                         # check equals sign
            \s*                       # any number of white spaces
            ".*?"                     # capture attribute value 
          )*+
        )
      \s*                             # any number of white spaces
      (?:                             # do not capture this match
        /\s*}                         # self terminated tag without body
        |                             # OR
        }                             # tag with body
        (
          (?:                         # do not capture this match
            (?!{\s*/?\1:\2\s*).       # use negative lookahead to ensure that text does not contain same nested tag 
            |                         # OR
            (?R)                      # use recursion to handle nested tag
          )*+
        )
        {\s*/\s*\1:\2\s*}             # match outmost closing tag
      )
    ~six',

    'attributes' => '~
      (?:
        (?P<att>\w+)                  # capture attribute name 
        \s*                           # any number of white spaces
        =                             # check equals sign
        \s*                           # any number of white spaces
        "(?P<val>.*?)"                # capture attribute value 
      )+
    ~six',
    
    'each' => '~
      {ext:each\s+as\s+(\w+?)}        # match outmost opening tag at least with one attribute
      (
        (?:                           # do not capture this match
          (?!{/?ext:each}).           # use negative lookahead to ensure that text does not contain same nested tag 
          |                           # OR
          (?R)                        # use recursion to handle nested tag
        )*+
      )
      {/ext:each}                     # match outmost closing tag
    ~six'
    
  );
  
  private static function has_each($input) {
    return strpos($input, 'ext:each') !== false;
  }
  
  private static function parse_each($input) {
    
    $block = array();
    
    if (is_array($input)) {
      $block['name'] = 'ext_each';
      $block['key']  = $input[1];
      $input = $input[2];
    }
    
    $block['body'] = preg_replace_callback(self::$regexs['each'], array('self', 'parse_each'), $input);
    
    if (!empty($block['name'])) {
      return '#_ext_each_'. STL_CodeBlocks::add('ext_each', $block);
    }
    
    return $block['body'];
    
  }
  
  public static function parse($input) {
    
    $block = array();
    
    if (is_array($input)) {
      $block = array(
        'package' => $input[1],
        'name'    => $input[2],
        'type'    => sizeof($input) == 5 ? 'ext' : 'tag',
      );
      if (preg_match_all(self::$regexs['attributes'], $input[3], $matches)) {
        $block['attributes'] = array_combine($matches['att'], $matches['val']);
      }
      $input = $input[4];
    }
    
    $input = trim($input);
    
    if ($input) {
      $block['body'] = preg_replace_callback(self::$regexs['ext'], array('self', 'parse'), $input);
      $block['body'] = self::parse_each($block['body']);
      
      if (!empty($block['name'])) {
        return '#_ext_'. STL_CodeBlocks::add('ext', $block);
      }

      return $block['body'];
    } else {
      if (!empty($block) && $block['type'] == 'tag') {
        return '#_ext_'. STL_CodeBlocks::add('ext', $block);
      }
    }
    
    return null;
    
  }
  
}

class STL_ParseCondition {

  private static function get_array() {
    return array(
      'var'   => array(),
      'eq'    => array(),
      'value' => array(),
      'oper'  => array(),
    );
  }
  
  private static function add_var(&$structure, $token, $operator) {
  
    $token = array_map('trim', $token);
  
    $var = array_shift($token);
    $eq  = array_shift($token);
    $val = trim(array_shift($token), '"\'');
    
    if ($var{0} == '!') {
      $var = substr($var, 1);
      $eq = '!';
    } else if (!$eq && !$val) {
      $eq = '==';
      $val = 'true';
    }
    
    $structure['eq'][]    = $eq;
    $structure['value'][] = $val;
    $structure['oper'][]  = $operator;
    $structure['var'][]   = $var;
    
  }
  
  public static function parse($input) {
    
    $logical    = '~\s+(?P<oper>and|or|&&|\|\|)\s+~';
    $comparison = '~(===|!==|==|!=|<>|>=|<=|>|<|\s+in\s+)~';
    
    $tokens = preg_split($logical, $input, 0, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    
    if (!empty($tokens)) {
    
      $structure = self::get_array();
    
      if (sizeof($tokens) == 1) {
        self::add_var(
          $structure,
          preg_split($comparison, array_shift($tokens), 0, PREG_SPLIT_DELIM_CAPTURE),
          null
        );
      } else {
      
        $tokens = array_chunk($tokens, 2);
        
        foreach($tokens as $token) {
          self::add_var(
            $structure, 
            preg_split($comparison, array_shift($token), 0, PREG_SPLIT_DELIM_CAPTURE), 
            array_shift($token)
          );
        }
        
      }

      return $structure;
      
    }
  
    return null;
    
  }
  
}

class STL_ParseForEach {
  
  private static $regexs = array(
    
    'for' => '~
      {\s*for                         # match outmost opening tag
        \s*                           # any number of white spaces
        (.+?)                         # capture variable name
        \s*                           # any number of white spaces
        in                            # "in" operator
        \s*                           # any number of white spaces
        (.+?)                         # capture array name
        \s*                           # any number of white spaces
      }                   
      (
        (?:                           # do not capture this match
          (?!{/?for(?!else)).         # use negative lookahead to ensure that text does not contain same nested tag 
          |                           # OR
          (?R)                        # use recursion to handle nested tag
        )++
      )
     {\s*/\s*for\s*}                  # match outmost closing tag
    ~six',
    
    'else' => '~{\s*forelse\s*}~',

  );
  
  private static function parse_else(&$input) {
  
    if (!empty($input['body'])) {
    
      if ($tokens = preg_split(self::$regexs['else'], $input['body'], -1, PREG_SPLIT_DELIM_CAPTURE)) {
  
        $input['body'] = array_shift($tokens);

        if (!empty($tokens)) {
        
          if (empty($input['forelse'])) {
            $input['forelse'] = array();
          }
          
          $tokens = array_chunk($tokens, 2);
          
          foreach ($tokens as $token) {
            $input['forelse'] = array(
              'body' => trim(array_shift($token))
            );
          }

        }
        
      }
      
    }
    
  }
  
  public static function parse($input) {
    
    $block = array();
    
    if (is_array($input)) {
      $block['var'] = $input[1];
      $block['array'] = $input[2];
      $input = $input[3];
    }
    
    $block['body'] = trim(preg_replace_callback(self::$regexs['for'], array('self', 'parse'), $input));
    
    if (!empty($block['var'])) {
      self::parse_else($block);
      return '#_for_'. STL_CodeBlocks::add('for', $block);
    }
    
    return $block['body'];
    
  }
  
}

class STL_ParseIF {

  private static $regexs = array(
    
    'if' => '~
      {if(.*?)}                       # match outmost opening tag at least with one attribute
      (
        (?:                           # do not capture this match
          (?!{/?if).                  # use negative lookahead to ensure that text does not contain same nested tag 
          |                           # OR
          (?R)                        # use recursion to handle nested tag
        )++
      )
      {/if}                           # match outmost closing tag
     ~six
    ',
    
    'else' => '~{\s*else(?:(?:if)?(.*?))\s*}~si'
    
  );
    
  private static function parse_else(&$input) {
  
    if (!empty($input['body'])) {
    
      if ($tokens = preg_split(self::$regexs['else'], $input['body'], -1, PREG_SPLIT_DELIM_CAPTURE)) {
      
        $input['body'] = array_shift($tokens);
        
        if (!empty($tokens)) {
        
          if (empty($input['else'])) {
            $input['else'] = array();
          }
          
          $tokens = array_chunk($tokens, 2);
          
          foreach ($tokens as $token) {
            $input['else'][] = array(
              'cond' => STL_ParseCondition::parse(array_shift($token)),
              'body' => trim(array_shift($token))
            );
          }
          
        }
        
      }
      
    }
    
  } //end parse_else
  
  public static function parse($input) {
    
    $cond = null;
    
    if (is_array($input)) {
      $cond = trim($input[1]);
      $input = $input[2];
    }
    
    $block = array(
      'cond' => STL_ParseCondition::parse($cond),
      'body' => trim(preg_replace_callback(self::$regexs['if'], array('self', 'parse'), $input))
    );
    
    self::parse_else($block);
    $block['body'] = STL_ParseExtension::parse(STL_ParseForEach::parse($block['body']));
        
    return '#_if_'. STL_CodeBlocks::add('if', $block);
    
  } //end parse
  
}

class STL_Condition {
  
  private static $methods = array(
    '===' => array('self', 'if_eqs'),
    '=='  => array('self', 'if_eq'),
    '!==' => array('self', 'if_neqs'),
    '!='  => array('self', 'if_neq'),
    '<>'  => array('self', 'if_neq'),
    '>='  => array('self', 'if_geq'),
    '<='  => array('self', 'if_leq'),
    '!'   => array('self', 'if_not'),
    'and' => array('self', 'if_and'),
    '&&'  => array('self', 'if_and'),
    'or'  => array('self', 'if_or'),
    '||'  => array('self', 'if_or'),
    '>'   => array('self', 'if_gt'),
    '<'   => array('self', 'if_lt'),
    'in'  => array('self', 'if_in')
  );
  
  private static function if_eq($v1, $v2) {
    return $v1 == $v2;
  }
  
  private static function if_eqs($v1, $v2) {
    return $v1 === $v2;
  }
  
  private static function if_neq($v1, $v2) {
    return $v1 != $v2;
  }
  
  private static function if_neqs($v1, $v2) {
    return $v1 !== $v2;
  }
  
  private static function if_not($v) {
    return $v ? !!$v : !$v;
  }
  
  private static function if_and($v1, $v2) {
    return $v1 && $v2;
  }
  
  private static function if_or($v1, $v2) {
    return $v1 || $v2;
  }
  
  private static function if_gt($v1, $v2) {
    return $v1 > $v2;
  }
  
  private static function if_geq($v1, $v2) {
    return $v1 >= $v2;
  }
  
  private static function if_lt($v1, $v2) {
    return $v1 < $v2;
  }
  
  private static function if_leq($v1, $v2) {
    return $v1 <= $v2;
  }
  
  private static function if_in($v1, $v2) {
    return is_array($v2) && in_array($v1, $v2);
  }
  
  private static function is_and($v) {
    return $v == 'and' || $v == '&&';
  }
  
  private static function is_or($v) {
    return $v == 'or' || $v == '||';
  }
  
  private static function value($value) {
    
    $values = array(
      'null'  => null,
      'false' => false,
      'true'  => true
    );
    
    if (is_numeric($value)) {
      return (float) $value;
    } else if (is_string($value) && array_key_exists(strtolower($value), $values)) {
      return $values[strtolower($value)];
    }
    
    if (!is_string($value)) {
      return $value;
    }
    
    return $value;
    
  }
  
  public static function evaluate($condition, $context) {
    $result = false;
    $cond = null;
    
    if (is_array($condition['var'])) {

      foreach($condition['var'] as $id => $var) {

        $var = $context->lookup($var);
        $eq  = $condition['eq'][$id];
        $val = self::value($condition['value'][$id]);
        
        if ($eq == 'in') {
          $val = $context->lookup($val);
        }
        
        $tmp = call_user_func(
          self::$methods[$eq],
          self::value($var),
          self::value($val)
        );

        if ($tmp == true && self::is_and($condition['oper'][$id])) {
          
        } else if ($tmp == false && self::is_or($condition['oper'][$id])) {
          
        } else if ($tmp == true && self::is_or($condition['oper'][$id])) {
          $result = true;
          break;
        } else if (!$condition['oper'][$id]) {
          
        } else {            
          $result = false;
          break;
        }
        
        if (self::is_and($cond)) {
          $result &= $tmp;
        } else if (self::is_or($cond)) {
          $result |= $tmp;
        } else {
          $result = $tmp;
        }
        
        $cond = $condition['oper'][$id];
        
      }
      
    }
    
    return !!$result;
  }
  
}

class STL_ParseVar {
  
  private static $context;
  
  private static function parse_callback($input) {
    if (is_array($input)) {
      return self::$context->lookup($input[1]);
    }
    return null;
  }
  
  public static function parse($text, $context) {
    self::$context = $context;
    return preg_replace_callback(
      '~%(.*?)%~', 
      array('self', 'parse_callback'), 
      $text
    );
  }
  
}

class STL_ParseFunction {

  private static $regex = array(
  
    'fn' => '~
      (?<fn>\w+?)                     # match outmost function name
      (?P<sig>                        # capture function signature
        \(                            # match opening parenthesis
          (?P<args>                   # capture function arguments
            [^()]++                   # allow any character except parenthesis
            |                         # OR
            (?P>sig)                  # continue recursively 
          )
        \)
      )++
    ~sx'
      
  );
  
  public static function is_valid($fn) {
    return function_exists($fn);
  }
  
  private static function exec($fn, $arg_str) {
    
    $args = array_map('trim', array_map('trim', explode(',', $arg_str)), array('"'));
    
    if (self::is_valid($fn)) {
      return call_user_func_array($fn, $args);
    }
    
    return null;
    
  }
  
  public static function parse($input) {
    
    if (is_array($input)) {

      $input = $input[1];
      while(preg_match_all(self::$regex['fn'], $input, $matches)) {
        $input = str_replace($matches[0], self::exec($matches['fn'][0], $matches['args'][0]), $input);    
      }
      
    }
    
    return preg_replace_callback('~{fn:(.*?)}~', array('self', 'parse'), $input);
    
  }
  
}

class STL_GlobalContext {

  private static $context;
  
  public static function put($key, $value) {
    self::getInstance()->put($key, $value);
  }
  
  public static function lookup($var) {
    return self::getInstance()->lookup($var, false);
  } 
  
  public static function getInstance() {
    if (!self::$context) {
      self::$context = new STL_Context();
      self::$context->put_all(
        array(
          'post' => $_POST,
          'get'  => $_GET
        )
      );
    }
    return self::$context;
  }
  
}

class STL_Context {
  
  private $context   = array();
  public function __construct($context = null) {
    if ($context) {
      if (is_array($context)) {
        $this->context = array_merge($this->context, $context);
      } else {
        $this->context = array_merge($this->context, $context->get_all());
      }
    }
  }
  
  public function put($key, $value) {
    $this->context[$key] = $value;
    return $this;
  }
  
  public function put_all($map) {
    $this->context = array_merge($this->context, $map);
    return $this;
  }
  
  public function get_all() {
    return $this->context;
  }

  private static function get_var($context, $var, $index = null) {
    
    if (is_array($context) && array_key_exists($var, $context)) {

      $context = $context[$var];
      
      if (is_numeric($index) && array_key_exists($index, $context)) {
        $context = $context[$index];
      }
      
      return $context;
      
    } else if (is_object($context)) {
      
      $ucfirst = $lcfirst = $var;
      strtoupper($ucfirst{0});
      strtolower($lcfirst{0});
      
      $getters = array(
        'get' . $ucfirst,
        'get' . $lcfirst,
        'has' . $ucfirst,
        'has' . $lcfirst,
        'is'  . $ucfirst,
        'is'  . $lcfirst
      );
      
      try {
        
        if (isset($context->{$var})) {
          $value = $context->{$var};
          if (is_array($value) && is_numeric($index) && isset($value[$index])) {
            $value = $value[$index];
          }
          return $value;
        }
        
        throw new ReflectionException();
        
      } catch(ReflectionException $e) {
      
        try {
          
          $prop = new ReflectionProperty($context, $var);
          
          if ($prop->isPublic()) {
            $context = $prop->getValue($context);
          } else {
            throw new ReflectionException();
          }
          
        } catch(ReflectionException $e) {
          
          $value = null;
          
          foreach($getters as $getter) {
            
            try {
              
              $meth = new ReflectionMethod($context, $getter);
              
              if ($meth->isPublic()) {
                $value = $meth->invoke($context);
                break;
              }
              
            } catch (ReflectionException $e) {
              $value = null;
            }
            
          }
          
        }
        
      }
      
      if (is_array($value) && is_numeric($index) && isset($value[$index])) {
        $value = $value[$index];
      }
      
      return preg_replace('~\\\~', '', $value);
      
    }
    
    return null;
    
  }
  
  public function lookup($var, $gc = true) {
    
    $result = null;
    $regex  = '~
      (?:
        (?P<var>\w+)                  # match variable name
        (?:                           # do not capture this match
          \[                          # match opening square bracket
            \s*                       # any number of white spaces
            (?P<index>\d+)            # match and capture numeric index of array
            \s*                       # any number of white spaces
          \]                          # match closing square bracket
        )?                            # make this match optionsl
        (?:\.)?                       # optionally match trailing dot
      ) 
    ~six';

    $context = &$this->context;
    
    if (strpos($var, '.') === false && strpos($var, '[') === false) {
      $result = $this->get_var($context, $var);
    } else if (preg_match_all($regex, $var, $matches)) {
      
      $result = $this->get_var(
        $context, 
        array_shift($matches['var']),
        array_shift($matches['index'])
      );
      
      if (sizeof($matches['var'])) { 
        while($result && sizeof($matches['var'])) {
          $result = $this->get_var(
            $result, 
            array_shift($matches['var']),
            array_shift($matches['index'])
          );
        }
      }
      
      if ($gc) {
        if (is_null($result)) {
         $result = STL_GlobalContext::lookup($var);
        }
      }
      
    }
    
    return $result;
    
  }
  
  private function get_from_string_callback($input) {
    return $this->lookup($input[1]);
  }
  
  public function get_from_string($input) {
    return preg_replace_callback(
      '~%%(.*?)%%~',
      array($this, 'get_from_string_callback'),
      $input
    );
  }
  
  public function __destruct() {
    $this->context = null;
  }
  
}

class STL_Evaluator {
  
  private $context       = null;
  private $include_path  = array();
  private static $extension = null;
  
  public function __construct($context) {
    $this->context = $context;
  }
  
  private function eval_ext_each($block) {
    $result   = array();
    $ext      = self::$extension;
    $iterator = $ext->get_iterator(); 
    
    if ($iterator) {

      while ($iterator->has_next()) {
      
        $context = new STL_Context($this->context);
        $context->put($block['key'], $iterator->next());
        $parser   = new STL_Evaluator($context);
        
        if ($ext->preprocesses_iterator_output()) {
          $block['body'] = $ext->preprocess_iterator_output($block['body'], $context);
        }
        
        $output = STL_ParseFunction::parse(
          STL_ParseVar::parse($parser->parse($block), $context)
        );
        
        if ($ext->postprocesses_iterator_output()) {
          $output = $ext->postprocess_iterator_output($output, $context);
        }
        
        $result[] = $output;
        
      }
      
    }
    
    return implode('', $result);
  }
  
  private function eval_ext($block) {
    
    STL_FileLoader::load_extension($block['package'], $block['name']);
    
    $result  = null;
    $class   = sprintf('ext_%s_%s', $block['package'], $block['name']);
      
    if (class_exists($class)) {
    
      $context = null;
      $ext     = new $class();
      
      $ext->add_attributes($block['attributes']);
      
      if ($block['type'] == 'ext') {
      
        $ext->init($this->context);
        
        self::$extension = $ext;

        if ($ext->preprocesses_extension_output()) {
          $block['body'] = $ext->preprocess_extension_output($block['body'], $this->context);
        }
        
        $parser = new STL_Evaluator($this->context);
        $result = STL_ParseFunction::parse(
          STL_ParseVar::parse($parser->parse($block), $this->context)
        );
        
        if ($ext->postprocesses_extension_output()) {
          $result = $ext->postprocess_extension_output($result, $this->context);
        }
        
      } else {
        $result = $ext->render($this->context);
      }
      
    }

    return $result;
    
  }

  private function eval_for($block) {
    
    $result = array();
    $array  = $this->context->lookup($block['array']);
    
    if (is_array($array)) {
    
      $context = new STL_Context($this->context);
      
      foreach ($array as $k => $v) {
        
        $context->put('key', $k);
        $context->put($block['var'], $v);
        $parser = new STL_Evaluator($context);
        
        $result[] = $parser->parse(array(
          'body' => STL_ParseFunction::parse(
            STL_ParseVar::parse($block['body'], $context)
          )
        ));
        
      }
      
    } else if (!empty($block['forelse'])) {
      $result[] = $this->parse($block['forelse']);
    }
    
    return implode('', $result);
    
  }
  
  private function eval_if($block) {    
    $result = null;

    if ($block['cond'] && STL_Condition::evaluate($block['cond'], $this->context)) {
      $result = $this->parse($block);
    } else if (!empty($block['else'])) {
      foreach ($block['else'] as $else) {
        if ($else['cond'] && STL_Condition::evaluate($else['cond'], $this->context)) {
          $result = $this->parse($else);
        } else if (empty($else['cond'])) {
          $result = $this->parse($else);
        } 
      }
    } else if (empty($block['cond'])) {
      $result = $this->parse($block);
    }
    
    if ($result) {
      $result = STL_ParseFunction::parse(
        STL_ParseVar::parse($result, $this->context)
      );
    }
    
    return $result;
  }
    
  private function parse_callback($input) {
    $result = null;
    
    if (is_array($input) && ($block = STL_CodeBlocks::get($input[1], $input[2]))) {
      $callback = array($this, 'eval_'. $input[1]);
      if (is_callable($callback))  {
        $result = call_user_func($callback, $block);
      }
    }

    return $result; 
  }
  
  private function parse($block) {
    $result = preg_replace_callback(
      '~#_(\w+)_(\d+)~', 
      array($this, 'parse_callback'), 
      $block['body']
    );
    return $result;
  }
  
  public function evaluate() {
    $blocks = &STL_CodeBlocks::get_blocks();

    return STL_ParseFunction::parse(
      STL_ParseVar::parse(
        $this->parse(array_pop($blocks['if'])), 
        $this->context
      )
    );
  }
  
}

class STL_FileLoader {

  private static $template_dirs = array();
  private static $extension_dirs   = array();
  private static $templates     = array();

  private static function dir($dir) {
    $doc_root = $_SERVER['DOCUMENT_ROOT'];
    $dir      = preg_replace(sprintf('~^%s~', $doc_root), '', '/'. trim($dir, '\/'));
    
    if ($dir{0} != '/') {
      $dir = '/'. $dir;
    }
    
    return $doc_root . $dir;
  }
  
  public static function add_template_dir($dir) {
    $dir = self::dir($dir);
    self::$template_dirs[md5($dir)] = $dir;
  }
  
  public static function add_extension_dir($dir) {
    $dir = self::dir($dir);
    self::$extension_dirs[md5($dir)] = $dir;
  }
  
  public static function get_template($name) {
    $name = trim($name, '\/');
    
    foreach (self::$template_dirs as $dir) {
      
      $path = sprintf('%s/%s', $dir, $name);
      $hash = md5($path);
    
      if (isset(self::$templates[$hash])) {
        return self::$templates[$hash];
      } else if (file_exists($path)) {
        return (self::$templates[$hash] = file_get_contents($path));
      }
      
    }
    
    return null;
  }
  
  public static function load_extension($package, $name) {

    $class = sprintf('ext_%s_%s', $package, $name);
    
    if (!class_exists($class)) {

      foreach (self::$extension_dirs as $dir) {
      
        $mpath = sprintf('%s/%s/%s.ext.php', $dir, $package, $name);
        $lpath = sprintf('%s/%s/%s.lib.php', $dir, $package, $package);
        
        if (file_exists($mpath)) {
          require_once($mpath);
        }
        
        if (file_exists($lpath)) {
          require_once($lpath);
        }
        
      }
      
    }
    
    return null;
  }
  
}

class STL_Template {
  
  private $tpl = array();
  private $context;
  private $evaluator;
  
  public function __construct($tpl, $file = false) {
    $this->context = new STL_Context();
    $this->add_tpl($tpl, $file);
  }
  
  public function put($key, $value) {
    $this->context->put($key, $value);
    return $this->context;
  }
  
  public function extend($tpl, $file = false) {
    array_unshift($this->tpl, $file ? STL_FileLoader::get_template($tpl) : $tpl);
    $this->parse_extends();
    return $this;
  }
  
  public function process() {
    STL_ParseIF::parse($this->get_extended());
    $evaluator = new STL_Evaluator($this->context);
    $result = $evaluator->evaluate();
    return $result;
  }
  
  private function add_tpl($tpl, $file = false) {
    if (!$file) {
      $this->tpl[] = $tpl;
    } else {
      $this->tpl[] = STL_FileLoader::get_template($tpl);
    }
    $this->parse_extends();
  }
  
  private $files = array();
  
  private function parse_extends_callback($input) {
    if (is_array($input)) {
      $this->files = preg_split('~\s*,\s*~', $input[1]);
    } else {
      $this->files = array();
    }
    return null;
  }
  
  private function parse_extends() {
    $this->tpl[0] = preg_replace_callback(
      '~{extends\s+(.*?)\s*}~', 
      array($this, 'parse_extends_callback'), 
      $this->tpl[0]
    );
    if (!empty($this->files) && is_array($this->files)) {
      foreach ($this->files as $file) {
        $tpl = new STL_Template($file, true);
        //array_unshift($this->tpl, $tpl->get_extended());
      }
    }
  }
  
  private function get_extended() {
  
    $regex_tpl = '~
      {\s*block\s+name\s*=\s*"%s"\s*}
        (.*?)
      {\s*/\s*block\s*}
    ~six';
    
    $this->tpl = array_filter($this->tpl);
    
    if (!empty($this->tpl)) {
    
      $tpl = array_pop($this->tpl);
      rsort($this->tpl);
      
      foreach ($this->tpl as $data) {
        $regex = sprintf($regex_tpl, '(\w+)');
        if (preg_match_all($regex, $tpl, $matches)) {
          foreach($matches[0] as $key=>$match) {
            $data = preg_replace(sprintf($regex_tpl, $matches[1][$key]), $match, $data);
          }
          $tpl = $data;
        }
      }
      
    }
    
    return preg_replace(sprintf($regex_tpl, '\w+'), '$1', $tpl);
    
  }
  
  public static function add_template_dir($dir) {
    STL_FileLoader::add_template_dir($dir);
  }
  
  public static function add_extension_dir($dir) {
    STL_FileLoader::add_extension_dir($dir);
  }
  
}

interface STL_IExtensionDataIterator {
  public function has_next();
  public function next();
}

abstract class STL_AbstractExtensionDataIterator implements STL_IExtensionDataIterator {
  protected $data;
  
  public function __construct($data) {
    $this->data = $data;
  }
}

class STL_ArrayIterator extends STL_AbstractExtensionDataIterator {
  private $current;
  
  public function has_next() {
    return !(($this->current = current($this->data)) === false);  
  }
  
  public function next() {
    next($this->data);
    return $this->current;
  }
}

interface STL_IExtensionOutputPreProcessor {
  public function preprocess_extension_output($input, STL_Context $context);
}

interface STL_IExtensionOutputPostProcessor {
  public function postprocess_extension_output($input, STL_Context $context);
}

interface STL_IExtensionOutputProcessor 
  extends STL_IExtensionOutputPreProcessor, 
    STL_IExtensionOutputPostProcessor {}

interface STL_IExtensionDataIteratorOutputPreProcessor {
  public function preprocess_iterator_output($input, STL_Context $context);
}

interface STL_IExtensionDataIteratorOutputPostProcessor {
  public function postprocess_iterator_output($input, STL_Context $context);
}

interface STL_IExtensionDataIteratorOutputProcessor
  extends STL_IExtensionDataIteratorOutputPreProcessor, 
    STL_IExtensionDataIteratorOutputPostProcessor {}

abstract class STL_AbstractExtension {
  
  private   $attributes         = array();
  protected $iterator_data      = array();
  protected $allowed_attributes = array();
  
  public function __construct() {}
  
  private function is_allowed_attribute($name) {
    return in_array($name, $this->allowed_attributes);
  }
  
  protected function register_attribute($name) {
    $this->allowed_attributes[] = $name;
  }
  
  protected function register_attributes($attributes) {
    $this->allowed_attributes = array_merge($this->allowed_attributes, $attributes);
  }
  
  public function set_attribute($name, $value) {
    if ($this->is_allowed_attribute($name)) {
      $this->attributes[$name] = $value;
    }
    return $this;
  }
  
  public function add_attributes($map) {
    $this->attributes += array_intersect_key(
      $map,
      array_flip(
        array_filter(
          array_keys($map), 
          array($this, 'is_allowed_attribute')
        )
      )
    );
  }
  
  public function get_attribute($name, $default_value = null, $type = null) {
    $value = null;
    if (isset($this->attributes[$name])) {
      $value = $this->attributes[$name];
    }
    if (!$value) {
      $value = $default_value;
    }
    if ($type) {
      settype($value, $type);
    }
    return $value;
  }
  
  public function get_attributes() {
    return $this->attributes;
  }

  public function set_iterator_data($data) {
    return $this->iterator_data = $data;
  }
  
  public function preprocesses_extension_output() {
    return $this instanceof STL_IExtensionOutputPreProcessor;
  }
  
  public function postprocesses_extension_output() {
    return $this instanceof STL_IExtensionOutputPostProcessor;
  }
  
  public function preprocesses_iterator_output() {
    return $this instanceof STL_IExtensionDataIteratorOutputPreProcessor;
  }
  
  public function postprocesses_iterator_output() {
    return $this instanceof STL_IExtensionDataIteratorOutputPostProcessor;
  }
  
  public function get_iterator() {
    if ($this instanceof STL_IExtensionDataIterator) {
      return $this;
    } else if (is_array($this->iterator_data)) {
      return new STL_ArrayIterator($this->iterator_data);
    }
    return null;
  }
  
  public abstract function init(STL_Context $context);
  
}

abstract class STL_AbstractTag extends STL_AbstractExtension {
  public function init(STL_Context $context) {}
  public abstract function render(STL_Context $context);
}
