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
class Yellow_Bitcoin_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * check if the fullScreen setting is set to yes / no
     * @return bool
     */
    public function isFullScreen()
    {
        return (Mage::getStoreConfig('payment/bitcoin/fullscreen') == 1);
    }

    /***
     * does the secured url has https
     * in another words : does the store has ssl certificate
     * @return bool
     */
    public function doesTheStoreHasSSL()
    {
        $store_id    = Mage::app()->getStore()->getId();
        $secured_url = Mage::getStoreConfig("web/secure/base_url" , $store_id);
        preg_match("/^https:\/\//" , $secured_url , $matches);
        if(count($matches) == 1 ){
            return true;
        }
        return false;
    }


    /**
     * replace https with http in case the store doesn't have ssl certificate
     * @param $url
     * @return mixed
     */
    public function replaceHttps($url)
    {
        return str_replace("https://" , "http://" , $url);
    }

    /**
     * return platform version
     * @return string
     */
    public function getPlatformVersion()
    {
        $platform = "Magento " . Mage::getEdition() ." ". Mage::getVersion();
        return  $platform;
    }

    /**
     * return  plugin version
     * @return string
     */
    public function getModuleVersion()
    {
        $plugin  = (string) Mage::getConfig()->getNode()->modules->Yellow_Bitcoin->version;
        return $plugin;
    }
}
