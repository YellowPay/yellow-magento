<?php

class Yellow_Bitcoin_Block_Adminhtml_Ipn_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {

        parent::__construct();
        $this->_objectId = "id";
        $this->_blockGroup = "bitcoin";
        $this->_controller = "adminhtml_ipn";
        $this->_updateButton("save", "label", Mage::helper("bitcoin")->__("Save Item"));
        $this->_updateButton("delete", "label", Mage::helper("bitcoin")->__("Delete Item"));

        $this->_addButton("saveandcontinue", array(
            "label" => Mage::helper("bitcoin")->__("Save And Continue Edit"),
            "onclick" => "saveAndContinueEdit()",
            "class" => "save",
        ), -100);


        $this->_formScripts[] = "

							function saveAndContinueEdit(){
								editForm.submit($('edit_form').action+'back/edit/');
							}
						";
    }

    public function getHeaderText()
    {
        if (Mage::registry("ipn_data") && Mage::registry("ipn_data")->getId()) {

            return Mage::helper("bitcoin")->__("Edit Item '%s'", $this->htmlEscape(Mage::registry("ipn_data")->getId()));

        } else {

            return Mage::helper("bitcoin")->__("Add Item");

        }
    }
}