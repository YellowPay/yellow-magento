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
    class Yellow_Bitcoin_Block_Fullscreen_Widget_Content extends Yellow_Bitcoin_Block_Widget
    {
        /**
         * create an invoice & return the url of it
         * @return string
         */
        public function GetWidgetUrl()
        {
            $order_id = Mage::getSingleton('checkout/session') ->getLastRealOrderId();
            $order = Mage::getModel("sales/order")->loadByIncrementId($order_id);
            if (!($order)
                or !($payment = $order->getPayment())
                or !($instance = $payment->getMethodInstance())
                or ($instance->getCode() != 'bitcoin')
            ) {
                return 'no payment';
            }
            if (Mage::getStoreConfig('payment/bitcoin/fullscreen') != 1 ) {
                return 'disabled';
            }
            $invoice = $instance->getInvoiceData();
            return $invoice['url'];
        }
    }
