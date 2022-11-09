<?php

namespace CaTools\Profile;

use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;

abstract class AbstractProfileTest extends TestCase
{

    /** @var DOMDocument */
    protected $profile;
    /** @var  DOMXPath */
    protected $xpath;

    public function setUp(): void
    {
        $basePath = getenv('APP_ROOT');
        $this->profile = new DOMDocument();
        $profileName = getenv('PROFILE');
        $this->profile->load("$basePath/$profileName.xml");
        $this->xpath = new DOMXPath($this->profile);
        $this->xpath->registerNamespace('php', 'http://php.net/xpath');
        $this->xpath->registerPhpFunctions('preg_match');
    }

    public function assertUiExists(string $ui_code)
    {
        $this->assertEquals(
            1,
            $this->xpath->query("/profile/userInterfaces/userInterface[@code=\"$ui_code\"]")->length,
            "The $ui_code User Interface should exist"
        );
    }

    /**
     * @param  string  $ui_code
     * @param  string  $screen_idno
     */
    public function assertScreenExistsInUI(
        string $ui_code,
        string $screen_idno
    ): void {
        $this->assertEquals(
            1,
            $this->xpath->query("/profile/userInterfaces/userInterface[@code=\"$ui_code\"]/screens/screen[@idno=\"$screen_idno\"]")->length,
            "The `$screen_idno` screen must exist in the ui `$ui_code`"
        );
    }

    public function assertRelationshipTypeExists(string $leftTable, string $rightTable, string $type)
    {
        $rightTable = preg_replace('/^ca_/', '', $rightTable);
        $joinTable = "{$leftTable}_x_{$rightTable}";
        $query = "/profile/relationshipTypes/relationshipTable[@name=\"$joinTable\"]/types/type[@code=\"$type\"]";
        $this->assertEquals(
            1,
            $this->xpath->query($query)->length,
            "The `$type` relationship type must exist between $leftTable and $rightTable. Xpath query: $query"
        );
    }

    /**
     * @param array $settings
     * @param string $element
     */
    public function assertSettings(array $settings, string $element, $table, $type): void
    {
        foreach ($settings as $name => $value) {
            $this->assertMetadataRestrictionSetting(
                $value,
                $name,
                $element,
                $table,
                $type
            );
        }
    }

    /**
     * @param $type
     * @param $table
     * @param null  $context
     */
    protected function assertTypeExistsForTable($type, $table, $context = null)
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

    protected function assertFieldUsages(
        $expected,
        $bundle
    ): void {
        $this->assertCount(
            $expected,
            $this->xpath->query("/profile/userInterfaces/userInterface/screens/screen/bundlePlacements/placement/bundle[text()=\"$bundle\"]"),
            "The $bundle placement must occur exactly $expected times"
        );
    }

    /**
     * @param string $ui
     * @param string $screen
     * @param string $bundle
     * @param int $count
     * @param array $labels in the order that they should appear for the bundle in that ui.
     */
    protected function assertFieldExistsInUi(
        string $ui,
        string $screen,
        string $bundle,
        int $count = 1,
        array $labels = []
    ): void {
        $xpath = $this->xpath;
        $result =  $xpath->query("/profile/userInterfaces/userInterface[@code=\"$ui\"]/screens/screen[@idno=\"$screen\"]/bundlePlacements/placement/bundle[text() = \"$bundle\"]");
        $this->assertEquals(
            $count,
            $result->length,
            "The $bundle placement must exist on the $screen screen within the `$ui` ui"
        );
        if ($labels) {
            $this->assertCount($count, $labels, "Number of labels must match number of placements");
            /**
             * @var int $i
             * @var \DOMElement $node
             */
            foreach ($result as $i => $node) {
                $labelText = $labels[$i];

                $label = $xpath->query($node->parentNode->getNodePath() . '/settings/setting[@name="label"]')->item(0)->textContent;
                $this->assertEquals($labelText, $label, "Bundle $bundle on screen $screen in ui $ui requires the label $label");
            }
        }
    }

    /**
     * @param  string  $ui
     * @param  string  $screen
     * @param  string  $bundle
     */
    protected function assertFieldDoesNotExistInUi(
        string $ui,
        string $screen,
        string $bundle
    ): void {
        $xpath = $this->xpath;
        $this->assertEquals(
            0,
            $xpath->query("/profile/userInterfaces/userInterface[@code=\"$ui\"]/screens/screen[@idno=\"$screen\"]/bundlePlacements/placement/bundle[text() = \"$bundle\"]")->length,
            "The $bundle placement must not exist on the $screen screen within the ui $ui"
        );
    }

