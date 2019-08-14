<?php
class Nostress_Aukro_Block_Payment_Info extends Mage_Payment_Block_Info
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('nostress_aukro/payment/info.phtml');
    }
}