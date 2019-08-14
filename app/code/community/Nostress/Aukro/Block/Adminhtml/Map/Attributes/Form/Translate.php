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
* @category Nostress 
* @package Nostress_Aukro
*/

class Nostress_Aukro_Block_Adminhtml_Map_Attributes_Form_Translate extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
	protected $_selected = ' selected="selected" ';
	
    public function __construct()
    {
    	$this->setTemplate('nostress_aukro/translate_array.phtml');
        $this->addColumn('from', array(
            'label' => Mage::helper('aukro')->__('From'),
            'style' => 'width:60px',
        	'class' => 'option-control',
        	'type' => 'text',
        ));
        $this->addColumn('to', array(
            'label' => Mage::helper('aukro')->__('To'),
            'style' => 'width:60px',
        	'class' => 'option-control',
        	'type' => 'text',
        ));      
        $this->_addAfter = false;  
        parent::__construct();
    }
    
    
    protected function _renderCellTemplate($columnName)
    {
        if (empty($this->_columns[$columnName])) {
            throw new Exception('Wrong column name specified.');
        }
        
        $rowIndex = $this->getData('row_index');
        $attributePath = $this->getData('custom_attribute_array_path_full');
        $isDisabled = $this->getData('isDisabled');
        $attributeName = $this->getData('attribute');
        
        if ($isDisabled == true) 
        	$disabled = 'disabled="disabled"';
        else
        	$disabled = null;
        $column     = $this->_columns[$columnName];
        $inputName  = $attributePath.'['.$rowIndex.']['.$attributeName.'][#{_id}][' . $columnName . ']';

        if ($column['renderer']) {
            return $column['renderer']->setInputName($inputName)->setColumnName($columnName)->setColumn($column)
                ->toHtml();
        }

        return '<input type="text" '.$disabled.' name="' . $inputName . '" value="#{' . $columnName . '}" ' .
            ($column['size'] ? 'size="' . $column['size'] . '"' : '') . ' class="' .
            (isset($column['class']) ? $column['class'] : 'input-text') . '"'.
            (isset($column['style']) ? ' style="'.$column['style'] . '"' : '') . '/>';
    }
    
    public function addColumn($name, $params)
    {
        $this->_columns[$name] = array(
            'label'     => empty($params['label']) ? 'Column' : $params['label'],
            'size'      => empty($params['size'])  ? false    : $params['size'],
            'style'     => empty($params['style'])  ? null    : $params['style'],
            'class'     => empty($params['class'])  ? null    : $params['class'],
            'type'     => empty($params['type'])  ? null    : $params['type'],
            'renderer'  => false,
        );
        if ((!empty($params['renderer'])) && ($params['renderer'] instanceof Mage_Core_Block_Abstract)) {
            $this->_columns[$name]['renderer'] = $params['renderer'];
        }
    }
   
    public function rowDisabled() {
    	return $this->getData('isDisabled');
    }
    
    public function getArrayRows()
    {
		if (null !== $this->_arrayRowsCache) 
    	{
    	     return $this->_arrayRowsCache;
    	}  
    	
    	$result = array();
    	
    	$values = $this->getData('values');
    	
    	if ($values != null) {
			foreach ($values as $key => $value) {
				$row = array(
					'_id' => $key,
					'from'	=>	$value['from'],
					'to'	=>	$value['to']
				);
				$result[$key] = new Varien_Object($row);
			}
    	}
    	
		$this->_arrayRowsCache = $result;
       	return $this->_arrayRowsCache;   
    	
    }
    
    
}