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
class Yellow_Bitcoin_Model_Sales_Quote_Address_Total_Fee extends Mage_Sales_Model_Quote_Address_Total_Abstract{
    
    protected $_code = 'yellow_fee';
 
    public function collect(Mage_Sales_Model_Quote_Address $address)
    {

        parent::collect($address);
 
        /// reset values
        $this->_setAmount(0);
        $this->_setBaseAmount(0);

        $address->setYellowFee(0);
        $address->setBaseYellowFee(0);

        $quote  = $address->getQuote();
        $quote->setYellowFee(0);
        $quote->setBaseYellowFee(0);

        $items = $this->_getAddressItems($address);
        if (!count($items)) {
            return $this; //this makes only address type shipping to come through
        }


        $model = Mage::getModel("bitcoin/bitcoin");
        if($model->canApplyFee($address)){
            $fee            = $model->getYellowFee();
            $totals         = array_sum($address->getAllTotalAmounts());
            //$baseTotals     = array_sum($address->getAllBaseTotalAmounts());
            $fee            = round(($totals * $fee) / (1 - $fee) , 2,  PHP_ROUND_HALF_UP );
            if( $fee > 0 ){
                $baseFee = $address->getQuote()->getStore()->convertPrice($fee, false);
                $address->setYellowFee($fee);
                $address->setBaseYellowFee($baseFee);
                $quote->setYellowFee($fee);
                $quote->setBaseYellowFee($baseFee);
                $address->setGrandTotal($address->getGrandTotal() + $address->getYellowFee());
                $address->setBaseGrandTotal($address->getBaseGrandTotal() + $address->getBaseYellowFee());
            }

        }
        return $this;
    }
 
    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        $fee = $address->getYellowFee();
        $address->addTotal(array(
                'code'=>$this->getCode(),
                'title'=>Mage::helper('bitcoin')->__('Transaction Fee'),
                'value'=> $fee
        ));
        return $this;
    }
}