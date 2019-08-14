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
* Resource  of data loader for export process
* @category Nostress
* @package Nostress_Aukro
*
*/

class Nostress_Aukro_Model_Mysql4_Data_Loader_Product extends Nostress_Aukro_Model_Mysql4_Data_Loader
{
 	protected $_lastCategoryProductTableAlias = self::NCP;
	protected $_defaultAttributes = array("id","type","group_id","is_child");
 	protected $_productFlatMandatoryColumns = array('visibility','tax_class_id');
 	protected $_multiColumns = array("super_attributes","categories","media_gallery");
 	protected $_staticColumns = array("currency","country_code","locale");
 	
 	const VISIBILITY = 4;
	const STOCK_STATUS = 1;
	const MAIN_TABLE_SUBST = '{{main_table}}';
    
    /**
     * Initialize collection select
     * Redeclared for remove entity_type_id condition
     * in catalog_product_entity we store just products
     *
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
     */
    public function init()
    {
    	$this->defineColumns();
        $tableAlias = $this->getMainTable(true);
		$table = $this->getMainTable();
        
		$select = $this->getSelect();
		$select->reset();
        $select->from(array($tableAlias => $table),$this->getProductFlatColumns());
        return $this;
    }
    
    public function getMainTable($alias = false)
    {
        return $this->getProductFlatTable($alias);
    }
    
    public function getAllColumns()
    {
    	$columns = parent::getAllColumns();
    	foreach ($this->_productFlatMandatoryColumns as $value)
    	{
    		$columns[$value] = $value;
    	}
    	return $columns;
    }
    
    protected function getProductFlatColumns()
    {
        $columns = $this->getColumns($this->getMainTable(true));
        $unknownAttributes = $this->getUnknownAttributes();
        $this->checkProductAttributes(array_merge($unknownAttributes,$this->_productFlatMandatoryColumns));
        $columns = array_merge($columns,$this->prepareColumnsFromAttributes($unknownAttributes));
        
        return $columns;
    }
    
    protected function defineColumns()
    {
        parent::defineColumns();

        $this->_columns[$this->getProductFlatTable(true)] =
        array(  'entity_id',
                "id" => "entity_id",
                "type" => "type_id",
                "url" => "CONCAT('{$this->getStoreBaseUrl()}',{$this->getProductFlatTable(true)}.url_path)"
                                                     );
        $this->_columns[self::NPACD] = array(  Nostress_Aukro_Helper_Data_Loader::COLUMN_CATEGORIES_DATA => Nostress_Aukro_Helper_Data_Loader::GROUPED_COLUMN_ALIAS  );
		
        $this->_columns[self::CISS] = array(  "stock_status" => "stock_status"/*,
        									  "qty" => "qty"*/);
        $this->_columns[self::CISI] = array(  	"qty" => $this->helper()->getRoundSql(self::CISI.".qty",0)/*,
        										"is_in_stock" => "is_in_stock"*/);
        $this->_columns[self::PCPF] = array("parent_sku" => "sku");
        $this->_columns[self::CPR] = array(	"parent_id" => "parent_id",
                                            "group_id" => "IFNULL(".self::CPR.".parent_id,{$this->getMainTable(true)}.entity_id)",
                                            "is_child" => "(".self::CPR.".parent_id IS NOT NULL)",
        									"is_parent" => "(".self::CPR.".parent_id IS NULL)");
        

        $this->_columns[self::NCSA] = array("super_attributes" => "super_attributes");
        $this->_columns[self::NCC] = array("categories" => "categories");
        $this->_columns[self::NCMG] = array("media_gallery" => "media_gallery");
        $this->_columns[self::RRA] = array("reviews_count" => "reviews_count",
                                           "reviews_url" => "CONCAT('{$this->getStoreBaseUrl()}','{$this->getReviewUrlPrefix()}', CAST({$this->getProductFlatTable(true)}.entity_id AS CHAR)) AS reviews_url");
        
        $this->_columns[self::PCISS] = array("parent_stock_status" => self::PCISS.".stock_status"/*,
                                             "parent_qty" => self::PCISS.".qty"*/);
        $this->_columns[self::PCISI] = array("parent_qty" => $this->helper()->getRoundSql(self::PCISI.".qty",0) /*,
        		                             "parent_is_in_stock" => "(".self::PCISI.".is_in_stock)"*/);
        $this->_columns[self::NCT] = array("tax_percent" => "IFNULL({$this->helper()->getRoundSql("tax_percent*100")},0)");
        $this->_columns[self::NEC] = array(	"taxonomy_name" => "name",
                                            "taxonomy_id" => "id",
                                            "taxonomy_path" => "path",
                                            "taxonomy_ids_path" => "ids_path",
                                            "taxonomy_level" => "level",
                                            "taxonomy_parent_name" => "parent_name",
                                            "taxonomy_parent_id" => "parent_id"
                                        	);
		$this->_columns[$this->getCategoryFlatTable(true,null,true)] = array("category_parent_name" => "name");
		$this->_columns[self::CPE] = array(	"last_update_datetime" => self::CPE.".updated_at",
                                            "last_update_date" => "DATE_FORMAT(".self::CPE.".updated_at,'%Y-%m-%d')",
                                            "last_update_time" => "DATE_FORMAT(".self::CPE.".updated_at,'%T')"
                                        	);
        
        $this->definePriceColumns();
        
    }
	
