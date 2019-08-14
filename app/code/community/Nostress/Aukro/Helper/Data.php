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

class Nostress_Aukro_Helper_Data extends Mage_Core_Helper_Abstract
{
	const STATUS_ACTIVE = 1;	//active products
	const NO_VISIBLE = 1;   //Visible NOWHERE
	const PATH_DELIMITER = '/';
	const DEF_PERMISSION = 0777;
	const CUSTOM_ATTRIBUTE_ROW_INDEX_OFFSET = 1000;
	const LANGUAGE_CODE_LENGTH = 2;
	const CATALOG_CATEGORY_FLAT_PROCESS_CODE = "catalog_category_flat";
	
	const LOG_FILE = "aukro-connector.txt";
	const EVENT_LOG_FILE = "aukro.log";
	const LC_TIMESTAMP = 'timestamp';
	const LC_NOP = 'nop';
	const LC_DELIMITER = ";";
	protected $_eventLogTemplate = array(self::LC_TIMESTAMP => "", self::LC_NOP => "");
	
	//config paths
	const PATH_GENERAL = 'aukro/general/';
	const PATH_ENGINES_CONFIG = 'default/aukro/engine';
	const PATH_STORE_LOCALE = 'general/locale/code';
	const PATH_STORE_COUNTRY = 'general/country/default';
	const PATH_HELP = 'aukro/help/';
	
	//config params
	const PARAM_ADD_PRODUCTS = 'add_products';
	const PARAM_URL_STORE = 'url_store';
	const PARAM_URL_CATEGORY = 'url_category';
	const PARAM_FILE_SUFFIX_SHOW = 'suffix_show';
	const PARAM_EXPORT_META = 'export_meta';
	const PARAM_EXPORT_OUT_OF_STOCK = 'export_out_of_stock_products';
	const PARAM_CRON_PERIOD = 'cron_run_period';
	const PARAM_ATTRIBUTE_CODES = 'common_attribute_codes';
	const PARAM_DELIMITER = 'delimiter';
	const PARAM_TAXONOMY = 'taxonomy';
	const PARAM_NAME = 'name';
	const PARAM_FILEPATH = 'filepath';
	const PARAM_TEMP_PREFIX = 'temp_file_prefix';
	const PARAM_TAXONOMY_SOURCE_URL = 'taxonomy_source_url';
	const PARAM_MEDIA_GALLERY = 'media_gallery_attribute_code';
	const PARAM_REVIEW_URL = 'review_url';
	const PARAM_IMAGE_FOLDER = 'image_folder';
	const PARAM_BATCH_SIZE = 'batch_size';
	const PARAM_DEBUG_MODE = 'debug_mode';
	const PARAM_CONNECTORS_JSON_URL = 'connectors_json_url';
	
	const PARAM_LOG_EVENTS = 'log_events';
	const PARAM_LOG_LIMIT = 'log_limit';
	const PARAM_LOG_REST = 'log_rest';
	
	const CODE = 'code';
	const TYPE = 'type';
	const VALUE = 'value';
	const LABEL = 'label';
	const XML = 'xml';
	const CSV = 'csv';
	const CDATA = 'cdata';
	const TYPE_SELECT = 'select';
	const TYPE_MULTISELECT = 'multiselect';
	
	const ENTITY_PRODUCT = 'product';
	const ENTITY_CATEGORY = 'category';
	
	const CATEGORY_ATTRIBUTE_PREFIX = 'nsc_taxonomy_';
	const NOSTRESSDOC_TAG = "nscdoc";
	
	const FILE_TYPE_XML = "xml";
	const FILE_TYPE_CSV = "csv";
	const FILE_TYPE_ZIP = "zip";
	const FILE_TYPE_TXT = "txt";
	const FILE_TYPE_HTML = "html";
	protected $_fileTypes = array(self::FILE_TYPE_CSV, self::FILE_TYPE_XML, self::FILE_TYPE_ZIP,self::FILE_TYPE_HTML,self::FILE_TYPE_TXT);
	
	//time
	const TIME_HOURS_PER_DAY = 24;
	const TIME_SECONDS_PER_HOUR = 3600;
	const TIME_SECONDS_PER_MINUTE = 60;
	const TIME_SECONDS_PER_DAY = 60;
	const TIME_DELIMITER = ":";
	protected $_attributeCodes;
	
	const HELP_LICENSE_CONDITIONS = "license_conditions";
	
	protected function _getConfig($path, $flag=false,$asArray=false,$storeId = null)
	{
	    $result = null;
	    if ($flag)
	        $result = Mage::getStoreConfigFlag($path,$storeId);
	    else
	        $result = Mage::getStoreConfig($path,$storeId);
	
	    if($asArray)
	        $result = explode(",",$result);
	    return $result;
	}
	
//***************************** COMMON *******************************************
	public function isDebugMode() {
		return $this->getGeneralConfig(self::PARAM_DEBUG_MODE) ? true:false;
	}
	
