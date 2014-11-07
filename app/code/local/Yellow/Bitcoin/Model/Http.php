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
    Class Yellow_Bitcoin_Model_Http extends Varien_Http_Client
    {

         /**
          * curl options , might be changed later to be configured
          * right now i am using php's default values
          */
        protected $_allowedParams = array(
            'timeout'      => 10, /// this might need to convert to configuration later once needed
            'maxredirects' => CURLOPT_MAXREDIRS,
            'proxy'        => CURLOPT_PROXY,
            'ssl_cert'     => CURLOPT_SSLCERT,
            'userpwd'      => CURLOPT_USERPWD
        );

        public function __construct()
        {
            parent::__construct();
            $adapter = new Varien_Http_Adapter_Curl();
            $adapter->setOptions($this->_allowedParams);
            $this->setAdapter($adapter);
            return $this;
        }
    }