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
Class Yellow_Bitcoin_Model_Log extends Mage_Core_Model_Abstract
{

    const DATABASE_ENABLED_CONFIG_PATH = "payment/bitcoin/db_log";

    public function _construct()
    {
        $this->_init('bitcoin/log');
        return parent::_construct();
    }


    /**
     * log message to database
     * @param $message
     * @param null $invoice_id
     * @return bool|record id
     */
    public function logMessage($message, $invoice_id = null)
    {
        $enabled = Mage::getStoreConfig(self::DATABASE_ENABLED_CONFIG_PATH);
        if ($enabled == true) {
            $timezone = Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE);
            $now = new \DateTime("now", new \DateTimeZone($timezone));
            $this->setData("message", $message);
            $this->setData("created_at", $now->format("Y-m-d H:i:s"));
            if ($invoice_id) {
                $this->setData("invoice_id", $invoice_id);
            }
            $this->save();
            return $this->getData("id");
        } else {
            return false;
        }
    }


}
