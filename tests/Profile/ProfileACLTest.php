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
    }
    public function testProfileContainsRoles()
    {
        $xpath = $this->xpath;
        $this->assertEquals(2, $xpath->query('/profile/roles/role')->length, 'The number of roles should match');
        $this->assertEquals(1, $xpath->query('/profile/roles/role[@code="museum"]')->length, 'The role with code "museum" should exist.');
        $this->assertEquals(1, $xpath->query('/profile/roles/role[@code="library"]')->length, 'The role with code "library" should exist.');
    }
}
