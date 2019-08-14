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
class Nostress_Aukro_Model_Mysql4_Data_Loader_Product_Aukro_Collection extends Mage_Catalog_Model_Resource_Product_Collection
{
//  	public function _construct()
//    {    
//        // Note that the export_id refers to the key field in your database table.
//        $this->_init('aukro/data_loader_product_aukro_collection', 'entity_id');
//    }
    protected $_storeId;
	
	
	public function setSelect($select,$storeId)
	{		
		$this->_select = $select;
		$this->_storeId = $storeId;
//		echo $this->_select->__toString();
//		exit();
		
	}
	
    /**
     * Get SQL for get record count
     *
     * @return Varien_Db_Select
     */
    public function getSelectCountSql()
    {
        $this->_renderFilters();

        $countSelect = $this->_getClearSelect()
            ->columns('COUNT(DISTINCT cpf'.$this->_storeId.'.entity_id)')
            ->resetJoinLeft();
        return $countSelect;
    }
    
    /**
     * Retrive all ids for collection
     *
     * @param unknown_type $limit
     * @param unknown_type $offset
     * @return array
     */
    public function getAllIds($limit = null, $offset = null)
    {
        $idsSelect = $this->_getClearSelect();
        $idsSelect->columns("cpf{$this->_storeId}.". $this->getEntity()->getIdFieldName());
        $idsSelect->limit($limit, $offset);
        $idsSelect->resetJoinLeft();
		$idsSelect->distinct();
        return $this->getConnection()->fetchCol($idsSelect, $this->_bindParams);
    }
    
	/**
     * Retreive clear select
     *
     * @return Varien_Db_Select
     */
    protected function _getClearSelect()
    {
        $select = clone $this->getSelect();
        $select->reset(Zend_Db_Select::GROUP);
        $select->reset(Zend_Db_Select::ORDER);
        $select->reset(Zend_Db_Select::LIMIT_COUNT);
        $select->reset(Zend_Db_Select::LIMIT_OFFSET);
        $select->reset(Zend_Db_Select::COLUMNS);

        return $select;
    }
}