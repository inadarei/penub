<?php

interface HTMLFormElement {}
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
    $this->properties         = NULL;
  }
  
}

class HTMLElement extends HTMLBaseElement implements HTMLElementWithBody {
  public function append() {
    $elements = func_get_args();

    foreach ($elements as $element) {
      $this->properties->children[] = $element;
    }
    
    return $this;
  }
}

function HTMLElement($tag) {
  return new HTMLElement($tag);
}

abstract class HTMLBaseFormElement extends HTMLBaseElement {
  
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
  
  public function __toString() {
    foreach ($this->validator as $validator) {
      if (!call_user_func_array($validator, array($this))) {
        $this->attributes['class'][] = 'error';
      }
    }
    return parent::__toString();
  }
  
}

class HTMLForm extends HTMLBaseFormElement implements HTMLElementWithBody {
  
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
        
        if ($element instanceof Fieldset) {
          $this->append_fieldset($element);
        } else {
          $this->properties->elements[] = $element;
        }
        
      }
      
      $this->properties->children[] = $element;
    }
    
    return $this;
  }
  
  private function append_fieldset($fieldset) {
    foreach ($fieldset->children as $child) {
      $child->form = $this;
      if ($child instanceof Fieldset) {
        $this->append_fieldset($child);
      } else if ($child instanceof HTMLFormElement) {
        $fieldset->elements[] = $child;
        $this->properties->elements[] = $child;
      }
    }
  }
  
}

function Form($name) {
  return new HTMLForm($name);
}

class HTMLLabel extends HTMLBaseElement {
  
  public function __construct($label, $for, $title = NULL) {
    parent::__construct('label');
    
    $this->allowed_attributes[] = 'for';
    $this->allowed_attributes[] = 'title';
    
    $this->for($for)->title($title);
    $this->properties->children = array($label);
  }
  
  public function __toString() {
    $this->properties->children[] = ':';
    
    if ($this->properties->input->required == TRUE) {
      $this->properties->children[] = HTMLElement('span')->class(array('required'))->append('*');
    }
    
    return parent::__toString();
  }
  
}

function Label($label, $for = NULL, $title = NULL) {
  return new HTMLLabel($label, $for, $title);
}

abstract class HTMLFormInputElement extends HTMLBaseFormElement implements HTMLFormElement {

  public function __construct($tag, $name, $label = NULL) {
    $tokens = explode('/', trim($name, '\/'));
    
    if (sizeof($tokens) > 1) {
      $name = sprintf('%s[%s]', array_shift($tokens), implode('][', $tokens));
    }
    
    parent::__construct($tag, $name);
    
    $this->label($label);
    $this->properties->required = FALSE;
  }
  
  public function default_value($value) {
    $this->properties->default_value = $value;
    return $this;
  }
  
  public function label($label) {
    if ($label) {
      $label = new HTMLLabel($label, $this->id);
      $this->properties->label = $label;
      $label->properties->input = $this;
    }
    return $this;
  }
  
  public function id($id) {
    $this->attributes['id'] = preg_replace(
      array('~\]?\[~', '~[^-_:\.\w]~i'), 
      array('-', ''), $id
    );
    
    if (!empty($this->properties->label)) {
      $this->properties->label->for($id);
    }
    
    return $this;
  }
  
  public function required($flag) {
    $this->properties->required = !!$flag;
    return $this;
  }
  
  public function __toString() {
    return (string) $this->label . parent::__toString();
  }
  
}

abstract class HTMLInputElement extends HTMLFormInputElement implements HTMLVoidElement, HTMLFormElement {
  
  public function __construct($name, $label = NULL, $value = NULL) {
    parent::__construct('input', $name, $label);

    $this->allowed_attributes[] = 'type';
    $this->allowed_attributes[] = 'value';
    
    $this->value($value);
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

class HTMLTextarea extends HTMLFormInputElement implements HTMLFormElement {
  
  public function __construct($name, $label = NULL, $value = NULL) {
    parent::__construct('textarea', $name, $label);
    
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

class Fieldset extends HTMLElement implements HTMLFormElement, HTMLElementWithBody  {
  
  public function __construct($legend) {
    parent::__construct('fieldset');
    
    $this->properties->elements = array();
    
    if ($legend) {
      $element = new HTMLElement('legend');
      $element->append($legend);
      $this->properties->children[] = $element;
    }
  }
  
}

function Fieldset($legend) {
  return new Fieldset($legend);
}