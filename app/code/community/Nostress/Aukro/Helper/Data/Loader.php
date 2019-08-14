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
* Helper.
*
* @category Nostress
* @package Nostress_Aukro
*
*/

class Nostress_Aukro_Helper_Data_Loader extends Nostress_Aukro_Helper_Data
{
    const FLAT_CATALOG_ERROR = 1004;
    
    const GROUP_ROW_SEPARATOR = ";;";
    const GROUP_ROW_ITEM_SEPARATOR = "||";
    const GROUP_CONCAT = "GROUP_CONCAT";
    const GROUP_SEPARATOR = "SEPARATOR";
    const GROUPED_COLUMN_ALIAS = "concat_colum";
    
    const COLUMN_CATEGORIES_DATA = "ccd";
	
    const CONDITION_EXPORT_OUT_OF_STOCK = 'export_out_of_stock';
    const CONDITION_PARENTS_CHILDS = 'parents_childs';
    const CONDITION_TYPES = 'types';
    const CONDITION_PRODUCT_IDS = 'product_ids';
    const DISABLED = "disabled";
    
    public function getCommonParameters()
    {
    	$params = array();
    	
    	$params[self::PARAM_REVIEW_URL] = $this->getGeneralConfig(self::PARAM_REVIEW_URL);
    	$params[self::PARAM_IMAGE_FOLDER] = $this->getGeneralConfig(self::PARAM_IMAGE_FOLDER);
    	
    	return $params;
    }
    
    public function groupConcatColumns($columns)
    {
        $res = self::GROUP_CONCAT . "(";
        $columnValues = array_values($columns);
        
        $columnString = "";
        $separator = $this->getGroupRowItemSeparator();
        foreach ($columnValues as $value)
        {
            if(empty($columnString))
                $columnString = $value;
            else
        	    $columnString .= ",'{$separator}',".$value;
        }
        $res .= $columnString." ".self::GROUP_SEPARATOR." '{$this->getGroupRowSeparator()}'";
        
        $res .= ") as ".self::GROUPED_COLUMN_ALIAS;
        return $res;
    }
    
    protected function getGroupRowSeparator()
    {
        return self::GROUP_ROW_SEPARATOR;
    }
    
    protected function getGroupRowItemSeparator()
    {
        return self::GROUP_ROW_ITEM_SEPARATOR;
    }
    
	public function getPriceColumnFormat($columnName, $taxRateColumnName,$currencyRate = null, $originalPriceIncludeTax=false,$calcPriceIncludeTax = true, $round=true)
	{
	
		$resSql = $columnName;
		
		if(isset($currencyRate) && is_numeric($currencyRate))
		{
			$resSql .= "*".$currencyRate;
		}
		
		if(!$originalPriceIncludeTax && $calcPriceIncludeTax)
		{
			$resSql .= "*(1+ IFNULL(".$taxRateColumnName.",0))";
		}
		else if($originalPriceIncludeTax && !$calcPriceIncludeTax)
		{
			$resSql .= "*(1/(1+ IFNULL(".$taxRateColumnName.",0)))";
		}
	    	
	    if ($round) {
	    	$resSql = $this->getRoundSql($resSql);
	    }
	   
	    return $resSql;
	}
	
	public function getRoundSql($column,$decimalPlaces = 2)
	{
		return "ROUND(".$column.",{$decimalPlaces})";
	}
	
	public function getStoreCurrencyRate($store)
	{
		$from = Mage::app()->getBaseCurrencyCode();
		$to = $store->getCurrentCurrencyCode();

		if($from == $to)
			return null;
		else
			return $this->getCurrencyRate($from,$to);
	}
	
	protected function getCurrencyRate($from,$to)
	{
		return Mage::getModel('directory/currency')->load($from)->getRate($to);
	}
				
	public  function getProductFlatColumns($storeId)
	{
        $productFlatResource = Mage::getResourceModel('catalog/product_flat')->setStoreId($storeId);
        return $productFlatResource->getAllTableColumns();
	}
	
	public  function getCategoryFlatColumns($storeId)
	{
        $flatResource = Mage::getResourceModel('catalog/category_flat')->setStoreId($storeId);
        $describe =  Mage::getSingleton('core/resource')->getConnection('core_write')->describeTable($flatResource->getMainTable());
        return array_keys($describe);
	}
	
	public function getFlatCatalogErrorCode() {
	    return self::FLAT_CATALOG_ERROR;
	}
	
	public function getLoaderAttributes()
	{
		$resource = Mage::getResourceModel('aukro/data_loader_product_aukro');
		
		$columns = $resource->getAllColumns();
		$staticColumns = $resource->getStaticColumns();
		$staticColumns = array_combine($staticColumns, $staticColumns);
		$columns = array_merge($columns,$staticColumns);
		$multiColumns = $resource->getMultiColumns();
		
		ksort($columns);
		$attributes = array();
		foreach ($columns as $alias => $column)
		{
			$attribute = array();
			$attribute[self::VALUE] = $alias;
			$attribute[self::LABEL] = $this->codeToLabel($alias);
			if(in_array($alias,$multiColumns))
				$attribute[self::DISABLED] = "1";
			$attributes[$attribute[self::VALUE]] = $attribute;
		}
		return $attributes;
	}

	/**
	 * Retrieve adminhtml session model object
	 *
	 * @return Mage_Adminhtml_Model_Session
	 */
	protected function _getSession()
	{
	    return Mage::getSingleton('adminhtml/session');
	}
	
	public function reindexFlatCatalogs() {
	    
	    $this->_reindexFlatCatalogCategoryIndex();
	    $this->_reindexFlatCatalogProductIndex();
	    $this->_cacheFlushAll();
	}
	
	protected function _reindexFlatCatalogCategoryIndex()
	{
	    $process = Mage::getModel('index/process')->load(Nostress_Aukro_Helper_Data::CATALOG_CATEGORY_FLAT_PROCESS_CODE, 'indexer_code');
	    $this->_reindexProcess($process);
	}
	
	protected function _reindexFlatCatalogProductIndex()
	{
	    $process = Mage::helper('catalog/product_flat')->getProcess();
	    $this->_reindexProcess($process);
	}
	
	protected function _reindexProcess($process)
	{
	    if ($process)
	    {
	        try
	        {
	            $process->reindexEverything();
	            $this->_getSession()->addSuccess(
	                    Mage::helper('index')->__('%s index was rebuilt.', $process->getIndexer()->getName())
	            );
	        } catch (Mage_Core_Exception $e) {
	            $this->_getSession()->addError($e->getMessage());
	        } catch (Exception $e) {
	            $this->_getSession()->addException($e,
	                    Mage::helper('index')->__('There was a problem with reindexing process.')
	            );
	        }
	    }
	    else
	    {
	        $this->_getSession()->addError(
	                Mage::helper('index')->__('Cannot initialize the indexer process.')
	        );
	    }
	}
	
	/**
	 * Flush cache storage
	 */
	protected function _cacheFlushAll()
	{
	    Mage::dispatchEvent('adminhtml_cache_flush_all');
	    Mage::app()->getCacheInstance()->flush();
	    $this->_getSession()->addSuccess(Mage::helper('adminhtml')->__("The cache storage has been flushed."));
	}
}