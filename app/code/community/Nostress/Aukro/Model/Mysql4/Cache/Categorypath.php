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

class Nostress_Aukro_Model_Mysql4_Cache_Categorypath extends Nostress_Aukro_Model_Mysql4_Cache
{   	
	protected $_cacheName = 'Category path';
	
	const DEF_MAX_LEVEL = 10; 
	const DEF_FIRST_LEVEL_TO_INCLUDE = 2; 
	const LEVEL = 'level';     
	const NCCP = 'nccp'; //nostress cache category path
	
    public function _construct()
    {    
        $this->_init('aukro/cache_categorypath', 'category_id');
    }
    
	protected function defineColumns()
    {
		parent::defineColumns();
        $this->_columns[$this->getCategoryFlatTable(true)] = 
        		array( 	"category_id" => "entity_id", 
        				"store_id" => "({$this->getStoreId()})",
        				"category_path" => "('')",   				
        				"ids_path" => "path",
        				"level" => "level");          
    }
    
    protected function reloadTable()
    {
    	$firstLevelToInclude = self::DEF_FIRST_LEVEL_TO_INCLUDE;
    	$maxLevel = $this->getCategoryMaxLevel();
    	$sql = $this->getCategoryPathUpdateSql($maxLevel,$firstLevelToInclude);
    	//echo $sql."<br>";
//    	exit();
    	$this->runQuery($sql);
    }
    
	protected function getCategoryPathUpdateSql($maxLevel,$firstLevelToInclude)
	{
		$storeId = $this->getStoreId();
		
		$sql = $this->getCleanCategoryPathSql();
		$i = 0;
		for($i;$i<$firstLevelToInclude;$i++)
		{
			$sql .= $this->getInsertCategoryPathSql($i,true);
		}
		
		$i = $firstLevelToInclude;
		for($i;$i<=$maxLevel;$i++)
		{
			$sql .= $this->getInsertCategoryPathSql($i,false);
		}
		return $sql;
	}

	protected function getInsertCategoryPathSql($level,$base = false)
	{
		$storeId = $this->getStoreId();
		$sql =  "INSERT INTO {$this->getMainTable()} ";

		$mainTable = $this->getMainTable();
		$mainTableAlias = self::NCCP;
		$tableAlias = $this->getCategoryFlatTable(true);
		$table = $this->getCategoryFlatTable();
        
		$select = $this->getEmptySelect();
		$select->reset();
        $select->from(array($tableAlias => $table),null);
        $columns = $this->getColumns($tableAlias);
		
		if(!$base)
		{                    
			$select->join(
					array($mainTableAlias => $mainTable),
					"{$mainTableAlias}.level = ({$tableAlias}.level-1) AND {$tableAlias}.path LIKE CONCAT({$mainTableAlias}.ids_path,'/%') AND {$mainTableAlias}.store_id ={$storeId} ",
					null
						);
			
			$columns["category_path"] = "IF({$mainTableAlias}.category_path <> '',CONCAT({$mainTableAlias}.category_path,'/',{$tableAlias}.name),{$tableAlias}.name)"; 
		}
		$select->columns($columns);
		$select->where($tableAlias.".level=?",$level);		
		
		$sql .= $this->getSubSelectTable($select).";";
		return $sql;
	}
	
    protected function getCategoryMaxLevel()
    {
    	$result = $this->runOneRowSelectQuery($this->getCategoryPathMaxLevelSql());
    	
    	if(array_key_exists(self::LEVEL,$result))
    		return $result[self::LEVEL];
    	else 
    		return self::DEF_MAX_LEVEL;
    }
    
	protected function getCategoryPathMaxLevelSql()
	{
		return "SELECT MAX(".self::LEVEL.") as ".self::LEVEL." FROM {$this->getCategoryFlatTable()}; ";
	}
	
	protected function getCleanCategoryPathSql()
	{
		return "DELETE FROM {$this->getMainTable()} WHERE store_id = {$this->getStoreId()}; \n";
	}
}