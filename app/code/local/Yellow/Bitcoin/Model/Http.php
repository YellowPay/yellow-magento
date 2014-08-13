<?php

Class Yellow_Bitcoin_Model_Http  extends Varien_Http_Client{

   /*
     * curl options , might be changed later to be configured 
     * right now i am using php's default values 
     */
    protected $_allowedParams = array(
        'timeout' => 10,
        'maxredirects' => CURLOPT_MAXREDIRS,
        'proxy' => CURLOPT_PROXY,
        'ssl_cert' => CURLOPT_SSLCERT,
        'userpwd' => CURLOPT_USERPWD
    );
    private $http_verb = "POST";
    

    public function __construct() {
        parent::__construct();
        $adapter = new Varien_Http_Adapter_Curl();
        $adapter->setOptions($this->_allowedParams);
        $this->setAdapter($adapter);
        return $this;
    }
}
