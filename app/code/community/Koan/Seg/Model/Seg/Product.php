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

        $catIds = $product->getCategoryIds();
        $categoriesResult = array();
        if ($catIds AND is_array($catIds) AND count($catIds)) {
            foreach ($catIds as $catId) {
                $category = Mage::getModel('catalog/category')
                    ->setStoreId($storeId)
                    ->load($catId);

                if ($category AND $category->getId()) {
                    $categoriesResult[] = $category->getName();
                }
            }
        }
        if (count($categoriesResult)) {
            $this->setData('Categories', $categoriesResult);
            $outputAttributes[] = 'Categories';
        }

        $brands = $this->_getHelper()->getProductBrands($product, $storeId);
        if (!empty($brands)) {
            $this->setData('Brands', $brands);
            $outputAttributes[] = 'Brands';
        }

        return $this->toArray($outputAttributes);
    }

    private function _getHelper()
    {
        return Mage::helper('koan_seg');
    }
}