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
* Helper.
*
* @category Nostress
* @package Nostress_Aukro
*
*/

class Nostress_Aukro_Helper_Data_Client extends Nostress_Aukro_Helper_Data
{
	protected $_versionHelper;

	const RESPONSE_FEED = 'feed';
	const RESPONSE_TAXONOMY = 'taxonomy';
	const RESPONSE_ERROR = 'error';
	const RESPONSE_ERRORS = 'errors';
	const RESPONSE_INFO = 'info';
	const RESPONSE_MODULE = 'module';
	const RESPONSE_PLUGIN = 'plugin';
	const RESPONSE_LICENSE = 'license';
	const RESPONSE_VALIDITY = 'validity';
	const RESPONSE_KEY = 'key';
	const RESPONSE_COLLECTION = 'collection';

	const PARAM_LICENSE = 'license';
	const PARAM_SERVER = 'server';
	const PARAM_SERVER_ID = 'server_id';
	const PARAM_SIGN = 'sign';
	const PARAM_LINK = 'link';
	const PARAM_FILE_TYPE = 'file_type';
	const PARAM_PLUGINS = 'plugins';
	const PARAM_REQUEST_TYPE = 'request_type';

	const TYPE_FEEDS_AND_TAXONOMIES = "feeds_and_taxonomies";
	const TYPE_PLUGINS = "plugins";
	const TYPE_LICENSE = "license";

	const PARAM_CP_URL_SECURE = 'server_url_secure';
	const PARAM_CP_URL_UNSECURE = 'server_url_unsecure';
	const PARAM_API_URL_SECURE = 'koongo_api_url_secure';
	const PARAM_API_URL_UNSECURE = 'koongo_api_url_unsecure';

	const API_FUNCTION_CREATE_LICENSE = 'createAukroLicense';
	
	const CACHE_KEY_AVAILABLE_COLLECTIONS = 'koongo_available_collections';
	const CACHE_KEY_CONNECTORS_INFO = 'koongo_connectors_info';
	
	const LICENSE_NOT_VALID = "License invalid";

	public function getConnectorInfoByCode($code)
	{
		$info =  $this->getConnectorsInfo();
		foreach ($info as $item)
		{
			if(!empty($item[self::CODE]))
			{
				if(empty($code))
					return $item;
				if($item[self::CODE] == $code)
					return $item;
			}
		}
		if(!empty($info['custom']) && is_array($info['custom']))
		{
			$customConnectors = $info['custom'];
			foreach ($customConnectors as $item)
			{
				if(!empty($item[self::CODE]))
				{
					if($item[self::CODE] == $code)
						return $item;
				}
			}
		}
		return false;
	}
	
	public function getConnectorsInfo()
	{
		return $this->getInfoData(self::PARAM_CONNECTORS_JSON_URL,self::CACHE_KEY_CONNECTORS_INFO);
	}
	
	public function getAvailableCollections()
	{
		return $this->getInfoData(self::PARAM_COLLECTIONS_JSON_URL,self::CACHE_KEY_AVAILABLE_COLLECTIONS);
	}
	
	public function getUniversityInfo()
	{
		return $this->_getInfoData(self::PARAM_UNIVERSITY_JSON_URL, true);
	}
	
	public function getAvailableCollectionsAsOptionArray($isMultiselect = false)
	{
		$collections = $this->getAvailableCollections();
		$result = array();
		
		if(empty($collections) || !is_array($collections))
			return $result;
		foreach($collections as $item)
		{
			$result[$item["address"]] = array("label" => $item["address"],"value" => $item["code"]);
		}
		sort($result);
		
		if(!$isMultiselect)
			array_unshift($result, array("label" => $this->__("-- Please Select --"), "value" => ""));
		return $result;
	}
	
	/** API functions */
	
