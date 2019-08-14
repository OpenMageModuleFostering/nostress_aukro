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

class Nostress_Aukro_Model_Categoryproducts extends Mage_Core_Model_Abstract 
{
	protected $_store;
	
	public function _construct() 
	{
		parent::_construct ();
		$this->_init ( 'aukro/categoryproducts' );
	}
	
	public function updateProductAssignment($product)
	{
		$this->removeProductFromAllProfiles($product->getId());
		
		$relationsUpdated = false;
		foreach($product->getStoreIds() as $productStoreViewId)
		{
			$profileCollection =  Mage::getModel('aukro/profile')->getCollectionByStoreId($productStoreViewId);
			$categoryIds = $product->getCategoryIds();

			foreach($profileCollection as $profile)
			{
				foreach($categoryIds as $categoryId)
				{
					if($this->isCategoryInProfile($categoryId,$profile->getId()))
					{
						$this->addProductToProfile($product->getId(),$categoryId,$profile->getId());
						$relationsUpdated = true;
					}
				}
			}			
		}	
		if($relationsUpdated)
			Mage::getSingleton('adminhtml/session')->addSuccess(
                	Mage::helper('aukro')->__('The export profile-product relations has been updated.')
            	);
	} 
	
	public function updateCategoryProducts($profileId,$categoryproducts,$storeId)
	{
		$this->_store = Mage::app()->getStore($storeId);
		$catProdParsed = $this->parseCategoryProducts($categoryproducts);
		$this->runQuery($profileId,$catProdParsed,true);
	}
	
	public function getExportCategoryProducts($profileId)
	{
		$collection = $this->loadCategoryProducts($profileId);
		return $this->prepareCategoryProductResult($collection);
	}
	
	public function getExportCategories($profileId)
	{
		$collection = $this->loadCategoryProducts($profileId);
		return $this->prepareCategoriesArray($collection);
	}
	
	public function isCategoryInProfile($categoryId,$profileId)
	{
		return $this->getResource()->isCategoryInProfile($categoryId,$profileId);
	}
	
	public function getExportRelationIdsFiltred($profileId)
	{
		$collection = $this->loadCategoryProducts($profileId);
		return $this->prepareFilterRelationIdsCollection($collection);
	}
	
	public function getCategoryProductsCount($profileId)
	{
		return $this->getResource()->getProfileRecordCount($profileId);
	}
	
	public function addProductToProfile($productId,$categoryId,$profileId)
	{
		$categoryproducts = array($categoryId => array($productId));
		$this->runQuery($profileId,$categoryproducts,false);
	}
	
	public function removeProductFromAllProfiles($productId)
	{
		$this->getResource()->deleteRecords(null,$productId);
	}
	
	private function prepareFilterRelationIdsCollection($collection)
	{
		$idsArr = array();
		foreach($collection as $item)
		{
			$level =  $item->getLevel();
			$productId = $item->getProductId();
			
			if(isset($idsArr[$productId]) && $idsArr[$productId]["l"] >= $level)
			{
				continue;
			}
						
			$idsArr[$productId]["l"] = $level;
			$idsArr[$productId]["r"] = $item->getId();
		}
		
		$result = array();
		foreach($idsArr as $id)
		{
			$result[] = $id["r"];
		}
		return $result;
	}
	
	private function prepareCategoriesArray($collection)
	{
		$tmpAr = array();
		foreach($collection as $record)
		{
			$tmpAr[$record->getCategoryId()][] = $record->getProductId();
		}
		
		$result = array();
		foreach($tmpAr as $categoryId => $products)
		{
			$result[] = $categoryId;
		}
		return $result;
	}
	
	private function prepareCategoryProductResult($collection)
	{
		$tmpAr = array();
		foreach($collection as $record)
		{
			$tmpAr[$record->getCategoryId()][] = $record->getProductId();
		}
		
		$result = "";
		$first = true;
		foreach($tmpAr as $categoryId => $products)
		{
			if($first)
				$first = false;
			else
				$result .= "|";
			$result.= $categoryId.",".implode(",",$products);
		}
		return $result;
	}
	
	private function loadCategoryProducts($profileId)
	{
		$collection = $this->getCollection()->addFieldToFilter('export_id',$profileId);
		$tableName = $this->getResource()->getTable('catalog/category');
		$collection->getSelect()->join(array('cc'=>$tableName), 'main_table.category_id = cc.entity_id', array('level'));

		//$q = $collection->getSelect()->__toString();				
		$collection->load();
		
		return $collection;
	}
	
	private function loadCategoryProductsSimple($profileId)
	{
		$collection = $this->getCollection()->addFieldToFilter('export_id',$profileId);
		//$q = $collection->getSelect()->__toString();				
		$collection->load();
		
		return $collection;
	}
	
	private function runQuery($profileId,$categoryproducts,$deleteRecords)
    {
	    try 
	    {
	    	if($deleteRecords)
            	$this->getResource()->deleteRecords($profileId);
			$this->getResource()->insertRecords($profileId,$categoryproducts);
				
		} 
		catch (Exception $e) 
		{
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage()
                //Mage::helper('catalogrule')->__('Unable to apply rules.')
            );
            throw $e;
	    }
    }  
	
	private function parseCategoryProducts($categoryproducts)
	{
		$result = Array();
		$groups = explode("|",$categoryproducts);
		foreach($groups as $group)
		{
			$members = explode(",",$group);
			if(count($members) == 0 || $members[0] == "" || !is_numeric($members[0]))
				continue;
			$categoryId = $members[0];	
			unset($members[0]);
			$productIds = Array();
			
			$deleted = false;
			foreach($members as $productId)
			{
				if($productId == 'deleted')
				{
					$deleted = true;
					break;
				}
				if(is_numeric($productId))
				{
					$productIds[] = $productId;
				}
			}
			if($deleted)
				continue;
			
			if(count($productIds) == 0)
			{
				$productIds = $this->loadCategoryproductIds($categoryId);
			}
			
			$result[$categoryId] = $productIds;
		}
		return $result;
	}
	
	private function loadCategoryproductIds($categoryId)
	{
		$category = Mage::getModel('catalog/category')->load($categoryId);
		$collection = Mage::helper('aukro/data_product')->prepareCategoryProductCollection($this->_store,$category);
		$collection->load();
		return $collection->getAllIds();
	}
}