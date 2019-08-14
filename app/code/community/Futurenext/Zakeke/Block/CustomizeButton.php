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
 * Zakeke customization button block
 *
 * @category    Futurenext
 * @package     Futurenext_Zakeke
 */
class Futurenext_Zakeke_Block_CustomizeButton extends Futurenext_Zakeke_Block_View
{
    /**
     * Url to the new product customization page
     *
     * @return string
     */
    public function getCustomizeAddUrl()
    {
        return $this->getUrl('zakeke/Customize/Add');
    }

    /**
     * Url to to product customization editing page
     *
     * @return string
     */
    public function getCustomizeConfigureUrl()
    {
        $params = array(
            'id' => $this->getRequest()->getParam('id')
        );

        $zakekeOption = $this->getZakekeOption();
        if ($zakekeOption) {
            $params[Futurenext_Zakeke_Helper_Data::ZAKEKE_DESIGN_PARAM] = $zakekeOption['design'];
        }

        return $this->getUrl('zakeke/Customize/Configure') . '?' . http_build_query($params);
    }

    /**
     * Check if the customer is editing a product quote item
     *
     * @return bool
     */
    public function isEditMode()
    {
        return $this->getRequest()->getActionName() == 'configure';
    }

    /**
     * Url to the correct controller page
     * @return string
     */
    public function getCustomizeUrl()
    {
        return $this->isEditMode() ? $this->getCustomizeConfigureUrl()
            : $this->getCustomizeAddUrl();
    }

    /**
     * Get json configuration needed to javascript
     *
     * @return string
     */
    public function getJsonConfig()
    {
        $data = array(
            'customizeUrl' => $this->getCustomizeUrl()
        );
        /** @var Mage_Core_Helper_Data $coreHelper */
        $coreHelper = Mage::helper('core');
        return $coreHelper->jsonEncode($data);
    }
}