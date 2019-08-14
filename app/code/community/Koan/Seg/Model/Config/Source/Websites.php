<?php

class Koan_Seg_Model_Config_Source_Websites
{
    public function toOptionArray()
    {
        $websites = Mage::app()->getWebsites();
        $result = array();

        foreach ($websites as $website) {
            $value = Mage::getConfig()->getNode(sprintf('websites/%s/koan_seg/general/seg_website_id', $website->getCode()));
            if ($value and count($value) > 0) {
                $result[] = array('value' => $website->getId(), 'label' => $website->getName() . ' - ' . $value[0]);
            }
        }

        return $result;

    }

}