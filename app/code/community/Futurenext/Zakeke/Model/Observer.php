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
 * Zakeke Observer
 *
 * @category    Futurenext
 * @package     Futurenext_Zakeke
 */
class Futurenext_Zakeke_Model_Observer
{
    /**
     * Fills and set the zakeke additional options
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     * @throws Exception
     */
    public function productLoadAfterCartAdd($observer)
    {
        /** @var Futurenext_Zakeke_Helper_ZakekeApi $zakekeApi */
        $zakekeApi = Mage::helper('futurenext_zakeke/zakekeApi');

        try {
            $action = Mage::app()->getFrontController()->getAction();
            if (!$action) {
                return $this;
            }
            $actionName = $action->getFullActionName();
            if ($actionName !== 'checkout_cart_add' && $actionName !== 'checkout_cart_updateItemOptions') {
                return $this;
            }

            $request = $action->getRequest();
            $design = $request->getParam(
                Futurenext_Zakeke_Helper_Data::ZAKEKE_DESIGN_PARAM
            );
            $model = $request->getParam(
                Futurenext_Zakeke_Helper_Data::ZAKEKE_MODEL_PARAM
            );
            if (!$design || !$model) {
                return $this;
            }

            $qty = $request->getParam('qty');
            if ($qty) {
                $filter = new Zend_Filter_LocalizedToNormalized(
                    array('locale' => Mage::app()->getLocale()->getLocaleCode())
                );
                $qty = $filter->filter($qty);
            }

            /** @var Mage_Catalog_Model_Product $product */
            $product = $observer->getProduct();

            $toAddProductId = $request->getParam('product');
            if ($toAddProductId && $toAddProductId != $product->getId()) {
                return $this;
            }

            /** @var Futurenext_Zakeke_Helper_Data $zakekeHelper */
            $zakekeHelper = Mage::helper('futurenext_zakeke');

            if (!$zakekeHelper->isProductCustomizable($product)) {
                Mage::log(
                    'Zakeke tried order ' . $product->getSku() . ' with model ' . $model  . ' but is not customizable'
                );
                return $this;
            }

            $options = array();
            $additionalOption = $product->getCustomOption('additional_options');
            if ($additionalOption) {
                $options = (array)unserialize($additionalOption->getValue());
            }

            if (Futurenext_Zakeke_Helper_Data::getZakekeOption($options)) {
                return $this;
            }

            $zakekeCartData = $zakekeApi->getCartInfo($design);

            /** @var Mage_Core_Helper_Data $coreHelper */
            $coreHelper = Mage::helper('core');

            $originalFinalPrice = $product->getFinalPrice($qty);

            $zakekePrice = 0.0;
            if ($zakekeCartData->pricing['modelPriceDeltaPerc']) {
                $zakekePrice += $originalFinalPrice * ($zakekeCartData->pricing['modelPriceDeltaPerc'] / 100);
            } else {
                $zakekePrice += (float)$zakekeCartData->pricing['modelPriceDeltaValue'];
            }
            $zakekePrice += (float)$zakekeCartData->pricing['designPrice'];

            $zakekeOption = array(
                'label' => $coreHelper->__('Customization'),
                'option_id' => 'zakeke',
                'value' => $zakekeHelper->htmlView($zakekeCartData->previews),
                'print_value' => '#' . $design,
                'custom_view' => true,
                'design' => $design,
                'model' => $model,
                'previews' => $zakekeCartData->previews,
                'pricing' => $zakekeCartData->pricing,
                'price' => $zakekePrice,
                'original_final_price' => $originalFinalPrice,
                'is_zakeke' => true
            );

            $options[] = $zakekeOption;

            if ($zakekePrice) {
                $zakekeFinalPrice = $coreHelper->currency($zakekePrice, true, false);
                $zakekePricingOption = [
                    'label' => $coreHelper->__('Customization Price'),
                    'value' => $zakekeFinalPrice
                ];
                $options[] = $zakekePricingOption;
            }

            $product->addCustomOption('additional_options', serialize($options));
        } catch (Exception $e) {
            Mage::logException($e);
            $zakekeApi->log('Observer productLoadAfterCartAdd exception ' . $e);
            throw $e;
        }
        return $this;
    }

