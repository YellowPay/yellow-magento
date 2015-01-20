<?php

class Yellow_Bitcoin_Block_Adminhtml_Ipn_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId("ipnGrid");
        $this->setDefaultSort("id");
        $this->setDefaultDir("DESC");
        $this->setSaveParametersInSession(true);

    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel("bitcoin/ipn")->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn("id", array(
            "header" => Mage::helper("bitcoin")->__("ID"),
            "align" => "right",
            "width" => "50px",
            "type" => "number",
            "index" => "id",
        ));

        $this->addColumn("quote_id", array(
            "header" => Mage::helper("bitcoin")->__("quote_id"),
            "index" => "quote_id",
        ));
        $this->addColumn("order_id", array(
            "header" => Mage::helper("bitcoin")->__("order_id"),
            "index" => "order_id",
        ));
        $this->addColumn("invoice_id", array(
            "header" => Mage::helper("bitcoin")->__("invoice_id"),
            "index" => "invoice_id",
        ));
        $this->addColumn("url", array(
            "header" => Mage::helper("bitcoin")->__("url"),
            "index" => "url",
        ));
        $this->addColumn("status", array(
            "header" => Mage::helper("bitcoin")->__("status"),
            "index" => "status",
        ));
        $this->addColumn("address", array(
            "header" => Mage::helper("bitcoin")->__("address"),
            "index" => "address",
        ));
        $this->addColumn("invoice_price", array(
            "header" => Mage::helper("bitcoin")->__("invoice_price"),
            "index" => "invoice_price",
        ));
        $this->addColumn("invoice_ccy", array(
            "header" => Mage::helper("bitcoin")->__("invoice_ccy"),
            "index" => "invoice_ccy",
        ));
        $this->addColumn("base_price", array(
            "header" => Mage::helper("bitcoin")->__("base_price"),
            "index" => "base_price",
        ));
        $this->addColumn('base_ccy', array(
            'header' => Mage::helper('bitcoin')->__('base_ccy'),
            'index' => 'base_ccy'
        ));
        $this->addColumn('server_time', array(
            'header' => Mage::helper('bitcoin')->__('server_time'),
            'index' => 'server_time',
            'type' => 'datetime',
        ));
        $this->addColumn('expiration_time', array(
            'header' => Mage::helper('bitcoin')->__('expiration_time'),
            'index' => 'expiration_time',
            'type' => 'datetime',
        ));
        $this->addColumn('created_at', array(
            'header' => Mage::helper('bitcoin')->__('created_at'),
            'index' => 'created_at',
            'type' => 'datetime',
        ));
        $this->addColumn('updated_at', array(
            'header' => Mage::helper('bitcoin')->__('updated_at'),
            'index' => 'updated_at',
            'type' => 'datetime',
        ));
        $this->addColumn("hash", array(
            "header" => Mage::helper("bitcoin")->__("hash"),
            "index" => "hash",
        ));
        $this->addExportType('*/*/exportCsv', Mage::helper('sales')->__('CSV'));
        $this->addExportType('*/*/exportExcel', Mage::helper('sales')->__('Excel'));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl("*/*/edit", array("id" => $row->getId()));
    }


    protected function _prepareMassaction()
    {
        /// we don't need mass actions
        return $this;
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('ids');
        $this->getMassactionBlock()->setUseSelectAll(true);

        return $this;

        $this->getMassactionBlock()->addItem('remove_ipn', array(
            'label' => Mage::helper('bitcoin')->__('Remove Ipn'),
            'url' => $this->getUrl('*/adminhtml_ipn/massRemove'),
            'confirm' => Mage::helper('bitcoin')->__('Are you sure?')
        ));
        return $this;
    }


}