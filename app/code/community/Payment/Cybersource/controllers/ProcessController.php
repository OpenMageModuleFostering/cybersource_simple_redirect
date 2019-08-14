<?php

/**
 * Cybersource Process Controller
 *
 * @category    Payment
 * @package     Payment_Cybersource
 * @author      Andrew Moskal <andrew.moskal@softaddicts.com>
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Payment_Cybersource_ProcessController extends Mage_Core_Controller_Front_Action {

    protected $_order;

    protected function _getCheckout() {
        return Mage::getSingleton('checkout/session');
    }

    public function getCybersource() {
        return Mage::getSingleton('cybersource/cybersource');
    }

    public function getOrder() {
        if (null === $this->_order) {
            $session = Mage::getSingleton('checkout/session');
            $this->_order = Mage::getModel('sales/order');
            $this->_order->loadByIncrementId($session->getLastRealOrderId());
        }
        return $this->_order;
    }

    public function redirectAction() {
        $order = $this->getOrder();
        if (!$order->getId()) {
            $this->norouteAction();
            return;
        }

        $order->addStatusToHistory(
                $order->getStatus(), $this->__('Customer was redirected to Cybersource.')
        );
        $order->save();

        $this->getResponse()->setBody($this->getLayout()->createBlock('cybersource/redirect')->setOrder($order)->toHtml());
    }

    public function failureAction() {
        $order = $this->getOrder();
        if (!$order->getId()) {
            $this->_redirect('checkout/cart');
            return;
        }

        $order->cancel();
        $order->addStatusToHistory(
                $order->getStatus(), $this->__('Payment was failed.')
        );
        $order->save();

        $this->_getCheckout()->addError($this->__('Payment has been failed.'));
        $this->_redirect('checkout/cart');
    }
    
    public function resultAction() {
        $request = $this->getRequest();
        $params = $request->getParams();

        if ($this->_validateResponse($params)) {
            $rrNumber = isset($params['req_reference_number']) ? $params['req_reference_number'] : null;
            $order = Mage::getModel('sales/order')->loadByIncrementId($rrNumber);
            if ($order && $order->canInvoice()) {
                $invoice = $order->prepareInvoice();
                $invoice->register()->capture();
                Mage::getModel('core/resource_transaction')
                        ->addObject($invoice)
                        ->addObject($invoice->getOrder())
                        ->save();
                $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true);
                $order->getPayment()->setLastTransId($params['transaction_id']);
                $order->sendNewOrderEmail()
                        ->setEmailSent(true)
                        ->save();
            }
        }
    }

    public function cancelAction() {
        $order = $this->getOrder();
        if (!$order->getId()) {
            $this->_redirect('checkout/cart');
            return;
        }

        $order->cancel();
        $order->addStatusToHistory(
                $order->getStatus(), $this->__('Payment was canceled.')
        );
        $order->save();

        $this->_getCheckout()->addError($this->__('Payment has been canceled.'));
        $this->_redirect('checkout/cart');
        return;
    }

    protected function _validateResponse($params) {
        $rrNumber = isset($params['req_reference_number']) ? $params['req_reference_number'] : null;
        $order = Mage::getModel('sales/order')->loadByIncrementId($rrNumber);
        if (!$order) {
            return false;
        }
        $errors = array();
        if (isset($params['reason_code']) && !in_array((int)$params['reason_code'], array(100, 110))) {
            $errors[] = 'reason_code is not 100 or 110';
        }
        
        if (isset($params['decision']) && 'ACCEPT'  !== $params['decision']) {
            $errors[] = 'decision is not accept';
        }

        $hashSign = Mage::helper('cybersource')->getHashSign($params);
        $signature = isset($params['signature']) ? $params['signature'] : null;
        if ($hashSign != $signature) {
            $errors[] = 'singature is invalid';
        }

        return (0 === count($errors));
    }

    public function successAction() {
        $order = $this->getOrder();
        if (!$order->getId()) {
            $this->_redirect('checkout/cart');
            return;
        }

        $responseParams = $this->getRequest()->getParams();
        $validateResponse = $this->_validateResponse($responseParams);
        if ($validateResponse) {

            $order->addStatusToHistory(
                    $order->getStatus(), $this->__('Customer successfully returned from CyberSource and payment was approved.')
            );
            $order->save();

            $this->_redirect('checkout/onepage/success');
            return;
        } else {
            $comment = '';
            if (isset($responseParams['message'])) {
                $comment .= '<br />Error: ';
                $comment .= "'" . $responseParams['message'] . "'";
            }
            $order->cancel();
            $order->addStatusToHistory(
                    $order->getStatus(), $this->__('Customer successfully returned from CyberSource. Payment was declined.') . $comment
            );
            $order->save();

            $this->_getCheckout()->addError($this->__('There is an error processing your payment.' . $comment));
            $this->_redirect('checkout/cart');
            return;
        }
    }
}
