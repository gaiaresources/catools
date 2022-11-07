<?php

namespace CaTools\Profile;

use Exception;
use Phinx\Config\Config;
use Phinx\Migration\Manager;

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

        $sourceFilename = $this->getProfileFilename();
        // Profile needs to be in the directory.
        $filename = __CA_BASE_DIR__ . "/install/profiles/xml/$profile.xml";
        if (!is_file($sourceFilename)){
            throw new Exception("Migration file $sourceFilename for profile $profile does not exist");
        }
        copy($this->getProfileFilename(), $filename);
        $command = "support/bin/caUtils update-installation-profile --profile-name $profile";
        $this->runCommand($command);
        unlink($filename);
    }

    /**
     * Profile migrations by default cannot go `down` so we simply implement an empty method.
     * @return void
     */
    public function down()
    {
    }

    /**
     * Defines whether migrations should be run on the source system.
     *
     * @return bool
     */
    protected function shouldRun(): bool
    {
        // listed on https://instanceurl/index.php/administrate/setup/ConfigurationCheck/DoCheck
        return defined('__CA_SYSTEM_GUID__') ? !in_array(__CA_SYSTEM_GUID__, $this->getExportedFromArray()) : true;
    }
    /**
     * Defines the profile name for the migration.
     * @return string
     */
 
    abstract protected function getProfileName():string;

    protected function isApplicable(): bool
    {
        $options = $this->getAdapter()->getOptions();
        $lastMigration = (int) $options['includes_migrations_until'] ?? 0;
        return (int) $this->getVersion() > $lastMigration;
    }

    /**
     * @return string
     */
    protected function getProfileFilename(): string
    {
        $profile = $this->getProfileName();
        return dirname(__CA_BASE_DIR__) . "/db/migrations/$profile.xml";
    }

}
