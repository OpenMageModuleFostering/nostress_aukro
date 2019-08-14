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

class Nostress_Aukro_Block_Adminhtml_Map_Categories extends Mage_Adminhtml_Block_Widget_Grid_Container {
	
  	public function __construct()
  	{
		$this->_controller = 'adminhtml_map_categories';
		$this->_blockGroup = 'aukro';
		$this->_headerText = Mage::helper('aukro')->__('Mapped Categories');
    
		parent::__construct();
		
		$this->addCheckMappingButton();
		
		$this->removeButton('add');
	}
  
	protected function addCheckMappingButton() {
		$this->_addButton('check_mapping', array(
       		'label'     => Mage::helper('aukro')->__('Check Mapping'),
       		'onclick'   => "setLocation('".$this->getUrl("*/*/checkmapping")."');",
       		'class'     => 'reload',
      	), -100);
  	}
	
}