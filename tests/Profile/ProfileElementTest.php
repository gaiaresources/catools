<?php
namespace RWAHS\Profile;

use DOMElement;

class ProfileElementTest extends AbstractProfileTest
{
    public function testListAttributesHaveLists()
    {
        $list_elements = $this->xpath->query('//metadataElement[@datatype="List"]');
        $this->assertEquals(22, $list_elements->length, 'Number of list attributes should match.');
        /** @var DOMElement $list_element */
        foreach ($list_elements as $list_element) {
            $list_code = $list_element->getAttribute('list');
            $element_code = $list_element->getAttribute('code');
            $this->assertNotNull($list_code, "The list attribute needs to be set for the {$list_element->getNodePath()} element");
            $this->assertEquals(1, $this->xpath->query("/profile/lists/list[@code='$list_code']")->length, "List attributes require a matching list. The list '$list_code' does not exist and is required for the $element_code element." .
                "\nHere's an xml element:\n" .
                <<<"LIST_XML"
<list code="$list_code" hierarchical="0" system="1" vocabulary="0">
  <labels>
    <label locale="en_AU">
      <name>$list_code</name>
    </label>
  </labels>
</list>
LIST_XML
            );
        }
    }

    public function testValidTypeRestrictions()
    {
        $restrictions = $this->xpath->query('/profile/elementSets/metadataElement/typeRestrictions/restriction');
        /** @var DOMElement $restriction */
        foreach ($restrictions as $restriction) {
            $table = $restriction->getElementsByTagName('table')->item(0)->textContent;
            $metadata_element = $this->xpath->query('ancestor::metadataElement', $restriction)->item(0);
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

    public function testObjectTypeRestrictionsHaveTypes()
    {
        $restrictions = $this->xpath->query('/profile/elementSets/metadataElement[not(@code = "Description" or @code = "LastEditBy" or @code = "LastEditDate" or @code = "LegacyID")]/typeRestrictions/restriction[table = "ca_objects"]');
        /** @var DOMElement $restriction */
        foreach ($restrictions as $restriction) {
            /** @var DOMElement $metadata_element */
            $metadata_element = $this->xpath->query('ancestor::metadataElement', $restriction)->item(0);
            $table = $restriction->getElementsByTagName('table')->item(0)->textContent;

            $type_element = $restriction->getElementsByTagName('type');
            $this->assertEquals(1, $type_element->length, "The metadata element {$metadata_element->getAttribute('code')} in the table $table at {$restriction->getNodePath()} requires a type restriction and does not have one");
            if ($type_element->length) {

            }
        }
    }

    public function testCheckboxesUseYesNoLists()
    {
        $checkbox_settings = $this->xpath->query('//metadataElement[@datatype="List"]/settings/setting[@name="render" and text() = "yes_no_checkboxes"]');
        $this->assertGreaterThan(0, $checkbox_settings->length, 'You need to have at least one checkbox element in your profile');
        /** @var DOMElement $checkbox_setting */
        foreach($checkbox_settings as $checkbox_setting){
            $metadata_element = $checkbox_setting->parentNode->parentNode;
            $this->assertContains($metadata_element->getAttribute('list'), array('YesNoDefaultNo','YesNoDefaultYes'), "All checkbox fields should be bound to one of the YesNo lists. Element: `{$metadata_element->getAttribute('code')}`` List: `{$metadata_element->getAttribute('list')}``" );
        }
    }

    public function testListElementsWithNullOptionTextDoNotRequireValue()
    {
        $optional_list_settings = $this->xpath->query('//metadataElement[@datatype="List"]/settings/setting[@name="nullOptionText"]');
        $this->assertGreaterThan(0, $optional_list_settings->length, 'You need to have at least one element with nullOptionText set.');
        /** @var DOMElement $optional_list_setting */
        foreach($optional_list_settings as $optional_list_setting){
            $metadata_element = $optional_list_setting->parentNode->parentNode;
            $this->assertEquals(1, $this->xpath->query($metadata_element->getNodePath() . '/settings/setting[@name="requireValue" and text() = 0]')->length,
                'The element `' . $metadata_element->getAttribute('code') . '` has `nullOptionText` set and therefore should have a setting of
                <setting name="requireValue">0</setting>');
        }
    }
    public function testListElementsWhichDoNotRequireValueHaveNullOptionTextSet()
    {
        $optional_list_settings = $this->xpath->query('//metadataElement[@datatype="List"]/settings/setting[@name="requireValue" and text() = 0]');
        $this->assertGreaterThan(0, $optional_list_settings->length, 'You need to have at least one optional element in your profile');
        /** @var DOMElement $optional_list_setting */
        foreach($optional_list_settings as $optional_list_setting){
            $metadata_element = $optional_list_setting->parentNode->parentNode;
            $this->assertEquals(1, $this->xpath->query($metadata_element->getNodePath() . '/settings/setting[@name="nullOptionText"]')->length,
                'The element `' . $metadata_element->getAttribute('code') . '` has `requireValue ` set to `0` and therefore should have a setting of
                <setting name="nullOptionText">Not Set</setting>');
        }
    }

    public function testListElementsHaveRequireValueSet()
    {
        $list_elements = $this->xpath->query('//metadataElement[@datatype="List" and @list != "YesNoDefaultNo" and @list != "YesNoDefaultYes"]');
        /** @var DOMElement $list_element */
        foreach($list_elements as $list_element){
            $this->assertEquals(1, $this->xpath->query($list_element->getNodePath() . '/settings/setting[@name="render"]')->length,
                'The element `' . $list_element->getAttribute('code') . '` has no render set.');
            if ($this->xpath->query($list_element->getNodePath() . '/settings/setting[@name="render" and text() !="checklist"]')->length){
                $this->assertEquals(1, $this->xpath->query($list_element->getNodePath() . '/settings/setting[@name="requireValue"]')->length,
                    'The element `' . $list_element->getAttribute('code') . '` has no requireValue set.');
            }

        }
    }

    public function testYesNoListsUseCheckboxes()
    {
        $yes_no_list_elements = $this->xpath->query('//metadataElement[@datatype="List" and (@list = "YesNoDefaultNo" or @list = "YesNoDefaultYes")]');
        $this->assertGreaterThan(0, $yes_no_list_elements->length, 'You need to have at least one element associated with the YesNo lists');
        /** @var DOMElement $metadata_element */
        foreach($yes_no_list_elements as $metadata_element){
            $this->assertEquals('yes_no_checkboxes', $this->xpath->query($metadata_element->getNodePath() . '/settings/setting[@name="render"]')->item(0)->textContent, "All elements associated with YesNo lists should be rendered as `yes_no_checkboxes`. Element: `{$metadata_element->getAttribute('code')}`` List: `{$metadata_element->getAttribute('list')}``");
        }
    }

    public function testMuseumFieldsExistOnPhotographsAndPublicMemorials()
    {
        $museum_restrictions = $this->xpath->query('//metadataElement//restriction[table="ca_objects" and type="museum"]');
        $this->assertGreaterThan(0, $museum_restrictions->length, 'xpath not returning any museum restrictions');
        /** @var DOMElement $restriction */
        foreach($museum_restrictions as $restriction){
            /** @var DOMElement $metadata_element */
            $metadata_element = $this->xpath->query('ancestor::metadataElement', $restriction)->item(0);
            $code = $metadata_element->getAttribute('code');
            if(!in_array($code, array('Description', 'LastEditDate', 'LastEditBy'))){
                $this->assertEquals(1, $this->xpath->query($metadata_element->getNodePath() . '/typeRestrictions/restriction[table="ca_objects" and type="photograph"]')->length, "Expect to have a restriction for objects of type `photograph` for the element `$code`");
                $this->assertEquals(1, $this->xpath->query($metadata_element->getNodePath() . '/typeRestrictions/restriction[table="ca_objects" and type="publicMemorial"]')->length, "Expect to have a restriction for objects of type `publicMemorial` for the element `$code`");
            }
        }
    }

    public function testContainersHaveElements()
    {
        $this->assertEquals(0, $this->xpath->query('/profile/elementSets//metadataElement[@datatype="Container" and not(elements/metadataElement)]')->length, 'Container elements require child elements');
    }

}
