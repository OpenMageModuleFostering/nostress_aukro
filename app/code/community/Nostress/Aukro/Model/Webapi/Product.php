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

class Nostress_Aukro_Model_Webapi_Product extends Nostress_Aukro_Model_Webapi_Abstract
{
    const SHIPMENT_FIRST_ID = 36;
    const SHIPMENT_LAST_ID = 48;
    const SHIPMENT_NEXT_PREFIX = 1;
    const SHIPMENT_AMOUNT_PREFIX = 2;
    
    const ERR_INVALID_ITEM_ID = 'ERR_INVALID_ITEM_ID';
    
	protected $_categoryMapping;
	protected $_aukroAttributes = null;
	protected $_categoryAttributes = null;
	protected $_currentCategory;
	protected $_defaultConfig = null;
	
	/**
	 * pevne atributy kategorie
	 *
	 * @var array
	 */
	protected $_baseCategoryAttributes = array( "display_duration", "auto_display", "base_unit");
	
	protected $_attributesMappingByAukro = null;
	protected $_attributesMappingByMagento = null;
	
	/**
	 * parametry typu multiselect
	 *
	 * @var array
	 */
	protected $_attributesMultiselect = array( 'payment_type', 'deluxe_options', 'shipping_conditions', 'freeshipping');
	
	/**
     * zakladni nastaveni pro mapovani atributu
	 */
	protected $_baseAttributes = array(
	        
	        array("code" => 1,"label" => "Název", "predefined" => "","magento" => "name","type"=>"","description" => ""),
	        array("code" => 2,"label" => "Aukro kategorie", "predefined" => "","magento" => "aukrocategory_id","type"=>"","description" => ""),
	        array("code" => 24,"label" => "Popis", "predefined" => "","magento" => "description","type"=>"","description" => ""),
	        array("code" => 8,"label" => "Cena", "predefined" => "","magento" => "price_final_include_tax","type"=>"","description" => ""),
	        array("code" => 16,"label" => "Obrázek", "predefined" => "","magento" => "image","type"=>"","description" => ""),
	        array("code" => 5,"label" => "Množství", "predefined" => "","magento" => "qty","type"=>"","description" => ""),
	        
	        array("code" => 4, "label" => "Délka trvání", "predefined" => "","magento" => "display_duration","type"=>"","description" => ""),
	        array("code" => 30,"label" => "Automatické vystavení", "predefined" => "","magento" => "auto_display","type"=>"","description" => ""),
	        array("code" => 28,"label" => "Základní měrná jednotka", "predefined" => "","magento" => "base_unit","type"=>"","description" => ""),
	        
	        array("code" => 0, "label" => "ID produktu v aukru", "predefined" => "","magento" => "aukro_product_id","type"=>"","description" => ""),
	        array("code" => 3, "label" => "ID kategorie", "predefined" => "","magento" => "category_id","type"=>"","description" => ""),
	)	;
	
	/**
     * mapovani atributu s konfigurace
	 */
	protected $_attributesDefaultMapping = array(
	    
        'country' => 9,
        'county' => 10,
        'city' => 11,
        'postcode' => 32,
        
	    'shipping_payer' => 12,
	    'shipping_conditions' => 13,
	    'payment_type' => 14,
        'deluxe_options' => 15,
        'additional_info' => 27,
        'freeshipping' => 35,
    );
	
	
	protected $_emptyField = array(
        'fid' => 0, 'fvalueString' => null, 'fvalueInt' => null,
        'fvalueFloat' => null, 'fvalueImage' => null, 'fvalueDatetime' => null,
        'fvalueDate' => null, 'fvalueRangeInt' => null,
        'fvalueRangeFloat' => null, 'fvalueRangeDate' => null
    );
	
	public function getExposedItems() {
	    
	    $params = array(
            'userId' => $this->_user_id,
            'webapiKey'	=> Mage::helper('aukro')->getAukroWebApiKey(),
            'countryId' => self::AUKRO_COUNTRY
	    );
	    $result = $this->soapCall( 'doGetUserItems',$params);
	    if( isset($result['userItemList']['item'])) {
	       $result = $this->helper()->formatData( $result['userItemList']['item']);
	    } else {
	       $result = array();
	    }
	    $items = array();
	    foreach( $result as $item) {
	        $items[ (string)$item['itId']] = $item['itName'];
	    }
	    return $items;
	}
	
