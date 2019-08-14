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
 
class Nostress_Aukro_Block_Adminhtml_Orders_Grid extends Mage_Adminhtml_Block_Widget_Grid {
	
  public function __construct()
  {
      parent::__construct();
      $this->setId('aukroOrders');
      $this->setDefaultSort('aukro_order_id');
      $this->setDefaultDir('ASC');
      $this->setSaveParametersInSession(true);
  }
  
    protected function _getCollectionClass()
    {
        return 'sales/order_grid_collection';
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel($this->_getCollectionClass());
        $collection
            ->addFieldToFilter( 'aukro_order_id', array( 'notnull'=>true))
            ->getSelect()
            ->join( array('so' => 'sales_flat_order'), 'main_table.entity_id = so.entity_id', 'aukro_order_id')
            ;
            
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
  
  protected function _prepareColumns()
  {
        $this->addColumn('real_order_id', array(
            'header'=> Mage::helper('sales')->__('Order #'),
            'width' => '80px',
            'type'  => 'text',
            'index' => 'increment_id',
        ));
        $this->addColumn('created_at', array(
            'header' => Mage::helper('sales')->__('Purchased On'),
            'index' => 'created_at',
            'type' => 'datetime',
            'width' => '100px',
        ));

        $this->addColumn('billing_name', array(
            'header' => Mage::helper('sales')->__('Bill to Name'),
            'index' => 'billing_name',
        ));

        $this->addColumn('shipping_name', array(
            'header' => Mage::helper('sales')->__('Ship to Name'),
            'index' => 'shipping_name',
        ));

        $this->addColumn('base_grand_total', array(
            'header' => Mage::helper('sales')->__('G.T. (Base)'),
            'index' => 'base_grand_total',
            'type'  => 'currency',
            'currency' => 'base_currency_code',
        ));

        $this->addColumn('grand_total', array(
            'header' => Mage::helper('sales')->__('G.T. (Purchased)'),
            'index' => 'grand_total',
            'type'  => 'currency',
            'currency' => 'order_currency_code',
        ));

        $this->addColumn('status', array(
            'header' => Mage::helper('sales')->__('Status'),
            'index' => 'status',
            'type'  => 'options',
            'width' => '70px',
            'options' => Mage::getSingleton('sales/order_config')->getStatuses(),
        ));
        
        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) {
            $this->addColumn('action',
                    array(
                            'header'    => Mage::helper('sales')->__('Action'),
                            'width'     => '50px',
                            'type'      => 'action',
                            'getter'     => 'getId',
                            'actions'   => array(
                                    array(
                                            'caption' => Mage::helper('sales')->__('View'),
                                            'url'     => array('base'=>'adminhtml/sales_order/view'),
                                            'field'   => 'order_id'
                                    )
                            ),
                            'filter'    => false,
                            'sortable'  => false,
                            'index'     => 'stores',
                            'is_system' => true,
                    ));
        }
        
    return parent::_prepareColumns();
  }
  
  public function getRowUrl($row)
  {
      if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) {
          return $this->getUrl('adminhtml/sales_order/view', array('order_id' => $row->getId()));
      }
      return false;
  }
  
}