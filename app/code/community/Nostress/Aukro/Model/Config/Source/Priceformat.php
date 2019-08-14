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
* Exports model - source for dropdown menu "Product group size"
* 
* @category Nostress 
* @package Nostress_Aukro
* 
*/

class Nostress_Aukro_Model_Config_Source_Priceformat
{
    const STANTDARD = "standard";
    const CURRENCY_SUFFIX = "currency_suffix";
    const CURRENCY_PREFIX = "currency_prefix";
    const SYMBOL_SUFFIX = "symbol_suffix";
    const SYMBOL_PREFIX = "symbol_prefix";
        
    public function toOptionArray()
    {
        return array(
            array('value'=> self::STANTDARD, 'label'=>Mage::helper('aukro')->__('Standard e.g. 149.99')),
            array('value'=>self::CURRENCY_SUFFIX, 'label'=>Mage::helper('aukro')->__('Currency suffix e.g. 149.99 USD')),
            array('value'=>self::CURRENCY_PREFIX, 'label'=>Mage::helper('aukro')->__('Currency prefix e.g. USD 149.99')),
            array('value'=>self::SYMBOL_SUFFIX, 'label'=>Mage::helper('aukro')->__('Symbol suffix e.g 149.99 US$')),
            array('value'=>self::SYMBOL_PREFIX, 'label'=>Mage::helper('aukro')->__('Symbol prefix e.g US$ 149.99'))                                  
        );
    }
}
?>