<?php

/**
 * @category    MineWhat
 * @package     MineWhat_Insights
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MineWhat_Insights_Helper_Data extends Mage_Core_Helper_Data {

  const CONFIG_ACTIVE = 'minewhat_insights/settings/active';
  const CONFIG_BASE_SCRIPT = 'minewhat_insights/settings/base_script';

  public function isModuleEnabled($moduleName = null) {
    if (Mage::getStoreConfig(self::CONFIG_ACTIVE) == '0') {
      return false;
    }

    return parent::isModuleEnabled($moduleName = null);
  }

  public function getBaseScript($store = null) {
    return Mage::getStoreConfig(self::CONFIG_BASE_SCRIPT, $store);
  }

}