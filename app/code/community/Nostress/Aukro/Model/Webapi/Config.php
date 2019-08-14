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

class Nostress_Aukro_Model_Webapi_Config extends Nostress_Aukro_Model_Webapi_Abstract
{
    public function getCountries() {
        
        $userData = Mage::helper('aukro')->getAukroLoginData();
        $params = array('countryCode' => self::AUKRO_COUNTRY, 'webapiKey' => $userData['webapi_key']);
        return $this->soapCall( 'doGetCountries', $params);
    }
    
    public function getRegions() {
    
        $userData = Mage::helper('aukro')->getAukroLoginData();
        $params = array('countryCode' => self::AUKRO_COUNTRY, 'webapiKey' => $userData['webapi_key']);
        return $this->soapCall( 'doGetStatesInfo', $params);
    }
    
    public function getFieldConfig( $fieldID = null) {
        
        $userData = Mage::helper('aukro')->getAukroLoginData();
        $params = array('countryCode' => self::AUKRO_COUNTRY, 'localVersion'=>0, 'webapiKey' => $userData['webapi_key']);
        $fields = $this->soapCall( 'doGetSellFormFieldsExt', $params);
        // mozna bude potreba, ale je to pomale. nacita se asi 1300 atributu
    }
}