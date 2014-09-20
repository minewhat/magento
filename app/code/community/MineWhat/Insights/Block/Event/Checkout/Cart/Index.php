<?php

/**
 * @category    MineWhat
 * @package     MineWhat_Insights
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MineWhat_Insights_Block_Event_Checkout_Cart_Index extends Mage_Core_Block_Template {

	protected function _construct() {
		parent::_construct();
		$this->setTemplate('minewhat/insights/event/checkout/cart/index.phtml');
	}

	public function getProductToShoppingCart() {
		if (($product = Mage::getModel('core/session')->getProductToShoppingCart())) {
			    Mage::getModel('core/session')->unsProductToShoppingCart();
			    return $product;
		}

		return null;
	}

	protected function _toHtml() {
		if (!$this->helper('minewhat_insights')->isModuleOutputEnabled()) {
		    return '';
		}
		return parent::_toHtml();
    	}

}
