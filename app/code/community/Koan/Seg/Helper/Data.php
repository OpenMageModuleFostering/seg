<?php

class Koan_Seg_Helper_Data extends Mage_Core_Helper_Abstract
{
    const SEG_WEBSITE_ID_PATH = 'koan_seg/general/seg_website_id';

    const SEG_ORDER_HISTORY_ENDPOINT_URL_PATH = 'koan_seg/endpoint/order_history';
    const SEG_UPDATE_CUSTOMERS_ENDPOINT_URL_PATH = 'koan_seg/endpoint/update_customers';
    const SEG_ORDER_PLACED_ENDPOINT_URL_PATH = 'koan_seg/endpoint/order_placed';

    const SEG_ORDERS_EXPORT_STATUSES = 'koan_seg/general/orders_export_statuses';

    const SEG_ORDERS_EXPORT_BATCH_SIZE_PATH = 'koan_seg/general/orders_export_batch_size';
    const SEG_CUSTOMERS_EXPORT_BATCH_SIZE_PATH = 'koan_seg/general/customers_export_batch_size';

//    const SEG_PRODUCT_BRAND_ATTRIBUTE_CODE_PATH = 'koan_seg/general/brand_attr_code';
    const SEG_PRODUCT_TAG_ATTRIBUTE_CODES_PATH = 'koan_seg/general/tag_attr_codes';

    const SEG_ROLLBAR_LOG_REQUEST_INFO = 'koan_seg/general/rollbar_report_params';

    const SEG_EXPORT_CRON_ENABLED = 'koan_seg/general/export_cron_enable';

    const SEG_EXPORTER_PHP_MEMORY_LIMIT = 'koan_seg/advanced/php_memory_limit';
    const SEG_EXPORTER_REQUEST_TIMEOUT = 'koan_seg/advanced/request_timeout';

    public function getOrdersExportStatuses($storeId = null)
    {
        $statuses = Mage::getStoreConfig(self::SEG_ORDERS_EXPORT_STATUSES, $storeId);
        if (empty($statuses)) {
            return null;
        }
        $result = array_map('trim', explode(',', $statuses));
        return $result;
    }

    public function getRequestTimeout($storeId = null)
    {
        $timeOut = Mage::getStoreConfig(self::SEG_EXPORTER_PHP_MEMORY_LIMIT, $storeId);
        if (!$timeOut) {
            $timeOut = 60;
        }
        return $timeOut;
    }

    public function getExporterPhpMemoryLimit($storeId = null)
    {
        return Mage::getStoreConfig(self::SEG_EXPORTER_PHP_MEMORY_LIMIT, $storeId);
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

    public function getTagAttributeCodes($storeId = null)
    {
        return Mage::getStoreConfig(self::SEG_PRODUCT_TAG_ATTRIBUTE_CODES_PATH, $storeId);
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
                //'environment' => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB),
                'environment' => 'production',
                'root' => Mage::getBaseDir(),
                'batched' => 0,

            ));
        }
    }

    public function getProductTags($product, $storeId)
    {
        $tagAttributeCodes = $this->getTagAttributeCodes($storeId);
        $tagAttributeType = null;

        $cache = Mage::app()->getCache();
        $cacheKey = md5($tagAttributeCodes . $product->getId() . $storeId);

        if (Mage::app()->useCache('COLLECTION_DATA')) {
            $result = $cache->load($cacheKey);
            if ($result) {
                return unserialize($result);
            }
        }

        if ($tagAttributeCodes) {
            $tagAttributeCodes = array_map('trim', explode(',', $tagAttributeCodes));
        }

        $tags = array();

        if ($tagAttributeCodes AND is_array($tagAttributeCodes) AND count($tagAttributeCodes)) {

            foreach ($tagAttributeCodes as $attributeCode) {

                $attrType = $this->_getAttributeTypeByCode($attributeCode);
                //Try to determine product brand
                if (is_null($attrType)) {
                    $attrType = 'text';
                }

                $getAttribute = 'get' . $this->_camelize($attributeCode);

                if ($attrType == 'select') {

                    $resultBrand = null;
                    $attrValue = $product->$getAttribute();

                    if (!empty($attrValue)) {
                        $product->setStoreId($storeId)
                            ->setData(
                                $attributeCode,
                                $attrValue
                            );
                        $tags[] = $product->getAttributeText($attributeCode);
                    }
                } else if ($attrType == 'multiselect') {

                    $productAttrIds = null;
                    $attrValueList = $product->$getAttribute();
                    if ($attrValueList) {
                        $productAttrValsArray = explode(',', $attrValueList);
                        if ($productAttrValsArray AND is_array($productAttrValsArray) AND count($productAttrValsArray)) {
                            foreach ($productAttrValsArray as $attrValue) {
                                if (!empty($attrValue)) {
                                    $product->setStoreId($storeId)
                                        ->setData(
                                            $attributeCode,
                                            $attrValue
                                        );
                                    $tags[] = $product->getAttributeText($attributeCode);
                                }
                            }
                        }
                    }

                } else {
                    $tags[] = $product->$getAttribute();
                }
            }
        }

        if ($tags AND is_array($tags) AND count($tags)) {
            foreach ($tags as $key => $tag) {
                if (is_null($tags[$key])) {
                    unset($tags[$key]);
                }
            }
        }

        $cache->save(serialize($tags), $cacheKey, array('COLLECTION_DATA'), 86400);

        return $tags;
    }

    private function _getAttributeTypeByCode($code)
    {
        $attribute = Mage::getModel('eav/entity_attribute')->loadByCode('catalog_product', $code);
        if ($attribute) {
            return $attribute->getFrontendInput();
        }

        return 'text';
    }

    protected function _camelize($name)
    {
        return uc_words($name, '');
    }

    public function getAllProductCategories($product)
    {
        //$product->load($product->getId());

        $cache = Mage::app()->getCache();

        $result = null;

        $catIds = $product->getCategoryIds();

        $cacheKey = md5('catresult-' . $product->getId() . $catIds);

        if (Mage::app()->useCache('COLLECTION_DATA')) {
            $result = $cache->load($cacheKey);
            if ($result) {
                return unserialize($result);
            }
        }

        if ($catIds AND is_array($catIds) AND count($catIds)) {

            $catsCollection = Mage::getModel('catalog/category')->getCollection();
            $catsCollection->addIsActiveFilter();

            $catsCollection->addNameToResult();
            $catsCollection->addFieldToFilter('entity_id', array('in' => $catIds));

            $result = array();

            foreach ($catsCollection as $cat) {
                $parentCategories = $cat->getParentCategories();
                foreach ($parentCategories as $parent) {
                    if ($parent->getLevel() == 1) {
                        continue;
                    }
                    $result[$parent->getId()] = $parent->getName();
                }

                $result[$cat->getId()] = $cat->getName();
            }

            $cache->save(serialize($result), $cacheKey, array('COLLECTION_DATA'), 86400);
        }

        return $result;

    }
}