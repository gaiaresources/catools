<?php

class ProfileElementTest extends PHPUnit_Framework_TestCase
{
    public function testListAttributesHaveLists()
    {
        $basePath = dirname(dirname(__DIR__));
        $doc = new DOMDocument();
        $doc->load("$basePath/profile/rwahs.xml");
        $xpath = new DOMXPath($doc);
        $list_elements = $xpath->query('/profile/elementSets/metadataElement[@datatype="List"]');
        $this->assertEquals(8, $list_elements->length, 'Number of list attributes should match.');
        /** @var DOMElement $list_element */
        foreach($list_elements as $list_element){
            $list_code = $list_element->getAttribute('list');
            $element_code = $list_element->getAttribute('code');
            $this->assertNotNull($list_code, "The list attribute needs to be set for the {$list_element->getNodePath()} element");
            $this->assertEquals(1, $xpath->query("/profile/lists/list[@code='$list_code']")->length, "List attributes require a matching list. The list '$list_code' does not exist and is required for the $element_code element.");
        }
    }
}
