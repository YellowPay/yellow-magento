<?php
class Yellow_Bitcoin_Block_Info extends Mage_Payment_Block_Info
{
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('bitcoin/info/invoice.phtml');
    }
    public function getInvoiceUrl()
    {
        $order       = $this->getInfo()->getOrder();
        if (false === isset($order) || true === empty($order)) {
            return false;
        }
        $id = $order->getId();
        $invoice_url = Mage::getModel("bitcoin/ipn")->load($id , "order_id")->getData("url");
        if($invoice_url){
            return $invoice_url ;
        }else{
            return false;
        }
    }
}