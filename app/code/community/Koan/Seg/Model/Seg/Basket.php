<?php

class Koan_Seg_Model_Seg_Basket extends Varien_Object
{
    public function prepare($itemId)
    {
        $item = Mage::getModel('sales/quote_item')->load($itemId);
        if (!$item OR !$item->getId()) {
            Mage::throwException($this->_getHelper()->__('Quote Item with this id does not exists!'));
        }

        $quoteId = $item->getQuoteId();
        if (!$quoteId) {
            Mage::throwException($this->_getHelper()->__('Quote id does not exists!'));
        }

        $quote = Mage::getModel('sales/quote')->load($quoteId);
        if (!$quote) {
            Mage::throwException('Quote is not valid!');
        }

        if (!$quoteId = $quote->getId()) {
            Mage::throwException('Missing Quote Id');
        }

        $item->setQuote($quote);
        $revenue = is_null($quote->getBaseGrandTotal()) ? 0 : $quote->getBaseGrandTotal();

        $resultAttributes = array(
            'Discount',
            'Id',
            'OrderLines',
            //'_p',
            'Revenue'
        );

        $shippingAddress = $quote->getShippingAddress();

        $shippingMethod = $shippingAddress->getShippingDescription();
        if (!empty($shippingMethod)) {
            $this->setData('DeliveryMethod', $shippingMethod);
            $resultAttributes[] = 'DeliveryMethod';
        }

        $deliveryRevenue = $shippingAddress->getBaseShippingInclTax();
        if (!is_null($deliveryRevenue)) {
            $this->setData('DeliveryRevenue', number_format($deliveryRevenue, 2, '.', ''));
            $resultAttributes[] = 'DeliveryRevenue';
        }

        $discountAmount = $shippingAddress->getBaseDiscountAmount();
        if (!is_null($discountAmount)) {
            $this->setData('Discount', number_format($discountAmount, 2, '.', ''));
            $resultAttributes[] = 'Discount';
        }

        $this->setData('Id', $quoteId);
        $this->setData('OrderLines', $this->_prepareQuoteLines($quote, $itemId));

        $this->setData('Revenue', number_format($revenue, 2, '.', ''));

        return $this->toArray($resultAttributes);
    }

    private function _prepareQuoteLines($quote, $addedItemId)
    {
        $quoteItems = $quote->getAllVisibleItems();
        $result = array();
        if ($quoteItems AND count($quoteItems)) {
            foreach ($quoteItems as $item) {
                if ($item->getId() == $addedItemId) {
                    $result[] = Mage::getModel('koan_seg/seg_quote_line')->prepare($item, true);
                } else {
                    $result[] = Mage::getModel('koan_seg/seg_quote_line')->prepare($item);
                }

            }
        }

        return $result;
    }

    private function _getHelper()
    {
        return Mage::helper('koan_seg');
    }
}