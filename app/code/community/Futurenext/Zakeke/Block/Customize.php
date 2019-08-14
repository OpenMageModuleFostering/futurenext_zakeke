<?php
/*******************************************************
 * Copyright (C) 2017 FutureNext SRL
 *
 * This file is part of Zakeke.
 *
 * Zakeke can not be copied and/or distributed without the express
 * permission of FutureNext SRL
 *******************************************************/


/**
 * Zakeke base customization block
 *
 * @category    Futurenext
 * @package     Futurenext_Zakeke
 */
class Futurenext_Zakeke_Block_Customize extends Futurenext_Zakeke_Block_View
{
    /**
     * Zakeke authentication token.
     *
     * @return string
     */
    public function getZakekeToken()
    {
        return Mage::registry(
            Futurenext_Zakeke_Helper_Data::ZAKEKE_REGISTRY_TOKEN
        );
    }

    /**
     * Color attribute identifier
     *
     * @return int|false
     */
    public function getColorAttributeId()
    {
        /** @var Mage_Eav_Model_Config $eav */
        $eav = Mage::getModel('eav/config');
        $attribute = $eav->getAttribute('catalog_product', 'color');
        if (!isset($attribute)) {
            return false;
        }
        return $attribute->getAttributeId();
    }

    /**
     * How we display prices regarding tax
     *
     * @return string
     */
    protected function getTaxPricesPolicy()
    {
        $store = Mage::app()->getStore();

        /** @var Mage_Tax_Helper_Data $taxHelper */
        $taxHelper = Mage::helper('tax');
        if ($taxHelper->displayPriceExcludingTax($store)) {
            return 'excluding';
        } else {
            return 'including';
        }
    }

    /**
     * Return the URL for the Zakeke customizer iFrame
     * complete of product SKU and security token
     *
     * @param bool $isMobile - Use the mobile or the large version
     * @return string
     */
    public function getCustomizerUrl($isMobile)
    {
        $product = $this->getProduct();
        $sku = null;
        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE) {
            $sku = $product->getData('sku');
        } else {
            $sku = $product->getSku();
        }

        $data = array(
            'name' => $product->getName(),
            'token' => $this->getZakekeToken(),
            'currency' => Mage::app()->getStore()->getCurrentCurrencyCode(),
            'taxPricesPolicy' => $this->getTaxPricesPolicy(),
            'culture' => str_replace('_', '-', Mage::app()->getLocale()->getLocaleCode()),
            'modelCode' => $sku,
            'ecommerce' => 'magento1',
            'attribute' => array()
        );

        $params = Mage::app()->getRequest()->getParams();

        if (isset($params['super_attribute'])) {
            foreach ($params['super_attribute'] as $key => $value) {
                $data['attribute'][$key] = $value;
            }
        }

        if (isset($params['options'])) {
            foreach ($params['options'] as $key => $value) {
                $data['attribute'][$key] = $value;
            }
        }

        if (isset($params['qty'])) {
            $data['qty'] = $params['qty'];
        }

        $zakekeOption = Mage::app()->getRequest()->getParam(
            Futurenext_Zakeke_Helper_Data::ZAKEKE_DESIGN_PARAM
        );
        if ($zakekeOption) {
            $data['designdocid'] = $zakekeOption;
        }

        $path = '/Customizer/index.html';
        if ($isMobile) {
            $path = '/Customizer/index.mobile.html';
        }

        $url = Futurenext_Zakeke_Helper_Data::ZAKEKE_BASE_URL . $path . '?' . http_build_query($data);
        return $url;
    }

    public function getMobileUrl()
    {
        return $this->getCustomizerUrl(true);
    }

    public function getLargeUrl()
    {
        return $this->getCustomizerUrl(false);
    }

    /**
     * Url where to go when the customization is added to cart
     *
     * @return string
     */
    public function getReturnUrl()
    {
        return Mage::app()->getRequest()->getParam('return_url', $this->getUrl('checkout/cart'));
    }

    /**
     * Get json configuration needed to javascript
     *
     * @return string
     */
    public function getJsonConfig()
    {
        /** @var Mage_Core_Model_Session $session */
        $session = Mage::getSingleton('core/session');

        $data = array(
            'zakekeUrl' => Futurenext_Zakeke_Helper_Data::ZAKEKE_BASE_URL,
            'customizerLargeUrl' => $this->getLargeUrl(),
            'customizerSmallUrl' => $this->getMobileUrl(),
            'params' => Mage::app()->getRequest()->getParams(),
            'colorAttributeId' => $this->getColorAttributeId(),
            'checkoutUrl' => $this->getAddtocartFormTarget(),
            'cartUrl' => $this->getUrl('checkout/cart'),
            'returnUrl' => $this->getReturnUrl(),
            'formKey' => $session->getFormKey(),
            'zakekeToken' => $this->getZakekeToken(),
            'baseUrl' => Mage::getBaseUrl()
        );
        /** @var Mage_Core_Helper_Data $coreHelper */
        $coreHelper = Mage::helper('core');
        return $coreHelper->jsonEncode($data);
    }
}