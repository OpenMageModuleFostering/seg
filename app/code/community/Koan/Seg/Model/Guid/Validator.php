<?php

class Koan_Seg_Model_Guid_Validator extends Mage_Core_Model_Config_Data
{
    protected function _beforeSave()
    {
        $value = $this->getValue();

        if ($value AND (strlen($value) != 36)) {
            Mage::throwException('Proper Website Id Length should be 32 characters!');
        }

        return $this;
    }
}