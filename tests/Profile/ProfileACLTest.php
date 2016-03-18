<?php

namespace RWAHS\Profile;

class ProfileACLTest extends AbstractProfileTest
{
    public function testProfileContainsGroups()
    {
        $xpath = $this->xpath;
        $this->assertEquals(2, $xpath->query('/profile/groups/group')->length, 'The number of groups should match');
        $this->assertEquals(1, $xpath->query('/profile/groups/group[@code="museum"]')->length, 'The group with code "museum" should exist.');
        $this->assertEquals(1, $xpath->query('/profile/groups/group[@code="library"]')->length, 'The group with code "library" should exist.');
        $this->assertEquals(1, $xpath->query('/profile/groups/group[@code="photographs"]')->length, 'The group with code "photographs" should exist.');
    }

    public function testProfileContainsRoles()
    {
        $xpath = $this->xpath;
        $this->assertEquals(2, $xpath->query('/profile/roles/role')->length, 'The number of roles should match');
        $this->assertEquals(1, $xpath->query('/profile/roles/role[@code="museum"]')->length, 'The role with code "museum" should exist.');
        $this->assertEquals(1, $xpath->query('/profile/roles/role[@code="library"]')->length, 'The role with code "library" should exist.');
        $this->assertEquals(1, $xpath->query('/profile/roles/role[@code="photographs"]')->length, 'The role with code "photographs" should exist.');
    }

    public function testUIAccess()
    {
        $xpath = $this->xpath;
        $this->assertEquals(1, $xpath->query('/profile/userInterfaces/userInterface[@code="museum_object_ui"]/groupAccess/permission[@group="museum" and @access="edit"]')->length, 'The museum role should have edit access to the museum editor');
        $this->assertEquals(0, $xpath->query('/profile/userInterfaces/userInterface[@code="museum_object_ui"]/groupAccess/permission[@group="library" and @access="edit"]')->length, 'The library role should not have edit access to the museum editor');
        $this->assertEquals(0, $xpath->query('/profile/userInterfaces/userInterface[@code="museum_object_ui"]/groupAccess/permission[@group="photographs" and @access="edit"]')->length, 'The photographs role should not have edit access to the museum editor');

        $this->assertEquals(1, $xpath->query('/profile/userInterfaces/userInterface[@code="library_object_ui"]/groupAccess/permission[@group="library" and @access="edit"]')->length, 'The library role should have edit access to the library editor');
        $this->assertEquals(0, $xpath->query('/profile/userInterfaces/userInterface[@code="library_object_ui"]/groupAccess/permission[@group="museum" and @access="edit"]')->length, 'The museum role should not have edit access to the library editor');
        $this->assertEquals(0, $xpath->query('/profile/userInterfaces/userInterface[@code="library_object_ui"]/groupAccess/permission[@group="photographs" and @access="edit"]')->length, 'The photographs role should not have edit access to the library editor');

        $this->assertEquals(1, $xpath->query('/profile/userInterfaces/userInterface[@code="photographs_object_ui"]/groupAccess/permission[@group="photographs" and @access="edit"]')->length, 'The photographs role should have edit access to the photographs editor');
        $this->assertEquals(0, $xpath->query('/profile/userInterfaces/userInterface[@code="photographs_object_ui"]/groupAccess/permission[@group="museum" and @access="edit"]')->length, 'The museum role should not have edit access to the photographs editor');
        $this->assertEquals(0, $xpath->query('/profile/userInterfaces/userInterface[@code="photographs_object_ui"]/groupAccess/permission[@group="library" and @access="edit"]')->length, 'The library role should not have edit access to the photographs editor');
    }
}
