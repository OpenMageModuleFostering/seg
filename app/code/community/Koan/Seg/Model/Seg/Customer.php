<?php

/**
 * @category Koan
 * @package Seg
 * @author Seg <hello@getseg.com, http://getseg.com>
 * @copyright Seg <http://getseg.com>
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Koan_Seg_Model_Seg_Customer extends Varien_Object
{
    public function prepare($customer)
    {
        if (!$customer) {
            Mage::throwException('Customer is not valid!');
        }

        if (!$customerId = $customer->getId()) {
            Mage::throwException('Missing Customer Id');
        }

        $attributes = array(
            'Email',
        );

        $email = $customer->getEmail();
        if (empty($email)) {
            Mage::throwException($this->__('Customer email is missing!'));
        }

        $this->setData('Email', $email);

        $title = $customer->getPrefix();
        if (!empty($title)) {
            $this->setData('Title', $title);
            $attributes[] = 'Title';
        }

        $firstName = $customer->getFirstname();
//        if (empty($firstName)) {
//            $firstName = $email;
//        }

        if (!empty($firstName)) {
            $this->setData('FirstName', $firstName);
            $attributes[] = 'FirstName';
        }

        $lastName = $customer->getLastname();
        if (!empty($lastName)) {
            $this->setData('LastName', $lastName);
            $attributes[] = 'LastName';
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
            $this->setData('CountryCode', $country);
            $attributes[] = 'CountryCode';
        }

        return $this->toArray($attributes);
    }
}