<?php

/**
 * @category    MineWhat
 * @package     MineWhat_Insights
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MineWhat_Insights_Block_Base_Script extends Mage_Core_Block_Template {

    protected function _construct() {
        parent::_construct();
        $this->setTemplate('minewhat/insights/base/script.phtml');
    }

    protected function _toHtml() {
        if (!$this->helper('minewhat_insights')->isModuleOutputEnabled()) {
            return '';
        }
        return parent::_toHtml();
    }
    
    public function getUser() {
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
          $user = Mage::getSingleton('customer/session')->getCustomer();
          return $user;
        }
        return null;
    }

}
