<?php

class Yellow_Bitcoin_Block_Adminhtml_Log_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {

        $form = new Varien_Data_Form();
        $this->setForm($form);
        $fieldset = $form->addFieldset("bitcoin_form", array("legend" => Mage::helper("bitcoin")->__("Item information")));


        $fieldset->addField("message", "textarea", array(
            "label" => Mage::helper("bitcoin")->__("message"),
            "class" => "required-entry",
            "required" => true,
            "name" => "message",
        ));

        $dateFormatIso = Mage::app()->getLocale()->getDateTimeFormat(
            Mage_Core_Model_Locale::FORMAT_TYPE_SHORT
        );

        $fieldset->addField('created_at', 'date', array(
            'label' => Mage::helper('bitcoin')->__('created_at'),
            'name' => 'created_at',
            "class" => "required-entry",
            "required" => true,
            'time' => true,
            'image' => $this->getSkinUrl('images/grid-cal.gif'),
            'format' => $dateFormatIso
        ));
        $fieldset->addField("invoice_id", "text", array(
            "label" => Mage::helper("bitcoin")->__("invoice_id"),
            "class" => "required-entry",
            "required" => true,
            "name" => "invoice_id",
        ));


        if (Mage::getSingleton("adminhtml/session")->getLogData()) {
            $form->setValues(Mage::getSingleton("adminhtml/session")->getLogData());
            Mage::getSingleton("adminhtml/session")->setLogData(null);
        } elseif (Mage::registry("log_data")) {
            $form->setValues(Mage::registry("log_data")->getData());
        }
        return parent::_prepareForm();
    }
}