	public function getAttributeLabel($attribute,$storeId)
	{
	    $labels = $attribute->getStoreLabels();
  		$label = $attribute->getFrontendLabel();
        if(array_key_exists($storeId,$labels))
        	$label = $labels[$storeId];
        return $label;
	}
	
	public function getCdataString($input)
	{
		return "<![CDATA[{$input}]]>";
	}
	
//***************************** CURRENCY *****************************************
	public function getStoreCurrency($storeId, $symbol = false) {
		$currency = Mage::app()->getStore($storeId)->getDefaultCurrencyCode();
		if (!$symbol)
			return $currency;
		
		return Mage::app()->getLocale()->currency($currency)->getSymbol();
	}
	
//***************************** FLAT CATALOG **************************************************

    public function getFlatHelper($type)
    {
        return Mage::helper("catalog/{$type}_flat");
    }
	
    public function isEnabledFlat($storeId,$type = 'product')
    {
        return $this->getFlatHelper($type)->isEnabled($storeId);
    }
	
//*****************************XML READ FUNCTIONS***********************************************
    public function log($message)
    {
    	Mage::log($message,Zend_Log::DEBUG,self::LOG_FILE);
    }
    
    protected function getEventLogTemplate()
    {
        $template = $this->_eventLogTemplate;
        $template[self::LC_TIMESTAMP] = $this->getDateTime();
        return $template;
    }
    
    public function logProductsRefreshEvent($count)
    {
        $template = $this->getEventLogTemplate();
        $template[self::LC_NOP] = $count;
        $this->logEvent($template);
    }
    
    protected function logEvent($message)
    {
        if($this->getGeneralConfig(self::PARAM_LOG_EVENTS) == "0")
            return;
    
        try
        {
            if(is_array($message))
                $message = implode(self::LC_DELIMITER,$message);
    
            $file = $this->getFullFilePath(self::EVENT_LOG_FILE,null);
            $dir = $this->getFullFilePath("",null);
            $this->createDirectory($dir);
    
            if(file_exists($file))
            {
                $this->checkFile($file);
                $message = PHP_EOL.$message;
                file_put_contents($file,$message,FILE_APPEND);
            }
            else
                $this->createFile($file,$message);
        }
        catch(Exception $e)
        {
            $this->log("Event log failed.".$e->getMessage());
        }
    }
    
    protected function checkFile($file)
    {
        $limit = (int)$this->getGeneralConfig(self::PARAM_LOG_LIMIT);
    
        $lines = file($file);
        if(count($lines) >= $limit)
        {
            $recordsLeft = (int)$this->getGeneralConfig(self::PARAM_LOG_REST);
            $recordsToRemove = $limit-$recordsLeft;
    
            $content = file_get_contents($file);
            $content = preg_replace("/^(.*".PHP_EOL."){{$recordsToRemove}}/", "", $content);
            file_put_contents($file,$content);
        }
    }
    
    public function createFile($file,$content)
    {
        if (!file_exists($file))
        {
            file_put_contents($file, $content);
            chmod($file, self::DEF_PERMISSION);
        }
    }
    
	public function stringToXml($input)
	{
		//return new SimpleXMLElement($input,LIBXML_NOCDATA);
		return simplexml_load_string($input);
	}
	
	public function importDom($simplexml)
	{
		return dom_import_simplexml($simplexml);
	}
	
	public function XMLnodeToArray($node)
	{
		$result = array();
		if(count($node[0]->children())==0)
		{
			return (string)$node[0];
		}
		
		$isArray = false;
		foreach ($node[0]->children() as $child)
		{
			if(!isset($result[$child->getName()]))
				$result[$child->getName()] = self::XMLnodeToArray($child);
			else if(!$isArray)
			{
				$oldValue = $result[$child->getName()];
				unset($result[$child->getName()]);
				$result[$child->getName()] = array();
				
				$result[$child->getName()][] = $oldValue;
				$result[$child->getName()][] = self::XMLnodeToArray($child);
				$isArray = true;
			}
			else if($isArray)
				$result[$child->getName()][] = self::XMLnodeToArray($child);
			
		}
		return $result;
	}
	
    //*********************** CONFIG - START**********************************
    
    /**
     * Return store config value for key
     *
     * @param   string $key
     * @return  string
     */
    public function getGoogleanalyticsConfig ($key, $flag=false)
    {
        $path = self::PATH_GOOGLEANALYTICS . $key;
        if ($flag) {
            return Mage::getStoreConfigFlag($path);
        } else {
            return Mage::getStoreConfig($path);
        }
    }
    