    //*************************************** BASE PART ******************************************
     /**
     * Joint product filter
     */
	public function joinProductFilter()
	{
	    $this->joinExportCategoryProduct();
	}
    /**
     * Joint export categoryproduct table
     */
	protected function joinExportCategoryProduct()
	{
		$select = $this->getSelect();
		$mainTableAlias = $this->getMainTable(true);
		
		$joinTableAlias = self::NCP;
		$joinTable = $this->getTable('aukro/categoryproducts');
		$select->join(
                    array($joinTableAlias => $joinTable),
                    $joinTableAlias.'.product_id='.$mainTableAlias.'.entity_id ',
                    $this->getColumns($joinTableAlias));
        $select->where($joinTableAlias.'.export_id=?', $this->getExportId());
        $this->_lastCategoryProductTableAlias = $joinTableAlias;
	}
	
	/**
     * Joint export categoryproduct table
     */
	public function joinCategoryProduct()
	{
		$select = $this->getSelect();
		$mainTableAlias = $this->getMainTable(true);
		
		$joinTableAlias = self::CCPI;
		$joinTable = $this->getTable('catalog/category_product');
		$select->join(
                    array($joinTableAlias => $joinTable),
                    $joinTableAlias.'.product_id='.$mainTableAlias.'.entity_id ',
                    $this->getColumns($joinTableAlias));
        
        $this->_lastCategoryProductTableAlias = $joinTableAlias;
	}
    
    /**
     * Joint category table
     */
	public function joinCategoryFlat()
	{
		$select = $this->getSelect();

		$attrNameId = Mage::getModel('eav/config')->getAttribute('catalog_category', 'name')->getAttributeId();
        $select
            ->joinLeft( array( 'cce'=>'catalog_category_entity'), "cce.entity_id = ".self::CCPI.".category_id", array())
		    ->joinLeft( array( 'cev'=>'catalog_category_entity_varchar'), self::CCPI.".category_id = cev.entity_id and cev.attribute_id = $attrNameId",
                array( 'category_name'=>'cev.value')
		    );

		return $this;
	}
	
	protected function joinCategoryPath()
	{
		$select = $this->getSelect();
		$mainTableAlias = $this->getCategoryFlatTable(true);
		$joinTableAlias = self::NCCP;
		$joinTable = $this->getTable('aukro/cache_categorypath');
		
		$select->join(
                    array($joinTableAlias => $joinTable),
                    "{$joinTableAlias}.category_id ={$mainTableAlias}.entity_id AND {$joinTableAlias}.store_id ={$this->getStoreId()}",
                    $this->getColumns($joinTableAlias)
                    );
		return $this;
	}

