<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 3-2-14
 * Time: 17:04
 */

namespace Devristo\BBCode;

use Devristo\BBCode\Parser\BBDomElement;
use Devristo\BBCode\Parser\RenderContext;

require_once(__DIR__."/../vendor/autoload.php");


class BBCodeTest extends \PHPUnit_Framework_TestCase {
    /**
     * @PHPUnit_Framework_TestCase
     */
    public function test_simple_no_nesting(){
        $bbcode = new BBCode();

        $html = $bbcode->toHtml("Hell[o] wo[i]r[/i]ld!");
        $this->assertEquals('Hell[o] wo<i>r</i>ld!', $html, "Parser might have been confused by [o] token if this fails");

        $html = $bbcode->toHtml("[b]Hello[/b] world");
        $this->assertEquals("<b>Hello</b> world", $html);

        $html = $bbcode->toHtml("[url]http://google.com[/url]");
        $this->assertEquals('<a href="http://google.com">http://google.com</a>', $html);
    }

    public function test_special_chars(){
        $bbcode = new BBCode();

        $html = $bbcode->toHtml("& test");

        $this->assertEquals("&amp; test", $html);
    }

    public function test_auto_url_disable(){
        $bbcode = new BBCode();

        $bbcode->setLinkify(false);
        $html = $bbcode->toHtml("Hello www.google.com");
        $this->assertEquals('Hello www.google.com', $html);
    }

    public function test_emoticons_text(){
        $bbcode = new BBCode();
        $bbcode->addEmoticon(':))');
        $bbcode->addEmoticon(':)');
        $bbcode->addEmoticon(':D');
        $bbcode->addEmoticon(':O');
        $bbcode->addEmoticon('<:O');

        $bbcode->getRenderContext()->setDecorator('emoticon', function(RenderContext $context, BBDomElement $element){
            return sprintf(
                "[emoticon]%s[/emoticon]",
                $element->getInnerBB()
            );
        });

        $html = $bbcode->toHtml('Hello:)):)www.google.com');
        $this->assertEquals('Hello[emoticon]:))[/emoticon][emoticon]:)[/emoticon]<a href="http://www.google.com">www.google.com</a>', $html);
    }

    public function test_emoticons_multiple(){
        $bbcode = new BBCode();
        $bbcode->addEmoticon(':)');
        $bbcode->addEmoticon(':(');

        $bbcode->getRenderContext()->setDecorator('emoticon', function(RenderContext $context, BBDomElement $element){
            return sprintf(
                "[emoticon]%s[/emoticon]",
                $element->getInnerBB()
            );
        });

        $html = $bbcode->toHtml('Hello:):):(');
        $this->assertEquals('Hello[emoticon]:)[/emoticon][emoticon]:)[/emoticon][emoticon]:([/emoticon]', $html);
    }

    public function test_fb_url(){
        $bbcode = new BBCode();

        $url = 'https://fbcdn-sphotos-d-a.akamaihd.net/hphotos-ak-xpf1/v/t1.0-9/10696319_10205012977821422_2934828278482148800_n.jpg?oh=90e3fb3f5246e671411242211fa15a37&oe=54D830EF&__gda__=1427610000_e7379c9d9f0ff30de955928498a4623';
        $html = $bbcode->toHtml("[img]{$url}[/img]");

        $encoded = htmlentities($url);
        $this->assertEquals("<img src=\"$encoded\">", $html);

    }

    public function test_inbalanced(){
        $bbcode = new BBCode();

        $html = $bbcode->toHtml("[b]Hello [i]world!");
        $this->assertEquals('<b>Hello <i>world!</i></b>', $html);
    }

    public function test_auto_http(){
        $bbcode = new BBCode();

        $html = $bbcode->toHtml("[url]google.com[/url]");
        $this->assertEquals('<a href="http://google.com">google.com</a>', $html, "Default renderer should make sure http is prepended if no email address is set as url");
    }

    public function test_auto_mailto(){
        $bbcode = new BBCode();

        $html = $bbcode->toHtml("[url]chris@example.com[/url]");
        $this->assertEquals('<a href="mailto:chris@example.com">chris@example.com</a>', $html, "Default renderer should make sure mailto is prepended if email address is set as url");
    }

    public function test_auto_url(){
        $bbcode = new BBCode();

        $html = $bbcode->toHtml("Hello www.google.com");
        $this->assertEquals('Hello <a href="http://www.google.com">www.google.com</a>', $html);
    }

    public function test_url_simple(){
        $bbcode = new BBCode();

        $html = $bbcode->toHtml("Hello [url]www.google.com[/]");
        $this->assertEquals('Hello <a href="http://www.google.com">www.google.com</a>', $html);
    }

    public function test_url_attribute(){
        $bbcode = new BBCode();

        $html = $bbcode->toHtml("Hello [url=www.google.com]test[/]");
        $this->assertEquals('Hello <a href="http://www.google.com">test</a>', $html);
    }


    public function test_img(){
        $bbcode = new BBCode();

        $html = $bbcode->toHtml("[img]http://example.com/logo.png[/img]");
        $this->assertEquals('<img src="http://example.com/logo.png">', $html);
    }

    public function test_tidy(){
        $bbcode = new BBCode();
        $document = $bbcode->toDocument("[b]Hello [p]big [/p] [p]awesome[/] world[/b]");

        $tidy = new Tidy();
        $tidy->addBlockTag("p");
        $tidy->tidy($document);

        $renderContext = RenderContext::create();
        $tidied = $renderContext->render($document);

        $this->assertEquals("[b]Hello [/b][p][b]big [/b][/p][b] [p]awesome[/] world[/b]", $tidied);
    }

    public function test_emoticons(){
        $bbcode = new BBCode();
        $bbcode->addEmoticon(':)');

        $bbcode->getRenderContext()->setDecorator('emoticon', function(RenderContext $context, BBDomElement $element){
            return sprintf(
                "[emoticon]%s[/emoticon]",
                $element->getInnerBB()
            );
        });

        $html = $bbcode->toHtml('Hello :)');
        $this->assertEquals('Hello [emoticon]:)[/emoticon]', $html);
    }

}
 