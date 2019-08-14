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
 * Control unit for product export process
 *
 * @category Nostress
 * @package Nostress_Aukro
 *
 */

class Nostress_Aukro_Model_Unit_Control extends Nostress_Aukro_Model_Unit
{
	const DEF_GROUP_BY_CATEGORY = 1;
	const DEF_RELOAD_CACHE = 1;
	
    public function upload($productIds,$storeId, $dryRun = false, $update = false)
    {
        $this->init();
        $this->logStatus(self::STATUS_RUNNING);
        
        $loader = Mage::getSingleton('aukro/data_loader_product_aukro');
        $transformator = Mage::getSingleton('aukro/data_transformation_xml_array');
        $productUploader = Mage::getSingleton("aukro/webapi_product");
        
        $count = 0;
        try
        {
            $loader->init($this->getLoaderParams($productIds,$storeId),true);
            $transformator->init($this->getTransformParams($storeId));
            while(($productsNumber = count($batch = $loader->loadBatch())) > 0)
            {
                $this->incrementProductCounter($productsNumber);
                $transformator->transform($batch);
            }
            
            if($this->getProductCounter() == 0)
            	$this->logAndException("Zero products selected for export. Please choose products to export in profile's detail.");

            $this->incrementProductCounter(-$transformator->getSkippedProductsCounter());
            $items = $transformator->getResult(true);
            if( $update) {
                $count = $productUploader->update($items);
            } else {
                $count = $productUploader->upload($items, $dryRun);
            }
        }
        catch(Exception $e)
        {
        	$this->logStatus(self::STATUS_ERROR);
            throw $e;
        }

        $this->logStatus(self::STATUS_FINISHED);
        return $count;
    }
    
    protected function getLoaderParams($productIds,$storeId)
    {
 		$params = array();
        $params["store_id"] = $storeId;
        $params["group_by_category"] = self::DEF_GROUP_BY_CATEGORY;
        $params["reload_cache"] = self::DEF_RELOAD_CACHE;
        $params["conditions"] = array(
                Nostress_Aukro_Helper_Data_Loader::CONDITION_PRODUCT_IDS => $productIds,
                Nostress_Aukro_Helper_Data_Loader::CONDITION_EXPORT_OUT_OF_STOCK => 1
        );
        $params["attributes"] = $this->getAttributeModel()->getAttributeCodes(true);
        return $params;
    }
    
    protected function getTransformParams($store_id)
    {
    	$params = array();
    	$params["attributes"] = $this->getAttributeModel()->getCollectionData();
    	$params['attributes'][] = array( 'code'=>'id', 'magento'=>'id');
    	
        $params["file_type"] = self::XML;
        $params["store_id"] = $store_id;
        $params["parents_childs"] = "0";
        $params["encoding"] = 'utf-8';
        return $params;
    }
    
    
    protected function logStatus($status)
    {
    	$this->helper()->log($this->helper()->__("Aukro upload status: ").$status);
    }
    
    protected function getAttributeModel()
    {
    	return Mage::getModel('aukro/mapping_attribute');
    }
}