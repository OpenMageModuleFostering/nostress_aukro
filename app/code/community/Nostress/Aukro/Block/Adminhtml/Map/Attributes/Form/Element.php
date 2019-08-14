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

class Nostress_Aukro_Block_Adminhtml_Map_Attributes_Form_Element extends Varien_Data_Form_Element_Abstract
{
    const ATTRIBUTE_ARRAY_PATH = "attributes[attribute]";
    const ATTRIBUTE_ARRAY_PATH_FULL = "attributes[attribute]";
    const CUSTOM_ATTRIBUTE_ARRAY_PATH = "custom_attributes[attribute]";
    const CUSTOM_ATTRIBUTE_ARRAY_PATH_FULL = "custom_attributes[attribute]";
    const DESCRIPTION_OPTIONS_DELIMITER = "\n\t* ";
    const DESCRIPTION_RECORD_DELIMITER = "\n";
    const INFO_IMAGE_PATH = "adminhtml/default/default/images/note_msg_icon.gif";
    
    protected $_rowIndex = 0;

    public function getElementHtml() {
		$elementAttributeHtml = '';
		$form = $this->getForm();
		
		$addButton = $form->getParent()->getLayout()->createBlock('adminhtml/widget_button')
			->setData(array(
				'label' => Mage::helper('aukro')->__('Add new attribute'),
				'onclick' => "addAttribute()",
				'class' => 'add addAttributeButton'
			));
		$removeButton = $form->getParent()->getLayout()->createBlock('adminhtml/widget_button')
			->setData(array(
				'label' => Mage::helper('aukro')->__('Remove last added attribute'),
				'onclick' => "removeCustomAttribute()",
				'class' => 'delete',
				'style' => 'margin-right: 10px;'
			));
		
		$addButtonHtml = "";
		$actionColumnHtml = "";
		if($this->getAllowCustomAttributes() == "1")
		{
			$addButtonHtml =  $addButton->toHtml();
			$actionColumnHtml = '<th>'.Mage::helper('aukro')->__('Action').'</th>';
		}
		
		$html = '
		<div class="grid" ><div class="hor-scroll">
			<script type="text/javascript">
				function showWarning(selectId,resize) {
					var selectedIndex = document.getElementById(selectId).selectedIndex;
					if (document.getElementById(selectId).options[selectedIndex].className == "warning") {
						document.getElementById(selectId+"_warning").style.display="block";
						document.getElementById(selectId).style.color="red";
						if (resize) {
							document.getElementById(selectId).style.width="90%";
						}
					} else {
						document.getElementById(selectId+"_warning").style.display="none";
						document.getElementById(selectId).style.color="black";
						if (resize) {
							document.getElementById(selectId).style.width="100%";
						}
					}
				}
				function saveAttributeMapping() {
					document.forms["attribute_mapping"].submit();
				}
				
			</script>
			<form id="attribute_mapping" action="'.$this->getSubmitUrl().'" method="post">
			<input name="form_key" type="hidden" value="'.Mage::getSingleton('core/session')->getFormKey().'" />
			<table class="data" cellspacing="0" id="tempshutdown_container">
					<thead>
					<tr class="headings">
						<th>'.Mage::helper('aukro')->__('#').'</th>
						<th>'.Mage::helper('aukro')->__('Attribute').'</th>
						<th>'.Mage::helper('aukro')->__('Prefix').'</th>
						<th>'.Mage::helper('aukro')->__('Constant value').'</th>
						<th>'.Mage::helper('aukro')->__('Magento attribute').'</th>
						<th>'.Mage::helper('aukro')->__('Translate attribute').'</th>
						<th>'.Mage::helper('aukro')->__('Suffix').'</th>
						<th>'.Mage::helper('aukro')->__('Export parent product<br> attribute value').'</th>
						<th>'.Mage::helper('aukro')->__('Chars limit').'</th>
						<th>'.Mage::helper('aukro')->__('Post-process value').'</th>
						'.$actionColumnHtml.'
					</tr>
					</thead>
					<tbody>
					'.$this->getRows().'
					</tbody>
			</table>
		</div></div>
		<div class="addAttributeButton">
			'./*$removeButton->toHtml().*/'
			'.$addButtonHtml.'
		</div></form>';
		
		return $html;
	}
	
