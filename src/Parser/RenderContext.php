<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 31-1-14
 * Time: 20:54
 */

namespace Devristo\BBCode\Parser;

class RenderContext {
    private $decorators = array();
    private $defaultDecorator = null;

    protected function __construct($defaultDecorator=null, array $decorators = null){
        $this->decorators = $decorators ? $decorators : array();
        $this->defaultDecorator = $defaultDecorator;
    }

    public function removeDecorator($tag){
        if(array_key_exists($tag, $this->decorators))
            unset($this->decorators[$tag]);
    }

    public function setDefaultDecorator($f){
        $this->defaultDecorator = $f;
    }

    public function removeAllDecorators(){
        $this->decorators = [];
    }

    public static function create(){

        $context = new self();
        $context->setDefaultDecorator(new VerbatimDecorator());

        return $context;
    }

    public function setTextDecorator($f){
        return $this->setDecorator('#text', $f);
    }

    public function setDecorator($name, $f){
        $this->decorators[$name] = $f;

        return $this;
    }

    /**
     * @param $name
     * @return callable
     */
    public function getDecorator($name){
        return array_key_exists($name, $this->decorators) ? $this->decorators[$name] : $this->defaultDecorator;
    }

    public function render($node){
        if($node->nodeName == '#text')
            $nodes = array($node);
        else $nodes = $node->childNodes;

        $result = '';
        foreach($nodes as $child){
            $newContext = new self($this->defaultDecorator, $this->decorators);
            $decorator = $this->getDecorator($child->nodeName);
            $result .= $decorator($newContext, $child);
        }

        return $result;
    }
}
