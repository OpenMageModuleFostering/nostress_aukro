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
 
class Nostress_Aukro_Adminhtml_Map_AttributesController extends Mage_Adminhtml_Controller_Action {
	
	protected function _initAction() {
		$this->loadLayout();
		$this->_title($this->__('Aukro connector'))
			->_title($this->__('Attributes Mapping'));
		
		/**
		* Set active menu item
		*/
		$this->_setActiveMenu('sales/aukro/mapped_attributes');
		/**
		* Add breadcrumb item
		*/
		$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Attributes Mapping'), Mage::helper('adminhtml')->__('Attributes Mapping'));
		
		return $this;
	}
	
	
	public function indexAction() {
		$this->_initAction()->renderLayout();
	}
	
	public function reloadAttributesAction () {
    	try
    	{
    		$result = Mage::getModel('aukro/mapping_attribute')->reloadAttributes();
            $this->_getSession()->addSuccess(Mage::helper('aukro')->__("Attributes was loaded successfully."));
        }
        catch (Exception  $e)
        {
        	$message = Mage::helper('aukro')->__("Attributes loading failed: ");
        	$this->_getSession()->addError($message. $e->getMessage());
        }
        // go to grid
        $this->_redirect('*/*/');
		
	}
	
	public function clearAttributesAction () {
    	try
    	{
    		$result = Mage::getModel('aukro/mapping_attribute')->clearAttributes();
            $this->_getSession()->addSuccess(Mage::helper('aukro')->__("All attributes was removed."));
        }
        catch (Exception  $e)
        {
        	$message = Mage::helper('aukro')->__("Attributes removing failed: ");
        	$this->_getSession()->addError($message. $e->getMessage());
        }
        // go to grid
        $this->_redirect('*/*/');
		
	}
	
	public function saveAttributeMappingAction() {
		try
		{
			$data = $this->getRequest()->getPost();
			$model = Mage::getModel('aukro/mapping_attribute');
			foreach ($data['attributes']['attribute'] as $row) {
				$model->setData('code',$row['code']);
				$model->setData('magento',$row['magento']);
				if ($row['limit'] == '')
					$model->setData('limit',null);
				else
					$model->setData('limit',$row['limit']);
				$postproc = '';
				foreach ($row['postproc'] as $item) {
					$postproc .= $item.',';
				}
				$postproc = rtrim($postproc,',');
				$model->setData('postproc',$postproc);
				$model->setData('prefix',$row['prefix']);
				$model->setData('constant',$row['constant']);
				$model->setData('translate',serialize($row['translate']));
				$model->setData('suffix',$row['suffix']);
				$model->setData('eppav',$row['eppav']);
				$model->save();
			}
            $this->_getSession()->addSuccess(Mage::helper('aukro')->__("Attributes mapping saved."));
		}
        catch (Exception  $e)
        {
        	$message = Mage::helper('aukro')->__("Attributes mapping save failed: ");
        	$this->_getSession()->addError($message. $e->getMessage());
        }
        $this->_redirect('*/*/');
	}
	
}