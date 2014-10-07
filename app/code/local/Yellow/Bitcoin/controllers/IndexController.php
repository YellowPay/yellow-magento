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
 * */
class Yellow_Bitcoin_IndexController extends Mage_Core_Controller_Front_Action {

    public function IpnAction() {
        try{
            /* need to check the ip address of the source from a whitelist list of ips , otherwise this might be used illegaly to update orders  */
            $this->log("-----------start an IPN request proccessing ------------");
            if ($this->getRequest()->isPost()) {
                $id = base64_decode($this->getRequest()->getParam("id"));
                $data = file_get_contents('php://input');
                $body = json_decode($data, true);
                $this->log("Id is :{$id}");
                $this->log("I had recived this data :" . $data);
                $url = $body["url"];
                /* simple valdation check | might be changed later */
                $collection = Mage::getModel("bitcoin/ipn")
                        ->getCollection()
                        ->getSelect()
                        ->where("quote_id = ? OR order_id = ?", $id)
                        ->where("url =?", $url);
                $yellow_log = $collection->query()->fetchAll();
                $from_order = $from_quote = false;
                if (count($yellow_log) == 1) {
                    if ($yellow_log[0]["quote_id"] === $id) {
                        $from_quote = true;
                        $from_order = false;
                        $this->log("its a quote");
                    } elseif ($yellow_log[0]["order_id"] === $id) {
                        $from_quote = false;
                        $from_order = true;
                        $this->log("its an order");
                    }
                } else {
                    $this->log("the validation has failed , url : {$url}");
                    $this->log("----------- skipped the IPN request proccessing ---------------------");
                    return $this->_forward("no-route");
                }
                if ($from_order) {
                    $order = Mage::getModel('sales/order')->load($id);
                }
                if ($from_quote) {
                    $order = Mage::getModel('sales/order')->load($id, "quote_id");
                }
                /* skip quote + authorizing state because the order hasn't been placed yet */
                if($from_quote && $body["status"] === "authorizing"){
                    $this->log(" quote id : {$id} is skipped because the order hasn't placed yet , IPN status {$body["status"]}");
                    echo json_encode(array("message" => "skipped"));
                    $this->log("----------- skipped the IPN request proccessing ---------------------");
                    return ;
                }
                if($order->getPayment()  instanceof Yellow_Bitcoin_Model_Bitcoin){
                    $payment = $order->getPayment()->getMethodInstance();
                    if (!$order || $payment->getCode() <> "bitcoin" || $order->getState() <> Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW) {
                        $this->log("either this order is not paid via Yellow , or it had unallowed state ");
                        $this->log("----------- skipped the IPN request proccessing ---------------------");
                        return $this->_forward("no-route");
                    }
                }
                $this->log(" invoice status :  {$body["status"]}");
                switch ($body['status']) {
                    case 'paid':
                        $status = Mage::getModel("bitcoin/bitcoin")->getSuccessStatus();
                        $status_message = "Payment confirmation received , invoice Id : " .$body['id']; // $invoice["message"];
                        $order->addStatusToHistory($status, $status_message);
                        $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING);
                        $order->sendNewOrderEmail();
                        $order->save();
                        Mage::getResourceModel("bitcoin/ipn")->MarkAsPaid($body["id"]);
                         /* create an invoice */
                        $invoiceModel = Mage::getModel('sales/order_invoice_api');
                        $invoice_id = $invoiceModel->create($order->getIncrementId(), array());
                        $invoiceModel->capture($invoice_id);
                        $this->log("Magento Invoice created !");
                        break;
                    case 'reissue':
                        $status = Mage::getModel("bitcoin/bitcoin")->getSuccessStatus(); /// this must bn changed when we had reissue / renew payment ready
                        $status_message = " client has re issued the invoice , invoice Id : " . $body['id']; // $invoice["message"];
                        $order->addStatusToHistory($status, $status_message);
                        $order->save();
                        break;
                    case 'partial':
                        $status = Mage::getModel("bitcoin/bitcoin")->getSuccessStatus(); /// this must bn changed when we had partail payment ready
                        $status_message = " client paid but payment is partial , invoice Id : " . $body['id'] ; // $invoice["message"];
                        $order->addStatusToHistory($status, $status_message);
                        $order->save();
                        Mage::getResourceModel("bitcoin/ipn")->MarkAsPartial($body["id"]);
                        break;
                    case 'failed':
                    case 'invalid':
                        $status = Mage::getModel("bitcoin/bitcoin")->getFailedStatus();
                        $status_message = " client failed to pay , invoice Id : " . $body['id'] ; // $invoice["message"];
                        $order->addStatusToHistory($status, $status_message);
                        $order->setState(Mage_Sales_Model_Order::STATE_HOLDED);
                        $order->cancel();
                        $order->save();
                        break;
                    /// its just a new invoice | authorizing , I will never expect a post with new status , though I had created the block of it
                    case 'authorizing':
                        Mage::getResourceModel("bitcoin/ipn")->MarkAsAuthorizing($body["id"]);
                        break;
                    case 'expired':
                        /* this to update the ipn table when invoice expired  */
                        Mage::getResourceModel("bitcoin/ipn")->MarkAsExpired($body["id"]);
                        break;
                    case 'refund_owed':
                        Mage::getResourceModel("bitcoin/ipn")->MarkAsRefundOwed($body["id"]);
                        break;
                    case 'refund_requested':
                        Mage::getResourceModel("bitcoin/ipn")->MarkAsRefundRequested($body["id"]);
                        break;
                    case 'refund_paid':
                        Mage::getResourceModel("bitcoin/ipn")->MarkAsRefundPaid($body["id"]);
                        break;
                    case 'new':
                    default:
                        break;
                }
                echo json_encode(array("message" => "done"));
            } else {
                return $this->_forward("no-route");
            }
            $this->log("----------- finished an IPN request proccessing ---------------------");
        } catch (\Exception $e){
            $this->log( "EXCEPTION:". $e->getMessage . "|" . $e->getLine());
        }
    }

    public function StatusAction() {
        $invoice = Mage::getSingleton('core/session')->getData("invoice");
        try {
            $id = $invoice["id"];
            $model = Mage::getModel('bitcoin/bitcoin');
            $order = $this->getOrder();
            if (!$order) {
                $this->log("this session doesn't has a recent order , maybe he/she is accessing this page directly");
                return $this->_forward("no-route");
            }
            $model->setOrder($order);
            $status = $model->checkInvoiceStatus($id);
            if($status == false){
               $this->log("failed to check invoice status");
               return $this->_forward("no-route");
            }
            switch ($status) {
                case "paid":
                case "partial":
                case "authorizing":
                    return $this->_redirect('checkout/onepage/success');
                    break;
                case "failed":
                    return $this->_redirect('checkout/onepage/failure');
                    break;
                default:
                    $this->log("unknow invoice status , invoice id : {$id}");
                    return $this->_forward("no-route");
                    break;
            }
        } catch (Mage_Core_Exception $e) {
            $this->log("an error occurred : {$e->getMessage()} on line {$e->getLine()}");
            return $this->_redirect('checkout/onepage/failure');
        }
    }

    private function getOrder() {
        $session = Mage::getSingleton('checkout/session');
        if (!$session->getLastRealOrderId()) {
            return false;
        }
        return Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
    }

    private function log($message) {
        return Mage::log($message, Zend_Log::INFO , "yellow.log");
    }

}
