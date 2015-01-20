<?php

class Yellow_Bitcoin_Block_Adminhtml_Log_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId("logGrid");
        $this->setDefaultSort("id");
        $this->setDefaultDir("DESC");
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel("bitcoin/log")->getCollection();
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

        $this->addColumn("message", array(
            "header" => Mage::helper("bitcoin")->__("Message"),
            "index" => "message",
        ));

        $this->addColumn('created_at', array(
            'header' => Mage::helper('bitcoin')->__('created_at'),
            'index' => 'created_at',
            'type' => 'datetime',
        ));
        $this->addColumn("invoice_id", array(
            "header" => Mage::helper("bitcoin")->__("invoice_id"),
            "index" => "invoice_id",
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
        $this->getMassactionBlock()->addItem('remove_log', array(
            'label' => Mage::helper('bitcoin')->__('Remove Log'),
            'url' => $this->getUrl('*/adminhtml_log/massRemove'),
            'confirm' => Mage::helper('bitcoin')->__('Are you sure?')
        ));
        return $this;
    }


}