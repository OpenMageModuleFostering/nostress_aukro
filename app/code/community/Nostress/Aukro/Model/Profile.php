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

class Nostress_Aukro_Model_Profile extends Nostress_Aukro_Model_Abstract {
    
	const START_TIME = 'start_time';
	const FILTER_FROM = 'from';
	const FILTER_TO = 'to';
	const FILTER_GTEQ = 'gteq'; //greaterOrEqualValue
	const FILTER_LTEQ = 'lteq';  //lessOrEqualValue	 

	const DEF_GROUP_BY_CATEGORY = "0";
	const DEF_RELOAD_CACHE = 1;	
	
	const START_TIME_HOUR_INDEX = 0;
	const START_TIME_MINUTE_INDEX = 1;
	const START_TIME_SECOND_INDEX = 2;
	const DEFAULT_START_TIME = "00";
	
	private $product; //current processed product
	private $store; //current chosen store
	private $category; //category for which i product exported
	private $_taxHelper; //tax helper
	private $encoding; //chosen encoding
	private $editedCategoryIds; //edited ids of categories	
	private $configManageStock = ''; //edited ids of categories
	
	private $_attributeSetMap; 
	private $_attributeSet = null;
	private $_decimalDelimiter = ".";
	private $_feedObject;
    private $_reloadCache;
    private $_profileDirectAttributes = array("id","store_id","name","frequency","enabled","filename","feed");
    
	public function _construct() 
	{
		parent::_construct ();
		$this->_init ( 'aukro/profile' );
		$this->_taxHelper = new Mage_Tax_Helper_Data ( );
		$delimiter = $this->getConfig(Nostress_Aukro_Helper_Data::PARAM_DELIMITER);
		if(isset($delimiter))
			$this->setDecimalDelimiter($delimiter);
	}
	
	public function setStatus($status)
	{
	    $id = $this->getId();
		if(!$id)
			return;
		$this->log("Export profile {$id} {$status}");	
		parent::setStatus($status);
		$this->setUpdateTime($this->helper()->getDateTime(null,true));
        $this->save();
	}	
	
	public function getProfilesByName($name)
	{
		$collection = $this->getCollection();
		$collection->addFieldToFilter('name',$name);
		$collection->getSelect();
		$collection->load();
		return $collection->getItems();		
	}
	
	public function getAllProfiles()
	{
		$collection = $this->getCollection();				
		$collection->load();
		return $collection->getItems();		
	}
	
    public function getCollectionByStoreId($storeId)
	{
		$collection = $this->getCollection()->addFieldToFilter('store_id',$storeId);
		$collection->load();
		return $collection;
	}
	
	public function getCollectionByTime($from,$to)
	{
		if(!isset($from) || !isset($to))
			return null;
		$collection = $this->getCollection();
		
		if($from[0] <= $to[0])
			$collection->addFieldToFilter(self::START_TIME,array(self::FILTER_FROM => $from, self::FILTER_TO => $to));
		else 
		{
			$collection->addFieldToFilter(self::START_TIME,array(array(self::FILTER_GTEQ => $from),array(self::FILTER_LTEQ => $to)));
		}
		$s = $collection->getSelect()->__toString();		
		$collection->load();
		return $collection;
	}
	
