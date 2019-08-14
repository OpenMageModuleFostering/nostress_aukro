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
* Xml data transformation for export process
* @category Nostress
* @package Nostress_Aukro
*
*/

class Nostress_Aukro_Model_Data_Transformation_Xml extends Nostress_Aukro_Model_Data_Transformation
{
    const GROUP_ID = 'group_id';
    const IS_CHILD = 'is_child';
    
    const MAIN_TAG = 'items';
    const ITEM_TAG = 'item';
    const BASIC_ATTRIBUTES = 'attributes';
    const MULTI_ATTRIBUTES = 'multi_attributes';
    const CUSTOM_ATTRIBUTES = 'custom_attributes';
    const TRANSLATIONS = 'translate';
        
    const EMPTY_VALUE = "";
    
    const DEF_TEXT_SEPARATOR = '"';
    const DEF_SETUP_SEPARATOR = ',';
    const DEF_DECIMAL_DELIMITER = '.';
    const DEF_CATEGORY_DELIMITER = "/";
    
    const SUPER_ATTRIBUTES = 'super_attributes';
    const MEDIA_GALLERY = 'media_gallery';
    const CATEGORIES = 'categories';
    
    const PP_CDATA = 'cdata';
    const PP_ENCODE_SPECIAL = 'encode_special_chars';
    const PP_DECODE_SPECIAL = 'decode_special_chars';
    const PP_REMOVE_EOL = 'remove_eol';
    const PP_STRIP_TAGS = 'strip_tags';
    const PP_DELETE_SPACES = 'delete_spaces';
    
    protected $_store;
	protected $_parent;
	protected $_groupId;
	protected $_row;
	protected $_multiAttributes = array(self::SUPER_ATTRIBUTES=>"attribute",self::MEDIA_GALLERY=>"image",self::CATEGORIES=>"category");
	protected $_itemData = "";
	protected $_multiAttributesMap;
	protected $_customAttributesMap;
	protected $changeDecimalDelimiter = false;
	protected $_postProcessFunctions = array(	self::PP_CDATA => "Character data",
												self::PP_ENCODE_SPECIAL=>"Encode special characters",
												self::PP_DECODE_SPECIAL=>"Decode special characters",
												self::PP_REMOVE_EOL=>"Remove end of lines",
												self::PP_STRIP_TAGS=>"Strip tags",
												self::PP_DELETE_SPACES=>"Delete spaces"
												);

	public function init($params)
    {
        parent::init($params);
        $this->preprocessAttributes();
        $this->initAttributeMaps();
        $this->initDecimalDelimiter();
    }
    
	public function transform($data)
	{
		parent::transform($data);
		$saveItemData = false;
		
		foreach($data as $row)
		{
		    $this->setRow($row);
		    $isChild = $this->getValue(self::IS_CHILD);
			if(!$isChild)
		    {
                $saveItemData = true;
		    }
		    
		    if($this->getChildsOnly() && !$isChild && !$this->isSimpleProduct())
		    {
		        continue;
		    }
		    
		    if($saveItemData)
		    {
		        $saveItemData = false;
                $this->saveItemData();
		    }
		    
		    $label = $this->getIsChildLabel($isChild);
            $this->addItemData($this->getTag($label));
		    
		    $this->processBasicAttributes($isChild);
		    $this->processCustomAttributes($isChild);
		    $this->processMultiAttributes();
		    $this->addItemData($this->getTag($label,true));
		}
	}
	
    protected function initDecimalDelimiter()
    {
        $delimiter = $this->getDecimalDelimiter();
        if($delimiter != self::DEF_DECIMAL_DELIMITER)
            $this->changeDecimalDelimiter = true;
    }
    
