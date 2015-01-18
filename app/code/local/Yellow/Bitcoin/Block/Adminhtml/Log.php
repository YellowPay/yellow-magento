<?php


class Yellow_Bitcoin_Block_Adminhtml_Log extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    public function __construct()
    {

        $this->_controller = "adminhtml_log";
        $this->_blockGroup = "bitcoin";
        $this->_headerText = Mage::helper("bitcoin")->__("Log Manager");
        $this->_addButtonLabel = Mage::helper("bitcoin")->__("Add New Item");
        parent::__construct();

    }

}