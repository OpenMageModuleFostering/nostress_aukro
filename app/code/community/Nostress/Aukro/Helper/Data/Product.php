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

class Nostress_Aukro_Helper_Data_Product extends Nostress_Aukro_Helper_Data
{
	const CODE = 'code';
	const LABEL = 'label';
	
	public function getSubProducts($product)
    {   
    	if(!isset($product))
    		return null;     
    	
    	$products = false;
    	$copyUrl = false;
    	switch($product->getTypeId())
    	{
    		case Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE:
    			$products = $this->getConfigurableSubProducts($product);
    			$copyUrl = true;
    			break;
    		case Mage_Catalog_Model_Product_Type::TYPE_GROUPED:
    		    $products = $this->getGroupedSubProducts($product);
    			break;
    		case Mage_Catalog_Model_Product_Type::TYPE_BUNDLE:
    		    $products = $this->getBundleSubProducts($product);
    			break;
    		default:    			
    			break;
    	}
    	$products = $this->addParentAttributes($product,$products,$copyUrl);
    	return $products;
    	
    }
    
    public function getConfigurableAttributes($product,$format = false)
    {
        $attributes = $product->getTypeInstance(true)->getConfigurableAttributes($product);
        $attributes =  Mage::helper('core')->decorateArray($attributes);
        if(!$format)
        	return $attributes;
        else 
        {
        	$result = array();
            foreach($attributes as $attribute)
   		    {
   		    	$productAttribute = $attribute->getProductAttribute();
   		    	if(!isset($productAttribute))
   		    	{
   		    	    $this->log("Missing product attribute, product id: {$product->getId()}");   		    	    
   		    	    continue;
   		    	}
   		    	$code = $productAttribute->getAttributeCode();
   		    	$label = $attribute->getLabel();
   		    	$result[] = array(self::CODE => $code,self::LABEL=>$label);
   		    }
   		    return $result;
        }
    }
    
    
    protected function getConfigurableSubProducts($product)
    { 
        //$subProduct->isSealable()
       return  $product->getTypeInstance(true)->getUsedProducts(null, $product);
    }
    
    protected function getBundleSubProducts($product)
    { 
       return $this->getProductsToPurchase($product);
    }
    
    protected function getGroupedSubProducts($product)
    { 
       return  $product->getTypeInstance(true)->getAssociatedProducts($product);
    }
    
    protected function addParentAttributes($parent,$childs,$copyUrl=false)
    {
        if(!isset($childs) || !is_array($childs))
            return $childs;
            
        $parentId = $parent->getId();
		$parentSku = $parent->getSku();
		$parentName = $parent->getName();
		
		$parentUrl = null;
		if($copyUrl)
			$parentUrl = $parent->getUrl();
		
		$i = 0;
	    foreach ($childs as $p) 
        {
           	$p->setParentId($parentId);
		   	$p->setParentSku($parentSku);
		   	$p->setParentName($parentName);
		   	if(isset($parentUrl))
		   	{	
		   		$i++;
		   		$p->setUrl($parentUrl."#{$i}");		   		
		   	}
        }
        return $childs;
    }
    
    /**
     * Retrieve products divided into groups required to purchase
     * At least one product in each group has to be purchased
     *
     * @param  Mage_Catalog_Model_Product $product
     * @return array
     */
    protected function getProductsToPurchase($product = null)
    {
        $bundle = $product->getTypeInstance(true);
        $product = $bundle->getProduct($product);
        $allProducts = array();
        
        foreach ($bundle->getOptions($product) as $option) 
        {
            $groupProducts = array();
            foreach ($bundle->getSelectionsCollection(array($option->getId()), $product) as $childProduct) 
            {                
                $allProducts[] = $childProduct;
            }
        }
        return $allProducts;
    }
	
	//*********************** CATEGORY PRODUCTS - START**********************************	
	
	public function prepareCategoryProductCollection($store, $category) {
		$collection = Mage::getModel('catalog/product')->getCollection()
			->addAttributeToSelect('sku')
			->addAttributeToSelect('name')
			->addAttributeToSelect('attribute_set_id')
			->addAttributeToSelect('type_id')
			->addAttributeToSelect('category_id')
			->addStoreFilter($store)
			->joinField('position',
				'catalog/category_product',
				'position',
				'product_id=entity_id',
				'category_id='.$category->getId(),
				'left')
			->joinField('qty',
				'cataloginventory/stock_item',
				'qty',
				'product_id=entity_id',
				'{{table}}.stock_id=1',
				'left');
		$collection->addCategoryFilter($category, true);
		$collection->setStoreId($store->getId());
		return $collection; 
	}
	//*********************** CATEGORY PRODUCTS - END**********************************
}