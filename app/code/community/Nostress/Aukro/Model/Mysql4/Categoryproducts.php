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

class Nostress_Aukro_Model_Mysql4_Categoryproducts extends Mage_Core_Model_Mysql4_Abstract
{
	const PRODUCT_SQL = "COUNT(DISTINCT product_id)";
	
    public function _construct()
    {    
        // Note that the export_id refers to the key field in your database table.
        $this->_init('aukro/categoryproducts', 'entity_id');
    }
    
    public function insertRecords($profileId,$categoryProducts)
    {
    	$queary = "";
    	foreach($categoryProducts as $categoryId => $productIds)
    	{
    		foreach($productIds as $productId)
    		{
    			$queary .= $this->insertRecordQuery($profileId,$categoryId,$productId);
    		}
    	}
    	$this->runQuery($queary);
    }
    
    public function deleteRecords($profileId,$productId=null)
    {
    	$queary = $this->deleteProfileQuery($profileId,$productId);
    	$this->runQuery($queary);
    }
    
    public function isCategoryInProfile($categoryId,$profileId)
    {
    	$queryString = $this->selectCategoryProfileQuery($categoryId,$profileId);
    	$qRes = $this->runSelectQuery($queryString,false);
    	if(!isset($qRes) || $qRes === false)
    		return false;
    	else
    		return true;
    }
    
    public function getProfileRecordCount($profileId)
    {
    	$queryString = $this->selectProfileRecordsCountQuery($profileId);
    	$result = $this->runSelectQuery($queryString,false);
    	if(is_array($result) && isset($result[self::PRODUCT_SQL]))
    		return $result[self::PRODUCT_SQL];
    	return 0;
    }
    
    private function selectCategoryProfileQuery($categoryId,$profileId)
    {
    	$table = $this->getMainTable();
    	return "SELECT 1 FROM ".$table." WHERE ".$table.".export_id = ".$profileId." AND ".$table.".category_id = ".$categoryId.";";    	
    }
    
	private function selectCategoryProductQuery($productId,$categoryId,$profileId)
    {
    	$table = $this->getMainTable();
    	return "SELECT 1 FROM ".$table." WHERE ".$table.".export_id = ".$profileId." AND ".$table.".category_id = ".$categoryId."AND ".$table.".product_id = ".$productId.";";    	
    }
    
    private function selectProfileRecordsCountQuery($profileId)
    {
    	$table = $this->getMainTable();
    	return "SELECT ".self::PRODUCT_SQL." FROM ".$table." WHERE ".$table.".export_id = ".$profileId.";";      	
    }
    
    private function deleteProfileQuery($profileId,$productId)
    {
    	$sql =  "DELETE FROM ".$this->getMainTable()." WHERE ";
    	$where = array();
    	if(isset($profileId))
    		$where[] = $this->getMainTable().".export_id = ".$profileId." "; 
    	if(isset($productId))
    		$where[] = $this->getMainTable().".product_id = ".$productId." ";   
    	
    	$sql .= implode(" AND ",$where);
    	$sql .= ";";
    	return $sql; 	   	
    }
    
    private function insertRecordQuery($profileId,$categoryId,$productId)
    {
    	return "INSERT INTO ".$this->getMainTable()." (export_id,category_id,product_id) VALUES (".$profileId.",".$categoryId.",".$productId.");";  	
    }
    
    private function runQuery($queryString)
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
    
    private function runSelectQuery($queryString,$fatchAll)
    {
    	//select query
    	$read = $this->_getReadAdapter();

		if($fatchAll)
			$res = $read->fetchAll($queryString); //get array
		else
			$res = $read->fetchRow($queryString); //fetch row
		
		return $res;
    }
}