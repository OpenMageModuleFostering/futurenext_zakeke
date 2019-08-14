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
 * Zakeke base block
 *
 * @category    Futurenext
 * @package     Futurenext_Zakeke
 */
class Futurenext_Zakeke_Block_View extends Mage_Core_Block_Template
{
    /**
     * Get the current product
     *
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct()
    {
        $product = Mage::registry('product');
        return $product;
    }

    /**
     * Return whether the current product is customizable with Zakeke
     *
     * @return bool
     */
    public function isProductCustomizable()
    {
        $product = $this->getProduct();

        /** @var Futurenext_Zakeke_Helper_Data $zakekeHelper */
        $zakekeHelper = Mage::helper('futurenext_zakeke');
        return $zakekeHelper->isProductCustomizable($product);
    }

    /**
     * Get the Zakeke product option
     *
     * @return array|null
     */
    protected function getZakekeOption()
    {
        $option = Mage::registry(
            Futurenext_Zakeke_Helper_Data::ZAKEKE_OPTION_REGISTRY
        );
        return $option;
    }

    /**
     * Retrieve Session Form Key
     *
     * @return string
     */
    public function getFormKey()
    {
        /** @var Mage_Core_Model_Session $session */
        $session = Mage::getSingleton('core/session');
        return $session->getFormKey();
    }
}