    protected function initAttributeMaps()
    {
        $map = $this->getAttributes();
        
		$this->_multiAttributesMap = array();
		foreach ($map as $key => $attribute)
		{
		    //add static attributes
		   $attribute = $this->initStaticAttributes($attribute);
		    
		    if(isset($attribute[self::POST_PROCESS]))
		    {
		    	$postprocessFuncitons = $attribute[self::POST_PROCESS];
		    	if(empty($postprocessFuncitons))
		    		$postprocessFuncitons = array();
		    	else
		    	{
		    		if(strpos($postprocessFuncitons,self::POSTPROC_DELIMITER) === false)
		    			$postprocessFuncitons = array($postprocessFuncitons);
		    		else
		    			$postprocessFuncitons = explode(self::POSTPROC_DELIMITER,$postprocessFuncitons);
		    	}
		    	$attribute[self::POST_PROCESS] = $postprocessFuncitons;
		    }
		    
		    //Choose custom attributes
		    if($attribute[self::CODE]== self::CUSTOM_ATTRIBUTE)
		    {
		    	$info = $this->helper()->getAttributeInfo($attribute[self::MAGENTO_ATTRIBUTE]);
		    	$label = $this->helper()->getAttributeLabel($info,$this->getStoreId());
		    	 
		    	$attribute[self::TAG] = $attribute[self::LABEL];
		    	if(!empty($label))
		    		$attribute[self::LABEL] = $label;
		    
		    	$this->_customAttributesMap[] = $attribute;
		    	unset($map[$key]);
		    	continue;
		    }
		    
		    //Remove multi attributes from attribute map
		    if(array_key_exists($attribute[self::MAGENTO_ATTRIBUTE],$this->_multiAttributes))
		    {
		    	$this->_multiAttributesMap[] = $attribute;
		    	unset($map[$key]);
		    	continue;
		    }
		    $map[$key] = $attribute;
		}
		//Format price
		$map = $this->preparePriceFields($map);
		$this->setAttributes($map);
    }
    
    protected function initStaticAttributes($attribute)
    {
    	$resetMagentoAttribute = true;
    	switch($attribute[self::MAGENTO_ATTRIBUTE])
    	{
    		//Add currency into feed
    		case self::CURRENCY:
    			$attribute[self::CONSTANT] .= $this->helper()->getStoreCurrency($this->getStoreId());
    			break;
    		case self::COUNTRY_CODE:
    			$attribute[self::CONSTANT] .= $this->helper()->getStoreCountry($this->getStore());
    			break;
    		case self::LOCALE:
    			$attribute[self::CONSTANT] .= $this->helper()->getStoreLocale($this->getStore());
    			break;
    		default:
    			$resetMagentoAttribute = false;
    			break;
    	}
    	if($resetMagentoAttribute)
    		$attribute[self::MAGENTO_ATTRIBUTE] = "";
    	return $attribute;
    }
    
	public function getResult($allData = false)
	{
	    if($allData)
	        $this->saveItemData();
	    $result = parent::getResult();
	    if(!empty($result))
	        $result = $this->getHeader().$result.$this->getTail();
	    return $result;
	}
	
	protected function preProcessRow($row)
	{
	    if(array_key_exists("stock_status",$row))
	    {
	       $stockStatus = $row["stock_status"];
	       $attribute = '';
	       $stockStatus = $this->getStockStatusValue($stockStatus,$attribute);
	       if(!empty($attribute))
	           $stockStatus = $row[$attribute];
	       $row["stock_status"] = $stockStatus;
	       
	    }
	    return $row;
	}
	
	protected function getStockStatusValue($status,&$attribute)
	{
	    $stock = $this->getStock();
	    if($status)
	        $status = $stock["yes"];
	    else
	    {
	        if(empty($stock["availability"]))
	             $status = $stock["no"];
	        else
	           $attribute =  $stock["availability"];
	    }
	    return $status;
	}
	
	protected function check($data)
	{
		if(!parent::checkSrc($data) || !is_array($data))
		{
			$message = $this->logAndException("Xml transformation source data are wrong.");
		}
		return true;
	}
	
