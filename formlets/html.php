<?php
/******************************************************************************
 * Copyright (c) 2014 Richard Klees <richard.klees@rwth-aachen.de>
 *
 * Representation of html entities. This does not in any way guarantee to 
 * produce valid HTML or something.
 */

require_once("checking.php");
require_once("helpers.php");

abstract class HTML {
    abstract public function render();
}

class HTMLNop extends HTML {
    public function render() {
        return "";
    }
}

class HTMLText extends HTML {
    private $_text; // string

    public function __construct($text) {
        guardIsString($text);
        $this->_text = $text;
    }

    public function render() {
        return $this->_text;
    }
}

class HTMLConcat extends HTML {
    private $_left; // HTML 
    private $_right; // HTML 

    public function __construct(HTML $left, HTML $right) {
        $this->_left = $left;
        $this->_right = $right;
    }

    public function render() {
        return $this->_left->render().$this->_right->render();
    }
}

class HTMLArray extends HTML {
    private $_content; // array of HTML

    public function __construct($content) {
        guardEach($content, "guardHTML");
    }

    public function render() {
        $res = "";
        foreach ($this->_content as $cont) {
            $res .= $cont->render();
        }
        return $res;
    }
}

class HTMLTag extends HTML {
    private $_name; // string
    private $_attributes; // dict of string => string
    private $_content; // maybe HTML

    public function __construct($name, $attributes, $content) {
        guardIsString($name);
        guardEachAndKeys($attributes, "guardIsString", "guardIsString");
        guardIfNotNull($content, "guardIsHTML");
        $this->_name = $name;
        $this->_attributes = $attributes;
        $this->_content = $content;
    }

    public function render() {
        $head = "<".$this->_name
                   .keysAndValuesToHTMLAttributes($this->_attributes);
        if ($this->_content === null || $this->_content instanceof HTMLNop) {
            return $head."/>";
        }
        return $head.">".$this->_content->render()."</".$this->_name.">";        
    }
}

function html_nop() {
    return new HTMLNop();
}
    
function html_tag($name, $attributes, $content = null) {
    return new HTMLTag($name, $attributes, $content);
}

function html_text($content) {
    return new HTMLText($content);
}

function html_concat(HTML $left, HTML $right) {
    return new HTMLConcat($left, $right);
}

function html_concatA($array) {
    return new HTMLArray($array);
}

?>
