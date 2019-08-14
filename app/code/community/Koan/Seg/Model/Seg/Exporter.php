<?php

class Koan_Seg_Model_Seg_Exporter
{
    const BATCH_STATUS_NOT_STARTED = 0;
    const BATCH_STATUS_STARTING = 1;
    const BATCH_STATUS_PROCESSING_ROWS = 2;

    const BATCH_STATUS_COMPLETE = 5;
    const BATCH_STATUS_ERROR = 6;

    const BATCH_STATUS_NEED_RETRY = 7;

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
    public function generateCustomersExportBatch($websiteId)
    {
        if (self::$_exportCustomersGenerateCron == true) {
            return $this;
        }

        $this->_getHelper()->initRollbar();

        try {
            $this->_exportCustomers(true, $websiteId);
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

    private function _exportCustomers($createNew = false, $websiteId = null)
    {
        if ($createNew == true) {
            Mage::getModel('koan_seg/batch_status')->createNew($websiteId, self::BATCH_ENTITY_TYPE_CUSTOMERS);
            return $this;
        }

        $batchRows = $this->_getBatchRows(self::BATCH_ENTITY_TYPE_CUSTOMERS);
        //We can use count here as there should be only few rows
        if (!$numRows = count($batchRows)) {
            return $this;
        }

        foreach ($batchRows as $batch) {

            try {

                //Reset collection:
                $this->_customersCollection = null;

                $currentStatus = $batch->getCurrentStatus();
                $websiteId = $batch->getWebsiteId();

                switch ($currentStatus) {
                    case self::BATCH_STATUS_NOT_STARTED:
                        $totalItemsCount = $this->_getCustomersCollectionSize($websiteId);
                        $batch->setStartingStatus($totalItemsCount, self::BATCH_STATUS_STARTING);
                    case self::BATCH_STATUS_STARTING:
                        $batch->setProcessingStatus(self::BATCH_STATUS_PROCESSING_ROWS);
                    case self::BATCH_STATUS_PROCESSING_ROWS:
                        $errors = $this->_processExportCustomers($batch);
                        if (count($errors)) {
                            $batch->setBatchRetryError('Some errors occured during the batch export. Pending retry.');
                        } else {
                            $batch->setCompleteStatus(self::BATCH_STATUS_COMPLETE);
                        }
                        break;
                    case self::BATCH_STATUS_NEED_RETRY:
                        $this->_retryExportCustomers($batch);
                        break;
                }

            } Catch (Exception $e) {
                $msg = $batch->setBatchError($e->getMessage());
                if ($msg) {
                    $e->setMessage($msg);
                }
                throw $e;
            }

        }

        return $this;
    }

    public function _getCustomersCollectionSize($websiteId)
    {
        $collection = $this->_getCustomersCollection($websiteId);
        return $collection->getSize();
    }

    public function _getCustomersCollection($websiteId)
    {
        if (!$this->_customersCollection) {
            $collection = Mage::getResourceModel('customer/customer_collection')
                ->addAttributeToSelect('*')
                ->joinAttribute('billing_country_id', 'customer_address/country_id', 'default_billing', null, 'left')
                ->joinAttribute('shipping_country_id', 'customer_address/country_id', 'default_shipping', null, 'left');

            if (Mage::getModel('customer/config_share')->isWebsiteScope()) {
                $collection->addFieldToFilter('website_id', $websiteId);
            }

            $this->_customersCollection = $collection;
        }

        return $this->_customersCollection;
    }

    private function _processExportCustomers($batch)
    {
        $errors = array();

        $numProcTotal = is_null($batch->getNumRowsProcessed()) ? 0 : intval($batch->getNumRowsProcessed());
        $totalRows = is_null($batch->getTotalRowCount()) ? 0 : intval($batch->getTotalRowCount());

        $websiteId = $batch->getWebsiteId();

        if ($numProcTotal < $totalRows) {

            $break = false;

            do {

                $collection = clone $this->_getCustomersCollection($websiteId);
                $collection->setOrder('entity_id', 'ASC');
                $collection->getSelect()->limit($this->_getExportCustomersPageSize(), $numProcTotal);
                $collection->load();

                $numProcessed = 0;

                try {

                    $customers = array();
                    foreach ($collection as $customer) {

                        $customers[] = Mage::getModel('koan_seg/seg_customer')->prepare($customer);
                        $numProcessed++;
                    }

                    $storeId = Mage::app()
                        ->getWebsite($websiteId)
                        ->getDefaultGroup()
                        ->getDefaultStoreId();

                    Mage::getModel('koan_seg/seg_client')->exportCustomers($customers, $storeId);

                    unset($customers);
                    $customers = array();


                } Catch (Exception $e) {

                    $msg = sprintf('exportCustomers - Payload %s, %s - %s', $numProcTotal, $this->_getExportCustomersPageSize(), $e->getMessage());
                    $errors[] = $msg;

                    $data = array(
                        'batch_id' => $batch->getId(),
                        'start' => $numProcTotal,
                        'limit' => $this->_getExportCustomersPageSize(),
                        'comment' => $e->getMessage(),
                        'num_retried' => 0
                    );

                    $log = Mage::getModel('koan_seg/batch_log');
                    $log->setData($data);
                    $log->save();

                    Mage::getSingleton('koan_seg/exception_handler')->handle(sprintf('Magento: error in batch exporter - exportCustomers - Payload %s, %s', $numProcTotal, $this->_getExportCustomersPageSize()), new Koan_Seg_Model_Singlepayload_Exception($e->getMessage()));

                }

                $numProcTotal += $numProcessed;
                $batch->updateNumRowsProcessed(null, $numProcessed);

                if ($numProcTotal >= $totalRows) {
                    //Update total rows
                    $batch->updateNumRowsProcessed($numProcTotal, 0);
                    $break = true;
                }

            } while (!$break);

        }

        return $errors;
    }

    private function _retryExportCustomers($batch)
    {
        $retryCollection = Mage::getModel('koan_seg/batch_log')->getCollection();
        $retryCollection->addFieldToFilter('batch_id', $batch->getId());
        $retryCollection->addFieldToFilter('num_retried', array('lt' => 3));

        $cnt = $retryCollection->getSize();
        if (!$cnt) {
            return;
        }

        $websiteId = $batch->getWebsiteId();

        foreach ($retryCollection as $retry) {

            $collection = clone $this->_getCustomersCollection($batch);
            $collection->setOrder('entity_id', 'ASC');
            $collection->getSelect()->limit($retry->getLimit(), $retry->getStart());
            $collection->load();

            try {

                $customers = array();
                foreach ($collection as $customer) {
                    $customers[] = Mage::getModel('koan_seg/seg_customer')->prepare($customer);
                }

                $storeId = Mage::app()
                    ->getWebsite($websiteId)
                    ->getDefaultGroup()
                    ->getDefaultStoreId();

                Mage::getModel('koan_seg/seg_client')->exportCustomers($customers, $storeId);

                unset($customers);
                $customers = array();

            } Catch (Exception $e) {

                $numRetried = $retry->getNumRetried();
                $numRetried = empty($numRetried) ? 0 : $numRetried;

                $retry->setNumRetried($numRetried + 1);
                $retry->setComment($retry->getComment() . PHP_EOL . $e->getMessage());
                $retry->save();

                Mage::getSingleton('koan_seg/exception_handler')->handle(sprintf('Magento: error in batch exporter - exportCustomers - Payload %s, %s', $retry->getStart(), $retry->getLimit()), new Koan_Seg_Model_Singlepayload_Exception($e->getMessage()));

            }

        }

        //Check if there are any retries???

        $retryCollection = Mage::getModel('koan_seg/batch_log')->getCollection();
        $retryCollection->addFieldToFilter('batch_id', $batch->getId());
        $retryCollection->addFieldToFilter('num_retried', array('lt' => 3));

        $cnt = $retryCollection->getSize();
        if (!$cnt) {
            Mage::throwException('ERROR: Exhausted num retries!');
        }

    }

    private function _getExportCustomersPageSize()
    {
        return Mage::helper('koan_seg')->getCustomersExportBatchSize();
    }

    /**************************************** END CUSTOMERS ******************************************/

    /**************************************** ORDERS ******************************************/

    //Prepare new history orders batch to export
    public function generateHistoryOrdersExportBatch($websiteId, $orderDateFilter = null)
    {
        if (self::$_exportHistoryOrdersGenerateCron == true) {
            return $this;
        }

        $this->_getHelper()->initRollbar();

        try {
            $this->_exportHistoryOrders(true, $orderDateFilter, $websiteId);
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

    private function _exportHistoryOrders($createNew = false, $orderDateFilter = null, $websiteId = null)
    {
        if ($createNew == true) {
            Mage::getModel('koan_seg/batch_status')->createNew($websiteId, self::BATCH_ENTITY_TYPE_HISTORY_ORDERS, $orderDateFilter);
            return $this;
        }

        $batchRows = $this->_getBatchRows(self::BATCH_ENTITY_TYPE_HISTORY_ORDERS);
        //We can use count here as there should be only few rows
        if (!$numRows = count($batchRows)) {
            return $this;
        }

        foreach ($batchRows as $batch) {

            $this->_historyOrdersCollection = null;

            try {
                $currentStatus = $batch->getCurrentStatus();

                switch ($currentStatus) {
                    case self::BATCH_STATUS_NOT_STARTED:
                        $totalItemsCount = $this->_getHistoryOrdersCollectionSize($batch);
                        $batch->setStartingStatus($totalItemsCount, self::BATCH_STATUS_STARTING);
                    case self::BATCH_STATUS_STARTING:
                        $batch->setProcessingStatus(self::BATCH_STATUS_PROCESSING_ROWS);
                    case self::BATCH_STATUS_PROCESSING_ROWS:
                        $errors = $this->_processExportHistoryOrders($batch);
                        if (count($errors)) {
                            $batch->setBatchRetryError('Some errors occured during the batch export. Pending retry.');
                        } else {
                            $batch->setCompleteStatus(self::BATCH_STATUS_COMPLETE);
                        }
                        break;
                    case self::BATCH_STATUS_NEED_RETRY:
                        $this->_retryExportHistoryOrders($batch);
                        break;
                }

            } Catch (Exception $e) {
                $batch->setBatchError($e->getMessage());
                throw $e;
            }

        }

        return $this;
    }

    private function _retryExportHistoryOrders($batch)
    {
        $retryCollection = Mage::getModel('koan_seg/batch_log')->getCollection();
        $retryCollection->addFieldToFilter('batch_id', $batch->getId());
        $retryCollection->addFieldToFilter('num_retried', array('lt' => 3));

        $cnt = $retryCollection->getSize();
        if (!$cnt) {
            return;
        }

        foreach ($retryCollection as $retry) {

            $collection = clone $this->_getHistoryOrdersCollection($batch);
            $collection->setOrder('entity_id', 'ASC');
            $collection->getSelect()->limit($retry->getLimit(), $retry->getStart());
            $collection->load();

            try {

                $orders = array();
                foreach ($collection as $order) {
                    $orders[] = Mage::getModel('koan_seg/seg_order')->prepare($order);
                }

                $websiteId = $batch->getWebsiteId();
                $storeId = Mage::app()
                    ->getWebsite($websiteId)
                    ->getDefaultGroup()
                    ->getDefaultStoreId();

                Mage::getModel('koan_seg/seg_client')->exportHistoryOrders($orders, $storeId);

                unset($orders);
                $orders = array();

            } Catch (Exception $e) {

                $numRetried = $retry->getNumRetried();
                $numRetried = empty($numRetried) ? 0 : $numRetried;

                $retry->setNumRetried($numRetried + 1);
                $retry->setComment($retry->getComment() . PHP_EOL . $e->getMessage());
                $retry->save();

                Mage::getSingleton('koan_seg/exception_handler')->handle(sprintf('Magento: error in batch exporter - exportHistoryOrders - Payload %s, %s', $retry->getStart(), $retry->getLimit()), new Koan_Seg_Model_Singlepayload_Exception($e->getMessage()));

            }

        }

        //Check if there are any retries???

        $retryCollection = Mage::getModel('koan_seg/batch_log')->getCollection();
        $retryCollection->addFieldToFilter('batch_id', $batch->getId());
        $retryCollection->addFieldToFilter('num_retried', array('lt' => 3));

        $cnt = $retryCollection->getSize();
        if (!$cnt) {
            Mage::throwException('ERROR: Exhausted num retries!');
        }

    }

    private function _processExportHistoryOrders($batch)
    {
        $errors = array();

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

                try {

                    $orders = array();
                    foreach ($collection as $order) {

                        //TODO: Handle collection export
                        $orders[] = Mage::getModel('koan_seg/seg_order')->prepare($order);
                        $numProcessed++;
                    }

                    $websiteId = $batch->getWebsiteId();
                    $storeId = Mage::app()
                        ->getWebsite($websiteId)
                        ->getDefaultGroup()
                        ->getDefaultStoreId();

                    Mage::getModel('koan_seg/seg_client')->exportHistoryOrders($orders, $storeId);

                    unset($orders);
                    $orders = array();

                } Catch (Exception $e) {

                    $msg = sprintf('exportHistoryOrders - Payload %s, %s - %s', $numProcTotal, $this->_getExportHistoryPageSize(), $e->getMessage());
                    $errors[] = $msg;

                    $data = array(
                        'batch_id' => $batch->getId(),
                        'start' => $numProcTotal,
                        'limit' => $this->_getExportHistoryPageSize(),
                        'comment' => $e->getMessage(),
                        'num_retried' => 0
                    );

                    $log = Mage::getModel('koan_seg/batch_log');
                    $log->setData($data);
                    $log->save();

                    Mage::getSingleton('koan_seg/exception_handler')->handle(sprintf('Magento: error in batch exporter - exportHistoryOrders - Payload %s, %s', $numProcTotal, $this->_getExportHistoryPageSize()), new Koan_Seg_Model_Singlepayload_Exception($e->getMessage()));

                }

                $numProcTotal += $numProcessed;
                $batch->updateNumRowsProcessed(null, $numProcessed);

                if ($numProcTotal >= $totalRows) {
                    $batch->updateNumRowsProcessed($numProcTotal, 0);
                    $break = true;
                }

            } while (!$break);

        }

        return $errors;
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
            self::BATCH_STATUS_PROCESSING_ROWS,
            self::BATCH_STATUS_NEED_RETRY,
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
        $websiteId = $batch->getWebsiteId();
        $storeId = Mage::app()
            ->getWebsite($websiteId)
            ->getDefaultGroup()
            ->getDefaultStoreId();

        if (!$this->_historyOrdersCollection) {
            $collection = Mage::getResourceModel('sales/order_collection');

            $allowedStatuses = $this->_getHelper()->getOrdersExportStatuses($storeId);
            if ($allowedStatuses AND is_array($allowedStatuses) AND count($allowedStatuses)) {
                $collection->addFieldToFilter('status', array('in' => $allowedStatuses));
            } else {
                $collection->addFieldToFilter('state', 'complete');
            }

            $storeIds = Mage::app()->getWebsite($websiteId)->getStoreIds();
            $collection->addFieldToFilter('store_id', array('in' => $storeIds));

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