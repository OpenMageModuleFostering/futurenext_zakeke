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
 * Zakeke module checker block
 *
 * @category    Futurenext
 * @package     Futurenext_Zakeke
 */
class Futurenext_Zakeke_Block_ModuleChecker extends Mage_Core_Block_Template
{
    /**
     * Get the list of incompatible modules
     *
     * @return Varien_Object[]
     */
    public function getModuleList()
    {
        return Mage::registry(
            Futurenext_Zakeke_Helper_Data::ZAKEKE_MODULE_LIST_REGISTRY
        );
    }

    /**
     * Check if Zakeke is reachable from the shop server
     *
     * @return bool
     */
    public function getIsZakekeReachable()
    {
        /** @var Futurenext_Zakeke_Helper_ZakekeApi $zakekeApi */
        $zakekeApi = Mage::helper('futurenext_zakeke/zakekeApi');
        return $zakekeApi->isReachable();
    }
}