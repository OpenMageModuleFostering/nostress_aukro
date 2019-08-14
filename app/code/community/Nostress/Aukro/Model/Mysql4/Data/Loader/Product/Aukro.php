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
* Resource  of data loader for export process
* @category Nostress
* @package Nostress_Aukro
*
*/

class Nostress_Aukro_Model_Mysql4_Data_Loader_Product_Aukro extends Nostress_Aukro_Model_Mysql4_Data_Loader_Product
{
	const LIMIT = 'limit';
    const PAGE_NUM = 'page';
    const SORT = 'sort';
    const SORT_DIR = 'dir';
    const FILTER = 'filter';
	const DEF_PAGE_LIMIT = 30;
    const DEF_NON_ORDER = 'product_to_display_id';
	
    const NCM = 'ncm'; //Nostress category mapping
    
    protected $_priceFieldMapping = array(	"price_final_include_tax" => "min_price",
    										"price_final_exclude_tax" => "min_price",
    										"price_original_include_tax" => "price",
    										"price_original_exclude_tax" => "price",
    										);
    
 	protected function defineColumns()
    {
        parent::defineColumns();
        $this->_columns[$this->getProductFlatTable(true)]["visibility"] = "visibility";
        
         $this->_columns[self::NCM] = array("category_id" => "category",
                                            "aukrocategory_id" => "aukrocategory",
                                            "aukrocategory_connection_name" => "connection_name",
                                            "aukrocategory_display_duration" => "display_duration",
         									"aukrocategory_display_auto" => "auto_display",
									        "aukrocategory_shipping_payment" => "shipping_payment",
									        "aukrocategory_attributes" => "attributes",
                                            );
    }
    public function joinTaxonomy()
	{
		return $this;
	}
	
	protected function joinCategoryPath()
	{
		$select = $this->getSelect();
		$mainTableAlias = $this->getCategoryFlatTable(true);
		$joinTableAlias = self::NCCP;
		$joinTable = $this->getTable('aukro/cache_categorypath');
		
		$select->joinLeft(
                    array($joinTableAlias => $joinTable),
                    "{$joinTableAlias}.category_id ={$mainTableAlias}.entity_id AND {$joinTableAlias}.store_id ={$this->getStoreId()}",
                    $this->getColumns($joinTableAlias)
                    );
		return $this;
	}
	
	public function joinCategoryMapping()
	{
		$mainTableAlias = self::CCPI;
		$joinTableAlias = self::NCM;
		$joinTable =  $this->getTable('aukro/mapping_category');
		$condition = "{$joinTableAlias}.category = {$mainTableAlias}.category_id ";
		
	    $this->joinTable($mainTableAlias,$joinTableAlias,$joinTable,false,$condition);
	    return $this;
	}
	
	public function getSelect($params = null)
	{
		$select = parent::getSelect();
		if(!isset($params))
			return $select;
		
		$select->reset(Zend_Db_Select::ORDER);
		$select->reset(Zend_Db_Select::LIMIT_COUNT);
		$select->reset(Zend_Db_Select::LIMIT_OFFSET);
		
		$page = $this->getArrayField(self::PAGE_NUM,$params,0);
		$limit = $this->getArrayField(self::LIMIT,$params,self::DEF_PAGE_LIMIT);
		$select->limit($limit,((int)$page-1)*(int)$limit);

		$order = $this->getArrayField(self::SORT,$params,false);
		if($order && $order != self::DEF_NON_ORDER)
		{
			$dir = $this->getArrayField(self::SORT_DIR,$params,false);
			$select->order($order." ".$dir);
		}
		
		$filter = $this->getArrayField(self::FILTER,$params,false);
		if($filter)
		{
			foreach($filter as $columnName => $condition)
			{
				$sqlCond = "";
				foreach($condition as $cond => $citem)
				{
					if($cond == "currency")
						continue;
						
					if(!empty($sqlCond))
						$sqlCond .= " OR ";

					if(!is_string($citem) && !is_numeric($citem))
						$citem = $citem->__toString();
					$cond = $this->translateCondition($cond);
					$sqlCond .= $this->getColumnName($columnName)." ".$cond." ".$citem." ";
				}
				$select->where($sqlCond);
			}
		}
		return $select;
		
	}
	
	protected function translateCondition($condition)
	{
		$newCond = $condition;
		switch($condition)
		{
			case "from":
				$newCond = ">=";
				break;
			case "to":
				$newCond = "<=";
				break;
			case "eq":
				$newCond = "=";
				break;
			default:
				break;
		}
		return $newCond;
	}
	
	protected function getColumnName($alias)
	{
		if(!isset($this->_columns))
    		$this->defineColumns();
    	
    	$columnName = "";
        foreach ($this->_columns as $tableAlias => $tableColumns)
        {
        	
        	if(isset($tableColumns[$alias]))
        	{
        		$columnAlias = $tableColumns[$alias];
        		if(!empty($columnAlias) && (ctype_upper($columnAlias[0]) || $columnAlias[0] == "("))
        		{
        			$columnName = $columnAlias;
        			break;
        		}
        		if(in_array($columnAlias,$this->_priceFieldMapping))
        			$columnAlias = $this->_priceFieldMapping[$columnAlias];
        		$columnName = $tableAlias.".".$columnAlias;
        		break;
        	}
        }
        if(empty($columnName))
        	$columnName = $this->getProductFlatTable(true).".".$alias;
        return $columnName;
	}
	
	public function addProductIdsCondition()
	{
	    if(($profileIdsArray = $this->getCondition(Nostress_Aukro_Helper_Data_Loader::CONDITION_PRODUCT_IDS,0)) != 0)
	    {
	    	$profileIds = "'".implode("','",$profileIdsArray)."'";
	        $select = $this->getSelect();
	        //products ids condition
		    $select->where($this->getProductFlatTable(true).".entity_id IN ({$profileIds})");
	    }
		
		return $this;
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
}
