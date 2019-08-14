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

DROP TABLE IF EXISTS {$this->getTable('nostress_aukro_category_mapping')};
CREATE TABLE {$this->getTable('nostress_aukro_category_mapping')} (
  `mapping_id` int(11) unsigned NOT NULL auto_increment,
  `category` int(11) unsigned NOT NULL,
  `aukrocategory` int(11) unsigned NOT NULL,
  `connection_name` varchar(255),
  `display_duration` int(11),
  `auto_display` int(11),
  `shipping_payment` varchar(1000),
  `attributes` varchar(1000),
  PRIMARY KEY (`mapping_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->endSetup();
