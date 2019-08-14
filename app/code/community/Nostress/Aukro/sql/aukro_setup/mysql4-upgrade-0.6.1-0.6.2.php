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
* @copyright Copyright (c) 2009 NoStress Commerce (http://www.nostresscommerce.cz)
*
*/

/**
* Sql instalation skript
*
* @category Nostress
* @package Nostress_Aukro
*
*/

$installer = $this;
$installer->startSetup();

$installer->run("

DROP TABLE IF EXISTS `nostress_aukro_product`;
CREATE TABLE `nostress_aukro_product` (
  `entity_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `aukro_id` varchar(15) NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  `current` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`entity_id`),
  KEY `product_id_ffk` (`product_id`),
  CONSTRAINT `product_id_ffk` FOREIGN KEY (`product_id`) REFERENCES `catalog_product_entity` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;
        
");

$installer->endSetup();

$productCollection = Mage::getModel( 'catalog/product')->getCollection();
$productCollection
->addAttributeToFilter( 'aukro_product_id', array( 'notnull'=>true))
;
$productCollection->load();
foreach( $productCollection as $product) {
    Mage::getModel( 'aukro/product')->create( $product->aukro_product_id, $product->getId());
}

