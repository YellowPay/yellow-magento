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
Class Yellow_Bitcoin_Model_Bitcoin extends Mage_Payment_Model_Method_Abstract
{

    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = 'bitcoin';

    /**
     * Payment Method features
     *
     * @var bool
     */
    protected $_isGateway = true;
    protected $_canOrder = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = false;
    protected $_canCaptureOnce = false;
    protected $_canRefund = false;
    protected $_canRefundInvoicePartial = false;
    protected $_canVoid = false;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = true;
    protected $_isInitializeNeeded = false;
    protected $_canFetchTransactionInfo = false;
    protected $_canReviewPayment = false;
    protected $_canCreateBillingAgreement = false;
    protected $_canManageRecurringProfiles = true;

    /**
     * Cash On Delivery payment block paths
     *
     * @var string
     */
    protected $_formBlockType = 'bitcoin/form_bitcoin';

    protected $_infoBlockType = 'bitcoin/info';

    /**
     * Server Root for Yellow API
     *
     * @var String
     */
    private $server_root = "https://api.yellowpay.co/v1/";

    /**
     * create invoice URI
     *
     * @var String
     */
    private $api_uri_create_invoice = "invoice/";

    /**
     * check invoice status URI
     *
     * @var String
     */
    private $api_uri_check_payment = "invoice/[id]/";

    /**
     * @type Mage_Sales_Model_Order
     **/
    private $order;
    
    
    /**
     * constructor method , it also allow to set custom API server
     * MADE FOR DEVELOPERS Only
     */
    public function __construct()
    {
        $custom_server_root = getenv("YELLOW_SERVER");
        if($custom_server_root){
            $this->server_root = $custom_server_root;
        }
        return parent::__construct();
    }

    /**
     * Check whether payment method can be used
     *
     * @param Mage_Sales_Model_Quote|null $quote
     *
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        $isAvailable = parent::isAvailable($quote);
        $quoteCurrency = $quote->getData("quote_currency_code");
        $currencies = array_map('trim', explode(',', Mage::getStoreConfig('payment/bitcoin/currencies')));
        $is_allowed_to_use_currency =  array_search($quoteCurrency, $currencies) !== false;
        return ($isAvailable && $is_allowed_to_use_currency);
    }

    /**
     * Get instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }

    /**
     * can be used in regular checkout
     *
     * @return bool
     */
    public function canUseCheckout()
    {
        if (!$this->isApiKeyConfigured()) {
            return false;
        }
        return $this->_canUseCheckout;
    }

    /**
     * Returns true if the merchant has set their api key
     *
     * @return boolean
     */
    public function isApiKeyConfigured()
    {
        $public_key = Mage::helper('core')->decrypt(Mage::getStoreConfig('payment/bitcoin/public_key'));
        $private_key = Mage::helper('core')->decrypt(Mage::getStoreConfig('payment/bitcoin/private_key'));
        return (!empty($private_key) && !empty($public_key));
    }

    /**
     * Authorize payment abstract method
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Mage_Payment_Model_Abstract | direct redirect to Yellow fullscreen payment page
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        if (!Mage::getStoreConfig('payment/bitcoin/fullscreen')) {
            $data = $this->CheckForPayment($payment);
            return $data;
        } else {
            return $this->CreateInvoiceAndRedirect($payment);
        }
    }

    /**
     *
     * create a yellow invoice
     *
     * @param $payment
     * @param $amount
     *
     * @return Yellow_Bitcoin_Model_Bitcoin
     */
    public function CreateInvoiceAndRedirect($payment)
    {
        $order = $payment->getOrder();
        if (is_array($this->createInvoice($order))) {
            $payment->setIsTransactionPending(true); // status will be PAYMENT_REVIEW instead of PROCESSING
            //$orderId = $order->getIncrementId();
            //$invoiceId = Mage::getModel('sales/order_invoice_api')->create($orderId, array());
            return $this;
        } else {
            $this->log("An error occurred during invoice creation: " . __LINE__);
            Mage::throwException(
                Mage::helper('bitcoin')->__(
                    "We're sorry, an error has occurred while completing your request. Please refresh the page to try again. If the error persists, please send us an email at support@yellowpay.co"
                )
            );
        }
    }

    /**
     * @param Varien_Object $payment
     *
     * @return Yellow_Bitcoin_Model_Bitcoin
     */
    public function CheckForPayment($payment)
    {
        $order = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);
        $quoteId = $order->getQuoteId();
        $ipn = Mage::getModel('bitcoin/ipn');
        $invoice = Mage::getSingleton('core/session')->getData("invoice");
        $invoice_status = $this->checkInvoice($invoice["id"]);
        if (!is_array($invoice_status)) {
            Mage::throwException(
                Mage::helper('bitcoin')->__(
                    "We're sorry, an error has occurred while completing your request. Please refresh the page to try again. If the error persists, please send us an email at support@yellowpay.co"
                )
            );
        }
        switch ($invoice_status["status"]) {
            case "new":
                // This is the error that is displayed to the customer during checkout.
                Mage::throwException("Order has not been paid. Please pay first and then place your order.");
                Mage::log(
                    'Order has not been paid. Please pay first and then place your order.',
                    Zend_Log::CRIT,
                    'yellow.log'
                );
                break;
            case "partial":
                // This is the error that is displayed to the customer during checkout.
                Mage::getResourceModel("bitcoin/ipn")->MarkAsPartial($invoice["id"]);
                Mage::throwException("Order has been partially paid. We don't support partial payment yet.");
                Mage::log(
                    'Order has been partially paid. We don\'t support partial payment yet.',
                    Zend_Log::CRIT,
                    'yellow.log'
                );
                break;

            case "authorizing":
                Mage::getResourceModel("bitcoin/ipn")->MarkAsAuthorizing($invoice["id"]);
                $payment->setIsTransactionPending(true);
                $order = $payment->getOrder();
                $status_message = "Yellow invoice created. Invoice Id: " . $invoice['id'];
                $commentHistory = $order->addStatusHistoryComment($status_message,Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW);
                $commentHistory->setIsVisibleOnFront(1);
                /* start to invoice the order */
                /*$order = $payment->getOrder();
                $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true)->save();
                if (!count($order->getInvoiceCollection())) {
                    $invoice = $order->prepareInvoice()
                            ->setTransactionId(1)
                            ->addComment('Invoiced automatically from widget payment')
                            ->register()
                            ->pay();
                    $transactionSave = Mage::getModel('core/resource_transaction')
                            ->addObject($invoice)
                            ->addObject($invoice->getOrder());
                    $transactionSave->save();
                }*/
                /* end invoice the order */
                break;
            case "paid":
                $invoiceModel = Mage::getModel('sales/order_invoice_api');
                $invoice_id = $invoiceModel->create($payment->getOrder()->getIncrementId(), array());
                $invoiceModel->capture($invoice_id);
                break;
            case "expired":
                Mage::throwException(
                    Mage::helper('bitcoin')->__(
                        "I'm sorry the invoice has {$invoice_status["status"]}, please refresh the shopping cart."
                    )
                );
                break;
            case "refund_owed":
                Mage::throwException(
                    Mage::helper('bitcoin')->__("Incorrect payment received, please request a refund.")
                );
                break;
            case "refund_requested":
                Mage::throwException(
                    Mage::helper('bitcoin')->__(
                        "Refund requested! To place a new order, please refresh shopping cart."
                    )
                );
                break;
            default:
                $this->log("EXCEPTION - UNKNOW STATUS: " . $invoice_status["status"], $invoice_status["id"]);
                Mage::throwException(
                    Mage::helper('bitcoin')->__(
                        "We're sorry, an error has occurred while completing your request. Please refresh the page to try again. If the error persists, please send us an email at support@yellowpay.co"
                    )
                );
                break;
        }
        return $this;
    }

    /**
     * read the invoice url from session and redirect to it
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        if (Mage::getStoreConfig('payment/bitcoin/fullscreen')) {
            if(Mage::helper("bitcoin")->doesTheStoreHasSSL()){
                $target_url = Mage::getUrl("bitcoin/index/pay" , array("_secure" => true));
            }else{
                $target_url = Mage::getUrl("bitcoin/index/pay" , array("_secure" => false));
            }
            return $target_url;
        } else {
            return '';
        }
    }

    /**
     * return invoice data from session
     * @return mixed
     */
    public function getInvoiceData()
    {
        return Mage::getSingleton('core/session')->getData("invoice");
    }

    /**
     * create a yellow invoice
     * @param Mage_Sales_Model_Quote $quote
     * @return boolean
     */
    public function createInvoice($quote)
    {
        $this->clearSessionData();
        if (get_class($quote) == "Mage_Sales_Model_Quote") {
            $array_key = "quoteId";
            $currency_code_key = "quote_currency_code";
        } else {
            $array_key = "orderId";
            $currency_code_key = "order_currency_code";
        }
        $base_price = $quote->getData("grand_total");
        $base_ccy = $quote->getData($currency_code_key);
        $quote_id = $quote->getData("entity_id");

        /// create the ipn url based on the site configuration
        if(Mage::helper("bitcoin")->doesTheStoreHasSSL()){
            $ipnUrl = Mage::getUrl("bitcoin/index/ipn", array("_secure" => true, "id" => base64_encode($quote_id)));
        }else{
            $ipnUrl = Mage::getUrl("bitcoin/index/ipn", array("_secure" => false, "id" => base64_encode($quote_id)));
        }
        $this->log( "GENERATED IPN URL : " . $ipnUrl);
        $http_client = $this->getHTTPClient();
        $yellow_payment_data = array(
            "base_price" => $base_price, /// Set to 0.30 for testing
            "base_ccy" => $base_ccy, /// Set to "USD" for testing
            "callback" => $ipnUrl
        );
        $post_body = json_encode($yellow_payment_data);
        $nonce = round(microtime(true) * 1000);
        $url = $this->server_root . $this->api_uri_create_invoice;
        $message = $nonce . $url . $post_body;
        $private_key = Mage::helper('core')->decrypt($this->getConfiguration("private_key"));
        $hash = hash_hmac("sha256", $message, $private_key, false);

        $http_client->setHeaders($this->getHeaders($nonce, $hash));
        $http_client->setMethod("POST")
            ->setUri($url);
        $http_client->setRawData($post_body);
        try {
            $response = $http_client->request();
            if ($response->getStatus() == "200") {
                $body = $response->getBody();
                $data = json_decode($body, true);
                $this->log("Response: " . $response, $data["id"]);
                /* save the invoice in the database  */
                $invoice_data = array(
                    $array_key => $quote_id,
                    "invoice_id" => $data["id"],
                    "url" => $data["url"],
                    "status" => $data["status"],
                    "address" => $data["address"],
                    "invoice_price" => $data["invoice_price"],
                    "invoice_ccy" => $data["invoice_ccy"],
                    "server_time" => $data["server_time"],
                    "expiration_time" => $data["expiration"],
                    "raw_body" => $yellow_payment_data,
                    "base_price" => $yellow_payment_data["base_price"],
                    "base_ccy" => $yellow_payment_data["base_ccy"],
                    "hash" => $hash
                );
                /// replace https with http on the invoice url in case the store doesn't have ssl certificate
                $helper = Mage::helper("bitcoin");
                if(!$helper->doesTheStoreHasSSL())
                {
                    $invoice_data["url"] = $helper->replaceHttps($data["url"]);
                    $data["url"] = $helper->replaceHttps($data["url"]);
                }

                Mage::getModel("bitcoin/ipn")->saveInvoice($invoice_data);
                /* end saving invoice */
                Mage::getSingleton('core/session')->setData('invoice', $data);
                Mage::getSingleton('core/session')->setData('has_invoice', true);
                return $data;
            } else {
                Mage::throwException($response->getBody());
                $this->log("Error code response received: {$response->getStatus()}", $array_key . ":" . $quote_id);
                $this->log("Response body:" . json_encode($response->getBody()), $array_key . ":" . $quote_id);
                return false;
            }
        } catch (Exception $exc) {
            $this->log($exc->getMessage(), $array_key . ":" . $quote_id);
            $this->log("EXCEPTION: " . json_encode($exc), $array_key . ":" . $quote_id);
            Mage::throwException(
                Mage::helper('bitcoin')->__(
                    "{$exc->getMessage()}\n We're sorry, an error has occurred while completing your request. Please refresh the page to try again. If the error persists, please send us an email at support@yellowpay.co"
                )
            );
        }
    }

    /**
     * check yellow invoice status over Yellow API
     *
     * @param integer $id
     * @return boolean
     *
     */
    public function checkInvoice($id)
    {
        $url = $this->server_root . str_replace("[id]", $id, $this->api_uri_check_payment);
        $nonce = round(microtime(true) * 1000);
        $message = $nonce . $url;
        $private_key = Mage::helper('core')->decrypt($this->getConfiguration("private_key"));
        $hash = hash_hmac("sha256", $message, $private_key, false);
        $http_client = $this->getHTTPClient();
        $http_client->setHeaders($this->getHeaders($nonce, $hash));
        $http_client->setMethod("GET")->setUri($url);
        try {
            $body = $http_client->request()->getBody();
            $data = json_decode($body, true);
            Mage::getSingleton('core/session')->setData('check_invoice', $data);
            return $data;
        } catch (Exception $exc) {
            $this->log($exc->getMessage(), $id);
            $this->log("EXCEPTION:" . json_encode($exc), $id);
            Mage::throwException(
                Mage::helper('bitcoin')->__(
                    "We're sorry, an error has occurred while completing your request. Please refresh the page to try again. If the error persists, please send us an email at support@yellowpay.co\n" . $exc->getMessage()
                )
            );
        }
        return false;
    }

    /**
     * check yellow invoice status over Yellow API
     *
     * @param integer $id
     * @return boolean
     *
     */

    public function checkInvoiceStatus($id)
    {
        $data = $this->checkInvoice($id);
        if (!is_array($data)) {
            Mage::throwException(
                Mage::helper('bitcoin')->__(
                    "We're sorry, an error has occurred while completing your request. Please refresh the page to try again. If the error persists, please send us an email at support@yellowpay.co\n line: " . __LINE__
                )
            );
        }
        $order = $this->getOrder();
        switch ($data["status"]) {
            case $data["status"] == "new":
                $this->log("Status page accessed for a new invoice.", $id);
                $this->log("Nothing to do. Redirecting back to the payment page.", $id);
                break;
            case $data["status"] == "paid" :
                $commentHistory = $order->addStatusHistoryComment(
                    "Payment confirmed. Invoice Id: " . $data["id"],
                    $this->getSuccessStatus()
                );
                $commentHistory->setIsVisibleOnFront(1);
                $order->sendNewOrderEmail();
                $order->save();
                Mage::getResourceModel("bitcoin/ipn")->MarkAsPaid($id);
                /* create an invoice */
                $invoiceModel = Mage::getModel('sales/order_invoice_api');
                $invoice_id = $invoiceModel->create($order->getIncrementId(), array());
                $invoiceModel->capture($invoice_id);
                break;
            case $data["status"] == "authorizing":
                $commentHistory = $order->addStatusHistoryComment(
                    "Authorizing payment. This typically takes 10 minutes. Invoice Id: {$data['id']}",
                    Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW
                );
                $commentHistory->setIsVisibleOnFront(1);
                $order->save();
                Mage::getResourceModel("bitcoin/ipn")->MarkAsAuthorizing($id);
                break;
            case $data["status"] == "refund_owed" :
                $order->setState(Mage_Sales_Model_Order::STATE_NEW);
                $this->log("refund_owed order", $id);
                Mage::getResourceModel("bitcoin/ipn")->MarkAsRefundOwed($id);
                $message = "The bitcoin payment invoice received an incorrect payment. To request a refund please contact support@yellowpay.co and include your ".$order->getIncrementId()." order number as well as the invoice id " . $data['id'];
                $commentHistory = $order->addStatusHistoryComment(
                    $message,
                    $this->getFailedStatus()
                );
                $commentHistory->setIsVisibleOnFront(1);
                $order->save();
                break;
            case $data["status"] == "refund_requested" :
                $order->setState(Mage_Sales_Model_Order::STATE_NEW);
                if (!$order->canCancel()) {
                    $this->log("Unable to cancel order. Order #" . $order->getIncrementId(), $id);
                } else {
                    $this->log("refund_requested order", $id);
                    Mage::getResourceModel("bitcoin/ipn")->MarkAsRefundRequested($id);
                    $commentHistory = $order->addStatusHistoryComment(
                        "Your request for a refund has been received! We'll be in touch soon. In the mean time, you can reach us at support@yellowpay.co",
                        $this->getFailedStatus()
                    );
                    $commentHistory->setIsVisibleOnFront(1);
                    $order->cancel();
                    $order->save();
                    $this->log("Order cancelled. Order #" . $order->getIncrementId(), $id);
                }
                break;
            case $data["status"] === "failed":
            case $data["status"] === "expired":
                $order->setState(Mage_Sales_Model_Order::STATE_NEW);
                if (!$order->canCancel()) {
                    $this->log("Unable to cancel order. Order #" . $order->getIncrementId(), $id);
                } else {
                    $this->log("Order expired : " . $order->getIncrementId(), $id);
                    Mage::getResourceModel("bitcoin/ipn")->MarkAsExpired($id);
                    $commentHistory = $order->addStatusHistoryComment(
                        "The bitcoin payment invoice has expired, please place your order again to receive a new invoice.",
                        $this->getFailedStatus()
                    );
                    $commentHistory->setIsVisibleOnFront(1);
                    $order->cancel();
                    $order->save();
                    $this->log("Order cancelled. Order #" . $order->getIncrementId(), $id);
                }
                break;
            default:
                return false;
                break;
        }
        return $data["status"];
    }

    /**
     *
     * @param Mage_Sales_Model_Order $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     *
     * @return Mage_Sales_Model_Order
     * @throws \Exception
     */
    public function getOrder()
    {
        if (!$this->order) {
            $session = Mage::getSingleton('checkout/session');
            if (!$session->getLastRealOrderId()) {
                throw new \Exception("Order id can't be null ", 500);
            }
            $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
            $this->order = $order;
        }
        return $this->order;
    }

    /**
     * read from magento configuration
     *
     * @param string $param
     * @return type
     */
    public function getConfiguration($param)
    {
        return $this->getConfigData($param);
    }

    /**
     * get HTTP client
     *
     * @return \Yellow_Bitcoin_Model_Http
     */
    private function getHTTPClient()
    {
        return new Yellow_Bitcoin_Model_Http();
    }

    /**
     * prepare HTTP Headers
     *
     * @param String $nonce
     * @param String $signature
     * @return string
     */
    private function getHeaders($nonce, $signature)
    {
        //current platform information
        $helper   = Mage::helper("bitcoin");
        $platform = $helper->getPlatformVersion();
        $plugin   = $helper->getModuleVersion();

        $headers = array(
            "Content-type:application/json",
            "API-Key:" . Mage::helper('core')->decrypt($this->getConfiguration('public_key')),
            "API-Nonce:$nonce",
            "API-Sign:$signature",
            "API-Platform:" . $platform ,
            "API-Plugin:" . $plugin
        );
        return $headers;
    }

    /**
     * returns success status
     *
     * @return String
     */
    public function getSuccessStatus()
    {
        return Mage_Sales_Model_Order::STATE_PROCESSING;
    }

    /**
     * returns failed status
     *
     * @return String
     */
    public function getFailedStatus()
    {
        return Mage_Sales_Model_Order::STATE_CANCELED;
    }

    /**
     *
     * @param string $invoiceIncrementId
     * @return boolean
     */
    public function captureInvoice($invoiceIncrementId)
    {
        return Mage::getModel("sales/order_invoice_api")->capture($invoiceIncrementId);
    }
    
    /**
     *
     * clear session data
     *
     * @return boolean
     */
    public function clearSessionData()
    {
        Mage::getSingleton('core/session')->unsetData("invoice");
        Mage::getSingleton('core/session')->unsetData("has_invoice");
        Mage::getSingleton('core/session')->unsetData("check_invoice");
        return true;
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

    /***
     * return the configured server root
     * @return string
     */
    public function getConfiguredServerRoot()
    {
        return $this->server_root;
    }

}
