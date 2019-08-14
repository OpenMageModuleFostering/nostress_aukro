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

class Nostress_Aukro_Model_Webapi_Order extends Nostress_Aukro_Model_Webapi_Abstract
{
    const CMD_DO_GET_SITE_JOURNAL_DEALS = 'doGetSiteJournalDeals'; // vrati seznam objednavek z aukra
    
    const ORDER_TYPE_BUY = 1;
    const ORDER_TYPE_SHIPPING = 2;
    const ORDER_TYPE_SHIPPING_CANCELED = 3;
    const ORDER_TYPE_PAYU = 4;
    
    const ORDER_STATUS_PENDING = 'pending';
    const ORDER_STATUS_UNFIHISHED = 'aukro_unfinished';
    
    const ORDER_PAYMENT_TYPE_WIRE_TRANSFER = 'wire_transfer';
    const ORDER_PAYMENT_TYPE_COLLECT_ON_DELIVERY = 'collect_on_delivery';
    const ORDER_PAYMENT_TYPE_CART = 'aukro';
    
    protected function _getAukroOrders() {
        
        // objednavky z aukra
        $events = $this->_getSiteJournalDeals();
        
        if( !is_array( $events)) {
            return false;
        }
        
        // seskupeni udalosti podle dealID
        $ordersByDealID = array();
        foreach( $events['siteJournalDeals']['item'] as $event) {
            $event = (array) $event;
            $dealID = $event['dealId'];
            if( !isset( $ordersByDealID[$dealID])) {
                $event['tmp_types'] = array();
                $event['transaction_ids'] = array();
                $ordersByDealID[$dealID] = $event;
            }
            $ordersByDealID[$dealID]['tmp_types'][] = $event['dealEventType'];
            if( !empty($event['dealTransactionId']) && !in_array( $event['dealTransactionId'], $ordersByDealID[$dealID]['transaction_ids'])) {
                $ordersByDealID[$dealID]['transaction_ids'][] = $event['dealTransactionId'];
            }
        }
        
        // seskupeni podle dealTransactionId
        $orders = array();
        
        foreach( $ordersByDealID as $dealID => $order) {
            $transID = count($order['transaction_ids']) ? $order['transaction_ids'][0] : $dealID;
            if( !isset( $orders[$transID])) {
                $order['dealIDs'] = array();
                $order['items'] = array();
                $order['types'] = array();
                $orders[$transID] = $order;
            }
            $orders[$transID]['dealIDs'][] = $dealID;
            $orders[$transID]['types'] = array_merge( $orders[$transID]['types'], $order['tmp_types']);
            $orders[$transID]['items'][] = array(
                'item_id' => $order['dealItemId'],
                'qty' => $order['dealQuantity'],
            );
        }
        
        return $orders;
    }
    
