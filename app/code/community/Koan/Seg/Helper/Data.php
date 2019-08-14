<?php

class Koan_Seg_Helper_Data extends Mage_Core_Helper_Abstract
{
    const SEG_WEBSITE_ID_PATH = 'koan_seg/general/seg_website_id';

    const SEG_ORDER_HISTORY_ENDPOINT_URL_PATH = 'koan_seg/endpoint/order_history';
    const SEG_UPDATE_CUSTOMERS_ENDPOINT_URL_PATH = 'koan_seg/endpoint/update_customers';
    const SEG_ORDER_PLACED_ENDPOINT_URL_PATH = 'koan_seg/endpoint/order_placed';

    const SEG_ORDERS_EXPORT_BATCH_SIZE_PATH = 'koan_seg/general/orders_export_batch_size';
    const SEG_CUSTOMERS_EXPORT_BATCH_SIZE_PATH = 'koan_seg/general/customers_export_batch_size';

    const SEG_PRODUCT_BRAND_ATTRIBUTE_CODE_PATH = 'koan_seg/general/brand_attr_code';
    const SEG_ROLLBAR_LOG_REQUEST_INFO = 'koan_seg/general/rollbar_report_params';

    const SEG_EXPORT_CRON_ENABLED = 'koan_seg/general/export_cron_enable';

    const SEG_EXPORTER_PHP_MEMORY_LIMIT = 'koan_seg/advanced/php_memory_limit';

    public function getExporterPhpMemoryLimit()
    {
        return Mage::getStoreConfig(self::SEG_EXPORTER_PHP_MEMORY_LIMIT, null);
    }

    public function isExportCronEnabled()
    {
        return Mage::getStoreConfigFlag(self::SEG_EXPORT_CRON_ENABLED, null);
    }

    public function getWebsiteId($storeId = null)
    {
        return Mage::getStoreConfig(self::SEG_WEBSITE_ID_PATH, $storeId);
    }

    public function getOrderHistoryUrl()
    {
        return Mage::getStoreConfig(self::SEG_ORDER_HISTORY_ENDPOINT_URL_PATH);
    }

    public function getUpdateCustomersUrl()
    {
        return Mage::getStoreConfig(self::SEG_UPDATE_CUSTOMERS_ENDPOINT_URL_PATH);
    }

    public function getOrderPlacedUrl()
    {
        return Mage::getStoreConfig(self::SEG_ORDER_PLACED_ENDPOINT_URL_PATH);
    }

    public function getOrdersExportBatchSize()
    {
        return Mage::getStoreConfig(self::SEG_ORDERS_EXPORT_BATCH_SIZE_PATH);
    }

    public function getCustomersExportBatchSize()
    {
        return Mage::getStoreConfig(self::SEG_CUSTOMERS_EXPORT_BATCH_SIZE_PATH);
    }

    public function getBrandAttributeCode($storeId = null)
    {
        return Mage::getStoreConfig(self::SEG_PRODUCT_BRAND_ATTRIBUTE_CODE_PATH, $storeId);
    }

    public function logRequestInfo($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::SEG_ROLLBAR_LOG_REQUEST_INFO, $storeId);
    }

    public function includeRollBar()
    {
        if (strpos(get_include_path(), Mage::getModuleDir(null, 'Koan_Seg')) == false) {
            set_include_path(get_include_path() . PATH_SEPARATOR . Mage::getModuleDir(null, 'Koan_Seg') . DS . 'lib');
        }

        require_once('Rollbar.php');
    }

    public function initRollbar()
    {
        $this->includeRollBar();

        if (!Rollbar::$instance) {
            Rollbar::init(array(

                'access_token' => '39591143a2524b08b29a18a653897f95',
                'environment' => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB),
                'root' => Mage::getBaseDir(),
                'batched' => 0,

            ));
        }
    }

    public function getProductBrands($product, $storeId)
    {
        $brandAttributeCode = $this->getBrandAttributeCode($storeId);
        $brandAttributeType = null;

        if ($brandAttributeCode) {
            $brandAttributeType = $this->_getBrandAttributeType($brandAttributeCode);
        }

        //Try to determine product brand
        if (is_null($brandAttributeType)) {
            $brandAttributeType = 'text';
        }

        $getBrand = 'get' . $this->_camelize($brandAttributeCode);

        if ($brandAttributeType == 'select') {

            $resultBrand = null;
            $brandValue = $product->$getBrand();

            if (!empty($brandValue)) {
                $product->setStoreId($storeId)
                    ->setData(
                        $brandAttributeCode,
                        $brandValue
                    );
                $resultBrand = $product->getAttributeText($brandAttributeCode);
            }

        } else if ($brandAttributeType == 'multiselect') {

            $productBrandIds = null;
            $resultBrand = array();

            $brandValueList = $product->$getBrand();
            if ($brandValueList) {
                $productBrandValsArray = explode(',', $brandValueList);
                if ($productBrandValsArray AND is_array($productBrandValsArray) AND count($productBrandValsArray)) {
                    foreach ($productBrandValsArray as $brandValue) {
                        if (!empty($brandValue)) {
                            $product->setStoreId($storeId)
                                ->setData(
                                    $brandAttributeCode,
                                    $brandValue
                                );
                            $resultBrand[] = $product->getAttributeText($brandAttributeCode);
                        }
                    }
                }
            }

        } else {
            $resultBrand = $product->$getBrand();
        }

        $brand = $resultBrand ? $resultBrand : null;

        if (!$brand) {
            return null;
        }

        if (!is_array($brand)) {
            $brand = array($brand);
        }

        return $brand;
    }

    private function _getBrandAttributeType($brandAttributeCode)
    {
        $attribute = Mage::getModel('eav/entity_attribute')->loadByCode('catalog_product', $brandAttributeCode);
        if ($attribute) {
            return $attribute->getFrontendInput();
        }

        return 'text';
    }

    protected function _camelize($name)
    {
        return uc_words($name, '');
    }
}