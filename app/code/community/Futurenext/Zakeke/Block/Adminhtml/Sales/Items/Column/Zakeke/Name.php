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
 * Zakeke admin sales item column render block
 *
 * @category    Futurenext
 * @package     Futurenext_Zakeke
 */
class Futurenext_Zakeke_Block_Adminhtml_Sales_Items_Column_Zakeke_Name extends Mage_Adminhtml_Block_Sales_Items_Column_Name
{
    public function getZakekeOption()
    {
        /**
         * Get the Zakeke product option
         *
         * @return int|false
         * @throws Exception
         */
        try {
            $options = $this->getItem()->getProductOptionByCode('additional_options');
            if (!$options) {
                return false;
            }
            return Futurenext_Zakeke_Helper_Data::getZakekeOption($options);
        } catch (Exception $e) {
            Mage::logException($e);
            throw $e;
        }
    }

    /**
     * Get the Zakeke design preview files
     *
     * @return int|false
     * @throws Exception
     */
    public function getZakekeDesignId()
    {
        try {
            $options = $this->getItem()->getProductOptions();
            if (!$options) {
                return false;
            }

            if (!isset($options['info_buyRequest'])) {
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
     * @return array|false
     * @throws Exception
     */
    public function getZakekePreviews()
    {
	    /** @var Futurenext_Zakeke_Helper_ZakekeApi $zakekeApi */
	    $zakekeApi = Mage::helper('futurenext_zakeke/zakekeApi');

        try {
            $zakekeDesignId = $this->getZakekeDesignId();
            if (!$zakekeDesignId) {
                return false;
            }

            try {
                return $zakekeApi->getPreview($zakekeDesignId);
            } catch (Exception $e) {
                $zakekeOption = $this->getZakekeOption();
                if (isset($zakekeOption['previews'])) {
                    return $zakekeOption['previews'];
                }

                return false;
            }
        } catch (Exception $e) {
            Mage::logException($e);
            throw $e;
        }
    }

    /**
     * Get the Zakeke design output zip url
     *
     * @return string|false
     * @throws Exception
     */
    public function getZakekeOutputZip()
    {
	    /** @var Futurenext_Zakeke_Helper_ZakekeApi $zakekeApi */
	    $zakekeApi = Mage::helper('futurenext_zakeke/zakekeApi');

        try {
            $zakekeDesignId = $this->getZakekeDesignId();
            if (!$zakekeDesignId) {
                return false;
            }
            try {
                $zipUrl = $zakekeApi->getZakekeOutputZip($zakekeDesignId);
                return $zipUrl;
            } catch (Exception $e) {
                return false;
            }
        } catch (Exception $e) {
            Mage::logException($e);
            throw $e;
        }
    }
}
?>
