<?php

Mage::helper('koan_seg')->includeRollBar();

class Koan_Seg_Model_Seg_Client extends Varien_Http_Client
{
    const REQUEST_TIMEOUT = 30;

    public function __construct()
    {
        //$this->_getHelper()->initRollbar();

        $this->config['useragent'] = 'Koan_Seg_Model_Seg_Client';
        parent::__construct();
    }

    public function exportCustomers($customers)
    {
        if (!$customers) {
            Mage::throwException($this->__('Customers data does not exists!'));
        }

        if (!is_array($customers) OR !count($customers)) {
            Mage::throwException($this->__('Invalid customers data!'));
        }

        $params = json_encode($customers);

        $url = $this->_getExportCustomerUrl();

        $this->_init();
        $this->setUri($url);

        $this->setRawData($params, 'application/json');

        if ($this->_getHelper()->logRequestInfo()) {
            Mage::getSingleton('koan_seg/logger')->log('Magento: posting customers to Seg', 'info', array('customers' => $params, 'endpoint' => $url));
        }

        try {
            $response = $this->request();
        } Catch (Exception $e) {
            throw $e;
        }

        try {
            $this->__handleResponse($response, 'exportCustomers');
        } Catch (Exception $e) {
            Mage::throwException('[exportCustomers]::__handleResponse: ' . $e->getMessage());
        }

        return $this;
    }

    public function exportHistoryOrders($orders)
    {
        if (!$orders) {
            Mage::throwException($this->__('Order data does not exists!'));
        }

        if (!is_array($orders) OR !count($orders)) {
            Mage::throwException($this->__('Invalid order data!'));
        }

        $params = json_encode($orders);

        $url = $this->_getExportOrderHistoryUrl();

        $this->_init();
        $this->setUri($url);

        $this->setRawData($params, 'application/json');

        if ($this->_getHelper()->logRequestInfo()) {

            $batchSize = 20;
            $total = count($orders);

            $numBatches = floor(floatval($total) / floatval($batchSize)) + (floatval($total) % floatval($batchSize));
            $batchCounter = 0;

            $batch = array();

            $counter = 0;
            foreach ($orders as $order) {
                $counter++;

                if ($counter >= $total) {
                    $batchCounter++;
                    $logData = json_encode($batch);
                    Mage::getSingleton('koan_seg/logger')->log(
                        'Magento: posting history orders to Seg', 'info', array('orders' => $logData, 'endpoint' => $url, 'batch' => $batchCounter, 'batches' => $numBatches));

                    break;
                }

                if (count($batch) < $batchSize) {
                    $batch[] = $order;
                } else {
                    $batchCounter++;
                    $logData = json_encode($batch);

                    Mage::getSingleton('koan_seg/logger')->log(
                        'Magento: posting history orders to Seg', 'info', array('orders' => $logData, 'endpoint' => $url, 'batch' => $batchCounter, 'batches' => $numBatches));

                    $batch = array();
                }

            }

        }

        try {
            $response = $this->request();
        } Catch (Exception $e) {
            throw $e;
        }

        try {
            $this->__handleResponse($response, 'posting history orders to Seg');
        } Catch (Exception $e) {
            Mage::throwException('[exportHistoryOrders]::__handleResponse: ' . $e->getMessage());
        }

        return $this;
    }

    public function exportNewOrder($orderData)
    {
        if (!$orderData) {
            Mage::throwException($this->__('Order data does not exists!'));
        }

        if (!is_array($orderData)) {
            Mage::throwException($this->__('Invalid order data!'));
        }

        $params = json_encode($orderData);

        $url = $this->_getOrderPlacedUrl();

        $this->_init();
        $this->setUri($url);

        $this->setRawData($params, 'application/json');

        if ($this->_getHelper()->logRequestInfo()) {
            Mage::getSingleton('koan_seg/logger')->log('Magento: posting new order to Seg', 'info', array('order' => $params, 'endpoint' => $url));
        }

        try {
            $response = $this->request();
        } Catch (Exception $e) {
            throw $e;
        }

        try {
            $this->__handleResponse($response, 'posting new order to Seg');
        } Catch (Exception $e) {
            Mage::throwException('[exportHistoryOrders]::__handleResponse: ' . $e->getMessage());
        }

        return $this;
    }

    private function __handleResponse($response, $msg = '')
    {
        /** @var Zend_Http_Response $response */
        if ($response->isSuccessful()) {
            if ($this->_getHelper()->logRequestInfo()) {
                Mage::getSingleton('koan_seg/logger')->log(sprintf('Magento: %s - response from Seg', $msg), 'info',
                    array('response' => 'ResponseOK: ' . $response->getBody()));
            }
            return true;
        }

        if ($response->isError()) {
            $code = $response->getStatus();
            $message = $response->getMessage();
            Mage::getSingleton('koan_seg/exception_handler')->handleHttpResponseError(sprintf('Magento: error in \'%s\' HTTP request: %s', $msg, $message), $code, $message);
        }

        return false;
    }

    private function _init()
    {
        $this->setConfig($this->_getHttpConfig());
        $this->setMethod(Zend_Http_Client::POST);

        if ($headers = $this->_getHttpHeaders()) {
            $this->setHeaders($headers);
        }
        return $this;
    }

    private function _getExportCustomerUrl()
    {
        return sprintf($this->_getHelper()->getUpdateCustomersUrl(), $this->_getHelper()->getWebsiteId());
    }

    private function _getExportOrderHistoryUrl()
    {
        return sprintf($this->_getHelper()->getOrderHistoryUrl(), $this->_getHelper()->getWebsiteId());
    }

    private function _getOrderPlacedUrl()
    {
        return sprintf($this->_getHelper()->getOrderPlacedUrl(), $this->_getHelper()->getWebsiteId());
    }

    private function _getHttpHeaders()
    {
        return array('accept' => 'application/json');
    }

    private function _getHttpConfig()
    {
        return array(
            'timeout' => $this->_getTimeout(),
            //'proxy' => '127.0.0.1:8888' //For development purposes only
        );
    }

    private function _getTimeout()
    {
        return self::REQUEST_TIMEOUT;
    }

    private function _getHelper()
    {
        return Mage::helper('koan_seg');
    }

    private function __($text)
    {
        return $this->_getHelper()->__($text);
    }
}