<?php
/**
 * NOTICE OF LICENSE
 *
 * This file is licenced under the Software License Agreement.
 * With the purchase or the installation of the software in your application
 * you accept the licence agreement.
 *
 * You must not modify, adapt or create derivative works of this source code
 *
 * @author    Musaffar Patel
 * @copyright 2016-2021 Musaffar Patel
 * @license   LICENSE.txt
 */
class CartController extends CartControllerCore
{
    protected function processChangeProductInCart()
    {
        if (!Module::isEnabled('rj_makitosync')) {
            parent::processChangeProductInCart();
        }
        
        include_once(_PS_MODULE_DIR_ . "rj_makitosync/rj_makitosync.php");
        $rj_makitosync = new Rj_MakitoSync();
        $this->customization_id = $rj_makitosync->addCustomization($this->customization_id);
        parent::processChangeProductInCart();
        if (Tools::getValue('op')) {
            Rj_MakitoSync::updateMakitoCartQuantity($this->context->cart->id, $this->id_product, $this->id_product_attribute);
        }

        Rj_MakitoSync::updatePriceCustomizedData($this->context->cart->id, $this->id_product, $this->id_product_attribute, $this->customization_id);

    }
}
