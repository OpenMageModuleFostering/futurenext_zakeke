<?php
/*******************************************************
 * Copyright (C) 2017 Zakeke
 *
 * This file is part of Zakeke.
 *
 * Zakeke can not be copied and/or distributed without the express
 * permission of Zakeke
 *******************************************************/


/* @var Futurenext_Zakeke_Model_Resource_Setup $installer */
$installer = $this;

$installer->startSetup();

/**
 * Create table 'futurenext_zakeke_enabled'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('futurenext_zakeke_enabled'))
    ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ), 'Entity ID')
    ->addColumn('sku', Varien_Db_Ddl_Table::TYPE_TEXT, 64, array(
        'nullable'  => false
    ), 'SKU')
    ->addIndex($installer->getIdxName('futurenext_zakeke_enabled', array('sku')),
        array('sku'))
    ->setComment('Zakeke Customizable Product Table');
$installer->getConnection()->createTable($table);

/**
 * Save rules with resources
 */

/** @var Mage_Api2_Model_Acl_Global_Role $role */
$role = Mage::getModel('api2/acl_global_role');
$role->setRoleName('Zakeke');
$role->save();
$roleId = $role->getId();
$resources = array(
    'zakekeSettings' => array('retrieve'),
    'zakekeProductOptions' => array('create', 'retrieve', 'update', 'delete'),
    'zakekeColors' => array('retrieve'),
    'zakekeEnabled' => array('create', 'retrieve', 'update', 'delete'),
    'product' => array('retrieve'),
    'product_category' => array('retrieve'),
    'product_image' => array('retrieve'),
    'stock_item' => array('retrieve'),
    'order' => array('retrieve'),
    'order_item' => array('retrieve')
);

/** @var Mage_Api2_Model_Acl_Global_Rule $rule */
$rule = Mage::getModel('api2/acl_global_rule');
foreach ($resources as $resourceId => $privileges) {
    foreach ($privileges as $privilege) {
        $rule->setId(null)
            ->isObjectNew(true);

        $rule->setRoleId($roleId)
            ->setResourceId($resourceId)
            ->setPrivilege($privilege)
            ->save();
    }
}

/**
 * Save OAuth Consumer
 */

/** @var Mage_Oauth_Model_Consumer $oauthConsumer */
$oauthConsumer = Mage::getModel('oauth/consumer');
/** @var Mage_Oauth_Helper_Data $oauthHelper */
$oauthHelper = Mage::helper('oauth');

$oauthConsumer->setName('Zakeke');
$oauthConsumer->setKey($oauthHelper->generateConsumerKey());
$oauthConsumer->setSecret($oauthHelper->generateConsumerKey());
$oauthConsumer->setCallbackUrl(
    Futurenext_Zakeke_Helper_Data::ZAKEKE_BASE_URL . '/integration/magento1/success'
);
$oauthConsumer->save();

$installer->endSetup();
?>
