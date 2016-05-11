<?php

namespace RWAHS\Profile;


class LibraryProfileTest extends AbstractProfileTest
{
    function testLibraryStorageLocationsExist()
    {
        $locations = array('BattyeLibrary', 'FoyerAnnex', 'Library', 'MapAnnex', 'Museum', 'Passage', 'PhotographsRoom');

        foreach($locations as $location){
            $this->assertEquals(1,$this->xpath->query("/profile/lists/list[@code='LibraryStorageLocations']//item[@idno='$location']")->length, "Need to have a storage location entry for `$location`.");
        }
    }
    function testNoExtraLibraryLocationsExist()
    {
        $locations = array('BattyeLibrary', 'FoyerAnnex', 'Library', 'MapAnnex', 'Museum', 'Passage', 'PhotographsRoom');
        /** @var \DOMElement $location */
        foreach($this->xpath->query("/profile/lists/list[@code='LibraryStorageLocations']//item") as $location){
            $id = $location->getAttribute('idno');
            $this->assertContains($id, $locations, "The location `$id` should not exist.");
        }
    }
}