    /**
     * Return store config value for key
     *
     * @param   string $key
     * @return  string
     */
    public function getGeneralConfig ($key, $flag=false,$asArray=false)
    {
        $path = self::PATH_GENERAL.$key;
        $result = null;
        if ($flag) {
            $result = Mage::getStoreConfigFlag($path);
        } else {
            $result = Mage::getStoreConfig($path);
        }
        if($asArray)
        	$result = explode(",",$result);
        return $result;
    }
    
    public function setGeneralConfig ($key, $value)
    {
        $path = self::PATH_GENERAL.$key;
        return $this->_setConfig($path,$value);
    }
    
    protected function _setConfig($path,$value)
    {
        Mage::getModel('core/config')->saveConfig($path,$value);
        Mage::getConfig()->reinit();
        Mage::app()->reinitStores();
    }
    
    
    protected function getConfig($configPath) {
		return Mage::getConfig ()->getNode ($configPath);
	}
    
	public function getMerchantName($engineName)
	{
		return ( string ) $this->getConfig ('default/aukro/merchant/'.$engineName.'_username' );
	}

//********************************************************************************************************************************************
//********************************************************************************************************************************************
	public function getAppProductTypes()
	{
	    $types = Mage::getModel('catalog/product_type')->getTypes();
	    return array_keys($types);
	}
	
	
	public function getEngineConfig($engine = null,$param = null,$asArray = false)
	{
		$path = self::PATH_ENGINES_CONFIG;
		if(isset($engine))
			$path .= self::PATH_DELIMITER.strtolower($engine);
		if(isset($param))
			$path .= self::PATH_DELIMITER.$param;
		return $this->getNode($path,$asArray);
	}
	
	protected function getNode($path,$asArray = false)
	{
		$node = Mage::getConfig()->getNode($path);
		if(empty($node))
			return null;
		if($asArray)
			$node = $node->asArray();
		return $node;
	}
	
	public function getEntityType($entity)
	{
		return Mage::getModel("catalog/{$entity}")->getResource()->getEntityType();
	}
	
	/**
	 * Returns product or category attributes sets
	 * @param $product
	 */
	public function getAttributeSets($entity = self::ENTITY_PRODUCT)
	{
		$entityType = self::getEntityType($entity);
			
   		return Mage::getResourceModel('eav/entity_attribute_set_collection')
        					->setEntityTypeFilter($entityType->getId())
					        ->distinct('attribute_set_id');
	}
	
	protected function getAttributesCollection($attributeSetId)
	{
		return Mage::getResourceModel('catalog/product_attribute_collection')
            				->setAttributeSetFilter($attributeSetId)
            				->load();
	}
	
	protected function getAttributeCodes()
	{
		if(!isset($this->_attributeCodes))
			$this->_attributeCodes = $this->getGeneralConfig(self::PARAM_ATTRIBUTE_CODES,false,true);
		return $this->_attributeCodes;
	}
	
	protected function getAttributeCommonCode($attributeCode,$setName)
	{
		$attributeCodes = $this->getAttributeCodes();
		foreach($attributeCodes as $code)
		{
			if($this->cmpAttributeCodes($attributeCode,$code))
				return $code;
		}
		return false;
	}
	
	protected function cmpAttributeCodes($srcCode,$dstCode)
	{
		if(strpos($srcCode,$dstCode)!== false)
			return true;
		else
			return false;
	}
	
	public function getAttributSetMap() {
		$attributeSetMap = array();
		$attributeSets = $this->getAttributeSets();
		
		foreach ($attributeSets as $id => $set) {
			$name = $set->getAttributeSetName();
			$attributeCollection = $this->getAttributesCollection($id);
			
			$attributes = array();
			foreach ($attributeCollection as $atrId => $attribute) {
				$atrCode = $attribute->getAttributeCode();
				$commonCode = $this->getAttributeCommonCode($atrCode,$name);
				if ($commonCode != false) {
					$attributes[$commonCode] = array(self::CODE => $atrCode,self::TYPE=> $attribute->getFrontendInput());
				}
			}
			$attributeSetMap[$id] = $attributes;
		}
		return $attributeSetMap;
	}
	
	public function getAttributeInfo($attributeCode) {
		$attributeInfo = Mage::getResourceModel('eav/entity_attribute_collection')
			->setCodeFilter($attributeCode)
			->getFirstItem();
		return $attributeInfo;
	}

  	public function getVisibleProductAttributes($asArray = false)
	{
	    $collection = Mage::getResourceModel('catalog/product_attribute_collection')
	      ->addVisibleFilter();
	    $collection->load();
	    if(!$asArray)
	    	return $collection;
	    else
	    {
	    	$attributes = array();
	    	foreach($collection as $atrId => $attribute)
	    	{
	    		$atrCode = $attribute->getAttributeCode();
	    		$attributes[$atrCode] = $attribute;
	    	}
	    	return $attributes;
	    }
  	}
	