	/**
	 * Subselect is too time consumpting
	 */
	public function joinAllCategoriesData()
	{
	    $mainTable = $this->getTable('catalog/category_product_index');
	    $mainTableAlias = self::CCPI;
	    $joinTableAlias = $this->getCategoryFlatTable(true);
		$joinTable = $this->getCategoryFlatTable();
	    
	    $subSelect = $this->getEmptySelect();
	    $subSelect->from(array($mainTableAlias => $mainTable), array( "product_id"));
	    $subSelect->joinLeft(
                    array($joinTableAlias => $joinTable),
                    $joinTableAlias.'.entity_id='.$mainTableAlias.'.category_id',
                    $this->getColumns($joinTableAlias,null,true)
                    );
        $subSelect->where($mainTableAlias.'.store_id=?', $this->getStoreId());
	    $subSelect->group('product_id');
	    
	    
	    
	    $select = $this->getSelect();
		$mainTableAlias = $this->getMainTable(true);
		$joinTableAlias = self::$this->getSubSelectTable($this->getProductCategoriesSql());
		$joinTable =  $this->getSubSelectTable($subSelect);
		
		$select->joinLeft(
                    array($joinTableAlias => $joinTable),
                    $joinTableAlias.'.product_id ='.$mainTableAlias.'.entity_id',
                    $this->getColumns($joinTableAlias)
                    );
		return $this;
	}
	
	/**
	 * Subselect is too time consumpting
	 */
	public function joinAllCategoriesCache()
	{
		$select = $this->getSelect();
		$mainTableAlias = $this->getMainTable(true);
		$joinTableAlias = self::NCC;
		$joinTable =  $this->getTable('aukro/cache_categories');
		
		$select->joinLeft(
                    array($joinTableAlias => $joinTable),
                    "{$joinTableAlias}.product_id ={$mainTableAlias}.entity_id AND {$joinTableAlias}.store_id = {$this->getStoreId()}",
                    $this->getColumns($joinTableAlias)
                    );
		return $this;
	}

	public function joinProductCategoryMaxLevel()
	{
		$mainTable = $this->getTable('catalog/category_product');
	    $mainTableAlias = self::CCPI;
		$this->joinCategoryLevel($mainTable,$mainTableAlias);
	}
	
	public function joinExportCategoryProductMaxLevel()
	{
		$mainTableAlias = self::NCP;
		$mainTable = $this->getTable('aukro/categoryproducts');
		$condition = "{$mainTableAlias}.export_id = {$this->getExportId()}";
		
		$this->joinCategoryLevel($mainTable,$mainTableAlias,$condition);
	}
	
	protected function joinCategoryLevel($mainTable,$mainTableAlias,$condition = null)
	{
	    $subSelect = $this->getEmptySelect();
	    $subSelect->from(array($mainTableAlias => $mainTable), array( "product_id"));
	    $subSelect->joinLeft( array('cce' => 'catalog_category_entity'), "cce.entity_id = $mainTableAlias.category_id",
            array("max_level" => "MAX(cce.level)")
        );
	    $subSelect->group('product_id');
	    
	    $select = $this->getSelect();
		$joinTableAlias = self::NCPML;
		$joinTable = $this->getSubSelectTable($subSelect);
		$select->join(
            array($joinTableAlias => $joinTable),
            $joinTableAlias.".max_level = cce.level AND {$joinTableAlias}.product_id = {$this->getMainTable(true)}.entity_id",
            $this->getColumns($joinTableAlias)
        );
		return $this;
	}
	
    /**
     * Joint export categoryproduct table
     */
	public function orderByCategory()
	{
		$select = $this->getSelect();
		$select->order("cce.entity_id");
	}
	
    /**
     * Joint export categoryproduct table
     */
	public function groupByProduct()
	{
		$select = $this->getSelect();
		$select->group($this->getMainTable(true).".entity_id");
	}
	
    /**
     * Joint export categoryproduct table
     */
	public function orderByProductType()
	{
		$select = $this->getSelect();
		$select->order($this->getMainTable(true).".type_id");
	}
	
	//**********************************COMMON PART*****************************************************
	public function joinProductRelation()
	{
		$joinTableAlias = self::CPR;
		$joinTable = $this->getTable("catalog/product_relation");
		
        $condition =  $joinTableAlias.'.child_id='.self::MAIN_TABLE_SUBST.".entity_id ";
        $this->joinMainTable($joinTableAlias,$joinTable,true,$condition);
        
        $select = $this->getSelect();
        $select->order("group_id");
        
        $this->addParentsCondition();
        
		return $this;
	}
	
