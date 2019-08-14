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

class Nostress_Aukro_Model_Mysql4_Enginecategory extends Nostress_Aukro_Model_Mysql4_Abstract
{	
	const TAXONOMY_CODE = 'taxonomy_code';
	const LOCALE = 'locale';
	const LIMIT = 100;
	const COUNT_FIELD = "COUNT(*)";
	
    public function _construct()
    {    
        // Note that the export_id refers to the key field in your database table.
        $this->_init('aukro/enginecategory', 'entity_id');
    }    
    
    public function insertEngineCategoryRecords($engineCode,$locale,$records)
    {    	
    	$emptyRecord = array(self::LOCALE=>$locale, self::TAXONOMY_CODE => $engineCode);
    	
    	$sql = "";    	
    	$i = 0;
    	
    	foreach($records as $row)
    	{
    		$i++;
    		$sql .= $this->insertRecordQuery(array_merge($emptyRecord,$row));
    		if($i > self::LIMIT)
    		{
    			$this->runQuery($sql);
    			$sql = "";
    			$i = 0;
    		}
    	}    
    	if(!empty($sql))
    	    $this->runQuery($sql);   	    	    	
    }             
    
	protected function insertRecordQuery($row)
    {
    	$columns = implode(",",array_keys($row));    	
    	$values = $this->addEscapeChars(array_values($row));
    	$values = implode("','",$values);
    	
    	$sql = "INSERT INTO {$this->getMainTable()} ({$columns}) VALUES ('{$values}');";
    	
    	return $sql;  	
    }
    
    protected function addEscapeChars($items)
    {
    	foreach($items as $key => $item)
    	{
    		$items[$key] = str_replace("'","\\'",$item);
    	}	
    	return $items;
    }
    
    public function cleanTable()
    {    	
    	$this->runQuery($this->deleteRecordQuery());
    }
    
    protected function deleteRecordQuery($where='')
    {
    	$sql = "DELETE FROM ".$this->getMainTable();
    	if($where != '')
    		$sql .= ' '.$where;
		$sql .= ";";
    	return $sql;  	
    }
    
    public function countColumns($engineCode,$locale)
    {
        $result = $this->runOneRowSelectQuery($this->countColumnsQuery($engineCode,$locale));
        if(array_key_exists(self::COUNT_FIELD,$result))
            return $result[self::COUNT_FIELD];
        else 
            return 0;
    }
    
    protected function countColumnsQuery($engineCode,$locale)
    {
        $sql = "SELECT ".self::COUNT_FIELD." FROM {$this->getMainTable()} WHERE taxonomy_code = '{$engineCode}' AND locale = '{$locale}';";
        return $sql;
    }
    

}