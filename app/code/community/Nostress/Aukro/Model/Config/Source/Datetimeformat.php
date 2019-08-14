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
* Exports model - source for dropdown menu "Product group size"
* 
* @category Nostress 
* @package Nostress_Aukro
* 
*/

class Nostress_Aukro_Model_Config_Source_Datetimeformat extends Nostress_Aukro_Model_Abstract
{
    const STANTDARD = "standard";
    const ISO8601 = "iso8601";
    const ATOM = "atom";
	const SLASH = "slash";
    const COOKIE = "cookie";
	const RFC822 = "rfc822";
	const RSS = "rss";
	
    const STANTDARD_DATETIME = "Y-m-d H:i:s";
    const STANTDARD_DATE = "Y-m-d";
    const STANTDARD_TIME = "H:i:s";
    
    const STANTDARD_DATETIME_SQL = "%Y-%m-%d %H:%i:%s";
    const STANTDARD_DATE_SQL = "%Y-%m-%d";
    const STANTDARD_TIME_SQL = "%H:%i:%s";    
    
    protected $_formats = array();
    
    public function toOptionArray()
    {
        return array(
            array('value'=> self::STANTDARD, 'label'=>Mage::helper('aukro')->__('Standard (Y-m-d H:i:s)')),
            array('value'=>self::ISO8601, 'label'=>Mage::helper('aukro')->__('ISO  8601 (Y-m-dTH:i:sO)')),            
        	array('value'=>self::SLASH, 'label'=>Mage::helper('aukro')->__('Slash delimiter (Y/m/d H:M)')),
        	array('value'=>self::ATOM, 'label'=>Mage::helper('aukro')->__('ATOM,W3C (Y-m-d\TH:i:sP)')),
        	array('value'=>self::COOKIE, 'label'=>Mage::helper('aukro')->__('COOKIE (l, d-M-y H:i:s T)')),
        	array('value'=>self::RFC822, 'label'=>Mage::helper('aukro')->__('RFC822 (D, d M Y H:i:s O)')),       	
        	array('value'=>self::RSS, 'label'=>Mage::helper('aukro')->__('RSS (D, d M Y H:i:s O)')),
        );
    }
    
    protected function prepareFormats()
    {   	    	
    	$this->_formats = array(
	    	self::STANTDARD	=> array(
	    		self::PHP => array(self::DATE_TIME => self::STANTDARD_DATETIME,self::DATE => self::STANTDARD_DATE, self::TIME => self::STANTDARD_TIME),
	    		self::SQL => array(self::DATE_TIME => self::STANTDARD_DATETIME_SQL,self::DATE => self::STANTDARD_DATE_SQL, self::TIME => self::STANTDARD_TIME_SQL),
	    	),				
	    	self::ISO8601	=> array(
	    		self::PHP => array(self::DATE_TIME => DateTime::ISO8601,self::DATE => self::STANTDARD_DATE, self::TIME => "H:i:sO"),
	    		self::SQL => array(self::DATE_TIME => "%Y-%m-%dT%T".$this->getTimeShift(),self::DATE => self::STANTDARD_DATE_SQL, self::TIME => self::STANTDARD_TIME_SQL.$this->getTimeShift()),
	    	),
	    	self::SLASH	=> array(
	    		self::PHP => array(self::DATE_TIME => "Y/m/d H:i",self::DATE => "Y/m/d", self::TIME => "H:i"),
	    		self::SQL => array(self::DATE_TIME => "%Y/%m/%d %H:%i",self::DATE => "%Y/%m/%d", self::TIME => "%H:%i"),
    		),
    		self::ATOM	=> array(
	    		self::PHP => array(self::DATE_TIME => DateTime::ATOM,self::DATE => self::STANTDARD_DATE, self::TIME => "H:i:sP"),
	    		self::SQL => array(self::DATE_TIME => "%Y-%m-%dT%T".$this->getTimeShift("P"),self::DATE => self::STANTDARD_DATE_SQL, self::TIME => self::STANTDARD_TIME_SQL.$this->getTimeShift("P")),
	    	),
	    	self::COOKIE	=> array(
	    		self::PHP => array(self::DATE_TIME => DateTime::COOKIE,self::DATE => "l, d-M-y", self::TIME => "H:i:s T"),
	    		self::SQL => array(self::DATE_TIME => "%W, %d-%b-%y %T ".$this->getTimeShift("T"),self::DATE => "%W, %d-%M-%y", self::TIME => self::STANTDARD_TIME_SQL." ".$this->getTimeShift("T")),
	    	),	
	    	self::RFC822	=> array(
	    		self::PHP => array(self::DATE_TIME => DateTime::RFC822,self::DATE => "D, d M y", self::TIME => "H:i:s O"),
	    		self::SQL => array(self::DATE_TIME => "%a, %d %b %y %T ".$this->getTimeShift(),self::DATE => "%a, %d %M %y", self::TIME => self::STANTDARD_TIME_SQL." ".$this->getTimeShift()),
	    	),
	    	self::RSS	=> array(
	    		self::PHP => array(self::DATE_TIME => DateTime::RSS,self::DATE => "D, d M Y", self::TIME => "H:i:s O"),
	    		self::SQL => array(self::DATE_TIME => "%a, %d %b %Y %T ".$this->getTimeShift(),self::DATE => "%a, %d %M %Y", self::TIME => self::STANTDARD_TIME_SQL." ".$this->getTimeShift()),
	    	),	
    	);
    }

    protected function getTimeShift($format = "O") 
    {
    	return $this->convertTimestamp(Mage::getModel('core/date')->timestamp(time()),$format);	
    }
    
    protected function getPhpFormat($format,$type)
    {
    	return $this->getFormat($format,$type);
    }
    
	public function getSqlFormat($format,$type)
    {
    	return $this->getFormat($format,$type,self::SQL);
    }
    
    protected function getFormat($format,$type,$platform = self::PHP)
    {
    	if(empty($this->_formats))
    		$this->prepareFormats();
    	
    	$formatBase = $this->_formats[self::STANTDARD][$platform];
    	if(isset($this->_formats[$format][$platform]))
    		$formatBase = $this->_formats[$format][$platform];

    	$result = $formatBase[self::DATE_TIME];
    	if(isset($formatBase[$type]))
    		$result = $formatBase[$type];
		return $result;
    }    
    
	protected function convertTimestamp($timestamp,$format)
	{
		$time = date(self::STANTDARD_DATETIME, $timestamp);
		if($format == self::STANTDARD_DATETIME)
			return $time;
		
		$timezone = $this->getTimezone();
		$datetime = new DateTime($time,$timezone);
		return $datetime->format($format);		
	}
    
	protected function getTimezone($storeId = Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID)
	{
		$timezone = Mage::app()->getStore()->getConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE);
		return new DateTimeZone($timezone);
	}	
	
	public function formatDatetime($timestamp,$format=self::STANDARD)
	{
		$phpFormat = $this->getPhpFormat($format,self::DATE_TIME);
		return $this->convertTimestamp($timestamp,$phpFormat);
	}
    
	public function formatDate($timestamp,$format=self::STANDARD)
	{
		$phpFormat = $this->getPhpFormat($format,self::DATE);
		return $this->convertTimestamp($timestamp,$phpFormat);
	}
	
	public function formatTime($timestamp,$format=self::STANDARD)
	{
		$phpFormat = $this->getPhpFormat($format,self::TIME);
		return $this->convertTimestamp($timestamp,$phpFormat);
	}	
}
?>