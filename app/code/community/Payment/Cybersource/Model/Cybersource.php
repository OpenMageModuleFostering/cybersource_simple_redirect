<?php

/**
 * Cybersource Model
 *
 * @category    Payment
 * @package     Payment_Cybersource
 * @author      Andrew Moskal <andrew.moskal@softaddicts.com>
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Payment_Cybersource_Model_Cybersource extends Mage_Payment_Model_Method_Abstract {

    const CYBERSOURCE_PAYMENT_TEST_URL = 'https://testsecureacceptance.cybersource.com/pay';
    const CYBERSOURCE_PAYMENT_LIVE_URL = 'https://secureacceptance.cybersource.com/pay';
    

    protected $_code = 'cybersource';
    protected $_formBlockType = 'cybersource/form';
    protected $_infoBlockType = 'cybersource/info';
    protected $_isGateway = false;
    protected $_canAuthorize = false;
    protected $_canCapture = true;
    protected $_canCapturePartial = false;
    protected $_canRefund = false;
    protected $_canVoid = false;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = false;

    public function validate() {
        parent::validate();
        return $this;
    }

    public function capture(Varien_Object $payment,$amount = null) {
        $payment->setStatus(self::STATUS_APPROVED)->setLastTransId($this->getTransactionId());
        return $this;
    }

    public function getCybersourceUrl() {
        return (1 === (int)$this->getConfigData('mode'))?self::CYBERSOURCE_PAYMENT_LIVE_URL:self::CYBERSOURCE_PAYMENT_TEST_URL;
    }

    public function getOrderPlaceRedirectUrl() {
        return Mage::getUrl('cybersource/process/redirect',array('_secure' => true));
    }

    protected function getSuccessUrl() {
        return Mage::getUrl('cybersource/process/success', array('_secure' => true));
    }

    protected function getCancelUrl() {
        return Mage::getUrl('cybersource/process/cancel', array('_secure' => true));
    }
    
    protected function getFailureUrl() {
        return Mage::getUrl('cybersource/process/failure', array('_secure' => true));
    }

    protected function getResultUrl() {
        return Mage::getUrl('cybersource/process/result', array('_secure' => true));
    }

    public function getCustomer() {
        if (empty($this->_customer)) {
            $this->_customer = Mage::getSingleton('customer/session')->getCustomer();
        }
        return $this->_customer;
    }

    public function getCheckout() {
        if (empty($this->_checkout)) {
            $this->_checkout = Mage::getSingleton('checkout/session');
        }
        return $this->_checkout;
    }

    public function getQuote() {
        if (empty($this->_quote)) {
            $this->_quote = $this->getCheckout()->getQuote();
        }
        return $this->_quote;
    }

    public function getOrder() {
        if (empty($this->_order)) {
            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($this->getCheckout()->getLastRealOrderId());
            $this->_order = $order;
        }
        return $this->_order;
    }

    public function getEmail() {
        $email = $this->getOrder()->getCustomerEmail();
        if (!$email) {
            $email = $this->getQuote()->getBillingAddress()->getEmail();
        }
        return $email;
    }

    public function getOrderAmount() {
        return sprintf('%.2f', $this->getOrder()->getGrandTotal());
    }

    public function getOrderCurrency() {
        $currency = $this->getOrder()->getOrderCurrency();
        return is_object($currency)? $currency->getCurrencyCode():null;
    }

    public function getHashSign($fields) {
        return Mage::helper('cybersource')->getHashSign($fields);
    }

    public function getFormFields() {
        $order = $this->getOrder();
        $fields = array();

        $fields['profile_id'] = $this->getConfigData('profile_id');
        $fields['access_key'] = $this->getConfigData('access_key');
        $fields['transaction_uuid'] = Mage::helper('core')->uniqHash();
        $fields['signed_field_names'] = '';

        $fields['unsigned_field_names'] = '';
        $fields['signed_date_time'] = gmdate("Y-m-d\TH:i:s\Z");
        $fields['locale'] = 'en';
        $fields['transaction_type'] = 'sale';
        $fields['reference_number'] = $order->getRealOrderId();
        $fields['amount'] = $this->getOrderAmount();
        $fields['currency'] = $this->getOrderCurrency();

        $billingAddress = $order->getBillingAddress();
        $fields['bill_to_address_city'] = $billingAddress->getCity();
        $fields['bill_to_address_country'] = $billingAddress->getCountry();
        $fields['bill_to_address_line1'] = $billingAddress->getStreet(1);
        $fields['bill_to_address_line2'] = $billingAddress->getStreet(2);
        $fields['bill_to_address_postal_code'] = $billingAddress->getPostcode();
        $fields['bill_to_address_state'] = $billingAddress->getRegionCode();
        $fields['bill_to_company_name'] = $billingAddress->getCompany();

        $fields['bill_to_email'] = $this->getEmail();
        $fields['bill_to_forename'] = $billingAddress->getFirstname();
        $fields['bill_to_surname'] = $billingAddress->getLastname();
        $fields['bill_to_phone'] = $billingAddress->getTelephone();
        $fields['customer_ip_address'] = Mage::helper('core/http')->getRemoteAddr();


        $items = $order->getAllItems();
        $n = 0;
        foreach ($items as $_item) {
            $fields['item_' . $n . '_code'] = 'default';
            $fields['item_' . $n . '_unit_price'] = sprintf("%01.2f", $_item->getPrice());
            $fields['item_' . $n . '_sku'] = $_item->getSku();
            $fields['item_' . $n . '_quantity'] = (int) $_item->getQtyOrdered();
            $fields['item_' . $n . '_name'] = $_item->getName();
            $n++;
        }

        $fields['line_item_count'] = $n;
        $fields['signed_field_names'] = implode(',', array_keys($fields));
        $fields['signature'] = $this->getHashSign($fields);

        return $fields;
    }

}
