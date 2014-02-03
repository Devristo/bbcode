<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 3-2-14
 * Time: 17:09
 */

namespace Devristo\BBCode;


use Devristo\BBCode\Parser\DocumentBuilder;
use Devristo\BBCode\Parser\BBDomElement;
use Devristo\BBCode\Parser\BBDomText;
use Devristo\BBCode\Parser\Parser;
use Devristo\BBCode\Parser\RenderContext;

class BBCode {
    public function __construct(){
        $this->renderContext = RenderContext::create();

        $this->renderContext->setTextDecorator(function(RenderContext $context, BBDomText $node){
            $text = htmlentities($node->textContent, null, 'utf-8');

            $text = preg_replace_callback("#http(?:s?)://[^\\s]+#i", function($match){
                return sprintf('<a href="%s">%s</a>', $match[0], $match[0]);
            }, $text);

            $text = preg_replace_callback("#www\\.[^\\s]+#i", function($match){
                return sprintf('<a href="http://%s">%s</a>', $match[0], $match[0]);
            }, $text);

            $text = preg_replace_callback('/\\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\\b/i', function($match){
                return sprintf('<a href="mailto:%s">%s</a>', $match[0], $match[0]);
            }, $text);

            return $text;
        });

        $this->renderContext->setDecorator('url', function(RenderContext $context, BBDomElement $node){
            if($node->hasAttribute("url")){
                $url = $node->getAttribute('url');
                $content = $context->render($node);
            } else {
                $url = $content = $node->getInnerBB();
                $content = htmlentities($content, null, 'utf-8');
            }

            $chars = count_chars($url);
            if (substr($url, 0,7) !== 'http://' && substr($url, 0,8) !== 'https://' && $chars[ord('@')] != 1)
                $url = 'http://' . $url;
            elseif(preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i', $url))
                $url = 'mailto:' . $url;

            return sprintf('<a href="%s">%s</a>', htmlentities($url, null, 'utf-8'), $content);
        });

        $this->renderContext->setDecorator('i', function(RenderContext $context, BBDomElement $node){
            return sprintf("<i>%s</i>", $context->render($node));
        });

        $this->renderContext->setDecorator('b', function(RenderContext $context, BBDomElement $node){
            return sprintf("<b>%s</b>", $context->render($node));
        });

        $this->renderContext->setDecorator('u', function(RenderContext $context, BBDomElement $node){
            return sprintf("<u>%s</u>", $context->render($node));
        });

        $this->renderContext->setDecorator('img', function(RenderContext $context, BBDomElement $node){
            $url = $node->getInnerBB();

            return sprintf('<img src="%s">', htmlentities($url, null, 'utf-8'));
        });
    }

    public function getRenderContext(){
        return $this->renderContext;
    }

    public function toHtml($bbcode){
        $parser = new Parser();
        $doc = new DocumentBuilder();

        $parser->parse($doc, $bbcode);
        return $this->renderContext->render($doc->getDOMDocument());
    }
}