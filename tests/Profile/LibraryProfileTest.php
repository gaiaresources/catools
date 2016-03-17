<?php

namespace RWAHS\Profile;


class LibraryProfileTest extends AbstractProfileTest
{
    function testLibraryStorageLocationsExist()
    {
        $locations = array('BattyeLibrary','BoxCollection','Display','FoyerAnnex','LargeBook','Library','MapAnnex','MuseumStore','Passage','PhotographsRoom','Reference','TranbyRoom');

        foreach($locations as $location){
            $this->assertEquals(1,$this->xpath->query("/profile/lists/list[@code='LibraryStorageLocations']//item[@idno='$location']")->length, "Need to have a storage location entry for `$location`.");
        }
    }
}
