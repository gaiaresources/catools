<?php
namespace RWAHS\Profile;

class ProfileValidationTest extends AbstractProfileTest
{
    public function testProfileConformsToSchema()
    {
        $this->xpath->registerNamespace("xsi", "http://www.w3.org/2001/XMLSchema-instance");
        $url = $this->xpath->query('@xsi:noNamespaceSchemaLocation')->item(0)->textContent;
        $this->assertTrue($this->profile->schemaValidate($url));    }
}
