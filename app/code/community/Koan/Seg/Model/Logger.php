<?php

Mage::helper('koan_seg')->includeRollBar();

class Koan_Seg_Model_Logger
{
    public function log($action, $level = 'error', $data)
    {
        $data['site_id'] = Mage::helper('koan_seg')->getWebsiteId();
        Rollbar::report_message($action, $level, $data);
    }

}