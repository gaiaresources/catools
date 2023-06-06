<?php

namespace CaTools\Profile;

use ApplicationException;
use Datamodel;
use Phinx\Migration\AbstractMigration;
use Symfony\Component\Console\Helper\ProgressBar;

abstract class AbstractModelDeletionMigration extends AbstractMigration
{
    /**
     * Defines the list of tables and records that you want this migration to delete.
     *
     * @return array keyed with CA table names with idno of records you want to delete.
     */
    abstract protected function getTables();

    public function up()
    {
        $this->deleteRecords();
    }

    /**
     * @throws ApplicationException
     */
    protected function deleteRecords()
    {
        $tables = $this->getTables();
        foreach ($tables as $table => $idnos) {
            $instance = Datamodel::getInstance($table);
            if (!is_array($idnos)) {
                $idnos = [$idnos];
            }
            foreach ($idnos as $idno) {
                $ids = $instance::find(['idno' => $idno], ['allowWildcards' => true, 'returnAs' => 'ids']);
                $instance->setMode(ACCESS_WRITE);
                $total = count($ids) ?? 1;
                $progressBar = new ProgressBar($this->getOutput(), $total);
                $progressBar->setRedrawFrequency(5);
                $progressBar->setFormat('%current%/%max% -- %message%');
                $progressBar->setMessage("Deleting records in $idno");
                foreach ($ids as $id) {
                    $instance->load($id);
                    $progressBar->advance();
                    $instance->delete(false, ['queueIndexing' => true]);
                }
                $progressBar->finish();
            }
        }

    }

}
