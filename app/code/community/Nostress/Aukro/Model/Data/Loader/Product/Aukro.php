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
* Aukro loader for export process
* @category Nostress
* @package Nostress_Aukro
*
*/

class Nostress_Aukro_Model_Data_Loader_Product_Aukro extends Nostress_Aukro_Model_Data_Loader_Product
{
	const PARAM_BATCH_SIZE = 100;
  	const DEF_GROUP_BY_CATEGORY = 0;
  	const DEF_RELOAD_CACHE = 0;
	
  	public function _construct()
    {
        // Note that the export_id refers to the key field in your database table.
        $this->_init('aukro/data_loader_product_aukro', 'entity_id');
    }
    
	protected function getDefaultParams()
	{
		$params = array();
        $params["export_id"] = -1;
        $params["batch_size"] = self::PARAM_BATCH_SIZE;
        $params["use_product_filter"] = "0";
        $params["group_by_category"] = self::DEF_GROUP_BY_CATEGORY;
        $params["reload_cache"] = self::DEF_RELOAD_CACHE;
        $params["stock_status_dependence"] = "";
        $params['load_all_product_categories'] = "0";
        $params['conditions'] = array(
            Nostress_Aukro_Helper_Data_Loader::CONDITION_EXPORT_OUT_OF_STOCK => 1
        );
        return $params;
	}
	
	public function init($params,$mergeWithDefault = false)
	{
		$defParams = $this->getDefaultParams();
		$params = array_merge($defParams,$params);
		parent::init($params);
	}
	
	public function getGridCollection($params)
	{
		$adapterParams = $this->getDefaultParams();
		$adapterParams = array_merge($adapterParams,$params);
		$this->init($adapterParams);

		$collection = $this->getCollection();
		$select = $this->adapter->getSelect($params);
		$collection->setSelect($select,$adapterParams["store_id"]);
					
		return $collection;
	}
	
	protected function loadAllProducts()
	{
		$products = array();
		while(count($batch = $this->loadBatch()) > 0)
        {
        	$products += $batch;
        }
        return $products;
	}
	
 	protected function visibility()
    {
    	//$this->adapter->addVisibilityCondition();
    }
    
    protected function commonPart()
    {
    	parent::commonPart();
    	$this->adapter->joinCategoryMapping();
    	$this->adapter->addProductIdsCondition();
    }
}
