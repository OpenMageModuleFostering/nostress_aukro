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
* Xslt data transformation for export process
* @category Nostress 
* @package Nostress_Aukro
* 
*/

class Nostress_Aukro_Model_Data_Transformation_Xslt extends Nostress_Aukro_Model_Data_Transformation
{   		
	const ENCODING_TAG = '{{encoding}}'; 
	const CDATA_SECTION_TAG = '{{cdata_section_elements}}';
	const CUSTOM_COLUMNS_HEADER_TAG = "{{custom_columns_header}}";
	const COLUMNS_HEADER_TAG = "{{columns_header}}";
	const CSV_CUSTOM_ATTRIBUTES_TAG = "{{csv_custom_attributes}}";
	const CSV_CUSTOM_ATTRIBUTES_TEMPLATE = '<xsl:call-template name="column_param"><xsl:with-param name="value" select="attribute[{{i}}]/value"/></xsl:call-template>';
	const INDEX_TAG = "{{i}}";
	
	const INPUT_FILE = 'var/input.xml';
	const XSLT_FILE = 'var/trans.xsl';
	const DEF_ELEMENTS_DELIMITER = " ";
	const DEF_COLUMNS_DELIMITER = "|";	
	
	public function transform($data)
	{
		parent::transform($data);
		
		$xp = $this->initProcessor();
		$data = $this->initData($data);
		$this->_transform($xp,$data);  			    						
	}
	
	protected function _transform($xp,$data)
	{	    
        $result = "";
        $result = $xp->transformToXML($data); 

        if(!$result)
        {   
            $errMessage = "";
            $e = libxml_get_last_error();
            if($e)
            {
                $errMessage = $e->message;
            } 
            $this->logAndException('XSL transformation failed.', $errMessage);
        }
	   	$this->appendResult($result);
	}
	
	protected function initProcessor()
	{	    
		if(!class_exists("XsltProcessor"))
			$this->logAndException("Can't load class XsltProcessor. Please install/enable XSLT transformation library to/on your server.");
	    $xp = new XsltProcessor();
        // create a DOM document and load the XSL stylesheet
        $xsl = new DomDocument;
        $xsl->loadXML($this->getXslt());
  
        if($this->isDebugMode())
        {	
        	file_put_contents(self::XSLT_FILE,$this->getXslt());        	
        }
        
        // import the XSL styelsheet into the XSLT process
        $xp->importStylesheet($xsl); 
        $xp = $this->setProcessorParameters($xp);
        return $xp;
	}
	
	protected  function initData($data)
	{
        // create a DOM document and load the XML datat
        $xml_doc = new DomDocument;
        if($this->isDebugMode())
        	file_put_contents(self::INPUT_FILE,$data);
        	
        if(!$xml_doc->loadXML($data))
        {
            $this->logAndException("Can't init Xslt transformation data source.");
        }
        
        return $xml_doc;
	}
	
	protected function check($data)
	{
		if(!parent::checkSrc($data) || empty($data))
		{	
		    $this->logAndException("Xslt transformation source data are wrong.");						
		}
		return true;
	}
	
	protected function getXslt()
	{
		$xslt = parent::getXslt();
		$xslt = str_replace(self::ENCODING_TAG,$this->getEncoding(),$xslt); 
		
		switch($this->getFileType())
		{
		    case self::XML:
		        $xslt = str_replace(self::CDATA_SECTION_TAG,$this->getCdataSectionElements(),$xslt);
		        break;
		    case self::CSV:	
		 	case self::TXT:	
		        $xslt = str_replace(self::CSV_CUSTOM_ATTRIBUTES_TAG,$this->getCustomAttributesXslt(),$xslt);        
		        break;
		}				
		
		return $xslt;
	}
	
	protected function setProcessorParameters($xp)
	{
		$params = $this->getCustomParameters();
		$params = array_merge($this->getCommonParams(),$params);
		$params = array_merge($this->getFileTypeParams(),$params);
		
		foreach ($params as $code => $value) 
		{
			$xp->setParameter('', $code, $value);
		}
		return $xp;
	}
	
	protected function getCustomParameters()
	{
		$result = array();
		$params = $this->getCustomParams();
		if(isset($params) && isset($params[self::PARAM]) && is_array($params[self::PARAM]))
		{
			foreach ($params[self::PARAM] as $param) 
			{
				$result[$param[self::CODE]] = $param[self::VALUE];
			}
		}
		return $result;
	}
	
