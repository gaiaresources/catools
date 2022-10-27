<?php

namespace RWAHS\Profile;

require_once __CA_LIB_DIR__ . '/Plugins/SearchEngine/ElasticSearch.php';

use ca_bundle_displays;
use ca_editor_ui_screens;
use ca_editor_uis;
use ca_list_items;
use ca_lists;
use ca_locales;
use ca_metadata_elements;
use ca_storage_locations;
use CompositeCache;
use Datamodel;
use Db;
use Exception;
use Symfony\Component\Process\Process;

abstract class AbstractProfileMigration extends CollectiveaccessMigration
{
    /**
     * @throws Exception
     **/
    public function up()
    {
        $profile = $this->getProfileName();
        if (!$this->shouldRun()) {
            $this->getOutput()->writeln(
                "Skipping migration $profile." .
                " Most likely due to this being the system that migrations are sourced from."
            );
            return;
        }
        if (!$this->isApplicable()) {
            $this->getOutput()->writeln("Skipping migration $profile as migration is older than install date.");
            return;
        }

        // Profile needs to be in the directory.
        $filename = __CA_BASE_DIR__ . "/install/profiles/xml/$profile.xml";
        $sourceFilename = $this->getProfileFilename($profile);
        copy($sourceFilename, $filename);
        $command = "support/bin/caUtils update-installation-profile --profile-name $profile";
        $this->runCommand($command);
        unlink($filename);
        $emptyLocations = ca_storage_locations::find(['idno' => ''], ['returnAs' => 'modelInstances']);
        /** @var ca_storage_locations $emptyLocation */
        foreach ($emptyLocations as $emptyLocation) {
            $emptyLocation->delete(true, ['hard' => true]);
        }
    }

    public function down()
    {
    }

    /**
     * Defines whether migrations should be run on the source system.
     *
     * @return bool
     */
    protected function shouldRun()
    {
        // listed on https://instanceurl/index.php/administrate/setup/ConfigurationCheck/DoCheck
        return defined('__CA_SYSTEM_GUID__') ? !in_array(__CA_SYSTEM_GUID__, $this->getExportedFromArray()) : true;
    }
    /**
     * Defines the system(s) a profile update was exported from.
     * Allows to update profile in parallel on different machines and only
     * apply the updates not created in the environment. By default, set to NOT
     * run on any of the servers.
     *
     * @return array
     */
 
    abstract protected function getProfileName();

    protected function isApplicable()
    {
        $options = $this->getAdapter()->getOptions();
        $lastMigration = (int) $options['includes_migrations_until'] ?? 0;
        return (int) $this->getVersion() > $lastMigration;
    }

    /**
     * @param string $table
     * @param string $element
     */
    public function deleteEmptyTypeRestrictionForElement(string $table, string $element): void
    {
        $db = new Db();
        $db->dieOnError(true);
        $typeList = Datamodel::getInstance($table)->getTypeListCode();
        CompositeCache::flush('ElementTypeRestrictions');
        $this->getOutput()->writeln("Removing orphan type restrictions on element $element in table $table");
        $db->query("DELETE cmtr.* FROM ca_list_items i JOIN ca_lists l ON (i.list_id = l.list_id) JOIN ca_metadata_type_restrictions cmtr on cmtr.type_id = i.item_id WHERE idno = ? and list_code = ? AND i.deleted", [$element, $typeList]);
    }

    /**
     * @param string $table
     * @param string $element
     */
    public function deleteTypeRestrictionsForTable(string $table): void
    {
        $db = new Db();
        $db->dieOnError(true);
        $tableNum = Datamodel::getTableNum($table);
        CompositeCache::flush('ElementTypeRestrictions');
        $this->getOutput()->writeln("Removing all type restrictions for table $table");
        $db->query("DELETE FROM ca_metadata_type_restrictions WHERE table_num = ?", [$tableNum]);
    }

    /**
     * Move an element to the top of the container that it is in.
     * @param  string  $code
     *
     * @throws \ApplicationException
     */
    protected function moveElementUp(string $code): void
    {
        /** @var \ca_metadata_elements $element */
        $element = ca_metadata_elements::find(
            ['element_code' => $code],
            ['returnAs' => 'firstModelInstance']
        );
        if ($element) {
            $siblings = $element->getHierarchySiblings();
            $min = min(array_column($siblings, 'rank'));
            $element->set('rank', $min - 1);
            $element->update();
        }
    }

    /**
     * @return string
     */
    protected function getProfileFilename(): string
    {
        $profile = $this->getProfileName();
        return dirname(dirname(__DIR__)) . "/db/migrations/$profile.xml";
    }