	public function getRows() {
		$html = "";
		$values = $this->getValues();
		$attributes = $values["attribute"];
		$custom = 0;
		
		//echo "<pre>".print_r($this->getValues(), 1)."</pre>";
		
		$index = 1;
		if (!empty($attributes)) {
			foreach ($attributes as $key => $attribute) {
				$custom = 0;
				if ($attribute["code"] == "custom_attribute") {
					$custom = 1;
				}
				$html .= $this->_getRowTemplateHtml($attribute, $index, $custom);
				$index++;
			}
		}
		return $html;
	}
	
	protected function _getRowTemplateHtml($attribute, $key, $custom = 0) {
		$index = $this->_rowIndex;
		$this->_rowIndex++;
		$disabled = "";
		$disabledBool = 0;
		$disabledHtml = "";
		
		$removeElementButton = $this->getForm()->getParent()->getLayout()->createBlock('adminhtml/widget_button')
			->setData(array(
				'label' => Mage::helper('aukro')->__('Delete'),
				'onclick' => "removeCustomAttributeElement(".$key.")",
				'class' => 'delete'
			));
		
		if ($this->attribute($attribute, "type") == "disabled") {
			$disabled = "disabled";
			$disabledBool = 1;
			$disabledHtml = " disabled=\"disabled\"";
		}
		
		$attributesValues = Mage::helper('aukro/data_feed')->getAttributeOptions($this->getStoreId());
		$attributesValue = $this->attribute($attribute, "magento");
		$attributesStyle = 'width:100%;';
		if ($attributesValues) {
            foreach ($attributesValues as $option) {
                if (is_array($option) && !is_array($option['value'])) {
                    if (isset($option['red']) && $option['red'] == 1 && $option['value'] == $attributesValue) {
                    	$attributesStyle = 'width:90%;color:red';
                    }
                }
            }
        }
		
		$attributesConfig = array(
			"id" => "aukro_magentoattribute",
			"name" => (($custom == 1) ? self::CUSTOM_ATTRIBUTE_ARRAY_PATH : self::ATTRIBUTE_ARRAY_PATH)."[".$index."][magento".(($disabledBool == 1) ? "_disabled" : "")."]",
			"style" => $attributesStyle,
			"values" => $attributesValues,
			"value" => $attributesValue,
			"html_id" => '_'.$index,
			'onchange' => 'showWarning(\'TO_BE_REPLACED_WITH_ID\',true);',
			"disabled" => $disabled
		);
		
		$attributesSelect = new Nostress_Aukro_Block_Adminhtml_Map_Attributes_Form_Attributeselect($attributesConfig);
		$attributesSelect->setForm($this->getForm());
		
		$translateGrid = $this->getForm()->getParent()->getLayout()->createBlock('aukro/adminhtml_map_attributes_form_translate');
		if ($custom == 1)
			$attributeArrayPathFull = self::CUSTOM_ATTRIBUTE_ARRAY_PATH_FULL;
		else
			$attributeArrayPathFull = self::ATTRIBUTE_ARRAY_PATH_FULL;
		$translateGrid->setData(array(
			'values' => $attribute['translate'],
			'row_index' => $index,
			'custom_attribute_array_path_full' => self::ATTRIBUTE_ARRAY_PATH_FULL,
			'isDisabled' => $disabledBool,
			'attribute' => 'translate'
			
		));
		$translateGrid->setElement($this->getForm());
		$translateGridHtml = $translateGrid->toHtml();
		
		
		$attributesConfig["name"] = (($custom == 1) ? self::CUSTOM_ATTRIBUTE_ARRAY_PATH : self::ATTRIBUTE_ARRAY_PATH)."[".$index."][magento]";
		$attributesConfig["disabled"] = "";
		$attributesConfig["style"] = "display: none;";
		
		$attributesHiddenSelect = new Varien_Data_Form_Element_Select($attributesConfig);
		$attributesHiddenSelect->setForm($this->getForm());
		
		$parentConfig = array(
			"id" => "aukro_parentconfig",
			"name" => (($custom == 1) ? self::CUSTOM_ATTRIBUTE_ARRAY_PATH : self::ATTRIBUTE_ARRAY_PATH)."[".$index."][eppav".(($disabledBool == 1) ? "_disabled" : "")."]",
			"style" => "width: 100%;",
			"values" => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
			"value" => $this->attribute($attribute, "eppav", false),
			"disabled" => $disabled
		);
		
		$parentSelect = new Varien_Data_Form_Element_Select($parentConfig);
		$parentSelect->setForm($this->getForm());
		
		$parentConfig["name"] = (($custom == 1) ? self::CUSTOM_ATTRIBUTE_ARRAY_PATH : self::ATTRIBUTE_ARRAY_PATH)."[".$index."][eppav]";
		$parentConfig["disabled"] = "";
		$parentConfig["style"] = "display: none;";
		
		$parentHiddenSelect = new Varien_Data_Form_Element_Select($parentConfig);
		$parentHiddenSelect->setForm($this->getForm());
		
		$postConfig = array(
			"id" => "aukro_postconfig",
			"name" => (($custom == 1) ? self::CUSTOM_ATTRIBUTE_ARRAY_PATH : self::ATTRIBUTE_ARRAY_PATH)."[".$index."][postproc".(($disabledBool == 1) ? "_disabled" : "")."][]",
			"style" => "width: 200px;",
			"values" => Mage::helper('aukro/data_feed')->getPostProcessFunctionOptions($this->getFile()),
			"value" => $this->attribute($attribute, "postproc", false),
			"disabled" => $disabled
		);
		
		$postSelect = new Varien_Data_Form_Element_Multiselect($postConfig);
		$postSelect->setSize(3);
		$postSelect->setForm($this->getForm());
		
		$postConfig["name"] = (($custom == 1) ? self::CUSTOM_ATTRIBUTE_ARRAY_PATH : self::ATTRIBUTE_ARRAY_PATH)."[".$index."][postproc][]";
		$postConfig["disabled"] = "";
		$postConfig["style"] = "display: none;";
		
		$postHiddenSelect = new Varien_Data_Form_Element_Multiselect($postConfig);
		$postHiddenSelect->setForm($this->getForm());
		
		$description = $this->attribute($attribute, "description", null);
		if (isset($description)) {
			$description = $this->processDescription($description);
		}
		
		$pathColumn = "";
		if ($this->getFile() == "xml") {
			$pathColumn .= "<td style=\"display: table-cell;\">";
			if ($custom == 1) {
				$pathColumn .= "<input type=\"hidden\" name=\"".self::CUSTOM_ATTRIBUTE_ARRAY_PATH_FULL."[".$index."][path]\" value=\"".$this->attribute($attribute, "path")."\" />".$this->attribute($attribute, "path");
			}
			else {
				$pathColumn .= $this->attribute($attribute, "path");
			}
			$pathColumn .= "</td>";
		}
		
		$html = '
		<tr class="'.$disabled.'" '.(($custom == 1) ? "id=\"custom_".$key."\"" : "").'>
			<td style="display: table-cell;">'.$key.'</td>
			<td style="display: table-cell;">';
		if ($custom == 1) {
			$html .= "<input type=\"text\" name=\"".self::CUSTOM_ATTRIBUTE_ARRAY_PATH_FULL."[".$index."][label]\" value=\"".$this->attribute($attribute, "label")."\" style=\"width: 95%;\" /><input type=\"hidden\" name=\"".self::CUSTOM_ATTRIBUTE_ARRAY_PATH_FULL."[".$index."][delete]\" value=\"0\" id=\"customdelete".$key."\" />";
		}
		 else {
			$html .= $this->attribute($attribute, "label").'<img style="cursor: help;" alt="'.$description.'" title="'.$description.'" src="'.Mage::getBaseUrl('skin').self::INFO_IMAGE_PATH.'">';
		 }
		$html .= '</td>
			<td style="display: table-cell;"><input type="text" value="'.$this->attribute($attribute, "prefix").'" name="'.(($custom == 1) ? self::CUSTOM_ATTRIBUTE_ARRAY_PATH_FULL : self::ATTRIBUTE_ARRAY_PATH_FULL).'['.$index.'][prefix'.(($disabledBool == 1) ? "_disabled" : "").']" style="width: 95%;"'.$disabledHtml.' />'.(($disabledBool == 1) ? "<input type=\"hidden\" value=\"".$this->attribute($attribute, "prefix")."\" name=\"".(($custom == 1) ? self::CUSTOM_ATTRIBUTE_ARRAY_PATH_FULL : self::ATTRIBUTE_ARRAY_PATH_FULL)."[".$index."][prefix]\" />" : "").'</td>
			<td style="display: table-cell;"><input type="text" value="'.$this->attribute($attribute, "constant").'" name="'.(($custom == 1) ? self::CUSTOM_ATTRIBUTE_ARRAY_PATH_FULL : self::ATTRIBUTE_ARRAY_PATH_FULL).'['.$index.'][constant'.(($disabledBool == 1) ? "_disabled" : "").']" style="width: 95%;"'.$disabledHtml.' />'.(($disabledBool == 1) ? "<input type=\"hidden\" value=\"".$this->attribute($attribute, "constant")."\" name=\"".(($custom == 1) ? self::CUSTOM_ATTRIBUTE_ARRAY_PATH_FULL : self::ATTRIBUTE_ARRAY_PATH_FULL)."[".$index."][constant]\" />" : "").'</td>
			<td style="display: table-cell;">'.$attributesSelect->getElementHtml().(($disabledBool == 1) ? $attributesHiddenSelect->getElementHtml() : "" ).'</td>
			<td style="display: table-cell;">'.$translateGridHtml.'</td>
			<td style="display: table-cell;"><input type="text" value="'.$this->attribute($attribute, "suffix").'" name="'.(($custom == 1) ? self::CUSTOM_ATTRIBUTE_ARRAY_PATH_FULL : self::ATTRIBUTE_ARRAY_PATH_FULL).'['.$index.'][suffix'.(($disabledBool == 1) ? "_disabled" : "").']" style="width: 95%;"'.$disabledHtml.' />'.(($disabledBool == 1) ? "<input type=\"hidden\" value=\"".$this->attribute($attribute, "suffix")."\" name=\"".(($custom == 1) ? self::CUSTOM_ATTRIBUTE_ARRAY_PATH_FULL : self::ATTRIBUTE_ARRAY_PATH_FULL)."[".$index."][suffix]\" />" : "").'</td>
			<td style="display: table-cell;">'.$parentSelect->getElementHtml().(($disabledBool == 1) ? $parentHiddenSelect->getElementHtml() : "" ).'</td>
			<td style="display: table-cell;"><input type="text" value="'.$this->attribute($attribute, "limit").'" name="'.(($custom == 1) ? self::CUSTOM_ATTRIBUTE_ARRAY_PATH_FULL : self::ATTRIBUTE_ARRAY_PATH_FULL).'['.$index.'][limit'.(($disabledBool == 1) ? "_disabled" : "").']" style="width: 95%;"'.$disabledHtml.' />'.(($disabledBool == 1) ? "<input type=\"hidden\" value=\"".$this->attribute($attribute, "limit")."\" name=\"".(($custom == 1) ? self::CUSTOM_ATTRIBUTE_ARRAY_PATH_FULL : self::ATTRIBUTE_ARRAY_PATH_FULL)."[".$index."][limit]\" />" : "").'</td>
			<td style="display: table-cell;">'.$postSelect->getElementHtml().(($disabledBool == 1) ? $postHiddenSelect->getElementHtml() : "" ).'<input type="hidden" name="'.self::ATTRIBUTE_ARRAY_PATH_FULL.'['.$index.'][code]" value="'.$this->attribute($attribute, "code").'" /></td>
			'.(($custom == 1) ? "<td>".$removeElementButton->toHtml()."</td>" : "").'
		</tr>';
		
		return $html;
	}
	
