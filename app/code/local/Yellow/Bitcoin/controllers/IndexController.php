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
class Yellow_Bitcoin_IndexController extends Mage_Core_Controller_Front_Action
{


    public function payAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function IpnAction()
    {
        /**
         * return not found on all requests but POST
         */
        $this->log("----------- IPN request validation ------------");
        if (!$this->getRequest()->isPost()) {
            $this->log("skip none POST requests");
            return $this->returnForbidden();
        }
        $ip = long2ip(Mage::helper('core/http')->getRemoteAddr(true));
        $this->log("Start IPN request validation");
        $this->log("IP Address of the sender {$ip}");

        /* start to validate the signature */
        $request = $this->getRequest();
        $payload = $request->getRawBody();
        $public_key = $request->getHeader("API-Key");
        $nonce = $request->getHeader("API-Nonce");
        $request_signature = $request->getHeader("API-Sign");
        //$this->log("API-KEY:{$public_key}");
        //$this->log("API-Nonce:{$nonce}");
        //$this->log("API-Sign:{$request_signature}");
        $this->log("Received payload: " . $payload);

        if (!$public_key || !$nonce || !$request_signature || !$payload) {
            $this->log("Credentials missing. Exiting.");
            return $this->returnForbidden();
        }

        $private_key = Mage::helper('core')->decrypt(
            Mage::getModel("bitcoin/bitcoin")->getConfiguration("private_key")
        );
        $url = Mage::helper('core/url')->getCurrentUrl();
        $this->log("CURRENT IPN URL IS : " .$url);

        $message = $nonce . $url . $payload;
        $current_signature = hash_hmac("sha256", $message, $private_key, false);
        $this->log("Calculated signature: " . $current_signature);

        if ($request_signature <> $current_signature) {
            $this->log("IPN VALIDATION FAILED");
            $this->log("Returning 'page not found'");
            $this->log("Your payment data is still safe");
            $this->log("----------- IPN request processing will be skipped -----------");
            return $this->returnForbidden();
        }
        $this->log("IPN signature validation succeeded");
        /* end of validate the signature  */
        /* by this the request has passed validation */
        try {
            /* need to check the ip address of the source from a whitelist list of ips , otherwise this might be used illegaly to update orders  */
            $this->log("----------- IPN request processing ------------");
            $id = base64_decode($this->getRequest()->getParam("id"));
            //$data = file_get_contents('php://input');
            $body = json_decode($payload, true);
            //$this->log("current Id is: {$id}");
            $this->log("Received payload: " . $payload, $body["id"]);
            $url = $body["url"];
            $invoice_id = $body["id"];
            /* simple validation check | might be changed later */
            $collection = Mage::getModel("bitcoin/ipn")
                ->getCollection()
                ->getSelect()
                ->where("quote_id = ? OR order_id = ?", $id)
                ->where("invoice_id =?", $invoice_id);
            $yellow_log = $collection->query()->fetchAll();
            $from_order = $from_quote = false;
            if (count($yellow_log) == 1) {
                if ($yellow_log[0]["quote_id"] === $id) {
                    $from_quote = true;
                    $from_order = false;
                    //$this->log("its a quote", $body["id"]);
                } elseif ($yellow_log[0]["order_id"] === $id) {
                    $from_quote = false;
                    $from_order = true;
                    //$this->log("its an order", $body["id"]);
                }
            } else {
                $this->log("URL validation failed: {$url}");
                $this->log("----------- IPN request processing will be skipped -----------", $body["id"]);
                return $this->_forward("no-route");
            }
            if ($from_order) {
                $order = Mage::getModel('sales/order')->load($id);
            }
            if ($from_quote) {
                $order = Mage::getModel('sales/order')->load($id, "quote_id");
            }
            /* skip quote + authorizing state because the order hasn't been placed yet */
            if ($from_quote && $body["status"] === "authorizing") {
                $this->log(
                    "Quote id {$id} will be skipped because the order hasn't been placed yet. IPN status: {$body["status"]}",
                    $body["id"]
                );
                echo json_encode(array("message" => "skipped"));
                $this->log("----------- IPN request processing will be skipped -----------", $body["id"]);
                return;
            }
            if ($order->getPayment() instanceof Yellow_Bitcoin_Model_Bitcoin) {
                $payment = $order->getPayment()->getMethodInstance();
                if (!$order || $payment->getCode() <> "bitcoin" || $order->getState() <> Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW
                ) {
                    $this->log("Either this order is not paid via Yellow, or it has an unallowed state", $body["id"]);
                    $this->log("----------- IPN request processing will be skipped -----------", $body["id"]);
                    return $this->_forward("no-route");
                }
            }
            $this->log(" invoice status :  {$body["status"]}", $body["id"]);
            switch ($body['status']) {
                case 'paid':
                    $status = Mage::getModel("bitcoin/bitcoin")->getSuccessStatus();
                    $status_message = "Payment confirmation received. Invoice Id: " . $body['id']; // $invoice["message"];
                    $commentHistory = $order->addStatusHistoryComment($status_message,$status);
                    $commentHistory->setIsVisibleOnFront(1);
                    $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING);
                    $order->sendNewOrderEmail();
                    $order->save();
                    Mage::getResourceModel("bitcoin/ipn")->MarkAsPaid($body["id"]);
                    /* create an invoice */
                    $invoiceModel = Mage::getModel('sales/order_invoice_api');
                    $invoice_id = $invoiceModel->create($order->getIncrementId(), array());
                    $invoiceModel->capture($invoice_id);
                    $this->log("Magento Invoice created!", $body["id"]);
                    break;
                case 'reissue':
                    $status = Mage::getModel("bitcoin/bitcoin")->getSuccessStatus(); /// this must bn changed when we had reissue / renew payment ready
                    $status_message = "Client re-issued the invoice. Invoice Id: " . $body['id']; // $invoice["message"];
                    $commentHistory = $order->addStatusHistoryComment($status_message,$status);
                    $commentHistory->setIsVisibleOnFront(1);
                    $order->save();
                    break;
                case 'partial':
                    $status = Mage::getModel("bitcoin/bitcoin")->getSuccessStatus(); /// this must bn changed when we had partial payment ready
                    $status_message = "Client made a partial payment. Invoice Id: " . $body['id']; // $invoice["message"];
                    $commentHistory = $order->addStatusHistoryComment($status_message,$status);
                    $commentHistory->setIsVisibleOnFront(1);
                    $order->save();
                    Mage::getResourceModel("bitcoin/ipn")->MarkAsPartial($body["id"]);
                    break;
                case 'failed':
                case 'invalid':
                    $status = Mage::getModel("bitcoin/bitcoin")->getFailedStatus();
                    $status_message = "Client failed to pay. Invoice Id: " . $body['id']; // $invoice["message"];
                    $commentHistory = $order->addStatusHistoryComment($status_message,$status);
                    $commentHistory->setIsVisibleOnFront(1);
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
                    /// @todo : we need to log here
                    break;
            }
            echo json_encode(array("message" => "done"));
            $this->log("----------- IPN request processing complete -----------", $body["id"]);

        } catch (\Exception $e) {
            $this->log("EXCEPTION:" . $e->getMessage . "|" . $e->getLine(), $body["id"]);
        }
    }

    public function StatusAction()
    {
        $invoice = Mage::getSingleton('core/session')->getData("invoice");
        try {
            $id = $invoice["id"];
            $model = Mage::getModel('bitcoin/bitcoin');
            $order = $this->getOrder();
            if (!$order) {
                $this->log(
                    "This session does not have a recent order. This page may have been accessed directly or sessions were cleared somehow.",
                    $id
                );
                return $this->returnForbidden();
            }
            $model->setOrder($order);
            $status = $model->checkInvoiceStatus($id);
            if ($status == false) {
                $this->log("Invoice status check failed", $id);
                return $this->returnForbidden();
            }
            switch ($status) {
                case "new":
                    return $this->_redirect('bitcoin/index/pay');
                    break;
                case "paid":
                case "partial":
                case "authorizing":
                    return $this->_redirect('checkout/onepage/success');
                    break;
                case "failed":
                    return $this->_redirect('checkout/onepage/failure');
                    break;
                case "refund_requested":
                case "refund_owed":
                    return $this->_redirect('checkout/onepage/failure');
                    break;
                case "expired":
                    return $this->_redirect('checkout/onepage/failure');
                    break;
                default:
                    $this->log("Unknown invoice status. Invoice id: {$id}", $id);
                    return $this->_forward("no-route");
                    break;
            }
        } catch (Mage_Core_Exception $e) {
            $this->log("An error occurred: {$e->getMessage()} on line {$e->getLine()}", $id);
            return $this->_redirect('checkout/onepage/failure');
        }
    }

    /**
     * return current object
     * @return bool|Mage_Sales_Model_Order
     */
    private function getOrder()
    {
        $session = Mage::getSingleton('checkout/session');
        if (!$session->getLastRealOrderId()) {
            return false;
        }
        return Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
    }


    /**
     * log message to file
     *
     * @param string $message
     * @param string $invoice_id
     * @return boolean
     */
    private function log($message, $invoice_id = null)
    {
        $enabled = Mage::getStoreConfig(Yellow_Bitcoin_Model_Log::DATABASE_ENABLED_CONFIG_PATH);
        if ($enabled) {
            Mage::getModel("bitcoin/log")->logMessage($message, $invoice_id);
        }
        Mage::log($message, Zend_Log::ERR, "yellow.log");
        return true;
    }

    /**
     *
     * return 403 header
     * @return mixed
     *
     */
    private function returnForbidden()
    {
        return $this->getResponse()
            ->clearHeaders()
            ->setHttpResponseCode(403)
            ->sendResponse();
    }

}
