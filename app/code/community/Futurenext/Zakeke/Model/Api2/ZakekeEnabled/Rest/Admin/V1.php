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
 * Zakeke Magento REST API for setting a product as a customizable
 *
 * @category    Futurenext
 * @package     Futurenext_Zakeke
 */
class Futurenext_Zakeke_Model_Api2_ZakekeEnabled_Rest_Admin_V1 extends Futurenext_Zakeke_Model_Api2_ZakekeEnabled
{
    const SKU_PARAM_NOT_FOUND = 'Could not get sku from the request data.';

    /**
     * Clean the frontend cache
     *
     * @return void
     */
    protected function cleanCache()
    {
        Mage::app()->getCacheInstance()->cleanType('full_page');
        Mage::dispatchEvent(
            'adminhtml_cache_refresh_type',
            array('type' => 'full_page')
        );
        Mage::app()->getCacheInstance()->cleanType(Mage_Core_Block_Abstract::CACHE_GROUP);
        Mage::dispatchEvent(
            'adminhtml_cache_refresh_type',
            array('type' => Mage_Core_Block_Abstract::CACHE_GROUP)
        );
    }

    /**
     * List of customizable products
     *
     * @return array
     */
    public function _retrieveCollection()
    {
        /** @var Futurenext_Zakeke_Model_Resource_ZakekeEnabled_Collection $collection */
        $collection = Mage::getModel('futurenext_zakeke/zakekeEnabled')->getCollection();
        $enabled = $collection->load();
        $res = array();
        /** @var Futurenext_Zakeke_Model_ZakekeEnabled $item */
        foreach ($enabled->getItems() as $item) {
            $res[] = $item->toArray();
        }

        return $res;
    }

    /**
     * Get resource location
     *
     * @param Mage_Core_Model_Abstract $resource
     * @return string URL
     */
    protected function _getLocation($resource)
    {
        /* @var $apiTypeRoute Mage_Api2_Model_Route_ApiType */
        $apiTypeRoute = Mage::getModel('api2/route_apiType');

        $chain = $apiTypeRoute->chain(
            new Zend_Controller_Router_Route($this->getConfig()->getRouteWithEntityTypeAction($this->getResourceType()))
        );
        $params = array(
            'api_type' => $this->getRequest()->getApiType(),
            'id'       => $resource->getId(),
            'sku' => $resource->setSku()
        );
        $uri = $chain->assemble($params);

        return '/' . $uri;
    }

    /**
     * Enable a product for Zakeke
     *
     * @param array $data
     * @return string
     */
    public function _create(array $data)
    {
        Mage::log('Zakeke enable for customization ' . json_encode($data));

        if (!isset($data['sku'])) {
            $this->_critical(self::SKU_PARAM_NOT_FOUND);
        }
        $sku = $data['sku'];

        /** @var Futurenext_Zakeke_Model_ZakekeEnabled $zakekeEnabled */
        $zakekeEnabled = Mage::getModel('futurenext_zakeke/zakekeEnabled');
        $zakekeEnabledId = $zakekeEnabled->getIdFromSku($sku);
        Mage::log('Made customizable ' . $zakekeEnabledId);
        if ($zakekeEnabledId) {
            $zakekeEnabled->load($zakekeEnabledId);
            Mage::log(
                'ZakekeEnabled Rest _create: an entity already exist for the Sku ' . $sku . ' with id ' . $zakekeEnabledId
            );
            return $this->_getLocation($zakekeEnabled);
        }
        $zakekeEnabled->setSku($sku);

        try {
            $zakekeEnabled->save();
            Mage::log('Saved model');
            $this->cleanCache();
        } catch (Mage_Core_Exception $e) {
            Mage::logException($e);
            $this->_critical($e->getMessage(), Mage_Api2_Model_Server::HTTP_INTERNAL_ERROR);
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_critical(self::RESOURCE_UNKNOWN_ERROR);
        }

        return $this->_getLocation($zakekeEnabled);
    }

    /**
     * Set a SKU as not longer customizable by Zakeke
     *
     * @return void
     */
    public function _delete()
    {
        /** @var string $sku */
        $sku = $this->getRequest()->getParam('sku');
        if (!$sku) {
            $this->_critical(self::SKU_PARAM_NOT_FOUND);
        }

        $this->cleanCache();

        /** @var Futurenext_Zakeke_Model_ZakekeEnabled $zakekeEnabled */
        $zakekeEnabled = Mage::getModel('futurenext_zakeke/zakekeEnabled');
        $zakekeEnabledId = $zakekeEnabled->getIdFromSku($sku);
        if (!$zakekeEnabledId) {
            Mage::log('ZakekeEnabled Rest _delete: could not get the entity from the Sku ' . $sku);
            return;
        }

        try {
            $zakekeEnabled->load($zakekeEnabledId);
            $zakekeEnabled->delete();
        } catch (Mage_Core_Exception $e) {
            $this->_critical($e->getMessage(), Mage_Api2_Model_Server::HTTP_INTERNAL_ERROR);
        } catch (Exception $e) {
            $this->_critical(self::RESOURCE_INTERNAL_ERROR);
        }
    }
}