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
 * Zakeke new customization block
 *
 * @category    Futurenext
 * @package     Futurenext_Zakeke
 */
class Futurenext_Zakeke_Block_Customize_Add extends Futurenext_Zakeke_Block_Customize
{
    public function getAddtocartFormTarget()
    {
        return $this->getUrl('checkout/cart/add');
    }
}