    protected function assertFieldSetting(
        string $expected,
        string $ui,
        string $screen,
        string $bundle,
        string $setting,
        int $count = 1
    ): void {
        $xpathResult = $this->xpath->query("/profile/userInterfaces/userInterface[@code=\"$ui\"]/screens/screen[@idno=\"$screen\"]/bundlePlacements/placement[bundle[text()=\"$bundle\"]]/settings/setting[@name=\"$setting\"]");
        $this->assertCount($count, $xpathResult, "Number of settings for `$setting` setting for `$ui` > `$screen` > `$bundle` should match expected $count");
        $this->assertEquals(
            $expected,
            $xpathResult->item(0)->textContent,
            "The `$setting` setting for the `$ui` ui does not match \"$expected\""
        );
    }
    /**
     * @param  string  $code
     */
    public function assertMetadataElementExists(string $code): void
    {
        $query = "/profile/elementSets//metadataElement[@code=\"{$code}\"]";
        $this->assertCount(
            1,
            $this->xpath->query(
                $query
            ),
            "A metadata element `$code` should exist in the installation profile. Xpath query: $query"
        );
    }

    /**
     * @param string $code
     * @param string $container
     */
    public function assertMetadataElementExistsWithinContainer(string $code, string $container): void
    {
        $this->assertCount(
            1,
            $this->xpath->query(
                "/profile/elementSets//metadataElement[@code=\"{$container}\"]//metadataElement[@code=\"{$code}\"]"
            ),
            "A metadata element `$code` should exist within the container `$container`."
        );
    }

    /**
     * @param  string  $code
     */
    public function assertMetadataElementDoesntExist(string $code): void
    {
        $this->assertCount(
            0,
            $this->xpath->query(
                "/profile/elementSets//metadataElement[@code=\"{$code}\"]"
            ),
            "A metadata element `$code` should not exist in the installation profile."
        );
    }

    /**
     * @param  array  $reserved
     */
    public function assertMetadataElementsExist(array $reserved): void
    {
        foreach ($reserved as $code) {
            $this->assertMetadataElementExists($code);
        }
    }

    /**
     * @param string $code
     * @param string $table
     * @param null $subType [optional]
     * @param int $numRestrictions
     */
    public function assertMetadataRestrictionExists(string $code, string $table, $subType = null, int $numRestrictions = 1)
    {
        $subTypeRestriction = "";
        if ($subType) {
            $subTypeRestriction = " and type[text() = \"{$subType}\"]";
        }
        $xpathString = "/profile/elementSets/metadataElement[@code=\"{$code}\"]/typeRestrictions/restriction[table[text() = \"{$table}\"]{$subTypeRestriction}]";
        $resultLength = $this->xpath->query($xpathString)->length;

        $subTypeMessage = $subType ? '.' . $subType : '';
        $this->assertEquals(
            $numRestrictions,
            $resultLength,
            "Incorrect number of '{$table}{$subTypeMessage}' restrictions found on `MetadataElement` '{$code}'." .
            "\nThe restriction may be either missing, or matching on multiple subtype restrictions."
        );
    }

    /**
     * @param  string  $code
     * @param  int  $expectedLength
     * @param  string  $table[optional]
     */
    public function assertMetadataRestrictionLength($code, $expectedLength, $table = null): void
    {
        $tableRestriction = "";
        if ($table) {
            $tableRestriction = "[table[text() = \"${table}\"]]";
        }

        $xpathString = "/profile/elementSets/metadataElement[@code=\"{$code}\"]/typeRestrictions/restriction{$tableRestriction}";
        $actualLength = $this->xpath->query($xpathString)->length;

        $tableRestrictionMessage = $table ? " '{$table}'" : "";
        $this->assertEquals(
            $expectedLength,
            $actualLength,
            "Incorrect number of{$tableRestrictionMessage} restrictions found on the `MetadataElement` '{$code}'."
        );
    }