    /**
     * @param string $element
     * @param bool $recursive
     * @throws \ApplicationException
     */
    protected function deleteElement(string $element, bool $recursive = false): void
    {
        /** @var ca_metadata_elements $e */
        $e = ca_metadata_elements::findAsInstance(['element_code' => $element]);
        if ($e) {
            $this->getOutput()->writeln("Deleting metadata element $element");

                $children = ca_metadata_elements::find(['parent_id' => $e->getPrimaryKey()]);
            foreach ($children as $child) {
                $c = new ca_metadata_elements($child);
                if ($c) {
                    $childCode = $c->get('element_code');
                    if ($recursive) {
                        $this->deleteElement($childCode, $recursive);
                    } else {
                        throw new Exception("Cannot delete element because it has child elements including $childCode. All child elements: " . implode(',', $children));
                    }
                }
            }

            $this->deletePlacements($e);
            $e->delete(true, ['hard' => true, 'dontLogChange' => true]);
            if ($e->numErrors()) {
                throw new Exception(json_encode($e->getErrors()));
            }
        }
    }

    /**
     * @param string $uiCode
     * @param string $screenCode
     * @throws Exception
     */
    protected function deleteScreen(string $uiCode, string $screenCode): void
    {
        $ui = ca_editor_uis::findAsInstance(['editor_code' => $uiCode]);
        $this->getOutput()->writeln("Deleting screen $screenCode from ui $uiCode");
        if ($ui) {
            /** @var ca_editor_ui_screens $screen */
            $screen = ca_editor_ui_screens::loadScreen($ui->getPrimaryKey(), $screenCode);
            if ($screen) {
                $screen->removeAllPlacements();
                $screen->delete(true, ['hard' => true, 'dontLogChange' => true]);
                if ($screen->numErrors()) {
                    throw new Exception(json_encode($screen->getErrors()));
                }
            }
        }
    }

    /**
     * @param string $uiCode
     * @throws Exception
     */
    protected function deleteUi(string $uiCode, bool $screensOnly = false): void
    {
        /** @var ca_editor_uis $ui */
        $ui = ca_editor_uis::findAsInstance(['editor_code' => $uiCode]);
        if ($ui) {
            $this->getOutput()->writeln("Deleting UI $uiCode");
            foreach ($ui->getScreens(null, ['showAll' => true]) as $i => $screen) {
                $this->deleteScreen($uiCode, $screen['idno']);
            }
            if (!$screensOnly) {
                $ui->delete(true, ['hard' => true, 'dontLogChange' => true]);
                if ($ui->numErrors()) {
                    throw new Exception(json_encode($ui->getErrors()));
                }
            }
        }
    }

    /**
     * @param string $localeId
     * @throws Exception
     */
    protected function deleteLocale(string $localeId): void
    {
        $locale = new ca_locales();
        $locale->load($localeId);
        $locale->setMode(ACCESS_WRITE);
        $locale->delete(true, ['hard' => true, 'dontLogChange' => true]);
        if ($locale->numErrors()) {
            throw new Exception(json_encode($locale->getErrors()));
        }
    }

    /**
     * @param string $uiCode
     * @param string $screen
     * @param array $bundles
     * @throws Exception
     */
    protected function deleteBundlesFromScreenInUi(string $uiCode, string $screen, array $bundles): void
    {
        $ui = ca_editor_uis::findAsInstance(['editor_code' => $uiCode]);
        if ($ui) {
            $tableNum = $ui->get('editor_type');
            $tableName = \Datamodel::getTableName($tableNum);
            $screenInstance = ca_editor_ui_screens::loadScreen($ui->getPrimaryKey(), $screen);
            if ($screenInstance) {
                foreach ($bundles as $bundleName) {
                    $placement = $screenInstance->findPlacement($bundleName);
                    // Find a placement using the legacy format.
                    if (!$placement && preg_match("/$tableName\.(.*)/", $bundleName, $matches)) {
                        $placement = $screenInstance->findPlacement("ca_attribute_$matches[1]");
                    }
                    if ($placement) {
                        $this->getOutput()->writeln("Deleting bundle $bundleName from screen $screen in ui $uiCode");
                        $placement->delete(true, ['hard' => true, 'dontLogChange' => true]);
                        if ($placement->numErrors()) {
                            throw new Exception(json_encode($placement->getErrors()));
                        }
                    }
                }
            }
        }
    }

    /**
     * @param string $listCode
     * @throws Exception
     */
    protected function deleteList(string $listCode): void
    {
        /** @var ca_lists $list */
        $list = ca_lists::findAsInstance(['list_code' => $listCode]);
        if ($list) {
            $this->getOutput()->writeln("Deleting list $listCode");
            $this->deleteListItems($list);
            $list->delete(true, ['hard' => true, 'dontLogChange' => true]);
            if ($list->numErrors()) {
                throw new Exception(json_encode($list->getErrors()));
            }
        }
    }
    /**
     * @param string $listId
     * @throws Exception
     */
    protected function deleteListById(int $listId): void
    {
        /** @var ca_lists $list */
        $list = new ca_lists($listId);
        if ($list->getPrimaryKey()) {
            $this->getOutput()->writeln("Deleting list {$list->get('list_code')} with id $listId");
            $this->deleteListItems($list);
            $list->delete(true, ['hard' => true, 'dontLogChange' => true]);
            if ($list->numErrors()) {
                throw new Exception(json_encode($list->getErrors()));
            }
        }
    }

