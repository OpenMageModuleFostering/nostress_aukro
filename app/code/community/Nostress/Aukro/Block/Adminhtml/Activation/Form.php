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
* @category Nostress
* @package Nostress_Aukro
*/

class Nostress_Aukro_Block_Adminhtml_Activation_Form extends Mage_Adminhtml_Block_Widget_Form
{
	protected function _prepareForm()
	{
		$form = new Varien_Data_Form(array(
			'id' => 'edit_form',
			'action' => $this->getUrl("*/*/activate"),
			'method' => 'post',
			'enctype' => 'multipart/form-data',
		));
		
		$form->setUseContainer(true);
		$this->setForm($form);
		
		$fieldset = $form->addFieldset('activation_form', array(
			'legend' => Mage::helper('aukro')->__('Activation Form')
		));
		
		$fieldset->addField('code', 'hidden', array(
			'name' => 'code',
			'value' => 'AC4M',
		));
		
		$fieldset->addField('name', 'text', array(
			'label' => $this->__('Name:'),
			'class' => 'required-entry',
			'required' => true,
			'name' => 'name'
		));
		
		$fieldset->addField('email', 'text', array(
			'label' => $this->__('Email:'),
			'class' => 'required-entry validate-email',
			'required' => true,
			'name' => 'email'
		));
		
		$helper = Mage::helper( 'aukro');
		
		$licenseConditionsLink = $helper->getHelpUrl(Nostress_Aukro_Helper_Data::HELP_LICENSE_CONDITIONS);
		$fieldset->addField('accept_license_conditions', 'checkbox', array(
				'label' => $this->__('I accept Aukro Connector License Conditions'),
				'note' =>  $this->__('See').' <a href="'.$licenseConditionsLink.'" target="_blank">'.$this->__('Aukro Connector License Condtions').'</a>',
				'name' => 'accept_license_conditions',
				'value' => 0,
				//'checked' => 'false',
				'onclick' => 'this.value = this.checked ? 1 : 0;',
				'disabled' => false,
				'readonly' => false,
				'required' => true,
		));
		
		$fieldset->addField('submit_and_activate', 'button', array(
			'label' => "",
			'onclick'   => 'if(editForm.submit()){addOverlay();document.getElementById(\'loading-mask\').style.display = \'block\';}',
			'class' => 'btn-koongo-submit-orange',
			'value' => $this->__( 'Activate Aukro Connector'),
		));
				
		return parent::_prepareForm();
	}
}