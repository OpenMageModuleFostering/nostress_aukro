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

class Nostress_Aukro_Model_Mysql4_Cache_Superattributes extends Nostress_Aukro_Model_Mysql4_Cache
{   	    
	protected $_cacheName = 'Super attributes';
	
    public function _construct()
    {    
        $this->_init('aukro/cache_superattributes', 'product_id');
    }
    
	protected function defineColumns()
    {
		parent::defineColumns();   

       	$this->_columns[self::CPSA] = 
	        array(  "product_id" => "product_id",
	        		"store_id" => "({$this->getStoreId()})",);  
	    $this->_columns[self::NSA] = array(  "super_attributes" => "super_attributes");  
	    $this->_columns[self::CPSAL] = array(  "value" => "value");
	    $this->_columns[self::EA] = array(  "code" => "attribute_code");
	        
    }
    
    protected function reloadTable()
    {
    	$sql = $this->getProductInfoUpdateSql();
//    	echo $sql."<br>";
//    	exit();
    	$this->runQuery($sql);
    }
    
    protected function getProductInfoUpdateSql()
    {
    	$storeId = $this->getStoreId();
    	$sql = $this->getCleanMainTableSql();
		$sql .=  "INSERT INTO {$this->getMainTable()} ";
		$sql .= $this->getSubSelectTable($this->getSuperAttributesSql()).";";
		
		
//		$select = $this->getEmptySelect();
//		$mainTable = $this->getProductFlatTable();
//	    $mainTableAlias = $this->getProductFlatTable(true);
//		
//	    $select->from(array($mainTableAlias => $mainTable),$this->getColumns($mainTableAlias));
//		
//
//    	$joinTableAlias = self::NSA;
//		$joinTable = $this->getSubSelectTable($this->getSuperAttributesSql());
//    	$select->joinLeft(
//    					array($joinTableAlias => $joinTable),
//                    	$joinTableAlias.'.product_id='.$mainTableAlias.'.entity_id ',
//                    	$this->getColumns($joinTableAlias)
//    					);
//	    
//	    
//		$sql .= $this->getSubSelectTable($select).";";
		return $sql;
    }

    protected function getSuperAttributesSql()
    {
    	$mainTable = $this->getTable('catalog/product_super_attribute');
	    $mainTableAlias = self::CPSA;

	    $select = $this->getEmptySelect();
	    $select->from(array($mainTableAlias => $mainTable), $this->getColumns($mainTableAlias));
                       
        $joinTableValueAlias = 'value';
	    $joinTableAlias = $joinTableValueAlias;
		 $joinTable = $this->getTable('catalog/product_super_attribute_label');
		
	    $select->joinLeft(
                    array($joinTableAlias => $joinTable),
                    "{$joinTableAlias}.product_super_attribute_id={$mainTableAlias}.product_super_attribute_id AND {$joinTableAlias}.store_id = {$this->getStoreId()}",
                    null
                    );

        $joinTableDefaultValueAlias = 'defaultValue'; 
        $joinTableAlias = $joinTableDefaultValueAlias;
		
       	$select->joinLeft(
                    array($joinTableAlias => $joinTable),
                    "{$joinTableAlias}.product_super_attribute_id ={$mainTableAlias}.product_super_attribute_id AND {$joinTableAlias}.store_id = 0",
                    null
                    );

        //define concat columns
        $labelColumn = array( "label" => "IFNULL({$joinTableValueAlias}.value,{$joinTableDefaultValueAlias}.value)");    
	    $columns = array_merge($labelColumn,$this->getColumns(self::EA,null,false,true));
	    $select->columns($this->groupConcatColumns($columns));
		 
	    $select = $this->joinEavAttribute($select);    
        $select->group('product_id');  

        return $select;
    }
    
    public function getAllSuperAttributes()
    {
        $this->defineColumns();
        $select = $this->getSuperAttributesSelect();
        $result = $this->runSelectQuery($select);        
        
        $superAttributes = array();
        if(!isset($result) || !is_array($result))
            return $superAttributes;  
                        
        foreach($result as $row)
        {
            $superAttributes[] = $row['code'];
        }
        return $superAttributes;
    }
    
    protected function getSuperAttributesSelect()
    {               
        $mainTable = $this->getTable('catalog/product_super_attribute');
	    $mainTableAlias = self::CPSA;
	    
	    $select = $this->getEmptySelect();
	    $select->distinct();
	    $select->from(array($mainTableAlias => $mainTable),$this->getColumns(self::EA,null,false,true));
	    $this->joinEavAttribute($select);
        return $select;
    }
    
    public function getCacheColumns()
    {
        parent::getCacheColumns();
        
        $joinTableValueAlias = 'value';
        $joinTableDefaultValueAlias = 'defaultValue'; 
        $labelColumn = array( "label" => "IFNULL({$joinTableValueAlias}.value,{$joinTableDefaultValueAlias}.value)");    
	    return array_merge($labelColumn,$this->getColumns(self::EA,null,false,true));
    }
    
	protected function joinEavAttribute($select)
	{
		$mainTableAlias = self::CPSA;
		$joinTableAlias = self::EA;
		$joinTable = $this->getTable('eav/attribute');
		
		$select->join(
                    array($joinTableAlias => $joinTable),
                    "{$joinTableAlias}.attribute_id ={$mainTableAlias}.attribute_id",
                    null
                    );		
		return $select;
	}
	
	protected function getCleanMainTableSql()
	{
		return "DELETE FROM {$this->getMainTable()} WHERE store_id = {$this->getStoreId()};";
	}
}