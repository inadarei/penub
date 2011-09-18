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
  protected $events;
  
  public function __construct($tag) {
    $this->tag        = $tag;
    $this->properties = (object) array();
    $this->events     = (object) array(
      'onpropertychange'  => array(),
      'onattributechange' => array(),
    );
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
      $this->attributechanged($attribute, $value);
    } else {
      $this->properties->{$attribute} = $value;
      $this->propertychanged($attribute, $value);
    }
  }
  
  public function __call($attribute, $args) {
    $value = array_shift($args);
    
    if (method_exists($this, $attribute)) {
      call_user_func(array($this, $attribute), $value);
    } else if (in_array($attribute, $this->allowed_attributes)) {
      $this->attributes[$attribute] = $value;
      $this->attributechanged($attribute, $value);
    } else if (property_exists($this->events, $attribute)) {
      $this->events->{$attribute}[$value][] = array_shift($args);
    }
    
    return $this;
  }
  
  public function __unset($attribute) {
    if (array_key_exists($attribute, $this->attributes)) {
      unset($this->attributes[$attribute]);
    } else if (property_exists($this->properties, $attribute)) {
      unset($this->properties->{$attribute});
    }
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
  
  private function propertychanged($property, $value) {
    if (is_array($this->events->onpropertychange[$property])) {
      foreach ($this->events->onpropertychange[$property] as $callback) {
        call_user_func($callback, $value, $this);
      }
    }
  }
  
  private function attributechanged($property, $value) {
    if (is_array($this->events->onattributechange[$property])) {
      foreach ($this->events->onattributechange[$property] as $callback) {
        call_user_func($callback, $value, $this);
      }
    }
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
        $this->class[] = 'error';
      }
    }
    return parent::__toString();
  }
  
}

class HTMLForm extends HTMLBaseFormElement implements HTMLElementWithBody {
  
  private $fields;
  
  public function __construct($name) {
    parent::__construct('form', $name);
    
    $this->fields = (object) array();
    
    $this->allowed_attributes[] = 'enctype';
    $this->allowed_attributes[] = 'method';
    $this->allowed_attributes[] = 'action';
    
    $this->name($name)->id($name)->method('get');
  }
  
  public function &__get($property) {
    if (property_exists($this->fields, $property)) {
      return $this->fields->{$property};
    }
    return parent::__get($property);
  }
  
  public function __set($property, $value) {
    if (property_exists($this->fields, $property)) {
      $this->fields->{$property}->value = $value;
    } else {
      parent::__set($property, $value);
    }
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
          $this->fields->{$element->name} = $element;
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
        $this->fields->{$child->name} = $child;
      }
    }
  }
  
}

function Form($name) {
  return new HTMLForm($name);
}

class Fieldset extends HTMLElement implements HTMLFormElement, HTMLElementWithBody  {
  
  public function __construct($id, $legend = NULL) {
    parent::__construct('fieldset');
    
    $this->properties->elements = array();
    $this->id = $id;
    
    if ($legend) {
      $element = new HTMLElement('legend');
      $element->append($legend);
      $this->properties->children[] = $element;
    }
  }
  
}

function Fieldset($id, $legend = NULL) {
  return new Fieldset($id, $legend);
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
    
    $this->onattributechange('id', array($this, 'handle_id_change'));
    
    $this->id = $name;
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
  
  protected function handle_id_change($id) {
    $id = preg_replace(
      array('~\]?\[~', '~[^-_:\.\w]~i'), 
      array('-', ''), $id
    );
    
    if (!empty($this->properties->label)) {
      $this->properties->label->for($id);
    }
    
    $this->attributes['id'] = $id;
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
    
    $this->allowed_attributes[] = 'size';
    
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
    $this->allowed_attributes[] = 'value';
    
    $this->value($value);
  }

  public function __toString() {
    $this->properties->children = array($this->value);
    return parent::__toString();
  }
  
}

function Textarea($name, $label = NULL, $value = NULL) {
  return new HTMLTextarea($name, $label, $value);
}

class HTMLOption extends HTMLElement {
  
  public function __construct($value, $text, $selected_value) {
    parent::__construct('option');
    
    $this->allowed_attributes[] = 'value';
    $this->allowed_attributes[] = 'selected';
    
    if (!$value) $value = $text;
    
    $this->value = $value;
    
    if ($value == $selected_value) {
      $this->selected = 'selected';
    }
    
    $this->properties->children[] = $text;
  }
  
}

class HTMLSelect extends HTMLFormInputElement implements HTMLFormElement {

  public function __construct($name, $label = NULL, $value = NULL) {
    parent::__construct('select', $name, $label);
    
    $this->allowed_attributes[] = 'value';
    $this->allowed_attributes[] = 'size';
    $this->allowed_attributes[] = 'multiple';
    
    $this->value = $value;
    
    $this->onattributechange('value', array($this, 'handle_value_change'));
  }
  
  public function options(array $options) {
    
    foreach ($options as $value => $text) {
      $this->properties->children[] = new HTMLOption($value, $text, $this->value);
    }
    
    return $this;
  }
  
  protected function handle_value_change($value) {
    foreach ($this->properties->children as $option) {
      unset($option->selected);
      if ($option->value == $value) {
        $option->selected = 'selected';
      }
    }
  }
  
}

function Select($name, $label = NULL, $value = NULL) {
  return new HTMLSelect($name, $label, $value);
}