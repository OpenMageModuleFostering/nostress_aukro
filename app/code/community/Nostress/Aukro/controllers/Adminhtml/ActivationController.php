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
* Hlavni kontroler pro exportni intarface
*
* @category Nostress
* @package Nostress_Aukro
*
*/

class Nostress_Aukro_Adminhtml_ActivationController extends Mage_Adminhtml_Controller_Action
{
	protected $_helper;
	
	protected function _initAction()
	{
		$this->loadLayout();
		return $this;
	}
	
	public function indexAction()
	{
		$this->_initAction();
		$block = $this->getLayout()->getBlock('aukro_activation');
		$form = $block->getChild("form");
		if ($block)
		{
			$params = $this->getRequest()->getParams();
			$block->setData($params);
			$form->setData($params);
		}
		$this->renderLayout();
	}
	
	public function activateAction()
	{
		try
		{
			$params = $this->getRequest()->getParams();
			$result = Mage::helper('aukro/data_client')->createLicenseKey($params);
			
			$this->_getSession()->addSuccess($this->__('Aukro Connector has been activated with license key %s .',$result['key']));
		}
		catch (Exception  $e)
		{
			$message = $this->helper()->__("Module activation process failed. Error: ");
			$this->_getSession()->addError($message. $e->getMessage());
			$code = "";
			$urlParam = array();
			if(!empty($params["code"]))
				$urlParam = array("code" => $params["code"]);
			$this->_redirect('*/*/',$urlParam);
			return;
		}
		
        $this->_redirect('aukro/adminhtml_products_display/index');
	}
	
	protected function helper()
	{
		if (!isset($this->_helper))
			$this->_helper = Mage::helper('aukro');
		return $this->_helper;
	}
}