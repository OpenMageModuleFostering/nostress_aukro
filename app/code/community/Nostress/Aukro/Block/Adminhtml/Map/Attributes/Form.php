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

class Nostress_Aukro_Block_Adminhtml_Map_Attributes_Form extends Mage_Adminhtml_Block_Widget_Form {
	
  	public function __construct()
  	{    
		parent::__construct();
		$this->setShowGlobalIcon(true);
  	}
  	
  	public function _prepareLayout() {
		parent::_prepareLayout();
		$this->setChild('save_button',
			$this->getLayout()->createBlock('adminhtml/widget_button')
				->setData(array(
					'label' => Mage::helper('aukro')->__('Save Mapping'),
					'onclick' => "updateOutput();",
					'class' => 'save'
				))
		);
		$form = new Varien_Data_Form();
		$fieldset = $form->addFieldset('aukro_attributes_map_fieldset', array('legend' => Mage::helper('aukro')->__('Attributes Mapping Table')));		
		
		$fieldset->addType('attributes','Nostress_Aukro_Block_Adminhtml_Map_Attributes_Form_Element');
		
		$attributesCollection = Mage::getModel('aukro/mapping_attribute')->getCollection();
		$attributes = array();
		foreach ($attributesCollection as $item) {
			$attributes[] = array (
				'code'	=> $item->getCode(),
				'label'	=> $item->getLabel(),
				'magento'	=> $item->getMagento(),
				'type'	=> $item->getType(),
				'limit'	=> $item->getLimit(),
				'postproc'	=> $item->getPostproc(),
				'path'	=> $item->getPath(),
				'description'	=> unserialize($item->getDescription()),
				'prefix'	=> $item->getPrefix(),
				'constant'	=> $item->getConstant(),
				'translate'	=> unserialize($item->getTranslate()),
				'suffix'	=> $item->getSuffix(),
				'eppav'	=> $item->getEppav(),
			);
		}
		
		$fieldset->addField('aukro_attributes_map', 'attributes', array(
			'values' => array("attribute" => $attributes),
			'file' => 'xml',
			'submit_url' => $this->getUrl("*/*/saveAttributeMapping"),
			'store_id' => 1,
			'allow_custom_attributes' => 0
		), 'frontend_class')->setRenderer($this->getLayout()->createBlock('aukro/adminhtml_map_attributes_form_renderer_fieldset_element'));
		$this->setForm($form);
  	}
}