	public function getSiteJournal( $infoType = 0) {
	    
	    $params = array('sessionHandle' => $this->_session_id, 'startingPoint' => 0, 'infoType' => $infoType);
	     
	    $events = array();
	    $result = $this->soapCall( 'doGetSiteJournalInfo',$params);
	    $total = $result['siteJournalInfo']['itemsNumber'];
	    
	    if( $total > 0) {
	        $pages = ceil( $total / 100);
	        while( $pages != 0) {
	            $result = $this->soapCall( 'doGetSiteJournal',$params);
	            $result = $this->helper()->formatData( $result['siteJournalArray']['item']);
	            $events = array_merge( $events, $result);
	            $lastItem = end( $result);
	            $params['startingPoint'] = $lastItem['rowId'];
	            $pages--;
	        }
	    }
	    
	    return $events;
	}
	
	/**
	 * kotroluje seznam vystavenych aukci na aukro vs. seznam vystavenych produktu v magento
	 *
	 * @return array
	 */
	public function refresh() {
	    
	    $items = $this->getExposedItems();
	    
	    $countClosed = 0;
	    $productCollection = Mage::getModel( 'catalog/product')->getCollection();
	    $productCollection
	        ->addAttributeToFilter( 'aukro_product_id', array( 'notnull'=>true))
	        ;
	    $productCollection->load();
	    foreach( $productCollection as $product) {
	        // close exposure for products not in list
	        if( !isset( $items[$product->aukro_product_id])) {
	            Mage::getModel( 'aukro/product')->finish( $product->aukro_product_id);
	            $product->aukro_product_id = null;
	            $product->save();
	            $countClosed++;
	        // ok
	        } else {
	            unset( $items[$product->aukro_product_id]);
	        }
	    }
	    
	    $countNew = 0;
	    foreach( $items as $aukroID => $name) {
	        $product = Mage::getModel('catalog/product');
	        $product->loadByAttribute('name', $name);
	        if( $product->getId()) {
	            Mage::getModel( 'aukro/product')->create( $aukroID, $product->getId());
	            $product->aukro_product_id = $aukroID;
	            $product->save();
	            $countNew++;
	        }
	    }
	    
	    $this->helper()->logProductsRefreshEvent( count( $items));
	    
	    return array( $countClosed, $countNew);
	}
	
	/**
	 * upload polozek na aukro
	 *
	 * @param array $items
	 * @param bool $dryRun
	 *
	 * @return number
	 */
	public function upload($items,$dryRun = false)
	{
	    // pouze jedna polozka
	    if( isset( $items['attributes'])) {
	        $items = array( $items);
	    }
	    
		$output = array();
		$count = 0;
		foreach($items as $item)
		{
		    // ID produktu
            $itemID = $item['attributes']['id'];
            // ID na aukru
            $aukroProductID = (isset( $item['attributes']['id_produktu_v_aukru'])) ? $item['attributes']['id_produktu_v_aukru'] : null;
            // pokud neni ID na aukru provede se upload.
            if( $aukroProductID === null) {
                    			
    			$product = Mage::getModel( 'catalog/product');
    			$product->load( $itemID);
    			if( $product->getId()) {
    			    
    			    try {
    			        $fieldsArray = $this->prepareProductStructure($item['attributes']);
    			    } catch( Exception $e) {
    			        Mage::getSingleton('adminhtml/session')->addError($this->helper()->__("Error: Product '%s (%s)' %s", $product->getName(), $product->getSku(), $e->getMessage()));
    			        continue;
    			    }
    			    

    			    $qty = $product->getStockItem()->getQty();
    			    $inStock = $product->getIsInStock();
    			    if( $qty <= 0) {
    			        Mage::getSingleton('adminhtml/session')->addError($this->helper()->__("Error: Product '%s (%s)' does not have any items in stock!", $product->getName(), $product->getSku()));
    			        continue;
    			    }
    			    if( !$inStock) {
    			        Mage::getSingleton('adminhtml/session')->addError($this->helper()->__("Error: Product '%s (%s)' is out of stock!", $product->getName(), $product->getSku()));
    			        continue;
    			    }
    			    
    			    $aukroProductID = $this->_uploadProduct($fieldsArray,$dryRun);
        			if( $aukroProductID !== false && !is_array($aukroProductID)) {
                			
        			    $product->aukro_product_id = $aukroProductID;
        			    $product->save();
        			    
        			    Mage::getModel( 'aukro/product')->create( $aukroProductID, $product->getId());
        			            			    
        			    $count++;
        			} elseif( $dryRun) {
        			    $count++;
        			}
                }
            }
		}
		return $count;
	}
	
