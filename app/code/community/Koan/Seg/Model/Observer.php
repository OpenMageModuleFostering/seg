<?php

class Koan_Seg_Model_Observer
{
    public function cronprocess()
    {
        if (!Mage::helper('koan_seg')->isExportCronEnabled()) {
            return $this;
        }

        $exporter = Mage::getSingleton('koan_seg/seg_exporter');

        if ($exporter->hasCustomersToProcess()) {
            Mage::getSingleton('koan_seg/seg_exporter')->exportCustomers();
            return $this;
        }

        if ($exporter->hasOrdersToProcess()) {
            Mage::getSingleton('koan_seg/seg_exporter')->exportHistoryOrders();
        }

        return $this;
    }

    public function checkoutCartProductAddAfter($observer)
    {
        $quoteItem = $observer->getEvent()->getQuoteItem();
        $quoteItem->setAddedToCartFlag(true);

        return $observer;
    }

    public function cartItemAfterSave($observer)
    {
        $quoteItem = $observer->getEvent()->getItem();

        if ($children = $quoteItem->getChildren()) {
            foreach ($children as $childItem) {
                if ($childItem->getAddedToCartFlag() == true) {
                    $quoteItem = $childItem;
                    break;
                }

            }
        }

        if ($quoteItem->getAddedToCartFlag()) {
            if ($quoteItem->getParentItemId()) {
                Mage::getSingleton('customer/session')->setItemAddedToCart($quoteItem->getParentItemId());
                $quoteItem->unsAddedToCartFlag();
            } else if ($quoteItem->hasId()) {
                Mage::getSingleton('customer/session')->setItemAddedToCart($quoteItem->getId());
                $quoteItem->unsAddedToCartFlag();
            }

            return $observer;
        }

        return $observer;
    }

    public function orderPlaceAfter($observer)
    {
        $order = $observer->getEvent()->getOrder();
        if (!$order) {
            return $observer;
        }

        if (!$order->getId()) {
            return $observer;
        }

        try {
            $orderData = Mage::getModel('koan_seg/seg_order')->prepare($order);
            Mage::getModel('koan_seg/seg_client')->exportNewOrder($orderData);
        } Catch (Exception $e) {
            Mage::getSingleton('koan_seg/exception_handler')->handle('Magento: error in orderPlaceAfter observer - exportNewOrder', $e);
        }

        return $observer;
    }

}