	protected function preprocessAttributes()
	{
	    $attributes = $this->getAttributes();
	    $attributes = $this->getArrayField(self::ATTRIBUTE,$attributes,array());
	    if(empty($attributes))
	        $this->logAndException("Missing feed attributes configuration.");
	    $this->setAttributes($attributes);
	}
    
	protected function setRow($row)
	{
	    $row = $this->preProcessRow($row);
	    
	    if(array_key_exists(self::GROUP_ID,$row) && $this->setGroupId($row[self::GROUP_ID]))
	    {
	        $this->setParent($row);
	    }
	    $this->_row = $row;
	}
	
	protected function getAttributeValue($setup,$isChild)
	{
        $magentoAttribute = $setup[self::MAGENTO_ATTRIBUTE];
        $value = $this->getValue($magentoAttribute,$isChild && $setup[self::PARENT_ATTRIBUTE_VALUE]);

        if( isset($setup[self::TRANSLATIONS]) && is_array($setup[self::TRANSLATIONS])) {
         	foreach ($setup[self::TRANSLATIONS] as $translation) {
         		if ($translation['from'] == $value)
         			$value = $translation['to'];
         	}
        }
        
    	if(empty($value) && $value !== "0")
    	    $value = $setup[self::CONSTANT];
    	if( isset($setup[self::PREFIX])) {
    	    $value = $setup[self::PREFIX].$value;
    	}
    	if( isset($setup[self::SUFFIX])) {
    	    $value .= $setup[self::SUFFIX];
    	}
    	//prepocess value
    	$pp = isset($setup[self::POST_PROCESS]) ? $setup[self::POST_PROCESS] : array();
    	$limit = isset( $setup[self::LIMIT]) ? $setup[self::LIMIT] : null;
    	$value = $this->postProcess( $value, $pp, $limit);
    	return $value;
	}
	
	protected function getValue($index,$parent = false)
	{
	    if($parent)
	    {
	        $value = $this->getParentValue($index);
	    }
	    else
	    {
	        $value = $this->getArrayValue($index,$this->_row);
	    }
	    
	    $value = $this->prepareValue($value,$index);

	    return $value;
	}
	
	protected function prepareValue($value,$index)
	{
		if($this->changeDecimalDelimiter && is_numeric($value))
			$value = str_replace(self::DEF_DECIMAL_DELIMITER,$this->getDecimalDelimiter(),$value);
		if($index == self::CATEGORY_PATH)
		{
			$value = str_replace(self::DEF_CATEGORY_DELIMITER, $this->getCategoryPathDelimiter(), $value);
		}
		
		return $value;
	}
	
	protected function getParentValue($index)
	{
	    return $this->getArrayValue($index,$this->_parent);
	}
	
	protected function getArrayValue($index,$array)
	{
	    if(array_key_exists($index,$array))
	        return $array[$index];
	    else
	    {
	        $this->helper()->log($this->helper()->__("Missing input data column %s",$index));
	        return self::EMPTY_VALUE;
	    }
	        
	}
	
	protected function setGroupId($groupId)
	{
	    if($groupId == $this->_groupId)
	        return false;
	    else
	    {
	        $this->_groupId = $groupId;
	        return true;
	    }
	}
	
	protected function setParent($row)
	{
	    $this->_parent = $row;
	}
	
	protected function getHeader()
	{
	    return "<?xml version=\"1.0\" encoding=\"{$this->getEncoding()}\" ?><".self::MAIN_TAG.">";
	}
	
	protected function getTail()
	{
	    return "</".self::MAIN_TAG.">";
	}
	
	protected function getElement($name,$value)
	{
	    return "<{$name}>{$value}</{$name}>";
	}
	
    protected function getTag($name,$end = false)
   	{
   		if($end)
   			return "</{$name}>";
   		else
   		{
   			return "<{$name}>";
   		}
   	}
   	
