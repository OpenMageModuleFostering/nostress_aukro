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

class Nostress_Aukro_Model_Mysql4_Cache_Mediagallery extends Nostress_Aukro_Model_Mysql4_Cache
{   	  	
	protected $_cacheName = 'Media gallery';
		
    public function _construct()
    {    
        $this->_init('aukro/cache_mediagallery', 'product_id');
    }
    
	protected function defineColumns()
    {
		parent::defineColumns();   

       	$this->_columns[self::CPAMG] = 
	        array(  "product_id" => "entity_id",
	        		"store_id" => "({$this->getStoreId()})",);  
	    $this->_columns[self::CPAMGV] = array(  "label" => "label");  
	        
    }
    
    protected function reloadTable()
    {
    	$sql = $this->getProductInfoUpdateSql();
    	//echo $sql."<br>";
    	//exit();
    	$this->runQuery($sql);
    }
    
    protected function getProductInfoUpdateSql()
    {
    	$storeId = $this->getStoreId();
    	$sql = $this->getCleanMainTableSql();
		$sql .=  "INSERT INTO {$this->getMainTable()} ";
		$sql .= $this->getSubSelectTable($this->getMediaGallerySql()).";";
		return $sql;
    }

    protected function getMediaGallerySql()
    {
    	$mainTable = $this->getTable('catalog/product_attribute_media_gallery');
	    $mainTableAlias = self::CPAMG;	    	    
	    
	    $select = $this->getEmptySelect();
	    $select->from(array($mainTableAlias => $mainTable), $this->getColumns($mainTableAlias));
	    
	    $joinTableValueAlias = 'value';
	    $joinTableAlias = $joinTableValueAlias;
		$joinTable = $this->getTable('catalog/product_attribute_media_gallery_value');
		
	    $select->joinLeft(
                    array($joinTableAlias => $joinTable),
                    "{$joinTableAlias}.value_id={$mainTableAlias}.value_id AND {$joinTableAlias}.store_id = {$this->getStoreId()}",
                    null
                    );
                    
        $joinTableDefaultValueAlias = 'defaultValue'; 
        $joinTableAlias = $joinTableDefaultValueAlias;
		
       	$select->joinLeft(
                    array($joinTableAlias => $joinTable),
                    "{$joinTableAlias}.value_id={$mainTableAlias}.value_id AND {$joinTableAlias}.store_id = 0",
                    null
                    );
		                    
        //define concat columns
	    $columns = $this->getCacheColumns();
	    $select->columns($this->groupConcatColumns($columns));
        $select->group("{$mainTableAlias}.entity_id");  

        return $select;
    }
    
    public function getCacheColumns()
    {
        parent::getCacheColumns();
        
        $joinTableValueAlias = 'value';
        $joinTableDefaultValueAlias = 'defaultValue'; 
        $mainTableAlias = self::CPAMG;
        $labelColumn = array( "label" => "IFNULL({$joinTableValueAlias}.label,{$joinTableDefaultValueAlias}.label)");
        return array_merge($labelColumn,array("value"=>"CONCAT('{$this->getBaseUrl()}','{$this->getImageFolder()}', {$mainTableAlias}.value)"));
    }
	
	protected function getCleanMainTableSql()
	{
		return "DELETE FROM {$this->getMainTable()} WHERE store_id = {$this->getStoreId()};";
	}
	
	protected function getMediaGalleryAttributeCode()
	{
		return $this->helper()->getGeneralConfig(Nostress_Aukro_Helper_Data::PARAM_MEDIA_GALLERY);
	}
}