	public function getAllStoresLocale()
	{
		$allStores = Mage::app()->getStores();
		$localeArray = array();
		foreach ($allStores as $_eachStoreId => $store)
		{
			$locale = $this->getStoreLocale($store);
			if(!in_array($locale,$localeArray))
				$localeArray[] = $locale;
		}
		return $localeArray;
	}
	
	public function getStoreLocale($store) {
		return $store->getConfig(self::PATH_STORE_LOCALE);
	}
	
	public function getStoreLanguage($store)
	{
		$locale = $this->getStoreLocale($store);
		if(empty($locale))
			return "";
		$lang = substr($locale,0,self::LANGUAGE_CODE_LENGTH);
		$lang = strtoupper($lang);
		return $lang;
	}
	
	public function getStoreCountry($store) {
		return $store->getConfig(self::PATH_STORE_COUNTRY);
	}
	
	public function changeEncoding($dstEnc,$input,$srcEnc=null) {
		if ($srcEnc == $dstEnc)
			return $input;
		
		if (!is_array($input))
			return $this->_changeEncoding($dstEnc,$input,$srcEnc);
		
		$result = array();
		foreach ($input as $key => $item) {
			$result[$key] = $this->_changeEncoding($dstEnc,$item,$srcEnc);
		}
		return $result;
	}
	
	/*
	 * Returns encoded string.
	 */
	protected function _changeEncoding($dstEnc,$input,$srcEnc=null)
	{
		if(!isset($input) || empty($input))
			return $input;

		$extension = "mbstring";
		
		if(!isset($srcEnc))
		{
			if(!extension_loaded($extension))
			{
				throw new Exception(Mage::helper('aukro')->__('PHP Extension "%s" must be loaded', $extension).'.');
			}
			else
				$srcEnc = mb_detect_encoding($input);
		}
		try
		{
			$input = iconv($srcEnc, $dstEnc.'//TRANSLIT', $input);
		}
		catch(Exception $e)
		{
			try
			{
				$input = iconv($srcEnc, $dstEnc.'//IGNORE', $input);
				//$input = mb_convert_encoding($input,$dstEnc,$srcEnc);
			}
			catch(Exception $e)
			{
				//echo $input;
				throw $e;
			}
		}
		if($input == false)
			throw new Exception('Conversion from encoding '.$srcEnc.' to '.$dstEnc.' failure. Following string can not be converted:<BR>'.$input);

		return $input;
	}
	
	public function createCategoryAttributeCode($name)
	{
		$name = self::CATEGORY_ATTRIBUTE_PREFIX.$name;
		return $name;
	}
	
	public function createTaxonomyCodeFromAttributeCode($attributeCode)
	{
		$taxonomyCode = str_replace(self::CATEGORY_ATTRIBUTE_PREFIX,"",$attributeCode);
		return $taxonomyCode;
	}
	
	public function getTaxonomyEngineNames()
	{
		$names = array();
		$engCollection = $this->getEngineConfig(null,null,true);
		foreach($engCollection as $key => $engine)
		{
			if(isset($engine[self::PARAM_TAXONOMY]))
			{
				$names[] = $engine[self::PARAM_NAME];
			}
		}
		return $names;
	}
	
	public function createCode($input,$delimiter = '_')
	{
		$input = $this->removeDiacritic($input);
		$input = strtolower($input);
		
		//replace characters which are not number or letters by space
		$input = preg_replace('/[^0-9a-zA-Z]/',' ', $input);
		$input = trim($input);
		//replace one or more spaces by delimiter
		$input = preg_replace('/\s+/', $delimiter, $input);
		
		return $input;
	}
	
	public function codeToLabel($input,$delimiter = '_')
	{
		$input = str_replace($delimiter," ",$input);
		$input = ucfirst($input);
		return $input;
	}
	
