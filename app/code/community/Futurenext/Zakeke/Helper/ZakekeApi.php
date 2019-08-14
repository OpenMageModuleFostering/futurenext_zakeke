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
 * Zakeke API
 *
 * @category    Futurenext
 * @package     Futurenext_Zakeke
 */
class Futurenext_Zakeke_Helper_ZakekeApi extends Mage_Core_Helper_Abstract
{
    /**
     * Get the needed data for adding a product to the cart
     *
     * @param int $designId
     * @param float $qty
     * @throws Exception
     * @return object
     */
    public function getCartInfo($designId, $qty)
    {
        $data = $this->getMinimalData();
        $data['qty'] = $qty;

        $url = '/api/designdocs/' . $designId . '/cartinfo'
            . '?' . http_build_query($data);
        $json = $this->getRawRequest($url, null);

        $res = new stdClass();

        $res->pricing = $json['pricing'];

        $preview = new stdClass();
        $preview->url = $json['tempPreviewUrl'];
        $preview->label = '';

        $res->previews = array($preview);

        return $res;
    }

    /**
     * Get the Zakeke authentication token
     *
     * @param array $data - Data to pass as query string.
     * @throws Exception
     * @return string
     */
    public function getToken($data)
    {
        $registryKey = Futurenext_Zakeke_Helper_Data::ZAKEKE_REGISTRY_TOKEN . http_build_query($data);
        $token = Mage::registry($registryKey);
        if ($token !== null) {
            return $token;
        }

        $data = array_merge($data, $this->getMinimalData());

        $query = http_build_query($data);
        $url = Futurenext_Zakeke_Helper_Data::ZAKEKE_API_URL . '/api/Login?' . $query;

        $httpClient = new Varien_Http_Client($url);
        $request = $httpClient->request(Varien_Http_Client::GET);
        $res = $request->getBody();
        if ($request->getStatus() !== 200) {
            $this->doError($url, $request->getStatus(), $res);
        }

        /** @var Mage_Core_Helper_Data $helper */
        $helper = Mage::helper('core');
        /** @var array $json */
        $json = $helper->jsonDecode($res);

        if (!isset($json['token'])) {
            $this->doError($url, $request->getStatus(), $res);
        }

        Mage::register($registryKey, $json['token']);
        return $json['token'];
    }

    /**
     * Get the minimal data required to get an authentication token
     *
     * @return array
     */
    protected function getMinimalData()
    {
        $zakekeUsername = Mage::getStoreConfig('futurenext_zakeke/settings/zakekeUsername');
        $zakekePassword = Mage::helper('core')->decrypt(
            Mage::getStoreConfig('futurenext_zakeke/settings/zakekePassword')
        );

        return array(
            'user' => $zakekeUsername,
            'pwd' => $zakekePassword
        );
    }

    /**
     * GET request to a Zakeke resource.
     *
     * @param string $url
     * @param array $authData - Authentication credentials.
     * @throws Exception
     * @return array
     */
    protected function getRawRequest($url, $authData)
    {
        $url = Futurenext_Zakeke_Helper_Data::ZAKEKE_API_URL . $url;

        $httpClient = new Varien_Http_Client($url);
        if ($authData !== null) {
            $token = $this->getToken($authData);
            $httpClient->setHeaders(array(
                'X-Auth-Token' => $token
            ));
        }

        $request = $httpClient->request(Varien_Http_Client::GET);
        $res = $request->getBody();
        if ($request->getStatus() !== 200) {
            $this->doError($url, $request->getStatus(), $res);
        }

        /** @var Mage_Core_Helper_Data $helper */
        $helper = Mage::helper('core');
        return $helper->jsonDecode($res);
    }

    /**
     * POST request to a Zakeke resource.
     *
     * @param string $url
     * @param array $postData
     * @param array $authData - Authentication credentials.
     * @throws Exception
     * @return array
     */
    protected function postRequest($url, $postData, $authData)
    {
        $url = Futurenext_Zakeke_Helper_Data::ZAKEKE_API_URL . $url;

        $httpClient = new Varien_Http_Client($url);

        if ($authData !== null) {
            $token = $this->getToken($authData);
            $httpClient->setHeaders('X-Auth-Token', $token);
        }

        /** @var Mage_Core_Helper_Data $helper */
        $helper = Mage::helper('core');

        $jsonData = $helper->jsonEncode($postData);
        $httpClient->setHeaders('Content-type', 'application/json');
        $httpClient->setRawData($jsonData);
        $request = $httpClient->request(Varien_Http_Client::POST);
        $res = $request->getBody();
        if ($request->getStatus() !== 200) {
            $this->doError($url, $request->getStatus(), $res);
        }

        return $helper->jsonDecode($res);
    }

