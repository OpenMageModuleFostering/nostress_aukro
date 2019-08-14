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

$installer = Mage::getResourceModel('catalog/setup','core_setup');
$installer->startSetup();
	    	   
$data=array(
    'type'=>'varchar',
    'input'=>'text',
    'label'=>'ID produktu na aukru',
    'group' => 'Aukro',
    'required' => 0,
    'used_in_product_listing' => true,
);

$installer->addAttribute('catalog_product','aukro_product_id',$data);
$installer->endSetup();
