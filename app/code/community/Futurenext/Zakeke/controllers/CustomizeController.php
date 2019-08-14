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
 * Zakeke controller for customizations
 *
 * @category    Futurenext
 * @package     Futurenext_Zakeke
 *
 * @method $this setSku(string $sku)
 */
class Futurenext_Zakeke_CustomizeController extends Mage_Core_Controller_Front_Action
{
    /**
     * Ten years
     */
    const COOKIE_PERIOD = 315360000;

    const NOT_FOUND_MESSAGE = 'Failed to get a product from given parameters.';

    /**
     * Initialize product instance from add to cart form data
     *
     * @return Mage_Catalog_Model_Product|false
     */
    protected function _initProduct()
    {
        $productId = (int)$this->getRequest()->getParam('product');
        if (!$productId) {
            return false;
        }

        $storeId = Mage::app()->getStore()->getId();

        /** @var Mage_Catalog_Helper_Product $productHelper */
        $productHelper = Mage::helper('catalog/product');
        $product = $productHelper->getProduct($productId, $storeId);
        $buyRequest = new Varien_Object();
        $buyRequest->setData($this->getRequest()->getParams());

        /*
         * Setup necessary for custom options
         */
        try {
            $products = $product->getTypeInstance(true)->prepareForCart($buyRequest, $product);
            if (is_string($products)) {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }

        return $product;
    }

    /**
     * Set back redirect url to response
     *
     * @return $this
     * @throws Mage_Exception
     */
    protected function _goBack()
    {
        $returnUrl = $this->getRequest()->getParam('return_url');
        if ($returnUrl) {
            if (!$this->_isUrlInternal($returnUrl)) {
                throw new Mage_Exception('External urls redirect to "' . $returnUrl . '" denied!');
            }

            /** @var Mage_Checkout_Model_Session $session */
            $session = Mage::getSingleton('checkout/session');
            $session->getMessages(true);
            $this->getResponse()->setRedirect($returnUrl);
        } elseif ($backUrl = $this->_getRefererUrl()) {
            $this->getResponse()->setRedirect($backUrl);
        } else {
            $this->_redirect('/');
        }
        return $this;
    }

    /**
     * Ajax action that return pricing of a customization in json
     *
     * @throws Exception
     */
    public function priceAction()
    {
        $this->getResponse()->setHeader('Content-type', 'application/json');

        try {
            $product = $this->_initProduct();

            /** @var Futurenext_Zakeke_Helper_Data $zakekeHelper */
            $zakekeHelper = Mage::helper('futurenext_zakeke');

            /**
             * Check product availability
             */
            if (!($product && $zakekeHelper->isProductCustomizable($product))) {
                Mage::log('Zakeke CustomizeController: ' . self::NOT_FOUND_MESSAGE);
                $this->getResponse()->setHeader('HTTP/1.1', '404', true);
                $data = array('error' => self::NOT_FOUND_MESSAGE);
                $this->getResponse()->setBody(json_encode($data));
                return;
            }

            $qty = $this->getRequest()->getParam('qty');
            if (isset($qty)) {
                $filter = new Zend_Filter_LocalizedToNormalized(
                    array('locale' => Mage::app()->getLocale()->getLocaleCode())
                );
                $qty = $filter->filter($qty);
            }

            $baseFinalPrice = $product->getFinalPrice($qty);
            $baseZakekePrice = (float) $this->getRequest()->getParam('zakeke-price', 0.0);
            $zakekePercentPrice = (float) $this->getRequest()->getParam('zakeke-percent-price', 0.0);
            $baseZakekeFinalPrice = $baseZakekePrice;

            if ($zakekePercentPrice) {
                $baseZakekeFinalPrice += $baseFinalPrice*($zakekePercentPrice/100);
            }

            /** @var Mage_Tax_Helper_Data $taxHelper */
            $taxHelper = Mage::helper('tax');
            if ($taxHelper->displayPriceIncludingTax()) {
                if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                    $zakekeFinalPrice = $taxHelper->getPrice(
                        $product,
                        $baseZakekeFinalPrice,
                        true,
                        null,
                        null,
                        null,
                        null,
                        null,
                        false
                    );
                    $finalPrice = $taxHelper->getPrice(
                        $product,
                        $baseFinalPrice,
                        true,
                        null,
                        null,
                        null,
                        null,
                        null,
                        false
                    );
                } else {
                    $zakekeFinalPrice = $taxHelper->getPrice($product, $baseZakekeFinalPrice, true);
                    $finalPrice = $taxHelper->getPrice($product, $baseFinalPrice, true);
                }
            } else {
                if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                    $zakekeFinalPrice = $taxHelper->getPrice(
                        $product,
                        $baseZakekeFinalPrice,
                        false,
                        null,
                        null,
                        null,
                        null,
                        null,
                        false
                    );
                    $finalPrice = $taxHelper->getPrice(
                        $product,
                        $baseFinalPrice,
                        false,
                        null,
                        null,
                        null,
                        null,
                        null,
                        false
                    );
                } else {
                    $zakekeFinalPrice = $taxHelper->getPrice($product, $baseZakekeFinalPrice);
                    $finalPrice = $taxHelper->getPrice($product, $baseFinalPrice);
                }
            }

            $unitFinalPrice = $finalPrice + $zakekeFinalPrice;

            /** @var Mage_Core_Helper_Data $coreHelper */
            $coreHelper = Mage::helper('core');

            $data = array(
                'finalPrice' => $coreHelper->currency($unitFinalPrice, false, false),
            );
            $this->getResponse()->setBody(json_encode($data));
        } catch (Exception $e) {
            Mage::logException($e);
            throw $e;
        }
    }

    /**
     * Show the Zakeke interface when doing a new personalization
     */
    public function addAction()
    {
        /** @var Mage_Customer_Model_Session $session */
        $session = Mage::getSingleton('customer/session');

        try {
            $data = array();

            if (!$session->isLoggedIn()) {
                $guestCode = Mage::app()->getCookie()->get(
                    Futurenext_Zakeke_Helper_Data::GUEST_COOKIE
                );
                if (!$guestCode) {
                    /** @var Mage_Core_Helper_Data $coreHelper */
                    $coreHelper = Mage::helper('core');
                    $guestCode = $coreHelper->getRandomString(32);
                    Mage::app()->getCookie()->set(
                        Futurenext_Zakeke_Helper_Data::GUEST_COOKIE,
                        $guestCode,
                        self::COOKIE_PERIOD
                    );
                }
                $data['vc'] = $guestCode;
            } else {
                $data['cc'] = $session->getCustomerId();
            }

            $qty = $this->getRequest()->getParam('qty');
            if (isset($qty)) {
                $filter = new Zend_Filter_LocalizedToNormalized(
                    array('locale' => Mage::app()->getLocale()->getLocaleCode())
                );
                $this->getRequest()->setParam('qty', $filter->filter($qty));
            }

            $product = $this->_initProduct();

            /**
             * Check product availability
             */
            if (!$product) {
                throw new Exception($this->__('Can\'t retrieve request product'));
            }

            /** @var Futurenext_Zakeke_Helper_ZakekeApi $zakekeApi */
            $zakekeApi = Mage::helper('futurenext_zakeke/zakekeApi');
            $token = $zakekeApi->getToken($data);

            // Register current data
            Mage::register('current_product', $product);
            Mage::register('product', $product);
            Mage::register(
                Futurenext_Zakeke_Helper_Data::ZAKEKE_REGISTRY_TOKEN,
                $token
            );

            $this->loadLayout();
            $this->getLayout()->getBlock('head')->setTitle($product->getName());
            $this->renderLayout();
        } catch (Exception $e) {
            Mage::logException($e);
            $session->addException($e, $e->getMessage());
            $this->_goBack();
        }
    }

    /**
     * Show the Zakeke interface when configuing a quote item
     */
    public function configureAction()
    {
        /** @var Mage_Customer_Model_Session $session */
        $session = Mage::getSingleton('customer/session');

        try {
            /** @var Mage_Core_Helper_Http $coreHttpHelper */
            $coreHttpHelper = Mage::helper('core/http');
            $remoteAddr = $coreHttpHelper->getRemoteAddr();

            $data = array(
                'ip' => $remoteAddr
            );

            $guestCode = Mage::app()->getCookie()->get(
                Futurenext_Zakeke_Helper_Data::GUEST_COOKIE
            );
            if ($guestCode) {
                $data['vc'] = $guestCode;
            }

            if ($session->isLoggedIn()) {
                $data['cc'] = $session->getCustomerId();
            }

            $qty = $this->getRequest()->getParam('qty');
            if (isset($qty)) {
                $filter = new Zend_Filter_LocalizedToNormalized(
                    array('locale' => Mage::app()->getLocale()->getLocaleCode())
                );
                $this->getRequest()->setParam('qty', $filter->filter($qty));
            }

            $product = $this->_initProduct();

            /**
             * Check product availability
             */
            if (!$product) {
                throw new Exception($this->__('Can\'t retrieve request product'));
            }

            /** @var Futurenext_Zakeke_Helper_ZakekeApi $zakekeApi */
            $zakekeApi = Mage::helper('futurenext_zakeke/zakekeApi');
            $token = $zakekeApi->getToken($data);

            // Register current data
            Mage::register('current_product', $product);
            Mage::register('product', $product);
            Mage::register(
                Futurenext_Zakeke_Helper_Data::ZAKEKE_REGISTRY_TOKEN,
                $token
            );

            $this->loadLayout();
            $this->getLayout()->getBlock('head')->setTitle($product->getName());
            $this->renderLayout();
        } catch (Exception $e) {
            Mage::logException($e);
            $session->addException($e, $e->getMessage());
            $this->_goBack();
        }
    }
}