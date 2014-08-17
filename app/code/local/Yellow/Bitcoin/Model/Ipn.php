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

Class Yellow_Bitcoin_Model_Ipn extends Mage_Core_Model_Abstract {

    public function _construct() {
        $this->_init('bitcoin/ipn');
        return parent::_construct();
    }

    /**
     * saves the invoice to db 
     * 
     */
    public function saveInvoice($invoice) {
        $this->setQuoteId(isset($invoice['quoteId']) ? $invoice['quoteId'] : NULL);
        $this->setOrderId(isset($invoice['orderId']) ? $invoice['orderId'] : NULL);
        $this->setInvoiceId($invoice['invoice_id']);
        $this->setUrl($invoice['url']);
        $this->setrawBody(json_encode($invoice['raw_body']));
        $this->setStatus($invoice['status']);
        $this->setAddress($invoice["address"]);
        $this->setInvoicePrice($invoice["invoice_price"]);
        $this->setInvoiceCcy($invoice["invoice_ccy"]);
        $this->setBasePrice($invoice["base_price"]);
        $this->setBaseCcy($invoice["base_ccy"]);
        $this->setServerTime($invoice["server_time"]);
        $this->setExpirationTime($invoice["expiration_time"]);
        $this->setHash($invoice["hash"]);
        return $this->save();
    }

    /**
     * @param string $quoteId
     * @param array  $statuses
     *
     * @return boolean
     */
    public function GetStatusReceived($quoteId, $statuses) {
        if (!$quoteId) {
            return false;
        }
        $quote = Mage::getModel('sales/quote')->load($quoteId, 'entity_id');
        if (!$quote) {
            Mage::log('quote not found', Zend_Log::WARN, 'yellow.log');
            return false;
        }
        return false;
    }

    /**
     * @param string $quoteId
     *
     * @return boolean
     */
    public function GetQuotePaid($quoteId) {
        return $this->GetStatusReceived($quoteId, array('paid', 'confirmed', 'complete'));
    }

    /**
     * @param string $quoteId
     *
     * @return boolean
     */
    public function GetQuoteComplete($quoteId) {
        return $this->GetStatusReceived($quoteId, array('confirmed', 'complete'));
    }

}
