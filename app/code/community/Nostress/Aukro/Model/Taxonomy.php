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
 * Model for search engines taxonomy
 * 
 * @category Nostress 
 * @package Nostress_Aukro
 * 
 */

class Nostress_Aukro_Model_Taxonomy extends Mage_Core_Model_Abstract 
{
	const ALL_LOCALES = 'all';
	const ROOT = 'taxonomy';

	const SRC = 'src';
	const PATH = 'path';
	const FILENAME = 'filename';
	const DOWNLOAD = 'download';
	
	//config tags
	const LOCALE = 'locale';
	const DELIMITER = 'delimiter';
	const VARIABLE = 'variable';
	const DEFAULT_LOCALE = 'default';
	const TRANSLATE = 'rewrite';	
	const GENERAL = 'general';
	const OPTION =  'option';
	const LABEL = 'label';
	const VALUE = 'value';
	const COMMON = 'common';
	
	//columns
	const C_CODE = 'taxonomy_code'; 	
	const C_LOCALE = 'locale';
	const C_NAME = 'name';
	const C_ID = 'id';
	const C_PATH = 'path'; 	
	const C_IDS_PATH = 'ids_path';
	const C_LEVEL = 'level';
	const C_PARENT_NAME = 'parent_name';
	const C_PARENT_ID = 'parent_id';
	
	const DEFAULT_LOCALE_DELIMITER = "_";
		
	protected $_enginesConfig;
	protected $_message = array(true=>array(),false=>array());
	
	public function _construct() 
	{
		parent::_construct ();
		$this->_init ( 'aukro/enginecategory' );
	}
	
	public function getTaxonomy($code,$locale,$select = null)
	{
	    $filter = "";	    
	    if($this->countColumns($code,$locale) > 0)
	    {
		    $filter = $this->getFilterFields($code,$locale);
	    }
	    else
	    {
	        $filter = $this->getFilterFields($code);
	    }
	    
		$items = $this->_getTaxonomy($filter,$select);				
		return $items;
	}
	
	public function countColumns($code,$locale = self::ALL_LOCALES)
	{
	    return $this->getResource()->countColumns($code,$locale);
	}
	
	public function _getTaxonomy($filter = null,$select = null)
	{
		$collection = $this->getResourceCollection();
		$collection->addFieldsToFilter($filter);		
		$collection->addFieldsToSelect($select);
		$collection->getSelect();//init select don't delete
		$collection->load();
		return $collection->getItems();					
	}
	
	
	public function reloadTaxonomy()
	{
		//clear all
		$this->clearTaxonomy();	

		//get locales of all stores
		$locales = $this->helper()->getAllStoresLocale();
		
		//load all records
		$this->_reloadTaxonomy($locales);
		
		return $this->_message;
	}
	
	public function getTaxonomyConfig($code,$locale = null)
	{		
		//$engieConfig = $this->getEngineConfig($engineCode);
		$config = Mage::getModel('aukro/taxonomy_setup')->getSetupByCode($code);
		if(isset($config))
		{
			return $this->prepareTaxonomyConfig($config);				
		}
		else 
			return false;
	}
	
	public function getSelectFields($code)
	{
		$taxonomyConfig = $this->getTaxonomyConfig($code);
		if($taxonomyConfig == false)
		    return false;
		
		$locale = $this->getLocale();
		$localeConfig = $this->getArrayItem($taxonomyConfig,$locale,self::ALL_LOCALES);
		$general = $this->getArrayItem($localeConfig,self::GENERAL);
		$options = $this->getArrayItem($general,self::OPTION);
		
		$fields = array();
		$fields[self::LABEL] = $this->getArrayItem($options,self::LABEL);
		$fields[self::VALUE] = $this->getArrayItem($options,self::VALUE);
		return $fields;
	}

	protected function getFilterFields($code,$locale = self::ALL_LOCALES)
	{
		$fields = array();
    	$fields[self::C_CODE] = $code;
   		$fields[self::C_LOCALE] = $locale;
    	return $fields;
	}
	
	protected function prepareTaxonomyConfig($config)
	{		
		$common = $this->getArrayItem($config,self::COMMON);
		$locales = $this->getArrayItem($config,self::LOCALE);
		foreach($locales as $key => $locale)
		{
			$locales[$key] = $this->helper()->updateArray($locale,$common);
		}
		return $locales;
		
	}
	
	protected function clearTaxonomy()
	{
		$this->getResource()->cleanTable();
	}
	
	protected function _reloadTaxonomy($locales)
	{
		$taxonomySetupCollection = Mage::getModel('aukro/taxonomy_setup')->getCollection()->load();
		
		foreach($taxonomySetupCollection as $taxonomyItem)
		{
			$config = $taxonomyItem->getDecodedSetup();
			$config = $this->prepareTaxonomyConfig($config);
			$name =  $taxonomyItem->getName();
			$code =  $taxonomyItem->getCode();
			//prepare locales config
			$localesSourceConfig = $this->prepareLocalesSourceConfig($name,$config,$locales);
			//add engines taxonomy to DB
			foreach($localesSourceConfig as $locale => $sourceConfig)
				$this->insertEngineCategories($name,$code,$locale,$sourceConfig);
		}
	}
	