	public function processData($data, $id) {
		$config = array();
		
		//general configuration part
		$general = $this->getConfigField(self::GENERAL,$data);
		foreach ($this->_profileDirectAttributes as $attribute) {
			$this->setData($attribute,$this->getArrayField($attribute,$general,""));
			unset($general[$attribute]);
		}
	    $this->saveTime($general);
		unset($general[self::START_TIME]);
		$this->addFilenameSuffix();
		$config[self::GENERAL] = $general;
		
		//feed configuration part
		$feed = $this->getConfigField(self::FEED, $data);
		$customAttributes = array();
		$counter = 0;
		foreach ($feed[self::ATTRIBUTES][self::ATTRIBUTE] as $key => $customAttribute) {
			
			$index = $counter;
			if ($customAttribute[self::CODE] == self::CUSTOM_ATTRIBUTE) {
				if (isset($customAttribute[self::DELETE]) && $customAttribute[self::DELETE] == 1) {
					continue;
				}
				$index = $counter+Nostress_Aukro_Helper_Data::CUSTOM_ATTRIBUTE_ROW_INDEX_OFFSET;
			}
			
			if(isset($customAttribute[self::POST_PROCESS]))
			{
				$postprocFunctions = $customAttribute[self::POST_PROCESS];
				if(is_array($postprocFunctions))
					$customAttribute[self::POST_PROCESS] = implode(self::POSTPROC_DELIMITER, $postprocFunctions);
			}
			
			$customAttributes[$index] = $customAttribute;
			$counter++;
		}
		$feed[self::ATTRIBUTES][self::ATTRIBUTE] = $customAttributes;
		$config[self::FEED] = $feed;
		
	    //product configuration part
	    $product = $this->getConfigField(self::PRODUCT,$data);//self::PRODUCT,$data);
	    $types = $this->getArrayField(self::TYPES,$product,array());
	    $types = implode(",",$types);
	    $product[self::TYPES] = $types;
	    $config[self::PRODUCT] = $product;
	    
	    $this->setConfig($config);	         														
		$categoryProductIds = $data['category_product_ids'];
		
		if($this->getId() != '')
		{					
			//Get old export values 
			$originalProfile = Mage::getModel('aukro/profile')->load($this->getId());	    	
			$deleteFeedFiles = false;
			
			$oldCategoryProductIds = Mage::getModel('aukro/categoryproducts')->getExportCategoryProducts($this->getId());
			
			//If search engine was changed => start times have to be recounted or categories to export were changed
			if($originalProfile->getFeed() != $this->getFeed() || $oldCategoryProductIds != $this->getCategoryProductIds())
			{
				$deleteFeedFiles = true;					
			}					
			else 
			{
				//rename xml files
				if($this->getFilename() != $originalProfile->getFilename())  
				{
				    //rename file nad temp file
				    $this->helper()->renameFile($originalProfile->getCurrentFilename(true),$this->getCurrentFilename(true));				    
				    $this->resetUrl();
				}
			}
			if($deleteFeedFiles)
			{
			    $originalProfile->deleteFiles();
			    $this->setUrl($this->helper()->__('XML file doesnt exist'));				
			} 
			
    	}
    	else 
    	{
    		$this->setUrl($this->helper()->__('XML file doesnt exist'));					
    	}	
    	$this->save();
    	$this->helper()->updateCategoryProducts($this->getId(),$categoryProductIds,$this->getStoreId());
	}
	
	public function getFilename($full = false,$fileSuffix = null)
	{
	    $filename = parent::getFilename();
	    if(isset($fileSuffix))
	        $filename = $this->helper()->changeFileSuffix($filename,$fileSuffix);
	    if($full)
	    {
	        $feedDir = $this->helper()->getFeedDirectoryName($this->getFeed());
	        
	        $dirPath = $this->helper()->getFullFilePath("",$feedDir);
            
	        $this->helper()->createDirectory($dirPath);	        
	            
	        $filename = $this->helper()->getFullFilePath($filename,$feedDir);
	    }
	    return $filename;
	}
	
	protected function saveTime($config)
	{
	    $delimiter = Nostress_Aukro_Helper_Data::TIME_DELIMITER;
		$startTime = $this->getArrayField(self::START_TIME,$config,array(self::DEFAULT_START_TIME,self::DEFAULT_START_TIME,self::DEFAULT_START_TIME));
		$this->setStartTime($this->getArrayField(self::START_TIME_HOUR_INDEX,$startTime,self::DEFAULT_START_TIME)
		                    .$delimiter.$this->getArrayField(self::START_TIME_MINUTE_INDEX,$startTime,self::DEFAULT_START_TIME)
		                    .$delimiter.$this->getArrayField(self::START_TIME_SECOND_INDEX,$startTime,self::DEFAULT_START_TIME));
			
		if ($this->getCreatedTime == NULL || $this->getUpdateTime() == NULL) 
		{
			$this->setCreatedTime(now());						
		}
	}
	
	protected function getCurrentFilename($full=false)
	{
	    $suffix = $this->getFeedObject()->getFileType();
	    $generalConfig = $this->getCustomConfig(self::GENERAL,false);
	    $compressFile = $this->getArrayField("compress_file",$generalConfig,"0");	     
	    if($compressFile)
	        $suffix = Nostress_Aukro_Helper_Data::FILE_TYPE_ZIP;
	    
	    return $this->getFilename($full,$suffix);
	}
	
	public function delete()
	{
	    $this->deleteFiles();
	    parent::delete();
	}
	
	public function setMessageAndStatus($message,$status)
	{		
    	$this->setMessage($message);
    	$this->setStatus($status);
    	$this->save();
	}
	
	//delete feed and temp feed files
	protected function deleteFiles()
	{
		$this->helper()->deleteFile($this->getCurrentFilename());
	    $this->setUrl($this->helper()->__('XML file doesnt exist'));
	}
	
