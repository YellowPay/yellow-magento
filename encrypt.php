<?php
require_once '/var/www/app/Mage.php';
umask(0);
$app = Mage::app('default');
$data = $_SERVER['argv'][1];
$obj = Mage::getModel('core/encryption');
$helper = Mage::helper('core');
$obj->setHelper($helper);
echo $obj->encrypt($data);
?>
