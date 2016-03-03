<?php

class ProfileUiTest extends PHPUnit_Framework_TestCase
{
    public function testProfileContainsUis()
    {
        $basePath = dirname(dirname(__DIR__));
        $doc = new DOMDocument();
        $doc->load("$basePath/profile/rwahs.xml");
        $xpath = new DOMXPath($doc);
        $this->assertEquals(13, $xpath->query('//userInterfaces/userInterface')->length, 'The number of user interfaces should match');
        $this->assertEquals(1, $xpath->query('//userInterface[@code="museum_object_ui"]')->length, 'The user interface with code "museum_object_ui" should exist.');
        $this->assertEquals(1, $xpath->query('//userInterface[@code="standard_entity_ui"]')->length, 'The user interface with code "standard_entity_ui" should exist.');
        $this->assertEquals(1, $xpath->query('//userInterface[@code="subject_list_ui"]')->length, 'The user interface with code "subject_list_ui" should exist.');
        $this->assertEquals(1, $xpath->query('//userInterface[@code="conservation_ui"]')->length, 'The user interface with code "conservation_ui" should exist.');
        $this->assertEquals(1, $xpath->query('//userInterface[@code="object_subject_list_ui"]')->length, 'The user interface with code "object_subject_list_ui" should exist.');
        $this->assertEquals(1, $xpath->query('//userInterface[@code="occurrence_subject_list_ui"]')->length, 'The user interface with code "occurrence_subject_list_ui" should exist.');
        $this->assertEquals(1, $xpath->query('//userInterface[@code="places_ui"]')->length, 'The user interface with code "places_ui" should exist.');
        $this->assertEquals(1, $xpath->query('//userInterface[@code="standard_collection_ui"]')->length, 'The user interface with code "standard_collection_ui" should exist.');
        $this->assertEquals(1, $xpath->query('//userInterface[@code="standard_storage_locations_ui"]')->length, 'The user interface with code "standard_storage_locations_ui" should exist.');
        $this->assertEquals(1, $xpath->query('//userInterface[@code="standard_object_lots_ui"]')->length, 'The user interface with code "standard_object_lots_ui" should exist.');
        $this->assertEquals(1, $xpath->query('//userInterface[@code="standard_representation_ui"]')->length, 'The user interface with code "standard_representation_ui" should exist.');
        $this->assertEquals(1, $xpath->query('//userInterface[@code="standard_representation_annotation_ui"]')->length, 'The user interface with code "standard_representation_annotation_ui" should exist.');
        $this->assertEquals(1, $xpath->query('//userInterface[@code="movement_cataloguers_ui"]')->length, 'The user interface with code "movement_cataloguers_ui" should exist.');
    }
}
