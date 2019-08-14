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
 * Zakeke ZakekeEnabled resource
 *
 * @category    Futurenext
 * @package     Futurenext_Zakeke
 */
class Futurenext_Zakeke_Model_Resource_ZakekeEnabled extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('futurenext_zakeke/zakekeEnabled', 'entity_id');
    }

    /**
     * Get ZakekeEnabled identifier from SKU.
     *
     * @param string $sku
     * @return string
     */
    public function getIdFromSku($sku)
    {
        $connection = $this->_getConnection('read');
        $select = $connection->select()->from($this->getMainTable(), 'entity_id')->where('sku = :sku');
        $bind = array(':sku' => (string) $sku);
        return $connection->fetchOne($select, $bind);
    }
}