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

class Nostress_Aukro_Block_Adminhtml_Map_Attributes extends Mage_Adminhtml_Block_Widget_Form_Container {
	
  	public function __construct()
  	{
		$this->_controller = 'adminhtml_map_attributes';
		$this->_blockGroup = 'aukro';
		$this->_headerText = Mage::helper('aukro')->__('Mapped attributes');
    
		parent::__construct();
		
		$this->addClearAttributesButton();
		$this->addReloadAttributesButton();
		$this->addCustomSaveButton();
		
		
		$this->removeButton('back');
		$this->removeButton('save');
	}
  
    public function getFormHtml()
    {
        return $this->getLayout()->createBlock('aukro/adminhtml_map_attributes_form')->toHtml();
    }
	
	protected function addReloadAttributesButton() {
		$this->_addButton('reload_attributes', array(
       		'label'     => Mage::helper('aukro')->__('Load Attributes'),
       		'onclick'   => "setLocation('".$this->getUrl("*/*/reloadattributes")."');",
       		'class'     => 'reload',
      	), -100);
  	}
  	
	protected function addClearAttributesButton() {
		$this->_addButton('clear_attributes', array(
       		'label'     => Mage::helper('aukro')->__('Remove All Attributes'),
       		'onclick'   => "setLocation('".$this->getUrl("*/*/clearattributes")."');",
       		'class'     => 'reload',
      	), -50);
  	}
  	
	protected function addCustomSaveButton() {
		$this->_addButton('custom_save', array(
       		'label'     => Mage::helper('aukro')->__('Save'),
       		'onclick'   => "saveAttributeMapping();",
       		'class'     => 'save',
      	), -100);
  	}
	
}