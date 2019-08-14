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
 * Zakeke helper
 *
 * @category    Futurenext
 * @package     Futurenext_Zakeke
 */
class Futurenext_Zakeke_Helper_Data extends Mage_Core_Helper_Abstract
{
    const ZAKEKE_BASE_URL = 'https://www.zakeke.com';
    const ZAKEKE_API_URL = 'https://api.zakeke.com';
    const ZAKEKE_REGISTRY_TOKEN = 'zakeke-auth-token';
    const ADD_TO_CART_PARAM = 'zakeke-customization';
    const ZAKEKE_DESIGN_PARAM = 'zakeke-design';
    const ZAKEKE_PRICING_PARAM = 'zakeke-pricing';
    const ZAKEKE_MODEL_PARAM = 'zakeke-model';
    const ZAKEKE_OPTION_REGISTRY = 'zakeke-option';
    const GUEST_COOKIE = 'zakeke-guest';
    const ZAKEKE_MODULE_LIST_REGISTRY = 'zakeke-module-list';
    const ZAKEKE_CART_INFO_REGISTRY = 'zakeke-cart-info';
    const ZAKEKE_PRODUCT_TYPE = 'zakeke';
    const ZAKEKE_LOG_FILE = 'zakeke.log';

    /**
     * Return whether the product is customizable with Zakeke
     *
     * @param Mage_Catalog_Model_Product $product
     * @return bool
     */
    public function isProductCustomizable($product)
    {
        $sku = null;
        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE) {
            $sku = $product->getData('sku');
        } else {
            $sku = $product->getSku();
        }

        /** @var Futurenext_Zakeke_Model_ZakekeEnabled $zakekeEnabled */
        $zakekeEnabled = Mage::getModel('futurenext_zakeke/zakekeEnabled');
        $zakekeEnabledId = $zakekeEnabled->getIdFromSku($sku);
        return (bool) $zakekeEnabledId;
    }

    /**
     * Calculate the Zakeke pricing on the customized product
     *
     * @param float $originalFinalPrice
     * @param array $pricing
     * @param float $qty
     * @return float
     */
    public static function getZakekePrice($originalFinalPrice, $pricing, $qty)
    {
        $zakekePrice = 0.0;

        if ($pricing['modelPriceDeltaPerc'] > 0) {
            $zakekePrice += $originalFinalPrice * ((float) $pricing['modelPriceDeltaPerc'] / 100);
        } else {
            $zakekePrice += (float) $pricing['modelPriceDeltaValue'];
        }

        if ($pricing['designPrice'] > 0) {
            if (isset($pricing['pricingModel']) && $pricing['pricingModel'] === 'advanced') {
                $zakekePrice += (float) $pricing['designPrice'] / $qty;
            } else {
                $zakekePrice += (float) $pricing['designPrice'];
            }
        }

        return $zakekePrice;
    }

    /**
     * Get the Zakeke option or false if the product is not customized
     *
     * @param array $options
     * @return array|false
     */
    public static function &getZakekeOption(&$options)
    {
        $result = false;

        foreach ($options as &$option) {
            if (!isset($option['is_zakeke'])) {
                continue;
            }

            $result = $option;
            break;
        }

        return $result;
    }

    /**
     * Get the Zakeke price option or false if the product is not customized
     *
     * @param array $options
     * @return array|false
     */
    public static function &getZakekePriceOption(&$options)
    {
        $result = false;

        foreach ($options as &$option) {
            if (!isset($option['code']) || $option['code'] !== 'zakeke_price') {
                continue;
            }

            $result = $option;
            break;
        }

        return $result;
    }

    /**
     * Get the HTML view for the option
     *
     * @param array $previews
     * @return string
     */
    public function adminhtmlView($previews)
    {
        $output = '<div class="zakeke-preview"><div style="display: flex">';
        foreach ($previews as $preview) {
            $output .= '<div onclick="zakekeShowPreview(this)" data-zakeke-label="' . $this->quoteEscape($preview->label). '" style="position: relative; cursor: pointer; margin: 0;"><img src="' . $this->quoteEscape($preview->url) . '" style="width: 80px"></div>';
        }
        $output .= '</div></div>';
        return $output;
    }

    /**
     * Get the HTML view for the option that is also email friendly.
     *
     * @param array $previews
     * @return string
     */
    public function htmlView($previews)
    {
        return '<img src="' . $this->quoteEscape($previews[0]->url) . '" width="150" height="150" />';
    }
}