    public function refreshOrders() {
        
        $orders = $this->_getAukroOrders();
        if( !$orders) {
            return false;
        }
        
        // ziskani ulozenych aukro objednavek
        $orderCollection = Mage::getModel( 'sales/order')->getCollection();
        $orderCollection->getSelect()->where( 'aukro_order_id IS NOT NULL');
        $orderCollection->load();
        $salesOrders = array();
        foreach( $orderCollection as $order) {
            $salesOrders[$order->aukro_order_id] = $order->toArray( array( 'entity_id', 'state', 'status'));
        }
        
        // kontrola existence a stavu objednavek a polozek v nich
        $auctionIDs = array();
        $newOrders = array();
        $transactionsIDsForBuyForm = array();
        
        foreach( $orders as $orderID => $order) {
            
            $checkProduct = true;
            
            $order['aukro_order_id'] = $orderID;// pridani stavu objednavky
            $order['paid'] = false;
            $orderSalesStatus = isset( $order['sales_order']['status']) ? $order['sales_order']['status'] : null;
            
            // objednavky bez formulare prepravy starsi nez 3 hodiny pustime dal s konecnym stavem unfinisned
            // jinak objednavky ignoruje dokud se formular neobjevi, max do 1 hodiny
            if( !in_array( self::ORDER_TYPE_SHIPPING, $order['types'])) {
                $dateNow = new Zend_Date();
                $measure = new Zend_Measure_Time($dateNow->sub( new Zend_Date($order['dealEventTime']))->toValue(), Zend_Measure_Time::SECOND);
                $measure->convertTo(Zend_Measure_Time::HOUR);
                if($measure->getValue() < 3) {
                    continue;
                    // pokud je objednavka starsi nez 3 hodiny a stale neni formular prepravy, tak uz nebude
                } else {
                    $order['status'] = self::ORDER_STATUS_UNFIHISHED;
                    $order['state_comment'] = Mage::helper('aukro')->__('Shipping form was not filled in. Contact customer!');
                }
            }
            
            $shipping = false;
            // cyklus pres stavy
            foreach( $order['types'] as $type) {
                switch( $type) {
                    case self::ORDER_TYPE_SHIPPING:
                        $shipping = true;
                        unset($order['status'], $order['state_comment']);
                        break;
                    case self::ORDER_TYPE_SHIPPING_CANCELED:
                        $shipping = false;
                        $order['status'] = self::ORDER_STATUS_UNFIHISHED;
                        $order['state_comment'] = Mage::helper('aukro')->__('Shipping form was canceled by customer.');
                        break;
                }
            }
            
            // formular prepravy vyplnen
            if( $shipping && count($order['transaction_ids'])) {
                $transactionsIDsForBuyForm[] = end($order['transaction_ids']);
            }
            
            // kontrola opozdeneho zaplaceni objednavky
            // objednavka uz existuje
            if( isset( $salesOrders[$order['aukro_order_id']])) {
                // aukro objednavka zaplacena pres PAYU
                if( in_array( self::ORDER_TYPE_PAYU, $order['types'])) {
                    $order['sales_order'] = $salesOrders[$order['aukro_order_id']];
                    $order['paid'] = true;
                    $checkProduct = false;
                    // pokud je objednavka jiz zaplacena, tak ji ignorujeme
                    if( $order['sales_order']['state'] != Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) {
                        continue;
                    }
                // pokud objednavka neni dodatecne zaplacena, ale existuje, tak ji ignorujeme
                } else {
                    continue;
                }
            }
            
            if( $checkProduct) {
                // kontrola existence produktu aukce. POkud neexistuje, aukce se ignoruje
                $productExists = true;
                foreach( $order['items'] as &$auctionData) {
                    $auctionID = $auctionData['item_id'];
                    $_product = Mage::getModel( 'catalog/product')->loadByAttribute( 'aukro_product_id', $auctionID);
                    if( $_product === false) {
                        // zkusime produkt najit v historii
                        $_aukroProduct = Mage::getModel( 'aukro/product')->load( $auctionID, 'aukro_id');
                        if( $_aukroProduct->getId() ) {
                            // podle aukro_id z historie najdeme product
                            $_product = Mage::getModel( 'catalog/product')->load( $_aukroProduct->getProductId());
                            // v historii je, ale product neexistuje. To by se asi nemelo stat
                            if( !$_product || !$_product->getId()) {
                                $productExists = false;
                                break;
                            }
                        // neni v historii
                        } else {
                            $productExists = false;
                            break;
                        }
                    }
                    $auctionData['productId'] = $_product->getId();
                    
                    // shlukovani ID aukci pro ziskani informaci o kontaktech
                    if( !in_array( $auctionID, $auctionIDs)) {
                        $auctionIDs[] = $auctionID;
                    }
                }
                unset( $auctionData);
            
                if( $productExists === false) {
                    continue;
                }
            }
            
            $newOrders[$orderID] = $order;
        }
        
        $countNew = $countPaid = 0;
        if( count( $newOrders)) {
            // zjisteni informaci z formulare prepravy
            if( count( $transactionsIDsForBuyForm)) {
                $shippingAddresses = $this->_getShippingAddresses( $transactionsIDsForBuyForm);
                foreach( $newOrders as $orderID => &$order) {
                    foreach( $order['transaction_ids'] as $transID) {
                        if( isset( $shippingAddresses[ $transID])) {
                            $order['shipping'] = $shippingAddresses[ $transID];
                        }
                    }
                }
            }
            
            // zjisteni informaci z uctu kupujicich
            if( count( $auctionIDs)) {
                // zjisteni informaci o kupujicich
                $contacts = $this->_doMyContact( $auctionIDs);
                // pridani informaci do objednavek
                foreach( $newOrders as &$order) {
                    $order['customer'] = $contacts[ $order['dealBuyerId']];
                }
            }
            
            // ulozeni novych objednavek
            foreach( $newOrders as $orderID => $newOrder) {
                try {
                    if( $newOrder['paid']) {
                        $prefixMessage = $this->helper()->__("Error in paying order: ");
                        $this->_payOrder( $newOrder);
                        $countPaid++;
                    } else {
                        $prefixMessage = $this->helper()->__("Error in creating order: ");
                        $orderInfo = $this->_prepareOrderInfo($newOrder);
                        if( $orderInfo !== false) {
                            $this->_createOrder( $orderInfo);
                            $countNew++;
                        }
                    }
                } catch( Exception $e) {
                    $this->_getSession()->addError( $prefixMessage.$e->getMessage());
                }
            }
        }
                   
        return array( $countNew, $countPaid);
    }
    