	protected function removeDiacritic($input)
	{
		$transTable = Array(
		  'ä'=>'a',
		  'Ä'=>'A',
		  'á'=>'a',
		  'Á'=>'A',
		  'à'=>'a',
		  'À'=>'A',
		  'ã'=>'a',
		  'Ã'=>'A',
		  'â'=>'a',
		  'Â'=>'A',
		  'č'=>'c',
		  'Č'=>'C',
		  'ć'=>'c',
		  'Ć'=>'C',
		  'ď'=>'d',
		  'Ď'=>'D',
		  'ě'=>'e',
		  'Ě'=>'E',
		  'é'=>'e',
		  'É'=>'E',
		  'ë'=>'e',
		  'Ë'=>'E',
		  'è'=>'e',
		  'È'=>'E',
		  'ê'=>'e',
		  'Ê'=>'E',
		  'í'=>'i',
		  'Í'=>'I',
		  'ï'=>'i',
		  'Ï'=>'I',
		  'ì'=>'i',
		  'Ì'=>'I',
		  'î'=>'i',
		  'Î'=>'I',
		  'ľ'=>'l',
		  'Ľ'=>'L',
		  'ĺ'=>'l',
		  'Ĺ'=>'L',
		  'ń'=>'n',
		  'Ń'=>'N',
		  'ň'=>'n',
		  'Ň'=>'N',
		  'ñ'=>'n',
		  'Ñ'=>'N',
		  'ó'=>'o',
		  'Ó'=>'O',
		  'ö'=>'o',
		  'Ö'=>'O',
		  'ô'=>'o',
		  'Ô'=>'O',
		  'ò'=>'o',
		  'Ò'=>'O',
		  'õ'=>'o',
		  'Õ'=>'O',
		  'ő'=>'o',
		  'Ő'=>'O',
		  'ř'=>'r',
		  'Ř'=>'R',
		  'ŕ'=>'r',
		  'Ŕ'=>'R',
		  'š'=>'s',
		  'Š'=>'S',
		  'ś'=>'s',
		  'Ś'=>'S',
		  'ť'=>'t',
		  'Ť'=>'T',
		  'ú'=>'u',
		  'Ú'=>'U',
		  'ů'=>'u',
		  'Ů'=>'U',
		  'ü'=>'u',
		  'Ü'=>'U',
		  'ù'=>'u',
		  'Ù'=>'U',
		  'ũ'=>'u',
		  'Ũ'=>'U',
		  'û'=>'u',
		  'Û'=>'U',
		  'ý'=>'y',
		  'Ý'=>'Y',
		  'ž'=>'z',
		  'Ž'=>'Z',
		  'ź'=>'z',
		  'Ź'=>'Z'
		);
		return strtr($input, $transTable);
	}
	
	public function updateArray($src,$dst,$force = true)
	{
		if(!isset($dst))
			$dst = array();
		
		if(!is_array($src))
			return $dst;
		foreach($src as $key => $node)
		{
			if(!is_array($node))
			{
			    if($force || $node != "" || !array_key_exists($key,$dst))
				    $dst[$key] = $node;
			}
			else
			{
				$tmpDst = null;
				if(isset($dst[$key]))
					$tmpDst = $dst[$key];
				$res = $this->updateArray($node,$tmpDst,$force);
				$dst[$key] = $res;
			}
		}
		return $dst;
	}
	
	public function getRootCategory($path,$delimiter)
	{
		$delimiter = trim($delimiter);
		$index = strpos($path,$delimiter);
		if($index === false)
			return $path;
		else
		{
			$root = trim(substr($path,0,$index));
			return $root;
		}
	}


//*****************************TIME FUNCTIONS***********************************************

	public function convertTimeToSeconds($hours,$minutes,$seconds)
	{
		return ($hours*self::TIME_SECONDS_PER_HOUR) + ($minutes*self::TIME_SECONDS_PER_MINUTE) + $seconds;
	}
	
	public function getTime($timeString = null,$format = false)
	{
		$time = $this->_getDateTime($timeString);
		if($format)
			$time = $this->formatTimestemp($time,$format);
			
		return $time;
	}
	
	public function getDate($timeString = null, $format = false) {
		$time = $this->_getDateTime($timeString);
		if ($format)
			$time = $this->formatDatestemp($time,$format);
			
		return $time;
	}
	
	public function getDateTime($timeString = null, $format = false) {
		$dateTime = $this->_getDateTime($timeString);
		
		if ($format)
			$dateTime = $this->formatDatetimestemp($dateTime,$format);
			
		return $dateTime;
	}
	
	protected function _getDateTime($timeString = null) {
		$time = null;
		if (!isset($timeString))
			$time = time();
		else
			$time = strtotime($timeString);
		
		//get time zone time
		return Mage::getModel('core/date')->timestamp($time);
	}
	
	public function getProcessorTime()
	{
		$mtime = microtime();
   		$mtime = explode(" ",$mtime);
   		$mtime = $mtime[1] + $mtime[0];
   		return $mtime;
	}
	
