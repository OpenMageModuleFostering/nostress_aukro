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

class Nostress_Aukro_Model_Adminhtml_System_Config_Source_Paymentmethods
{
    public function toOptionArray()
    {
        return $this->getActivePaymentMethods();
    }
    
    public function getActivePaymentMethods()
    {
        $payments = Mage::getSingleton('payment/config')->getActiveMethods();
        $methods = array(array('value'=>'', 'label'=>Mage::helper('adminhtml')->__('--Please Select--')));
        foreach ($payments as $paymentCode=>$paymentModel) {
            $paymentTitle = Mage::getStoreConfig('payment/'.$paymentCode.'/title');
            $methods[$paymentCode] = array(
                    'label'   => $paymentTitle,
                    'value' => $paymentCode,
            );
        }
        return $methods;
    }
}
	 