<?php

/**
 * @category Koan
 * @package Seg
 * @author Seg <hello@getseg.com, http://getseg.com>
 * @copyright Seg <http://getseg.com>
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Koan_Seg_Model_Seg_Quote_Line extends Varien_Object
{

    public function prepare($item, $isAdded = false)
    {
        $qty = $item->getQty();
        $parentProduct = null;

        //Product is configurable parent
        if (count($item->getChildren())) {
            $children = $item->getChildren();
            $product = $children[0]->getProduct();
            $parentProduct = $item->getProduct();
            //Product is simple
        } else {
            $product = $item->getProduct();
        }

        $product->load($product->getId());

        if ($parent = $item->getParentItem()) {
            $subtotal = $parent->getBaseRowTotalInclTax();
            $discount = is_null($parent->getBaseDiscountAmount()) ? 0 : $parent->getBaseDiscountAmount();
            $subtotal -= $discount;
        } else {
            $subtotal = $item->getBaseRowTotalInclTax();
            $discount = is_null($item->getBaseDiscountAmount()) ? 0 : $item->getBaseDiscountAmount();
            $subtotal -= $discount;
        }

        if ($parent = $item->getParentItem()) {
            $originalPrice = $parent->getBasePriceInclTax();
        } else {
            $originalPrice = $item->getBasePriceInclTax();
        }

        if ($discount > 0) {
            $price = $subtotal / $item->getQty();
        } else {
            $price = $originalPrice;
        }

        $this->setData('Quantity', number_format($qty, 2, '.', ''));
        $this->setData('Id', $item->getProductId());
        $this->setData('ImageUrl', $product->getImageUrl());

        //TODO: Check this parameter for configurable products and Bundle products etc
        $this->setData('Name', $item->getName());

        $this->setData('OriginalPrice', number_format($originalPrice, 2, '.', ''));
        $this->setData('Price', number_format($price, 2, '.', ''));

        $optionsResult = array();

        $helper = Mage::helper('catalog/product_configuration');

        if ($parentProduct) {
            $typeId = $parentProduct->getTypeId();
            if ($typeId AND $typeId == Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE) {
                $optionsResult = $helper->getConfigurableOptions($item);

                if ($optionsResult) {
                    $variantName = '';
                    $i = 0;
                    foreach ($optionsResult as $option) {
                        if ($i > 0) {
                            $variantName .= ', ';
                        }

                        $variantName .= sprintf('%s: %s', $option['label'], $option['value']);
                        $i++;
                    }

                    $this->setData('VariantName', $variantName);
                }

            }

        }

        $outputAttributes = array(
            'Quantity',
            'Id',
            'ImageUrl',
            'Name',
            'OriginalPrice',
            'Price',
        );

        $brands = $this->_getHelper()->getProductBrands($product, $item->getStoreId());
        if (!empty($brands)) {
            $this->setData('Brands', $brands);
            $outputAttributes[] = 'Brands';
        }

        if (!empty($variantName)) {
            $outputAttributes[] = 'VariantName';
        }

        $catIds = $product->getCategoryIds();
        $categoriesResult = array();
        if ($catIds AND is_array($catIds) AND count($catIds)) {
            foreach ($catIds as $catId) {
                $category = Mage::getModel('catalog/category')
                    ->setStoreId($item->getStoreId())
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

        if ($isAdded == true) {
            $this->setData('Added', true);
            array_unshift($outputAttributes, 'Added');
        }

        return $this->toArray($outputAttributes);
    }

    private function _getHelper()
    {
        return Mage::helper('koan_seg');
    }
}