	protected function getFileTypeParams()
	{
		$result = array();
		if($this->getFileType() == self::CSV || $this->getFileType() == self::TXT)
		{
			$result[self::TEXT_ENCLOSURE] =  $this->getTextEnclosure();
			$result[self::COLUMN_DELIMITER] = $this->getColumnDelimiter();
			$result[self::NEWLINE] = $this->getNewLine();
			$result[self::COLUMNS_HEADER] = $this->getColumnsHeader();
		}
		return $result;
	}
	
	protected function getCommonParams()
	{
		$store = Mage::app()->getStore($this->getStoreId());
		$result = array();
		$result[self::LOCALE] =  $this->helper()->getStoreLocale($store);
		$result[self::LANGUAGE] = $this->helper()->getStoreLanguage($store);
		$result[self::COUNTRY] = $this->helper()->getStoreCountry($store);
		$result[self::DATE] = $this->helper()->getDate(null,true);
		$result[self::DATE_TIME] = $this->helper()->getDatetime(null,$this->getDatetimeFormat());
		$result[self::TIME] = $this->helper()->getTime(null,true);
		
		return $result;
	}
	
	protected function getCdataSectionElements()
	{
		$elements = parent::getCdataSectionElements();
		$result = "";
		if(is_array($elements) && !empty($elements))
		{
			$result = implode(self::DEF_ELEMENTS_DELIMITER, $elements);
		}
		return $result;		
	}
	
	protected function getStrippedColumnsHeader()
	{
		$headerTemplate = parent::getColumnsHeader();
		$headerTemplate = str_replace(self::COLUMNS_HEADER_TAG,"",$headerTemplate);
		$headerTemplate = str_replace(self::CUSTOM_COLUMNS_HEADER_TAG,"",$headerTemplate);
		return $headerTemplate;
	}
	
	protected function _getColumnsHeader()
	{
		$headerTemplate = $this->getStrippedColumnsHeader();
		
		if(!empty($headerTemplate))
		{
			if(strpos($headerTemplate,self::DEF_COLUMNS_DELIMITER) !== false)
				$headerTemplate = explode(self::DEF_COLUMNS_DELIMITER, $headerTemplate);
			else
				$headerTemplate = array($headerTemplate);
			
			$headerTemplate = $this->prepareCsvRow($headerTemplate);
		}
		else 
			$headerTemplate = "";
		
		return $headerTemplate;

	}
	protected function getColumnsHeader()
	{
		$headerTemplate = parent::getColumnsHeader();
		$staticColumns = $this->_getColumnsHeader();
		$attributeColumns = $this->getBasicAttributesColumnsHeader();
		$customColumns = $this->getCustomColumnsHeader();
		
		$headerTemplateStripped = $this->getStrippedColumnsHeader();
		
		$headerTemplate = str_replace($headerTemplateStripped,$staticColumns,$headerTemplate);
		$headerTemplate = str_replace(self::COLUMNS_HEADER_TAG,$attributeColumns,$headerTemplate);
		$headerTemplate = str_replace(self::CUSTOM_COLUMNS_HEADER_TAG,$customColumns,$headerTemplate);
		
		$delimiter = $this->getColumnDelimiter();
		$enclosure = $this->getTextEnclosure();
		$headerTemplate = str_replace($enclosure.$enclosure,"",$headerTemplate);
		$headerTemplate = str_replace($delimiter.$delimiter,$delimiter,$headerTemplate);
		$headerTemplate = str_replace($delimiter.$delimiter,$delimiter,$headerTemplate);
		
		$headerTemplate = trim($headerTemplate,$delimiter);
		return $headerTemplate;
	}
	
	protected function getBasicAttributesColumnsHeader()
	{
		$columns = parent::getBasicAttributesColumnsHeader();
		$result = $this->prepareCsvRow($columns);
		return $result;
	}
	
	protected function getCustomColumnsHeader()
	{
		$columns = parent::getCustomColumnsHeader();
		$result = $this->prepareCsvRow($columns);
		return $result;
	}
	
	protected function prepareCsvRow($columns)
	{
		$result = "";
		if(!is_array($columns) || empty($columns))
		{
			return $result;
		}
		$enclosure = $this->getTextEnclosure();
		$delimiter = $this->getColumnDelimiter();
		$result = implode($enclosure.$delimiter.$enclosure, $columns);
		$result = $delimiter.$enclosure.$result.$enclosure.$delimiter;
		return $result;
	}
	
	protected function getCustomAttributesXslt()
	{
	    $customAttributesCount = count($this->getCustomColumnsHeader());
	    
	    $result = "";
	    for($i = 1;$i <= $customAttributesCount;$i++)
	    {
	         $result .= str_replace(self::INDEX_TAG,$i,self::CSV_CUSTOM_ATTRIBUTES_TEMPLATE);    
	    }
	    return $result;
	}
}
