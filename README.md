bbcode
======
A simple yet flexible BBCode parser for PHP 5.4 and higher.

Features
--------
  * Transforms BBCode documents to a ```DOMDocument```. Can be altered using its API or queried using XPath.
  * Annotates smileys and urls in text nodes
  * Flexible recursive rendering mechanism.

Installation
------------
The easiest way to get started is by using composer. Since the API is not finalised you need to refer to
_devristo/bbcode_ as follows:

```JSON
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/Devristo/bbcode.git"
    }
  ], "require": {
    "devristo/bbcode": "dev-master"
  }
}
```

Minimal example
---------------
For basic BBCode _devristo/bbcode_ offers out of the box support.
This is the case for __[b]__, __[i]__, __[img]__, __[s]__, __[u]__ and __[url]__ tags.

```php
<?php
require_once("vendor/autoload.php");

use Devristo\BBCode\BBCode;

$bbcode = new BBCode();
$html = $bbcode->toHtml("Hello [b]world[/b]");

// Echoes 'Hello <b>world</b>'
echo $html;
```

Transforming BBCode to a DOMDocument
------------------------------------
Internally _devristo/bbcode_ parses BBCode into a DOMDocument before rendering its content to HTML.

```php
<?php
require_once("vendor/autoload.php");

use Devristo\BBCode\BBCode;

$bbcode = new BBCode();
$document = $bbcode->toDocument("Hello [b]world[/b]");

// Echoes 'world'
echo $document->getElementsByTagName("b")->item(0)->textContent;
```

###Linkification
By default _devristo/bbcode_ annotates all links by creating an __url__ element in the DOM tree.
You can disable this behaviour using ```$bbcode->setLinkify(false)```. The following example uses linkification
and the generated DOM model to query all urls used in the BBCode.

```php
<?php
require_once("vendor/autoload.php");

use Devristo\BBCode\BBCode;

$bbcode = new BBCode();
$document = $bbcode->toDocument("
  Hello visitor of www.github.com/Devristo/bbcode , 
  did you come here using [url]https://google.com/[/url] 
  or by [url=https://bing.com/]Bing.com[/url] ?
");

// Echoes:
// www.github.com/Devristo/bbcode
// https://google.com/
// https://bing.com/
foreach($document->getElementsByTagName("url") as $urlElement) {
    $url = $urlElement->getAttribute('url') ?: $urlElement->textContent;
    echo $url. PHP_EOL;
}
```

###Emoticons
Similar to links _devristo/bbcode_ annotates emoticons with the emoticon tag in the DOM tree. Emoticons are matched on 
word basis and must be defined using ```$bbcode->addEmoticon(':)')```. They can be parsed by setting a _decorator_ for
'emoticon' elements.

```php
<?php
require_once("vendor/autoload.php");

use Devristo\BBCode\BBCode;
use Devristo\BBCode\Parser\RenderContext;
use Devristo\BBCode\Parser\BBDomElement;

$bbcode = new BBCode();
$bbcode->addEmoticon(':)');
$bbcode->getRenderContext()->setDecorator(
 'emoticon', 
 function(RenderContext $context, BBDomElement $element){
    $images = array(
      ':)' => 'smile.gif'
    );
    
    $code = $element->getInnerBB();
    return '<img src="'.$images[$code].'" alt="'.$code.'">';
 }
);

// Echoes 'Hello world <img src="smile.gif" alt=":)">'
echo $bbcode->toHtml("Hello world :)");
```

Defining new tags and their decorators
-----------------
Eventhough all possible BBCode tags are parsed into the DOM, unknown tags will be rendered by the ```VerbatimDecorator```
which outputs the originally fed BBCode. To render a tag in a different way its decorator should be set to the
__RenderContext__.

```php
<?php
require_once("vendor/autoload.php");

use Devristo\BBCode\BBCode;
use Devristo\BBCode\Parser\RenderContext;
use Devristo\BBCode\Parser\BBDomElement;

$bbcode = new BBCode();
$bbcode->getRenderContext()->setDecorator(
 'spoiler', 
 function(RenderContext $context, BBDomElement $element){
    return '<div style="background: black; color: black">'.$context->render($element).'</div>';
 }
);

// Echoes '<div style="background: black; color: black">Hello <b>world</b></div>'
echo $bbcode->toHtml("[spoiler]hello [b]world[/spoiler]");
```

You can override internal _devristo/bbcode_ decorators by simple setting the decorator for the tags explicitly. If you
want to disable all internal decorators simply call ```$bbcode->getRenderContext()->removeAllDecorators()``` before 
defining your own.

Altering RenderContext on-the-fly
---------------------------------
Often you might want to change the rendering of decendent elements of a specific tag. For example we could define a 
__[quote]__ tag, and render nested quotes differently.

```php
<?php
require_once("vendor/autoload.php");

use Devristo\BBCode\BBCode;
use Devristo\BBCode\Parser\RenderContext;
use Devristo\BBCode\Parser\BBDomElement;

$bbcode = new BBCode();
$bbcode->getRenderContext()->setDecorator(
 'quote', 
 function(RenderContext $context, BBDomElement $element){
    // $context is the RenderContext used for direct and indirect descendants of the current element.
    
    $context->setDecorator('quote', function(RenderContext $context, BBDomElement $element){
      return '<blockquote>[ ... ]</blockquote>';
    });
    
    return '<blockquote>'.$context->render($element).'</blockquote>';
 }
);

// Echoes '<blockquote>Hello <blockquote>[ ... ]</blockquote></blockquote>'
echo $bbcode->toHtml("[quote]Hello [quote]world[/quote]");
```

Retrieving raw BBCode
---------------------
Sometimes we are content with the raw contents of an element. A good example is the following __[code]__ tag, which
renders everything between code-tags as is.

```php
<?php
require_once("vendor/autoload.php");

use Devristo\BBCode\BBCode;
use Devristo\BBCode\Parser\RenderContext;
use Devristo\BBCode\Parser\BBDomElement;

$bbcode = new BBCode();
$bbcode->getRenderContext()->setDecorator(
 'code', 
 function(RenderContext $context, BBDomElement $element){
    return '<pre>'.$element->getInnerBB().'</pre>';
 }
);

// Echoes '<pre>Hello [quote]world</pre>'
echo $bbcode->toHtml("[code]Hello [quote]world[/code]");
```
