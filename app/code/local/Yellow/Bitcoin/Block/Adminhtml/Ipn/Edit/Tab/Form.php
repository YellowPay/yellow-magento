<?php

class Yellow_Bitcoin_Block_Adminhtml_Ipn_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {

        $form = new Varien_Data_Form();
        $this->setForm($form);
        $fieldset = $form->addFieldset("bitcoin_form", array("legend" => Mage::helper("bitcoin")->__("Item information")));


        $fieldset->addField("id", "text", array(
            "label" => Mage::helper("bitcoin")->__("id"),
            "class" => "required-entry",
            "required" => true,
            "name" => "id",
        ));

        $fieldset->addField("quote_id", "text", array(
            "label" => Mage::helper("bitcoin")->__("quote_id"),
            "name" => "quote_id",
        ));

        $fieldset->addField("order_id", "text", array(
            "label" => Mage::helper("bitcoin")->__("order_id"),
            "name" => "order_id",
        ));

        $fieldset->addField("invoice_id", "text", array(
            "label" => Mage::helper("bitcoin")->__("invoice_id"),
            "name" => "invoice_id",
        ));

        $fieldset->addField("url", "text", array(
            "label" => Mage::helper("bitcoin")->__("url"),
            "name" => "url",
        ));

        $fieldset->addField("raw_body", "textarea", array(
            "label" => Mage::helper("bitcoin")->__("raw_body"),
            "name" => "raw_body",
        ));

        $fieldset->addField("status", "text", array(
            "label" => Mage::helper("bitcoin")->__("status"),
            "name" => "status",
        ));

        $fieldset->addField("address", "text", array(
            "label" => Mage::helper("bitcoin")->__("address"),
            "name" => "address",
        ));

        $fieldset->addField("invoice_price", "text", array(
            "label" => Mage::helper("bitcoin")->__("invoice_price"),
            "name" => "invoice_price",
        ));

        $fieldset->addField("invoice_ccy", "text", array(
            "label" => Mage::helper("bitcoin")->__("invoice_ccy"),
            "name" => "invoice_ccy",
        ));

        $fieldset->addField("base_price", "text", array(
            "label" => Mage::helper("bitcoin")->__("base_price"),
            "name" => "base_price",
        ));

        $dateFormatIso = Mage::app()->getLocale()->getDateTimeFormat(
            Mage_Core_Model_Locale::FORMAT_TYPE_SHORT
        );

        $fieldset->addField('base_ccy', 'date', array(
            'label' => Mage::helper('bitcoin')->__('base_ccy'),
            'name' => 'base_ccy',
            'time' => true,
            'image' => $this->getSkinUrl('images/grid-cal.gif'),
            'format' => $dateFormatIso
        ));
        $dateFormatIso = Mage::app()->getLocale()->getDateTimeFormat(
            Mage_Core_Model_Locale::FORMAT_TYPE_SHORT
        );

        $fieldset->addField('server_time', 'date', array(
            'label' => Mage::helper('bitcoin')->__('server_time'),
            'name' => 'server_time',
            'time' => true,
            'image' => $this->getSkinUrl('images/grid-cal.gif'),
            'format' => $dateFormatIso
        ));
        $dateFormatIso = Mage::app()->getLocale()->getDateTimeFormat(
            Mage_Core_Model_Locale::FORMAT_TYPE_SHORT
        );

        $fieldset->addField('expiration_time', 'date', array(
            'label' => Mage::helper('bitcoin')->__('expiration_time'),
            'name' => 'expiration_time',
            'time' => true,
            'image' => $this->getSkinUrl('images/grid-cal.gif'),
            'format' => $dateFormatIso
        ));
        $dateFormatIso = Mage::app()->getLocale()->getDateTimeFormat(
            Mage_Core_Model_Locale::FORMAT_TYPE_SHORT
        );

        $fieldset->addField('created_at', 'date', array(
            'label' => Mage::helper('bitcoin')->__('created_at'),
            'name' => 'created_at',
            'time' => true,
            'image' => $this->getSkinUrl('images/grid-cal.gif'),
            'format' => $dateFormatIso
        ));
        $dateFormatIso = Mage::app()->getLocale()->getDateTimeFormat(
            Mage_Core_Model_Locale::FORMAT_TYPE_SHORT
        );

        $fieldset->addField('updated_at', 'date', array(
            'label' => Mage::helper('bitcoin')->__('updated_at'),
            'name' => 'updated_at',
            'time' => true,
            'image' => $this->getSkinUrl('images/grid-cal.gif'),
            'format' => $dateFormatIso
        ));
        $fieldset->addField("hash", "text", array(
            "label" => Mage::helper("bitcoin")->__("hash"),
            "name" => "hash",
        ));


        if (Mage::getSingleton("adminhtml/session")->getIpnData()) {
            $form->setValues(Mage::getSingleton("adminhtml/session")->getIpnData());
            Mage::getSingleton("adminhtml/session")->setIpnData(null);
        } elseif (Mage::registry("ipn_data")) {
            $form->setValues(Mage::registry("ipn_data")->getData());
        }
        return parent::_prepareForm();
    }
}
