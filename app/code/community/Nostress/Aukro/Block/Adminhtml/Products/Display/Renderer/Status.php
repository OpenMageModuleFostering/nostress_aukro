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
 *
 * @category Nostress
 * @package Nostress_Aukro
 */

class Nostress_Aukro_Block_Adminhtml_Products_Display_Renderer_Status extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    const STATUS_CLASS_COLOR_GREEN = 'grid-severity-notice';
    const STATUS_CLASS_COLOR_ORANGE = 'grid-severity-major';
    const STATUS_CLASS_COLOR_RED= 'grid-severity-critical';

    protected function getStatusHtml($class,$value)
    {
        $value = $this->__($value);
        return  '<span class="'.$class.'"><span>'.$value.'</span></span>';
    }
    
    /**
     * Prepare link to display in grid
     *
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $class = self::STATUS_CLASS_COLOR_RED;
    	$message = "";
    	if( $row->aukrocategory_id <= 0) {
    	    $message .= Mage::helper('aukro')->__("Specify aukrocategory")."! <br />";
    	}
    	if( $row->qty <= 0) {
    	    $message .= Mage::helper('aukro')->__("No items in stock")."! <br />";
    	}
    	if( $row->stock_status == 0) {
    	    $message .= Mage::helper('aukro')->__("Out of stock")."! <br />";
    	}
    	if( $row->price_final_include_tax < 1) {
    	    $message .= Mage::helper('aukro')->__("Price must be higher than 1 Kč")."! <br />";;
    	}
    	
    	if( empty( $message)) {
    	    $aukroID = $row->getAukroProductId();
    	    if( empty($aukroID)) {
    	        $message = Mage::helper('aukro')->__("Ready for exposing");
    	    } else {
    	        $message = Mage::helper('aukro')->__("Exposed on Aukro");
    	    }
    	    $class = self::STATUS_CLASS_COLOR_GREEN;
    	}
    	return $this->getStatusHtml( $class, $message);
    }

}