	protected function _updateProduct( $aukroProductID, $data) {
  		$params = array(
  		    'sessionId' => $this->_session_id,
  		    'itemId'=> (float) $aukroProductID,
            'fieldsToModify' => $data
        );
  		$output = $this->soapCall(self::CMD_DO_UPDATE_ITEM,$params);
  		if( is_array( $output) && isset( $output['changedItem']['itemId'])) {
  		    return $output['changedItem']['itemId'];
  		}
  		return $output;
	}
	
	/**
	 * ruseni polozek na aukru
	 *
	 * @param array $ids
	 * @return number
	 */
	public function remove( $ids) {
	    
	    if( !is_array( $ids)) {
	        $ids = array( $ids);
	    }

	    // zjisteni aukro ID produktu
	    $aukroIds = array();
	    $count = 0;
	    
	    $exposedItems = $this->getExposedItems();
	    
	    foreach( $ids as $id) {
	        $product = Mage::getModel( 'catalog/product');
	        $product->load( $id);
	        if( $product->aukro_product_id !== null) {
                    
	            // pokud neni vystaveny nebo ukonceni probehlo v poradku
	            if( !array_key_exists($product->aukro_product_id, $exposedItems) ||
	               $this->finishItem( $product->aukro_product_id))
	            {
	                Mage::getModel( 'aukro/product')->finish( $product->aukro_product_id);
	                $product->aukro_product_id = null;
	                $product->save();
	                $count++;
	            }
	        }
	    }
	    
	    return $count;
	}
    
    /**
	 * update polozek na aukro
	 *
	 * @param array $items
	 *
	 * @return number
	 */
	public function update($items)
	{
	    // pouze jedna polozka
	    if( isset( $items['attributes'])) {
	        $items = array( $items);
	    }
	    
		$output = array();
		$count = 0;
		foreach($items as $item)
		{
		    // ID produktu
            $itemID = $item['attributes']['id'];
            // ID na aukru
            $aukroProductID = (isset( $item['attributes']['id_produktu_v_aukru'])) ? $item['attributes']['id_produktu_v_aukru'] : null;
            // pokud je na aukro provede se update
            if( $aukroProductID !== null) {
                    			
    			$product = Mage::getModel( 'catalog/product');
    			$product->load( $itemID);
    			if( $product->getId()) {
    			    
    			    try {
    			        $fieldsArray = $this->prepareProductStructure($item['attributes']);
    			    } catch( Exception $e) {
    			        Mage::getSingleton('adminhtml/session')->addError($this->helper()->__("Error: Product '%s (%s)' %s", $product->getName(), $product->getSku(), $e->getMessage()));
    			        continue;
    			    }
    			    
    			    $qty = $product->getStockItem()->getQty();
    			    $inStock = $product->getIsInStock();
    			    if( $qty <= 0) {
    			        Mage::getSingleton('adminhtml/session')->addError($this->helper()->__("Error: Product '%s (%s)' does not have any items in stock!", $product->getName(), $product->getSku()));
    			        continue;
    			    }
    			    if( !$inStock) {
    			        Mage::getSingleton('adminhtml/session')->addError($this->helper()->__("Error: Product '%s (%s)' is out of stock!", $product->getName(), $product->getSku()));
    			        continue;
    			    }
    			        			    
    			    $aukroProductID = $this->_updateProduct( $aukroProductID, $fieldsArray);
    			    if( $aukroProductID) {
    			        $count++;
    			    }
        			
                }
            }
		}
		return $count;
	}
	
	
	/**
	 * ukonceni jedne polozky
	 *
	 * @param string $aukroItemId
	 * @return bool
	 */
	public function finishItem( $aukroItemId) {
	     
	    $params = array(
	            'sessionHandle' => $this->_session_id,
	            'finishItemId' => (float) $aukroItemId,
	            'finishCancelAllBids' => 0,
	            'finishCancelReason' => ''
	    );
	    
	    try {
            $response = (bool) $this->soapCall( self::CMD_DO_FINISH_ITEM, $params, false);
	    } catch( Exception $e) {
	        
	        if( $e->faultcode == self::ERR_INVALID_ITEM_ID) {
	            //Mage::getSingleton('core/session')->addError("Item '$aukroItemId' has been canceled in aukro!");
	            return true;
	        } else {
	            $this->error($e);
	            return false;
	        }
	    }
	    return $response;
	}
	
