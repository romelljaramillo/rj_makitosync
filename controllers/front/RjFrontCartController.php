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
 * @author    Roanja
 * @copyright 2016-2021 Roanja
 * @license   LICENSE.txt
 */
class RjFrontCartController extends Module
{
   
    public function processCustomization()
    {
        $_GET;
        $_POST;
        $context_cart = Context::getContext()->cart;
        if (Tools::getValue('areacode')) {
            
            $price = 0;
            $index = 0;
            
            if (!$context_cart->id) {
                if (Context::getContext()->cookie->id_guest) {
                    $guest = new Guest(Context::getContext()->cookie->id_guest);
                    $context_cart->mobile_theme = $guest->mobile_theme;
                }
                $context_cart->add();
                if ($context_cart->id) {
                    Context::getContext()->cookie->id_cart = (int)$context_cart->id;
                }
            }
    
            if (!isset($context_cart->id)) {
                return false;
            }
    
            $id_cart = $context_cart->id;
            $id_product = Tools::getValue('id_product');
            $quantity = Tools::getValue('qty');

            if (!$field_ids = self::getCustomizationFieldIds($id_product)) {
                return false;
            }

            foreach ($field_ids as $field_id) {
                if ($field_id['is_module']) {
                    $index = (int)$field_id['id_customization_field'];
                }
            }

            if ($index) {
                $id_product_attribute = 0;
                $groups = Tools::getValue('group');
                if (!empty($groups)) {
                    $id_product_attribute = (int) Product::getIdProductAttributeByIdAttributes(
                        $id_product,
                        $groups,
                        true
                    );
                }

                $ids_rjmakito_cart = self::getIdMakitoCart($id_cart, $id_product, $id_product_attribute);

                if ($ids_rjmakito_cart) {
                    $id_customization = $context_cart->_addCustomization($id_product, $id_product_attribute, $index, Product::CUSTOMIZE_TEXTFIELD, json_encode($ids_rjmakito_cart), $quantity, true);
                    self::updateCustomizedData($id_customization);
                } else {
                    $context_cart->deleteCustomizationToProduct((int) $id_product, $index);
                }
            }
            return $id_customization;
        }
    }

    public function customizationExists($id_cart, $id_product, $id_product_attribute)
    {
        $req = 'SELECT c.`id_cart`
                FROM `'._DB_PREFIX_.'customization` c
                WHERE c.`id_cart` = '.(int)$id_cart.'
                AND c.`id_product` = '.(int)$id_product.'
                AND c.`id_product_attribute` = '.(int)$id_product_attribute;
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($req);

        return ($row);
    }

    public static function getMakitoCartById($id_rjmakito_cart)
    {
        return Db::getInstance()->getRow(
            'SELECT mc.* FROM `' . _DB_PREFIX_ . 'rjmakito_cart` mc
            WHERE mc.id_rjmakito_cart = ' . (int) $id_rjmakito_cart
        );
    }

    public static function getIdMakitoCart($id_cart, $id_product, $id_product_attribute)
    {
        $query = Db::getInstance()->executes(
            'SELECT mc.id_rjmakito_cart FROM `' . _DB_PREFIX_ . 'rjmakito_cart` mc
            WHERE mc.id_cart = ' . (int) $id_cart . '
            AND mc.id_product = ' . (int) $id_product . '
            AND mc.id_product_attribute = ' . (int) $id_product_attribute
        );

        foreach ($query as $value) {
            $resp[] = $value['id_rjmakito_cart'];
        }

        return $resp;
    }

    public function processUpdatePrintInCart($id_cart, $id_shop)
    {
        $id_product_attribute = 0;
        $id_product = (int)Tools::getValue('id_product');

        $id_product_attribute = 0;
        $groups = Tools::getValue('group');
        if (!empty($groups)) {
            $id_product_attribute = (int) Product::getIdProductAttributeByIdAttributes(
                $id_product,
                $groups,
                true
            );
        }
        
        $getvalues = self::getValuesPrintJobs();
        if($getvalues){
            foreach ($getvalues as $key => $value) {
                if(!$this->makitoCartExists($id_cart, $id_product_attribute, $value['areacode'])){
                    $result_add = Db::getInstance()->insert('rjmakito_cart', [
                        'id_cart' => (int)$id_cart,
                        'id_product_attribute' => $id_product_attribute,
                        'id_shop' => (int)$id_shop,
                        'areacode' => $value['areacode'],
                        'reference' => $value['reference'],
                        'teccode' => $value['teccode'],
                        'id_product' => $id_product,
                        'qty' => (int)$value['qty'],
                        'qcolors' => (int)$value['qcolors'],
                        'areawidth' => $value['areawidth'],
                        'areahight' => $value['areahight'],
                        'cliche' => $value['cliche'],
                        'price' => null,
                        'date_add' => date('Y-m-d H:i:s'),
                    ]);
                } else {
                    self::updateMakitoCartQuantity($id_cart, $id_product, $id_product_attribute, $value['areacode']);
                }
            }
        }
    }

    public function getCartProductQuantity($id_cart, $id_product, $id_product_attribute)
    {
        $req = 'SELECT cp.`quantity`
                FROM `'._DB_PREFIX_.'cart_product` cp
                WHERE cp.`id_cart` = '.(int)$id_cart.'
                AND cp.`id_product` = '.(int)$id_product.'
                AND cp.`id_product_attribute` = '.(int)$id_product_attribute;
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($req);
    }

    public function makitoCartExists($id_cart, $id_product_attribute, $areacode)
    {
        $req = 'SELECT mc.`id_cart`
                FROM `'._DB_PREFIX_.'rjmakito_cart` mc
                WHERE mc.`id_cart` = '.(int)$id_cart.'
                AND mc.`id_product_attribute` = '.(int)$id_product_attribute.'
                AND mc.`areacode` = '.$areacode;
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($req);

        return ($row);
    }
}