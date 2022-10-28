<?php
namespace RWAHS\Profile;

use ApplicationException;
use BaseModel;
use ca_bundle_display_placements;
use ca_bundle_displays;
use ca_editor_ui_bundle_placements;
use ca_editor_ui_screens;
use ca_editor_uis;
use Exception;
use Phinx\Migration\AbstractMigration;
use Symfony\Component\Process\Process;

abstract class CollectiveaccessMigration extends AbstractMigration
{
    public const INSTANCE_PARAMS = ['returnAs' => 'firstModelInstance'];

    /**
     * @param ca_editor_ui_screens|ca_bundle_displays $screenOrDisplay
     * @param ca_editor_ui_bundle_placements|ca_bundle_display_placements $placement
     * @param array $settings
     * @return void
     * @throws Exception
     */
    public function saveSettings(BaseModel $screenOrDisplay, BaseModel $placement, array $settings): void
    {
        // Reload screen/display without using the cache
        $this->loadSettingDefinitions($screenOrDisplay, $placement);
        $this->setAndSaveSettings($settings, $placement, $screenOrDisplay);
        $placement->update();
    }

    /**
     * @param array $settings
     * @param ca_editor_ui_bundle_placements|ca_bundle_display_placements $placement
     * @param ca_editor_ui_screens|ca_bundle_displays $screenOrDisplay
     * @throws Exception
     */
    private function setAndSaveSettings(array $settings, BaseModel $placement, BaseModel $screenOrDisplay)
    {
        $errors = [];
        foreach ($settings as $setting => $value) {
            if (!$placement->isValidSetting($setting)) {
                $errors[] = sprintf("Setting %s is not a valid setting for placement %s in within %s %s.", $setting, json_encode($placement->getFieldValuesArray()), get_class($screenOrDisplay), json_encode($screenOrDisplay->getFieldValuesArray()));
            } else {
                $savedSettings = $placement->setSetting($setting, $value);
                $savedValue = $savedSettings[$setting] ?? null;
                if ($savedSettings === false) {
                    $errors[] = sprintf("Failed to save setting %s with value %s", $setting, json_encode($value));
                } elseif ($savedValue != $value) {
                    $errors[] = sprintf("Setting %s does not have the same set value (%s) as we have just tried to set (%s)", $setting, json_encode($savedValue), $value);
                }
            }
        }
        if ($errors) {
            throw new Exception('Failed to add settings with the following error messages' . json_encode($errors));
        }
        if ($placement->numErrors()) {
            throw new Exception('Failed to save placement with the following error messages' . json_encode($placement->getErrorDescriptions()));
        }
    }

    /**
     * @param $screenOrDisplay
     * @param $placement
     * @return void
     */
    public function loadSettingDefinitions($screenOrDisplay, $placement): void
    {
        $screenOrDisplay->load($screenOrDisplay->getPrimaryKey(), false);
        $bundleName = $placement->get('bundle_name');
        $availableBundles = $screenOrDisplay->getAvailableBundles();
        $bundleSettings = $availableBundles[$bundleName]['settings'] ?? [];
        // Reload placement without using the cache
        $placement->load($placement->getPrimaryKey(), false);
        // Required in order to be able to save new settings against the placement
        $placement->setSettingDefinitionsForPlacement($bundleSettings);
    }

    /**
     * Run a cli command in a process and throw an exception on any captured errors.
     * @param string $command
     * @param int $timeout
     * @throws Exception
     */
    public function runCommand(string $command, int $timeout = 0): void
    {
        $process = Process::fromShellCommandline($command, __CA_BASE_DIR__);
        $process->setTimeout($timeout);
        $errored = false;
        $errors = [];
        // This callback enables the command's output to be passed through.
        $process->mustRun(function ($type, $buffer) use ($errored, &$errors) {
            $errored |= preg_match('/\d+\s+errors?\s+occurred/', $buffer);
            $errored |= preg_match('/Invalid options specified/', $buffer);
            if (Process::ERR === $type || $errored) {
                $this->getOutput()->writeln('<error>ERROR</error> ' . $buffer);
                $errors[] = $buffer;
            } else {
                $this->getOutput()->writeln($buffer);
            }
        });
        if ($errors) {
            throw new Exception("Migration failed:\n\t" . join("\n\t", $errors));
        }
    }

    /**
     * @param string $bundleName
     * @param ca_editor_ui_screens $screen
     * @param ca_editor_uis $ui
     * @param null|string $after
     * @return array|ca_editor_ui_bundle_placements|false|int|mixed|null
     * @throws ApplicationException
     */
    public function ensurePlacement(string $bundleName, ca_editor_ui_screens $screen, ca_editor_uis $ui, $after = null)
    {
        $placement = ca_editor_ui_bundle_placements::find(['bundle_name' => $bundleName, 'screen_id' => $screen->getPrimaryKey()], self::INSTANCE_PARAMS);
        if (!$placement) {
            if ($after) {
                $placementId = $ui->addPlacementToScreenAfter($screen->getPrimaryKey(), $bundleName, $bundleName, [], $after);
            } else {
                $placementId = $ui->addPlacementToScreen($screen->getPrimaryKey(), $bundleName, $bundleName, []);
            }
            $placement = new ca_editor_ui_bundle_placements($placementId);
        } else {
            $placementId = $placement->getPrimaryKey();
        }
        $placement->load($placementId, false);
        return $placement;
    }
}
