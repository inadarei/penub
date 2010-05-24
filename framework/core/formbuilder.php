<?php

interface HTMLVoidElement {}
interface HTMLElementWithBody {
  public function append();
}

abstract class HTMLBaseElement {
  
  protected $allowed_attributes = array('id', 'class', 'style');
  protected $attributes         = array();
  protected $tag                = NULL;
  protected $properties;
  
  public function __construct($tag) {
    $this->tag        = $tag;
    $this->properties = (object) array();
  }
  
  public function &__get($attribute) {
    if (isset($this->attributes[$attribute])) {
      return $this->attributes[$attribute];
    } else if (property_exists($this->properties, $attribute)) {
      return $this->properties->{$attribute};
    }
    return NULL;
  }
  
  public function __set($attribute, $value) {
    if (in_array($attribute, $this->allowed_attributes)) {
      $this->attributes[$attribute] = $value;
    } else {
      $this->properties->{$attribute} = $value;
    }
  }
  
  public function __call($attribute, $args) {
    $value = array_shift($args);
    
    if (method_exists($this, $attribute)) {
      call_user_func(array($this, $attribute), $value);
    } else if (in_array($attribute, $this->allowed_attributes)) {
      $this->attributes[$attribute] = $value;
    }
    
    return $this;
  }
  
  public function __toString() {
    $attributes = NULL;

    //Penub::hook('alter_element_'. $this->id, $this);
    
    foreach ($this->attributes as $key => $value) {
    
      if (is_array($value)) {
        $value = filter_var_array($value, FILTER_SANITIZE_STRING);
        $value = implode(' ', $value);
      } else {
        $value = filter_var($value, FILTER_SANITIZE_STRING);
      }
      
      $attributes .= sprintf(' %s="%s"', $key, $value);
      
    }
    
    $html = array();
    
    if ($this instanceof HTMLVoidElement) {
      $html[] = sprintf('<%s%s/>', $this->tag, $attributes);
    } else {
      
      $html[] = sprintf('<%s%s>', $this->tag, $attributes);
      
      $children = $this->children;
      
      foreach ($children as $child) {
        $html[] = (string) $child;
      }
      
      $html[] = sprintf('</%s>', $this->tag);
      
    }

    return implode('', $html);  
  }
   
  public function __destruct() {
    $this->attributes         = NULL;
    $this->allowed_attributes = NULL;
  }
  
}

abstract class HTMLElement extends HTMLBaseElement implements HTMLElementWithBody {
  public function append() {
    $elements = func_get_args();
    foreach ($elements as $element) {
      $this->properties->children[] = $element;
    }
  }
}

abstract class HTMLFormElement extends HTMLBaseElement {
  
  public function __construct($tag, $name) {
    parent::__construct($tag);
    
    $this->allowed_attributes[]  = 'name';
    $this->properties->validator = array();
    
    $this->name($name)->id($name);
  }
  
  public function validator($callback) {
    $this->properties->validator[] = $callback;
    return $this;
  }
  
  public function default_value($value) {
    $this->properties->default_value = $value;
  }
  
  public function __toString() {
    foreach ($this->validator as $validator) {
      if (!call_user_func_array($validator, array($this->value, $this))) {
        $this->attributes['class'][] = 'error';
      }
    }
    return parent::__toString();
  }
  
}

class HTMLForm extends HTMLFormElement implements HTMLElementWithBody {
  
  public function __construct($name) {
    parent::__construct('form', $name);
    
    $this->allowed_attributes[] = 'enctype';
    $this->allowed_attributes[] = 'method';
    $this->allowed_attributes[] = 'action';
    
    $this->name($name)->id($name)->method('get');
  }
  
  public function append() {
    $elements = func_get_args();

    foreach ($elements as $element) {
      
      if ($element instanceof HTMLFormElement) {
        $element->form = $this;
        $this->properties->elements[] = $element;
      }
      
      $this->properties->children[] = $element;
    }
    
    return $this;
  }
  
}

function Form($name) {
  return new HTMLForm($name);
}

abstract class HTMLInputElement extends HTMLFormElement implements HTMLVoidElement {
  
  public function __construct($name, $label = NULL, $value = NULL) {
    parent::__construct('input', $name);

    $this->allowed_attributes[] = 'type';
    $this->allowed_attributes[] = 'value';
    
    $this->label($label)->value($value);
  }
  
}

class HTMLInputTypeText extends HTMLInputElement {
  public function __construct($name, $label = NULL, $value = NULL) {
    parent::__construct($name, $label, $value);
    $this->type('text');
  }
}

function Text($name, $label = NULL, $value = NULL) {
  return new HTMLInputTypeText($name, $label, $value);
}

class HTMLTextarea extends HTMLFormElement {
  
  public function __construct($name, $label = NULL, $value = NULL) {
    parent::__construct('textarea', $name);
    
    $this->allowed_attributes[] = 'rows';
    $this->allowed_attributes[] = 'cols';
    
    $this->value($value);
  }
  
  public function value($value) {
    $this->value = $value;
    return $this;
  }
  
  public function __toString() {
    $this->properties->children = array($this->value);
    return parent::__toString();
  }
  
}

function Textarea($name, $label = NULL, $value = NULL) {
  return new HTMLTextarea($name, $label, $value);
}