	public function createLicenseKey($params)
	{
		$params[self::PARAM_SERVER_ID] = $this->getServerId();
		$response = $this->postApiRequest($params,self::API_FUNCTION_CREATE_LICENSE);
		$response = $this->processResponse($response);
		if(empty($response[self::RESPONSE_KEY]))
		{
			throw new Exception($this->__("Server response is missing the license key."));
		}
		
		$this->versionHelper()->saveLicenseKey($response[self::RESPONSE_KEY]);
		return $response;
	}
	
	/** Server funcitons */
	
	public function updateFeeds()
	{
		$this->checkLicense();
		//1.send request
		$response = $this->sendServerRequest(self::TYPE_FEEDS_AND_TAXONOMIES);
		//2.process response
		$response = $this->processResponse($response);
		//3.check response data
		if(!isset($response[self::RESPONSE_FEED]) || !isset($response[self::RESPONSE_TAXONOMY]))
		{
			throw new Exception($this->__("Missing feeds and taxonomy data in response"));
		}

		//4.update tables
		return $this->updateConfig($response[self::RESPONSE_FEED],$response[self::RESPONSE_TAXONOMY]);
	}

	public function updatePlugins()
	{
	    $response = $this->sendServerRequest(self::TYPE_PLUGINS);
		$response = $this->processResponse($response);

		if(isset($response[self::RESPONSE_INFO]))
			$this->updateInfo($response[self::RESPONSE_INFO]);
		return true;
	}

	public function updateLicense()
	{
		if(Mage::helper('aukro/version')->isLicenseKeyT())
			return false;
	    $response = $this->sendServerRequest(self::TYPE_LICENSE);
		$response = $this->processResponse($response);

		if(isset($response[self::RESPONSE_LICENSE]))
			$this->updateLicenseData($response[self::RESPONSE_LICENSE]);
		return true;
	}

	protected function sendServerRequest($type="")
	{
		$server = $this->getServerName();
		$license =  $this->versionHelper()->getLicenseKey();

		$sign = $this->getSign($server,$license);
		$params = array();
		$params[self::PARAM_SIGN]= $sign;
		$params[self::PARAM_LICENSE] = $license;
		$params[self::PARAM_SERVER] = $server;
		$params[self::PARAM_REQUEST_TYPE] = $type;
		return $this->postServerRequest($params);
	}

	protected function postServerRequest($params)
	{
		try
		{
			$response = $this->postUrlRequest($this->getNosressServerUrl(),$params);
		}
		catch (Exception $e)
		{
			$response = $this->postUrlRequest($this->getNosressServerUrl(false),$params);
		}
		return $response;
	}
	
	protected function postApiRequest($params,$apiFunction)
	{
		try
		{
			$response = $this->postJsonUrlRequest($this->getKoongoApiUrl().$apiFunction,$params);
		}
		catch (Exception $e)
		{
			$response = $this->postJsonUrlRequest($this->getKoongoApiUrl(false).$apiFunction,$params);
		}
		return $response;
	}

	protected function processResponse($response)
	{
		$response = $this->decodeResponse($response);
		$this->checkResponseContent($response);
		return $response;
	}

	protected function updateConfig($feedConfig,$taxonomyConfig)
	{
		if(empty($feedConfig))
			throw new Exception($this->__("Feeds configuration empty"));

		// call model and update tables
		Mage::getSingleton('aukro/feed')->updateFeeds($feedConfig);
		Mage::getSingleton('aukro/taxonomy_setup')->updateTaxonomies($taxonomyConfig);
		Mage::helper('aukro/data_profile')->updateProfilesFeedConfig();
		return $this->getLinks($feedConfig);
	}

	protected function updateInfo($info)
	{
		if(empty($info))
			return;

		$pluginInfo = array();
		if(isset($info[self::RESPONSE_PLUGIN]))
			$pluginInfo = $info[self::RESPONSE_PLUGIN];

		$moduleInfo = array();
		if(isset($info[self::RESPONSE_MODULE]))
			$moduleInfo = $info[self::RESPONSE_MODULE];

		Mage::getSingleton('aukro/plugin')->updatePluginInfo($pluginInfo);
		Mage::helper('aukro/version')->processModuleInfo($moduleInfo);
		return;
	}

