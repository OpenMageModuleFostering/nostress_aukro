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
 
class Nostress_Aukro_Block_Adminhtml_Products_Displayed_Grid extends Nostress_Aukro_Block_Adminhtml_Products_Display_Grid
{
    
    protected function _loadCollection() {
      
      $collection = Mage::getModel('aukro/data_loader_product_aukro')->getGridCollection($this->getGridParams());
      $collection->getSelect()->where( 'aukro_product_id IS NOT NULL');
      $this->setCollection($collection);
      $collection->load();
   }
    
    protected function _prepareMassaction() {
        
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('productIds');
        
        $this->getMassactionBlock()->addItem('aukro_update', array(
            'label'=> Mage::helper('aukro')->__('Aukro Update'),
            'url'  => $this->getUrl('*/*/massAukroUpdate')
        ));
        
        $this->getMassactionBlock()->addItem('aukro_remove', array(
            'label'=> Mage::helper('aukro')->__('Remove'),
            'url'  => $this->getUrl('*/*/massAukroRemove')
        ));
        
        return $this;
    }
}