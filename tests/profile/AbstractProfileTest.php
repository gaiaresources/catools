<?php

abstract class AbstractProfileTest extends PHPUnit_Framework_TestCase
{

    /** @var DOMDocument */
    protected $profile;
    /** @var  DOMXPath */
    protected $xpath;

    public function setUp()
    {
        $basePath = dirname(dirname(__DIR__));
        $this->profile = new DOMDocument();
        $this->profile->load("$basePath/profile/rwahs.xml");
        $this->xpath = new DOMXPath($this->profile);
    }

    /**
     * @param $type
     * @param $table
     * @return mixed
     */
    protected function typeExistsForTable($type, $table, $context = null)
    {
        $context = $context ? " Context: $context." : null;
        $table_map = array(
            'ca_entities' => 'entity'
        );
        if (isset($table_map[$table])) {
            $table = $table_map[$table];
        } else {
            $table = preg_replace('/^ca_(.*)s$/', '$1', $table);
        }
        $list_code = $table . '_types';
        $type_list = $this->xpath->query("/profile/lists/list[@code='$list_code']");
        $this->assertEquals(1, $type_list->length, "The type list for $table ($list_code) needs to exist.$context");
        $this->assertEquals(1, $this->xpath->query("/profile/lists/list[@code='$list_code']/items//item[@idno='$type']")->length, "The type '$type' must exist in the list for $table ($list_code).$context");
    }
}
