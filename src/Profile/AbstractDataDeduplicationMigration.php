<?php

namespace CaTools\Profile;
use Phinx\Migration\AbstractMigration;

abstract class AbstractDataDeduplicationMigration extends AbstractMigration {
    /**
     * Adapted from @see \DeleteDuplicatePids
     *
     * Deletes all duplicate values for a specific metadata element.
     */
    protected function deleteDuplicateRows($element)
    {
        $this->execute('SET NAMES utf8 COLLATE utf8_general_ci');

        $this->execute(<<<'TEMP_TABLE'
CREATE TEMPORARY TABLE duplicate_attributes (
    row_id INT(10),
    table_num TINYINT(3),
    num_records INT(10),
    attribute_id INT(10),
    INDEX attribute_id_idx(attribute_id),
    INDEX row_id_ids(row_id),
    INDEX table_num_idx(table_num),
    INDEX combined_idx(table_num, row_id, attribute_id)
)
TEMP_TABLE
        );
        $this->execute(<<<"KEEP_ROWS"
INSERT INTO duplicate_attributes
SELECT 
       row_id, 
       table_num,
       count(*) AS num_records,
       min(a.attribute_id) AS attribute_id 
FROM ca_attribute_values av 
    JOIN ca_metadata_elements cme ON av.element_id = cme.element_id
    JOIN ca_attributes a ON av.attribute_id = a.attribute_id
WHERE element_code = '$element'
GROUP BY row_id, table_num
HAVING num_records > 1
KEEP_ROWS
        );
        $this->execute('SET FOREIGN_KEY_CHECKS=0');
        $this->execute(<<<"DELETE_ROWS"
DELETE
    av.*,
    a.* FROM ca_attribute_values av,
    ca_attributes a,
    duplicate_attributes d,
    ca_metadata_elements cme
WHERE 
      av.attribute_id = a.attribute_id AND 
      a.table_num = d.table_num AND 
      a.row_id = d.row_id AND
      a.attribute_id != d.attribute_id AND
      av.element_id = cme.element_id AND
      cme.element_code = '$element'
DELETE_ROWS
        );
        $this->execute('SET FOREIGN_KEY_CHECKS=1');

        $this->execute('DROP TEMPORARY TABLE duplicate_attributes');
    }
}
