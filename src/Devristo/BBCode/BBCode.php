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
use Devristo\BBCode\Parser\VerbatimDecorator;

class BBCode {
    protected $emoticons = array();
    protected $linkify = true;

    public function __construct(){
        $this->renderContext = RenderContext::create();
        $this->parser = new Parser();
        $this->initDefaultDecorators();
    }

    protected function initDefaultDecorators(){
        $this->renderContext->setTextDecorator(function(RenderContext $context, BBDomText $node){
            $text = preg_replace('~\R~u', "\n", $node->textContent);
            $text = htmlentities($text, null, 'utf-8');
            $text = nl2br($text);

            return $text;
        });

        $this->renderContext->setDecorator('url', function(RenderContext $context, BBDomElement $node){
            if($node->hasAttribute("url")){
                $url = $node->getAttribute('url');
                $context->setDecorator('url', new VerbatimDecorator());
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

    public function addEmoticon($code){
        $this->emoticons[$code] = true;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getLinkify()
    {
        return $this->linkify;
    }

    /**
     * @param boolean $linkify
     * @return $this
     */
    public function setLinkify($linkify)
    {
        $this->linkify = $linkify;
        return $this;
    }

    public function getParser()
    {
        return $this->parser;
    }

    public function setParser(Parser $parser){
        $this->parser = $parser;
        return $this;
    }

    public function getRenderContext(){
        return $this->renderContext;
    }

    public function setRenderContext($renderContext){
        $this->renderContext = $renderContext;
        return $this;
    }

    protected function identifyWord($word)
    {
        if(preg_match("#(http://|https://|www\\.)[^\\s]+#", $word) == 1)
            return 'url';
        elseif(array_key_exists($word, $this->emoticons))
            return 'emoticon';
        else
            return 'text';
    }

    protected function parseText(BBDomText $node){
        $parentNode = $node->parentNode;

        $parts = preg_split('#(\s+)#', $node->textContent, -1, PREG_SPLIT_DELIM_CAPTURE);

        $text = '';
        foreach($parts as $word){
            $type = $this->identifyWord($word);

            if(!$this->getLinkify() && $type=='url')
                $type = 'text';

            if($type == 'text'){
                if($text == null)
                    $text = $word;
                else $text .= $word;
            }else if($type == 'url'){
                if(strlen($text) > 0) {
                    $parentNode->insertBefore(new BBDomText($text), $node);
                    $text = '';
                }

                $parentNode->insertBefore(new BBDomElement('url', $word), $node);
            }elseif($type == 'emoticon'){
                if(strlen($text) > 0){
                    $parentNode->insertBefore(new BBDomText($text), $node);
                    $text = '';
                }

                $parentNode->insertBefore(new BBDomElement('emoticon', $word), $node);
            }
        }

        if(strlen($text) > 0)
            $parentNode->insertBefore(new BBDomText($text), $node);

        $node->parentNode->removeChild($node);
    }

    private function postProcess(\DOMDocument $document){
        $nodes = new \SplQueue();
        $nodes->push($document);

        while($nodes->count()){
            $current = $nodes->pop();

            if($current instanceof BBDomText)
                $this->parseText($current);
            else{
                foreach($current->childNodes as $child)
                    $nodes->push($child);
            }

        }
    }

    public function toDocument($bbcode){
        $builder = new DocumentBuilder();
        $this->parser->parse($builder, $bbcode);

        $document = $builder->getDOMDocument();
        $this->postProcess($document);

        return $document;
    }

    public function toHtml($bbcode){
        $document = $this->toDocument($bbcode);
        return $this->renderContext->render($document);
    }
}