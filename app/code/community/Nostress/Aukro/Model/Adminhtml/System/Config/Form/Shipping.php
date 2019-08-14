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
 * Backend for serialized array data
 * 
 * @category Nostress 
 * @package Nostress_Pdfprintouts
 * 
 */

class Nostress_Aukro_Model_Adminhtml_System_Config_Form_Shipping extends Mage_Adminhtml_Model_System_Config_Backend_Serialized
{
    /**
     * Unset array element with '__empty' key and save data to proper table
     *
     */
    protected function _beforeSave()
    {    	
        $groups = $this->getGroups();
        $shippingPricing = $groups['shipping_pricing'];
        if (is_array($value)) {
            unset($value['__empty']);
        }
        
        $shippingModel = Mage::getModel('aukro/shipping_pricing');
        
        foreach ($shippingPricing as $key => $shipping) {
        	$dbData['id'] = $key;
        	
        	if ($shipping['first'] == '')
        		$dbData['first'] = null;
        	else 
        		$dbData['first'] = $shipping['first'];
        		
        	if ($shipping['next'] == '')
        		$dbData['next'] = null;
        	else 
        		$dbData['next'] = $shipping['next'];
        		
        	if ($shipping['amount'] == '')
        		$dbData['amount'] = null;
        	else 
        		$dbData['amount'] = $shipping['amount'];
        		
        	$shippingModel->setData($dbData);
        	$shippingModel->save();
        }

    }
}
