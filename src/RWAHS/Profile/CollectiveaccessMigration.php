<?php
namespace RWAHS\Profile;

use BaseModel;
use ca_bundle_display_placements;
use ca_bundle_displays;
use ca_editor_ui_bundle_placements;
use ca_editor_ui_screens;
use Exception;
use Phinx\Migration\AbstractMigration;

abstract class CollectiveaccessMigration extends AbstractMigration
{

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
    public function setAndSaveSettings(array $settings, BaseModel $placement, BaseModel $screenOrDisplay)
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
}