	public function getDefaultConfig() {
	    
	    if( $this->_defaultConfig === null) {
	        $this->_defaultConfig = Mage::helper( 'aukro')->getDefaultConfig();
	        foreach( $this->_attributesMultiselect as $attribCode) {
	            if( isset( $this->_defaultConfig[$attribCode])) {
	                $this->_defaultConfig[$attribCode] = $this->_formatMultiselect( $this->_defaultConfig[$attribCode]);
	            }
	        }
	    }
	    return $this->_defaultConfig;
	}
	
	/**
	 * format multiselect values from 1,2,4 to 7 (sum)
	 *
	 * @param string $value
	 * @return int
	 */
	protected function _formatMultiselect( $value) {
	    if( !is_array( $value)) {
	        $value = explode(',', $value);
	    }
	    return array_sum( $value);
	}
	
	protected function getAukroAttributes( $categoryId) {
	    	    
	    $allAttributes = $this->getSellFormFields($categoryId);
        $attributes = array();
        foreach( $allAttributes['sellFormFieldsForCategory']['sellFormFieldsList']['item'] as $attrib) {
            $attributes[ $attrib['sellFormId']] = $attrib;
        }
        return $attributes;
	}
	
	protected function prepareProductStructure($productData)
	{
	    $this->initAttributesMapping();
	    $categoryId = $this->getArrayField('id_kategorie',$productData);
	    // vrati defaultni a atributy z kategorie (nacita se pouze jednou pro celou kategorii)
		$attributes = $this->initCurrentCategory( $categoryId);
		if( $attributes === false) {
		    throw new Exception( $this->helper()->__('does not have specify aukrocategory!'));
		}
		// zpracuje atributy produktu
		$this->ppProductAttributes($productData, $attributes);
		
		$fields_arr = array();
		
		$noMapping = array();
		
		$resTypes = $this->getResTypes();
		
		foreach( $attributes as $code => $value) {
		    
		    if( is_int( $code)) {
		        $id = $code;
		    } elseif( isset($this->_attributesMappingByMagento[$code])) {
		        $id = $this->_attributesMappingByMagento[$code];
		    } elseif( isset( $this->_attributesDefaultMapping[$code])) {
		        $id = $this->_attributesDefaultMapping[$code];
		    } else {
		        $noMapping[ $code] = $value;
		        continue;
		        //throw new Zend_Exception( "Nezname mapovani pro $code");
		    }

		    
		     
		    if( is_array( $value) && isset( $value['type']) && isset( $value['value'])) {
                $oldValue = $value;
	            $value = $value['value'];
	            if( isset( $value['type'])) {
	                $type = $value['type'];
	            } else {
	                $type = null;
	            }
		    } else {
		        $type = null;
		    }
		    		    		    
		    $type = $resTypes[$this->_aukroAttributes[$id]['sellFormResType']];
		    // katagorie nepodporuje tento atribut. Napr stav zbozi z produktu
		    if( $type === null) {
		        continue;
		    }
		    
		    // tvorba bitoveho pole, pokud je value pole integeru
		    if( is_array($value) && $type == self::FVALUE_INT) {
		        $value = $this->_formatMultiselect($value);
		    }
		    
		    if( $value === null) {
		        $value = $this->_aukroAttributes[$id]['sellFormDefValue'];
		    } elseIf( $type == self::FVALUE_INT) {
		        $max = $this->_aukroAttributes[$id]['sellMaxValue'];
		        if( $max > 0 && $value > $max) {
		            $value = $max;
		        }
		    }
		    $fields_arr[ $id] = $this->getNewField($id, $value, $type);
		}
		
		ksort( $fields_arr);
		$fieldsValues = array();
		foreach( $fields_arr as $field) {
		    $fieldsValues[] = $field;
		}
				
		return $fieldsValues;
	}
		
