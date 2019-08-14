<?php
/**
 * Magento Module developed by NoStress Commerce
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@nostresscommerce.cz so we can send you a copy immediately.
 *
 * @copyright Copyright (c) 2012 NoStress Commerce (http://www.nostresscommerce.cz)
 *
 */

/**
 *
 * @category Nostress
 * @package Nostress_Aukro
 */

$installer = $this;

$installer->startSetup();

$installer->run("

DROP TABLE IF EXISTS {$this->getTable('nostress_aukro_attribute_mapping')};
CREATE TABLE {$this->getTable('nostress_aukro_attribute_mapping')} (
  `code` int(11) unsigned NOT NULL,
  `label` varchar(255),
  `predefined` varchar(1000),
  `magento` varchar(255),
  `type` varchar(255),
  `limit` int(11) unsigned,
  `postproc` varchar(255),
  `path` varchar(255) default 'ITEM ROOT',
  `description` varchar(1000),
  `prefix` varchar(255),
  `constant` varchar(255),
  `translate` varchar(1000),
  `suffix` varchar(255),
  `eppav` varchar(255),
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->getConnection()->addColumn(
    $this->getTable('nostress_aukro_category_mapping'),
    'status',
    'int(1) NULL AFTER `attributes`'
);

$installer->endSetup();