    /**
     * @param ca_metadata_elements $e
     * @throws Exception
     */
    protected function deletePlacements(ca_metadata_elements $e): void
    {
        \CompositeCache::flush('ElementTypeRestrictions');
        $restrictions = $e->getTypeRestrictions();
        $tables = [];
        foreach ($restrictions as $restriction) {
            $tables[$restriction['table_num']] = $restriction['table_num'];
        }
        foreach ($tables as $tableName) {
            $tableName = \Datamodel::getTableName($tableName);

            foreach (ca_editor_uis::getUIList($tableName) as $ui) {
                $uiInstance = new ca_editor_uis($ui['ui_id']);
                if (!$uiInstance) {
                    continue;
                }
                foreach ($uiInstance->getScreens() as $screen) {
                    $this->deleteBundlesFromScreenInUi($ui['editor_code'], $screen['idno'], [$tableName . '.' . $e->get('element_code')]);
                }
            }
        }
    }

    /**
     * @param string $displayCode
     */
    protected function deleteDisplay(string $displayCode): void
    {
        /** @var ca_bundle_displays $display */
        $display = ca_bundle_displays::findAsInstance(['display_code' => $displayCode]);
        if ($display) {
            $display->delete('true', ['hard' => true]);
        }
    }

    protected function deleteListItem(string $listCode, string $idno, $id = null)
    {
        if (!$id) {
            $id = caGetListItemID($listCode, $idno);
        }
        $item = new ca_list_items($id);
        if ($item && $item->getPrimaryKey()) {
            $item->delete(true, ['hard' => true]);
            if ($item->numErrors()) {
                $this->getOutput()->writeln($item->getErrors());
            }
        }
    }

    /**
     * @param ca_lists $list
     * @throws \ApplicationException
     */
    protected function deleteListItems(ca_lists $list)
    {
        $items = ca_list_items::find(['list_id' => $list->getPrimaryKey()], ['includeDeleted' => true, 'returnAs' => 'arrays']);
        usort($items, function ($a, $b) {
            return $b['hier_left'] <=> $a['hier_left'];
        });
        foreach ($items as $item) {
            $this->deleteListItem($list->get('list_code'), $item['idno'], $item['item_id']);
        }
    }

    /**
     * Delete records that have been marked as deleted in the database.
     * @throws Exception
     */
    protected function purgeDeletedRecords(): void
    {
        try {
            $this->getAdapter()->getConnection();
            $this->query('SELECT 1');
        } catch (\PDOException $e) {
            $this->getOutput()->writeln($e->getMessage());
            $this->getOutput()->writeln('Attempting to reconnect');
            $this->getAdapter()->disconnect();
            $this->getAdapter()->connect();
        }
        $orphanLists = $this->query("SELECT list_id FROM ca_lists WHERE deleted");
        foreach ($orphanLists->fetchAll(\PDO::FETCH_COLUMN) as $listId) {
            $this->deleteListById((int)$listId);
        }
        $process = Process::fromShellCommandline('yes y 2>/dev/null| caUtils purge-deleted');
        $process->setTimeout(null);
        $process->mustRun(function ($type, $output) {
            if ($type === 'out') {
                $this->getOutput()->writeln($output);
            } else {
                $this->getOutput()->writeln("<error>$type $output</error>");
            }
        });
    }

    protected function backupAlerts()
    {
        $db = new Db();
        if (array_search('backup_ca_metadata_alert_triggers', $db->getTables()) === false) {
            $db->query('CREATE TABLE `backup_ca_metadata_alert_triggers` (
  `trigger_id` int(10) unsigned NOT NULL DEFAULT 0,
  `rule_id` int(10) unsigned NOT NULL,
  `element_code` varchar(30) CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`trigger_id`),
  KEY `element_code` (`element_code`),
  KEY `trigger_id` (`trigger_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1

');
            $db->query('REPLACE INTO `backup_ca_metadata_alert_triggers` SELECT trigger_id, rule_id, cme.element_code
FROM ca_metadata_alert_triggers t
         JOIN ca_metadata_elements cme on t.element_id = cme.element_id');
            $db->query('UPDATE ca_metadata_alert_triggers
SET element_id = NULL
WHERE element_id IS NOT NULL ');
        }
    }

    protected function restoreAlerts()
    {
        $db = new Db();
        if (array_search('backup_ca_metadata_alert_triggers', $db->getTables()) !== false) {
            $db->query('update backup_ca_metadata_alert_triggers t
    JOIN ca_metadata_elements cme on t.element_code = cme.element_code JOIN ca_metadata_alert_triggers cmat on cmat.trigger_id = t.trigger_id
SET cmat.element_id = cme.element_id WHERE cmat.element_id IS NULL');
            $db->query('DROP TABLE backup_ca_metadata_alert_triggers');
        }
    }
}
