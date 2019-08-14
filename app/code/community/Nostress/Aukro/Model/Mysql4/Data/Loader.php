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
* Resource  of product loader for export process
* @category Nostress
* @package Nostress_Aukro
*
*/

class Nostress_Aukro_Model_Mysql4_Data_Loader extends Nostress_Aukro_Model_Mysql4_Abstract
{
    //Category product relation table
    const CPF = 'cpf';
    const CPE = 'cpe'; //catalog product entity
    const PCPF = 'pcpf'; //Parent catalog product flat
    const CCF = 'ccf';
    const PCCF = 'pccf'; //Parent category flat
    const CCPI = 'ccpi';
    const CPR = 'cpr';  //Catalog product relation
    const CISS = 'ciss'; //Catalog inventory stock status
    const PCISS = 'pciss'; //Parent product join of Catalog inventory stock status
    const CISI = 'cisi'; //Catalog inventory stock item
    const CPAMG = 'cpamg'; //Catelog product entity media gallery
	const CPAMGV = 'cpamgv'; //Catelog product entity media gallery value
    const PCISI = 'pcisi'; //Parent product - Catalog inventory stock item
    const CPSA = 'cpsa'; //Catalog product super attribute
	const CPSAL = 'cpsal'; //Catalog product super attribute label
	const EA = 'ea'; //Entity attribute
    const RRA = 'rra'; //Review aggregate
	const CIP = 'cip'; //Catalog product price index
    
    const NPACD = 'npacd'; // Nostress product all category data
    const NCPML = 'ncpml'; // Nostress category product max level
    const NCCP = 'nccp'; //Nostress cache category path
    const NCSA = 'ncsa'; //Nostress cache super attributes
    const NCT = 'nct'; //Nostress cache tax
    const NCC = 'ncc'; //Nostress cache categories
	const NCMG = 'ncmg';//Nostress media gallery
    const NR = 'nr';   //Nostress records
    const NCP = 'ncp'; //Nostress category path
	const NSA = 'nsa'; // Nostress super attributes
    const NEC = 'nec'; //Nostress engine category
    
	const DEF_IMAGE_FOLDER = "catalog/product";
	const DEF_REVIEW_URL = "review/product/list/id/";
    
	const VALUE_COLUMN_SUFFIX = '_value';
    /**
     * Current scope (store Id)
     *
     * @var int
     */
    protected $_storeId;
    protected $_websiteId;
    protected $_columns;
    protected $_productFlatMandatoryColumns = array();
    protected $_multiColumns = array();
    protected $_staticColumns = array();
    protected $_exportId;
    protected $_select;
    protected $_params;
    protected $_helper;
    protected $_atttibutes = '*';
    protected $_defaultAttributes = array();
    protected $_conditions = array();
    protected $_taxonomyCode;
    protected $_batchSize = 1000;
    protected $_offset = 0;
    
    public function _construct()
    {
        $this->_init('catalog/product', 'entity_id');
    }
    
    public function loadBatch()
    {
    	$select = clone $this->getSelect();
    	$batchSize = $this->getBatchSize();
    	$offset = $this->getOffset();
    	
    	$select->limit($batchSize,$offset);
    	
    	//echo $select->__toString();
    	//exit();
    	
    	$batch = $this->runSelectQuery($select);
    	$this->setOffset($batchSize+$offset);
    	
    	return $batch;
    }
        
    protected function defineColumns()
    {
        $this->_columns = array();
        
        $this->_columns['cce'] = array( "category_id" => "entity_id",
                                                                  	"category_path_ids" => "path",
                                                                 	"category_level" => "level",
                                                                 	"category_parent_id" => "parent_id");
        
        $this->_columns['cev'] = array("category_name" => "value");
        
        
        //$this->defineCategoryTaxonomyColumns();
        
       	//$this->_columns[self::NCCP] = array("category_path" => "category_path");
    }
    
