<?php

Class Yellow_Bitcoin_Model_Bitcoin extends Mage_Payment_Model_Method_Abstract {

    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = 'bitcoin';

    /**
     * Payment Method features
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
    //protected $_infoBlockType = 'bitcoin/info_bitcoin';
    private $server_root = "https://yolanda-perkins.herokuapp.com/";
    private $api_uri_create_invoice = "api/invoice/";
    private $api_uri_check_payment  = "api/invoice/[id]/";
    private $order;
    
    
    
    public function isAvailable( $quote = null ) {
        parent::isAvailable($quote);
        $quoteCurrency = $quote->getData("quote_currency_code");
        $currencies = Mage::getStoreConfig('payment/bitcoin/currencies');
        $currencies = array_map('trim', explode(',', $currencies));
        return array_search($quoteCurrency, $currencies) !== false;
    }
    /**
     * Get instructions text from config
     *
     * @return string
     */
    public function getInstructions() {
        return trim($this->getConfigData('instructions'));
    }

    /**
     * @return boolean
     */
    public function canUseCheckout() {
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
    public function isApiKeyConfigured() {
        $public_key = Mage::getStoreConfig('payment/bitcoin/public_key');
        $private_key = Mage::getStoreConfig('payment/bitcoin/private_key');
        return (!empty($private_key) && !empty($public_key));
    }

    public function authorize(Varien_Object $payment, $amount) {
        if (!Mage::getStoreConfig('payment/bitcoin/fullscreen')) {
            return $this->CheckForPayment($payment);
        } else {
            return $this->CreateInvoiceAndRedirect($payment);
        }
    }

    /**
     * @param $payment
     * @param $amount
     *
     * @return Yellow_Bitcoin_Model_Bitcoin
     */
    public function CreateInvoiceAndRedirect($payment) {
        $order = $payment->getOrder();
        $orderId = $order->getIncrementId();
        $this->createInvoice($order);
        $payment->setIsTransactionPending(true); // status will be PAYMENT_REVIEW instead of PROCESSING
        $invoiceId = Mage::getModel('sales/order_invoice_api')->create($orderId, array());
        return $this;
    }

    /**
     * @param Varien_Object $payment
     *
     * @return Yellow_Bitcoin_Model_Bitcoin
     */
    public function CheckForPayment($payment) {
        $quoteId = $payment->getOrder()->getQuoteId();
        $ipn = Mage::getModel('bitcoin/ipn');

        if (!$ipn->GetQuotePaid($quoteId)) {
            // This is the error that is displayed to the customer during checkout.
            Mage::throwException("Order not paid for.  Please pay first and then Place your Order.");
            Mage::log('Order not paid for. Please pay first and then Place Your Order.', Zend_Log::CRIT, 'yellow.log');
        } else if (!$ipn->GetQuoteComplete($quoteId)) {
            // order status will be PAYMENT_REVIEW instead of PROCESSING
            $payment->setIsTransactionPending(true);
        } else {
            $this->MarkOrderPaid($payment->getOrder());
        }

        return $this;
    }

    /**
     * computes a unique hash determined by the contents of the cart
     *
     * @param string $quoteId
     *
     * @return boolean|string
     */
    public function getQuoteHash($quoteId) {
        $quote = Mage::getModel('sales/quote')->load($quoteId, 'entity_id');
        if (!$quote) {
            Mage::log('getQuoteTimestamp: quote not found', Zend_Log::ERR, 'yellow.log');

            return false;
        }

        // encode items
        $items = $quote->getAllItems();
        $latest = NULL;
        $description = '';

        foreach ($items as $i) {
            $description.= 'i' . $i->getItemId() . 'q' . $i->getQty();
            // could encode $i->getOptions() here but item ids are incremented if options are changed
        }

        $hash = base64_encode(hash_hmac('sha256', $description, $quoteId));
        $hash = substr($hash, 0, 30); // fit it in posData maxlen

        return $hash;
    }

    public function getOrderPlaceRedirectUrl() {
        if (Mage::getStoreConfig('payment/bitcoin/fullscreen')) {
            $invoice = Mage::getSingleton('core/session')->getData("invoice");
            return $invoice["url"];
        } else {
            return '';
        }
    }

    public function createInvoice($quote) {
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
        $ipnUrl = Mage::getUrl("bitcoin/index/ipn", array("id" => base64_encode($quote_id)));
        $redirectUrl = Mage::getUrl("bitcoin/index/status");
        $http_client = $this->getHTTPClient();
        $yellow_payment_data = array(
            "base_price" => 0.10,///$base_price,
            "base_ccy"   => $base_ccy,
            "callback"   => $ipnUrl,
            "redirect"   => $redirectUrl
        );
        $post_body = json_encode($yellow_payment_data);
        $nonce = round(microtime(true) * 1000);
        $url = $this->server_root . $this->api_uri_create_invoice;
        $message = $nonce . $url . $post_body;
        $private_key = $this->getConfiguration("private_key");
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
                Mage::getModel("bitcoin/ipn")->saveInvoice($invoice_data);
                /* end saving invoice */
                Mage::getSingleton('core/session')->setData('invoice', $data);
                Mage::getSingleton('core/session')->setData('has_invoice', true);
                return $data;
            } else {
                Mage::getSingleton('core/session')->setData('error', "There has been an error during invoice creation  , please refresh the page");
                $this->log("I had seen an error code {$response->getStatus()}");
                $this->log("response body was :" . json_encode($response->getBody));
                return false;
            }
        } catch (Exception $exc) {
            $this->log($exc->getMessage());
            return false;
        }
    }

    public function checkInvoiceStatus($id) {
        ////  we might need to check the db to see if we had this id before 
        $url = $this->server_root . str_replace("[id]", $id, $this->api_uri_check_payment);
        $nonce = round(microtime(true) * 1000);
        $message = $nonce . $url;
        $private_key = $this->getConfiguration("private_key");
        $hash = hash_hmac("sha256", $message, $private_key, false);
        $http_client = $this->getHTTPClient();
        $http_client->setHeaders($this->getHeaders($nonce, $hash));
        $http_client->setMethod("GET")
                ->setUri($url);
        try {
            $body = $http_client->request()->getBody();
            $data = json_decode($body, true);
            Mage::getSingleton('core/session')->setData('check_invoice', $data);
            if ($data["status"] == "paid" || $data["status"] == "unconfirmed") {
                $order = $this->getOrder();
                $order->addStatusToHistory($this->getSuccessStatus(), "client paid " . $data["status"] , true);
                $order->sendNewOrderEmail();
                $order->save();
                return $data["status"];
            }

            if ($data["status"] === "failed") {
                $order = $this->getOrder();
                $order->addStatusToHistory($this->getFailedStatus(), "client failed to pay", true);
                $order->cancel();
                $order->save();
                return $data["status"];
            }
        } catch (Exception $exc) {
            $this->log($exc->getMessage());
        }
        return false;
    }

    public function getOrderInformations() {
        $order = $this->getOrder();
        $data = array(
            'base_amount' => $this->round($order->getData('base_grand_total')),
            'base_currency' => Mage::app()->getStore()->getBaseCurrencyCode(),
            'customer_amount' => $this->round($order->getData("grand_total")),
            'customer_currency' => $order->getData("order_currency_code"),
            'orderid' => $order->getData("increment_id")
        );
        Mage::getSingleton('core/session')->setData('order_details', $data);
        return $data;
    }

    public function setOrder($order) {
        $this->order = $order;
    }

    public function getOrder() {
        if (!$this->order) {
            $session = Mage::getSingleton('checkout/session');
            if (!$session->getLastRealOrderId()) {
                throw new \Exception(" order Id can't be null ", 500);
            }
            $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
            $this->order = $order;
        }
        return $this->order;
    }

    public function clearCart() {
        $cartHelper = Mage::helper('checkout/cart');
        $items = $cartHelper->getCart()->getItems();

        foreach ($items as $item) {
            $itemId = $item->getItemId();
            $cartHelper->getCart()->removeItem($itemId);
        }
        $cartHelper->getCart()->save();
    }

    private function getConfiguration($param) {
        return $this->getConfigData($param);
    }

    private function getHTTPClient() {
        return new Yellow_Bitcoin_Model_Http();
    }

    private function getHeaders($nonce, $signature) {
        $headers = array(
            "Content-type:application/json",
            "API-Key:" . $this->getConfiguration('public_key'),
            "API-Nonce:$nonce",
            "API-Sign:$signature"
        );
        return $headers;
    }

    public function getSuccessStatus() {
        return Mage_Sales_Model_Order::STATE_PROCESSING;
    }

    public function getFailedStatus() {
        return Mage_Sales_Model_Order::STATE_CANCELED;
    }

    private function log($message) {
        Mage::log($message, Zend_Log::ERR, "yellow.log");
        return true;
    }

    private function round($amount) {
        return round($amount, 2, PHP_ROUND_HALF_EVEN);
    }

}
