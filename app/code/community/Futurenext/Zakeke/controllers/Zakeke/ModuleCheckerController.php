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
 * Zakeke controller for module checker
 *
 * @category    Futurenext
 * @package     Futurenext_Zakeke
 */
class Futurenext_Zakeke_Zakeke_ModuleCheckerController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Show if the Zakeke module is ready for the store
     */
    public function indexAction()
    {
        /** @var Futurenext_Zakeke_Model_ModuleList $moduleListHelper */
        $moduleListHelper = Mage::getSingleton('futurenext_zakeke/moduleList');
        $moduleListHelper->gather();
        $moduleList = $moduleListHelper->getList();
        /** @var Futurenext_Zakeke_Helper_ZakekeApi $zakekeApi */
        $zakekeApi = Mage::helper('futurenext_zakeke/zakekeApi');
        try {
            $completeModuleList = $zakekeApi->moduleChecker($moduleList);
        } catch (Exception $e) {
            $completeModuleList = false;
        }
        Mage::register(
            Futurenext_Zakeke_Helper_Data::ZAKEKE_MODULE_LIST_REGISTRY,
            $completeModuleList
        );

        $this->loadLayout()
            ->_title('Zakeke Readiness Check')
            ->_setActiveMenu('futurenext_zakeke/moduleChecker')
            ->_addBreadcrumb($this->__('Zakeke'), $this->__('Readiness Check'));
        $this->renderLayout();
    }
}