    protected function _getPayment( $code, $itemIds) {
        
        $payment = array();
        $storeId = Mage::app()->getStore()->getId();
        $config = Mage::getStoreConfig ( 'aukro', $storeId);
        $method = isset($config['shipping_and_payment'][$code]) ? $config['shipping_and_payment'][$code] : null;
        $payment['method'] = $method;

        // metoda nebyla nalezena v konfiguraci
        if( empty( $method)) {
            // misconfiguration
            if( $code == self::ORDER_PAYMENT_TYPE_COLLECT_ON_DELIVERY || $code == self::ORDER_PAYMENT_TYPE_WIRE_TRANSFER) {
                throw new Zend_Exception( "Payment type '$code' has not specify payment method. Please specify '$code' payment method in configuration!");
            // platba kartou nebo prevodem v hotovosti
            } else {
                $aukroPaymentMethods = $this->_doGetPaymentMethods($itemIds);
                foreach( $aukroPaymentMethods as $aukroMethod) {
                    if( $aukroMethod['paymentMethodId'] == $code) {
                        $payment['method_name'] = $aukroMethod['paymentMethodName'];
                        $payment['method'] = self::ORDER_PAYMENT_TYPE_CART;
                        return $payment;
                    }
                }
                throw new Zend_Exception( $this->_helper->__("Unknow payment method '%s'!", $code));
            }
        }
        
        return $payment;
    }
    
    protected function _doGetPaymentMethods( $itemIds) {
        
        $params = array('sessionId' => $this->_session_id, 'itemIds'=>$itemIds);
        $methods = $this->soapCall('doGetPaymentMethods',$params);
        
        if(isset( $methods['paymentMethods']['item'])) {
            return $this->helper()->formatData($methods['paymentMethods']['item']);
        } else {
            return false;
        }
        
    }
    
