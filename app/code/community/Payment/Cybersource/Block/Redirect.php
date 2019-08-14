<?php

/**
 * Cybersource Block Redirect
 *
 * @category    Payment
 * @package     Payment_Cybersource
 * @author      Andrew Moskal <andrew.moskal@softaddicts.com>
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Payment_Cybersource_Block_Redirect extends Mage_Core_Block_Abstract {

    protected function _toHtml() {
        $standard = $this->getOrder()->getPayment()->getMethodInstance();

        $form = new Varien_Data_Form();
        $form->setAction($standard->getCybersourceUrl())
                ->setId('payment_form')
                ->setName('payment_form')
                ->setMethod('POST')
                ->setUseContainer(true);

        foreach ($standard->getFormFields() as $field => $value) {
            $form->addField($field, 'hidden', array('name' => $field, 'value' => $value));
        }

        $html = '<html><body>';
        $html.='<div>';
        $html.= $this->__('Redirecting to CyberSource Payment Gateway ...');
        $html.='</div>';
        $html.= $form->toHtml();
        $html.= '<script type="text/javascript">document.getElementById("payment_form").submit();</script>';
        $html.= '</body></html>';

        return $html;
    }

}
