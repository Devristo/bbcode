<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 31-1-14
 * Time: 20:16
 */

namespace Devristo\BBCode\Parser;


class DocumentBuilder {
    /**
     * @var BBDomElement[]
     */
    private $stack;

    /**
     * @var BBDomElement
     */
    private $active = null;

    public function __construct(){
        $this->active = new \DOMDocument();
        $this->active->registerNodeClass('DOMElement', '\Devristo\BBCode\Parser\BBDomElement');
        $this->active->registerNodeClass('DOMText', '\Devristo\BBCode\Parser\BBDomText');
        $this->stack = array($this->active);
    }

    public function writeStartElement($name, $openToken=null){
        $element = $this->stack[0]->createElement($name);

        if($openToken)
            $element->setOpenToken($openToken);

        if($this->active)
            $this->active->appendChild($element);

        array_push($this->stack, $element);
        $this->active = $element;
        return $this;
    }

    public function writeAttribute($name, $value){
        $this->active->setAttribute($name, $value);
        return $this;
    }

    public function writeEndElement($name=null, $closeToken=null){
        if(count($this->stack) == 1)
            return $this;

        if($name === null)
            $popped = array_pop($this->stack);
        else {
            for($i=count($this->stack)-1; $i > 0; $i--){
               if($this->stack[$i]->nodeName == $name){
                   $popped = $this->stack[$i];
                   $this->stack = array_slice($this->stack, 0, $i);
                   break;
               }
            }
        }

        if($closeToken != null)
            $popped->setCloseToken($closeToken);

        $this->active = $this->stack[count($this->stack) - 1];

        return $this;
    }

    public function writeText($text){
        $this->active->appendChild(new \DOMText($text));
        return $this;
    }

    /**
     * @return \DOMDocument
     */
    public function getDOMDocument(){
        return $this->stack[0];
    }
} 