    protected function _prepareOrderInfo( $order) {
        
        if( !isset( $order['customer']) || !count( $order['items'])) {
            return false;
        }
        
        $date = new Zend_Date($order['dealEventTime']);
        $orderInfo = array(
            'created_at' => $date->get( 'y-MM-dd HH:mm:ss'),
            'aukro_order_id' => $order['aukro_order_id'],
            'customer_email' => $order['customer']['contactEmail'],
            'customer_firstname' => $order['customer']['contactFirstName'],
            'customer_lastname' => $order['customer']['contactLastName'],
            'customer_note' => isset( $order['shipping']['note']) ? $order['shipping']['note'] : null,
            'items' => array(),
            'billing_address' => array(
                'firstname'  => $order['customer']['contactFirstName'],
                'lastname'   => $order['customer']['contactLastName'],
                'street'     => $order['customer']['contactStreet'],
                'city'       => $order['customer']['contactCity'],
                'country_id' => 'CZ',
                'postcode'   => $order['customer']['contactPostcode'],
                'telephone'  => $order['customer']['contactPhone'],
                'company' => $order['customer']['contactCompany'],
            ),
            'payment' => array(
                'method'    => 'checkmo',
            ),
            'status' => isset( $order['status']) ? $order['status'] : true,
            'state_comment' => isset( $order['state_comment']) ? $order['state_comment'] : "",
            'is_paid' => in_array( self::ORDER_TYPE_PAYU, $order['types'])
        );
        
        $itemIds = array();
        foreach( $order['items'] as $item) {
            $orderInfo['items'][$item['productId']] = $item['qty'];
            $itemIds[] = $item['item_id'];
        }
        
        if( isset($order['shipping'])) {
            
            $orderInfo['shipping_method'] = 'aukro_aukro';
            $orderInfo['shipping_method_data'] = $order['shipping']['method'];
            
            $orderInfo['payment'] = $this->_getPayment( $order['shipping']['payment_type'], $itemIds);

            // rozdeleni jmena na jmeno a prijmeni
            $fullname = $order['shipping']['address']['postBuyFormAdrFullName'];
            $names = explode(' ', $fullname);
            $firstname = $lastname = "";
            if( $names > 0) {
                $firstname = $names[0];
                unset( $names[0]);
            }
            if( $names > 0) {
                $lastname = implode( ' ', $names);
            }
            $orderInfo['shipping_address'] = array(
                    'firstname'  => $firstname,
                    'lastname'   => $lastname,
                    'street'     => $order['shipping']['address']['postBuyFormAdrStreet'],
                    'city'       => $order['shipping']['address']['postBuyFormAdrCity'],
                    'country_id' => 'CZ',
                    'postcode'   => $order['shipping']['address']['postBuyFormAdrPostcode'],
                    'telephone'  => $order['shipping']['address']['postBuyFormAdrPhone'],
                    'company'    => $order['shipping']['address']['postBuyFormAdrCompany'],
            );
        } else {
            $orderInfo['shipping_address'] = array(
                    'firstname'  => $order['customer']['contactFirstName'],
                    'lastname'   => $order['customer']['contactLastName'],
                    'street'     => $order['customer']['contactStreet'],
                    'city'       => $order['customer']['contactCity'],
                    'country_id' => 'CZ',
                    'postcode'   => $order['customer']['contactPostcode'],
                    'telephone'  => $order['customer']['contactPhone'],
                    'company'    => $order['customer']['contactCompany'],
            );
        }
        
        return $orderInfo;
    }
    
