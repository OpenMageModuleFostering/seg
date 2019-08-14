<?php

class Koan_Seg_Model_Seg_Order extends Varien_Object
{
    public function prepare($order, $includeMailAndDate = true)
    {
        if (!$order) {
            Mage::throwException($this->_getHelper()->__('Order is not valid!'));
        }

        if (!$orderId = $order->getId()) {
            Mage::throwException($this->_getHelper()->__('Missing Order Id'));
        }

        $baseGrandTotal = is_null($order->getBaseGrandTotal()) ? 0 : $order->getBaseGrandTotal();
        $baseTotalRefunded = is_null($order->getBaseTotalRefunded()) ? 0 : $order->getBaseTotalRefunded();

        $revenue = $baseGrandTotal - $baseTotalRefunded;

        $shippingMethod = $order->getShippingDescription();
        if (empty($shippingMethod)) {
            $shippingMethod = 'VIRTUAL ORDER';
        }

        $customerEmail = is_null($order->getCustomerEmail()) ? 'unknown@mail.com' : $order->getCustomerEmail();
        $createdAt = is_null($order->getCreatedAt()) ? '0000-00-00 0:00:00' : $order->getCreatedAt();

        $params = array(
            'DeliveryMethod',
            'DeliveryRevenue',
            'Discount',
            'Id',
            'OrderLines',
            //'_p',
            'Revenue'
        );

        if ($includeMailAndDate) {
            $this->setData('email', $customerEmail);
            $this->setData('date', $createdAt);

            $params[] = 'email';
            $params[] = 'date';
        }

        $this->setData('DeliveryMethod', $shippingMethod);
        $this->setData('DeliveryRevenue', number_format($order->getBaseShippingAmount(), 2, '.', ''));
        $this->setData('Discount', number_format($order->getBaseDiscountAmount() * (-1), 2, '.', ''));
        $this->setData('Id', $orderId);
        $this->setData('OrderLines', $this->_prepareOrderLines($order));
        //$this->setData('_p', $order->getBaseGrandTotal());
        $this->setData('Revenue', number_format($revenue, 2, '.', ''));

        return $this->toArray($params);

        //return prepared array structure for sending via HTTP request
    }

    private function _prepareOrderLines($order)
    {
        $orderItems = $order->getAllVisibleItems();

        $result = array();
        if ($orderItems AND count($orderItems)) {
            foreach ($orderItems as $item) {
                $result[] = Mage::getModel('koan_seg/seg_order_line')->prepare($item);
            }
        }

        return $result;
    }

    private function _getHelper()
    {
        return Mage::helper('koan_seg');
    }

}