    protected function prepareLocalesSourceConfig($taxonomyName,$taxonomyConfig,$locales)
    {
    	$localeConfigArray = array();
    	foreach($taxonomyConfig as $localeCode => $config)
	    {
    		try 
    		{
	    		if($localeCode == self::ALL_LOCALES)
	    		{	    			
	    			if($this->hasArrayItem($config,self::DOWNLOAD))
	    			{
	    				$localeConfigArray = $this->processLocales($locales,$config);
						break;
	    			}   
	    			else 
	    			{
	    				$localeConfigArray[$localeCode] = $this->getArrayItem($config,self::SRC);
	    			} 			
	    		}
	    		else if(in_array($localeCode,$locales))
	    		{	    	
	    			$localeConfigArray[$localeCode] = $this->getArrayItem($config,self::SRC);
	    			
	    		}
	    		
	    	}
		    catch(Exception $e)
	    	{
	    		Mage::log("Taxonomy: {$taxonomyName} Locale: {$localeCode} -- {$e} ",Zend_Log::DEBUG,Nostress_Aukro_Helper_Data::LOG_FILE);
	    	}
    	}
    	
    	return $localeConfigArray;
    	
    }
    
    protected function processLocales($locales,$config)
    {
    	$localeConfigArray = array();    	    	
    	$sourceConfig = $this->getArrayItem($config,self::SRC,array());
    	$sourceFile = $this->getArrayItem($sourceConfig,self::FILENAME);
    	
    	$localeConfig = $this->getArrayItem($config,self::DOWNLOAD);
    	$delimiter = $this->getArrayItem($localeConfig,self::DELIMITER);
    	$variable = $this->getArrayItem($localeConfig,self::VARIABLE);
    	$defLoc = $this->getArrayItem($localeConfig,self::DEFAULT_LOCALE,self::ALL_LOCALES);
    	$translate = $this->getArrayItem($localeConfig,self::TRANSLATE,null,true);
    	
    	if(!in_array($defLoc,$locales))
    		$locales[] = $defLoc;

    	foreach($locales as $locale)
    	{    		
    		$sourceConfig[self::FILENAME] = $this->prepareSrcFilename($sourceFile,$locale,$variable,$delimiter,$translate);
    		$localeConfigArray[$locale] = $sourceConfig;
    	}
    	return $localeConfigArray;
    }
    
    protected function prepareSrcFilename($src,$locale,$variable,$delimiter,$translate)
    {
    	if(isset($translate[$locale]))
    		$locale = $translate[$locale];    	
    		
    	$locale = str_replace(self::DEFAULT_LOCALE_DELIMITER,$delimiter,$locale);
    	$src = str_replace($variable,$locale,$src);
    	return $src;
    }
    
    protected function hasArrayItem($array,$index)
    {
    	if(isset($array[$index]))
    		return true;
    	else
    		return false;
    }
    
	protected function getArrayItem($array,$index,$default = null,$allowNull = false)
    {
    	if(isset($array[$index]))
    		return $array[$index];
    	else if($allowNull)
    		return null;
    	else if(!isset($array[$default]))
    	{    
    		throw new Exception("Missing taxonomy config node '{$index}' ");		
    		return null;
    	}
    	else
    		return $array[$default];
    }
    
    protected function insertEngineCategories($name,$code,$locale,$fileSourceConfig)
    {
    	$message = "";
    	try
    	{
			//load engine category records
			$records = $this->loadEngineCategoriesFromFile($fileSourceConfig);
			$this->getResource()->insertEngineCategoryRecords($code,$locale,$records);
			$message = "Taxonomy \"{$name}\" locale: \"{$locale}\" has been updated";		
			$this->_message[true][] = $message;
    	}
    	catch(Exception $e)
    	{
    		$message = "Taxonomy \"{$name}\" locale: \"{$locale}\" hasn't been updated  Error:".$e->getMessage()." ";
    		$this->_message[false][] = $message;
    	}		
    	$this->log($message);
    }       
    
    protected function insertEngineCategoryRecords($engineCode,$locale,$records)
    {    	
    	$collection = $this->getCollection()->initParams($engineCode,$locale);
    	
    	foreach($records as $row)
    	{
    		$collection->addNewItem($row);
    	}    	
    	
    	$collection->save();
    } 
    
    protected function loadEngineCategoriesFromFile($params)
    {
    	$records = Mage::getModel('aukro/data_reader')->getFileContent($params);    	    		    	
    	$records = Mage::getModel('aukro/taxonomy_preprocessor')->processRecords($records,$params);
    	return $records;    	
    }
    
    protected function helper()
    {
    	return Mage::helper('aukro');
    }
    
    protected function log($message)
    {
    	Mage::log($message,Zend_Log::DEBUG,Nostress_Aukro_Helper_Data::LOG_FILE);
    }       
}