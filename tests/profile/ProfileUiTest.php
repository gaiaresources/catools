<?php

class ProfileUiTest extends PHPUnit_Framework_TestCase {
    public function testProfileContainsUis() {
        $basePath = dirname(dirname(__DIR__));
        $doc = new DOMDocument();
        $doc->load("$basePath/profile/rwahs.xml");
        $xpath = new DOMXPath($doc);
        $this->assertEquals(14, $xpath->query('//userInterfaces/userInterface')->length, 'The number of user interfaces should match');
    }
}
