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
class Yellow_Bitcoin_Block_Sales_Order_Fee extends Mage_Core_Block_Template
{

    protected $_template = 'bitcoin/checkout/total/fee.phtml';

    /**
     * Get label cell tag properties
     *
     * @return string
     */
    public function getLabelProperties()
    {
        return $this->getParentBlock()->getLabelProperties();
    }

    /**
     * Get order store object
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return $this->getParentBlock()->getOrder();
    }

    /**
     * Get totals source object
     *
     * @return Mage_Sales_Model_Order
     */
    public function getSource()
    {
        return $this->getParentBlock()->getSource();
    }

    /**
     * Get value cell tag properties
     *
     * @return string
     */
    public function getValueProperties()
    {
        return $this->getParentBlock()->getValueProperties();
    }

    /**
     * 
     * @return Yellow_Bitcoin_Block_Sales_Order_Total
     */
    public function initTotals()
    {
        if ((float) $this->getOrder()->getYellowFee()) {
            $source = $this->getSource();
            $value  = $source->getYellowFee();
            $label  = "Transaction Fee" ;

            $this->getParentBlock()->addTotal(new Varien_Object(array(
                'code'   => 'yellow_fee',
                'strong' => false,
                'label'  => Mage::helper('bitcoin')->__($label),
                'value'  => $source instanceof Mage_Sales_Model_Order_Creditmemo ? - $value : $value
            )));
        }

        return $this;
    }

}