    /**
     * Reorder a customized product.
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     * @throws Exception
     */
    public function reorder($observer)
    {
        /** @var Futurenext_Zakeke_Helper_ZakekeApi $zakekeApi */
        $zakekeApi = Mage::helper('futurenext_zakeke/zakekeApi');

        try {
            $action = Mage::app()->getFrontController()->getAction();
            if ($action === null) {
                return $this;
            }
            $actionName = $action->getFullActionName();
            if ($actionName !== 'sales_order_reorder') {
                return $this;
            }

            /** @var Mage_Sales_Model_Quote_Item $item */
            $item = $observer->getQuoteItem();
            $buyInfo = $item->getBuyRequest();

            if (!isset($buyInfo[Futurenext_Zakeke_Helper_Data::ZAKEKE_DESIGN_PARAM])) {
                return $this;
            }

            if (!isset($buyInfo[Futurenext_Zakeke_Helper_Data::ZAKEKE_MODEL_PARAM])) {
                return $this;
            }

            /** @var Futurenext_Zakeke_Helper_Data $zakekeHelper */
            $zakekeHelper = Mage::helper('futurenext_zakeke');

            $product = $item->getProduct();
            if (!$zakekeHelper->isProductCustomizable($item->getProduct())) {
                return $this;
            }

            $qty = $action->getRequest()->getParam('qty');
            if ($qty !== null) {
                $filter = new Zend_Filter_LocalizedToNormalized(
                    array('locale' => Mage::app()->getLocale()->getLocaleCode())
                );
                $qty = $filter->filter($qty);
            }

            $design = $buyInfo[Futurenext_Zakeke_Helper_Data::ZAKEKE_DESIGN_PARAM];
            $model = $buyInfo[Futurenext_Zakeke_Helper_Data::ZAKEKE_MODEL_PARAM];

            $additionalOptions = array();
            $additionalOption = $item->getOptionByCode('additional_options');
            if ($additionalOption !== null) {
                $additionalOptions = (array)unserialize($additionalOption->getValue());
            }

            /** @var Mage_Core_Helper_Data $coreHelper */
            $coreHelper = Mage::helper('core');

            $zakekeCartData = $zakekeApi->getCartInfo($design);

            $originalFinalPrice = $product->getFinalPrice($qty);

            $zakekePrice = 0.0;
            if ($zakekeCartData->pricing['modelPriceDeltaPerc']) {
                $zakekePrice += $originalFinalPrice * ($zakekeCartData->pricing['modelPriceDeltaPerc'] / 100);
            } else {
                $zakekePrice += (float)$zakekeCartData->pricing['modelPriceDeltaValue'];
            }
            $zakekePrice += (float)$zakekeCartData->pricing['designPrice'];

            $zakekeOption = array(
                'label' => $coreHelper->__('Customization'),
                'option_id' => 'zakeke',
                'value' => $zakekeHelper->htmlView($zakekeCartData->previews),
                'print_value' => '#' . $design,
                'custom_view' => true,
                'design' => $design,
                'model' => $model,
                'previews' => $zakekeCartData->previews,
                'pricing' => $zakekeCartData->pricing,
                'price' => $zakekePrice,
                'original_final_price' => $originalFinalPrice,
                'is_zakeke' => true
            );
            $additionalOptions[] = $zakekeOption;

            if ($zakekePrice) {
                $zakekeFinalPrice = $coreHelper->currency($zakekePrice);

                $zakekePricingOption = [
                    'label' => $coreHelper->__('Customization Price'),
                    'value' => $zakekeFinalPrice
                ];
                $additionalOptions[] = $zakekePricingOption;
            }

            $item->addOption(array(
                'code' => 'product_type',
                'value' => 'zakeke',
                'product' => $item->getProduct(),
                'product_id' => $item->getProduct()->getId()
            ));

            $item->addOption(array(
                'code' => 'additional_options',
                'value' => serialize($additionalOptions),
                'product' => $item->getProduct(),
                'product_id' => $item->getProduct()->getId()
            ));
        } catch (Exception $e) {
            Mage::logException($e);
            $zakekeApi->log('Observer reorder exception ' . $e);
            throw $e;
        }
        return $this;
    }

