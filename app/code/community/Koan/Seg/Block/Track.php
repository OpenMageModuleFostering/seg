<?php

/**
 * @category Koan
 * @package Seg
 * @author Seg <hello@getseg.com, http://getseg.com>
 * @copyright Seg <http://getseg.com>
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Koan_Seg_Block_Track extends Mage_Core_Block_Template
{
    const PAGE_TYPE_PRODUCT_VIEW = 1;
    const PAGE_TYPE_CATEGORY = 2;
    const PAGE_TYPE_DEFAULT = 3;
    const PAGE_TYPE_CHECKOUT_SUCCESS = 4;
    const PAGE_TYPE_CART = 5;

    public function getCustomerData()
    {
        if (!$customer = $this->getCustomer()) {
            return null;
        }

        if (!$email = $customer->getEmail()) {
            return null;
        }

        $result = array('Email' => $email);

        /*
        $id = $customer->getId();
        if (!empty($id)) {
            $result['Id'] = $id;
        }
*/
        $title = $customer->getPrefix();
        if (!empty($title)) {
            $result['Title'] = $title;
        }

        $firstname = $customer->getFirstname();
        if (!empty($firstname)) {
            $result['FirstName'] = $firstname;
        }

        $lastname = $customer->getLastname();
        if (!empty($lastname)) {
            $result['LastName'] = $lastname;
        }

        $address = $customer->getPrimaryBillingAddress();
        if (!$address) {
            $address = $customer->getPrimaryShippingAddress();
        }

        $country = null;
        if ($address AND $address->getId()) {
            $country = $address->getCountry();
        }

        if (!empty($country)) {
            $result['CountryCode'] = $country;
        }

        $gender = $customer->getGender();
        if($gender){
            $genderTxt = $this->_getAttributeText($customer, 'gender');
            if (!empty($genderTxt)) {
                $result['Gender'] = $genderTxt;
            }
        }

        if ($result AND is_array($result) and count($result)) {
            return json_encode($result);
        }

        return null;
    }

    public function getCustomerDob()
    {
        if (!$customer = $this->getCustomer()) {
            return null;
        }

        $dob = $customer->getDob();
        if (!empty($dob)) {
            return date('Y-m-d', strtotime($dob));
        }

        return null;
    }

    public function getPageData()
    {
        $result = array('type' => self::PAGE_TYPE_DEFAULT);

        $request = $moduleName = Mage::app()->getRequest();
        if (!$request) {
            return $result;
        }

        $moduleName = $request->getModuleName();
        $controllerName = $request->getControllerName();
        $actionName = $request->getActionName();

        $product = Mage::registry('current_product');
        $category = Mage::registry('current_category');

        if ($moduleName == 'catalog' AND $controllerName == 'product' AND ($product AND $product->getId())) {

            $result['type'] = self::PAGE_TYPE_PRODUCT_VIEW;
            $result['data'] = $product;
            return $result;
        }

        if ($moduleName == 'catalog' AND $controllerName == 'category' AND ($category && $category->getId())) {
            $result['type'] = self::PAGE_TYPE_CATEGORY;
            $result['data'] = $category;
            return $result;
        }

        if ($moduleName == 'checkout' AND $controllerName == 'onepage' AND $actionName == 'success') {
            $result['type'] = self::PAGE_TYPE_CHECKOUT_SUCCESS;
            return $result;
        }

        if ($moduleName == 'checkout' AND $controllerName == 'cart' AND $actionName == 'index') {
            $result['type'] = self::PAGE_TYPE_CART;
            return $result;
        }

        return $result;
    }

    protected function _beforeToHtml()
    {
        $websiteId = $this->helper('koan_seg')->getWebsiteId();
        if (empty($websiteId)) {
            $this->_template = null;
            return $this;
        }

        if ($pageData = $this->getPageData()) {
            if (isset($pageData['type']) AND $pageData['type'] == self::PAGE_TYPE_CHECKOUT_SUCCESS) {
                $this->_prepareLastOrder();
            }
        }

        return parent::_beforeToHtml();
    }

    protected function _prepareLastOrder()
    {
        $orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
        if ($orderId) {
            $order = Mage::getModel('sales/order')->load($orderId);
            if ($order->getId()) {
                $this->addData(array(
                    'order_id' => $order->getId(),
                ));
            }
        }
    }

    public function getTrackingEventCode()
    {
        try {
            //Check if we have addToCart event in session
            //and return add to cart event
            $quoteItemId = Mage::getSingleton('customer/session')->getItemAddedToCart();

            if ($quoteItemId) {
                Mage::getSingleton('customer/session')->unsItemAddedToCart();
                return $this->_getAddToCartEventCode($quoteItemId);
            }

            $pageData = $this->getPageData();
            $pageType = isset($pageData['type']) ? $pageData['type'] : self::PAGE_TYPE_DEFAULT;

            switch ($pageType) {
                case self::PAGE_TYPE_PRODUCT_VIEW:

                    $product = isset($pageData['data']) ? $pageData['data'] : null;

                    if (!$product) {
                        Mage::throwException($this->_getHelper()->__('Product doesn\'t exists!'));
                    }

                    return $this->_getProductViewEventCode($product);
                    break;

                case self::PAGE_TYPE_CATEGORY:

                    $category = isset($pageData['data']) ? $pageData['data'] : null;

                    if (!$category) {
                        Mage::throwException($this->_getHelper()->__('Category doesn\'t exists!'));
                    }

                    return $this->_getRangeViewEventCode($category);
                    break;

                case self::PAGE_TYPE_CHECKOUT_SUCCESS:

                    $orderId = $this->getOrderId();
                    $order = Mage::getModel('sales/order')->load($orderId);

                    if (!$order OR !$order->getId()) {
                        Mage::throwException($this->_getHelper()->__('Order doesn\'t exists!'));
                    }

                    return $this->_getOrderSuccessEventCode($order);
                    break;

                case self::PAGE_TYPE_CART:
                    return $this->_getCartViewEventCode();
                    break;

                default:
                    return array('event' => 'PageView', 'data' => null);
                    break;
            }

        } Catch (Exception $e) {
            Mage::getSingleton('koan_seg/exception_handler')->handle('Magento: error in frontend tracking - getTrackingEventCode', $e);
        }

        return null;
    }

    private function _getCartViewEventCode()
    {
        $cart = $this->_getCart();
        $quote = $cart->getQuote();

        if (!$quote) {
            return false;
        }

        $cartData = Mage::getModel('koan_seg/seg_basket')->prepareBasketView($quote);
        $result = array(
            'event' => 'AddedToBasket',
            'data' => json_encode($cartData)
        );

        return $result;
    }

    private function _getAddToCartEventCode($itemId)
    {
        $cartData = Mage::getModel('koan_seg/seg_basket')->prepare($itemId);
        $result = array(
            'event' => 'AddedToBasket',
            'data' => json_encode($cartData)
        );

        return $result;
    }

    private function _getRangeViewEventCode($category)
    {
        $categoryData = Mage::getModel('koan_seg/seg_range')->prepare($category);

        $result = array(
            'event' => 'RangeView',
            'data' => json_encode($categoryData)
        );

        return $result;
    }

    private function _getProductViewEventCode($product)
    {
        $productData = Mage::getModel('koan_seg/seg_product')->prepare($product);

        $result = array(
            'event' => 'ProductView',
            'data' => json_encode($productData)
        );

        return $result;
    }

    private function _getOrderSuccessEventCode($order)
    {
        $orderData = Mage::getModel('koan_seg/seg_order')->prepare($order, false);

        $result = array(
            'event' => 'OrderPlaced',
            'data' => json_encode($orderData)
        );

        return $result;
    }


    private function getCustomer()
    {
        return Mage::getSingleton('customer/session')->getCustomer();
    }

    private function _getAttributeText($customer, $attribute)
    {
        return $customer->getResource()
            ->getAttribute($attribute)
            ->getFrontend()
            ->getValue($customer);
    }

    private function _getHelper()
    {
        return Mage::helper('koan_seg');
    }

    protected function _getCart()
    {
        return Mage::getSingleton('checkout/cart');
    }

}