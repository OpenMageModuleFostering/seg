<?php

/**
 * @category Koan
 * @package Seg
 * @author Seg <hello@getseg.com, http://getseg.com>
 * @copyright Seg <http://getseg.com>
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Koan_Seg_Block_Header extends Mage_Core_Block_Template
{
    protected function _beforeToHtml()
    {
        $currentStore = Mage::app()->getStore()->getId();

        $websiteId = $this->helper('koan_seg')->getWebsiteId($currentStore);
        if (empty($websiteId)) {
            $this->_template = null;
        }
        return $this;
    }

}