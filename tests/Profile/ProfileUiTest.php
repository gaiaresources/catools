<?php

namespace RWAHS\Profile;

use DOMAttr;
use DOMElement;

class ProfileUiTest extends AbstractProfileTest
{
    public function testProfileContainsUis()
    {
        $xpath = $this->xpath;
        $this->assertEquals(14, $xpath->query('/profile/userInterfaces/userInterface')->length, 'The number of user interfaces should match');
        $this->assertEquals(1, $xpath->query('/profile/userInterfaces/userInterface[@code="museum_object_ui"]')->length, 'The user interface with code "museum_object_ui" should exist.');
        $this->assertEquals(1, $xpath->query('/profile/userInterfaces/userInterface[@code="library_object_ui"]')->length, 'The user interface with code "library_object_ui" should exist.');
        $this->assertEquals(1, $xpath->query('/profile/userInterfaces/userInterface[@code="photographs_object_ui"]')->length, 'The user interface with code "photographs_object_ui" should exist.');
        $this->assertEquals(1, $xpath->query('/profile/userInterfaces/userInterface[@code="standard_entity_ui"]')->length, 'The user interface with code "standard_entity_ui" should exist.');
        $this->assertEquals(1, $xpath->query('/profile/userInterfaces/userInterface[@code="subject_list_ui"]')->length, 'The user interface with code "subject_list_ui" should exist.');
        $this->assertEquals(1, $xpath->query('/profile/userInterfaces/userInterface[@code="conservation_ui"]')->length, 'The user interface with code "conservation_ui" should exist.');
        $this->assertEquals(1, $xpath->query('/profile/userInterfaces/userInterface[@code="object_subject_list_ui"]')->length, 'The user interface with code "object_subject_list_ui" should exist.');
        $this->assertEquals(1, $xpath->query('/profile/userInterfaces/userInterface[@code="occurrence_subject_list_ui"]')->length, 'The user interface with code "occurrence_subject_list_ui" should exist.');
        $this->assertEquals(1, $xpath->query('/profile/userInterfaces/userInterface[@code="places_ui"]')->length, 'The user interface with code "places_ui" should exist.');
        $this->assertEquals(1, $xpath->query('/profile/userInterfaces/userInterface[@code="standard_collection_ui"]')->length, 'The user interface with code "standard_collection_ui" should exist.');
        $this->assertEquals(1, $xpath->query('/profile/userInterfaces/userInterface[@code="standard_storage_locations_ui"]')->length, 'The user interface with code "standard_storage_locations_ui" should exist.');
        $this->assertEquals(1, $xpath->query('/profile/userInterfaces/userInterface[@code="standard_object_lots_ui"]')->length, 'The user interface with code "standard_object_lots_ui" should exist.');
        $this->assertEquals(1, $xpath->query('/profile/userInterfaces/userInterface[@code="standard_representation_ui"]')->length, 'The user interface with code "standard_representation_ui" should exist.');
        $this->assertEquals(1, $xpath->query('/profile/userInterfaces/userInterface[@code="standard_representation_annotation_ui"]')->length, 'The user interface with code "standard_representation_annotation_ui" should exist.');
        $this->assertEquals(1, $xpath->query('/profile/userInterfaces/userInterface[@code="movement_cataloguers_ui"]')->length, 'The user interface with code "movement_cataloguers_ui" should exist.');
    }

    public function testValidRestrictionsForUserInterfacesAndScreens()
    {
        $uis_path = "/profile/userInterfaces/userInterface";
        $uis = $this->xpath->query($uis_path);
        $restrictions_count = 0;

        /** @var DOMElement $ui */
        foreach ($uis as $ui) {
            $ui_table = $ui->getAttribute('type');
            /** @var DOMElement $ui_or_screen_restriction */
            foreach ($this->xpath->query("{$ui->getNodePath()}//restriction") as $ui_or_screen_restriction) {
                $restrictions_count++;
                $this->typeExistsForTable($ui_or_screen_restriction->getAttribute('type'), $ui_table, 'User interface or screen restriction: ' . $ui_or_screen_restriction->getNodePath());
            }
        }
        $this->assertGreaterThan(0, $restrictions_count, 'At least one restriction should exist');
    }

