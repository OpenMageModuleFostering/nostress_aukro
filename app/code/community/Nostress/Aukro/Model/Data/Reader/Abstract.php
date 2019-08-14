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
* 
* @category Nostress 
* @package Nostress_Aukro
* 
*/

class Nostress_Aukro_Model_Data_Reader_Abstract
{   
	protected $_recordField = array();
	protected $_handle;
		
	public function openFile($filename,$params = array())
	{
		$this->initParams($params);
		if (($this->_handle = fopen($filename, "r")) !== FALSE)
		{
			return true;
		}
		else
		{
			throw new Exception("Can't open file {$filename} for reading.");		
			return false;
		}
	}
	
	protected function initParams($params)
	{
		
	}
	/**
	 * Returns one record from file as array 
	 */
	public function getRecord()
	{				
		if(isset($this->_handle))
			return fgets($this->_handle);
		else 
			return false;
	}
	
	public function closeFile()
	{
		fclose($this->_handle);
	}	
}
