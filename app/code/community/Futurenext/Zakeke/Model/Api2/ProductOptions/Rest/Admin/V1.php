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
 * Zakeke Magento REST API for retrieving product options
 *
 * @category    Futurenext
 * @package     Futurenext_Zakeke
 */
class Futurenext_Zakeke_Model_Api2_ProductOptions_Rest_Admin_V1 extends Futurenext_Zakeke_Model_Api2_Colors
{
    /**
     * Retrieve the product options
     *
     * @return array
     */
    public function _retrieveCollection()
    {
        $res = array();

        $sku = $this->getRequest()->getParam('sku');

        /** @var Mage_Catalog_Model_Product $product */
        $product = Mage::getModel('catalog/product');
        $product->load($product->getIdBySku($sku));

        if (is_null($product->getId())) {
            $this->_critical(
                sprintf('Cannot find a product with such Sku: %s', $sku),
                404);
        }

        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE) {
            $productAttributes = $product->getTypeInstance(true)->getConfigurableAttributes($product);

            foreach ($productAttributes as $configurableAttribute) {
                $attribute = $configurableAttribute->getProductAttribute();
                $values = null;
                if (!is_null($attribute->getSource())) {
                    $values = $attribute->getSource()->getAllOptions(false);
                }

                $res[] = array(
                    'attribute_id' => $attribute->getAttributeId(),
                    'label' => $attribute->getFrontendLabel(),
                    'values' => $values,
                    'custom_option' => false
                );
            }
        }

        $productCustomOptions = $product->getOptions();
        foreach ($productCustomOptions as $customOption) {
            $values = array();
            foreach ($customOption->getValues() as $value) {
                $values[] = array(
                    'value' => $value->getOptionTypeId(),
                    'label' => $value->getTitle()
                );
            }
            $res[] = array(
                'attribute_id' => $customOption->getOptionId(),
                'label' => $customOption->getTitle(),
                'values' => $values,
                'custom_option' => true
            );
        }

        return $res;
    }
}