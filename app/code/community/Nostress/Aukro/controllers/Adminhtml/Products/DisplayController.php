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
 */

/**
 *
 * @category Nostress
 * @package Nostress_Aukro
 */
 
class Nostress_Aukro_Adminhtml_Products_DisplayController extends Mage_Adminhtml_Controller_Action
{
	const DEF_STORE_ID = 1;
	protected $_helper;
	
	protected function _initAction() {
		$this->loadLayout();
		$this->_title($this->__('Aukro connector'));
		
		/**
		* Set active menu item
		*/
		$this->_setActiveMenu('sales/aukro/display_products');
		/**
		* Add breadcrumb item
		*/
		$this->_addBreadcrumb(Mage::helper('aukro')->__('Products Exposing'), Mage::helper('aukro')->__('Products Exposing'));
		
		return $this;
	}
	
	
	public function indexAction()
	{
		try
    	{
    	    $this->_initAction();
    	    $this->_title($this->__('Products Exposing'));
    	    $this->renderLayout();
    	}
    	catch (Exception  $e)
    	{
    	    if($this->_checkFlatCatalog($e)) {
        		$message = $this->helper()->__("Grid load failed:");
            	$this->_getSession()->addError($message. $e->getMessage());
            	$this->renderLayout();
    	    }
     	}
	}
	
	public function displayedAction()
	{
	    try
	    {
	        $this->_initAction();
	        $this->_title($this->__('Exposed Products'));
	        $this->renderLayout();
	    }
	    catch (Exception  $e)
	    {
	        if($this->_checkFlatCatalog($e)) {
    	        $message = $this->helper()->__("Grid load failed:");
    	        $this->_getSession()->addError($message. $e->getMessage());
    	        $this->renderLayout();
	        }
	    }
	}
	
	protected function _checkFlatCatalog( Exception $e) {
	    if( $e->getCode() == Mage::helper('aukro/data_loader')->getFlatCatalogErrorCode()) {
	        Mage::helper('aukro/data_loader')->reindexFlatCatalogs();
	        $this->_redirect( '*/*/*');
	        return false;
	    } else {
	        return true;
	    }
	}
	
	public function refreshAction() {
	    
	    try {
	        $productApi = Mage::getModel( 'aukro/webapi_product');
	        list( $countClosed, $countReexposed) = $productApi->refresh();
	        if( $countClosed) {
	            $this->_getSession()->addSuccess($this->__('Total of %d product(s) were closed on aukro.', $countClosed));
	        } else {
	            $this->_getSession()->addSuccess($this->__('No products were closed on aukro.', $countClosed));
	        }
	        if( $countReexposed) {
	            $this->_getSession()->addSuccess($this->__('Total of %d product(s) were re-exposed on aukro.', $countReexposed));
	        } else {
	            $this->_getSession()->addSuccess($this->__('No products were re-exposed on aukro.', $countReexposed));
	        }
	    } catch (Exception $e) {
	        $this->_getSession()->addError($e->getMessage());
	    }
	    $this->_redirectReferer();
	}
	
	public function massAukroUploadAction()
	{
		$productIds = $this->getRequest()->getParam('productIds');
		$storeId = $this->getRequest()->getParam('store',self::DEF_STORE_ID);
		$dryRun = (bool) $this->getRequest()->getParam( 'dryrun', false);
		
		if (!is_array($productIds)) {
			$this->_getSession()->addError($this->__('Please select product(s)'));
		}
		else {
			try {
				$count = Mage::getSingleton("aukro/unit_control")->upload($productIds,$storeId, $dryRun);
				if( $dryRun) {
				    $this->_getSession()->addSuccess($this->__('Total of %d products(s) were successfully validated.', $count));
				} else {
				    $this->_getSession()->addSuccess($this->__('Total of %d products(s) were successfully uploaded to aukro.', $count));
				}
			} catch (Exception $e) {
				$this->_getSession()->addError($e->getMessage());
			}
		}
		$this->_redirect('*/*/index');
	}
	
	public function massAukroRemoveAction()
	{
	    $productIds = $this->getRequest()->getParam('productIds');
	    $storeId = $this->getRequest()->getParam('store',self::DEF_STORE_ID);
	    if (!is_array($productIds)) {
	        $this->_getSession()->addError($this->__('Please select product(s)'));
	    }
	    else {
	        try {
                $productApi = Mage::getModel( 'aukro/webapi_product');
                $count = $productApi->remove( $productIds);
	            $this->_getSession()->addSuccess($this->__('Total of %d products(s) were successfully removed from aukro.', $count));
	        } catch (Exception $e) {
	            $this->_getSession()->addError($e->getMessage());
	        }
	    }
	    $this->_redirect('*/*/displayed');
	}
	
	public function massAukroUpdateAction()
	{
	    $productIds = $this->getRequest()->getParam('productIds');
	    $storeId = $this->getRequest()->getParam('store',self::DEF_STORE_ID);
	    if (!is_array($productIds)) {
	        $this->_getSession()->addError($this->__('Please select product(s)'));
	    }
	    else {
	        try {
	            $count = Mage::getSingleton("aukro/unit_control")->upload($productIds,$storeId, false, true);
	            $this->_getSession()->addSuccess($this->__('Total of %d products(s) were successfully updated on aukro.', $count));
	        } catch (Exception $e) {
	            $this->_getSession()->addError($e->getMessage());
	        }
	    }
	    $this->_redirect('*/*/displayed');
	}
	
	protected function helper()
	{
		if (!isset($this->_helper))
			$this->_helper = Mage::helper('aukro');
		return $this->_helper;
	}
	
}