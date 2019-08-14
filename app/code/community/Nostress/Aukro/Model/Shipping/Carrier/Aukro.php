<?php

/**
* Our test shipping method module adapter
*/
class Nostress_Aukro_Model_Shipping_Carrier_Aukro extends Mage_Shipping_Model_Carrier_Abstract
    implements Mage_Shipping_Model_Carrier_Interface
{
	/**
	 * unique internal shipping method identifier
	 *
	 * @var string [a-z0-9_]
	 */
  	protected $_code = 'aukro';
  	protected $_isFixed = true;
  	
	/**
     * Enter description here...
     *
     * @param Mage_Shipping_Model_Rate_Request $data
     * @return Mage_Shipping_Model_Rate_Result
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $result = Mage::getModel('shipping/rate_result');
        $shippingData = $request->getFreeShipping();
        $request->setFreeShipping( false);
        $method = Mage::getModel('shipping/rate_result_method');

        $method->setCarrier('aukro');
        $method->setCarrierTitle($this->getConfigData('title'));

        $method->setMethod('aukro');
        $method->setMethodTitle( $shippingData['label']);

        $method->setPrice( $shippingData['fee']);
        $method->setCost( $shippingData['fee']);

        $result->append($method);

        return $result;
    }
  
	public function getAllowedMethods()
    {
        return array('aukro'=>$this->getConfigData('name'));
    }
}