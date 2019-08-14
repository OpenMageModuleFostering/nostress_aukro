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

class Nostress_Aukro_Model_Mapping_Attribute extends Nostress_Aukro_Model_Abstract
{
	protected $_extraAttributes = array("aukrocategory_id");
									
    public function _construct()
    {
		parent::_construct ();
        $this->_init('aukro/mapping_attribute');
    }
    
    public function reloadAttributes() {
    	$webapi = Mage::getModel('aukro/webapi_abstract');
		$categoryDataResponse = $webapi->getCategoryData();
		$aukroCategories = Mage::helper('aukro')->formatAukroCategories($categoryDataResponse);
		
		$categoryMappingCollection = Mage::getModel('aukro/mapping_category')->getCollection();
    	$collection = $this->getCollection()->load();
    	$collectionCodes = array();
    	
    	if(count($collection) == 0)
    		$collection = $this->addDefaultAttributes($collection);
    	
    	foreach($collection as $attribute)
    		$collectionCodes[$attribute->getLabelCode()] = $attribute;
    	
    	foreach ($categoryMappingCollection as $item) {
    		if ($item->getAukrocategory() == 0 || $item->getAukrocategory() == null)
    			continue;
    		    		
    		$fields = $webapi->getSellFormAttributesForCategory($item->getAukrocategory());
    		if (count($fields['sellFormFields']['item']) != 0) {
    			foreach ($fields['sellFormFields']['item'] as $params)
    			{
    				$code = $this->helper()->createCode($params['sellFormTitle']);
    				if(isset($collectionCodes[$code]))
    					$curAtrib = $collectionCodes[$code];
    				else
    				{
    					$curAtrib = clone $this;
    					$curAtrib->setData('code',$params['sellFormId']);
    				}
    				
    				$curAtrib->setData('label_code',$code);
    				$curAtrib->setData('label',$params['sellFormTitle']);
    				$curAtrib->setData('predefined',$params['sellFormDesc']);
    				$curAtrib->setData('type',$params['sellFormType']);
    				$curAtrib->setData('description',serialize(array('text' => $params['sellFormFieldDesc'])));
    				    				
    				if(!isset($collectionCodes[$code]))
    				{
    					$collection->addItem($curAtrib);
    					$collectionCodes[$curAtrib->getLabelCode()] = $curAtrib;
    				}
    			}
    		}
    	}
    	$collection->save();
    }
    
    protected function addDefaultAttributes($collection)
    {
    	$defAtribs = $this->getDefaultAttributes();
    	foreach($defAtribs as $attribute)
    	{
    		$curAtrib = clone $this;
    		$curAtrib->setData($attribute);
    		$collection->addItem($curAtrib);
    	}
		return $collection;
    }
    
    public function clearAttributes()
    {
    	$col = $this->getCollection()->load();
    	foreach($col as $item)
    		$item->delete();
    }
    
    public function getAttributeCodes($addExtraAttributes = false)
    {
    	$collection = $this->getCollection()->load();
    	$codes = array();
    	if($addExtraAttributes)
    		$codes = $this->_extraAttributes;
    	foreach($collection as $attribute)
    	{
    		if(!isset($attribute[self::MAGENTO_ATTRIBUTE]))
    			continue;
    		
    		$code = $attribute[self::MAGENTO_ATTRIBUTE];
    		if(!in_array($code,$codes))
    		$codes[] = $code;
    	}
    	return $codes;
    }
    
    public function getCollectionData()
    {
    	$collection = $this->getCollection()->load();
    	$dataArray = array();
    	foreach($collection as $item)
    	{
    		$data = $item->getData();
    		$data['id'] = $data['code'];
    		$data['code'] = $data['label_code'];
    		$dataArray[] = $data;
    	}
    	return $dataArray;
    }
    
    protected function getDefaultAttributes()
    {
    	$defAttribs = Mage::getSingleton("aukro/webapi_product")->getBaseAttributes();
    	return $defAttribs;
    }
}