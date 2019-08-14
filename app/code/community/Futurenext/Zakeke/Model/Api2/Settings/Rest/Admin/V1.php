<?php
/*******************************************************
 * Copyright (C) 2017 Zakeke
 *
 * This file is part of Zakeke.
 *
 * Zakeke can not be copied and/or distributed without the express
 * permission of Zakeke
 *******************************************************/


/**
 * Zakeke Magento REST API for retrieving a subset of the store settings
 *
 * @category    Futurenext
 * @package     Futurenext_Zakeke
 */
class Futurenext_Zakeke_Model_Api2_Settings_Rest_Admin_V1 extends Futurenext_Zakeke_Model_Api2_Settings
{
    /**
     * Retrieve a subset of the store settings
     *
     * @return array
     */
    public function _retrieve() {
        $data = array();
        $data['locale'] = Mage::app()->getLocale()->getLocaleCode();
        $data['base_currency_code'] = Mage::app()->getStore()->getBaseCurrencyCode();
        $data['media_url'] = Mage::getBaseUrl('media');
        return $data;
    }
}