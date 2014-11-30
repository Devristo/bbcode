<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Chris
 * Date: 16-6-13
 * Time: 10:14
 * To change this template use File | Settings | File Templates.
 */
namespace Devristo\BBCode\Parser;

/**
 * Renderer of a UBB tag tree
 * @author Chris
 */
class Parser
{
    protected $knownTokens = array();
    protected $selfClosing = array();
    protected $emoticons = array();

    public function registerSelfClosingTag($token)
    {
        $token = strtolower($token);
        $this->selfClosing[$token] = true;

        return $this;
    }

    private function buildTree($in, DocumentBuilder $doc)
    {
        $pattern = '$(\[(?:/[a-z]*|[a-z]+[^\]]*)\])(\n){0,1}$i';

        // TOKENIZE input stream
        $tokens = preg_split($pattern, $in, -1, PREG_SPLIT_DELIM_CAPTURE);
        for ($i = 0; $i < count($tokens); $i++) {
            $token = $tokens[$i];

            /*
             * If token is an open tag create a TAG object
             * - Append tag to parent
             * - Set node to take ownership of children
             */
            if ($this->isOpenToken($token) && ($attributes = $this->parseAttributes($token)) !== null) {
                $tag = $this->getTagFromToken($token);
                $doc->writeStartElement($tag, $token);

                foreach($attributes as $key => $value){
                    $doc->writeAttribute($key, $value);
                }

                if(array_key_exists($tag, $this->selfClosing))
                    $doc->writeEndElement();

            } elseif ($this->isCloseToken($token)) {
                /*
                 * If close tag matches open tag remove it from the stack
                 * Set node to parent
                 */

                $tagName = $this->getTagFromToken($token);
                if(!$doc->writeEndElement($tagName, $token))
                    $doc->writeText($token);

            } else {
                $doc->writeText($token);
            }
        }

        return $doc->getDOMDocument();
    }

    protected function parseAttributes($token){
        $attributes = array();

        $currentKey = '';
        $currentValue = '';

        $state = 'name';

        for($i=1; $i<strlen($token); $i++){
            $char = $token[$i];

            switch($state){
                case 'name':
                    if($char == ']')
                        $state = 'done';
                    else if($char == '='){
                        $state = 'assignment';

                    } elseif(preg_match("/[a-z_][a-z_0-9]*/i", $char)) {
                        $currentKey .= $token[$i];
                    } else{
                        $currentKey = $currentValue = '';
                    }
                    break;
                case 'assignment':
                    if($char == '"')
                        $state = 'value-quoted';
                    else {
                        $i--;
                        $state = 'value';
                    }
                    break;
                case 'value-quoted':
                    if($char != '"')
                        $currentValue .= $char;
                    else {
                        $attributes[trim($currentKey)] = $currentValue;

                        $currentKey = $currentValue = '';
                        $state = 'name';
                    }
                    break;
                case 'value':
                    if(!preg_match('/[\s\],]/', $char))
                        $currentValue .= $char;
                    else {
                        $attributes[trim($currentKey)] = $currentValue;

                        $currentKey = $currentValue = '';

                        if($char == ']')
                            $state = 'done';
                        else $state = 'name';
                    }
            }
        }

        if($state != 'done')
            return null;

        return $attributes;
    }

    /**
     * Parses and renders the UBB code supplied
     * @param DocumentBuilder $doc The DocumentBuilder to parse the input to
     * @param string $input The TopicPost object or raw string to parse
     * @return \DOMDocument Returns the rendered HTML.
     */
    public function parse(DocumentBuilder $doc, $input)
    {
        try{
            $root = $this->buildTree($input, $doc);
        } catch(\Exception $e){
            $root = new \DOMDocument();
            $root->appendChild(new \DOMText($input));
        }

        return $root;
    }

    public function addEmoticon($code){
        $this->emoticons[$code] = true;
    }

    private function getTagFromToken($token)
    {
        $pattern = '$\[/?([a-z0-9]+)=?([^\]]*)\]$i';
        $matches = array();

        preg_match($pattern, $token, $matches);
        if(count($matches) > 1)
            return strtolower($matches[1]);
        else
            return null;
    }

    private function isOpenToken($str)
    {
        if (strlen($str) == 0) return false;

        $matchFormat = $str[0] == '[' && ctype_alpha($str[1]);

        if (!$matchFormat) return false;
        return $this->getTagFromToken($str);
    }

    private function isCloseToken($str)
    {
        if (strlen($str) == 0) return false;
        return preg_match('#^\[/[a-z0-9]*\]$#i', $str);
    }


}