    /**
     * Adds additiona_options when converting a quote item to an order item
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     * @throws Exception
     */
    public function salesConvertQuoteItemToOrderItem(Varien_Event_Observer $observer)
    {
        /** @var Futurenext_Zakeke_Helper_ZakekeApi $zakekeApi */
        $zakekeApi = Mage::helper('futurenext_zakeke/zakekeApi');

        try {
            /** @var Mage_Sales_Model_Quote_Item $quoteItem */
            $quoteItem = $observer->getItem();
            $additionalOptionsOption = $quoteItem->getOptionByCode('additional_options');
            if ($additionalOptionsOption) {
                $additionalOptions = (array)unserialize($additionalOptionsOption->getValue());

                /** @var Mage_Sales_Model_Order_Item $orderItem */
                $orderItem = $observer->getOrderItem();
                $options = $orderItem->getProductOptions();
                $options['additional_options'] = $additionalOptions;
                $orderItem->setProductOptions($options);
            }
        } catch (Exception $e) {
            Mage::logException($e);
            $zakekeApi->log('Observer salesConvertQuoteItemToOrderItem exception ' . $e);
            throw $e;
        }
        return $this;
    }

    /**
     * Set the zakeke product type
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     * @throws Exception
     */
    public function cartAdd($observer)
    {
        /** @var Futurenext_Zakeke_Helper_ZakekeApi $zakekeApi */
        $zakekeApi = Mage::helper('futurenext_zakeke/zakekeApi');

        try {
            /** @var Mage_Catalog_Model_Product $product */
            $product = $observer->getProduct();

            $action = Mage::app()->getFrontController()->getAction();
            $request = $action->getRequest();
            $qty = $request->getParam('qty');
            if (isset($qty)) {
                $filter = new Zend_Filter_LocalizedToNormalized(
                    array('locale' => Mage::app()->getLocale()->getLocaleCode())
                );
                $qty = $filter->filter($qty);
            }

            /** @var Mage_Catalog_Model_Product_Option $additionalOption */
            $additionalOption = $product->getCustomOption('additional_options');
            if (!$additionalOption) {
                return $this;
            }

            $options = (array)unserialize($additionalOption->getValue());
            if ($option = Futurenext_Zakeke_Helper_Data::getZakekeOption($options)) {
                /** @var Mage_Sales_Model_Quote_Item $quoteItem */
                $quoteItem = $observer->getQuoteItem();
                /** @var Mage_Sales_Model_Quote_Item_Option $option */
                $optionType = Mage::getModel('sales/quote_item_option');
                $optionType->setProductId($product->getId())
                    ->setCode('product_type')
                    ->setProduct($product)
                    ->setValue(Futurenext_Zakeke_Helper_Data::ZAKEKE_PRODUCT_TYPE);
                $quoteItem->addOption($optionType);

                $zakekePrice = 0.0;
                if (isset($option['price'])) {
                    $zakekePrice = $option['price'];
                }

                if ($zakekePrice <= 0.0) {
                    return $this;
                }

                /** @var Mage_Directory_Helper_Data $directoryHelper */
                $directoryHelper = Mage::helper('directory');
                $baseCurrencyCode = Mage::app()->getStore()->getBaseCurrencyCode();
                $currentCurrencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();

                $baseFinalPrice = $product->getFinalPrice($quoteItem->getQty()) + $zakekePrice;
                $finalPrice = $directoryHelper->currencyConvert($baseFinalPrice, $baseCurrencyCode, $currentCurrencyCode);

                $quoteItem->setCustomPrice($finalPrice);
                $quoteItem->setOriginalCustomPrice($finalPrice);
                $product->setIsSuperMode(true);
            }
        } catch (Exception $e) {
            Mage::logException($e);
            $zakekeApi->log('Observer cartAdd exception ' . $e);
            throw $e;
        }
        return $this;
    }

