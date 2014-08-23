<?php
/**
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2014 YellowPay.co
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 **/
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
        /* need to check the ip address of the source from a whitelist list of ips , otherwise this might be used illegaly to update orders  */ 
        if ($this->getRequest()->isPost()) {
            $id  = base64_decode($this->getRequest()->getParam("id"));
            $body = json_decode(file_get_contents('php://input'),true);
            $url = $body["url"];
            /* simple valdation check | might be changed later */
            $collection = Mage::getModel("bitcoin/ipn")
                                    ->getCollection()
                                    ->getSelect()
                                    ->where("quote_id = ? OR order_id = ?" , $id)
                                    ->where("url =?", $url);
            $yellow_log = $collection->query()->fetchAll();
            $from_order = $from_quote =false;
            if(count($yellow_log) == 1){
                if($yellow_log[0]["quote_id"] ===  $id){
                    $from_quote = true;
                    $from_order = false;
                }elseif($yellow_log[0]["order_id"] === $id ){
                    $from_quote = false;
                    $from_order = true;
                }
            }else{
                return $this->_forward("no-route");
            }
            if($from_order){
                $order = Mage::getModel('sales/order')->load($id);
            }
            if($from_quote){
                $order = Mage::getModel('sales/order')->load($id,"quote_id");
            }
            if (!$order || $order->getPayment()->getMethodInstance()->getCode() <> "bitcoin" || $order->getState() <> Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW) {
                    return $this->_forward("no-route");
            }
            switch ($body['status']) {
                case 'paid':
                    $status = Mage::getModel("bitcoin/bitcoin")->getSuccessStatus();
                    $status_message = " client paid " ; // $invoice["message"];
                    $order->addStatusToHistory($status,  $status_message);
                    $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING);
                    $order->sendNewOrderEmail();
                    $order->save();
                    break;
                case 'reissue':
                    $status = Mage::getModel("bitcoin/bitcoin")->getSuccessStatus(); /// this must bn changed when we had reissue / renew payment ready
                    $status_message = " client has re issued the invoice " ; // $invoice["message"];
                    $order->addStatusToHistory($status,  $status_message);
                    $order->save();
                    break;
                case 'partial':
                    $status = Mage::getModel("bitcoin/bitcoin")->getSuccessStatus(); /// this must bn changed when we had partail payment ready
                    $status_message = " client paid but payment is unconfirmed / partial :" ; // $invoice["message"];
                    $order->addStatusToHistory($status,  $status_message);
                    $order->save();
                    break;
                case 'failed':
                case 'invalid':
                    $status = Mage::getModel("bitcoin/bitcoin")->getFailedStatus();
                    $status_message = " client failed to pay :" ; // $invoice["message"];
                    $order->addStatusToHistory($status,  $status_message);
                    $order->setState(Mage_Sales_Model_Order::STATE_HOLDED);
                    $order->cancel();
                    $order->save();
                    break;
                /// its just a new invoice | unconfirmed , I will never expect a post with new status , though I had created the block of it  
                case 'unconfirmed':
                case 'new':
                default:
                    break;
            }
            echo json_encode(array("message" => "done"));
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
