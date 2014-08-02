<?php

class Yellow_Bitcoin_Model_Resource_Ipn extends Mage_Core_Model_Resource_Db_Abstract {

    protected function _construct() {
        $this->_init('bitcoin/ipn', 'id');
    }

}