    public function testValidRestrictionsForBundles()
    {
        $restrictions_count = 0;
        /** @var DOMElement $relation_restriction */
        foreach ($this->xpath->query("/profile/userInterfaces/userInterface/screens/screen/bundlePlacements/placement/settings/setting[@name='restrict_to_type']") as $relation_restriction) {
            /** @var DOMElement $ui */
            $ui = $this->xpath->query('ancestor::userInterface[@type]', $relation_restriction)->item(0);
            $restrictions_count++;
            $bundle = $relation_restriction->parentNode->parentNode->getElementsByTagName('bundle')->item(0);
            $this->typeExistsForTable($relation_restriction->textContent, $bundle->textContent, $ui->getAttribute('code') . '(' . $relation_restriction->getNodePath() . ')');
        }
        $this->assertGreaterThan(0, $restrictions_count, 'At least one restriction should exist');
    }

    public function testAttributeExistsForAttributeBundles()
    {
        $attribute_count = 0;
        $exceptions = array('Description', 'LastEditDate', 'LastEditBy');
        /** @var DOMElement $attribute_ui_placement */
        foreach ($this->xpath->query("/profile/userInterfaces/userInterface/screens/screen/bundlePlacements/placement/bundle[starts-with(.,'ca_attribute')]") as $attribute_ui_placement) {
            /** @var DOMElement $ui */
            $ui = $this->xpath->query('ancestor::userInterface[@type]', $attribute_ui_placement)->item(0);
            $ui_table = $ui->getAttribute('type');

            $attribute_count++;

            $attribute_code = preg_replace('/^ca_attribute_/', '', $attribute_ui_placement->textContent);
            $this->assertEquals(1, $this->xpath->query("/profile/elementSets/metadataElement[@code='$attribute_code']")->length, "The attribute `$attribute_code` should exist in the installation profile. Placement is at: " . $attribute_ui_placement->getNodePath());
            $this->assertGreaterThanOrEqual(1, $this->xpath->query("/profile/elementSets/metadataElement[@code='$attribute_code']/typeRestrictions/restriction/table[text() = '$ui_table']")->length, "The attribute `$attribute_code` is used in a user interface for `$ui_table` ({$ui->getAttribute('code')}).
             The attribute does not have a type restriction for that table.
             Placement is at: " . $attribute_ui_placement->getNodePath());
            $ui_types = $this->xpath->query("{$ui->getNodePath()}/typeRestrictions/restriction/@type");
            $type_count = 0;
            /** @var DOMAttr $type_attribute */
            foreach($ui_types as $type_attribute){
                $type_attribute->textContent;
                $type_count ++;
                if(!in_array($attribute_code, $exceptions)){
                    $this->assertEquals(1, $this->xpath->query("/profile/elementSets/metadataElement[@code='$attribute_code']/typeRestrictions/restriction/type[text() = '$type_attribute->textContent']")->length,
                        "The attribute `$attribute_code` is used in a user interface for `$ui_table` ({$ui->getAttribute('code')}).
             The attribute does not have a type restriction for that type `$type_attribute->textContent`
             Placement is at: " . $attribute_ui_placement->getNodePath());
                }
            }
        }
        $this->assertGreaterThan(1, $attribute_count, 'At least one restriction should exist');
    }

    public function testNonAttributeBundlesExist()
    {
        $attribute_count = 0;
        $known_bundles = array(
            // intrinsics
            'idno', 'access', 'status', 'acquisition_type_id', 'idno_stub', 'lot_status_id', 'extent',
            // interstitial
            'effective_date', 'source_info',
            // labels
            'preferred_labels', 'nonpreferred_labels',
            // related classes
            'ca_object_representations', 'ca_occurrences', 'ca_entities', 'ca_collections','ca_loans', 'ca_objects',
            'ca_object_lots', 'ca_places', 'ca_storage_locations', 'ca_list_items', 'ca_representation_annotations', 'ca_representation_annotation_properties',
            // special
            'ca_objects_location', 'ca_objects_deaccession', 'hierarchy_navigation', 'hierarchy_location', 'ca_storage_locations_contents', 'media'
            );
        /** @var DOMElement $ui_placement */
        foreach ($this->xpath->query("/profile/userInterfaces/userInterface/screens/screen/bundlePlacements/placement/bundle[not(starts-with(.,'ca_attribute'))]") as $ui_placement) {
            /** @var DOMElement $ui */
            $ui = $this->xpath->query('ancestor::userInterface[@type]', $ui_placement)->item(0);
            $ui_table = $ui->getAttribute('type');
            $bundle = $ui_placement->textContent;
            $this->assertContains($bundle, $known_bundles,
                "The bundle `$bundle` is used in a user interface for `$ui_table` ({$ui->getAttribute('code')}).
             The attribute does not have a type restriction for that table.
             Placement is at: " . $ui_placement->getNodePath());
            $attribute_count++;
        }
        $this->assertGreaterThan(1, $attribute_count, 'At least one restriction should exist');
    }
}
