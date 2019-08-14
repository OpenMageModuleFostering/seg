<?php

class Koan_Seg_Model_Seg_Order_Line extends Varien_Object
{

    public function prepare($item)
    {
        $qty = $item->getQtyOrdered();

        //Product is configurable parent
        if (count($item->getChildrenItems())) {
            $children = $item->getChildrenItems();
            $product = $children[0]->getProduct();
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
            $price = $subtotal / $item->getQtyOrdered();
        } else {
            $price = $originalPrice;
        }

        //$this->setData('Quantity', number_format($qty, 2, '.', ''));
        $this->setData('Quantity', intval($qty));
        $this->setData('Id', $item->getProductId());
        $this->setData('ImageUrl', $product->getImageUrl());

        //TODO: Check this parameter for configurable products and Bundle products etc
        $this->setData('Name', $item->getName());

        $this->setData('OriginalPrice', number_format($originalPrice, 2, '.', ''));
        $this->setData('Price', number_format($price, 2, '.', ''));

        $optionsResult = array();

        if ($options = $item->getProductOptions()) {
            if (isset($options['options'])) {
                $optionsResult = array_merge($optionsResult, $options['options']);
            }
            if (isset($options['additional_options'])) {
                $optionsResult = array_merge($optionsResult, $options['additional_options']);
            }
            if (!empty($options['attributes_info'])) {
                $optionsResult = array_merge($options['attributes_info'], $optionsResult);
            }

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

        $outputAttributes = array(
            'Quantity',
            'Id',
            'ImageUrl',
            'Name',
            'OriginalPrice',
            'Price',
        );

        $tags = $this->_getHelper()->getProductTags($product, $item->getStoreId());

        if (!empty($variantName)) {
            $outputAttributes[] = 'VariantName';
        }

//        $catIds = $product->getCategoryIds();
//        if ($catIds AND is_array($catIds) AND count($catIds)) {
//            foreach ($catIds as $catId) {
//                $category = Mage::getModel('catalog/category')
//                    ->setStoreId($item->getStoreId())
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