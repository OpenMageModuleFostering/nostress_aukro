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

class Nostress_Aukro_Model_Data_Loader extends Nostress_Aukro_Model_Abstract
{   
    protected $adapter;
    protected $_flatCatalogs = array('product','category');
    
    public function _construct()
    {    
        // Note that the export_id refers to the key field in your database table.
        $this->_init('aukro/data_loader', 'entity_id');
    }	
    
    public function init($params)
    {
        $this->setData($params);
        $this->initAdapter();
    }
    
    public function loadBatch()
    {
    	return $this->adapter->loadBatch();
    }
    
    protected function initAdapter()
    {
        $this->adapter = $this->getResource();
        $this->adapter->setStoreId($this->getStoreId());
        $this->adapter->setExportId($this->getExportId());
        $this->adapter->setAttributes($this->getAttributes());
        $this->adapter->setConditions($this->getConditions());
        $this->adapter->setTaxonomyCode($this->getTaxonomyCode());
        $this->adapter->setBatchSize($this->getBatchSize());
        
        $this->adapter->init();
    } 
    
    protected function isFlatEnabled()
    {
        $fc = $this->getFlatCatalogs();
        foreach ($fc as $type) 
        {
        	$enabled = $this->helper()->isEnabledFlat($this->getStoreId(),$type);
        	if(!$enabled)
        	{
        	    throw new Exception($this->helper()->__(ucfirst($type)." flat catalog is disabled"));
        	}
        }        
        return true;
    }
    
    protected function getFlatCatalogs()
    {
        return $this->_flatCatalogs;
    }
}
