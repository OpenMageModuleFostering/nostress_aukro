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
* Data loader for export process
* @category Nostress 
* @package Nostress_Aukro
* 
*/

class Nostress_Aukro_Model_Data_Transformation extends Nostress_Aukro_Model_Abstract
{   
	protected $_dstData;
	
    public function init($params)
    {
        $this->setData($params);
    }
	
	public function getResult($allData = false)
	{
	    return $this->_dstData;
	}
	
   	protected function appendResult($string)
   	{
   	    $this->_dstData .= $string;   	    
   	}
   	
	public function transform($data)
	{
		$this->check($data);	
	}
	
	protected function checkSrc($data)
	{
		if(!isset($data) || empty($data))
			return false;
		return true;
	}
	
}
