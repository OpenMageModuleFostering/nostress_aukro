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
 
class Nostress_Aukro_Adminhtml_Map_CategoriesController extends Mage_Adminhtml_Controller_Action {
	
	protected function _initAction() {
		$this->loadLayout();
		$this->_title($this->__('Aukro connector'))
			->_title($this->__('Categories mapping'));
		
		/**
		* Set active menu item
		*/
		$this->_setActiveMenu('sales/aukro/mapped_categories');
		/**
		* Add breadcrumb item
		*/
		$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Categories mapping'), Mage::helper('adminhtml')->__('Categories mapping'));
		
		return $this;
	}
	
	
	public function indexAction() {
		$this->_initAction()->renderLayout();
	}
	
	public function getAukroCategoryAttributesAction() {
		$categoryId = $_POST["category"];
		$defaultValues = unserialize($_POST["attributes"]);
		$categoryAttributesResponse = Mage::getModel('aukro/webapi_abstract')->getSellFormAttributesForCategory($categoryId);
		$categoryAttributes = $categoryAttributesResponse['sellFormFields']['item'];
		
		$response = '<div class="hor-scroll"><table class="form-list" cellspacing="0">';
		
		foreach ($categoryAttributes as $attributeKey => $params) {
			
			if ($params['sellFormOpt'] == Nostress_Aukro_Model_Webapi_Abstract::MANDATORY)
				$required = '<span class="required">*</span>';
			else
				$required = '';
			
			$response .= '<tr>';
			$response .= '<td class="label"><label>'.$params['sellFormTitle'].$required.'</label></td>';
			$response .= '<td class="value">'.$this->getHtmlInputOption($params,$defaultValues[$params['sellFormId']]).'</td>';
			$response .= '</tr>';
		}
		
		$response .= '</table></div>';
		echo $response;
	}
	
	
	public function getHtmlInputOption($params,$defaultValue) {
		$result = '';

		if ($params['sellFormOpt'] == Nostress_Aukro_Model_Webapi_Abstract::MANDATORY)
			$required = '';
		else
			$required = '';
		
		switch ($params['sellFormType']) {
			case Nostress_Aukro_Model_Webapi_Abstract::STRING :
			case Nostress_Aukro_Model_Webapi_Abstract::INTEGER :
			case Nostress_Aukro_Model_Webapi_Abstract::FLOAT :
				$result .= '<input value="'.$defaultValue.'" class="input-text '.$required.'" type="text" name="general[aukro][aukro_attributes]['.$params['sellFormId'].']">';
				break;
			case Nostress_Aukro_Model_Webapi_Abstract::SELECT :
				$options = explode('|',$params['sellFormDesc']);
				$optionsValues = explode('|',$params['sellFormOptsValues']);
				
				$result .= '<select class="'.$required.'" value="'.$defaultValue.'" name="general[aukro][aukro_attributes]['.$params['sellFormId'].']">';
				
				foreach ($options as $optionKey => $option) {
					if ($defaultValue == $optionsValues[$optionKey])
						$selected = 'selected="selected"';
					else
						$selected = '';
					$result .= '<option '.$selected.' value="'.$optionsValues[$optionKey].'">'.$options[$optionKey].'</option>';
				}
				
				$response .= '</select>';
				break;
			case Nostress_Aukro_Model_Webapi_Abstract::RADIOBUTTON :
				$options = explode('|',$params['sellFormDesc']);
				$optionsValues = explode('|',$params['sellFormOptsValues']);
				
				foreach ($options as $optionKey => $option) {
					$result .= '<input class="'.$required.'" type="radio" name="general[aukro][aukro_attributes]['.$params['sellFormId'].']['.$optionsValues[$optionKey].']" value="'.$optionsValues[$optionKey].'"><label class="normal">'.$options[$optionKey].'</label><br>';
				}
				break;
				
			case Nostress_Aukro_Model_Webapi_Abstract::CHECKBOX :
				$options = explode('|',$params['sellFormDesc']);
				$optionsValues = explode('|',$params['sellFormOptsValues']);
				
				foreach ($options as $optionKey => $option) {
					if (isset($defaultValue[$optionsValues[$optionKey]]))
						$checked = 'checked="checked"';
					else
						$checked = '';
					$result .= '<input class="checkbox '.$required.'" type="checkbox" name="general[aukro][aukro_attributes]['.$params['sellFormId'].']['.$optionsValues[$optionKey].']" value="'.$optionsValues[$optionKey].'" '.$checked.'><label class="normal">'.$options[$optionKey].'</label><br>';
				}
				break;
				
			case Nostress_Aukro_Model_Webapi_Abstract::IMAGE :
				
			case Nostress_Aukro_Model_Webapi_Abstract::TEXTAREA :
					$result .= '<textarea value="'.$defaultValue.'" class="'.$required.'" name="general[aukro][aukro_attributes]['.$params['sellFormId'].']">';
				break;
				
			case Nostress_Aukro_Model_Webapi_Abstract::DATETIME :
				
			case Nostress_Aukro_Model_Webapi_Abstract::DATE :
			default:
				break;
		}
		return $result;
	}
	
	
	public function checkMappingAction () {
    	try
    	{
			$count = Mage::getModel('aukro/mapping_category')->checkCategoryMappingValidity();
            $this->_getSession()->addSuccess(Mage::helper('aukro')->__("Checking mapping completed successfully. Errors count: %s",$count));
        }
        catch (Exception  $e)
        {
        	$message = Mage::helper('aukro')->__("Checking mapping failed: ");
        	$this->_getSession()->addError($message. $e->getMessage());
        }
        // go to grid
        $this->_redirect('*/*/');
	}
	
}