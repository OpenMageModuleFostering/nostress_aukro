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

class Nostress_Aukro_Model_Config_Source_Productgroupsize
{
    public function toOptionArray()
    {
        return array(
/*            array('value'=>'1', 'label'=>Mage::helper('aukro')->__('1')),
            array('value'=>'5', 'label'=>Mage::helper('aukro')->__('5')),
            array('value'=>'10', 'label'=>Mage::helper('aukro')->__('10')),
            array('value'=>'30', 'label'=>Mage::helper('aukro')->__('30')),
            array('value'=>'50', 'label'=>Mage::helper('aukro')->__('50')),*/
            array('value'=>'100', 'label'=>Mage::helper('aukro')->__('100')),
            array('value'=>'300', 'label'=>Mage::helper('aukro')->__('300')),
            array('value'=>'500', 'label'=>Mage::helper('aukro')->__('500')),  
            array('value'=>'1000', 'label'=>Mage::helper('aukro')->__('1000')),
            array('value'=>'2000', 'label'=>Mage::helper('aukro')->__('2000')),
            array('value'=>'5000', 'label'=>Mage::helper('aukro')->__('5000')),
            array('value'=>'10000', 'label'=>Mage::helper('aukro')->__('10000')), 
        	array('value'=>'20000', 'label'=>Mage::helper('aukro')->__('20000')),
//         	array('value'=>'30000', 'label'=>Mage::helper('aukro')->__('30000')),
//         	array('value'=>'40000', 'label'=>Mage::helper('aukro')->__('40000')),
//         	array('value'=>'50000', 'label'=>Mage::helper('aukro')->__('50000')),
        );
    }
}
?>
