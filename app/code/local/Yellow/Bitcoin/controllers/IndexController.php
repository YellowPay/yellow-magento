<?php

class Yellow_Bitcoin_IndexController extends Mage_Core_Controller_Front_Action {

    public function IndexAction() {
        $current_page = Mage::getSingleton('core/session')->getData("current_page");
        if ($current_page == "status") {
            return $this->_redirect("*/*/status");
        } elseif ($current_page == "cancel") {
            return $this->_redirect("*/*/cancel");
        } else {
            Mage::getSingleton('core/session')->setData("current_page", "index");
        }

        try {
            $this->loadLayout();
            if (!Mage::getSingleton('core/session')->getData('has_invoice')) {
                $model = Mage::getModel('bitcoin/bitcoin');
                $order = $this->getOrder();
                if (!$order) {
                    return $this->_forward("no-route");
                }
                $model->setOrder($order);
                if ($model->createInvoice($order)) {
                    $invoice = Mage::getSingleton('core/session')->getData('invoice');
                    $order->addStatusToHistory($order->getStatus(), "the customer is on the payment page , his invoice id  is '{$invoice["id"]}'");
                    $order->save();
                    $model->clearCart();
                }
            }
            $this->renderLayout();
        } catch (Mage_Core_Exception $e) {
            //$order->cancel();
            $order->addStatusToHistory($order->getStatus(), 'Failed to redirect customer to Payment Page: ' . $e->getMessage());
            $order->save();
            $this->_redirect('checkout/onepage/failure');
        }
    }

    public function IpnAction() {
        if ($this->getRequest()->isPost()) {
            $invoice= $this->getRequest()->getPost();
            $id = base64_decode($this->getRequest()->getParam("id"));
            $order = Mage::getModel('sales/order')->load($id, 'quote_id');
            if (!$order || $order->getPayment()->getMethodInstance()->getCode() <> "bitcoin" || $order->getState() <> Mage_Sales_Model_Order::STATE_NEW) {
                return $this->_forward("no-route");
            }

            switch ($invoice['status']) {
                case 'paid':
                    $status = Mage::getModel("bitcoin")->getSuccessStatus();
                    $status_message = " client paid :" ; // $invoice["message"];
                    $order->addStatusToHistory($status,  $status_message);
                    $order->sendNewOrderEmail();
                    $order->save();
                    break;
                case 'unconfirmed':
                case 'partial':
                    $status = Mage::getModel("bitcoin")->getSuccessStatus();
                    $status_message = " client paid but payment is unconfirmed / partial :" ; // $invoice["message"];
                    $order->addStatusToHistory($status,  $status_message);
                    $order->save();
                    break;
                case 'failed':
                case 'invalid':
                    $status = Mage::getModel("bitcoin")->getFailedStatus();
                    $status_message = " client failed to pay :" ; // $invoice["message"];
                    $order->addStatusToHistory($status,  $status_message);
                    $order->cancel();
                    $order->save();
                    
                    break;
                /// its just a new invoice , I will never expect a post with new status , though I had created it 
                case 'new':
                default:
                    break;
            }
            return json_encode(array("message" => "done"));
        } else {
            return $this->_forward("no-route");
        }
    }

    public function StatusAction() {
        $invoice = Mage::getSingleton('core/session')->getData("invoice");
        try {
            $id = $invoice["id"];
            $model = Mage::getModel('bitcoin/bitcoin');
            $order = $this->getOrder();
            if (!$order) {
                return $this->_forward("no-route");
            }
            $model->setOrder($order);
            $status = $model->checkInvoiceStatus($id);
            switch ($status) {
                case "paid":
                case "partial":
                case "unconfirmed":
                    return $this->_redirect('checkout/onepage/success');
                    break;
                case "failed":
                    return $this->_redirect('checkout/onepage/failure');
                    break;
                default:
                    break;
            }
        } catch (Mage_Core_Exception $e) {
            /// needs log in here
            return $this->_redirect('checkout/onepage/failure');
        }
    }

    private function getOrder() {
        $session = Mage::getSingleton('checkout/session');
        if (!$session->getLastRealOrderId()) {
            return false;
        }
        $order = Mage::getModel('sales/order');
        $order = $order->loadByIncrementId($session->getLastRealOrderId());
        return $order;
    }

}
