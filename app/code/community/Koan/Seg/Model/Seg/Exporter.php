<?php

class Koan_Seg_Model_Seg_Exporter
{
    const BATCH_STATUS_NOT_STARTED = 0;
    const BATCH_STATUS_STARTING = 1;
    const BATCH_STATUS_PROCESSING_ROWS = 2;

    const BATCH_STATUS_COMPLETE = 5;
    const BATCH_STATUS_ERROR = 6;

    const BATCH_ENTITY_TYPE_HISTORY_ORDERS = 'history_orders';
    const BATCH_ENTITY_TYPE_CUSTOMERS = 'customers';

    public static $_exportHistoryOrdersGenerateCron = false;
    public static $_exportHistoryOrdersCron = false;

    public static $_exportCustomersGenerateCron = false;
    public static $_exportCustomersCron = false;

    private $_historyOrdersCollection = null;

    private $_customersCollection = null;

    /**************************************** CUSTOMERS ******************************************/

    //Prepare new customers batch to export
    public function generateCustomersExportBatch()
    {
        if (self::$_exportCustomersGenerateCron == true) {
            return $this;
        }

        $this->_getHelper()->initRollbar();

        try {
            $this->_exportCustomers(true);
        } Catch (Exception $e) {
            Mage::getSingleton('koan_seg/exception_handler')->handle('Magento: error in generateCustomersExportBatch', $e);
        }

        self::$_exportCustomersGenerateCron = true;
        return $this;
    }

    //Export prepared batches of customers (if any)
    public function exportCustomers()
    {
        if (self::$_exportCustomersCron == true) {
            return $this;
        }

        $this->_getHelper()->initRollbar();

        try {

            if ($limit = $this->_getHelper()->getExporterPhpMemoryLimit()) {
                if (!empty($limit)) {
                    ini_set('memory_limit', $limit);
                }
            }

            $this->_exportCustomers();
        } Catch (Exception $e) {
            Mage::getSingleton('koan_seg/exception_handler')->handle('Magento: error in batch exporter - exportCustomers', $e);
        }

        self::$_exportCustomersCron = true;
        return $this;
    }

    private function _exportCustomers($createNew = false)
    {
        if ($createNew == true) {
            Mage::getModel('koan_seg/batch_status')->createNew(self::BATCH_ENTITY_TYPE_CUSTOMERS);
            return $this;
        }

        $batchRows = $this->_getBatchRows(self::BATCH_ENTITY_TYPE_CUSTOMERS);
        //We can use count here as there should be only few rows
        if (!$numRows = count($batchRows)) {
            return $this;
        }

        foreach ($batchRows as $batch) {

            try {
                $currentStatus = $batch->getCurrentStatus();

                switch ($currentStatus) {
                    case self::BATCH_STATUS_NOT_STARTED:
                        $totalItemsCount = $this->_getCustomersCollectionSize();
                        $batch->setStartingStatus($totalItemsCount, self::BATCH_STATUS_STARTING);
                    case self::BATCH_STATUS_STARTING:
                        $batch->setProcessingStatus(self::BATCH_STATUS_PROCESSING_ROWS);
                    case self::BATCH_STATUS_PROCESSING_ROWS:
                        $this->_processExportCustomers($batch);
                        $batch->setCompleteStatus(self::BATCH_STATUS_COMPLETE);
                }

            } Catch (Exception $e) {
                $batch->setBatchError($e->getMessage());
                throw $e;
            }

        }

        return $this;
    }

    public function _getCustomersCollectionSize()
    {
        $collection = $this->_getCustomersCollection();
        return $collection->getSize();
    }

    public function _getCustomersCollection()
    {
        if (!$this->_customersCollection) {
            $collection = Mage::getResourceModel('customer/customer_collection')
                ->addAttributeToSelect('*')
                ->joinAttribute('billing_country_id', 'customer_address/country_id', 'default_billing', null, 'left')
                ->joinAttribute('shipping_country_id', 'customer_address/country_id', 'default_shipping', null, 'left');
            //->load();

            $this->_customersCollection = $collection;
        }

        return $this->_customersCollection;
    }

    private function _processExportCustomers($batch)
    {
        $numProcTotal = is_null($batch->getNumRowsProcessed()) ? 0 : intval($batch->getNumRowsProcessed());
        $totalRows = is_null($batch->getTotalRowCount()) ? 0 : intval($batch->getTotalRowCount());

        if ($numProcTotal < $totalRows) {

            $break = false;

            do {

                $collection = clone $this->_getCustomersCollection();
                $collection->setOrder('entity_id', 'ASC');
                $collection->getSelect()->limit($this->_getExportCustomersPageSize(), $numProcTotal);
                $collection->load();

                $numProcessed = 0;

                $customers = array();
                foreach ($collection as $customer) {

                    $customers[] = Mage::getModel('koan_seg/seg_customer')->prepare($customer);
                    $numProcessed++;
                }

                Mage::getModel('koan_seg/seg_client')->exportCustomers($customers);

                unset($customers);
                $customers = array();

                $numProcTotal += $numProcessed;
                $batch->updateNumRowsProcessed(null, $numProcessed);

                if ($numProcTotal >= $totalRows) {
                    //Update total rows
                    $batch->updateNumRowsProcessed($numProcTotal, 0);
                    $break = true;
                }

            } while (!$break);

        }

    }

    private function _getExportCustomersPageSize()
    {
        return Mage::helper('koan_seg')->getCustomersExportBatchSize();
    }

    /**************************************** END CUSTOMERS ******************************************/

    /**************************************** ORDERS ******************************************/

