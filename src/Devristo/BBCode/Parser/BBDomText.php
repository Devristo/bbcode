<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 3-2-14
 * Time: 17:14
 */

namespace Devristo\BBCode\Parser;


class BBDomText extends \DOMText{
    public function getInnerBB(){
        return $this->textContent;
    }

    public function getOuterBB(){
        return $this->textContent;
    }
} 