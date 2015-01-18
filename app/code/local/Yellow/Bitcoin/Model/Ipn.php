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
Class Yellow_Bitcoin_Model_Ipn extends Mage_Core_Model_Abstract
{

    public function _construct()
    {
        $this->_init('bitcoin/ipn');
        return parent::_construct();
    }

    /**
     * saves the invoice to db
     *
     */
    public function saveInvoice($invoice)
    {
        $timezone = Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE);
        $now = new \DateTime("now", new \DateTimeZone($timezone));
        $this->setQuoteId(isset($invoice['quoteId']) ? $invoice['quoteId'] : null);
        $this->setOrderId(isset($invoice['orderId']) ? $invoice['orderId'] : null);
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
        $this->setCreatedAt($now->format("Y-m-d H:i:s"));
        $this->setUpdatedAt($now->format("Y-m-d H:i:s"));
        $this->setHash($invoice["hash"]);
        return $this->save();
    }

}