	public function addParentsCondition()
	{
	    if($this->parentsOnly())
	    {
	        $select = $this->getSelect();
		    $select->where(self::CPR.".parent_id IS NULL ");
	    }
	}
	
	protected function parentsOnly()
	{
	    if($this->getCondition(Nostress_Aukro_Helper_Data_Loader::CONDITION_PARENTS_CHILDS,0) == Nostress_Aukro_Model_Config_Source_Parentschilds::PARENTS_ONLY)
	        return true;
	    else
	        return false;
	}
	
	public function addTypeCondition()
	{
	    $allTypes = $this->helper()->getAppProductTypes();
	    $attribute = Mage::getModel('catalog/product_type')->getTypes();
	    
	    $types = $this->getCondition(Nostress_Aukro_Helper_Data_Loader::CONDITION_TYPES,$allTypes);
	    
	    if(empty($types))
	        return $this;
	    if(!is_array($types))
	        $types = explode(",",$types);
	    
        if(count($allTypes) != count($types) )
        {
	        $select = $this->getSelect();
	        $types = "'".implode("','",$types) ."'";
		    $select->where($this->getMainTable(true).".type_id IN ({$types})");
	    }
	    
	    return $this;
	}
	
	 /**
     * Joint product table
     */
	protected function joinParentProductFlat()
	{
	    if($this->parentsOnly())
	        return $this;
	    			
		$mainTableAlias = self::CPR;
		$joinTableAlias = self::PCPF;
		$joinTable = $this->getProductFlatTable();

        $condition = $joinTableAlias.'.entity_id='.self::MAIN_TABLE_SUBST.'.parent_id';
        $this->joinTable($mainTableAlias,$joinTableAlias,$joinTable,true,$condition);
		return $this;
	}
	
	public function addVisibilityCondition()
	{
		$this->joinParentProductFlat();
		
		$select = $this->getSelect();
		
		$where = "{$this->getMainTable(true)}.visibility= ? ";
	    if(!$this->parentsOnly())
	        $where .= "OR ".self::PCPF.".visibility= ?";
		
		$select->where($where,self::VISIBILITY);

	}
	
	//////////////////////////////////STOCK////////////////////////////////////
	
	public function addStockCondition()
	{
	    if($this->getCondition(Nostress_Aukro_Helper_Data_Loader::CONDITION_EXPORT_OUT_OF_STOCK,0) == 0)
	    {
	        $this->joinParentStock();
	        
	        $select = $this->getSelect();
		    $select->where(self::CISS.".stock_status = ".self::STOCK_STATUS." AND (".self::CISI.".qty > 0 OR {$this->getProductFlatTable(true)}.type_id <> 'simple') ".
						"AND (".self::PCISS.".stock_status = ".self::STOCK_STATUS." OR ".self::PCISS.".stock_status IS NULL) ");
	    }
		
		return $this;
	}
	
	public function joinStock()
	{
		$this->joinNormalStockStatus();
		$this->joinNormalStockItem();
	}
	
	public function joinParentStock()
	{
		$this->joinParentStockStatus();
		$this->joinParentStockItem();

	}
	
	protected function joinNormalStockStatus()
	{
		$mainTableAlias = $this->getMainTable(true);
		$productIdColumnName = 'entity_id';
		$joinTableAlias = self::CISS;
		$this->joinStockStatus($mainTableAlias,$joinTableAlias,$productIdColumnName);
	}
	
	public function joinParentStockStatus()
	{
		$mainTableAlias = self::CPR;
		$productIdColumnName = 'parent_id';
		$joinTableAlias = self::PCISS;
		$this->joinStockStatus($mainTableAlias,$joinTableAlias,$productIdColumnName);
	}
	
	protected function joinStockStatus($mainTableAlias,$joinTableAlias,$productIdColumnName)
	{
		$joinIfColumnsEmpty = $this->getCondition(Nostress_Aukro_Helper_Data_Loader::CONDITION_EXPORT_OUT_OF_STOCK,0) == 0;
		$joinTable = $this->getTable("cataloginventory/stock_status");
		$condition = $joinTableAlias.'.product_id='.self::MAIN_TABLE_SUBST.".{$productIdColumnName} AND {$joinTableAlias}.website_id ={$this->getWebsiteId()} ";
		
		$this->joinTable($mainTableAlias,$joinTableAlias,$joinTable,$joinIfColumnsEmpty,$condition);
	}
	
