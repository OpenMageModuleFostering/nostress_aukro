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
 
class Nostress_Aukro_Model_Observer extends Mage_Core_Model_Abstract {
	
	public function saveCategoryAukroMapping($eventArgs) {
        $category = $eventArgs->getData('category');
        $mapping = $category->getData('aukro');
        
        Mage::getModel('aukro/mapping_category')->saveCategoryMapping($mapping,$category->getId());
	}
	
	
	public function checkCategoryMappingValidity() {
		
		$mappingCollection = Mage::getModel('aukro/mapping_category')->getCollection();
		
		$categoryDataResponse = Mage::getModel('aukro/webapi_abstract')->getCategoryData();
		$aukroCategories = Mage::helper('aukro')->formatAukroCategories($categoryDataResponse);
		$aukroIds = array_keys($aukroCategories);
		
		$categoryModel = Mage::getModel('catalog/category');
		$tree = $categoryModel->getTreeModel();
		$tree->load();
		$magentoIds = $tree->getCollection()->getAllIds();
		$errorCounter = 0;
		foreach ($mappingCollection as $item) {
			$status = 0;
			if (!in_array($item->getCategory(), $magentoIds))
				$status += 1;
			if (!in_array($item->getAukroCategory(), $aukroIds))
				$status += 2;
			if ($status != 0) {
				$errorCounter++;
			}
			$item->setStatus($status);
			$item->save();
		}
		Mage::getSingleton('adminhtml/session')->addSuccess(
			Mage::helper('aukro')->__('Aukro categories mapping has been checked. No. of errors found: ').$errorCounter
        );
	}
	
	public function refreshOrders() {
	    
	    $orderApi = Mage::getModel( 'aukro/webapi_order');
	    list( $countNew, $countPaid) = $orderApi->refreshOrders();
        Mage::log( Mage::helper('aukro')->__('Total of %d order(s) were successfully created from aukro by cron.', $countNew), null, 'aukro.log');
        Mage::log( Mage::helper('aukro')->__('Total of %d order(s) were successfully paid from aukro by cron.', $countPaid), null, 'aukro.log');
	    
	    return true;
	}
	
	public function refreshAuctions() {

	    list( $countDeleted, $countReexposed) = Mage::getModel( 'aukro/webapi_product')->refresh();
	    Mage::log( Mage::helper('aukro')->__('Total of %d auctions(s) were closed on aukro by cron action.', $countDeleted), null, 'aukro.log');
	    Mage::log( Mage::helper('aukro')->__('Total of %d auctions(s) were re-exposed on aukro by cron action.', $countReexposed), null, 'aukro.log');
	    
	    return true;
	}
	
}