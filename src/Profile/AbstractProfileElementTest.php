<?php

namespace CaTools\Profile;

use DOMElement;
use PHPUnit\Framework\ExpectationFailedException;

class AbstractProfileElementTest extends AbstractProfileTest
{
    public function testListAttributesHaveLists()
    {
        $list_elements = $this->xpath->query('//metadataElement[@datatype="List"]');
        /** @var DOMElement $list_element */
        foreach ($list_elements as $list_element) {
            $list_code = $list_element->getAttribute('list');
            $element_code = $list_element->getAttribute('code');
            $this->assertNotNull($list_code, "The list attribute needs to be set for the {$list_element->getNodePath()} element");

            $this->assertListExists($list_code, $element_code);
        }
    }

    public function testValidTypeRestrictions()
    {
        $restrictions = $this->xpath->query('/profile/elementSets/metadataElement/typeRestrictions/restriction');
        /** @var DOMElement $restriction */
        $failures = [];
        foreach ($restrictions as $restriction) {
            $table = $restriction->getElementsByTagName('table')->item(0)->textContent;
            /** @var DOMElement $metadata_element */
            $metadata_element = $this->xpath->query('ancestor::metadataElement', $restriction)->item(0);
            try {
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
                if ($type_element->length && $type_element->item(0)->textContent) {
                    $type = $type_element->item(0)->textContent;
                    if ($interstitial && $type) {
                        // This is an interstitial attribute
                        $selector .= "/types/type[@code='$type']";
                        $this->assertEquals(1, $this->xpath->query($selector)->length, "The `$selector` element should exist in the profile.");
                    } else {
                        $this->assertTypeExistsForTable($type, $table, "Element: " . $metadata_element->getAttribute('code'));
                    }
                }
            } catch (ExpectationFailedException $e) {
                $failures[] = $e->toString();
            }
        }
        $this->assertCount(0, $failures, join("\n", $failures));
    }


    public function testContainersHaveElements()
    {
        $this->assertCount(0, $this->xpath->query('/profile/elementSets//metadataElement[@datatype="Container" and not(elements/metadataElement)]'), 'Container elements require child elements');
    }

    public function testTypeRestrictionsAreNotNumeric()
    {
        $settings = $this->xpath->query('//setting[@name="restrictToTypes"]');
        /** @var DOMElement $setting */
        foreach ($settings as $setting) {
            $value = $setting->textContent;
            $this->assertIsNotNumeric($value, "Setting 'restrictToTypes' must not be numeric." .
                "\nElement is at " . $setting->getNodePath() .
                "\n Element: " . $this->xpath->document->saveXML($setting->parentNode->parentNode));
        }
    }

    public function testStatusElementDoesNotExist()
    {
        $reserved = ['idno', 'status', 'name',];
        foreach ($reserved as $code) {
            $this->assertCount(0, $this->xpath->query(
                "/profile/elementSets/metadataElement[@code=\"{$code}\"]"
            ), "A root level metadata element cannot have the code `$code` as that collides with the intrinsic `$code` field.");
        }
    }
}