	protected function joinNormalStockItem()
	{
		$mainTableAlias = $this->getMainTable(true);
		$productIdColumnName = 'entity_id';
		$joinTableAlias = self::CISI;
		$this->joinStockItem($mainTableAlias,$joinTableAlias,$productIdColumnName);
	}
	
	public function joinParentStockItem()
	{
		$mainTableAlias = self::CPR;
		$productIdColumnName = 'parent_id';
		$joinTableAlias = self::PCISI;
		$this->joinStockItem($mainTableAlias,$joinTableAlias,$productIdColumnName);
	}
	
	protected function joinStockItem($mainTableAlias,$joinTableAlias,$productIdColumnName)
	{
		$joinIfColumnsEmpty = $this->getCondition(Nostress_Aukro_Helper_Data_Loader::CONDITION_EXPORT_OUT_OF_STOCK,0) == 0;
		$joinTable = $this->getTable("cataloginventory/stock_item");
		$condition = $joinTableAlias.'.product_id='.self::MAIN_TABLE_SUBST.".{$productIdColumnName} ";
		
		$this->joinTable($mainTableAlias,$joinTableAlias,$joinTable,$joinIfColumnsEmpty,$condition);
	}
	
	public function joinSuperAttributesCache()
	{
	    $joinTableAlias = self::NCSA;
		$joinTable =  $this->getTable('aukro/cache_superattributes');
		$condition = "{$joinTableAlias}.product_id =".self::MAIN_TABLE_SUBST.".entity_id AND {$joinTableAlias}.store_id = {$this->getStoreId()}";
		
	    $this->joinMainTable($joinTableAlias,$joinTable,false,$condition);
	    return $this;
	}
	
	public function joinMediaGalleryCache()
	{
		$joinTableAlias = self::NCMG;
		$joinTable =  $this->getTable('aukro/cache_mediagallery');
		$condition = "{$joinTableAlias}.product_id =".self::MAIN_TABLE_SUBST.".entity_id AND {$joinTableAlias}.store_id = {$this->getStoreId()}";
		
	    $this->joinMainTable($joinTableAlias,$joinTable,false,$condition);
	    return $this;
	}
	
	public function joinReview()
	{
		$joinTableAlias = self::RRA;
		$joinTable =  $this->getTable('review/review_aggregate');
		$condition =  "{$joinTableAlias}.entity_pk_value = ".self::MAIN_TABLE_SUBST.".entity_id AND {$joinTableAlias}.store_id = {$this->getStoreId()}";
		
	    $this->joinMainTable($joinTableAlias,$joinTable,false,$condition);
	    return $this;
	}
	
    public function joinTaxonomy()
	{
	    $mainTableAlias = $this->getCategoryFlatTable(true);
		$joinTableAlias = self::NEC;
		$joinTable =  $this->getTable('aukro/enginecategory');
		$taxonomyColumn = $this->getTaxonomyColumnName();
		if(!isset($taxonomyColumn))
		    return $this;
        
		$locale = $this->getTaxonomyLocale();
		$code = $this->getTaxonomyCode();
        if(!isset($locale))
            return $this;
		       
		$condition =  "{$joinTableAlias}.id = ".self::MAIN_TABLE_SUBST.".{$taxonomyColumn} AND {$joinTableAlias}.locale = '{$locale}' AND {$joinTableAlias}.taxonomy_code = '{$code}' ";
		
	    if($this->joinTable($mainTableAlias,$joinTableAlias,$joinTable,false,$condition))
	    	$this->checkCategoryAttributes(array($taxonomyColumn));
	    	
	    return $this;
	}
	
	public function joinParentCategory()
	{
		$mainTableAlias = $this->getCategoryFlatTable(true);
		$joinTableAlias = $this->getCategoryFlatTable(true,null,true);
		$joinTable =  $this->getCategoryFlatTable();
		       
		$condition =  "{$joinTableAlias}.entity_id = ".self::MAIN_TABLE_SUBST.".parent_id";
	    $this->joinTable($mainTableAlias,$joinTableAlias,$joinTable,false,$condition);
	    	    	
	    return $this;
	}
	