	protected function _getShipmentAttributes( $categoryId, $aukroAttributes) {
	    
	    $shippingModel = Mage::getModel('aukro/shipping_pricing');
	    $shippingAttributes = array();
	    
	    foreach( $aukroAttributes as $attribID => $attrib) {
	        if( $attribID >= self::SHIPMENT_FIRST_ID && $attribID <= self::SHIPMENT_LAST_ID) {
	            // mapovani dopravy je v popisu atributu
	            $idMapping = $attrib['sellFormFieldDesc'];

	            // nalezeni polozky
	            $item = $shippingModel->load( $idMapping);
	            // pokud je vyplnena prvni polozka vytvori se atribut
    	        if( $item !== null) {
    	            $itemData = $item->getData();
    	            if( !empty( $itemData['first'])) {
                        $shippingAttributes[ $attribID] = $itemData['first'];
                        // potom se kontroluje "dalsi"
                        if( !empty( $itemData['next']) && isset($aukroAttributes[self::SHIPMENT_NEXT_PREFIX.$attribID])) {
                            $shippingAttributes[self::SHIPMENT_NEXT_PREFIX.$attribID] = $itemData['next'];
                        }
                        // potom se kontroluje "mnozstvi"
                        if( !empty( $itemData['amount']) && isset($aukroAttributes[self::SHIPMENT_AMOUNT_PREFIX.$attribID])) {
                            $shippingAttributes[self::SHIPMENT_AMOUNT_PREFIX.$attribID] = $itemData['amount'];
                        }
    	            }
    	        }
	        }
	    }
	    
	    return $shippingAttributes;
	}
	
	protected function ppProductAttributes( $productData, &$attributes) {
	    
	    $attributesMapping = $this->_attributesMappingByAukro;
	    foreach( $productData as $labelCode => $value) {
	        if( isset( $attributesMapping[$labelCode])) {
	            if( $labelCode == 'popis' && isset( $attributes['offer_label'])) {
	                $value .= " <br/><br/>".$attributes['offer_label'];
	            }
	            if( $labelCode == 'obrazek') {
	                 $img = $this->convertImage($value);
	                 // false = obrazek neexistuje = no_selection
	                 if( $img === false) {
	                     $value = -1; // -1 = atribut se preskoci
	                 } else {
    		             $type = self::FVALUE_IMAGE;
    		             $value = array( 'value'=>$img, 'type'=>$type);
	                 }
	            } elseif( $labelCode == 'cena') {
	                $value = $value / 100;
	            }
	            // -1 je null hodnota pro nektere atributy
	            if( $value != -1) {
	                $attributes[ $attributesMapping[$labelCode]['magento']] = $value;
	            }
	        }
	    }
	}
	
