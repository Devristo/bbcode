<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 11-2-14
 * Time: 20:37
 */

namespace Devristo\BBCode;


class Tidy {
    protected $blockTags = array();

    public function __construct(){
        $this->addBlockTag("#document");
    }

    protected static function normalize($tagName){
        return strtolower($tagName);
    }

    public function addBlockTag($tagName){
        $this->blockTags[self::normalize($tagName)] = true;
    }

    public function removeBlockTag($tagName){
        $tagName = self::normalize($tagName);
        if(array_key_exists($tagName, $this->blockTags))
            unset($this->blockTags[$tagName]);
    }

    public function ensureValidBlockNesting(\DOMDocument $document){
        $stack = array($document);

        while (count($stack)) {
            /** @var $parent \DOMNode */
            $parent = array_pop($stack);

            if ($parent->hasChildNodes()) {
                for ($i = 0; $i < $parent->childNodes->length; $i++) {
                    $child = $parent->childNodes->item($i);
                    $valid = !array_key_exists($child->nodeName, $this->blockTags) || array_key_exists($parent->nodeName, $this->blockTags);

                    if (!$valid) {
                        /**
                         * Seems like PARENT is an inline node while CHILD is a BLOCK node
                         *
                         * Steps to fix:
                         *  - All siblings of CHILD on the left should remain in tact, copy them to a clone of PARENT on the left
                         *  - Insert CHILD to the left of the ORIGINAL parent (so right of the siblings above)
                         *      - Adopt original children of CHILD by a copy of the INLINE PARENT
                         *  - On the right of CHILD all elements are unaffected, and will be processed in next iteration
                         */

                        $grandDad = $parent->parentNode;

                        // Preserve siblings on the left
                        if ($i > 0) {
                            $left = $parent->cloneNode(false);
                            for ($j = 0; $j < $i; $j++)
                                $left->appendChild($parent->childNodes->item($j));

                            $grandDad->insertBefore($left, $parent);
                        }

                        // Promote child
                        $grandDad->insertBefore($child, $parent);

                        // Adopt grandchildren
                        $clonedParent = $parent->cloneNode(false);
                        $child->appendChild($clonedParent);

                        foreach($child->childNodes as $grandChild){
                            $clonedParent->appendChild($grandChild);
                        }

                    }

                    array_push($stack, $child);
                }
            }
        }
    }

    public function tidy(\DOMDocument $document)
    {
        $this->ensureValidBlockNesting($document);
    }
} 