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
* Model for Export
* 
* @category Nostress 
* @package Nostress_Aukro
* 
*/

abstract class Nostress_Aukro_Model_Mysql4_Abstract extends Mage_Core_Model_Mysql4_Abstract
{        
    protected function runQuery($queryString)
    {
    	if(!isset($queryString) || $queryString == "")
    		return $this;
    	$this->beginTransaction();
        try {
            
            $this->_getWriteAdapter()->query($queryString);       
            $this->commit();
        }
        catch (Exception $e){
            $this->rollBack();
            throw $e;
        }
        return $this;
    }
    
    protected function runSelectQuery($select)
    {
    	return $this->getReadConnection()->fetchAll($select);
    }
    
	protected function runOneRowSelectQuery($queryString)
    {    	
    	return $this->getReadConnection()->fetchRow($queryString);
    }
}