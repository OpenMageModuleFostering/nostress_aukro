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

class Nostress_Aukro_Model_Config_Source_Stockdependence
{
    const STOCK_AND_QTY = 'stock_and_qty';
    const QTY = 'qty';
    const STOCK = 'stock';
    
    public function toOptionArray()
    {
        return array(
            array('value'=> self::STOCK_AND_QTY, 'label'=>Mage::helper('aukro')->__('Stock status & Qty')),
            array('value'=> self::QTY, 'label'=>Mage::helper('aukro')->__('Qty only')),
            array('value'=> self::STOCK, 'label'=>Mage::helper('aukro')->__('Stock status only')),                      
        );
    }
}
?>