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
 * Zakeke model for retrieving the installed modules on the store
 *
 * @category    Futurenext
 * @package     Futurenext_Zakeke
 */
class Futurenext_Zakeke_Model_ModuleList extends Varien_Object
{
    protected $_lists = array();

    public function gather()
    {
        $config = Mage::getConfig();
        foreach($config->getNode('modules')->children() as $item)
        {
            $o = new Varien_Object();
            $o->setName($item->getName());
            $o->setActive((string)$item->active);
            $o->setCodePool((string)$item->codePool);

            //use same logic from Mage_Core_Model_Config::getModuleDir
            //but recreated here to allow for poorly configued modules
            $codePool 	= $config->getModuleConfig($item->getName())->codePool;
            $dir		= $config->getOptions()->getCodeDir().DS.$codePool.DS.uc_words($item->getName(),DS);
            $o->setPath($dir);

            $exists = file_exists($o->getPath());
            $exists = $exists ? 'yes' : 'no';
            $o->setPathExists($exists);

            $exists = file_exists($o->getPath() . '/etc/config.xml');
            $exists = $exists ? 'yes' : 'no';
            $o->setConfigExists($exists);
            $o->setModuleVersion('?');
            if($exists == 'yes')
            {
                $xml = simplexml_load_file($o->getPath() . '/etc/config.xml');
                $modules = $xml->modules;
                if(!$modules){ continue; }

                $name = $modules->{$item->getName()};
                if(!$name){ continue; }

                $version = $name->version;
                if(!$version) {
                    $version = '?';
                }

                $version = (string) $version;
                $o->setModuleVersion($version);
            }


            if(!array_key_exists($o->getCodePool(), $this->_lists))
            {
                $this->_lists[$o->getCodePool()] = array();
            }
            $this->_lists[$o->getCodePool()][] = $o;
        }

        return $this;
    }

    public function getCoreList()
    {
        return $this->_getList('core');
    }

    public function getLocalList()
    {
        return $this->_getList('local');
    }

    public function getCommunityList()
    {
        return $this->_getList('community');
    }

    public function getList($type = null)
    {
        if ($type) {
            return $this->_lists[$type];
        } else {
            return $this->_lists;
        }
    }

    public function getUnknownCodePools()
    {
        $known = array('core','local','community');
        $pools = array_keys($this->_lists);
        $final = array();
        foreach($pools as $item)
        {
            if(!in_array($item,$known))
            {
                $final[] = $item;
            }
        }
        return $final;
    }
}