	protected function initCurrentCategory($categoryId)
	{
	    if( !isset( $categoryId)) {
	        return false;
	    }
	    // na zacatku nebo pri zmene kategorie se musi nacist znovu vsechny atributy
		if($this->_currentCategory === null || $this->_currentCategory->getCategory() != $categoryId) {

		    // entita magento kategorie
		    $this->_currentCategory = $this->getAukroCategoryData( $categoryId);
		    $aukroCategoryId = $this->_currentCategory['aukrocategory'];
		    // atributy dane kategorie z aukra
		    $this->_aukroAttributes = $this->getAukroAttributes( $aukroCategoryId);
		    // defaultni nastaveni z konfigurace
		    $this->_categoryAttributes = $this->getDefaultConfig();
		    
		    // autributy z nastaveni kategorie
		    $category = $this->getCurrentCategory();
		    $categoryAttributes = $this->getBaseCategoryAttributes();
		    foreach($categoryAttributes as $attributeName) {
		        $value = $category->getData($attributeName);
		        if($value == 0 || !empty($value)) {
		            if( $value == -1) { continue;}
		            
		            if( in_array( $attributeName, $this->_attributesMultiselect)) {
		                $value = $this->_formatMultiselect($value);
		            }
		            
		            $this->_categoryAttributes[ $attributeName] = $value;
		        }
		    }
		    
		    // specificke atributy z nastaveni kategorie
		    $specificAttributes = unserialize( $category->getData( 'attributes'));
		    if( !empty($specificAttributes)) {
		        $this->_categoryAttributes += $specificAttributes;
		    }
		    
		    // atributy doruceni
		    $shipmentAttributes = $this->_getShipmentAttributes( $aukroCategoryId, $this->_aukroAttributes);
		    if( !empty( $shipmentAttributes)) {
		        $this->_categoryAttributes += $shipmentAttributes;
		    }
		}
		
		return $this->_categoryAttributes;
	}
	
	protected function getCurrentCategory()
	{
		return $this->_currentCategory;
	}
	
	public function initAttributesMapping() {
	    
	    if( $this->_attributesMappingByAukro === null) {
	        $mapping = Mage::getModel( 'aukro/mapping_attribute')->getCollectionData();
	        $this->_attributesMappingByAukro = array();
	        foreach( $mapping as $attrib) {
	            $this->_attributesMappingByAukro[ $attrib['label_code']] = $attrib;
	            if( !empty($attrib['magento'])) {
	                $this->_attributesMappingByMagento[ $attrib['magento']] = $attrib['id'];
	            }
	        }
	    }
	}
    
	public function getBaseAttributes()
	{
	    if(!isset($this->_baseAttributes[0]["label_code"]))
	    {
	        $attribs = $this->_baseAttributes;
	        foreach($attribs as $key => $data)
	        {
	            $attribs[$key]["label_code"] =  $this->helper()->createCode($data['label']);
	        }
	        $this->_baseAttributes = $attribs;
	    }
	    return $this->_baseAttributes;
	}
	
    protected function getBaseCategoryAttributes()
    {
    	return $this->_baseCategoryAttributes;
    }
    
    protected function getNewField($id,$value,$type = null)
    {
    	$field = $this->getEmptyField();
    	$field[self::FID] = $id;
    	if(!isset($type))
    		$type = $this->getType($value);
    	
    	$field[$type] = $value;
    	return $field;
    }
    
    protected function getType($value)
    {
    	$type = self::FVALUE_STRING;
    	if(is_int($value))
    		$type = self::FVALUE_INT;
    	else if(is_float($value))
    		$type = self::FVALUE_FLOAT;
    	else if(is_array($value))
    	    $type = self::FVALUE_RANGE_INT;
    	return $type;
    }
    
    protected function getEmptyField()
    {
    	return $this->_emptyField;
    }
    
    protected function getAukroCategoryData($id)
    {
    	$cm = $this->getCategoryMapping();
    	if(isset($id) && isset($cm[$id]))
    		return $cm[$id];
    	else
    		$this->logAndException("Aukro category %s doesn't exist.");
    }
    
    protected function getCategoryMapping()
    {
    	if(!isset($this->_categoryMapping))
    	{
    		$items = Mage::getModel('aukro/mapping_category')->getCollection()->load();
    		$this->_categoryMapping = array();
    		foreach( $items as $item) {
    		    $this->_categoryMapping[ $item['category']] = $item;
    		}
    	}
    	return $this->_categoryMapping;
    }
    
    protected function convertImage($value)
    {
    	$hnd = @fopen($value, 'rb'); @fclose($hnd);
 	 	if ($hnd)
  		{
    		// pokud ano pak nacteme jeho obsah
    		$value = file_get_contents($value);
  		}
  		else
  		{
  			$value = false;
  		}
  		return $value;
    }
}