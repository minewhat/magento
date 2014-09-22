<?php

class MineWhat_Insights_Model_Observer
{
  public function logCartAdd($observer) {

    if (!$observer->getQuoteItem()->getProduct()->getId()) {
      return;
    }

    $product = $observer->getProduct();
    $id = $observer->getQuoteItem()->getProduct()->getId();
    $bundle = array();

    if($product->getTypeId() == 'bundle') {

      $id = $product->getId();
      $optionCollection = $product->getTypeInstance()->getOptionsCollection();
      $selectionCollection = $product->getTypeInstance()->getSelectionsCollection($product->getTypeInstance()->getOptionsIds());
      $options = $optionCollection->appendSelections($selectionCollection);
      foreach( $options as $option )
      {
        $_selections = $option->getSelections();
        foreach( $_selections as $selection )
        {
          $bundleItem = array();
          $bundleItem['pid'] = $selection->getId();
          $bundleItem['sku'] = $selection->getSku();
          $bundleItem['price'] = $selection->getPrice();
          $bundle[] = $bundleItem;
        }
      }

    }

    $parentId = '';
    $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($id);
    if($parentIds != null && count($parentIds) > 0) {
      $parentId = $parentIds[0];
    }
    Mage::getModel('core/session')->setProductToShoppingCart(
      array(
        'id' => $id,
        'sku' => $product->getSku(),
        'parentId' => $parentId,
        'qty' => $product->getQty(),
        'bundle' => $bundle
      )
    );

  }
}