	protected function updateLicenseData($lincenseData)
	{
		if(empty($lincenseData))
			return;
		Mage::helper('aukro/version')->processLicenseData($lincenseData);
	}

	protected function checkResponseContent($response)
	{
		$error = "";
		if(!empty($response[self::RESPONSE_ERROR]))
		{
			throw new Exception($response[self::RESPONSE_ERROR]);
		}
		else if(!empty($response[self::RESPONSE_ERRORS]))
		{
			throw new Exception(implode(", ", $response[self::RESPONSE_ERRORS]));
		}
	}

	protected $_xdfsdfskltyllk = "du45itg6df4kguyk";

	protected function checkResponseEmpty($response,$error)
	{
		if(!isset($response) || empty($response))
		{
			throw new Exception($this->__("Invalid or empty server response.").$this->__("Curl error: ").$error);
		}
	}

	protected function getLinks($feedConfig)
	{
		$links = array();
		foreach($feedConfig as $config)
		{
			if(isset($config[self::PARAM_LINK]) && !in_array($config[self::PARAM_LINK],$links))
				$links[] = $config[self::PARAM_LINK];
		}
		return $links;
	}

	protected $_xdfsdfskmfowlt54b4 = "kd6fg54";

	protected function decodeResponse($response)
	{
		$response = json_decode($response,true);
		return $response;
	}

	protected function getServerName()
	{
		return $this->versionHelper()->getServerName();
	}
	
	protected function getServerId()
	{
		return $this->versionHelper()->getServerId();
	}

	protected function checkLicense()
	{
		if(!$this->versionHelper()->isLicenseValid())
		{
			throw new Exception($this->__('Your License is not valid'));
		}
	}

	protected function getSign($server,$license)
	{
		return md5(sha1($this->_xdfsdfskltyllk.$server.$license."\$this->_xdfsdfskmfowlt54b4"));
	}

	protected function getNosressServerUrl($secured = true)
	{
		if($secured)
			return $this->getGeneralConfig(self::PARAM_CP_URL_SECURE);
		else
			return $this->getGeneralConfig(self::PARAM_CP_URL_UNSECURE);
	}
	
	protected function getKoongoApiUrl($secured = true)
	{
		if($secured)
			return $this->getGeneralConfig(self::PARAM_API_URL_SECURE);
		else
			return $this->getGeneralConfig(self::PARAM_API_URL_UNSECURE);
	}

	protected function postUrlRequest($request_url,$post_params)
	{
		$ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $request_url);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_POST, true);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_params);
	    $result = curl_exec($ch);
	    $this->checkResponseEmpty($result, curl_error($ch));
	    curl_close($ch);

	    return $result;
	}
	
	protected function postJsonUrlRequest($request_url,$post_params)
	{
		$post_params = json_encode($post_params);
		return $this->postUrlRequest($request_url,$post_params);
	}

	protected function  versionHelper()
	{
		if(!isset($this->_versionHelper))
		{
			$this->_versionHelper = Mage::helper('aukro/version');
		}
		return $this->_versionHelper;
	}

	protected function getInfoData($type,$cacheKey)
	{
		$cache = Mage::app()->getCache();
		$json = $cache->load($cacheKey);
		if(empty($json))
		{
			$json = $this->_getInfoData($type);
			if(!empty($json))
				$cache->save($json, $cacheKey);
		}
		$collections = $this->decodeResponse($json);
		return $collections;
	}
	
	protected function _getInfoData($type,$decode = false)
	{
		$url = $this->getGeneralConfig($type);
		$data = Mage::getModel('aukro/data_reader')->getRemoteFileContent($url);
		
		if($decode)
			$data = $this->decodeResponse($data);
		return $data;
	}
}