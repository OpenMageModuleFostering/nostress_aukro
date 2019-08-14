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
 
class Nostress_Aukro_Block_Adminhtml_Map_Attributes_Grid extends Mage_Adminhtml_Block_Widget_Grid {
	
  public function __construct()
  {
      parent::__construct();
      $this->setId('aukroMappedAttributes');
      $this->setDefaultSort('category_id');
      $this->setDefaultDir('ASC');
      $this->setSaveParametersInSession(true);
  }
  
  protected function _prepareCollection()
  {
      $collection = new Varien_Data_Collection(); // TODO nacitavanie objednavok z aukra
      $this->setCollection($collection);
      return parent::_prepareCollection();
  }
  
  protected function _prepareColumns()
  {
  		// TODO definovat stlpce, ktore sa budu realne pouzivat
        $this->addColumn('real_order_id', array(
            'header'=> Mage::helper('sales')->__('Attribute #'),
            'width' => '80px',
            'type'  => 'text',
            'index' => 'increment_id',
        ));
        
        $this->addColumn('magento_category_name', array(
            'header'=> Mage::helper('sales')->__('Magento Attribute Name'),
            'type'  => 'text',
            'index' => 'increment_id',
        ));

        $this->addColumn('aukro_category_name', array(
            'header' => Mage::helper('sales')->__('Aukro Attribute Name'),
            'type'  => 'text',
            'index' => 'increment_id',
        ));

    return parent::_prepareColumns();
  }
  
  
	
}