    protected function _createOrder( $orderData) {
        
        $billingAddress = $orderData['billing_address'];
        $shippingAddress = $orderData['shipping_address'];
        
        $quote = Mage::getModel('sales/quote');
        
        $customer = Mage::getModel('customer/customer');
        $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
        $customer->loadByEmail($orderData['customer_email']);
        
        if($customer->getId()){
            $quote->assignCustomer($customer);
            $quote->setCustomerNote($orderData['customer_note']);
        }
        else
        {
            $quote->setIsMultiShipping(false);
            $quote->setCheckoutMethod('guest');
            $quote->setCustomerId(null);
            $quote->setCustomerEmail($orderData['customer_email']);
            $quote->setCustomerFirstname($orderData['customer_firstname']);
            $quote->setCustomerLastname($orderData['customer_lastname']);
            $quote->setCustomerNote($orderData['customer_note']);
            $quote->setCustomerIsGuest(true);
            $quote->setCustomerGroupId(Mage_Customer_Model_Group::NOT_LOGGED_IN_ID);
        }
        
        $quote->setStore(Mage::app()->getStore());
        $quote->setCreatedAt( $orderData['created_at']);
        
        $product = Mage::getModel('catalog/product');
        foreach($orderData['items'] as $itemID => $qty) {
            
            $product->load( $itemID);
            $quoteItem = Mage::getModel('sales/quote_item')->setProduct($product);
            $quoteItem->setQuote($quote);
            $quoteItem->setQty($qty);
            $quote->addItem($quoteItem);
        }
        $addressForm = Mage::getModel('customer/form');
        $addressForm->setFormCode('customer_address_edit')
                    ->setEntityType('customer_address');
        
        foreach ($addressForm->getAttributes() as $attribute) {
            if (isset($shippingAddress[$attribute->getAttributeCode()])) {
                $quote->getShippingAddress()->setData($attribute->getAttributeCode(), $shippingAddress[$attribute->getAttributeCode()]);
            }
        }
        
        foreach ($addressForm->getAttributes() as $attribute) {
            if (isset($billingAddress[$attribute->getAttributeCode()])) {
                $quote->getBillingAddress()->setData($attribute->getAttributeCode(), $billingAddress[$attribute->getAttributeCode()]);
            }
        }
        
        if( isset($orderData['shipping_method'])) {
            $quote->getShippingAddress()->setShippingMethod($orderData['shipping_method']);
            $quote->getShippingAddress()->setFreeShipping( $orderData['shipping_method_data']);
            $quote->getShippingAddress()->setCollectShippingRates(true);
            $quote->getShippingAddress()->collectShippingRates();
        }
        
        $quote->collectTotals();
        $quote->save();
        
        $items = $quote->getAllItems();
        $quote->reserveOrderId();
        
        $convertQuote = Mage::getSingleton('sales/convert_quote');
        
        // set payment
        $quotePayment = $quote->getPayment(); // Mage_Sales_Model_Quote_Payment
        $quotePayment->setMethod($orderData['payment']['method']);
        $quote->setPayment($quotePayment);
        
        $orderPayment = $convertQuote->paymentToOrderPayment($quotePayment);
        if( isset($orderData['payment']['method_name'])) {
            $orderPayment->setAdditionalInformation( 'type', $orderData['payment']['method_name']);
        }
        
        $_order = $convertQuote->addressToOrder($quote->getShippingAddress());
        $_order->setBillingAddress($convertQuote->addressToOrderAddress($quote->getBillingAddress()));
        $_order->setShippingAddress($convertQuote->addressToOrderAddress($quote->getShippingAddress()));
    
        $_order->setPayment( $orderPayment);
        
        foreach ($items as $item) {
            $orderItem = $convertQuote->itemToOrderItem($item);
            if ($item->getParentItem()) {
                $orderItem->setParentItem($_order->getItemByQuoteItemId($item->getParentItem()->getId()));
            }
            $_order->addItem($orderItem);
        }
        
        $_order->aukro_order_id = $orderData['aukro_order_id'];
        $_order->created_at = $orderData['created_at'];
        
        try {
            $_order->place();
            $_order->save();
        } catch (Exception $e){
            Mage::log($e->getMessage());
        }
        
        if( $orderData['status'] != self::ORDER_STATUS_PENDING) {
            
            $_order->setState( Mage_Sales_Model_Order::STATE_NEW, $orderData['status'], $orderData['state_comment']);
             
            try {
                $_order->save();
            } catch (Exception $e){
                Mage::log($e->getMessage());
            }
        }
        
        // platba kartou, zaplaceni objednavky a vystaveni faktury
        if( $orderData['payment']['method'] == self::ORDER_PAYMENT_TYPE_CART) {
            
            // platba kartou, zaplaceno
            if( $orderData['is_paid']) {
                $this->_paySalesOrder($_order);
            // kartou, ale nebylo zaplaceno
            } else {
                $_order->setState( Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true, $this->helper()->__('The order was NOT paid through Aukro system yet!'));
                $_order->save();
            }
        }

        return $_order;
    }
    