	public function resetUrl()
	{
	    $this->setUrl($this->getFileUrl());
	}
	
    protected function getFileUrl()
	{
	    $filename = $this->getCurrentFilename();
	    $feedDir = $this->helper()->getFeedDirectoryName($this->getFeed());
	    $filename = $this->helper()->getFileUrl($filename,$feedDir);	    
	    return $filename;
	}
	
	protected function addFilenameSuffix()
	{    
	    $filename = $this->helper()->addFileSuffix($this->getFilename(),$this->getFeed());
	    $this->setFilename($filename);   
	}
	
    protected function helper()
    {
    	return Mage::helper('aukro/data');
    }
    
    public function getLoaderParams()
    {        
        $productConfig = $this->getCustomConfig(self::PRODUCT);       
        $feedObject = $this->getFeedObject();
        $attributes = $this->getMagentoAttributes();
        
        $params = array();
        $params["export_id"] = $this->getExportId();
        $params["store_id"] = $this->getStoreId();
        $params["use_product_filter"] = $this->getArrayField("use_product_filter",$productConfig,"0");
        $params["group_by_category"] = self::DEF_GROUP_BY_CATEGORY;
        $params["reload_cache"] = $this->getReloadCache();
        $params["taxonomy_code"] = $feedObject->getTaxonomyCode();
        $params["batch_size"] = $this->helper()->getGeneralConfig(Nostress_Aukro_Helper_Data::PARAM_BATCH_SIZE);
        $params["conditions"] = $productConfig;
        $params["attributes"] = $attributes;
        
        $loadAllProductCategories = "0";
        if(in_array("categories",$attributes))
            $loadAllProductCategories = "1";
        $params['load_all_product_categories'] = $loadAllProductCategories;
        return $params;
    }
    
    protected function getTransformParams()
    {       
        $productConfig = $this->getCustomConfig(self::PRODUCT);            
        
        $params = $this->getMergedProfileFeedConfig();
		if(isset($params['common']))
		{
        	foreach($params['common'] as $key => $param)
        	{
        		$params[$key] = $param;
        	}
        	unset($params['common']);
        }
        $params["file_type"] = $this->getFeedObject()->getFileType();
        $params["store_id"] = $this->getStoreId();
        $params["parents_childs"] = $this->getArrayField("parents_childs",$productConfig,"0");
        $params["xslt"] = $this->getFeedObject()->getTrnasformationXslt();
        return $params;            				
    }
    
    public function getXmlTransformParams()
    {
    	return $this->getTransformParams();
    }
    
    public function getXsltTransformParams()
    {
    	$params = $this->getTransformParams();
    	//add params
    	if(isset($params[self::ATTRIBUTES][self::ATTRIBUTE]))
    	{
    		$cdataSectionElements = array();
    		$customColumnsHeader = array();
    		$columnsHeader = array();
    		foreach ($params[self::ATTRIBUTES][self::ATTRIBUTE] as $key => $customAttribute) 
    		{
    			if ($customAttribute[self::CODE] == self::CUSTOM_ATTRIBUTE) 
    			{
    				$customColumnsHeader[] = $customAttribute[self::LABEL];
    			}
    			else if($customAttribute[self::TYPE] != self::DISABLED && $customAttribute[self::TYPE] != self::CSV_DISABLED)
    			{
    				$columnsHeader[] = $customAttribute[self::LABEL];
    			}
    			
    			if(strpos($customAttribute[self::POST_PROCESS], self::CDATA) !== false)
    			{
    				$cdataSectionElements[] =  $customAttribute[self::LABEL];
    			}
    		}
    		$params[self::CUSTOM_COLUMNS_HEADER] = $customColumnsHeader;
    		$params[self::CDATA_SECTION_ELEMENTS] = $cdataSectionElements; 
    		$params[self::BASIC_ATTRIBUTES_COLUMNS_HEADER] = $columnsHeader;
    	}
    	return $params;
    }
    
    public function getWriterParams()
    {
        $params = $this->getCustomConfig(self::GENERAL);
        $suffix = $this->getFeedObject()->getFileType();
        $params["full_filename"] = $this->getFilename(true,$suffix);
        $params["filename"] = $this->getFilename(false,$suffix);
        $params["zip_filename"] = $this->getFilename(true,Nostress_Aukro_Helper_Data::FILE_TYPE_ZIP);
        return $params;
    }
    
