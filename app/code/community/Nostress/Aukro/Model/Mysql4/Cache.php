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

abstract class Nostress_Aukro_Model_Mysql4_Cache extends Nostress_Aukro_Model_Mysql4_Data_Loader
{     
	const STARTED = "started";  
	const FINISHED = "finished";
	
	protected $_cacheName = '';	
	
 	protected function defineColumns()
    {
        parent::defineColumns();
    }   
    
    public function reload($storeId)
    {
    	$this->logStatus(self::STARTED,$storeId);
    	$this->setStoreId($storeId);
    	$this->init();
    	$this->reloadTable();
    	$this->logStatus(self::FINISHED,$storeId);
    }
    
    protected function init()
    {
    	$this->defineColumns();
    }
    
    protected function logStatus($status,$storeId)
    {
    	$this->helper()->log($this->helper()->__("{$this->_cacheName} cache reload has %s for store %s",$status,$storeId));
    }
    
    public function getCacheColumns()
    {
        if(!isset($this->_columns))
            $this->defineColumns();       
        return array();
    }       
}