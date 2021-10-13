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

        /**
     * Create customization before adding to cart, so id_customization can be assigned to cart product
     */
    protected function processChangeProductInCart()
    {

        if (!Module::isEnabled('rj_makitosync')) {
            parent::processChangeProductInCart();
        }
        
        include_once(_PS_MODULE_DIR_ . "rj_makitosync/rj_makitosync.php");
        $this->customization_id = rj_makitosync::processCustomization();
        parent::processChangeProductInCart();
    }
}
