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

class Nostress_Aukro_Block_Adminhtml_Catalog_Category_Tab_Aukro extends Mage_Adminhtml_Block_Catalog_Form {
    
	protected $_aukrocategory = 0;
	protected $_aukroattributes;
	
	public function _construct()
    {
		parent::_construct ();
        $this->setTemplate('nostress_aukro/form.phtml');
    }
	
	
	public function _prepareLayout() {
		$aukroWebApi = Mage::getModel('aukro/webapi_abstract');
		parent::_prepareLayout();
		$form = new Varien_Data_Form();
		$form->setHtmlIdPrefix('aukro_');
		$fieldset = $form->addFieldset('base_fieldset', array('legend' => Mage::helper('aukro')->__('Aukro'), 'class' => "collapseable"));
		
		$categoryId = Mage::registry('current_category')->getId();
		$aukroValues = Mage::getModel('aukro/mapping_category')->getCollection()->addFieldToFilter('category',$categoryId)->getFirstItem();
		$this->_aukrocategory = $aukroValues->getAukrocategory();
		$this->_aukroattributes = $aukroValues->getAttributes();
		
		$categories = $aukroWebApi->getCategoryData();
		$fieldset->addField('category', 'select', array(
			'label' => Mage::helper('aukro')->__("Aukro category:"),
			'name' => "aukro_category",
			'onchange' => "reloadAukroAttributes(this.options[this.selectedIndex].value,'".$this->getUrl('aukro/adminhtml_map_categories/getAukroCategoryAttributes')."')",
			'values' => Mage::helper('aukro')->formatAukroCategories($categories),
			'value'	=> $aukroValues->getAukrocategory(),
		));
		
		$fieldset->addField('connection_name', 'text', array(
			'label' => Mage::helper('aukro')->__("Connection Name:"),
			'name' => "aukro_connection_name",
			'value'	=> $aukroValues->getConnectionName(),
		));
		
		$fieldset->addField('aukro_display_duration', 'select', array(
			'label' => Mage::helper('aukro')->__("Display Duration:"),
			'name' => "aukro_display_duration",
			'values' => Mage::getModel('aukro/adminhtml_system_config_source_duration')->toOptionArray(),
			'value'	=> $this->_getValue($aukroValues->getDisplayDuration())
		));
		
		$fieldset->addField('aukro_base_unit', 'select', array(
	        'label' => Mage::helper('aukro')->__("Base Unit:"),
	        'name' => "aukro_base_unit",
	        'values' => Mage::getModel('aukro/adminhtml_system_config_source_baseunit')->toOptionArray(),
	        'value'	=> $this->_getValue( $aukroValues->getBaseUnit()),
		));
		
		$fieldset->addField('aukro_auto_display', 'select', array(
			'label' => Mage::helper('aukro')->__("Auto Display Setting:"),
			'name' => "aukro_auto_display",
			'values' => Mage::getModel('aukro/adminhtml_system_config_source_autodisplay')->toOptionArray(),
			'value'	=> $this->_getValue($aukroValues->getAutoDisplay()),
		));
		
		$shipmentData = $aukroWebApi->getShipmentData();
		
		$fieldset->addField('aukro_shipping_payment', 'text', array(
			'label' => Mage::helper('aukro')->__("Shipping and Payment Setting:"),
			'name' => "aukro_shipping_payment",
			'defaultvalues'	=> unserialize($aukroValues->getShippingPayment()),
			'shipment'	=> $shipmentData['shipmentDataList']['item'],
		))->setRenderer($this->getLayout()->createBlock('aukro/adminhtml_catalog_category_tab_aukro_field'));
				
		$aukroAttributesFieldset = $form->addFieldset('aukro_attributes_fieldset', array('legend' => Mage::helper('aukro')->__('Aukro Category Attributes'), 'class' => "collapseable"));
		
		$form->setFieldNameSuffix('general[aukro]');
		$this->setForm($form);
	}
	
	public function getAukroCategory() {
		if ($this->_aukrocategory == 0 || $this->_aukrocategory == null)
			return (-1);
		return $this->_aukrocategory;
	}
	
	public function getAukroAttributes() {
		return $this->_aukroattributes;
	}
	
	protected function _getValue( $value) {
	    if( $value === null) {
	        return -1;
	    } else {
	        return $value;
	    }
	}
	
	
}