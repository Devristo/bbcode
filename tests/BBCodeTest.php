<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 3-2-14
 * Time: 17:04
 */

namespace Devristo\BBCode;

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

        $html = $bbcode->toHtml("www.google.com chris@example.com");
        $this->assertEquals('<a href="http://www.google.com">www.google.com</a> <a href="mailto:chris@example.com">chris@example.com</a>', $html);
    }

    public function test_img(){
        $bbcode = new BBCode();

        $html = $bbcode->toHtml("[img]http://example.com/logo.png[/img]");
        $this->assertEquals('<img src="http://example.com/logo.png">', $html);
    }

}
 