<?php

namespace DiDom\Tests;

use DiDom\Query;
use InvalidArgumentException;
use RuntimeException;

class QueryTest extends TestCase
{
    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage DiDom\Query::compile expects parameter 1 to be string, NULL given
     */
    public function testCompileWithNonStringExpression()
    {
        Query::compile(null);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage DiDom\Query::compile expects parameter 2 to be string, NULL given
     */
    public function testCompileWithNonStringExpressionType()
    {
        Query::compile('h1', null);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Unknown expression type "foo"
     */
    public function testCompileWithUnknownExpressionType()
    {
        Query::compile('h1', 'foo');
    }

    /**
     * @dataProvider compileCssTests
     */
    public function testCompileCssSelector($selector, $xpath)
    {
        $this->assertEquals($xpath, Query::compile($selector));
    }

    /**
     * @dataProvider getSegmentsTests
     *
     * @param string $selector
     * @param array $segments
     */
    public function testGetSegments($selector, $segments)
    {
        $this->assertEquals($segments, Query::getSegments($selector));
    }

    /**
     * @dataProvider buildXpathTests
     *
     * @param array $segments
     * @param string $xpath
     */
    public function testBuildXpath($segments, $xpath)
    {
        $this->assertEquals($xpath, Query::buildXpath($segments));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testBuildXpathWithEmptyArray()
    {
        Query::buildXpath(array());
    }

    /**
     * @expectedException \DiDom\Exceptions\InvalidSelectorException
     * @expectedExceptionMessage The expression must not be empty
     */
    public function testCompileWithEmptyXpathExpression()
    {
        Query::compile('', Query::TYPE_XPATH);
    }

    /**
     * @expectedException \DiDom\Exceptions\InvalidSelectorException
     * @expectedExceptionMessage The expression must not be empty
     */
    public function testCompileWithEmptyCssExpression()
    {
        Query::compile('', Query::TYPE_CSS);
    }

    /**
     * @expectedException \DiDom\Exceptions\InvalidSelectorException
     * @expectedExceptionMessage The selector must not be empty
     */
    public function testGetSegmentsWithEmptySelector()
    {
        Query::getSegments('');
    }

    /**
     * @expectedException \DiDom\Exceptions\InvalidSelectorException
     * @expectedExceptionMessage Invalid selector "input[=foo]": attribute name must not be empty
     */
    public function testEmptyAttributeName()
    {
        Query::compile('input[=foo]');
    }

    /**
     * @expectedException \DiDom\Exceptions\InvalidSelectorException
     * @expectedExceptionMessage Unknown pseudo-class "unknown-pseudo-class"
     */
    public function testUnknownPseudoClass()
    {
        Query::compile('li:unknown-pseudo-class');
    }

    /**
     * @dataProvider containsInvalidCaseSensitiveParameterDataProvider
     */
    public function testContainsInvalidCaseSensitiveParameter($caseSensitive)
    {
        $message = sprintf('Parameter 2 of "contains" pseudo-class must be equal true or false, "%s" given', $caseSensitive);

        $this->setExpectedException('DiDom\Exceptions\InvalidSelectorException', $message);

        Query::compile("a:contains('Log in', {$caseSensitive})");
    }

    public function containsInvalidCaseSensitiveParameterDataProvider()
    {
        return array(
            array('foo'),
            array('TRUE'),
            array('FALSE'),
        );
    }

    /**
     * @expectedException \DiDom\Exceptions\InvalidSelectorException
     * @expectedExceptionMessage nth-child (or nth-last-child) expression must not be empty
     */
    public function testEmptyNthExpression()
    {
        Query::compile('li:nth-child()');
    }

    /**
     * @expectedException \DiDom\Exceptions\InvalidSelectorException
     * @expectedExceptionMessage Invalid property "::"
     */
    public function testEmptyProperty()
    {
        Query::compile('li::');
    }

    /**
     * @expectedException \DiDom\Exceptions\InvalidSelectorException
     * @expectedExceptionMessage Unknown property "foo"
     */
    public function testInvalidProperty()
    {
        Query::compile('li::foo');
    }

    /**
     * @expectedException \DiDom\Exceptions\InvalidSelectorException
     * @expectedExceptionMessage Invalid nth-child expression "foo"
     */
    public function testUnknownNthExpression()
    {
        Query::compile('li:nth-child(foo)');
    }

    /**
     * @expectedException \DiDom\Exceptions\InvalidSelectorException
     * @expectedExceptionMessage Invalid selector "."
     */
    public function testGetSegmentsWithEmptyClassName()
    {
        Query::getSegments('.');
    }

    /**
     * @expectedException \DiDom\Exceptions\InvalidSelectorException
     * @expectedExceptionMessage Invalid selector "."
     */
    public function testCompilehWithEmptyClassName()
    {
        Query::compile('span.');
    }

    public function testCompileXpath()
    {
        $this->assertEquals('//div', Query::compile('//div', Query::TYPE_XPATH));
    }

    public function testSetCompiledInvalidArgumentType()
    {
        if (PHP_VERSION_ID >= 70000) {
            $this->setExpectedException('TypeError');
        } else {
            $this->setExpectedException('PHPUnit_Framework_Error');
        }

        Query::setCompiled(null);
    }

    public function testSetCompiled()
    {
        $xpath = "//*[@id='foo']//*[contains(concat(' ', normalize-space(@class), ' '), ' bar ')]//baz";
        $compiled = array('#foo .bar baz' => $xpath);

        Query::setCompiled($compiled);

        $this->assertEquals($compiled, Query::getCompiled());
    }

    public function testGetCompiled()
    {
        Query::setCompiled(array());

        $selector = '#foo .bar baz';
        $xpath = '//*[@id="foo"]//*[contains(concat(" ", normalize-space(@class), " "), " bar ")]//baz';
        $compiled = array($selector => $xpath);

        Query::compile($selector);

        $this->assertEquals($compiled, Query::getCompiled());
    }

    public function compileCssTests()
    {
        $compiled = array(
            array('a', '//a'),
            array('foo bar baz', '//foo//bar//baz'),
            array('foo > bar > baz', '//foo/bar/baz'),
            array('#foo', '//*[@id="foo"]'),
            array('.bar', '//*[contains(concat(" ", normalize-space(@class), " "), " bar ")]'),
            array('*[foo=bar]', '//*[@foo="bar"]'),
            array('*[foo="bar"]', '//*[@foo="bar"]'),
            array('*[foo=\'bar\']', '//*[@foo="bar"]'),
            array('select[name=category] option[selected=selected]', '//select[@name="category"]//option[@selected="selected"]'),
            array('*[^data-]', '//*[@*[starts-with(name(), "data-")]]'),
            array('*[^data-=foo]', '//*[@*[starts-with(name(), "data-")]="foo"]'),
            array('a[href^=https]', '//a[starts-with(@href, "https")]'),
            array('img[src$=png]', '//img[substring(@src, string-length(@src) - string-length("png") + 1) = "png"]'),
            array('a[href*=example.com]', '//a[contains(@href, "example.com")]'),
            array('script[!src]', '//script[not(@src)]'),
            array('a[href!="http://foo.com/"]', '//a[not(@href="http://foo.com/")]'),
            array('a[foo~="bar"]', '//a[contains(concat(" ", normalize-space(@foo), " "), " bar ")]'),
            array('input, textarea, select', '//input|//textarea|//select'),
            array('input[name="name"], textarea[name="description"], select[name="type"]', '//input[@name="name"]|//textarea[@name="description"]|//select[@name="type"]'),
            array('li:first-child', '//li[position() = 1]'),
            array('li:last-child', '//li[position() = last()]'),
            array('*:not(a[href*="example.com"])', '//*[not(self::a[contains(@href, "example.com")])]'),
            array('ul:empty', '//ul[count(descendant::*) = 0]'),
            array('ul:not-empty', '//ul[count(descendant::*) > 0]'),
            array('li:nth-child(odd)', '//*[(name()="li") and (position() mod 2 = 1 and position() >= 1)]'),
            array('li:nth-child(even)', '//*[(name()="li") and (position() mod 2 = 0 and position() >= 0)]'),
            array('li:nth-child(3)', '//*[(name()="li") and (position() = 3)]'),
            array('li:nth-child(-3)', '//*[(name()="li") and (position() = -3)]'),
            array('li:nth-child(3n)', '//*[(name()="li") and ((position() + 0) mod 3 = 0 and position() >= 0)]'),
            array('li:nth-child(3n+1)', '//*[(name()="li") and ((position() - 1) mod 3 = 0 and position() >= 1)]'),
            array('li:nth-child(3n-1)', '//*[(name()="li") and ((position() + 1) mod 3 = 0 and position() >= 1)]'),
            array('li:nth-child(n+3)', '//*[(name()="li") and ((position() - 3) mod 1 = 0 and position() >= 3)]'),
            array('li:nth-child(n-3)', '//*[(name()="li") and ((position() + 3) mod 1 = 0 and position() >= 3)]'),
            array('li:nth-of-type(odd)', '//li[position() mod 2 = 1 and position() >= 1]'),
            array('li:nth-of-type(even)', '//li[position() mod 2 = 0 and position() >= 0]'),
            array('li:nth-of-type(3)', '//li[position() = 3]'),
            array('li:nth-of-type(-3)', '//li[position() = -3]'),
            array('li:nth-of-type(3n)', '//li[(position() + 0) mod 3 = 0 and position() >= 0]'),
            array('li:nth-of-type(3n+1)', '//li[(position() - 1) mod 3 = 0 and position() >= 1]'),
            array('li:nth-of-type(3n-1)', '//li[(position() + 1) mod 3 = 0 and position() >= 1]'),
            array('li:nth-of-type(n+3)', '//li[(position() - 3) mod 1 = 0 and position() >= 3]'),
            array('li:nth-of-type(n-3)', '//li[(position() + 3) mod 1 = 0 and position() >= 3]'),
            array('ul:has(li.item)', '//ul[.//li[contains(concat(" ", normalize-space(@class), " "), " item ")]]'),
            array('form[name=register]:has(input[name=foo])', '//form[(@name="register") and (.//input[@name="foo"])]'),
            array('ul li a::text', '//ul//li//a/text()'),
            array('ul li a::text()', '//ul//li//a/text()'),
            array('ul li a::attr(href)', '//ul//li//a/@*[name() = "href"]'),
            array('ul li a::attr(href, title)', '//ul//li//a/@*[name() = "href" or name() = "title"]'),
            array('> ul li a', '/ul//li//a'),
        );

        $compiled = array_merge($compiled, $this->getContainsPseudoClassTests());
        $compiled = array_merge($compiled, $this->getPropertiesTests());

        $compiled = array_merge($compiled, array(
            array('a[title="foo, bar::baz"]', '//a[@title="foo, bar::baz"]'),
        ));

        return $compiled;
    }

    private function getContainsPseudoClassTests()
    {
        $strToLowerFunction = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';

        $containsXpath = array(
            // caseSensitive = true, fullMatch = false
            array('li:contains(foo)', '//li[contains(text(), "foo")]'),
            array('li:contains("foo")', '//li[contains(text(), "foo")]'),
            array('li:contains(\'foo\')', '//li[contains(text(), "foo")]'),

            // caseSensitive = true, fullMatch = false
            array('li:contains(foo, true)', '//li[contains(text(), "foo")]'),
            array('li:contains("foo", true)', '//li[contains(text(), "foo")]'),
            array('li:contains(\'foo\', true)', '//li[contains(text(), "foo")]'),

            // caseSensitive = true, fullMatch = false
            array('li:contains(foo, true, false)', '//li[contains(text(), "foo")]'),
            array('li:contains("foo", true, false)', '//li[contains(text(), "foo")]'),
            array('li:contains(\'foo\', true, false)', '//li[contains(text(), "foo")]'),

            // caseSensitive = true, fullMatch = true
            array('li:contains(foo, true, true)', '//li[text() = "foo"]'),
            array('li:contains("foo", true, true)', '//li[text() = "foo"]'),
            array('li:contains(\'foo\', true, true)', '//li[text() = "foo"]'),

            // caseSensitive = false, fullMatch = false
            array('li:contains(foo, false)', "//li[contains(php:functionString(\"{$strToLowerFunction}\", .), php:functionString(\"{$strToLowerFunction}\", \"foo\"))]"),
            array('li:contains("foo", false)', "//li[contains(php:functionString(\"{$strToLowerFunction}\", .), php:functionString(\"{$strToLowerFunction}\", \"foo\"))]"),
            array('li:contains(\'foo\', false)', "//li[contains(php:functionString(\"{$strToLowerFunction}\", .), php:functionString(\"{$strToLowerFunction}\", \"foo\"))]"),

            // caseSensitive = false, fullMatch = false
            array('li:contains(foo, false, false)', "//li[contains(php:functionString(\"{$strToLowerFunction}\", .), php:functionString(\"{$strToLowerFunction}\", \"foo\"))]"),
            array('li:contains("foo", false, false)', "//li[contains(php:functionString(\"{$strToLowerFunction}\", .), php:functionString(\"{$strToLowerFunction}\", \"foo\"))]"),
            array('li:contains(\'foo\', false, false)', "//li[contains(php:functionString(\"{$strToLowerFunction}\", .), php:functionString(\"{$strToLowerFunction}\", \"foo\"))]"),

            // caseSensitive = false, fullMatch = true
            array('li:contains(foo, false, true)', "//li[php:functionString(\"{$strToLowerFunction}\", .) = php:functionString(\"{$strToLowerFunction}\", \"foo\")]"),
            array('li:contains("foo", false, true)', "//li[php:functionString(\"{$strToLowerFunction}\", .) = php:functionString(\"{$strToLowerFunction}\", \"foo\")]"),
            array('li:contains(\'foo\', false, true)', "//li[php:functionString(\"{$strToLowerFunction}\", .) = php:functionString(\"{$strToLowerFunction}\", \"foo\")]"),
        );

        return $containsXpath;
    }

    private function getPropertiesTests()
    {
        return array(
            array('a::text', '//a/text()'),
            array('a::text()', '//a/text()'),
            array('a::attr', '//a/@*'),
            array('a::attr()', '//a/@*'),
            array('a::attr(href)', '//a/@*[name() = "href"]'),
            array('a::attr(href,title)', '//a/@*[name() = "href" or name() = "title"]'),
            array('a::attr(href, title)', '//a/@*[name() = "href" or name() = "title"]'),
        );
    }

    public function buildXpathTests()
    {
        $xpath = array(
            '//a',
            '//*[@id="foo"]',
            '//a[@id="foo"]',
            '//a[contains(concat(" ", normalize-space(@class), " "), " foo ")]',
            '//a[(contains(concat(" ", normalize-space(@class), " "), " foo ")) and (contains(concat(" ", normalize-space(@class), " "), " bar "))]',
            '//a[@href]',
            '//a[@href="http://example.com/"]',
            '//a[(@href="http://example.com/") and (@title="Example Domain")]',
            '//a[(@target="_blank") and (starts-with(@href, "https"))]',
            '//a[substring(@href, string-length(@href) - string-length(".com") + 1) = ".com"]',
            '//a[contains(@href, "example")]',
            '//a[not(@href="http://foo.com/")]',
            '//script[not(@src)]',
            '//li[position() = 1]',
            '//*[(@id="id") and (contains(concat(" ", normalize-space(@class), " "), " foo ")) and (@name="value") and (position() = 1)]',
        );

        $segments = array(
            array('tag' => 'a'),
            array('id' => 'foo'),
            array('tag' => 'a', 'id' => 'foo'),
            array('tag' => 'a', 'classes' => array('foo')),
            array('tag' => 'a', 'classes' => array('foo', 'bar')),
            array('tag' => 'a', 'attributes' => array('href' => null)),
            array('tag' => 'a', 'attributes' => array('href' => 'http://example.com/')),
            array('tag' => 'a', 'attributes' => array('href' => 'http://example.com/', 'title' => 'Example Domain')),
            array('tag' => 'a', 'attributes' => array('target' => '_blank', 'href^' => 'https')),
            array('tag' => 'a', 'attributes' => array('href$' => '.com')),
            array('tag' => 'a', 'attributes' => array('href*' => 'example')),
            array('tag' => 'a', 'attributes' => array('href!' => 'http://foo.com/')),
            array('tag' => 'script', 'attributes' => array('!src' => null)),
            array('tag' => 'li', 'pseudo' => 'first-child'),
            array('tag' => '*', 'id' => 'id', 'classes' => array('foo'), 'attributes' => array('name' => 'value'), 'pseudo' => 'first-child', 'rel' => '>'),
        );

        $parameters = array();

        foreach ($segments as $index => $segment) {
            $parameters[] = array($segment, $xpath[$index]);
        }

        return $parameters;
    }

    public function getSegmentsTests()
    {
        $segments = array(
            array('selector' => 'a', 'tag' => 'a'),
            array('selector' => '#foo', 'id' => 'foo'),
            array('selector' => 'a#foo', 'tag' => 'a', 'id' => 'foo'),
            array('selector' => 'a.foo', 'tag' => 'a', 'classes' => array('foo')),
            array('selector' => 'a.foo.bar', 'tag' => 'a', 'classes' => array('foo', 'bar')),
            array('selector' => 'a[href]', 'tag' => 'a', 'attributes' => array('href' => null)),
            array('selector' => 'a[href=http://example.com/]', 'tag' => 'a', 'attributes' => array('href' => 'http://example.com/')),
            array('selector' => 'a[href="http://example.com/"]', 'tag' => 'a', 'attributes' => array('href' => 'http://example.com/')),
            array('selector' => 'a[href=\'http://example.com/\']', 'tag' => 'a', 'attributes' => array('href' => 'http://example.com/')),
            array('selector' => 'a[href=http://example.com/][title=Example Domain]', 'tag' => 'a', 'attributes' => array('href' => 'http://example.com/', 'title' => 'Example Domain')),
            array('selector' => 'a[href=http://example.com/][href=http://example.com/404]', 'tag' => 'a', 'attributes' => array('href' => 'http://example.com/404')),
            array('selector' => 'a[href^=https]', 'tag' => 'a', 'attributes' => array('href^' => 'https')),
            array('selector' => 'li:first-child', 'tag' => 'li', 'pseudo' => 'first-child'),
            array('selector' => 'ul >', 'tag' => 'ul', 'rel' => '>'),
            array('selector' => '#id.foo[name=value]:first-child >', 'id' => 'id', 'classes' => array('foo'), 'attributes' => array('name' => 'value'), 'pseudo' => 'first-child', 'rel' => '>'),
            array('selector' => 'li.bar:nth-child(2n)', 'tag' => 'li', 'classes' => array('bar'), 'pseudo' => 'nth-child', 'expr' => '2n'),
        );

        $parameters = array();

        foreach ($segments as $segment) {
            $parameters[] = array($segment['selector'], $segment);
        }

        return $parameters;
    }
}
