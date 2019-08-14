<?php

/**
 * @category Koan
 * @package Seg
 * @author Seg <hello@getseg.com, http://getseg.com>
 * @copyright Seg <http://getseg.com>
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Koan_Seg_Model_Seg_Product extends Varien_Object
{
    public function prepare($product)
    {
        if (!$product) {
            Mage::throwException('Product is not valid!');
        }

        if (!$productId = $product->getId()) {
            Mage::throwException('Missing Product Id');
        }

        $storeId = Mage::app()->getStore()->getId();

        $outputAttributes = array(
            'Id', 'ImageUrl', 'Name', 'OriginalPrice', 'Price'
        );

        $productData = array(
            'Id' => $productId,
            'ImageUrl' => $product->getImageUrl(),
            'Name' => $product->getName(),
            'OriginalPrice' => Mage::helper('tax')->getPrice($product, $product->getPrice()),
            'Price' => Mage::helper('tax')->getPrice($product, $product->getFinalPrice()),
        );

        $this->setData($productData);

        $tags = $this->_getHelper()->getProductTags($product, $storeId);

//        $catIds = $product->getCategoryIds();
//        if ($catIds AND is_array($catIds) AND count($catIds)) {
//            foreach ($catIds as $catId) {
//                $category = Mage::getModel('catalog/category')
//                    ->setStoreId($storeId)
//                    ->load($catId);
//
//                if ($category AND $category->getId()) {
//                    $tags[] = $category->getName();
//                }
//            }
//        }
        $categories = Mage::helper('koan_seg')->getAllProductCategories($product);
        if ($categories AND is_array($categories) AND count($categories)) {
            foreach ($categories as $catName) {
                $tags[] = $catName;
            }
        }

        if (!empty($tags)) {
            $this->setData('Tags', @array_values($tags));
            $outputAttributes[] = 'Tags';
        }

        return $this->toArray($outputAttributes);
    }

    private function _getHelper()
    {
        return Mage::helper('koan_seg');
    }
}