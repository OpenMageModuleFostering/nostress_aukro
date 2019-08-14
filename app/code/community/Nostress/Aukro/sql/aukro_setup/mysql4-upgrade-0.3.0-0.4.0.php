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

CREATE TABLE IF NOT EXISTS {$this->getTable('nostress_aukro_cache_categorypath')} ( 
	`category_id` int(10) unsigned NOT NULL,
	`store_id` smallint(5) unsigned NOT NULL, 
	`category_path` text CHARACTER SET utf8,
	`ids_path` varchar(255) CHARACTER SET utf8 NOT NULL, 
	`level` int(11) NOT NULL, 
	PRIMARY KEY (`category_id`,`store_id`) 
	)ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS {$this->getTable('nostress_aukro_cache_categories')} ( 
	`product_id` int(10) unsigned NOT NULL, 
	`store_id` smallint(5) unsigned NOT NULL, 
	`categories` text CHARACTER SET utf8, 
	PRIMARY KEY (`product_id`,`store_id`) 
	)ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS {$this->getTable('nostress_aukro_cache_superattributes')} ( 
	`product_id` int(10) unsigned NOT NULL, 
	`store_id` smallint(5) unsigned NOT NULL, 
	`super_attributes` text CHARACTER SET utf8, 
	PRIMARY KEY (`product_id`,`store_id`) 
	)ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS {$this->getTable('nostress_aukro_cache_mediagallery')} ( 
	`product_id` int(10) unsigned NOT NULL, 
	`store_id` smallint(5) unsigned NOT NULL, 
	`media_gallery` text CHARACTER SET utf8, 
	PRIMARY KEY (`product_id`,`store_id`) 
	)ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS {$this->getTable('nostress_aukro_cache_tax')} ( 
	`tax_class_id`  smallint(6) NOT NULL, 
	`store_id` smallint(5) unsigned NOT NULL, 
	`tax_percent` decimal(12,4) NOT NULL, 
	PRIMARY KEY (`tax_class_id`,`store_id`) 
	)ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;
");


$installer->endSetup(); 
