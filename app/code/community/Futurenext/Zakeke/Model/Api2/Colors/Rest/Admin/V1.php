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
 * Zakeke Magento REST API for retrieving color product attribute values
 *
 * @category    Futurenext
 * @package     Futurenext_Zakeke
 */
class Futurenext_Zakeke_Model_Api2_Colors_Rest_Admin_V1 extends Futurenext_Zakeke_Model_Api2_Colors
{
    /**
     * Retrieve the product colors
     *
     * @return array
     */
    public function _retrieveCollection()
    {
        /** @var Mage_Eav_Model_Resource_Entity_Attribute_Collection $attributeCollection */
        $attributeCollection = Mage::getResourceModel('eav/entity_attribute_collection');
        $attributeInfo = $attributeCollection->setCodeFilter('color')->getFirstItem();
        $attributeId = $attributeInfo->getAttributeId();
        if (is_null($attributeId)) {
            return array();
        }

        /** @var Mage_Catalog_Model_Resource_Eav_Attribute $attribute */
        $attribute = Mage::getModel('catalog/resource_eav_attribute')->load($attributeId);
        $attributeOptions = $attribute->getSource()->getAllOptions(false);
        for($i = 0; $i < count($attributeOptions); $i++)
        {
            $attributeOptions[$i]['attribute_id'] = $attributeId;
        }

        return $attributeOptions;
    }
}