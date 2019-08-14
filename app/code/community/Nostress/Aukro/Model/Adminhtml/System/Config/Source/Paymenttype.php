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

class Nostress_Aukro_Model_Adminhtml_System_Config_Source_Paymenttype
{
    public function toOptionArray()
    {
        return array(
            array('value' => 1, 'label'=>Mage::helper('aukro')->__('Bank transfer (in advance)')),
            array('value' => 2, 'label'=>Mage::helper('aukro')->__('Payments via PayU')),
            array('value' => 4, 'label'=>Mage::helper('aukro')->__('Others'))
        );
    }
}
	 