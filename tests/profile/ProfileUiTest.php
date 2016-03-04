<?php

require_once('AbstractProfileTest.php');

class ProfileUiTest extends AbstractProfileTest
{
    public function testProfileContainsUis()
    {
        $xpath = $this->xpath;
        $this->assertEquals(13, $xpath->query('//userInterfaces/userInterface')->length, 'The number of user interfaces should match');
        $this->assertEquals(1, $xpath->query('//userInterfaces/userInterface[@code="museum_object_ui"]')->length, 'The user interface with code "museum_object_ui" should exist.');
        $this->assertEquals(1, $xpath->query('//userInterfaces/userInterface[@code="standard_entity_ui"]')->length, 'The user interface with code "standard_entity_ui" should exist.');
        $this->assertEquals(1, $xpath->query('//userInterfaces/userInterface[@code="subject_list_ui"]')->length, 'The user interface with code "subject_list_ui" should exist.');
        $this->assertEquals(1, $xpath->query('//userInterfaces/userInterface[@code="conservation_ui"]')->length, 'The user interface with code "conservation_ui" should exist.');
        $this->assertEquals(1, $xpath->query('//userInterfaces/userInterface[@code="object_subject_list_ui"]')->length, 'The user interface with code "object_subject_list_ui" should exist.');
        $this->assertEquals(1, $xpath->query('//userInterfaces/userInterface[@code="occurrence_subject_list_ui"]')->length, 'The user interface with code "occurrence_subject_list_ui" should exist.');
        $this->assertEquals(1, $xpath->query('//userInterfaces/userInterface[@code="places_ui"]')->length, 'The user interface with code "places_ui" should exist.');
        $this->assertEquals(1, $xpath->query('//userInterfaces/userInterface[@code="standard_collection_ui"]')->length, 'The user interface with code "standard_collection_ui" should exist.');
        $this->assertEquals(1, $xpath->query('//userInterfaces/userInterface[@code="standard_storage_locations_ui"]')->length, 'The user interface with code "standard_storage_locations_ui" should exist.');
        $this->assertEquals(1, $xpath->query('//userInterfaces/userInterface[@code="standard_object_lots_ui"]')->length, 'The user interface with code "standard_object_lots_ui" should exist.');
        $this->assertEquals(1, $xpath->query('//userInterfaces/userInterface[@code="standard_representation_ui"]')->length, 'The user interface with code "standard_representation_ui" should exist.');
        $this->assertEquals(1, $xpath->query('//userInterfaces/userInterface[@code="standard_representation_annotation_ui"]')->length, 'The user interface with code "standard_representation_annotation_ui" should exist.');
        $this->assertEquals(1, $xpath->query('//userInterfaces/userInterface[@code="movement_cataloguers_ui"]')->length, 'The user interface with code "movement_cataloguers_ui" should exist.');
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
        $uis_path = "/profile/userInterfaces/userInterface";
        $uis = $this->xpath->query($uis_path);
        $restrictions_count = 0;

        /** @var DOMElement $ui */
        foreach ($uis as $ui) {
            /** @var DOMElement $relation_restriction */

            foreach ($this->xpath->query("{$ui->getNodePath()}/screens/screen/bundlePlacements/placement/settings/setting[@name='restrict_to_type']") as $relation_restriction) {
                $restrictions_count++;
                $bundle = $relation_restriction->parentNode->parentNode->getElementsByTagName('bundle')->item(0);
                $this->typeExistsForTable($relation_restriction->textContent, $bundle->textContent, $ui->getAttribute('code') . '(' . $relation_restriction->getNodePath() . ')');
            }
        }
        $this->assertGreaterThan(0, $restrictions_count, 'At least one restriction should exist');
    }

}