    protected function getMergedProfileFeedConfig($feedCode = null)
    {
        $feedConfig = $this->getFeedObject($feedCode)->getAttributesSetup();        
        
        $profileFeedConfig = $this->getCustomConfig(self::FEED,false);
        if(!empty($profileFeedConfig))
        {            
            $profileFeedConfig = $this->helper()->updateArray($profileFeedConfig,$feedConfig);
        }
        else
        {
            $profileFeedConfig = $feedConfig;
        }
        
        $attributes = $this->removeEmptyAttributes($profileFeedConfig);
        if(!empty($attributes))
        	$profileFeedConfig[self::ATTRIBUTES][self::ATTRIBUTE] = $attributes;                 
        
        return $profileFeedConfig;
    }
    
    protected function removeEmptyAttributes($feedConfig)
    {
    	$attributes = $this->getArrayField(self::ATTRIBUTES,$feedConfig);
        $attributeInfoArray = $this->getArrayField(self::ATTRIBUTE,$attributes,array());
             
        $attributes = array();
        foreach ($attributeInfoArray as $attribute)
        {
         	$code = $this->getArrayField(self::CODE,$attribute);
         	$label = $this->getArrayField(self::LABEL,$attribute,"");
         	if(empty($label) && $code != self::CUSTOM_ATTRIBUTE)
         		continue;
         	$attributes[] = $attribute; 
        }
        return $attributes;
    }
    
    public function getBackendConfig($feedCode = null)
    {
        $profileFeedConfig = $this->getMergedProfileFeedConfig($feedCode);
        
        $config = $this->getConfig();
        if(empty($config))
        {
            $config = array();
        }
        
        $config[self::FEED] = $profileFeedConfig;
        return $config;
    }
    protected function getCustomConfig($index,$exception = true)
    {
        return $this->getConfigField($index,$this->getConfig(),$exception);
    }
    
    protected function getConfigField($index,$config,$exception = true)
    {
        $field = $this->getArrayField($index,$config);
        if(!isset($field) && $exception)
            $this->logAndException("Can't load %s configuration.",$index);
        return $field;
    }
    
    public function getReloadCache()
    {
        if(!isset($this->_reloadCache))
            $this->_reloadCache = self::DEF_RELOAD_CACHE;
        return $this->_reloadCache;
    }
    
    public function setReloadCache($reloadCache)
    {
        $this->_reloadCache = $reloadCache;
    }
    
    public function getFeedObject($feedCode = null)
    {
        if(!isset($feedCode))
            $feedCode = $this->getFeed();
        if(!isset($this->_feedObject))
            $this->_feedObject = Mage::getModel('aukro/feed')->getFeedByCode($feedCode);
        return $this->_feedObject;            
    }
    
    protected function getMagentoAttributes()
    {
         //$feedConfig = $this->getCustomConfig(self::FEED);
         $feedConfig = $this->getMergedProfileFeedConfig();
         $attributes = $this->getArrayField(self::ATTRIBUTES,$feedConfig);
         $attributeInfoArray = $this->getArrayField(self::ATTRIBUTE,$attributes);
         if(!isset($attributeInfoArray))
             $this->logAndException("Missing feed attributes configuration.");
         
         $attributes = array();
         foreach ($attributeInfoArray as $attribute)
         {
//         	$code = $this->getArrayField(self::CODE,$attribute);
//         	$label = $this->getArrayField(self::LABEL,$attribute,"");
//         	if(empty($label) && $code != self::CUSTOM_ATTRIBUTE)
//         		continue;
         	
             $magentoAttribute = $this->getArrayField(self::MAGENTO_ATTRIBUTE,$attribute);
             if(isset($magentoAttribute) && !empty($magentoAttribute) && !in_array($attribute,$attributes))
                 $attributes[] = $magentoAttribute;                 
         }
         
         //attribute from stock setup
         $common = $this->getArrayField("common",$feedConfig,array());
         $stock = $this->getArrayField("stock",$common);
         if(!isset($stock))
             $this->logAndException("Missing feed stock configuration.");
         $availabilityAttribute = $this->getArrayField("availability",$stock);
         if(isset($availabilityAttribute) && !empty($availabilityAttribute))
             $attributes[] = $availabilityAttribute;
         
         return $attributes;         
    }
    
    public function setConfig($config)
    {
        parent::setConfig(json_encode($config));
    }
    
    public function getConfig()
    {
        $id = $this->getId();
         if(!isset($id))
             return null;  
        $config = json_decode(parent::getConfig(),true);
		return $config;
    }

}