	/*
	 * Returns true if it is possible to generate XML.
	 */
	public function allowGenerate($profile)
	{
		if(!$profile->getEnabled()) //disabled to generate XML
			return false;

		$updateTime = $profile->getUpdateTime();
		if($updateTime == null)
			return true;
			
		$frequencyDaily = Mage_Adminhtml_Model_System_Config_Source_Cron_Frequency::CRON_DAILY;
		$frequencyWeekly = Mage_Adminhtml_Model_System_Config_Source_Cron_Frequency::CRON_WEEKLY;
		$frequencyMonthly = Mage_Adminhtml_Model_System_Config_Source_Cron_Frequency::CRON_MONTHLY;

		$frequency = $profile->getFrequency();
		$testTime = null;
		switch($frequency)
		{
			case Mage_Adminhtml_Model_System_Config_Source_Cron_Frequency::CRON_DAILY:
				$minutes = $this->getGeneralConfig(self::PARAM_CRON_PERIOD);
				$testTime = $this->getTime("-{$minutes} minutes");
				break;
			case Mage_Adminhtml_Model_System_Config_Source_Cron_Frequency::CRON_WEEKLY:
				$testTime = $this->getTime("-1 week");//time before week
				break;
			case Mage_Adminhtml_Model_System_Config_Source_Cron_Frequency::CRON_MONTHLY:
				$testTime =  $this->getTime("-1 month");	//time before month
				break;
			default:
				$testTime = null;
		}
		if(!isset($testTime))
			return false;
		
		$lastGenerationTime = strtotime($updateTime);
		if($lastGenerationTime <= $testTime)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	public function formatDatetimestemp($timestemp,$format)
	{
		return $this->datetimeModel()->formatDatetime($timestemp,$format);
	}
	
	public function formatDatestemp($timestemp,$format)
	{
		return $this->datetimeModel()->formatDate($timestemp,$format);
	}
	
	public function formatTimestemp($timestemp,$format)
	{
		return $this->datetimeModel()->formatTime($timestemp,$format);
	}
	
	protected function datetimeModel()
	{
		return  Mage::getModel("aukro/config_source_datetimeformat");
	}
	
//*******************************TIME FUNCTIONS - END ********************************************

//*********************** FILE FUNCTIONS******************************
	
	public function createDirectory($dir)
	{
	    if(!is_dir($dir))
	        mkdir($dir,self::DEF_PERMISSION,true);
	}
	
	public function downloadFile($fileUrl, $localFilename)
    {
        $err_msg = '';
       
        $out = fopen($localFilename,"wb");
        if (!$out)
        {
            $message = $this->__("Can't open file %s for writing",$localFilename);
            Mage::throwException($message);
        }
    
        $ch = curl_init();
    
        curl_setopt($ch, CURLOPT_FILE, $out);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $fileUrl);
    
        curl_exec($ch);
        $error = curl_error ( $ch);
        if(!empty($error))
        {
            $message = $this->__("Can't download file %s",$fileUrl);
            Mage::throwException($message);
        }
            
        curl_close($ch);
        fclose($out);
    
    }//end function
	