	protected function attribute($attributeArray, $index, $default = "") {
		if (isset($attributeArray[$index])) {
			return $attributeArray[$index];
		}
		else {
			return $default;
		}
	}
	
	protected function processDescription($description)
	{
		$descriptionText = $this->attribute($description,"text",null);
		$example = $this->attribute($description,"example",null);
		if(isset($example) && !empty($example))
		{
			$descriptionText.= self::DESCRIPTION_RECORD_DELIMITER.Mage::helper('Aukro')->__('Example: ').$example;
		}
		
		$options = $this->attribute($description,"options",null);
		if(isset($options) && !empty($options))
		{
			$options = self::DESCRIPTION_OPTIONS_DELIMITER.str_replace(";",self::DESCRIPTION_OPTIONS_DELIMITER,$options);
			$descriptionText.= self::DESCRIPTION_RECORD_DELIMITER.Mage::helper('Aukro')->__('Options: ').$options;
		}
		$descriptionText = ucfirst($descriptionText);
		return $descriptionText;
	}
	/**
	* Dublicate interface of Varien_Data_Form_Element_Abstract::setReadonly
	*
	* @param bool $readonly
	* @param bool $useDisabled
	* @return Mage_Adminhtml_Block_Catalog_Product_Helper_Form_Apply
	*/
	public function setReadonly($readonly, $useDisabled = false) {
		$this->setData('readonly', $readonly);
		$this->setData('disabled', $useDisabled);
		return $this;
	}
}