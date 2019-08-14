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
 * Zakeke model for keeping track of customizable products
 *
 * @category    Futurenext
 * @package     Futurenext_Zakeke
 *
 * @method $this setSku(string $sku)
 */
class Futurenext_Zakeke_Model_ZakekeEnabled extends Mage_Core_Model_Abstract
{
    protected $_eventPrefix = 'futurenext_zakeke_zakekeEnabled';
    protected $_eventObject = 'zakekeEnabled';

    protected function _construct()
    {
        $this->_init('futurenext_zakeke/zakekeEnabled');
    }

    /**
     * Get ZakekeEnabled identifier from SKU.
     *
     * @param string $sku
     * @return string
     */
    public function getIdFromSku($sku)
    {
        /** @var Futurenext_Zakeke_Model_Resource_ZakekeEnabled $resource */
        $resource = $this->getResource();
        return $resource->getIdFromSku($sku);
    }
}