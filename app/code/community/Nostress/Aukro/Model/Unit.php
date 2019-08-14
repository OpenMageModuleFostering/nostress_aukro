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
 * Unit for product export process
 * 
 * @category Nostress 
 * @package Nostress_Aukro
 * 
 */

class Nostress_Aukro_Model_Unit extends Nostress_Aukro_Model_Abstract 
{    
	const MB_SIZE = 1024;
	const TIME = 'time';
	const MEMORY = 'memory';
	const PRODUCTS = 'products';
	const TIME_DECIMAL = 2;
	
	protected $_startTime;
	protected $_totalTime;
	protected $_productCounter;
	
	
    protected function init()
    {
        $this->initStartTime();
    	$this->resetProductCounter();
        return $this;    
    }
    
    public function getProcessInfo($format = true)
    {
    	$info = array();
    	$info[self::TIME] = $this->getTotalTime($format);
    	$info[self::MEMORY] = $this->getTotalMemory($format);
    	$info[self::PRODUCTS] = $this->getProductCounter();
    	
    	if($format)
    	{
    	    $info = $this->helper()->__("Products: %s Time: %s Memory: %s ",$info[self::PRODUCTS],$info[self::TIME],$info[self::MEMORY]);
    	}
    	
    	return $info;
    }
    
    protected function initStartTime()
    {
   		$this->_startTime = $this->helper()->getProcessorTime();
    }
    
    protected function stopTime()
    {
    	$endTime =  $this->helper()->getProcessorTime();
    	$this->_totalTime = $endTime - $this->_startTime;
    }
    
    protected function getTotalTime($format = true)
    { 
    	$time = $this->_totalTime;
    	$time = round($time,self::TIME_DECIMAL);
    	if($format)
    		$time .= " ".$this->helper()->__("s");  
    	return $time;
    }
    
    protected function getTotalMemory($format = true)
    {
    	//$memory = memory_get_usage(true);
    	$memory = memory_get_peak_usage(1);
    	$memory = ($memory/self::MB_SIZE)/self::MB_SIZE;
    	if($format)
    		$memory .= " ".$this->helper()->__("MB");  
    	return $memory;
    }
    
    protected function incrementProductCounter($number)
    {
    	$this->_productCounter += $number;
    }
    
    protected function resetProductCounter()
    {
    	$this->_productCounter = 0;
    }
    
    protected function getProductCounter()
    {
    	return $this->_productCounter;
    }
}