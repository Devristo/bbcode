<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 31-1-14
 * Time: 23:32
 */

namespace Devristo\BBCode\Parser;


class VerbatimDecorator {
    public function __invoke(RenderContext $context, \DOMNode $node){
        if($node instanceof \DOMText)
            return $node->textContent;
        elseif($node instanceof BBDomElement)
            return $node->getOpenToken().$context->render($node).$node->getCloseToken();

        return new \InvalidArgumentException("A DOMText node or a BBDomElement should be passed as second argument");
    }
} 