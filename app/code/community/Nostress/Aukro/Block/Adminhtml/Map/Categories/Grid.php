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
 
class Nostress_Aukro_Block_Adminhtml_Map_Categories_Grid extends Mage_Adminhtml_Block_Widget_Grid {
	
  public function __construct()
  {
      parent::__construct();
      $this->setId('aukroMappedCategories');
      $this->setDefaultSort('category_id');
      $this->setDefaultDir('ASC');
      $this->setSaveParametersInSession(true);
  }
  
	protected function _prepareCollection()
  	{
		$collection = Mage::getModel('aukro/mapping_category')->getCollection();
      	$this->setCollection($collection);
      	return parent::_prepareCollection();
  }
  
  protected function _prepareColumns()
  {

        $this->addColumn('mapping_id', array(
            'header'=> Mage::helper('aukro')->__('#'),
            'width' => '80px',
            'type'  => 'text',
            'index' => 'mapping_id',
        ));
  		
        $category = Mage::getModel('catalog/category');
		$tree = $category->getTreeModel();
		$tree->load();
		$ids = $tree->getCollection()->getAllIds();
		$arr = array();
		foreach ($ids as $id) {
			$category->load($id);
			$arr[$id] = $category->getName();
		}
		asort($arr);
		
        $this->addColumn('category', array(
            'header'=> Mage::helper('aukro')->__('Magento Category'),
            'type'  => 'options',
            'index' => 'category',
        	'options'	=> $arr,
        ));
        
		$categoryDataResponse = Mage::getModel('aukro/webapi_abstract')->getCategoryData();
        $this->addColumn('aukrocategory', array(
            'header'=> Mage::helper('aukro')->__('Aukro Category'),
            'type'  => 'options',
            'index' => 'aukrocategory',
			'options'	=> Mage::helper('aukro')->formatAukroCategories($categoryDataResponse),
        ));
        
        $this->addColumn('connection_name', array(
            'header'=> Mage::helper('aukro')->__('Connection Name'),
            'type'  => 'text',
            'index' => 'connection_name',
        ));
        
        $this->addColumn('display_duration', array(
            'header'=> Mage::helper('aukro')->__('Display Duration'),
    		'type'      => 'options',
            'index' => 'display_duration',
        	'options'	=> Mage::getModel('aukro/adminhtml_system_config_source_duration')->getOptions(),
        ));
        
        $this->addColumn('auto_display', array(
            'header'=> Mage::helper('aukro')->__('Auto Display'),
            'type'  => 'options',
            'index' => 'auto_display',
        	'options'	=> Mage::getModel('aukro/adminhtml_system_config_source_autodisplay')->getOptions(),
        ));
        
    return parent::_prepareColumns();
  }
}