    /**
     * Get the Zakeke design price
     *
     * @param string $designId - Zakeke design identifier
     * @throws Exception
     * @return array
     */
    public function getDesignPricing($designId)
    {
        return $this->getRawRequest('/api/designs/' . $designId . '/pricing', $this->getMinimalData());
    }

    /**
     * Get the Zakeke design preview files
     *
     * @param string $designId - Zakeke design identifier
     * @throws Exception
     * @return array
     */
    public function getPreview($designId)
    {
        $data = array(
            'docid' => $designId
        );
        $json = $this->getRawRequest('/api/designs/0/previewfiles?' . http_build_query($data),
            $this->getMinimalData());

        $previews = array();
        /** @var array $preview */
        foreach ($json as $preview) {
            if ($preview['format'] == 'SVG') {
                continue;
            }

            $previewObj = new stdClass();
            $previewObj->url = $preview['url'];
            $previewObj->label = $preview['sideName'];
            $previews[] = $previewObj;
        }

        return $previews;
    }

    /**
     * Get the Zakeke design output zip
     *
     * @param string $designId - Zakeke design identifier
     * @throws Exception
     * @return string
     */
    public function getZakekeOutputZip($designId)
    {
        $data = array(
            'docid' => $designId
        );
        $json = $this->getRawRequest('/api/designs/0/outputfiles/zip?' . http_build_query($data),
            $this->getMinimalData());
        return $json['url'];
    }

    /**
     * Associate the guest with a customer
     *
     * @param string $guestCode - Guest identifier
     * @param string $customerId - Customer identifier
     * @throws Exception
     * @return void
     */
    public function associateGuest($guestCode, $customerId)
    {
        $data = $this->getMinimalData();
        $data['vc'] = $guestCode;
        $data['cc'] = $customerId;

        $this->getToken($data);
    }

    /**
     * Order containing Zakeke customized products placed
     *
     * @param array $data - data of the order
     * @throws Exception
     * @return void
     */
    public function placeOrder($data)
    {
        $authData = $this->getMinimalData();
        if (isset($data['customerID'])) {
            $authData['cc'] = $data['customerID'];
        } elseif (isset($data['visitorID'])) {
            $authData['vc'] = $data['visitorID'];
        }

        $orderData = array(
            'orderCode' => $data['orderCode'],
            'sessionID' => $data['sessionID'],
            'total' => $data['total'],
            'orderStatusID' => $data['orderStatusID'],
            'details' => $data['details'],
            'marketplaceID' => '1'
        );
        $this->postRequest('/api/orderdocs', $orderData, $authData);
    }

    /**
     * Return an array of incompatible modules with hints how to solve the problem
     *
     * @param array $moduleList
     * @throws Exception
     * @return array
     */
    public function moduleChecker($moduleList)
    {
        $incompatibleModules = array();

        $json = $this->getRawRequest('/api/IncompatiblePlugins', $this->getMinimalData());

        foreach ($moduleList as $module) {
            /** @var array $incompatibleModule */
            foreach ($json as $incompatibleModule) {
                if ($module['name'] !== $incompatibleModule['name']) {
                    continue;
                }

                if ($module['setup_version'] >= $incompatibleModule['fromVersion']
                    || $module['setup_version'] <= $incompatibleModule['toVersion']
                ) {
                    $moduleInfo = new Varien_Object();
                    $moduleInfo->setName($module['name']);
                    $moduleInfo->setSetupVersion($module['setup_version']);
                    $moduleInfo->setHowToFixHtml($incompatibleModule['fix']);

                    $incompatibleModules[] = $moduleInfo;
                }
            }
        }

        return $incompatibleModules;
    }

    /**
     * Check if Zakeke is reachable from the shop server
     *
     * @return bool
     */
    public function isReachable()
    {
        try {
            $httpClient = new Varien_Http_Client(Futurenext_Zakeke_Helper_Data::ZAKEKE_BASE_URL);
            $request = $httpClient->request(Varien_Http_Client::GET);
            return $request->getStatus() >= 200 && $request->getStatus() < 400;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param string $url
     * @param int $status
     * @param string $body
     * @throws \Exception
     */
    protected function doError($url, $status, $body) {
        $msg = 'Failed to get ' . $url . ' ' . $status . ' ' . $body;
        $this->log($msg, Zend_Log::ERR);
        throw new Exception($msg);
    }

    /**
     * Log event to Zakeke server
     *
     * @param string $message
     * @param int $level
     *
     * @return void
     */
    public function log($message, $level = null)
    {
        $level = $level === null ? Zend_Log::DEBUG : $level;

        Mage::log(
            $message,
            $level,
            Futurenext_Zakeke_Helper_Data::ZAKEKE_LOG_FILE,
            true
        );
    }
}