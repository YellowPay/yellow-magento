<?php
$installer = $this;
$installer->startSetup();
$installer->run("
CREATE TABLE `{$installer->getTable('bitcoin/log')}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message` longtext CHARACTER SET utf8 NOT NULL,
  `created_at` datetime NOT NULL,
  `invoice_id` varchar(255) CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1
");
$installer->endSetup();