    /**
     * Notify Zakeke that an order containing some Zakeke customized product is begin placed
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     * @throws Exception
     */
    public function orderSave($observer)
    {
        /** @var Futurenext_Zakeke_Helper_ZakekeApi $zakekeApi */
        $zakekeApi = Mage::helper('futurenext_zakeke/zakekeApi');

        try {
            $zakekeApi->log('Observer orderSave called');

            /** @var Mage_Sales_Model_Order $order */
            $order = $observer->getOrder();

            /** @var Mage_Customer_Model_Session $session */
            $session = Mage::getSingleton('customer/session');

            $data = array(
                'orderCode' => $order->getIncrementId(),
                'sessionID' => $session->getSessionId(),
                'total' => $order->getBaseSubtotal(),
                'orderStatusID' => 1,
                'details' => array()
            );

            $guestCode = Mage::app()->getCookie()->get(
                Futurenext_Zakeke_Helper_Data::GUEST_COOKIE
            );
            if (!$order->getCustomerIsGuest()) {
                $data['customerID'] = $order->getCustomerId();
            } elseif ($guestCode) {
                $data['visitorID'] = $guestCode;
            }

            /** @var Mage_Tax_Helper_Data $taxHelper */
            $taxHelper = Mage::helper('tax');

            $items = $order->getAllVisibleItems();
            /** @var Mage_Sales_Model_Order_Item $item */
            foreach ($items as $item) {
                if ($item->getParentItem()) {
                    continue;
                }

                $productOptions = $item->getProductOptions();

                if (!isset($productOptions['additional_options'])) {
                    continue;
                }

                $options = $productOptions['additional_options'];
                if ($option = Futurenext_Zakeke_Helper_Data::getZakekeOption($options)) {
                    $zakekePrice = 0.0;
                    if (isset($option['price'])) {
                        $zakekePrice = $option['price'];
                    }
                    $zakekeTaxPrice = $taxHelper->getPrice(
                        $item->getProduct(),
                        $zakekePrice,
                        false,
                        null,
                        null,
                        null,
                        null,
                        null,
                        false
                    );

                    $originalFinalPrice = 0.0;
                    if (isset($option['original_final_price'])) {
                        $originalFinalPrice = $option['original_final_price'];
                    }
                    $originalFinalTaxPrice = $taxHelper->getPrice(
                        $item->getProduct(),
                        $originalFinalPrice,
                        false,
                        null,
                        null,
                        null,
                        null,
                        null,
                        false
                    );

                    $itemData = array(
                        'designDocID' => $option['design'],
                        'orderDetailCode' => $item->getQuoteItemId(),
                        'quantity' => $item->getQtyOrdered(),
                        'designUnitPrice' => $zakekeTaxPrice,
                        'modelUnitPrice' => $originalFinalTaxPrice
                    );

                    $data['details'][] = $itemData;
                }
            }
            if (count($data['details']) > 0) {
                $zakekeApi->log('Observer orderSave order with such info ' . json_encode($data));
                $zakekeApi->placeOrder($data);
            }
        } catch (Exception $e) {
            Mage::logException($e);
            $zakekeApi->log('Observer orderSave exception ' . $e);
            throw $e;
        }
        return $this;
    }

    /**
     * Notify Zakeke if a guest is also a Zakeke user that now she is registered
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     * @throws Exception
     */
    public function customerRegister($observer)
    {
        try {
            $guestCode = Mage::app()->getCookie()->get(
                Futurenext_Zakeke_Helper_Data::GUEST_COOKIE
            );
            if (!$guestCode) {
                return $this;
            }

            /** @var Mage_Customer_Model_Customer $customer */
            $customer = $observer->getCustomer();

            /** @var Futurenext_Zakeke_Helper_ZakekeApi $zakekeApi */
            $zakekeApi = Mage::helper('futurenext_zakeke/zakekeApi');
            $zakekeApi->associateGuest($guestCode, $customer->getId());
        } catch (Exception $e) {
            Mage::logException($e);
            throw $e;
        }
        return $this;
    }

    /**
     * Notify if a Zakeke guest login with an account on the store
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     * @throws Exception
     */
    public function customerLogin($observer)
    {
        try {
            $guestCode = Mage::app()->getCookie()->get(
                Futurenext_Zakeke_Helper_Data::GUEST_COOKIE
            );
            if (!$guestCode) {
                return $this;
            }

            /** @var Mage_Customer_Model_Customer $customer */
            $customer = $observer->getCustomer();

            /** @var Futurenext_Zakeke_Helper_ZakekeApi $zakekeApi */
            $zakekeApi = Mage::helper('futurenext_zakeke/zakekeApi');
            $zakekeApi->associateGuest($guestCode, $customer->getId());
        } catch (Exception $e) {
            Mage::logException($e);
            throw $e;
        }
        return $this;
    }

    /**
     * Remove the Zakeke guest cookie on logout
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     * @throws Exception
     */
    public function customerLogout($observer)
    {
        try {
            $guestCode = Mage::app()->getCookie()->get(
                Futurenext_Zakeke_Helper_Data::GUEST_COOKIE
            );
            if (!$guestCode) {
                return $this;
            }

            Mage::app()->getCookie()->delete(
                Futurenext_Zakeke_Helper_Data::GUEST_COOKIE
            );
        } catch (Exception $e) {
            Mage::logException($e);
            throw $e;
        }
        return $this;
    }
}