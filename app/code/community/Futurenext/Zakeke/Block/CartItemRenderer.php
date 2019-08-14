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
 * Zakeke cart item render block
 *
 * @category    Futurenext
 * @package     Futurenext_Zakeke
 */
class Futurenext_Zakeke_Block_CartItemRenderer extends Mage_Checkout_Block_Cart_Item_Renderer
{
    /**
     * Get the Zakeke design previews
     *
     * @throws Exception
     * @return array|false
     */
    public function getZakekePreviews()
    {
        try {
            $product = $this->getProduct();

            $additionalOption = $product->getCustomOption('additional_options');
            if (!$additionalOption) {
                return false;
            }

            $options = (array)unserialize($additionalOption->getValue());
            $option = Futurenext_Zakeke_Helper_Data::getZakekeOption($options);
            if (!$option) {
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
     * Get list of all options for product
     *
     * @return array
     */
    public function getOptionList()
    {
        return $this->getProductOptions();
    }

    /**
     * Get item configure url
     *
     * @return string
     */
    public function getConfigureUrl()
    {
        $item = $this->getItem();
        $buyRequest = $item->getOptionByCode('info_buyRequest');
        $buyRequestParams = (array)unserialize($buyRequest->getValue());
        $buyRequestParams['id'] = $item->getId();
        unset($buyRequestParams['zakeke-token']);
        unset($buyRequestParams['zakeke-pricing']);
	    unset($buyRequestParams['form_key']);

        return $this->getUrl('ProductDesigner/Customize/Configure') . '?' . http_build_query($buyRequestParams);
    }
}
