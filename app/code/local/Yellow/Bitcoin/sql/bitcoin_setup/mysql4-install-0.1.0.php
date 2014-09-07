<?php
$installer = $this;
$installer->startSetup();

$installer->run("
CREATE TABLE IF NOT EXISTS `{$installer->getTable('bitcoin/ipn')}` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `quote_id` int(10) unsigned default NULL,
  `order_id` int(10) unsigned default NULL,
  `invoice_id` varchar(255) NOT NULL,
  `url` varchar(400) NOT NULL,
  `raw_body` VARCHAR( 255 ) NOT NULL,
  `status` varchar(20) NOT NULL,
  `address` VARCHAR( 255 ) NOT NULL,
  `invoice_price` decimal(16,8) NOT NULL,
  `invoice_ccy` varchar(10) NOT NULL,
  `base_price` decimal(16,8) NOT NULL,
  `base_ccy` varchar(10) NOT NULL,
  `server_time` datetime NOT NULL,
  `expiration_time` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `hash` varchar(400) NOT NULL ,
  PRIMARY KEY  (`id`),
  KEY `quote_id` (`quote_id`),
  KEY `order_id` (`order_id`),
  KEY `invoice_id` (`invoice_id`)
) ENGINE=INNODB  DEFAULT CHARSET=utf8 ;
");

$installer->endSetup();
	 