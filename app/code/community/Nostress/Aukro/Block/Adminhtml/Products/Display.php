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

class Nostress_Aukro_Block_Adminhtml_Products_Display extends Mage_Adminhtml_Block_Widget_Grid_Container {
	
  public function __construct()
  {
  	$this->_controller = 'adminhtml_products_display';
    $this->_blockGroup = 'aukro';
    $this->_headerText = Mage::helper('aukro')->__('Products Exposure');
	parent::__construct();
	
	$this->setTemplate('nostress_aukro/product.phtml');
	$this->removeButton('add');
	
	$this->_addRefreshButton();
  }
  
  protected function _addRefreshButton() {
      $this->_addButton('refresh', array(
              'label'     => Mage::helper('aukro')->__('Refresh Auctions'),
              'onclick'   => "setLocation('".$this->getUrl("*/*/refresh")."');",
              'class'     => 'reload',
      ), -100);
  }
  
/**
     * Prepare button and grid
     *
     * @return Mage_Adminhtml_Block_Catalog_Product
     */
    protected function _prepareLayout()
    {
        $this->setChild('store_switcher',
            $this->getLayout()->createBlock('adminhtml/store_switcher')
                ->setUseConfirm(false)
                ->setSwitchUrl($this->getUrl('*/*/*', array('store'=>null)))
                ->setTemplate('store/switcher.phtml')
        );

        $this->setChild('grid', $this->getLayout()->createBlock('aukro/adminhtml_products_display_grid', 'products_display.grid'));
        return parent::_prepareLayout();
    }
	
}