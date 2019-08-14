<?php

/**
 * Cybersource Data Helper
 *
 * @category    Payment
 * @package     Payment_Cybersource
 * @author      Andrew Moskal <andrew.moskal@softaddicts.com>
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Payment_Cybersource_Helper_Data extends Mage_Core_Helper_Abstract {

    public function getConfig($field, $default = null) {
        $value = Mage::getStoreConfig('payment/cybersource/' . $field);

        return ('' === trim($value)) ? $default : $value;
    }

    public function getHashSign($params) {
        $fieldsToSign = explode(",", $params['signed_field_names']);
        foreach ($fieldsToSign as $field) {
            $dataToSign[] = $field . "=" . $params[$field];
        }
        return base64_encode(hash_hmac('sha256', implode(",", $dataToSign), $this->getConfig('secret_key'), true));
    }

}