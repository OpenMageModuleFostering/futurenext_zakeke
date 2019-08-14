<?php
/*******************************************************
 * Copyright (C) 2016 FutureNext SRL
 *
 * This file is part of Zakeke.
 *
 * Zakeke can not be copied and/or distributed without the express
 * permission of FutureNext SRL
 *******************************************************/


/**
 * Zakeke ZakekeEnabled collection
 *
 * @category    Futurenext
 * @package     Futurenext_Zakeke
 */
class Futurenext_Zakeke_Model_Resource_ZakekeEnabled_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('futurenext_zakeke/zakekeEnabled');
    }
}