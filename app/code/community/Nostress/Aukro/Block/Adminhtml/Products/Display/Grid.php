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
 
class Nostress_Aukro_Block_Adminhtml_Products_Display_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
	protected $_helper;
	const DEF_STORE_ID = 1;
	const ATTRIBUTES = 'attributes';
	
	public function __construct()
  	{
    	parent::__construct();
      	$this->setId('aukroDisplayProducts');
      	$this->setDefaultSort('id');
      	$this->setDefaultDir('ASC');
      	$this->setStoreSwitcherVisibility();
      	$this->setSaveParametersInSession(true);
  	}
  
    protected function _addColumnFilterToCollection($column)
    {
     	return $this;
    }
    
 	protected function getFilterData($data,$storeId)
    {
    	$conditions = array();
    	
    	if(!isset($data))
    		return $conditions;
    		
    	$data = Mage::helper('adminhtml')->prepareFilterString($data);
    	 foreach ($this->getColumns() as $columnId => $column)
    	 {
            if (isset($data[$columnId])
                && (!empty($data[$columnId]) || strlen($data[$columnId]) > 0)
                && $column->getFilter()
            ) {

            	$column->getFilter()->setValue($data[$columnId]);
            	$field = ( $column->getFilterIndex() ) ? $column->getFilterIndex() : $column->getIndex();
            	if($columnId == "price")
            		$column->setCurrencyCode($this->myHelper()->getStoreCurrency($storeId));
                $cond = $column->getFilter()->getCondition();
                
                if ($field && isset($cond))
                	$conditions[$field] = $cond;
            }
        }
        return $conditions;
    }
    
  protected function getGridParams()
  {
  	$params = array();
  	$params[$this->getVarNameLimit()] = $this->getParam($this->getVarNameLimit(), $this->_defaultLimit);
  	$params[$this->getVarNamePage()] = $this->getParam($this->getVarNamePage(), $this->_defaultPage);
  	$params[$this->getVarNameSort()] = $this->getParam($this->getVarNameSort(), $this->_defaultSort);
  	$params[$this->getVarNameDir()] = $this->getParam($this->getVarNameDir(), $this->_defaultDir);
  	$params["store_id"] = (int) $this->getRequest()->getParam('store', self::DEF_STORE_ID);
  	$params[$this->getVarNameFilter()] = $this->getFilterData($this->getParam($this->getVarNameFilter(), null),$params["store_id"]);
  	$params[self::ATTRIBUTES] = $this->getColumnCodes();
  	
	return $params;
  }
  
  protected function getColumnCodes()
  {
  	$columns = $this->getColumns();
  	$codes = array( 'aukro_product_id');
  	foreach($columns as $code => $column)
  	{
  		if($code == "massaction" || $code == "action")
  			continue;
  		
  		$codes[] = $code;
  	}
  	return $codes;
  }
  
  protected function _loadCollection() {
      
      $collection = Mage::getModel('aukro/data_loader_product_aukro')->getGridCollection($this->getGridParams());
      $collection->getSelect()->where( 'aukro_product_id IS NULL');
      $this->setCollection($collection);
      $collection->load();
  }
  
  protected function _prepareCollection()
  {
    $this->_loadCollection();
    
    parent::_prepareCollection();
    return $this;
  }
  
  
  protected function _prepareColumns()
  {
        $this->addColumn('id',
            array(
                'header'=> Mage::helper('catalog')->__('ID'),
                'width' => '50px',
                'type'  => 'number',
                'index' => 'id',
        ));
        $this->addColumn('name',
            array(
                'header'=> Mage::helper('catalog')->__('Name'),
                'index' => 'name',
        ));
        
        $this->addColumn('type',
            array(
                'header'=> Mage::helper('catalog')->__('Type'),
                'width' => '60px',
                'index' => 'type',
                'type'  => 'options',
                'options' => Mage::getSingleton('catalog/product_type')->getOptionArray(),
        ));

        $this->addColumn('sku',
            array(
                'header'=> Mage::helper('catalog')->__('SKU'),
                'width' => '80px',
                'index' => 'sku',
        ));

        $this->addColumn('price_final_include_tax',
            array(
                'header'=> Mage::helper('catalog')->__('Price final include tax'),
                'type'  => 'number',
                'index' => 'price_final_include_tax',
        ));

        if (Mage::helper('catalog')->isModuleEnabled('Mage_CatalogInventory')) {
            $this->addColumn('qty',
                array(
                    'header'=> Mage::helper('catalog')->__('Qty'),
                    'width' => '100px',
                    'type'  => 'number',
                    'index' => 'qty',
            ));
        }

//         $this->addColumn('visibility',
//             array(
//                 'header'=> Mage::helper('catalog')->__('Visibility'),
//                 'width' => '70px',
//                 'index' => 'visibility',
//                 'type'  => 'options',
//                 'options' => Mage::getModel('catalog/product_visibility')->getOptionArray(),
//         ));

        $this->addColumn('category_name',
            array(
                'header'=> Mage::helper('aukro')->__('Category name'),
                'width' => '70px',
                'index' => 'category_name',
                'type'  => 'text',
        ));
        $this->addColumn('aukrocategory_id',
            array(
                'header'=> Mage::helper('aukro')->__('Aukrocategory id'),
                'width' => '70px',
                'index' => 'aukrocategory_id',
                'type'  => 'text',
        ));
        
//         $this->addColumn('aukro_product_id',
//                 array(
//                         'header'=> Mage::helper('aukro')->__('Aukro ID'),
//                         'width' => '70px',
//                         'index' => 'aukro_product_id',
//                         'type'  => 'text',
//                 ));
        
        $this->addColumn('stock_status',
            array(
                'header'=> Mage::helper('aukro')->__('Status'),
                'type'  => 'text',
                'index' => 'stock_status',
                'renderer' => 'aukro/adminhtml_products_display_renderer_status',
                'filter'    => false,
                'sortable'  => false,
                'width' => '300px',
        ));
                
        $this->addColumn('action',
            array(
                'header'    => Mage::helper('catalog')->__('Action'),
                'width'     => '50px',
                'type'      => 'action',
                'getter'     => 'getId',
                'actions'   => array(
                        array(
                                'caption' => Mage::helper('catalog')->__('Edit'),
                                'url'     => array(
                                        'base'=>'adminhtml/catalog_product/edit',
                                        'params'=>array('store'=>$this->getRequest()->getParam('store'))
                                ),
                                'field'   => 'id'
                        )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
        ));
        
    	return parent::_prepareColumns();
  }
  
	protected function myHelper()
	{
		if (!isset($this->_helper))
			$this->_helper = Mage::helper('aukro');
		return $this->_helper;
	}
  
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('productIds');
       
        $this->getMassactionBlock()->addItem('aukro_upload', array(
             'label'=> Mage::helper('aukro')->__('Expose'),
             'url'  => $this->getUrl('*/*/massAukroUpload')
        ));
        
        $this->getMassactionBlock()->addItem('aukro_upload_test', array(
            'label'=> Mage::helper('aukro')->__('Verify Exposure'),
            'url'  => $this->getUrl('*/*/massAukroUpload', array( 'dryrun'=>true))
        ));

        return $this;
    }
    
    public function getRowUrl($row)
    {
        return $this->getUrl('adminhtml/catalog_product/edit', array(
                'store'=>$this->getRequest()->getParam('store'),
                'id'=>$row->getId())
        );
    }

    
    /**
     * Render grid
     *
     * @return string
     */
    public function getGridHtml()
    {
        return $this->getChildHtml('grid');
    }
}