	public function joinProductEntity()
	{
		$mainTableAlias = $this->getMainTable(true);
		$joinTableAlias = self::CPE;
		$joinTable =  $this->getTable('catalog/product');
		       
		$condition =  "{$joinTableAlias}.entity_id = ".self::MAIN_TABLE_SUBST.".entity_id";
	    $this->joinTable($mainTableAlias,$joinTableAlias,$joinTable,false,$condition);
	    	    	
	    return $this;
	}
	
	public function joinTax()
	{
	    $joinTableAlias = self::NCT;
		$joinTable =  $this->getTable('aukro/cache_tax');
		$condition =  "{$joinTableAlias}.tax_class_id = ".self::MAIN_TABLE_SUBST.".tax_class_id AND {$joinTableAlias}.store_id = {$this->getStoreId()}";
		$priceColumns = $this->getColumns(self::CIP);
		$joinIfColumnsEmpty = !empty($priceColumns);
		
	    $this->joinMainTable($joinTableAlias,$joinTable,$joinIfColumnsEmpty,$condition);
	    return $this;
	}
	
	public function joinPrice()
	{
	    $joinTableAlias = self::CIP;
		$joinTable = $this->getTable('catalogindex/price');
		$condition =   "{$joinTableAlias}.entity_id = ".self::MAIN_TABLE_SUBST.".entity_id AND {$joinTableAlias}.website_id = {$this->getWebsiteId()} AND {$joinTableAlias}.customer_group_id = 0";
		
	    $this->joinMainTable($joinTableAlias,$joinTable,false,$condition);
	    return $this;
	}
	
	protected function definePriceColumns()
	{
		$originalPricesIncludesTax = Mage::helper('tax')->priceIncludesTax($this->getStore());
		$currencyRate = $this->helper()->getStoreCurrencyRate($this->getStore());
		$taxRateColumnName = self::NCT.'.tax_percent';
		
		$this->_columns[self::CIP] = array("price_final_exclude_tax" => $this->getPriceColumnFormat("min_price",false),//"final_price",false),
		 									"price_final_include_tax" => $this->getPriceColumnFormat("min_price",true),//"final_price",true),
		 									"price_original_exclude_tax" => $this->getPriceColumnFormat("price",false),
		 									"price_original_include_tax" => $this->getPriceColumnFormat("price",true),
		 									//"price_minimal_exclude_tax" => $this->getPriceColumnFormat("min_price",false),
		 									//"price_minimal_include_tax" => $this->getPriceColumnFormat("min_price",true)
		 									);
	}
	
	protected function getPriceColumnFormat($comunName,$includeTax)
	{
		$originalPricesIncludesTax = Mage::helper('tax')->priceIncludesTax($this->getStore());
		$currencyRate = $this->helper()->getStoreCurrencyRate($this->getStore());
		$taxRateColumnName = self::NCT.'.tax_percent';
		
		$columnFormat = $this->helper()->getPriceColumnFormat(self::CIP.".".$comunName, $taxRateColumnName,$currencyRate, $originalPricesIncludesTax,$includeTax);
		return $columnFormat;
	}
	
	protected function joinMainTable($joinTableAlias,$joinTable,$joinIfColumnsEmpty = false,$condition)
	{
	    $mainTableAlias = $this->getMainTable(true);
	    return $this-> joinTable($mainTableAlias,$joinTableAlias,$joinTable,$joinIfColumnsEmpty,$condition);
	}
	
	protected function joinTable($mainTableAlias,$joinTableAlias,$joinTable,$joinIfColumnsEmpty = false,$condition)
	{
	    $selectColumns =  $this->getColumns($joinTableAlias);
	    if(empty($selectColumns) && !$joinIfColumnsEmpty)
	        return false;
	        
	    $select = $this->getSelect();
		
		$condition = str_replace(self::MAIN_TABLE_SUBST,$mainTableAlias,$condition);
		
		$select->joinLeft(
                    array($joinTableAlias => $joinTable),
                    $condition,
                    $selectColumns
                    );
       	
		return true;
	}
}
