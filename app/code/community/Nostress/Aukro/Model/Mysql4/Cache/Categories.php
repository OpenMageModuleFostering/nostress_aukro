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

class Nostress_Aukro_Model_Mysql4_Cache_Categories extends Nostress_Aukro_Model_Mysql4_Cache
{   	
	protected $_cacheName = 'Categories';

	const CATEGORY_PREFIX = 'category_';
	
    public function _construct()
    {    
        $this->_init('aukro/cache_categories', 'product_id');
    }
    
	protected function defineColumns()
    {
		parent::defineColumns();       	     
    }
    
    protected function reloadTable()
    {
    	$sql = $this->getProductCategoriesUpdateSql();
    	//echo $sql."<br>";
    	//exit();
    	$this->runQuery($sql);
    }
    
    protected function getProductCategoriesUpdateSql()
    {
    	$storeId = $this->getStoreId();
    	$sql = $this->getCleanMainTableSql();
		$sql .=  "INSERT INTO {$this->getMainTable()} ";
		$sql .= $this->getSubSelectTable($this->getProductCategoriesSql()).";";
		return $sql;
    }
    
    protected function getProductCategoriesSql()
    {
    	$mainTable = $this->getTable('catalog/category_product_index');
	    $mainTableAlias = self::CCPI;
	    $joinTableAlias = $this->getCategoryFlatTable(true);
		$joinTable = $this->getCategoryFlatTable();
	    
	    $select = $this->getEmptySelect();
	    $select->from(array($mainTableAlias => $mainTable), array( "product_id","({$this->getStoreId()}) as store_id"));
	    $select->joinLeft(
                    array($joinTableAlias => $joinTable),
                    $joinTableAlias.'.entity_id='.$mainTableAlias.'.category_id',
                    null
                    );
        $select->where($mainTableAlias.'.store_id=?', $this->getStoreId());
	    $select->group('product_id');
	    $select = $this->joinCategoryPath($select);

	    //define concat columns
	    $columns = $this->getCacheColumns();
	    $select->columns($this->groupConcatColumns($columns));
	    
	    return $select;
    }
    
    public function getCacheColumns()
    {
        parent::getCacheColumns();
        $columns = array_merge($this->getColumns($this->getCategoryFlatTable(true),null,false,true),$this->getColumns(self::NCCP,null,false,true));
        $columns = $this->removeColumnPrefix($columns);
        return $columns;
    }
    
    protected function removeColumnPrefix($columns)
    {
        $result = array();
        foreach($columns as $alias => $column)
        {
            $alias = str_replace(self::CATEGORY_PREFIX,"",$alias);
            $result[$alias] = $column;            
        }
        return $result;
    }
    
	protected function joinCategoryPath($select)
	{
		$mainTableAlias = $this->getCategoryFlatTable(true);
		$joinTableAlias = self::NCCP;
		$joinTable = $this->getTable('aukro/cache_categorypath');
		
		$select->join(
                    array($joinTableAlias => $joinTable),
                    "{$joinTableAlias}.category_id ={$mainTableAlias}.entity_id AND {$joinTableAlias}.store_id ={$this->getStoreId()}",
                    null
                    );		
		return $select;
	}
	
	protected function getCleanMainTableSql()
	{
		return "DELETE FROM {$this->getMainTable()} WHERE store_id = {$this->getStoreId()};";
	}
}