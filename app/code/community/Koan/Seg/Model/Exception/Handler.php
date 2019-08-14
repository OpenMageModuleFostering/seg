<?php

Mage::helper('koan_seg')->includeRollBar();

class Koan_Seg_Model_Exception_Handler
{
    public function handle($action, Exception $e)
    {
        $data = array(
            'code' => $e->getCode(),
            'message' => $e->getMessage(),
        );

        Mage::getSingleton('koan_seg/logger')->log($action, 'error', $data);
    }

    public function handleHttpResponseError($action, $code, $message)
    {
        $data = array(
            'code' => $code,
            'message' => $message,
        );

        Mage::getSingleton('koan_seg/logger')->log($action, 'error', $data);
    }
}