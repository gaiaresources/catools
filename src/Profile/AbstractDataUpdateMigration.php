<?php

namespace CaTools\Profile;
use Phinx\Migration\AbstractMigration;
use Phinx\Migration\IrreversibleMigrationException;

abstract class AbstractDataUpdateMigration extends AbstractMigration {

    protected $tableNum = '57';
    protected $objectTypes = [];
    protected $elementIds = [];
    protected $localeId = '1';

    function up() {
        $this->execute('SET NAMES utf8 COLLATE utf8_general_ci');
        $sql = <<<SQL
SELECT li.`idno`, li.`item_id`
FROM ca_list_items li
INNER JOIN ca_lists l ON l.list_id = li.list_id
WHERE
    l.`list_code`='object_types'
  AND
    li.idno NOT LIKE 'Root%';
        ;
SQL;
        foreach ($this->query($sql) as $row) {
            $this->objectTypes[$row['idno']] = $row['item_id'];
        }
    }

    /**
     * @param $elementCodes
     */
    public function setElementIds($elementCodes) {
        $elementCodeString = static::arrayToSQL($elementCodes);
        $sql = <<<SQL
        SELECT `element_code`, `element_id`
        FROM ca_metadata_elements
        WHERE element_code IN {$elementCodeString};
SQL;

        foreach ($this->query($sql) as $row) {
            $this->elementIds[$row['element_code']] = $row['element_id'];
        }
        $diff = array_diff($elementCodes, array_keys($this->elementIds));
        if (!empty($diff)) {
            throw new IrreversibleMigrationException('Unable to map element ids for: ' . implode(', ', $diff));
        }
    }

    protected function insertAttributeFixedValue($containerId, $elementId, $value, $list=false) {
        $fields = [
            'element_id' => $elementId,
            'attribute_id' => 'a.`attribute_id`',
            'value_longtext1' => $value,
            'source_info' => '\'\'',
        ];
        if ($list) {
            $fields['item_id'] = $value;
        }

        if ($containerId == $elementId) {
            $this->insertAttributeFixedValueAttribute($elementId);
        }
        $this->insertAttributeFixedValueAttributeValue($fields, $containerId, $elementId);
    }

    protected function insertAttributeFixedValueAttribute($elementId) {
        $sql = <<<SQL
INSERT INTO ca_attributes(`element_id`, `locale_id`, `table_num`, `row_id`)
SELECT {$elementId}, {$this->localeId}, {$this->tableNum}, `object_id`
FROM ca_objects
WHERE type_id={$this->objectTypes['item']};
SQL;
        $this->execute($sql);
    }

    protected function insertAttributeFixedValueAttributeValue($fields, $containerId, $elementId) {
        $fieldDestString = static::arrayToSQL(array_keys($fields), '`');
        $fieldValuesString = implode(',', $fields);

        $sql = <<<SQL
INSERT INTO ca_attribute_values {$fieldDestString}
SELECT  $fieldValuesString
FROM ca_attributes a
INNER JOIN ca_objects o ON a.row_id=o.object_id 
WHERE 
      a.element_id={$containerId}
  AND
      o.type_id={$this->objectTypes['item']}; 
SQL;
        $this->execute($sql);
    }

    protected function purgeExistingAttributes($containerId, $elementId) {
        $sql = <<<SQL
UPDATE ca_attribute_values av
INNER JOIN ca_attributes a  on a.attribute_id = av.attribute_id
SET
    av.item_id=null,
    value_longtext1=null,
    av.value_decimal1 = null,
    av.value_decimal2 = null,
    av.value_integer1 = null,
    av.value_longtext2 = null,
    av.value_blob = null
WHERE
    av.element_id={$elementId}
AND
    a.element_id={$containerId}
  AND
    a.table_num={$this->tableNum};
SQL;
        $this->execute($sql);
    }

    public static function arrayToSQL($data, $quote = '\'') {
        return "($quote" . implode("$quote, $quote", $data) . "$quote)";
    }

    public function fetchListItems($list_names, $byLabel=false) {
        $returnBy = $byLabel ? 'name_singular' : 'idno';

        $listNamesString = static::arrayToSQL($list_names);
        $sql = <<<SQL
        SELECT lil.`name_singular`, li.`idno`, li.`item_id`, l.`list_code`
        FROM ca_list_item_labels lil
        INNER JOIN ca_list_items li ON lil.item_id = li.item_id
        INNER JOIN ca_lists l ON l.list_id = li.list_id
        WHERE l.`list_code` IN {$listNamesString} AND lil.`name_singular` NOT LIKE 'Root%';
SQL;

        $listItems = [];
        foreach ($this->query($sql) as $row) {
            $listItems[$row['list_code']][$row[$returnBy]] = $row['item_id'];
        }
        $diff = array_diff($list_names, array_keys($listItems));
        if (!empty($diff)) {
            throw new IrreversibleMigrationException('Unable to map list items for: ' . implode(', ', $diff));
        }
        return $listItems;

    }

    public function getListItemValue($listItems, $listCode, $listItem) {
        $value = $listItems[$listCode][$listItem];
        if (!isset ($value)) {
            throw new IrreversibleMigrationException("Unable to find list item value for \"{$listItem}\" in list \"{$listCode}\"");
        }
        return $value;
    }
}