    protected function defineCategoryTaxonomyColumns()
    {
        $tableAlias = $this->getCategoryFlatTable(true);
        $columns = Mage::getModel('aukro/taxonomy_setup')->getTaxonomyAttributeCodes();
        if(empty($columns))
            return;
        
        $columns = array_combine($columns,$columns);
        
        $this->_columns[$tableAlias] = array_merge($this->_columns[$tableAlias],$columns);
    }
    
    protected function getColumns($tableAlias,$defualt = null,$groupConcat = false,$addTablePrefix = false)
    {
        if(array_key_exists($tableAlias,$this->_columns))
            $result = $this->_columns[$tableAlias];
        else
            $result = $defualt;
            
       if($groupConcat)
           	$result = $this->groupConcatColumns($result);
       if($addTablePrefix)
			$result = $this->addTablePrefix($tableAlias,$result);
       return $this->filterColumns($result);
    }
    
    public function getAllColumns()
    {
    	if(!isset($this->_columns))
    		$this->defineColumns();
    	
        $columns = array();
        foreach ($this->_columns as $tableColumns)
        {
        	$columns = array_merge($columns,$tableColumns);
        }
        return $columns;
    }
    
    public function getMultiColumns()
    {
    	return $this->_multiColumns;
    }
    
    public function getStaticColumns()
    {
    	return $this->_staticColumns;
    }
    
    protected function filterColumns($columns)
    {
        if($this->_atttibutes == '*')
            return $columns;
        
        if(!isset($columns) || !is_array($columns))
        	return $columns;
            
        foreach ($columns as $key => $column)
        {
            if(!in_array($key,$this->_atttibutes))
                unset($columns[$key]);
        }
        
        return $columns;
    }
    
	protected function addTablePrefix($tableAlias,$columns)
    {
    	foreach ($columns as $key => $value)
    	{
    		$columns[$key] = $tableAlias.".".$value;
    	}
    	
    	return $columns;
    }
    
    protected function groupConcatColumns($columns)
    {
        return $this->helper()->groupConcatColumns($columns);
    }
    
    protected function getWebsiteId()
    {
        if (is_null($this->_websiteId)) {
            $this->_websiteId = Mage::app()->getStore($this->getStoreId())->getWebsiteId();
        }
        return $this->_websiteId;
    }
    
    protected function getProductFlatTable($alias = false,$storeId = null)
    {
        if (is_null($storeId))
        {
            $storeId = $this->getStoreId();
        }
        
        if($alias)
            return self::CPF.$storeId;
        else
            return  Mage::getResourceModel('catalog/product_flat')->getFlatTableName($storeId);
    }
    
    protected function getCategoryFlatTable($alias = false,$storeId = null,$parent = false)
    {
        if (is_null($storeId))
        {
            $storeId = $this->getStoreId();
        }
        
        if($alias)
        {
        	if($parent)
        		return self::PCCF.$storeId;
        	else
        		return self::CCF.$storeId;
        }
        else
            return  Mage::getResourceModel('catalog/category_flat')->getMainStoreTable($storeId);
    }
    
    public function setExportId($id)
    {
        $this->_exportId = $id;
    }
    
    public function getExportId()
    {
        return $this->_exportId;
    }
    
    
    public function setTaxonomyCode($code)
    {
        $this->_taxonomyCode = $code;
    }
    
    public function  getTaxonomyCode()

    {
        return $this->_taxonomyCode;
    }
    
    public function setBatchSize($size)
    {
        $this->_batchSize = $size;
    }
    
    public function getBatchSize()
    {
        return  $this->_batchSize;
    }
    
    public function setOffset($offset)
    {
    	$this->_offset = $offset;
    }
    
    public function getOffset()
    {
    	return $this->_offset;
    }
    
    public function setAttributes($attributes)
    {
        if(is_array($attributes) && isset($this->_defaultAttributes) && !empty($this->_defaultAttributes))
        {
            $attributes = array_merge($attributes,$this->_defaultAttributes);
        }
        $this->_atttibutes = $attributes;
        $this->addSuperAttributes();
    }
    
