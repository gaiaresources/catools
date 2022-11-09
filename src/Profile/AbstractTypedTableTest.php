<?php

namespace CaTools\Profile;

abstract class AbstractTypedTableTest extends AbstractProfileTest
{
    /**
     * @return string|null
     */
    abstract public function getTable();
    /**
     * @return string|null
     */
    abstract public function getType();
    /**
     * @return string|null
     */
    abstract public function getUI();

    /**
     * @return array
     */
    abstract public function getScreens();

    /**
     * @param string $element
     * @param array $settings
     * @param string $screen
     * @param int $numRestrictions
     */
    public function assertElementScreenAndSettings(string $element, array $settings, string $screen, int $numRestrictions = 1): void
    {
        $this->assertMetadataElementExists($element);
        $this->assertMetadataRestrictionExists($element, $this->getTable(), $this->getType(), $numRestrictions);
        $this->assertSettings($settings, $element, $this->getTable(), $this->getType());
        $this->assertFieldExistsInUi($this->getUI(), $screen, $this->getTable() . '.' . $element);
    }

    public function testUi()
    {
        $this->assertUiExists($this->getUI());
        if ($this->getType()) {
            $this->assertTypeExistsForTable($this->getType(), $this->getTable());
        }
        foreach ($this->getScreens() as $screen) {
            $this->assertScreenExistsInUI($this->getUI(), $screen);
        }
    }
}
