<?php
class Nostress_Aukro_Model_Product extends Nostress_Aukro_Model_Abstract
{
    public function _construct()
    {
		parent::_construct ();
        $this->_init('aukro/product');
    }
    
    public function create( $aukroID, $productID) {
        
        $collection = $this->getCollectionByProductID($productID);
        foreach( $collection as $item) {
            $item->current = 0;
            if( empty($item->ended_at)) {
                $item->ended_at = new Zend_Db_Expr( 'NOW()');
            }
            $item->save();
        }
        
        $product = Mage::getModel( 'aukro/product')->load( $aukroID, 'aukro_id');
        if( !$product->getId()) {
            $this->product_id = $productID;
            $this->aukro_id = $aukroID;
            $this->created_at = new Zend_Db_Expr( 'NOW()');
            $this->current = 1;
            $this->save();
        }
    }
    
    public function getCollectionByProductID( $productID) {
        
        $collection = $this->getCollection();
        $collection->getSelect()->where( 'product_id = ?', $productID);
        $collection->load();
        
        return $collection;
    }
    
    public function finish( $aukroID) {
        
        $product = Mage::getModel( 'aukro/product')->load( $aukroID, 'aukro_id');
        if( $product->getId()) {
            $product->ended_at = new Zend_Db_Expr( 'NOW()');
            $product->current = 0;
            $product->save();
        }
    }
}