	/* creates a compressed zip file */
	public function createZip($files = array(), $destination = '', $overwrite = false) {
		//if the zip file already exists and overwrite is false, return false
		if (file_exists($destination) && !$overwrite) {
			return false;
		}
		//vars
		$valid_files = array();
		//if files were passed in...
		if (is_array($files)) {
			//cycle through each file
			foreach ($files as $localName => $file) {
				//make sure the file exists
				if (file_exists($file)) {
					$valid_files[$localName] = $file;
				}
			}
		}
		//if we have good files...
		if (count($valid_files)) {
			//create the archive
			$zip = new ZipArchive();
			if ($zip->open($destination,$overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
				return false;
			}
			
			//add the files
			foreach ($valid_files as $localName => $file) {
				if (is_numeric($localName))
					$localName = $file;
				$zip->addFile($file,$localName);
			}
			//debug
			//echo 'The zip archive contains ',$zip->numFiles,' files with a status of ',$zip->status;
			
			//close the zip -- done!
			$zip->close();
			
			//check to make sure the file exists
			return file_exists($destination);
		}
		else {
			return false;
		}
	}
	
	public function getDefaultDirectoryName() {
		return (string)$this->getGeneralConfig(self::PARAM_FILEPATH);
	}
	
	public function addTempPrefix($filename)
	{
	    $tempPrefix = (string)$this->getGeneralConfig(self::PARAM_TEMP_PREFIX);
	    return $tempPrefix.$filename;
	}
	
    public function getFeedDirectoryName($feed)
	{
		$index = strpos($feed,"_");
		if($index !== FALSE)
			$feed = substr($feed,0,$index);
		return $feed;
	}
	
	/*
	 * Returns xml files url path.
	 */
	public function getFileUrl($fileName,$feedDir)
	{
		$path = $this->getDefaultDirectoryName();
		$path = str_replace('/media/','',$path);
		$url = Mage::getBaseUrl('media').$path.$feedDir.self::PATH_DELIMITER.$fileName;
		return $url;
	}
	
	/*
	* Return full file path.
	*/
	public function getFullFilePath($fileName,$feedDir) {
		$file = str_replace('//', self::PATH_DELIMITER, Mage::getBaseDir() . $this->getDefaultDirectoryName().self::PATH_DELIMITER.$feedDir.self::PATH_DELIMITER.$fileName);
		return $file;
	}
	
	public function removeFileSuffix($filename) {
		$filename = str_ireplace(".".self::FILE_TYPE_XML,"",$filename);
		$filename = str_ireplace(".".self::FILE_TYPE_CSV,"",$filename);
		return $filename;
	}
	
	public function addFileSuffix($filename,$feed) {
		$feedData = Mage::getModel('aukro/feed')->getFeedByCode($feed);
		if (isset($feedData))
			$filename .= ".".$feedData->getFileType();
		return $filename;
	}
	
	/**
	* Renamse feed files.
	* @param $oldFileName
	* @param $newFileName
	*/
	public function renameFile($originalFile,$newFile) {
		//rename file
		if (is_file($originalFile)) {
			rename($originalFile,$newFile);
		}
	}
	
	/*
	 * Deletes specified xml file.
	 */
	public function deleteFile($file)
	{
		if($file == null || $file === '')
		    return;

		if (file_exists($file))
		{
			unlink($file);
		}
	}
	
	public function changeFileSuffix($filename, $suffix = self::FILE_TYPE_ZIP) {
		
		if(isset($suffix) && !empty($suffix))
			$newSuffix = ".".$suffix;
		else
			$newSuffix = $suffix;
		
		foreach ($this->_fileTypes as $type) {
			$oldSuffix = ".".$type;
			if (strpos($filename,$oldSuffix) !== false) {
				$filename = str_replace($oldSuffix,$newSuffix,$filename);
				return $filename;
			}
		}
		return $filename;
	}
	
	/**
	 * Returns opened file.
	 *
	 * @param profile $model
	 * @param Boolean $fileExists
	 * @return Opened file for writeing.
	 */
	public function openFile($fileName,$mode)
	{
		$io = new Varien_Io_File();
        $io->setAllowCreateFolders(true);

        $index = strrpos($fileName,self::PATH_DELIMITER)+1;
        $path = substr($fileName,0,$index);
        $name = substr($fileName,$index);
        $io->open(array('path' => $path));
         
        try
        {
        	$io->streamOpen($name,$mode);
        }
        catch (Exception $e)
        {
        	throw $e;
        }
        return $io;
	}
	

//*********************** CRON TABLE**********************************
	/**
	 *
	 * @param Mage_Cron_Model_Schedule $schedule
	 * @return Varien_Object
	 */
	public function getRuntime(Mage_Cron_Model_Schedule $schedule)
	{
		$execTime = $schedule->getExecutedAt();
		$stopTime = $schedule->getFinishedAt();
		if($execTime == '0000-00-00 00:00:00') {
			$runtime = new Varien_Object();
			$runtime->setIsPending(1);
			$runtime->setHours(0);
			$runtime->setMinutes(0);
			$runtime->setSeconds(0);
			$runtime->setToString('0h 0m 0s');
			return $runtime;
		}

		if($stopTime == '0000-00-00 00:00:00') {
			$stopTime = now();
		}

		$runtime = strtotime($stopTime) - strtotime($execTime);
		$runtimeSec = $runtime % self::TIME_SECONDS_PER_MINUTE;
		$runtimeMin = (int) ($runtime / self::TIME_SECONDS_PER_MINUTE) % self::TIME_SECONDS_PER_MINUTE;
		$runtimeHour = (int) ($runtime / self::TIME_SECONDS_PER_HOUR);

		$runtime = new Varien_Object();
		$runtime->setIsPending(0);
		$runtime->setHours($runtimeHour);
		$runtime->setMinutes($runtimeMin);
		$runtime->setSeconds($runtimeSec);
		$runtime->setToString($runtimeHour . 'h ' . $runtimeMin . 'm ' . $runtimeSec . 's');
		return $runtime;
	}

	/**
	 * @todo render as Column in Grid
	 * @todo unterscheiden zwischen überfällig Rot normal schwarz
	 *
	 * @param Mage_Cron_Model_Schedule $schedule
	 * @return Varien_Object
	 */
	public function getStartingIn(Mage_Cron_Model_Schedule $schedule)
	{
		$schedTime = $schedule->getScheduledAt();

		if($schedTime == '0000-00-00 00:00:00' or $schedTime == '')
        {
			$runtime = new Varien_Object();
			$runtime->setHours(0);
			$runtime->setMinutes(0);
			$runtime->setSeconds(0);
			$runtime->setToString('0h 0m 0s');
			return $runtime;
		}

		// Calc Time interval till Exec
		$starttime = strtotime($schedTime) - strtotime(now());
		$prefix = '+';
		if($starttime < 0) {
			$prefix = '-';
			$starttime *= - 1;
		}
		$runtimeSec = $starttime % self::TIME_SECONDS_PER_MINUTE;
		$runtimeMin = (int) ($starttime / self::TIME_SECONDS_PER_MINUTE) % self::TIME_SECONDS_PER_MINUTE;
		$runtimeHour = (int) ($starttime / self::TIME_SECONDS_PER_HOUR);

		$runtime = new Varien_Object();
		$runtime->setHours($runtimeHour);
		$runtime->setMinutes($runtimeMin);
		$runtime->setSeconds($runtimeSec);
		$runtime->setPrefix($prefix);
		$runtime->setToString($runtimeHour . 'h ' . $runtimeMin . 'm ' . $runtimeSec . 's');

		return $runtime;
	}
	
	public function dS($input)
    {
    	return base64_decode($input);
    }

	/**
	 *
	 * @return
	 */
	public function getAvailableJobCodes()
	{
		return Mage::getConfig()->getNode('crontab/jobs');
	}

    /**
     * Transforms a datetime string into a DateTime object in the UTC (GMT) timezone
     * (Assumes that $datetime_string is currently in the timezone set in the Magento config)
     * @param  $datetime_string
     * @return DateTime
     */
    public function dateCorrectTimeZoneForDb($datetime_string)
    {
        $timezone_mage = new DateTimeZone(Mage::getStoreConfig('general/locale/timezone'));

        //$timezone_php = date_default_timezone_get();
        $datetime_mage = new DateTime($datetime_string, $timezone_mage);
        $datetime_offset = $datetime_mage->getOffset(); // offset in seconds, including daylight savings time
        $datetime_mage->modify('-'.$datetime_offset.' seconds');

        return $datetime_mage;
    }
    
    public function getHelpUrl($helpPath)
    {
        $path = self::PATH_HELP.$helpPath;
        return $this->_getConfig($path);
    }
    
//*********************** CRON TABLE - END**********************************
	
	public function array_unique_tree($array_tree) {
		$temp = array();
		$result = array();
		$labels = array();
		foreach ($array_tree as $key => $array) {
			if (!in_array($array["label"], $temp))
			{
				$labels[] = $array["label"];
				$temp[$key] = $array["label"];
				$result[] = $array;
			}
		}
		array_multisort($labels,SORT_ASC,SORT_REGULAR,$result);
		return $result;
	}
	
    public function updateCategoryProducts($profileId,$categoryproducts,$storeId)
    {
		$catProdModel = Mage::getModel('aukro/categoryproducts');
		$catProdModel->updateCategoryProducts($profileId,$categoryproducts,$storeId);
		Mage::getSingleton('adminhtml/session')->addSuccess(
              	Mage::helper('aukro')->__('The export profile-product relations has been updated.')
        );
    }
    
	public function getAukroLoginData() {
	    
		$storeId = Mage::app()->getStore()->getId();
		$data = array();
  		$data['username'] = (string) Mage::getStoreConfig ( 'aukro/connection_settings/username', $storeId);
  		$data['password'] = (string) Mage::getStoreConfig ( 'aukro/connection_settings/password', $storeId);
  		$data['webapi_key'] = (string) Mage::getStoreConfig ( 'aukro/connection_settings/webapi_key', $storeId);
  		return $data;
	}
	
	public function getDefaultConfig() {
	    
	    $storeId = Mage::app()->getStore()->getId();
	    $data = array();
	    $config = Mage::getStoreConfig ( 'aukro', $storeId);
	    foreach( $config as $section) {
	        foreach( $section as $key => $value) {
	            $data[$key] = $value;
	        }
	    }
	    unset( $data['username'], $data['password'], $data['webapi_key'], $data['wire_transfer'], $data['collect_on_delivery']);
	    return $data;
	}
	
	public function getAukroWebApiKey() {
  		return (string) Mage::getStoreConfig ( 'aukro/connection_settings/webapi_key');
	}
	
	public function formatAukroCategories($categories) {
		
		$output = array('0' => 'None');
		$groupedCategories = array();
		
		foreach ($categories['catsList']['item'] as $params) {
			$groupedCategories[$params['catParent']][$params['catId']] = $params['catName'];
		}
		
		$output = $output + $this->getSubCategories($groupedCategories, 0);
		
		return $output;
		
	}
	
	public function getSubCategories($groupedCategories, $id, $parentName = '') {
		$output = array();
		if (strlen($parentName) == 0) {
			$prefix = '';
		} else {
			$prefix = $parentName.' > ';
		}
		if (array_key_exists($id, $groupedCategories)) {
			foreach ($groupedCategories[$id] as $key => $name) {
				$output[$key] = $prefix.$name;
				$output = $output + $this->getSubCategories($groupedCategories, $key, $prefix.$name );
			}
		}
		
		return $output;
	}

	function formatData( $data) {
	
	    if( is_array( $data)) {
	        if( key( $data) !== 0) {
	            $tempData = array();
	            $tempData[] = $data;
	            $data = $tempData;
	        }
	        return $data;
	    } else {
	        return array( $data);
	    }
	}
}
	 