   	protected function addItemData($string)
   	{
   	    $this->_itemData .= $string;
   	}

   	protected function saveItemData()
   	{
   	    if(empty($this->_itemData))
   	        return;
   	    $element = $this->getElement(self::ITEM_TAG,$this->_itemData);
   	    $this->appendResult($element);
   	    $this->_itemData = "";
   	}
   	
   	protected function postProcess($value,$setup = null,$limit  = null)
   	{
   	    if(empty($value))
   	        return $value;
   	    
   	    
   	    if(empty($setup) || !is_array($setup))
   	    {
   	        $setup = array();
   	    }

        $value = $this->ppLimit($value,$limit);
   	    foreach ($setup as $item)
   	    {
   	        switch($item)
   	        {
   	            case self::PP_ENCODE_SPECIAL:
   	                $value = $this->ppEncodeSpecial($value);
   	                break;
   	            case self::PP_DECODE_SPECIAL:
   	                $value = $this->ppDecodeSpecial($value);
   	                break;
   	            case self::PP_STRIP_TAGS:
   	                $value = $this->ppStripTags($value);
   	                break;
   	            case self::PP_DELETE_SPACES:
   	                $value = $this->ppDeleteSpaces($value);
   	                break;
   	            case self::PP_REMOVE_EOL:
   	                $value = $this->ppRemoveEol($value);
   	                break;
   	        }
   	    }
   	    $value = $this->ppFile($value,in_array(self::PP_CDATA,$setup));
   	    $value = $this->ppDefault($value);
   	    return $value;
   	}
	
   	protected function ppEncodeSpecial($value)
   	{
   	    return htmlspecialchars($value);
   	}
   	
   	protected function ppDecodeSpecial($value)
   	{
   	    return htmlspecialchars_decode($value);
   	}
   	
   	protected function ppStripTags($value)
   	{
   	    return strip_tags($value);
   	}
   	
   	protected  function ppDeleteSpaces($string)
   	{
   	    return preg_replace("/\s+/", '', $string);
   	}
   	
   	protected function ppRemoveEol($string)
   	{
   	    return str_replace(array("\r\n", "\r", "\n"), ' ', $string);
   	}
   	
   	protected function ppFile($value)
   	{
   	    if($this->getFileType())
   	        $value = $this->ppXml($value);
   	    else if($this->getFileType() == self::CSV || $this->getFileType() == self::TXT)
   	        $value = $this->ppCsv($value);
   	        
   	    return $value;
   	}
   	
   	protected function ppCsv($value)
   	{
   	    $value = str_replace(self::DEF_TEXT_SEPARATOR,"&quot;",$value);
   	    return $value;
   	}
   	
   	protected function ppXml($value)
   	{
   		return $value;
   	    //return htmlspecialchars(strip_tags(str_replace(">","> ",$value)));
   	}
   	
   	protected function ppDefault($value)
   	{
   	    $value = $this->helper()->changeEncoding($this->getEncoding(),$value);
   	    $value = $this->getCdataString($value);
   	    return $value;
   	}
   	
   	protected function ppLimit($value,$limit)
   	{
   	    if(isset($limit) && !empty($limit))
   	    {
   	        $value = substr($value,0,$limit);
   	    }
   	    return $value;
   	}
   	
   	protected function getCdataString($input)
   	{
   		return $this->helper()->getCdataString($input);
   	}
   	
   	protected function getIsChildLabel($isChild)
   	{
   	    $label = self::PARENT;
		if($isChild)
		    $label = self::CHILD;
        return $label;
   	}
   	
