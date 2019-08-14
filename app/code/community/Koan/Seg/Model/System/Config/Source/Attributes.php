<?php

class Koan_Seg_Model_System_Config_Source_Attributes
{

//    public function toOptionArray()
//    {
//        return array(
//            array('value' => 1, 'label'=>Mage::helper('adminhtml')->__('Yes')),
//            array('value' => 0, 'label'=>Mage::helper('adminhtml')->__('No')),
//        );
//    }

    private function _getProductAttributes()
    {
        $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
            ->addVisibleFilter()
            ->getItems();

        return $attributes;
    }

    private function getSkippedAttributes()
    {
        return array(
            'name',
            'description',
            'short_description',
            'sku',
            'weight',
            'news_from_date',
            'new_to_date',
            'status',
            'url_key',
            'visibility',
            'open_amount_min',
            'open_amount_max',
            'price',
            'group_price',
            'special_price',
            'special_from_date',
            'special_to_date',
            'cost',
            'tier_price',
            'msrp_enabled',
            'msrp_display_actual_price_type',
            'msrp',
            'tax_class_id',
            'price_view',
            'meta_title',
            'meta_keyword',
            'meta_description',
            'image',
            'small_image',
            'thumbnail',
            'media_gallery',
            'gallery',
            'is_recurring',
            'recurring_profile',
            'custom_design',
            'custom_design_from',
            'custom_design_to',
            'custom_layout_update',
            'page_layout',
            'options_container',
            'gift_message_available',
            'gift_wrapping_available',
            'gift_wrapping_price',
            'use_config_allow_message',
            'use_config_email_template',
            'use_config_is_redeemable',
            'use_config_lifetime',
            'weight_type',
            'url_path'

        );
    }

    public function toOptionArray()
    {
        $result = array();

        foreach ($this->_getProductAttributes() as $attribute) {

            if (in_array($attribute->getName(), $this->getSkippedAttributes())) {
                continue;
            }

            $result[] = array('label' => $attribute->getName(), 'value' => $attribute->getName());
        }

        return $result;

    }

}
