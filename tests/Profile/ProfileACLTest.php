<?php

namespace RWAHS\Profile;

class ProfileACLTest extends AbstractProfileTest
{
    public function testProfileContainsGroups()
    {
        $xpath = $this->xpath;
        $this->assertEquals(4, $xpath->query('/profile/groups/group')->length, 'The number of groups should match');
        $this->assertEquals(1, $xpath->query('/profile/groups/group[@code="museum"]')->length, 'The group with code "museum" should exist.');
        $this->assertEquals(1, $xpath->query('/profile/groups/group[@code="library"]')->length, 'The group with code "library" should exist.');
        $this->assertEquals(1, $xpath->query('/profile/groups/group[@code="photograph"]')->length, 'The group with code "photograph" should exist.');
        $this->assertEquals(1, $xpath->query('/profile/groups/group[@code="memorial"]')->length, 'The group with code "memorial" should exist.');
    }

    public function testProfileContainsRoles()
    {
        $xpath = $this->xpath;
        $this->assertEquals(4, $xpath->query('/profile/roles/role')->length, 'The number of roles should match');
        $this->assertEquals(1, $xpath->query('/profile/roles/role[@code="museum"]')->length, 'The role with code "museum" should exist.');
        $this->assertEquals(1, $xpath->query('/profile/roles/role[@code="library"]')->length, 'The role with code "library" should exist.');
        $this->assertEquals(1, $xpath->query('/profile/roles/role[@code="photograph"]')->length, 'The role with code "photograph" should exist.');
        $this->assertEquals(1, $xpath->query('/profile/roles/role[@code="memorial"]')->length, 'The role with code "memorial" should exist.');
    }

    public function testUIAccess()
    {
        $xpath = $this->xpath;
        $this->assertEquals(1, $xpath->query('/profile/userInterfaces/userInterface[@code="museum_object_ui"]/groupAccess/permission[@group="museum" and @access="edit"]')->length, 'The museum role should have edit access to the museum editor');
        $this->assertEquals(0, $xpath->query('/profile/userInterfaces/userInterface[@code="museum_object_ui"]/groupAccess/permission[@group="library" and @access="edit"]')->length, 'The library role should not have edit access to the museum editor');
        $this->assertEquals(0, $xpath->query('/profile/userInterfaces/userInterface[@code="museum_object_ui"]/groupAccess/permission[@group="photograph" and @access="edit"]')->length, 'The photograph role should not have edit access to the museum editor');
        $this->assertEquals(0, $xpath->query('/profile/userInterfaces/userInterface[@code="museum_object_ui"]/groupAccess/permission[@group="memorial" and @access="edit"]')->length, 'The memorial role should not have edit access to the museum editor');

        $this->assertEquals(1, $xpath->query('/profile/userInterfaces/userInterface[@code="library_object_ui"]/groupAccess/permission[@group="library" and @access="edit"]')->length, 'The library role should have edit access to the library editor');
        $this->assertEquals(0, $xpath->query('/profile/userInterfaces/userInterface[@code="library_object_ui"]/groupAccess/permission[@group="museum" and @access="edit"]')->length, 'The museum role should not have edit access to the library editor');
        $this->assertEquals(0, $xpath->query('/profile/userInterfaces/userInterface[@code="library_object_ui"]/groupAccess/permission[@group="photograph" and @access="edit"]')->length, 'The photograph role should not have edit access to the library editor');
        $this->assertEquals(0, $xpath->query('/profile/userInterfaces/userInterface[@code="library_object_ui"]/groupAccess/permission[@group="memorial" and @access="edit"]')->length, 'The memorial role should not have edit access to the library editor');

        $this->assertEquals(1, $xpath->query('/profile/userInterfaces/userInterface[@code="photograph_object_ui"]/groupAccess/permission[@group="photograph" and @access="edit"]')->length, 'The photograph role should have edit access to the photograph editor');
        $this->assertEquals(0, $xpath->query('/profile/userInterfaces/userInterface[@code="photograph_object_ui"]/groupAccess/permission[@group="museum" and @access="edit"]')->length, 'The museum role should not have edit access to the photograph editor');
        $this->assertEquals(0, $xpath->query('/profile/userInterfaces/userInterface[@code="photograph_object_ui"]/groupAccess/permission[@group="library" and @access="edit"]')->length, 'The library role should not have edit access to the photograph editor');
        $this->assertEquals(0, $xpath->query('/profile/userInterfaces/userInterface[@code="photograph_object_ui"]/groupAccess/permission[@group="memorial" and @access="edit"]')->length, 'The memorial role should not have edit access to the photograph editor');

        $this->assertEquals(1, $xpath->query('/profile/userInterfaces/userInterface[@code="memorial_object_ui"]/groupAccess/permission[@group="memorial" and @access="edit"]')->length, 'The memorial role should have edit access to the memorial editor');
        $this->assertEquals(0, $xpath->query('/profile/userInterfaces/userInterface[@code="memorial_object_ui"]/groupAccess/permission[@group="museum" and @access="edit"]')->length, 'The museum role should not have edit access to the memorial editor');
        $this->assertEquals(0, $xpath->query('/profile/userInterfaces/userInterface[@code="memorial_object_ui"]/groupAccess/permission[@group="library" and @access="edit"]')->length, 'The library role should not have edit access to the memorial editor');
        $this->assertEquals(0, $xpath->query('/profile/userInterfaces/userInterface[@code="memorial_object_ui"]/groupAccess/permission[@group="photograph" and @access="edit"]')->length, 'The memorial role should not have edit access to the memorial editor');
    }

    public function testExampleUsersExist()
    {
        /** @var \DOMElement $role */
        foreach($this->xpath->query('/profile/roles/role') as $role){
            $role_code = $role->getAttribute('code');
            $this->assertEquals(1, $this->xpath->query("/profile/logins/login[@user_name='$role_code']")->length, "An example user for the role `$role_code` should exist");
        }
    }
}
