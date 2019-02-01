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
        uksort($this->emoticons, function($a, $b){
            return strlen($b) - strlen($a);
        });
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

        $text = $node->textContent;
        $matches = array();

        /*
         * Find all URLS in the text node and retrieve their offsets
         */
        $markers = array(
            0 => 'text',
            strlen($text) => 'end'
        );

        if($this->getLinkify() && preg_match_all('/\\b(?P<url>(?:[a-z]+:\/\/|www.)[^\s$\'"]+)/i', $text, $matches, PREG_OFFSET_CAPTURE)) {
            $cursor = 0;
            foreach ($matches['url'] as $match) {
                $offset = $match[1];
                $value = $match[0];

                if($offset >= $cursor && !array_key_exists($cursor, $markers))
                    $markers[$cursor] = 'text';

                $markers[$offset] = 'url';

                $next = $offset+strlen($value);
                if(!array_key_exists($next, $markers))
                    $markers[$next] = 'text';
            }
        }

        $smilies = $this->buildEmoticonTree();

        $copyMarkers = $markers;

        $offsets = array_keys($copyMarkers);
        $values = array_values($copyMarkers);

        for ($i=1; $i<count($copyMarkers); $i++) {
            $current = [$offsets[$i-1], $values[$i-1]];
            $next = [$offsets[$i], $values[$i]];

            list($offset, $type) = $current;
            list($end,) = $next;

            if($type == 'text') {
                $currentMatchingSet = $smilies;
                $stack = '';
                for ($i = $offset; $i < $end + 1; $i++) {
                    $char = $i < $end ? $text[$i] : '';

                    $newCharMatches = $char && array_key_exists($char, $currentMatchingSet);

                    // Oops new character wouldn't match any emoticons, but we did match a emoticon till now!
                    // Note: this also happens when we read past the end
                    if (!$newCharMatches && array_key_exists('', $currentMatchingSet)) {
                        $markers[$i - strlen($stack)] = 'emoticon';

                        // Insert a marker right after the emoticon, if it doesn't exist already
                        if (!array_key_exists($i, $markers))
                            $markers[$i] = 'text';

                        //
                        $stack = '';
                        $currentMatchingSet = $smilies;
                    }

                    // Character yields emoticon candidates, so add the char to the stack and prune the matching set
                    if (array_key_exists($char, $currentMatchingSet)) {
                        $currentMatchingSet = $currentMatchingSet[$char];
                        $stack .= $char;
                    } // No emoticons would be left, so ignore this char and start over
                    else {
                        $stack = '';
                        $currentMatchingSet = $smilies;
                    }
                }
            }
        }

        ksort($markers);
        reset($markers);

        $offsets = array_keys($markers);
        $values = array_values($markers);

        for ($i=1; $i<count($markers); $i++) {
            $current = [$offsets[$i-1], $values[$i-1]];
            $next = [$offsets[$i], $values[$i]];

            list($start, $type) = $current;
            list($end,) = $next;
            $part = substr($text, $start, $end-$start);

            $value = new BBDomText($part);

            if($type == 'emoticon'){
                $emoticon = $node->ownerDocument->createElement('emoticon');
                $emoticon->appendChild($value);
                $parentNode->insertBefore($emoticon, $node);
            } elseif($type == 'url' && $this->getLinkify()){
                $url = $node->ownerDocument->createElement('url');
                $url->appendChild($value);
                $parentNode->insertBefore($url, $node);
            } else{
                $parentNode->insertBefore($value, $node);
            }
        }

        $parentNode->removeChild($node);
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

    private function buildEmoticonTree()
    {
        $smilies = array();
        foreach($this->emoticons as $code => $value){
            $node = &$smilies;

            for($i=0; $i<strlen($code); $i++) {
                $char = $code[$i];
                if(!array_key_exists($char, $node))
                    $node[$char] = array();

                $node =& $node[$char];

                if($i + 1 == strlen($code)){
                    $node[''] = $code;
                }
            }
        }
        return $smilies;
    }
}