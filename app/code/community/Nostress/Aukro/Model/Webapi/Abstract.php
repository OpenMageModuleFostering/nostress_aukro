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

class Nostress_Aukro_Model_Webapi_Abstract extends Nostress_Aukro_Model_Abstract {
	
// 	const AUKRO_COUNTRY = 228; // 228 ND testwebapi.pl
// 	const AUKRO_URL = 'http://webapi.allegro.pl/uploader.php?wsdl';

	const AUKRO_COUNTRY = 56; 	// CZ Aukro.cz
	const AUKRO_URL = 'https://webapi.aukro.cz/service.php?wsdl';
	//const AUKRO_URL = 'https://webapi.aukro.cz/uploader.php?wsdl';

	/* AUKRO WEBAPI KONSTANTY */
	// html typ attributu
	const STRING = 1;
	const INTEGER = 2;
	const FLOAT = 3;
	const SELECT = 4 ;
	const RADIOBUTTON = 5;
	const CHECKBOX = 6;
	const IMAGE = 7;
	const TEXTAREA = 8;
	const DATETIME = 9;
	const DATE = 13;
	
	const FID = 'fid';
	const FVALUE_STRING = "fvalueString";
	const FVALUE_INT = "fvalueInt";
	const FVALUE_FLOAT = "fvalueFloat";
	const FVALUE_IMAGE = "fvalueImage";
	const FVALUE_DATETIME = "fvalueDatetime";
	const FVALUE_DATE = "fvalueDate";
	const FVALUE_RANGE_INT = "fvalueRangeInt";
	const FVALUE_RANGE_FLOAT = "fvalueRangeFloat";
	const FVALUE_RANGE_DATE = "fvalueRangeDate";
	
	const MANDATORY = 8;
	
	const CMD_DO_GET_SHIPMENT_DATA = 'doGetShipmentData'; //NEW Umožňuje získat údaje o způsobech přepravy z určité země
	const CMD_DO_GET_SELL_FORM_ATTRIBS = 'doGetSellFormAttribs'; //Zjištění specifickych atributů pro danou kategorii
	const CMD_DO_GET_SELL_FORM_FIELDS_FOR_CATEGORY = 'doGetSellFormFieldsForCategory'; // uplne vsechny atributy kategorie
	const CMD_DO_GET_CATS_DATA = 'doGetCatsData'; //Vrací kompletní seznam kategorií na Aukru
	const CMD_DO_QUERY_SYS_STATUS = 'doQuerySysStatus'; //NEW Tato metoda dovoluje získat informace o verzích WebAPI komponent.
	const CMD_DO_LOGIN_ENC = 'doLoginEnc'; //NEW Metoda pro přihlášení uživatele s využitím šifrování hesla.
	const CMD_DO_CHECK_NEW_AUCTION_EXT = 'doCheckNewAuctionExt'; //Ověřuje správnost všech zadaných polí prodejního formuláře
	const CMD_DO_NEW_AUCTION_EXT = 'doNewAuctionExt'; //Postará o vystavení produktu
	const CMD_DO_FINISH_ITEM = 'doFinishItem';
	const CMD_DO_UPDATE_ITEM = 'doChangeItemFields';
	
	protected $_webapi_ver;
	protected $_soap;
	protected $_helper;
	protected $_session_id;
	protected $_user_id;
	
	public function __construct() {
		$this->_soap = new SoapClient ( self::AUKRO_URL);
		$this->_soap->soap_defencoding = 'UTF-8';
		$this->_soap->decode_utf8 = false;
		$userData = Mage::helper('aukro')->getAukroLoginData();
		try {
			// ziskani verze WebAPI
			$params = array('sysvar' => 1,'countryId' => self::AUKRO_COUNTRY, 'webapiKey' => $userData['webapi_key']);
			$output = $this->soapCall(self::CMD_DO_QUERY_SYS_STATUS,$params,false);
  			$this->_webapi_ver = $output['verKey'];
			// prihlaseni k WebAPI
  			$pass_hash = base64_encode(hash('sha256', $userData['password'], true));
  			$params = array('userLogin' => $userData['username'], 'userHashPassword' => $pass_hash, 'countryCode' => self::AUKRO_COUNTRY, 'webapiKey' => $userData['webapi_key'], 'localVersion' => $this->_webapi_ver);
  			$output = $this->soapCall(self::CMD_DO_LOGIN_ENC,$params);
			$this->_session_id = $output['sessionHandlePart'];
  			$this->_user_id = $output['userId'];
		}
		catch (Exception $e)
		{
			$this->error($e);
			return array();
			//throw new Exception("Aukro webapi: ".$e->faultcode." - ".$e->faultstring);
		}
	}
	
