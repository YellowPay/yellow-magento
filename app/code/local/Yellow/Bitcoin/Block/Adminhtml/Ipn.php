<?php


class Yellow_Bitcoin_Block_Adminhtml_Ipn extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    public function __construct()
    {

        $this->_controller = "adminhtml_ipn";
        $this->_blockGroup = "bitcoin";
        $this->_headerText = Mage::helper("bitcoin")->__("Ipn Manager");
        $this->_addButtonLabel = Mage::helper("bitcoin")->__("Add New Item");
        parent::__construct();

    }

}