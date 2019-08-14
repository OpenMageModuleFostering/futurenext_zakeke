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
 * Zakeke checkout item render block
 *
 * @category    Futurenext
 * @package     Futurenext_Zakeke
 */
class Futurenext_Zakeke_Block_CheckoutItemRenderer extends Mage_Checkout_Block_Cart_Item_Renderer
{
    /**
     * Get product customize options
     *
     * @return array|false
     */
    public function getProductOptions()
    {
        /* @var Mage_Catalog_Helper_Product_Configuration $helper */
        $helper = Mage::helper('catalog/product_configuration');

        $product = $this->getProduct();
        $typeId = $product->getTypeId();
        $options = null;
        if ($typeId == Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE) {
            $options = $helper->getConfigurableOptions($this->getItem());
        } else {
            $options = $helper->getCustomOptions($this->getItem());
        }

        return $options;
    }

    /**
     * Get the Zakeke design preview files
     *
     * @throws Exception
     * @return int|false
     */
    public function getZakekeDesignId()
    {
        try {
            $additionalOptions = $this->getItem()->getOptionByCode('additional_options');
            if (!$additionalOptions) {
                return false;
            }

            $options = (array)unserialize($additionalOptions->getValue());
            $option = Futurenext_Zakeke_Helper_Data::getZakekeOption($options);
            if ($option === false) {
                return false;
            }

            return $option['design'];
        } catch (Exception $e) {
            Mage::logException($e);
            throw $e;
        }
    }

    /**
     * Get the Zakeke design preview files
     *
     * @throws Exception
     * @return array|false
     */
    public function getZakekePreviews()
    {
        try {
            $additionalOptions = $this->getItem()->getOptionByCode('additional_options');
            if (!$additionalOptions) {
                return false;
            }

            $options = (array)unserialize($additionalOptions->getValue());
            $option = Futurenext_Zakeke_Helper_Data::getZakekeOption($options);
            if ($option === false) {
                return false;
            }

            $previews = array();
            if (isset($option['previews'])) {
                $previews = $option['previews'];
            }
            return $previews;
        } catch (Exception $e) {
            Mage::logException($e);
            throw $e;
        }
    }
}