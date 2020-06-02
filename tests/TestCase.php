<?php

namespace DiDom\Tests;

use DOMDocument;
use Exception;
use PHPUnit_Framework_TestCase;

class TestCase extends PHPUnit_Framework_TestCase
{
    protected function loadFixture($filename)
    {
        $path = __DIR__.'/fixtures/'.$filename;

        if (file_exists($path)) {
            return file_get_contents($path);
        }

        throw new Exception(sprintf('Fixture "%s" does not exist', $filename));
    }

    protected function createDomElement($tagName, $value = null, $attributes = array())
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $node = $document->createElement($tagName, $value);

        foreach ($attributes as $attrName => $attrValue) {
            $node->setAttribute($attrName, $attrValue);
        }

        return $node;
    }
}