    protected function _paySalesOrder( $_order) {
        
        $_order->addStatusToHistory(
            $_order->getStatus(),
            $this->helper()->__('The order was paid through Aukro system!')
        );
        $_order->sendNewOrderEmail();
        
        $transactionId = $_order->getId();
        $_order->getPayment()->setTransactionId( $transactionId);
        if ($this->saveInvoice($_order)) {
            $_order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true);
        }
        $_order->save();
    }
    
    protected function _payOrder( $orderData) {
        
        $_order = Mage::getModel( 'sales/order');
        $_order->load( $orderData['sales_order']['entity_id']);
        if( !$_order->getId()) {
            throw new Exception( $this->helper()->__("Order %s does not exist!", $orderID));
        }
                
        $itemIds = array();
        foreach( $orderData['items'] as $item) {
            $itemIds[] = $item['item_id'];
        }
        $orderData['payment'] = $this->_getPayment( $orderData['shipping']['payment_type'], $itemIds);
        
        // platba kartou, zaplaceni objednavky a vystaveni faktury
        if( $orderData['payment']['method'] == self::ORDER_PAYMENT_TYPE_CART) {
            $this->_paySalesOrder($_order);
        } else {
            throw new Exception( $this->helper()->__("The order has wrong payment method during paying process! It's %s but should be %s.", $orderData['payment']['method'], self::ORDER_PAYMENT_TYPE_CART));
        }

        return $_order;
    }
    
    protected function _getSiteJournalDeals() {
        
        $params = array('sessionId' => $this->_session_id, 'journalStart' => 0);
        return $this->soapCall(self::CMD_DO_GET_SITE_JOURNAL_DEALS,$params);
    }
    
    public function getShipmentLabels() {
        
        $shipmentData = $this->getShipmentData();
        $shipmentLabels = array();
        foreach( $shipmentData['shipmentDataList']['item'] as $method) {
            $method = (array) $method;
            $shipmentLabels[ $method['shipmentId']] = $method['shipmentName'];
        }
        return $shipmentLabels;
    }
    
    protected function _getShippingAddresses( $transationIDs) {
        
        if( !is_array( $transationIDs)) {
            $transationIDs = array( $transationIDs);
        }
        
        $params = array('sessionId' => $this->_session_id, 'transactionsIdsArray' => $transationIDs);
        $output = $this->soapCall('doGetPostBuyFormsDataForSellers', $params);
        $output = $this->helper()->formatData($output['postBuyFormData']['item']);
        
        $shipmentLabels = $this->getShipmentLabels();
        
        $shipping = array();
        foreach( $output as $row) {
            $row = (array) $row;
            $shipping[ $row['postBuyFormId']] = array(
                'address'=> (array) $row['postBuyFormShipmentAddress'],
                'method' => array(
                    'fee' => $row['postBuyFormPostageAmount'],
                    'id' => $row['postBuyFormShipmentId'],
                    'label' => $shipmentLabels[ $row['postBuyFormShipmentId']]
                ),
                'payment_type' => $row['postBuyFormPayType'],
                'note' => $row["postBuyFormMsgToSeller"]
            );
        }
        return $shipping;
    }
        
    public function _doMyContact( $auctionIds) {
        
        if( !is_array( $auctionIds)) {
            $auctionIds = array( $auctionIds);
        }
        
        $params = array('sessionHandle' => $this->_session_id, 'auctionIdList' => $auctionIds, 'offset'=>0);
        $output = $this->soapCall('doMyContact', $params);
        $output = $this->helper()->formatData($output['mycontactList']['item']);
        
        $contacts = array();
        foreach( $output as $contact) {
            $contacts[ $contact['contactUserId']] = $contact;
        }
        
        return $contacts;
    }
    
    /**
     *  Save invoice for order
     *
     *  @param    Mage_Sales_Model_Order $order
     *  @return	  boolean Can save invoice or not
     */
    protected function saveInvoice (Mage_Sales_Model_Order $order)
    {
        if ($order->canInvoice()) {
            $invoice = $order->prepareInvoice();
    
            $invoice->register()->capture();
            Mage::getModel('core/resource_transaction')
            ->addObject($invoice)
            ->addObject($invoice->getOrder())
            ->save();
            return true;
        }
    
        return false;
    }
}