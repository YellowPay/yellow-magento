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
Class Yellow_Bitcoin_Block_Fullscreen_Widget_Footer extends Yellow_Bitcoin_Block_Fullscreen_Widget_Header
{
    /**
     * @var Mage_Sales_Model_Order
     */
    public $order;

    /**
     * @return sales email address
     */
    public function getContactEmail()
    {
        return Mage::getStoreConfig('trans_email/ident_sales/email');
    }

    /**
     * get customer email
     * @return string
     */
    public function getCustomerEmail()
    {
        if ($this->order instanceof Mage_Sales_Model_Order) {
            $email = $this->order->getCustomerEmail();
        } else {
            $email = "";
        }
        return $email;
    }

    /**
     * get customer email
     * @return string
     */
    public function getCustomerName()
    {
        if ($this->order instanceof Mage_Sales_Model_Order) {
            $name = Mage::getModel("customer/customer")->load($this->order->getCustomerId())->getName();
        } else {
            $name = "";
        }
        return $name;
    }

    /**
     * return billing address
     * @return mixed
     */
    public function getBillingAddress()
    {
        $order = $this->getLastOrder();
        $htmlAddress = $order->getBillingAddress()->format("html");
        return $htmlAddress;
    }

    /**
     * return yellow invoice data
     * @return array
     */
    public function getInvoiceData()
    {
        $order = $this->getLastOrder();

        if (!($order)
            or !($payment = $order->getPayment())
            or !($instance = $payment->getMethodInstance())
            or ($instance->getCode() != 'bitcoin')
        ) {
            return 'no payment';
        }
        if (Mage::getStoreConfig('payment/bitcoin/fullscreen') != 1) {
            return 'disabled';
        }
        $invoice = $instance->getInvoiceData();
        return $invoice;
    }

    /**
     * return last order object
     * @return Mage_Sales_Model_Order $order
     */
    public function getLastOrder()
    {
        $order_id = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        $order = Mage::getModel("sales/order")->loadByIncrementId($order_id);
        $this->order = $order;
        return $this->order;
    }


    /**
     * this was made for future use
     *
     * @param $order
     */
    public function setLastOrder($order)
    {
        $this->order = $order;
    }
}
