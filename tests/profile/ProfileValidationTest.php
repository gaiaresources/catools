<?php

class ProfileValidationTest extends PHPUnit_Framework_TestCase {
    public function testProfileConformsToSchema() {
        $basePath = dirname(dirname(__DIR__));
        $doc = new DOMDocument();
        $doc->load("$basePath/profile/rwahs.xml");
        $this->assertTrue($doc->schemaValidate("$basePath/profile/profile.xsd"));
    }
}
