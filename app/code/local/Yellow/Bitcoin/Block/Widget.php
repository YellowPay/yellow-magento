<?php

class Yellow_Bitcoin_Block_Widget extends Mage_Checkout_Block_Onepage_Payment
{

    /**
     */
    protected function _construct()
    {
        $this->setTemplate('bitcoin/widget.phtml');
        parent::_construct();
    }

    /**
     * @return string
     */
    public function GetQuoteId()
    {
        $quote   = $this->getQuote();
        $quoteId = $quote->getId();

        return $quoteId;
    }

    /**
     * create an invoice and return the url so that widget.phtml can display it
     *
     * @return string
     */
    public function GetWidgetUrl()
    {
        if (!($quote = Mage::getSingleton('checkout/session')->getQuote()) 
            or !($payment = $quote->getPayment())
            or !($instance = $payment->getMethodInstance())
            or ($instance->getCode() != 'bitcoin'))
        {
            return 'no payment';
        }
        if (Mage::getStoreConfig('payment/bitcoin/fullscreen'))
        {
            return 'disabled';
        }
        $quote = $this->getQuote();
        $payment = $quote->getPayment()->getMethodInstance();
        $invoice = $payment->createInvoice($quote);
        return $invoice['url'];
    }
}
