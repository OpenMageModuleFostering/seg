<?php

class Koan_Seg_Model_Resource_Batch_Log extends Mage_Core_Model_Resource_Db_Abstract
{
    public function _construct()
    {
        $this->_init('koan_seg/batch_log', 'id');
    }
}