	protected function processMultiAttributes()
	{
		if(!isset($this->_multiAttributes) || empty($this->_multiAttributes))
			return;
		$this->addItemData($this->getTag(self::MULTI_ATTRIBUTES));
	    foreach (array_keys($this->_multiAttributes) as $multiAttribute)
	    {
	        $parent = false;
	        if($multiAttribute == self::SUPER_ATTRIBUTES)
	            $parent = true;
	    	$multiAttribValue = $this->getValue($multiAttribute,$parent);
	    	if(!isset($multiAttribute))
	    	    continue;
	    	
	    	if(!is_array($multiAttribValue))
	    	{
	    	    $columns = $this->getMultiAttributeColumns($multiAttribute);
	    	    if(!isset($columns))
	    	        return;
	    	    $multiAttribValue = $this->parseAttribute($multiAttribValue,$columns);
                $this->setParentAttribute($multiAttribute,$multiAttribValue);
	    	}
	    	
	    	if($multiAttribute == self::SUPER_ATTRIBUTES)
	    	    $multiAttribValue = $this->processSuperAttributes($multiAttribValue);
	    	
	    	$string = $this->arrayToXml($multiAttribValue,$multiAttribute);
	    	$this->addItemData($string);
	    }
	    $this->addItemData($this->getTag(self::MULTI_ATTRIBUTES,true));
	}
	
	protected function getMultiAttributeColumns($attributeName)
	{
	    $resourceModelName = "";
	    switch ($attributeName)
	    {
	        case self::SUPER_ATTRIBUTES:
	            $resourceModelName = 'aukro/cache_superattributes';
	            break;
	        case self::CATEGORIES:
	            $resourceModelName = 'aukro/cache_categories';
	            break;
	        case self::MEDIA_GALLERY:
	            $resourceModelName = 'aukro/cache_mediagallery';
	            break;
	        default:
	            return null;
	    }
	    
	    $resourceModel = Mage::getResourceModel($resourceModelName);
	    $columns = array_keys($resourceModel->getCacheColumns());
	    return $columns;
	}
	
	protected function parseAttribute($attributeValue,$columns)
	{
	    $itemSeparator = Nostress_Aukro_Helper_Data_Loader::GROUP_ROW_ITEM_SEPARATOR;
	    $rowSeparator = Nostress_Aukro_Helper_Data_Loader::GROUP_ROW_SEPARATOR;
	    
	    $rows = explode($rowSeparator,$attributeValue);
	    $result = array();
	    foreach ($rows as $key => $row)
	    {
	        $values = explode($itemSeparator,$row);
	        if(count($columns) == count($values))
	            $result[$key] = array_combine($columns,$values);
	    }
	    return $result;
	}
	
	protected function setParentAttribute($index,$value)
	{
	    if(!$this->getValue(self::IS_CHILD) && isset($this->_parent))
	        $this->_parent[$index] = $value;
	}
	
	protected function arrayToXml($input,$multiAttribute)
	{
	    $result = "";
	    foreach ($input as $row)
	    {
	        $rowText = "";
	        foreach($row as $index => $value)
	        {
	            if(empty($value))
	                continue;
	            $value = $this->postProcess($value);
	            $rowText .= $this->getElement($index,$value);
	        }
	        
	        if(!empty($rowText))
	            $result .= $this->getElement($this->_multiAttributes[$multiAttribute],$rowText);
/*	    	$xml = new SimpleXMLElement("<{$this->_multiAttributes[$multiAttribute]}/>");
            array_walk_recursive($row, array ($xml, 'addChild'));
            $result .= $xml->asXML();	*/
	    }
	    if(!empty($result))
	        $result = $this->getElement($multiAttribute,$result);
	    return $result;
	}
	
	protected function processSuperAttributes($attributes)
	{
	    if(!$this->getValue(self::IS_CHILD))
	        return array();
	    foreach ($attributes as $key => $attribute)
	    {
	    	$code = $this->getArrayValue(self::CODE,$attribute);
	    	$value = $this->getValue($code);
	    	unset($attributes[$key][self::CODE]);
	    	$attributes[$key][self::VALUE] = $value;
	    }
	    return $attributes;
	}
	
	protected function isSimpleProduct()
	{
	    return $this->getValue(self::TYPE) == "simple";
	}
	
