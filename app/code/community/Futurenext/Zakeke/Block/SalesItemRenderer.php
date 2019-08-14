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
 * Zakeke sales item renderer block
 *
 * @category    Futurenext
 * @package     Futurenext_Zakeke
 */
class Futurenext_Zakeke_Block_SalesItemRenderer extends Mage_Sales_Block_Order_Item_Renderer_Default
{
    /**
     * Get the Zakeke design preview files
     *
     * @throws Exception
     * @return int|false
     */
    public function getZakekeDesignId()
    {
        try {
            /** @var array $options */
            $options = $this->getItem()->getProductOptions();
            if (!($options && isset($options['info_buyRequest']))) {
                return false;
            }

            $buyRequest = $options['info_buyRequest'];
            if (!isset($buyRequest[Futurenext_Zakeke_Helper_Data::ZAKEKE_DESIGN_PARAM])) {
                return false;
            }

            return $buyRequest[Futurenext_Zakeke_Helper_Data::ZAKEKE_DESIGN_PARAM];
        } catch (Exception $e) {
            Mage::logException($e);
            throw $e;
        }
    }

    /**
     * Get the Zakeke design preview files
     *
     * @throws Exception
     * @return array
     */
    public function getZakekePreviews()
    {
        try {
            $previews = [];
            /** @var array $options */
            $options = $this->getItem()->getProductOptions();
            if ($options && isset($options['additional_options'])) {
                $options = $options['additional_options'];
                $option = Futurenext_Zakeke_Helper_Data::getZakekeOption($options);
                if ($option !== false || isset($option['previews'])) {
                    $previews = $option['previews'];
                }
            }

            return $previews;
        } catch (Exception $e) {
            Mage::logException($e);
            throw $e;
        }
    }
}