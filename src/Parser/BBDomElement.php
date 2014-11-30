<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 31-1-14
 * Time: 23:23
 */

namespace Devristo\BBCode\Parser;


class BBDomElement extends \DOMElement{
    private $lineNo = null;

    public function ancestors(){
        $ancestors = array();

        $current = $this;

        while($parentNode = $current->parentNode){
            $ancestors[] = $parentNode;
            $current = $parentNode;
        }

        return $ancestors;
    }

    public function setOpenToken($token){
        $this->setAttribute('devristo_open', $token);
    }

    public function setCloseToken($token){
        $this->setAttribute('devristo_close', $token);
    }

    public function getCloseToken(){
        return $this->getAttribute('devristo_close');
    }

    public function getOpenToken(){
        return $this->getAttribute('devristo_open');
    }

    public function getLineNo(){
        return $this->lineNo == null ? parent::getLineNo() : $this->lineNo;
    }

    public function setLineNo($lineNo){
        $this->lineNo = $lineNo;
    }

    public function getInnerBB(){
        $result = '';
        foreach($this->childNodes as $child){
            /** @var $child BBDomElement */
            $result .= $child->getOuterBB();
        }

        return $result;
    }

    public function getOuterBB(){
        return $this->getOpenToken().$this->getInnerBB().$this->getCloseToken();
    }
} 