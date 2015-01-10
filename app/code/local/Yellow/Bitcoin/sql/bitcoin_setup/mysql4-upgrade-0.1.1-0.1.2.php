<?php
$installer = $this;
$installer->startSetup();
try {

    //yellow-checkout-custom-css
    $checkout_content = '<div id="yellow-checkout-custom-css" style="display: none"></div>';
    // get stores ids
    //$stores = Mage::getModel('core/store')->getCollection()->addFieldToFilter('store_id', array('gt'=>0))->getAllIds();
    // I will make it now one block for all stores
    $stores = array(0);
    foreach ($stores as $store) {
        $block = Mage::getModel('cms/block');
        $block->setTitle('yellow-checkout-custom-css');
        $block->setStores(array($store));
        $block->setIdentifier('yellow-checkout-custom-css');
        $block->setIsActive(1);
        $block->setContent($checkout_content);
        $block->save();
    }


    //yellow-invoice-custom-css
    $invoice_content = '<div id="yellow-invoice-custom-css" style="display: none"></div>';
    // get stores ids
    //$stores = Mage::getModel('core/store')->getCollection()->addFieldToFilter('store_id', array('gt'=>0))->getAllIds();
    // I will make it now one block for all stores
    $stores = array(0);
    foreach ($stores as $store) {
        $block = Mage::getModel('cms/block');
        $block->setTitle('yellow-invoice-custom-css');
        $block->setIdentifier('yellow-invoice-custom-css');
        $block->setStores(array($store));
        $block->setIsActive(1);
        $block->setContent($invoice_content);
        $block->save();
    }
} catch (\Exception $e) {
    Mage::log($e->getMessage(), null, "yellow.log");
    Mage::log($e->getTraceAsString(), null, "yellow.log");
}

$installer->endSetup();
