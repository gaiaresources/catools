<?php
require_once('AbstractProfileTest.php');

class ProfileElementTest extends AbstractProfileTest
{
    public function testListAttributesHaveLists()
    {
        $list_elements = $this->xpath->query('/profile/elementSets/metadataElement[@datatype="List"]');
        $this->assertEquals(8, $list_elements->length, 'Number of list attributes should match.');
        /** @var DOMElement $list_element */
        foreach ($list_elements as $list_element) {
            $list_code = $list_element->getAttribute('list');
            $element_code = $list_element->getAttribute('code');
            $this->assertNotNull($list_code, "The list attribute needs to be set for the {$list_element->getNodePath()} element");
            $this->assertEquals(1, $this->xpath->query("/profile/lists/list[@code='$list_code']")->length, "List attributes require a matching list. The list '$list_code' does not exist and is required for the $element_code element.");
        }
    }

    public function testValidTypeRestrictions()
    {
        $restrictions = $this->xpath->query('/profile/elementSets/metadataElement/typeRestrictions/restriction');
        /** @var DOMElement $restriction */
        foreach ($restrictions as $restriction) {
            $table = $restriction->getElementsByTagName('table')->item(0)->textContent;
            $metadata_element = $restriction->parentNode->parentNode;
            $this->assertEquals('metadataElement', $metadata_element->nodeName);
            $interstitial = false;
            $selector = '';
            if (preg_match('/_x_/', $table)) {
                // This is an interstitial attribute
                $selector = "/profile/relationshipTypes/relationshipTable[@name='$table']";
                $this->assertEquals(1, $this->xpath->query($selector)->length, "The `$selector` element should exist in the profile.");
                $interstitial = true;
            }

            $type_element = $restriction->getElementsByTagName('type');
            if ($type_element->length) {
                $type = $type_element->item(0)->textContent;
                if ($interstitial) {
                    // This is an interstitial attribute
                    $selector .= "/types/type[@code='$type']";
                    $this->assertEquals(1, $this->xpath->query($selector)->length, "The `$selector` element should exist in the profile.");
                } else {
                    $this->typeExistsForTable($type, $table);
                }
            }
        }
    }


}
