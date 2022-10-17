<?php
namespace RWAHS\Profile;

use PHPUnit\Framework\TestCase;
use DOMDocument;
use DOMXPath;

abstract class AbstractProfileTest extends TestCase
{

    /** @var DOMDocument */
    protected $profile;
    /** @var  DOMXPath */
    protected $xpath;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $basePath = dirname(__DIR__, 3);
        $this->profile = new DOMDocument();
        $profileName = getenv('PROFILE');
        $this->profile->load("$basePath/installation-profile/profile/$profileName.xml");
        $this->xpath = new DOMXPath($this->profile);
        $this->xpath->registerNamespace('php', 'http://php.net/xpath');
        $this->xpath->registerPhpFunctions('preg_match');
    }

    /**
     * @param $type
     * @param $table
     * @param null $context
     * @return mixed
     */
    protected function typeExistsForTable($type, $table, $context = null)
    {
        $context = $context ? " Context: $context." : null;
        $table_map = array(
            'ca_entities' => 'entity',
            'ca_set_items' => 'set',
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
