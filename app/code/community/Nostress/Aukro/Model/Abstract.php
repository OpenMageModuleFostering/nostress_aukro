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

class Nostress_Aukro_Model_Abstract extends Mage_Core_Model_Abstract
{
    const ATTRIBUTE = 'attribute';
    const ATTRIBUTES = 'attributes';
    const CONSTANT = 'constant';
    const SUFFIX = 'suffix';
    const PREFIX = 'prefix';
    const PARENT = 'parent';
    const CHILD = 'child';
    const PARENT_ATTRIBUTE_VALUE = 'eppav';
    const POST_PROCESS = 'postproc';
    const LABEL = 'label';
    const CODE = 'code';
    const TAG = 'tag';
    const VALUE = 'value';
    const LIMIT = 'limit';
    const TYPE = "type";
    const TYPES = "types";
    const XML = 'xml';
    const CSV = 'csv';
    const TXT = 'txt';
    const HTML = 'html';
    const FEED = 'feed';
    const PRODUCT = 'product';
    const GENERAL = 'general';
    const PARAM = 'param';
    const LOCALE = 'locale';
    const LANGUAGE = 'language';
    const COUNTRY = 'country';
    const COUNTRY_CODE = 'country_code';
    const TIME = 'time';
    const DATE = 'date';
    const DATE_TIME = 'date_time';
    const TEXT_ENCLOSURE = 'text_enclosure';
	const COLUMN_DELIMITER = 'column_delimiter';
    const NEWLINE = 'new_line';
	const CUSTOM_ATTRIBUTE = 'custom_attribute';
	const CURRENCY = 'currency';
	const PATH = 'path';
	const DELETE = 'delete';
	const CATEGORY_PATH = 'category_path';
	const CDATA_SECTION_ELEMENTS = "cdata_section_elements";
	const CUSTOM_COLUMNS_HEADER = "custom_columns_header";
	const COLUMNS_HEADER = "columns_header";
	const BASIC_ATTRIBUTES_COLUMNS_HEADER = "basic_attributes_columns_header";
	const DISABLED = 'disabled';
	const CSV_DISABLED = 'csv_disabled';
	const CDATA = 'cdata';
	const COMMON = 'common';
	const CUSTOM_PARAMS = 'custom_params';
	
	const POSTPROC_DELIMITER = ",";
	
    const STATUS_RUNNING = "RUNNING";
    const STATUS_FINISHED = "FINISHED";
    const STATUS_INTERRUPTED = "INTERRUPTED";
    const STATUS_ERROR = "ERROR";
    
    const TYPE_HTML = 'html';
    const TYPE_TEXT = 'txt';
	const TYPE_CSV = 'csv';
	const TYPE_XML = 'xml';
	
	const FILE_PATH = "path";
	const FILE_NAME = "filename";
	const FILE_TYPE = "type";
    
    const MAGENTO_ATTRIBUTE = 'magento';
    
    protected $_helper;
    
    protected function helper()
    {
        if(!isset($this->_helper))
    	    $this->_helper = Mage::helper('aukro');
    	return $this->_helper;
    }
    
    protected function log($message)
    {
        $this->helper()->log($message);
    }

    protected function logAndException($message,$param = null)
    {
        $translatedMessage = $this->helper()->__($message,$param);
        $this->helper()->log($translatedMessage);
        Mage::throwException($translatedMessage);
    }
    
    protected function getArrayField($index,$array, $default = null)
    {
        if(!is_array($array))
        {
            return $default;
        }
    	if(array_key_exists($index,$array))
    	{
    		return $array[$index];
    	}
    	else
    		return $default;
    }
    
	protected function isDebugMode()
	{
		return $this->helper()->isDebugMode();
	}
	
	protected function _getSession() {
	    return Mage::getSingleton('adminhtml/session');
	}
}