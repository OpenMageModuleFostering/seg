<?php

class Koan_Seg_Model_Batch_Status extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('koan_seg/batch_status');
    }

    public function createNew($websiteId, $entityType = null, $filter = null)
    {
        $data = array(
            'entity_type' => $entityType,
            'start_time' => null,
            'end_time' => null,
            'total_row_count' => 0,
            'num_rows_processed' => 0,
            'current_status' => 0,
            'comment' => 'Waiting for start ...',
            'num_retried' => 0,
            'website_id' => $websiteId
        );

        if ($filter) {
            $data['filter'] = $filter;
        }

        $this->setData(
            $data
        );

        try {
            $this->save();
        } Catch (Exception $e) {
            throw $e;
        }


        return $this;
    }

    public function setStartingStatus($rowCount, $currentStatus)
    {
        $this->addData(
            array(
                'start_time' => Varien_Date::now(),
                'total_row_count' => $rowCount,
                'current_status' => $currentStatus,
                'comment' => 'Starting new export ...',
            )
        );

        try {
            $this->save();
        } Catch (Exception $e) {
            throw $e;
        }

        return $this;
    }

    public function setProcessingStatus($currentStatus)
    {
        $this->addData(
            array(
                'current_status' => $currentStatus,
                'comment' => 'Processing rows ...',
            )
        );

        try {
            $this->save();
        } Catch (Exception $e) {
            throw $e;
        }

        return $this;
    }

    public function setBatchError($message)
    {
        $numRetried = is_null($this->getNumRetried()) ? 0 : $this->getNumRetried();
        $numRetried++;

        $data = array(
            'num_retried' => $numRetried,
            'comment' => $message,
            'current_status' => Koan_Seg_Model_Seg_Exporter::BATCH_STATUS_ERROR,
        );

        $this->addData(
            $data
        );

        try {
            $this->save();
        } Catch (Exception $e) {
            throw $e;
        }
    }

    public function setBatchRetryError($message)
    {
        $numRetried = is_null($this->getNumRetried()) ? 0 : $this->getNumRetried();
        $numRetried++;

        $data = array(
            'num_retried' => $numRetried,
            'comment' => $message,
            'current_status' => Koan_Seg_Model_Seg_Exporter::BATCH_STATUS_NEED_RETRY,
        );

        $this->addData(
            $data
        );

        try {
            $this->save();
        } Catch (Exception $e) {
            throw $e;
        }
    }

    public function setCompleteStatus($currentStatus)
    {
        $this->addData(
            array(
                'current_status' => $currentStatus,
                'comment' => 'Processing complete.',
                'end_time' => Varien_Date::now(),
            )
        );

        try {
            $this->save();
        } Catch (Exception $e) {
            throw $e;
        }

        return $this;
    }

    public function updateNumRowsProcessed($rowCount = null, $numRowsProcessed)
    {
        $newNumProcessed = (int)$numRowsProcessed + (int)$this->getNumRowsProcessed();

        $data = array(
            'num_rows_processed' => $newNumProcessed,
        );

        if ($rowCount) {
            $data['total_row_count'] = $rowCount;
        }

        $this->addData($data);

        try {
            $this->save();
        } Catch (Exception $e) {
            throw $e;
        }
    }

}