	public function getResTypes() {
	    
	    return array(
            self::STRING => self::FVALUE_STRING,
            self::INTEGER => self::FVALUE_INT,
            self::FLOAT => self::FVALUE_FLOAT,
            self::IMAGE => self::FVALUE_IMAGE,
            self::DATETIME => self::FVALUE_DATETIME,
            self::DATE => self::FVALUE_DATE,
        );
	}
	
	/**
	 * @return All aukro categories
	 */
	public function getCategoryData() {
		$params = array (
			'countryId' => self::AUKRO_COUNTRY,
			'localVersion'	=> $this->_webapi_ver,
			'webapiKey'	=> Mage::helper('aukro')->getAukroWebApiKey(),
		);
				
		return $this->soapCall(self::CMD_DO_GET_CATS_DATA,$params);
	}
	
	/**
	 * Gets aukro category data.
	 * @param $categoryId Category ID.
	 * @return Aukro category data
	 */
	public function getSellFormAttributesForCategory($categoryId) {
		$params = array (
			'countryId' => self::AUKRO_COUNTRY,
			'webapiKey'	=> Mage::helper('aukro')->getAukroWebApiKey(),
			'localVersion'	=> $this->_webapi_ver,
			'catId'	=> $categoryId,
		);
		
		return $this->soapCall(self::CMD_DO_GET_SELL_FORM_ATTRIBS,$params);
	}
	
	/**
	 * Gets aukro category data 2
	 * @param $categoryId Category ID.
	 * @return Aukro category data
	 */
	public function getSellFormFields($categoryId) {
	    $params = array (
            'webapiKey'	=> Mage::helper('aukro')->getAukroWebApiKey(),
            'countryId' => self::AUKRO_COUNTRY,
            'categoryId'	=> $categoryId,
	    );
	
	    return $this->soapCall( self::CMD_DO_GET_SELL_FORM_FIELDS_FOR_CATEGORY, $params);
	}
		
	/**
	 * @return Shipping information for current country.
	 */
	public function getShipmentData() {
		$params = array (
			'countryId' => self::AUKRO_COUNTRY,
			'webapiKey'	=> Mage::helper('aukro')->getAukroWebApiKey(),
		);
		
		return $this->soapCall(self::CMD_DO_GET_SHIPMENT_DATA,$params);
	}
	
	public function _uploadProduct($data,$dryRun = false)
	{
		 // vytvoreni vstupniho pole pro doNewAuctionExt
  		$params = array('sessionHandle' => $this->_session_id, 'fields' => $data);
  		// Misto realneho vystaveni nabidky muzeme zavolat pouze jeji kontrolu - metoda doCheckNewAuctionExt nevystavi nabidku.
  		if($dryRun)
  			$output = $this->soapCall(self::CMD_DO_CHECK_NEW_AUCTION_EXT,$params);
  		else
  			$output = $this->soapCall(self::CMD_DO_NEW_AUCTION_EXT,$params); // Realne vystaveni nabidky - metoda doNewAuctionExt jiz nabidku vystavi
  		
  		if( is_array( $output) && isset( $output['itemId'])) {
  		    return $output['itemId'];
  		}
  		
  		return $output;
	}
	
	protected function soapCall($cmd,$params,$catchException = true)
	{
	    if( count( $params) > 1) {
	        $params = array( $params);
	    }
	    
		if(!$catchException)
			return $this->objectToArray( $this->_soap->__soapCall($cmd, $params));
			
		try
		{
			return $this->objectToArray( $this->_soap->__soapCall($cmd, $params));
		}
		catch (Exception $e)
		{
			$this->error($e);
			return false;
		}
		
	}
	
	protected function helper()
	{
		if(!isset($this->_helper))
			$this->_helper = Mage::helper('aukro');
		return $this->_helper;
	}
	
	protected function error(Exception $e)
	{
		Mage::getSingleton('core/session')->addError("Aukro webapi: ".$e->faultcode." - ".$e->faultstring);
	}
	
	function objectToArray($d) {
	    return json_decode(json_encode($d), true);
	}
}
	 
	 