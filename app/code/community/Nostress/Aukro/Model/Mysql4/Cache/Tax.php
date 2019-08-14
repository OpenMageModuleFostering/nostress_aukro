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

class Nostress_Aukro_Model_Mysql4_Cache_Tax extends Nostress_Aukro_Model_Mysql4_Cache
{   	    
	protected $_cacheName = 'Tax';
	
    public function _construct()
    {    
        $this->_init('aukro/cache_tax', 'tax_class_id');
    }
    
    protected function reloadTable()
    {
    	$sql = $this->getUpdateSql();
    	//echo $sql."<br>";
    	//exit();
    	$this->runQuery($sql);
    }
    
    protected function getUpdateSql()
    {
    	$storeId = $this->getStoreId();
    	$store = Mage::app()->getStore($storeId);
    	$sql = $this->getCleanMainTableSql();
    	
    	$calc = Mage::getSingleton('tax/calculation');
    	$rates = $calc->getRatesForAllProductTaxClasses($calc->getRateOriginRequest($store));

    	foreach ($rates as $class=>$rate) 
    	{
    		$sql.= $this->getInsertCommand($class,$storeId,$rate)."; ";      		    		    		
    	}
		return $sql;
    }

    protected function getInsertCommand($taxClass,$storeId,$taxPercent)
    {
    	$taxPercent /= 100; 
    	
    	$sql =  "INSERT INTO {$this->getMainTable()} VALUES (";
    	$sql.= "'{$taxClass}','{$storeId}','{$taxPercent}' )";
    	return $sql;    	
    }   
	
	protected function getCleanMainTableSql()
	{
		return "DELETE FROM {$this->getMainTable()} WHERE store_id = {$this->getStoreId()};";
	}
}