    protected function addSuperAttributes()
    {
        if(!in_array("super_attributes",$this->_atttibutes))
            return;
            
        $superAttributes = Mage::getResourceModel('aukro/cache_superattributes')->getAllSuperAttributes();
        $this->_atttibutes = array_merge($superAttributes,$this->_atttibutes);
    }
    
    public function getAttributes()
    {
        return $this->_atttibutes;
    }
    
    protected function getUnknownAttributes()
    {
        $unknownAttributes = array();
        $allColumns = $this->getAllColumns();
        if(is_array($this->_atttibutes))
	        foreach ($this->_atttibutes as $attribName)
	        {
	        	if(!array_key_exists($attribName,$allColumns))
	        	    $unknownAttributes[] = $attribName;
	        }
	    foreach($unknownAttributes as $key => $value)
	    {
	    	if(in_array($value, $this->_staticColumns))
	    		unset($unknownAttributes[$key]);
	    }
        return $unknownAttributes;
    }
    
    public function setConditions($conditions)
    {
        $this->_conditions = $conditions;
    }
    
    public function getConditions()
    {
        return $this->_conditions;
    }
    
    public function getCondition($name,$default = null)
    {
        if(array_key_exists($name,$this->_conditions))
            return $this->_conditions[$name];
        else
            return $default;
    }
    
    /**
     * Set store scope
     *
     * @param int|string|Mage_Core_Model_Store $store
     * @return Mage_Catalog_Model_Resource_Collection_Abstract
     */
    public function setStore($store)
    {
        $this->setStoreId(Mage::app()->getStore($store)->getId());
        return $this;
    }
    
    /**
     * Set store scope
     *
     * @param int|string|Mage_Core_Model_Store $storeId
     * @return Mage_Catalog_Model_Resource_Collection_Abstract
     */
    public function setStoreId($storeId)
    {
        if ($storeId instanceof Mage_Core_Model_Store) {
            $storeId = $storeId->getId();
        }
        $this->_storeId = (int)$storeId;
        return $this;
    }

    /**
     * Return current store id
     *
     * @return int
     */
    public function getStoreId()
    {
        if (is_null($this->_storeId)) {
            $this->setStoreId(Mage::app()->getStore()->getId());
        }
        return $this->_storeId;
    }
    
    protected function getStore()
    {
    	return Mage::app()->getStore($this->getStoreId());
    }
    
    protected function getEmptySelect()
    {
    	$select = $this->_getReadAdapter()->select();
    	$res = clone $select;
    	$res->reset();
    	return $res;
    }
    
    protected function getSubSelectTable($select)
    {
    	return new Zend_Db_Expr( "(" . $select . ") ");
    }
    
    public function getSelect()
    {
    	if(!isset($this->_select))
    	{
    		$this->_select = $this->getEmptySelect();
    	}
    	return $this->_select;
    }
    
    protected function helper()
    {
        if(!isset($this->_helper))
    	    $this->_helper = Mage::helper('aukro/data_loader');
    	return $this->_helper;
    }
    
    protected function getParam($index,$default = null)
    {
    	if(!isset($this->_params))
    		$this->_params = $this->helper()->getCommonParameters();
    	if(array_key_exists($index,$this->_params))
    		return $this->_params[$index];
    	else
    		return $default;
    }
    
    protected function getImageFolder()
    {
    	//$this->getParam(Nostress_Aukro_Helper_Data_Loader::PARAM_IMAGE_FOLDER,self::DEF_IMAGE_FOLDER);
    	
    	return self::DEF_IMAGE_FOLDER;
    }
    
    protected function getReviewUrlPrefix()
    {
    	return $this->getParam(Nostress_Aukro_Helper_Data_Loader::PARAM_REVIEW_URL,self::DEF_REVIEW_URL);
    }
    
