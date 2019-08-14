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

class Nostress_Aukro_Model_Adminhtml_System_Config_Source_Abstract extends Mage_Eav_Model_Entity_Attribute_Source_Boolean
{
    public function toOptionArray()
    {
        return $this->getAllOptions();
    }
    
    public function getOptions() {
    	foreach ($this->toOptionArray() as $option) {
    		$options[$option['value']] = $option['label'];
    	}
    	return $options;
    }
    
    public function valueToLabel($value) {
    	$options = $this->toOptionArray();
    	foreach ($options as $option) {
    		if ($option['value'] == $value) {
    			return $option['label'];
    		}
    	}
    	return;
    }
    
}
	 