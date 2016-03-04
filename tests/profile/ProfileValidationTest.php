<?php

class ProfileValidationTest extends AbstractProfileTest
{
    public function testProfileConformsToSchema()
    {
        $this->assertTrue($this->profile->schemaValidate(dirname(dirname(__DIR__)) . "/profile/profile.xsd"));
    }
}
