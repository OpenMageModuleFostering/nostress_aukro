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
 * @copyright Copyright (c) 2009 NoStress Commerce (http://www.nostresscommerce.cz)
 *
 */

/**
 * Block for Export
 *
 * @category Nostress
 * @package Nostress_Pdfprintouts
 *
 */
 
class Nostress_Aukro_Block_Adminhtml_System_Config_Form_Field_Shipping extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    public function __construct()
    {
        $this->addColumn('first', array(
            'label' => Mage::helper('aukro')->__('First Item'),
            'style' => 'width:60px',
        	'class' => 'option-control',
        	'type' => 'select',
        ));
        $this->addColumn('next', array(
            'label' => Mage::helper('aukro')->__('Next Item'),
            'style' => 'width:60px',
        	'class' => 'option-control',
        	'type' => 'select',
        ));
        $this->addColumn('amount', array(
            'label' => Mage::helper('aukro')->__('Package Quantity'),
            'style' => 'width:60px',
        	'class' => 'option-control',
        	'type' => 'select',
        ));
        $this->_addAfter = false;
        $_mandatoryAttributes=array();
        $this->setTemplate('nostress_aukro/widget/form/config/shipping.phtml');
        parent::__construct();
    }
    
    public function getSavedValues() {
    	$collection = Mage::getModel('aukro/shipping_pricing')->getCollection();
		
    	$aukroWebApi = Mage::getModel('aukro/webapi_abstract');
		$shipmentData = $aukroWebApi->getShipmentData();
		$shipmentData = $shipmentData['shipmentDataList']['item'];
    	$values = array();
		foreach ($shipmentData as $shipment) {
			$values[$shipment['shipmentId']]['name'] = $shipment['shipmentName'];
		}
		
    	foreach ($collection as $item) {
    		if (isset($values[$item->getId()])) {
    			$values[$item->getId()]['first'] = $item->getFirst();
    			$values[$item->getId()]['next'] = $item->getNext();
    			$values[$item->getId()]['amount'] = $item->getAmount();
    		}
    	}
    	
    	return $values;
    }

    
}