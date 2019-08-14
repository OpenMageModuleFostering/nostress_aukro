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
* Helper.
* 
* @category Nostress 
* @package Nostress_Aukro
* 
*/

class Nostress_Aukro_Helper_Data_Profile extends Nostress_Aukro_Helper_Data
{
    public function run($profileIds = null, $stopIfRunning=true)
    {
    	Mage::helper('aukro/version')->validateLicenceBackend();
        if(is_array($profileIds))
        {
           	foreach ($profileIds as $profileId) 
	        {
	           	$this->_run($profileId);
	        }
        }
        else
        	$this->_run($profileIds);        

        return true;
    }

	protected function _run($profileId = null, $stopIfRunning=true)
    {
        $profile = Mage::getModel('aukro/profile');
        if(!isset($profileId))
        {
        	$profile = $profile->getAllProfiles();
        }
        else if (is_numeric($profileId)) {
            $profile->load($profileId);
        } else {
           	$profile =  $profile->getProfilesByName($profileId);
        }

       	$this->runProfile($profile);	
        return $profile;
    }
    
	protected function runProfile($profile)
    {
    	if(is_array($profile))
        {
           	foreach ($profile as $item) 
	        {
	           	$this->_runProfile($item);
	        }
        }
        else
        	$this->_runProfile($profile);	
    }
    
    protected function _runProfile($profile)
    {
    	if (!$profile->getId()) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Invalid Profile ID'));
        }     
        $controlUnit = Mage::getModel('aukro/unit_control')->run($profile);   
    	
    }
}