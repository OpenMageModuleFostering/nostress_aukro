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
 * @copyright Copyright (c) 2012 NoStress Commerce (http://www.nostresscommerce.cz)
 *
 */

/**
 *
 * @category Nostress
 * @package Nostress_Aukro
 */

class Nostress_Aukro_Model_Mapping_Category extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
		parent::_construct ();
        $this->_init('aukro/mapping_category');
    }
    
    
    public function saveCategoryMapping($data,$categoryId) {
    	$collection = $this->getCollection()->addFieldToFilter('category',$categoryId);
    	
    	$dbData = array (
    		'category'	=> $categoryId,
    		'aukrocategory'	=> $data['aukro_category'],
    		'connection_name'	=> $data['aukro_connection_name'],
    		'display_duration'	=> $data['aukro_display_duration'],
    		'auto_display'		=> $data['aukro_auto_display'],
    		'shipping_payment'	=> serialize($data['aukro_shipping']),
    		'attributes'		=> serialize($data['aukro_attributes']),
    	);
    	$test = $collection->getSize();
    	if ($collection->getSize() == 0) {
    		$this->setData($dbData);
    		$this->save();
    	} else {
    		$item = $collection->getFirstItem();
    		$dbData['mapping_id'] = $item->getMappingId();
    		$item->setData($dbData);
    		$item->save();
    	}
    	
    }
    
	public function checkCategoryMappingValidity() {
		
		$mappingCollection = $this->getCollection();
		
		$categoryDataResponse = Mage::getModel('aukro/webapi_abstract')->getCategoryData();
		$aukroCategories = Mage::helper('aukro')->formatAukroCategories($categoryDataResponse);
		
		$categoryModel = Mage::getModel('catalog/category');
		$tree = $categoryModel->getTreeModel();
		$tree->load();
		$magentoIds = $tree->getCollection()->getAllIds();
		$errorCounter = 0;
		foreach ($mappingCollection as $item) {
			$status = 0;
			if (!in_array($item->getCategory(), $magentoIds))
				$status += 1;
			if (!array_key_exists($item->getAukrocategory(), $aukroCategories))
				$status += 2;
			if ($status != 0) {
				$errorCounter++;
			}
			$item->setStatus($status);
			$item->save();
		}
		
		return $errorCounter;
	}
		
}