    //Prepare new history orders batch to export
    public function generateHistoryOrdersExportBatch($orderDateFilter = null)
    {
        if (self::$_exportHistoryOrdersGenerateCron == true) {
            return $this;
        }

        $this->_getHelper()->initRollbar();

        try {
            $this->_exportHistoryOrders(true, $orderDateFilter);
        } Catch (Exception $e) {
            Mage::getSingleton('koan_seg/exception_handler')->handle('Magento: error in generateHistoryOrdersExportBatch', $e);
        }

        self::$_exportHistoryOrdersGenerateCron = true;
        return $this;
    }

    //Export prepared batches of history orders (if any)
    public function exportHistoryOrders()
    {
        if (self::$_exportHistoryOrdersCron == true) {
            return $this;
        }

        $this->_getHelper()->initRollbar();

        try {

            if ($limit = $this->_getHelper()->getExporterPhpMemoryLimit()) {
                if (!empty($limit)) {
                    ini_set('memory_limit', $limit);
                }
            }

            $this->_exportHistoryOrders();
        } Catch (Exception $e) {
            Mage::getSingleton('koan_seg/exception_handler')->handle('Magento: error in batch exporter - exportHistoryOrders', $e);
        }

        self::$_exportHistoryOrdersCron = true;
        return $this;
    }

    private function _exportHistoryOrders($createNew = false, $orderDateFilter = null)
    {
        if ($createNew == true) {
            Mage::getModel('koan_seg/batch_status')->createNew(self::BATCH_ENTITY_TYPE_HISTORY_ORDERS, $orderDateFilter);
            return $this;
        }

        $batchRows = $this->_getBatchRows(self::BATCH_ENTITY_TYPE_HISTORY_ORDERS);
        //We can use count here as there should be only few rows
        if (!$numRows = count($batchRows)) {
            return $this;
        }

        foreach ($batchRows as $batch) {
            try {
                $currentStatus = $batch->getCurrentStatus();

                switch ($currentStatus) {
                    case self::BATCH_STATUS_NOT_STARTED:
                        $totalItemsCount = $this->_getHistoryOrdersCollectionSize($batch);
                        $batch->setStartingStatus($totalItemsCount, self::BATCH_STATUS_STARTING);
                    case self::BATCH_STATUS_STARTING:
                        $batch->setProcessingStatus(self::BATCH_STATUS_PROCESSING_ROWS);
                    case self::BATCH_STATUS_PROCESSING_ROWS:
                        $this->_processExportHistoryOrders($batch);
                        $batch->setCompleteStatus(self::BATCH_STATUS_COMPLETE);
                }

            } Catch (Exception $e) {
                $batch->setBatchError($e->getMessage());
                throw $e;
            }

        }

        return $this;
    }

    private function _processExportHistoryOrders($batch)
    {
        $numProcTotal = is_null($batch->getNumRowsProcessed()) ? 0 : intval($batch->getNumRowsProcessed());
        $totalRows = is_null($batch->getTotalRowCount()) ? 0 : intval($batch->getTotalRowCount());

        if ($numProcTotal < $totalRows) {

            $break = false;

            do {

                $collection = clone $this->_getHistoryOrdersCollection($batch);
                $collection->setOrder('entity_id', 'ASC');
                $collection->getSelect()->limit($this->_getExportHistoryPageSize(), $numProcTotal);
                $collection->load();

                $numProcessed = 0;

                $orders = array();
                foreach ($collection as $order) {

                    //TODO: Handle collection export
                    $orders[] = Mage::getModel('koan_seg/seg_order')->prepare($order);
                    $numProcessed++;
                }

                Mage::getModel('koan_seg/seg_client')->exportHistoryOrders($orders);

                unset($orders);
                $orders = array();

                $numProcTotal += $numProcessed;
                $batch->updateNumRowsProcessed(null, $numProcessed);

                if ($numProcTotal >= $totalRows) {
                    $batch->updateNumRowsProcessed($numProcTotal, 0);
                    $break = true;
                }

            } while (!$break);

        }

    }

    private function _getExportHistoryPageSize()
    {
        return Mage::helper('koan_seg')->getOrdersExportBatchSize();
    }

    public function hasCustomersToProcess()
    {
        $rows = $this->_getBatchRows(self::BATCH_ENTITY_TYPE_CUSTOMERS);
        if ($rows AND count($rows)) {
            return true;
        }

        return false;
    }

    public function hasOrdersToProcess()
    {
        $rows = $this->_getBatchRows(self::BATCH_ENTITY_TYPE_HISTORY_ORDERS);
        if ($rows AND count($rows)) {
            return true;
        }

        return false;
    }

    private function _getBatchRows($entityType)
    {
        $allowedStages = array(
            self::BATCH_STATUS_NOT_STARTED,
            self::BATCH_STATUS_STARTING,
            self::BATCH_STATUS_PROCESSING_ROWS
        );

        $collection = Mage::getResourceModel('koan_seg/batch_status_collection');
        $collection->addFieldToFilter('current_status', array('in' => $allowedStages));
        $collection->addFieldToFilter('entity_type', $entityType);
        $collection->setOrder('current_status', 'ASC');

        return $collection;
    }

    public function _getHistoryOrdersCollectionSize($batch = null)
    {
        return $this->_getHistoryOrdersCollection($batch)->getSize();
    }

    public function _getHistoryOrdersCollection($batch = null)
    {
        if (!$this->_historyOrdersCollection) {
            $collection = Mage::getResourceModel('sales/order_collection');
            $collection->addFieldToFilter('state', 'complete');

            if ($batch AND $filter = $batch->getFilter()) {
                $collection->addFieldToFilter('created_at', array('from' => $filter));
            }

            $this->_historyOrdersCollection = $collection;
        }

        return $this->_historyOrdersCollection;
    }

    /**************************************** END ORDERS ******************************************/


    public function _getHelper()
    {
        return Mage::helper('koan_seg');
    }

}