<?php

class Nostress_Aukro_Model_Payment extends Mage_Payment_Model_Method_Abstract
{
    protected $_code  = 'aukro';
    protected $_canCapture = true;
    protected $_canUseCheckout = false;
    protected $_canManageRecurringProfiles = false;
    
    protected $_infoBlockType = 'aukro/payment_info';
}
