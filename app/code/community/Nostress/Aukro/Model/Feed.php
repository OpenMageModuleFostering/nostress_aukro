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
* 
*/

class Nostress_Aukro_Model_Feed extends Nostress_Aukro_Model_Abstract
{   
	const COL_LINK = 'link';
	const COL_CODE = 'code';
	const COL_FILE_TYPE = 'file_type';
	const COL_TYPE = 'type';
	const COL_COUNTRY = 'country';
	const COL_ENABLED = 'enabled';
	const COL_TAXONOMY_CODE = 'taxonomy_code';
	
	const DEF_ENABLED = '1';
	const ENABLED_YES = '1';
	const ENABLED_NO = '0';
	const DEFAULT_ROOT = "ITEM ROOT";
	const XPATH_DELIMITER = '/';
	
	protected $_defaultAttribute = array(
		"code" => "",
		"label" => "",
		"magento" => "",
		"type" => "normal",
		"limit" => "",
		"postproc" => "",
	    "path" => "",
		"description" => array(
			"text" => "",
			"example" => "",
			"options" => "",
			"format" => "text"
		)
	);
	
	public function _construct() {
		parent::_construct ();
		$this->_init ('aukro/feed');
	}
	
	public function getFeedByCode($code = null) {
		if (isset($code))
			$filter = array(self::COL_CODE => $code);
		else
			$filter = array();
		$collection = $this->getFeedCollection($filter);
		foreach ($collection as $item)
			return $item;
		return null;
	}
	
	public function toOptionArray($enabled = null, $addFileType = null, $isMultiselect = true) {
		$options = Mage::getResourceModel('aukro/feed_collection')->loadData()->toOptionArray($enabled, $addFileType);
		$options = $this->helper()->array_unique_tree($options);
		
		if (!$isMultiselect) {
			array_unshift($options, array('value'=>'', 'label'=> Mage::helper('adminhtml')->__('--Please Select--')));
		}
		
		return $options;
	}
	
	public function updateFeedsEnabled($links) {		
		$collection = $this->getFeedCollection();
		foreach ($collection as $item) {
			$link = $item->getLink();
			if (in_array($link,$links))
				$item->setEnabled(self::ENABLED_YES);
			else
				$item->setEnabled(self::ENABLED_NO);							
		}
		$collection->save();
	}
	
	public function getFeedCollection($filter = null, $select = null) {
		$collection = $this->getCollection();
		if (isset($filter) && !empty($filter)) {
			$collection->addFieldsToFilter($filter);
		}
		
		if (isset($select) && !empty($select)) {
			$collection->addFieldsToSelect($select);
		}
		$collection->getSelect();
		return $collection->load();
	}
	
	public function feedsLoaded() {
		$collection = $this->getCollection()->load()->getItems();
		if (count($collection) > 0)
			return true;
		else
			return false;
	}
	
	public function updateFeeds($data) {
		$data = $this->prepareData($data);
		$this->updateData($data);    	    	
	}
	
	protected function updateData($data) {
		$collection = $this->getCollection()->load();
		foreach ($collection as $item) {
			$code = $item->getCode();
			if (isset($data[$code])) {
				$this->copyData($data[$code],$item);
				unset($data[$code]);
			}
			else {
				$item->delete();
			}
		}
		$this->insertData($data,$collection);
		$collection->save();
	}
	
	protected function insertData($data, $collection) {
		foreach ($data as $itemData) {
			$itemData[self::COL_ENABLED] = self::DEF_ENABLED;
			$colItem = $collection->getNewEmptyItem();
			$colItem->setData($itemData);
			$collection->addItem($colItem);
		}
	}
	
	protected function copyData($data,$dstItem) {
		foreach($data as $key => $src) {
			$dstItem->setData($key,$src);
		}
	}
	
	protected function prepareData($data) {
		$modifData = array();
		foreach ($data as $key => $item) {
			if (!isset($item[self::COL_CODE])) {
				throw new Exception($this->__("Missing feed setup attribute '".self::COL_CODE."'"));
			}
			$modifData[$item[self::COL_CODE]] = $item;
		}
		return $modifData;
	}
	
	public function getLayout() {
		$layout = parent::getLayout();
		$layout = $this->helper()->dS($layout);
		return $layout;
	}
	
	public function getTrnasformationXslt() {
		$layout = $this->getLayout();
		$xlst = $this->helper()->getTrnasformationXslt($layout);
		return $xlst;
	}
	
	public function getAttributesSetup($asArray = true) {
		$layout = $this->getLayout();
		$setup = $this->helper()->getAttributesSetup($layout,$asArray);
		if (!$setup)
			$this->logAndException('Missing layout and attributes setup in feed with code %s'.$this->getCode());
		$setup = $this->fillAttributesSetup($setup);
		return $setup;
	}
	
	protected function fillAttributesSetup($setup) {
		if (!isset($setup["attributes"]["attribute"])) {
			$this->logAndException('Missing attributes setup in feed with code %s'.$this->getCode());
		}
		$attributes = $setup["attributes"]["attribute"];
		
		foreach ($attributes as $index => $attribute) 
		{
		    $defaultAttribute = $this->_defaultAttribute;
		    if(array_key_exists(self::MAGENTO_ATTRIBUTE,$attribute) && !empty($attribute[self::MAGENTO_ATTRIBUTE]))
		        $defaultAttribute =  $this->helper()->updateArray($this->helper()->getAttributeDescription($attribute[self::MAGENTO_ATTRIBUTE]),$defaultAttribute,false);
		    
			$attribute = $this->helper()->updateArray($attribute, $defaultAttribute,false);
			
			if(isset($attribute[self::PATH]))
			{	
				if(!empty($attribute[self::PATH]))
					$attribute[self::PATH] = self::XPATH_DELIMITER.$attribute[self::PATH];
				$attribute[self::PATH] = self::DEFAULT_ROOT.$attribute[self::PATH];
			}
			
			if (empty($attribute[self::LABEL])) {
				$attribute[self::LABEL] = $attribute[self::CODE];
			}
			else if (empty($attribute[self::CODE])) 
			{
				$attribute[self::CODE] = $this->helper()->createCode($attribute[self::LABEL]);
			}
			
			$attributes[$index] = $attribute;
		}
		$setup["attributes"]["attribute"] = $attributes;
		
		if(isset($setup[self::COMMON][self::CUSTOM_PARAMS][self::PARAM][self::CODE]))
			$setup[self::COMMON][self::CUSTOM_PARAMS][self::PARAM] = array($setup[self::COMMON][self::CUSTOM_PARAMS][self::PARAM]);
			
		return $setup;
	}
	
	public function isFileText()
	{
		$type = $this->getFileType();
		$result = "";
		switch($type)
		{
			case self::TYPE_XML:
			case self::TYPE_HTML:
				$result = false;
				break;
			default:
				$result = true;
				break;
		}
		return $result;
	}
	
	
	protected function helper() 
	{
	    if(!isset($this->_helper))
		    $this->_helper = Mage::helper('aukro/data_feed_description');
		return $this->_helper;
	}
}
