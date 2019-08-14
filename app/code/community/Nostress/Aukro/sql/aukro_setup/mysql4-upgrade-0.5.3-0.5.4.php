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

// display_duration
$data = array(
    'type'=>'int',
    'input'=>'select',
    'label'=>'Délka trvání aukce',
    'group' => 'Aukro',
    'required' => 0,
    'default' => -1,
    'sort_order' => 5,
    'source' => 'aukro/adminhtml_system_config_source_duration',
    'used_in_product_listing' => true,
);
$installer->addAttribute('catalog_product','display_duration',$data);

// base_unit
$data = array(
    'type'=>'int',
    'input'=>'select',
    'label'=>'Základní měrná jednotka',
    'group' => 'Aukro',
    'required' => 0,
    'default' => -1,
    'sort_order' => 6,
    'source' => 'aukro/adminhtml_system_config_source_baseunit',
    'used_in_product_listing' => true,
);
$installer->addAttribute('catalog_product','base_unit',$data);

// auto_display
$data = array(
    'type'=>'int',
    'input'=>'select',
    'label'=>'Po skončení automaticky opětovně vystavit položky',
    'group' => 'Aukro',
    'required' => 0,
    'default' => -1,
    'sort_order' => 7,
    'source' => 'aukro/adminhtml_system_config_source_autodisplay',
    'used_in_product_listing' => true,
);
$installer->addAttribute('catalog_product','auto_display',$data);

// new_used
$data = array(
    'type'=>'int',
    'input'=>'select',
    'label'=>'Stav zboží',
    'group' => 'Aukro',
    'required' => 0,
    'default' => -1,
    'sort_order' => 8,
    'source' => 'aukro/adminhtml_system_config_source_productnewused',
    'used_in_product_listing' => true,
);
$installer->addAttribute('catalog_product','new_used',$data);

$installer->run("
    ALTER TABLE {$this->getTable('nostress_aukro_category_mapping')}
        ADD COLUMN `base_unit` int(11) DEFAULT -1 AFTER display_duration;
    "
);

$installer->endSetup();