	public function getChildsOnly()
	{
	    $parentChilds = $this->getParentsChilds();
	    if($parentChilds == Nostress_Aukro_Model_Config_Source_Parentschilds::CHILDS_ONLY)
	        return true;
	    return false;
	}
	
	protected function preparePriceFields($map)
	{
	    $priceFormat = $this->getPriceFormat();
	    
	    $currency = $this->helper()->getStoreCurrency($this->getStoreId());
	    $symbol = $this->helper()->getStoreCurrency($this->getStoreId(),true);
	    
	    foreach ($map as $key => $attributesInfo)
	    {
	        if(strpos($attributesInfo[self::MAGENTO_ATTRIBUTE],"price") !== false)
	        {
	            switch($priceFormat)
	            {
	                case Nostress_Aukro_Model_Config_Source_Priceformat::CURRENCY_SUFFIX:
	                    $attributesInfo[self::SUFFIX] = " ".$currency.$attributesInfo[self::SUFFIX];
	                    break;
	                case Nostress_Aukro_Model_Config_Source_Priceformat::CURRENCY_PREFIX:
	                    $attributesInfo[self::PREFIX] .= $currency." ";
	                    break;
	                case Nostress_Aukro_Model_Config_Source_Priceformat::SYMBOL_SUFFIX:
	                    $attributesInfo[self::SUFFIX] = " ".$symbol.$attributesInfo[self::SUFFIX];
	                    break;
	                case Nostress_Aukro_Model_Config_Source_Priceformat::SYMBOL_PREFIX:
	                    $attributesInfo[self::PREFIX] .= $symbol." ";
	                    break;
	                default:
	                    break;
	            }
	            $map[$key] = $attributesInfo;
	        }
	    }
	    return $map;
	}
	
	public function getPostProcessFunctions()
	{
		return $this->_postProcessFunctions;
	}
	
	///////////////////////////////////CUSTOM ATTRIBUTES////////////////////////////////////////
	protected function processBasicAttributes($isChild)
	{
		$map = $this->getAttributes();
		$this->addItemData($this->getTag(self::BASIC_ATTRIBUTES));
	    foreach ($map as $attributeInfo)
	    {
	    	$value = $this->getAttributeValue($attributeInfo,$isChild);
	    	if( $value == "0" || !empty($value))
	    	    $this->addItemData($this->getElement($attributeInfo[self::CODE],$value));
	    }
	    $this->addItemData($this->getTag(self::BASIC_ATTRIBUTES,true));
	}
	
	///////////////////////////////////CUSTOM ATTRIBUTES////////////////////////////////////////

	protected function processCustomAttributes($isChild)
	{
	    if(!isset($this->_customAttributesMap) || empty($this->_customAttributesMap))
	        return;
	    
		$this->addItemData($this->getTag(self::CUSTOM_ATTRIBUTES));
		foreach ($this->_customAttributesMap as $attributeInfo)
	    {
	    	$value = $this->getAttributeValue($attributeInfo,$isChild);
	    	if(empty($value))
	    		continue;
	    	
	    	$this->addItemData($this->getTag(self::ATTRIBUTE));
	    	$this->addItemData($this->getElement(self::VALUE,$value));
	    	$this->addItemData($this->getElement(self::TAG,$attributeInfo[self::TAG]));
	    	$this->addItemData($this->getElement(self::LABEL,$attributeInfo[self::LABEL]));
	    	$this->addItemData($this->getTag(self::ATTRIBUTE,true));
	    }
	    $this->addItemData($this->getTag(self::CUSTOM_ATTRIBUTES,true));
	}
	
	///////////////////////////////////COMMON FUNCTIONS/////////////////////////////////
	protected function getStore()
	{
		if(!isset($this->_store))
			$this->_store = Mage::app()->getStore($this->getStoreId);
		return $this->_store;
	}
}
