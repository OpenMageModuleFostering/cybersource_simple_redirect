<?php

/**
 * Cybersource Config Payment Modes
 *
 * @category    Payment
 * @package     Payment_Cybersource
 * @author      Andrew Moskal <andrew.moskal@softaddicts.com>
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Payment_Cybersource_Model_System_Config_Source_Modes {

    public function toOptionArray() {
        return array(
            0 => Mage::helper('cybersource')->__('Test'),
            1 => Mage::helper('cybersource')->__('Live'),
        );
    }

}
