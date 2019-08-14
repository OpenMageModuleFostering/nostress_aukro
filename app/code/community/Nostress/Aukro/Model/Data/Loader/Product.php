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
* Product loader for export process
* @category Nostress
* @package Nostress_Aukro
*
*/

class Nostress_Aukro_Model_Data_Loader_Product extends Nostress_Aukro_Model_Data_Loader
{
    const PRODUCTS_FILTER = 0;
    const PRODUCTS_ALL = 1;
    const PRODUCTS_FILTER_BY_CATEGORY = 2;
    const PRODUCTS_ALL_BY_CATEGORY = 3;
    
    
    public function _construct()
    {
        // Note that the export_id refers to the key field in your database table.
        $this->_init('aukro/data_loader_product', 'entity_id');
    }

    public function initAdapter()
    {
        parent::initAdapter();
        
        if($this->getReloadCache())
        	$this->reloadCache();
        $this->basePart();
        $this->commonPart();
        //echo $this->adapter->getSelect()->__toString();
        //exit();
    }
    
    protected function reloadCache()
    {
    	Mage::getModel('aukro/cache_categories')->reload();
    	Mage::getModel('aukro/cache_categorypath')->reload();
        Mage::getModel('aukro/cache_superattributes')->reload();
        Mage::getModel('aukro/cache_mediagallery')->reload();
        Mage::getModel('aukro/cache_tax')->reload();
       
    }
    
    //***************************BASE PART**************************************
    protected function basePart()
    {
    	$filterByProducts = $this->getUseProductFilter();
    	$groupByCategory = $this->getGroupByCategory();
    	
    	if($filterByProducts && $groupByCategory)
    	{
    		$this->productsFilterByCategory();
    	}
        else if(!$filterByProducts && $groupByCategory)
    	{
    		$this->productsAllByCategory();
    	}
        else if(!$filterByProducts && !$groupByCategory)
    	{
    		$this->productsAll();
    	}
    	else
    		$this->productsFilter();
    }
    
    /**
     * Init sql.
	 * Load filteres products from current store.
     */
	protected function productsFilter()
	{
		$this->adapter->joinProductFilter ();
		if ($this->loadAllProductCategoriesCondition())
		{
			//add all category information
			$this->adapter->joinAllCategoriesCache ();
		      
		}
		else
		{
			$this->adapter->joinCategoryFlat ();
			$this->adapter->joinExportCategoryProductMaxLevel ();
			$this->adapter->joinTaxonomy();
			$this->adapter->joinParentCategory();
		}
		
		$this->adapter->groupByProduct ();
	}
	
	/**
	 * Init sql.
	 * Load all products from current store.
	 */
	protected function productsAll()
	{
		if ($this->loadAllProductCategoriesCondition())
		{
			//add all category information
			$this->adapter->joinAllCategoriesCache ();
		}
		else
		{
			//add category information
			$this->adapter->joinCategoryProduct ();
			$this->adapter->joinCategoryFlat ();
			$this->adapter->joinProductCategoryMaxLevel();
			//$this->adapter->joinTaxonomy();
			//$this->adapter->joinParentCategory();
		}
		$this->adapter->groupByProduct();
	}
	
	/**
	 * Init sql.
	 * Load all products from current store, order by category.
	 */
	protected function productsAllByCategory()
	{
		$this->adapter->joinCategoryProduct ();
		$this->adapter->joinCategoryFlat ();
		
		if (!$this->loadAllProductCategoriesCondition())
		{
			//one category per product
			$this->adapter->joinProductCategoryMaxLevel ();
			$this->adapter->groupByProduct ();
		}
		$this->adapter->orderByCategory ();
		$this->adapter->joinTaxonomy();
		$this->adapter->joinParentCategory();
	}
	
	/**
	 * Init sql.
	 * Load filtered products from current store, order by category.
	 */
	protected function productsFilterByCategory()
	{
		$this->adapter->joinProductFilter ();
		//add category information
		$this->adapter->joinCategoryFlat ();
		
		if(!$this->loadAllProductCategoriesCondition())
		{
			//one category per product
			$this->adapter->joinExportCategoryProductMaxLevel ();
			$this->adapter->groupByProduct ();
		}
		$this->adapter->orderByCategory ();
		$this->adapter->joinTaxonomy();
		$this->adapter->joinParentCategory();
	}
	
	protected function loadAllProductCategoriesCondition()
    {
    	$allProductCategories = $this->getLoadAllProductCategories();
    	return $allProductCategories;
    }
    
    //***************************COMMON PART**************************************
    
    protected function commonPart()
    {
    	$this->adapter->joinProductEntity();
    	$this->adapter->joinProductRelation();
    	$this->adapter->addTypeCondition();
    	$this->visibility();
    	$this->adapter->orderByProductType();
    	
    	$this->stock();
        $this->adapter->joinSuperAttributesCache();
        $this->adapter->joinMediaGalleryCache();
        $this->adapter->joinReview();
        $this->price();
    }

    protected function visibility()
    {
    	$this->adapter->addVisibilityCondition();
    }
    
    protected function stock()
    {
    	$this->adapter->joinStock();
    	$this->adapter->addStockCondition();
    }
    
    protected function price()
    {
    	$this->adapter->joinTax();
    	$this->adapter->joinPrice();
    }
}
