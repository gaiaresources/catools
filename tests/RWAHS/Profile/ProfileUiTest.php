<?php

namespace RWAHS\Profile;

use DOMAttr;
use DOMElement;

class ProfileUiTest extends AbstractProfileTest
{
    public function testProfileContainsUis()
    {
        $xpath = $this->xpath;
        $this->assertGreaterThan(16, $xpath->query('/profile/userInterfaces/userInterface')->length, 'The number of user interfaces should match');
        $this->assertEquals(1, $xpath->query('/profile/userInterfaces/userInterface[@code="museum_object_ui"]')->length, 'The user interface with code "museum_object_ui" should exist.');
        $this->assertEquals(1, $xpath->query('/profile/userInterfaces/userInterface[@code="library_object_ui"]')->length, 'The user interface with code "library_object_ui" should exist.');
        $this->assertEquals(1, $xpath->query('/profile/userInterfaces/userInterface[@code="photograph_object_ui"]')->length, 'The user interface with code "photograph_object_ui" should exist.');
        $this->assertEquals(1, $xpath->query('/profile/userInterfaces/userInterface[@code="memorial_object_ui"]')->length, 'The user interface with code "memorial_object_ui" should exist.');
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
        foreach ($this->xpath->query("/profile/userInterfaces/userInterface/screens/screen/bundlePlacements/placement/settings/setting[@name='restrict_to_types']") as $relation_restriction) {
            /** @var DOMElement $ui */
            $ui = $this->xpath->query('ancestor::userInterface[@type]', $relation_restriction)->item(0);
            $restrictions_count++;
            $bundle = $relation_restriction->parentNode->parentNode->getElementsByTagName('bundle')->item(0);
            $this->typeExistsForTable($relation_restriction->textContent, $bundle->textContent, $ui->getAttribute('code') . '(' . $relation_restriction->getNodePath() . ')');
        }
        $this->assertGreaterThan(0, $restrictions_count, 'At least one restriction should exist');
        $this->assertEquals(0, $this->xpath->query("/profile/userInterfaces/userInterface/screens/screen/bundlePlacements/placement/settings/setting[@name='restrict_to_type']")->length, 'Always use restrict_to_types rather than restrict_to_type for bundle relationship restrictions.');
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
        $known_bundles = [
            'access',
            'acquisition_type_id',
            'ca_bundle_display_placements',
            'ca_bundle_display_type_restrictions',
            'ca_collections',
            'ca_editor_ui_bundle_placements',
            'ca_editor_ui_screen_type_restrictions',
            'ca_editor_ui_screens',
            'ca_editor_ui_type_restrictions',
            'ca_entities',
            'ca_list_items',
            'ca_movements',
            'ca_object_lots',
            'ca_object_lots_related_list',
            'ca_object_representations',
            'ca_objects',
            'ca_objects_table',
            'ca_occurrences',
            'ca_occurrences_related_list',
            'ca_places',
            'ca_representation_annotation_properties',
            'ca_representation_annotations',
            'ca_search_form_placements',
            'ca_set_items',
            'ca_site_pages_content',
            'ca_storage_locations',
            'ca_tour_stops',
            'ca_user_groups',
            'ca_user_roles',
            'ca_users',
            'color',
            'default_sort',
            'description',
            'display_code',
            'editor_code',
            'editor_type',
            'effective_date',
            'entity_id',
            'extent',
            'extent_units',
            'form_code',
            'hierarchy_location',
            'hierarchy_navigation',
            'icon',
            'idno',
            'idno_stub',
            'is_default',
            'is_enabled',
            'is_hierarchical',
            'is_system',
            'is_system_list',
            'is_system_ui',
            'item_status_id',
            'item_value',
            'keywords',
            'list_code',
            'lot_id',
            'lot_status_id',
            'media',
            'nonpreferred_labels',
            'object_id',
            'occurrence_id',
            'path',
            'preferred_labels',
            'rank',
            'set_code',
            'settings',
            'source_id',
            'source_info',
            'status',
            'sub_type_left_id',
            'sub_type_right_id',
            'table_num',
            'template_id',
            'title',
            'tour_code',
            'type_code',
            'use_as_vocabulary',
            'validation_format',
            'ca_objects_location',
            'ca_entities_related_list',
            'ca_objects_related_list',
            'ca_collections_related_list',
            'ca_storage_locations_related_list',
            'ca_storage_locations_contents',
            'include_subtypes_left',
            'include_subtypes_right',
            'ca_search_form_type_restrictions',
            'ca_metadata_alert_triggers',
            'ca_metadata_alert_rule_type_restrictions',
            'code',
            'bundle_name',
            'ca_metadata_dictionary_rules',
            'ca_object_representations_access_status',
            'ca_object_representations_related_list',
            'ca_loans',
            'ca_loans_related_list',
            'ca_places_related_list',
            'loan_id',
            'history_tracking_chronology',
            'history_tracking_current_contents',
            'ca_objects_deaccession',
        ];
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