    /**
     * @param  string  $expected
     * @param  string|array  $codes
     * @param  string  $setting
     */
    protected function assertMetadataElementSetting($expected, $codes, $setting): void
    {
        $metadataElementText = "";
        if (is_array($codes)) {
            $metadataElementSelectors = array_map(function ($code) {
                return "metadataElement[@code=\"{$code}\"]";
            }, $codes);
            $metadataElementText = implode("/elements/", $metadataElementSelectors);
        } else {
            $metadataElementText = "metadataElement[@code=\"{$codes}\"]";
        }

        $xpathResult = $this->xpath->query("/profile/elementSets//{$metadataElementText}/settings/setting[@name=\"{$setting}\"]");
        $this->assertEquals(1, $xpathResult->length, "There should exist one `{$setting}` setting for `{$metadataElementText}`.\n/profile/elementSets//{$metadataElementText}/settings/setting[@name=\"{$setting}\"]");
        $this->assertEquals(
            $expected,
            $xpathResult->item(0)->textContent,
            "There should exist one `{$setting}` setting for the `{$metadataElementText}` MetadataElement."
        );
    }

    public function assertSettingCount($element, $setting, $count = 1)
    {
        $metadataElementText = "metadataElement[@code=\"{$element}\"]";
        $xpathResult = $this->xpath->query("/profile/elementSets//{$metadataElementText}/settings/setting[@name=\"{$setting}\"]");
        $this->assertEquals($count, $xpathResult->length, "Number of settings for setting `{$setting}` setting for `{$metadataElementText}`.\n/profile/elementSets//{$metadataElementText}/settings/setting[@name=\"{$setting}\"] should match");
    }
    /**
     * @param  string  $expected
     * @param  string  $setting
     * @param  string  $code
     * @param  string  $table
     * @param  string  $subType [optional]
     */
    public function assertMetadataRestrictionSetting($expected, $setting, $code, $table, $subType = null): void
    {
        $subTypeRestriction = "";
        if ($subType) {
            $subTypeRestriction = " and type[text() = \"{$subType}\"]";
        }
        $xpathString = "/profile/elementSets/metadataElement[@code=\"{$code}\"]/typeRestrictions" .
            "/restriction[table[text() = \"{$table}\"]{$subTypeRestriction}]/settings/setting[@name=\"{$setting}\"]";
        $actual = $this->xpath->query($xpathString);
        $this->assertCount(
            1,
            $actual,
            "Restriction setting `{$setting}` for metadataElement `$code` should exist in the installation profile."
        );

        $subTypeMessage = $subType ? '.' . $subType : '';
        $this->assertEquals(
            $expected,
            $actual->item(0)->textContent,
            "Incorrect value for {$setting} setting in '{$table}{$subTypeMessage}' in `MetadataElement` '{$code}'." .
            "\nThe restriction may be either missing, or matching on multiple subtype restrictions."
        );
    }

    /**
     * @param  string  $list_code
     * @param  string  $element_code
     */
    public function assertListExists(
        string $list_code,
        string $element_code
    ): void {
        $this->assertEquals(
            1,
            $this->xpath->query("/profile/lists/list[@code='$list_code']")->length,
            /** @lang XML */ "List attributes require a matching list. The list '$list_code' does not exist and is required for the $element_code element."
                             .
                             "\nHere's an xml element:\n" .
                             <<<"LIST_XML"
<list code="$list_code" hierarchical="0" system="1" vocabulary="0">
  <labels>
    <label locale="en_AU">
      <name>$list_code</name>
    </label>
  </labels>
</list>
LIST_XML
        );
    }

    /**
     * @param  string  $list_code
     * @param  string  $item
     */
    public function assertItemExistsInList(
        string $list_code,
        string $item
    ): void {
        $this->assertEquals(
            1,
            $this->xpath->query("/profile/lists/list[@code='$list_code']//item[@idno='$item']")->length,
            /** @lang XML */ "Item with idno '$item' required in list '$list_code'"
                             .
                             "\nHere's an xml element:\n" .
                             <<<"ITEM_XML"

<item idno="$item" enabled="1" default="0" value="$item">
  <labels>
    <label locale="en_AU" preferred="1">
      <name_singular>$item</name_singular>
      <name_plural>$item</name_plural>
      <description/>
    </label>
  </labels>
</item>
ITEM_XML
        );
    }

    /**
     * @param string $list_code
     * @param int $expectedLength
     */
    public function assertListLength(
        string $list_code,
        int $expectedLength
    ): void {
        $actualLength = $this->xpath->query("/profile/lists/list[@code='{$list_code}']//items/item")->length;
        $this->assertEquals(
            $expectedLength,
            $actualLength,
            "Incorrect number of items found in '{$list_code}'."
        );
    }
}
