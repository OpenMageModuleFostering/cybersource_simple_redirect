<?php

/**
 * Cybersource Block Info
 *
 * @category    Payment
 * @package     Payment_Cybersource
 * @author      Andrew Moskal <andrew.moskal@softaddicts.com>
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Payment_Cybersource_Block_Info extends Mage_Payment_Block_Info {

    protected function _construct() {
        parent::_construct();
    }

    public function getMethodCode() {
        return $this->getInfo()->getMethodInstance()->getCode();
    }

}
