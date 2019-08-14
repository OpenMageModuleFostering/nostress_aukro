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
 
class Nostress_Aukro_Adminhtml_OrdersController extends Mage_Adminhtml_Controller_Action {
	
	protected function _initAction() {
		$this->loadLayout();
		$this->_title($this->__('Aukro connector'))
			->_title($this->__('Orders'));
		
		/**
		* Set active menu item
		*/
		$this->_setActiveMenu('sales/aukro/orders');
				/**
		* Add breadcrumb item
		*/
		$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Aukro orders'), Mage::helper('adminhtml')->__('Aukro orders'));
		
		return $this;
	}
	
	
	public function indexAction() {
		$this->_initAction()->renderLayout();
	}
	
	public function refreshOrdersAction() {
	    
	    try {
	        $orderApi = Mage::getModel( 'aukro/webapi_order');
	        list( $countNew, $countPaid) = $orderApi->refreshOrders();
	        Mage::log( Mage::helper('aukro')->__('Total of %d order(s) were successfully created from aukro.', $countNew), null, 'aukro.log');
	        Mage::log( Mage::helper('aukro')->__('Total of %d order(s) were successfully paid from aukro.', $countPaid), null, 'aukro.log');
	        
	        $this->_getSession()->addSuccess($this->__('Total of %d order(s) were successfully created from aukro.', $countNew));
	        if( $countPaid > 0) {
	            $this->_getSession()->addSuccess($this->__('Total of %d order(s) were successfully paid from aukro.', $countPaid));
	        }
	    } catch (Exception $e) {
	        $this->_getSession()->addError($e->getMessage());
	    }
	    $this->_redirect('*/*/index');
	}
	
}