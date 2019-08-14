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
* Array data transformation for export process
* @category Nostress
* @package Nostress_Aukro
*
*/

class Nostress_Aukro_Model_Data_Transformation_Xml_Array extends Nostress_Aukro_Model_Data_Transformation_Xml
{
	public function getResult($allData = false)
	{
		$data = parent::getResult($allData);
		$xmlNode = $this->initData($data);
		$array = $this->helper()->XMLnodeToArray($xmlNode);
		$items = $this->getItems($array);
//		var_dump($items);
//		exit();
		
		return $items;
	}
	
	protected  function initData($data)
	{
		return simplexml_load_string($data);
	}
	
	protected function getItems($data,$mergeParentChild = false)
	{
		$items = array();
		if(isset($data["item"]))
		{
			$items = $data["item"];
		}
		else
			$this->logAndException("Wrong data for upload to Aukro!");
		return $items;
	}
	
	protected function preprocessAttributes()
	{
	    $attributes = $this->getAttributes();
	    //$attributes = $this->getArrayField(self::ATTRIBUTE,$attributes,array());
	    if(empty($attributes))
	        $this->logAndException("Missing feed attributes configuration.");
	    $this->setAttributes($attributes);
	}
	
	protected function getHeader()
	{
	    return "<?xml version=\"1.0\" encoding=\"{$this->getEncoding()}\" ?><".self::MAIN_TAG.">";
	}
	
   	protected function getIsChildLabel($isChild)
   	{
        return self::ITEM_TAG;
   	}
   	
   	protected function saveItemData()
   	{
   	    if(empty($this->_itemData))
   	        return;
   	    $this->appendResult($this->_itemData);
   	    $this->_itemData = "";
   	}
}