    protected function getStoreBaseUrl()
    {
        return $this->getStore()->getBaseUrl();
    }
    
    protected function getBaseUrl()
    {
        return Mage::getBaseUrl('media');
    }
    
    protected function checkProductAttributes($attributes)
    {
        $flatColumns = $this->helper()->getProductFlatColumns($this->getStoreId());
		return $this->checkAttributes($attributes,$flatColumns);
    }
    
    protected function checkCategoryAttributes($attributes)
    {
		$flatColumns = $this->helper()->getCategoryFlatColumns($this->getStoreId());
		return $this->checkAttributes($attributes,$flatColumns,'category');
    }
    
    protected function checkAttributes($attributes,$flatColumns,$flatCatalogType = 'product')
    {
        $missingAttributes = array();
        foreach($attributes as $attribute)
        {
            if(!in_array($attribute,$flatColumns))
                $missingAttributes[] = $attribute;
        }
        
        if(count($missingAttributes) > 0)
        {
            $message = $this->helper()->__("Following attributes missing in {$flatCatalogType} flat catalog: %s",implode(",",$missingAttributes));
            $this->helper()->log($message);
            throw new Exception($message, $this->helper()->getFlatCatalogErrorCode());
            
            return false;
        }
        
        return true;
    }
    
    protected function prepareColumnsFromAttributes($attributes)
    {
        $columns = array();
        $allAttributesInfo = $this->helper()->getVisibleProductAttributes(true);
        foreach ($attributes as $attributeCode)
        {
            if(!array_key_exists($attributeCode,$allAttributesInfo))
            {
                $message = $this->helper()->__("Attribute with code %s doesn't exist",$attributeCode);
                $this->helper()->log($message);
                throw new Exception($message);
            }
            
            $info = $allAttributesInfo[$attributeCode];
            
            $value = $attributeCode;
            switch($info->getFrontendInput())
            {
                case 'media_image':
                    $value = $this->mediaImageColumn($attributeCode);
                    break;
                case 'multiselect':
                case 'select':
                    // nostress select ignore _value suffix
                    $sourcemodel = Mage::getModel( $info->getSourceModel());
                    if( $sourcemodel !== null && $sourcemodel instanceof Nostress_Aukro_Model_Adminhtml_System_Config_Source_Abstract) {
                        break;
                    }
                    $value = $this->multiSelectColumn($attributeCode);
                    break;
                default:
                    break;
            }
            
            $columns[$attributeCode] = $value;
        }
        return $columns;
    }
    
    protected function mediaImageColumn($code)
    {
         return "CONCAT('{$this->getBaseUrl()}','{$this->getImageFolder()}', {$this->getProductFlatTable(true)}.{$code})";
    }
    
    protected function multiSelectColumn($code)
    {
        return $code.self::VALUE_COLUMN_SUFFIX;
    }
    
	protected function getTaxonomyColumnName()
	{
	    $taxonomyCode = $this->getTaxonomyCode();
	    if(!isset($taxonomyCode))
            return null;
        return $this->helper()->createCategoryAttributeCode($this->_taxonomyCode);
	}
	
	protected function getTaxonomyLocale()
	{
	    $taxonomyCode = $this->getTaxonomyCode();
	    if(!isset($taxonomyCode))
            return null;
            
	    $locale = $this->helper()->getStoreLocale($this->getStore());
	    $taxonomyModel = Mage::getModel('aukro/taxonomy');
	    
	    $cols = $taxonomyModel->countColumns($taxonomyCode,$locale);
	    if(!isset($cols) || $cols == "0")
	    {
	        $cols = $taxonomyModel->countColumns($taxonomyCode);
	        if(!isset($cols) || $cols == "0")
	        {
	            $message = $this->helper()->log($this->helper()->__("Taxonomy table empty"));
	            return null;
	        }
	        $locale = Nostress_Aukro_Model_Taxonomy::ALL_LOCALES;
	    }
	    return $locale;
	}
}

