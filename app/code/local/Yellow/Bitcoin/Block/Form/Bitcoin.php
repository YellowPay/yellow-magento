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

/**
 * Block for  Yellow Bitcoin payment method form
 */
class Yellow_Bitcoin_Block_Form_Bitcoin extends Mage_Payment_Block_Form
{

    /**
     * Instructions text
     *
     * @var string
     */
    protected $_instructions;

    const CSS_BLOCK_ID = "yellow-checkout-custom-css";

    /**
     * Block construction. Set block template.
     */
    protected function _construct()
    {
        $class = Mage::getConfig()->getBlockClassName('core/template');
        try {
            $css_block = Mage::getModel('cms/block')->setStoreId(Mage::app()->getStore()->getId())->load(self::CSS_BLOCK_ID);
            if ($css_block->getData("is_active") == 1) {
                $css_output = $css_block->getData("content");
            } else {
                $css_output = "";
            }
        } catch (\Exception $e) {
            $css_output = "";
            Mage::log($e->getMessage() . __LINE__ . " on  " . __FILE__, null, "yellow.log");
            Mage::log($e->getTraceAsString() . __LINE__ . " on  " . __FILE__, null, "yellow.log");
        }


        $logo = new $class;
        $logo->setTemplate('bitcoin/form/logo.phtml');
        $this->setTemplate('bitcoin/form/bitcoin.phtml')
            ->setRedirectMessage(
                Mage::helper('bitcoin')->__('You will be paid via Yellow')
            )
            ->setMethodTitle('')
            ->setMethodLabelAfterHtml($css_output . $logo->toHtml());

        return parent::_construct();
    }

    /**
     * Get instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        return;
        if (is_null($this->_instructions)) {
            $this->_instructions = $this->getMethod()->getInstructions();
        }
        return $this->_instructions;
    }

}
