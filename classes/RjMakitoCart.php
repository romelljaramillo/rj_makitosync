<?php
/**
 * 2007-2020 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */
class RjMakitoCart extends ObjectModel
{
    public $id_cart;
    public $id_product_attribute;
    public $id_shop;
    public $id_product;
    public $id_order;
    public $id_customer;
    public $id_customization;
    public $areacode;
    public $reference;
    public $teccode;
    public $qty;
    public $qcolors;
    public $areawidth;
    public $areahight;
    public $cliche;
    public $price;
    public $date_add;
    public $date_upd;
    
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'rjmakito_cart',
        'primary' => 'id_rjmakito_cart',
        'multilang_shop' => false,
        'fields' => [
            // Config fields
            'id_cart'       => ['type' => self::TYPE_INT],
            'id_product_attribute' => ['type' => self::TYPE_INT],
            'id_shop'       => ['type' => self::TYPE_INT],
            'id_product'    => ['type' => self::TYPE_INT],
            'id_order'       => ['type' => self::TYPE_INT],
            'id_customer'    => ['type' => self::TYPE_INT],
            'id_customization'  => ['type' => self::TYPE_INT],
            'areacode'      => ['type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true],
            'reference'     => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'required' => true],
            'teccode'       => ['type' => self::TYPE_STRING, 'required' => true],
            'qty'           => ['type' => self::TYPE_INT],
            'qcolors'       => ['type' => self::TYPE_INT],
            'areawidth'     => ['type' => self::TYPE_FLOAT],
            'areahight'     => ['type' => self::TYPE_FLOAT],
            'cliche'        => ['type' => self::TYPE_BOOL],
            'price'        => ['type' => self::TYPE_FLOAT],
            'date_add' =>   array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
            'date_upd' =>   array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat')
        ],
    ];

    public static function getMakitoCartById($id_rjmakito_cart)
    {
        return Db::getInstance()->getRow(
            'SELECT mc.* FROM `' . _DB_PREFIX_ . 'rjmakito_cart` mc
            WHERE mc.id_rjmakito_cart = ' . (int) $id_rjmakito_cart
        );
    }
    /**
     * Retorna el id 
     *
     * @param [int] $id_cart
     * @param [int] $id_product
     * @param [int] $id_product_attribute
     * @param [int] $areacode
     * @return void
     */
    public static function makitoCartExists($id_cart, $id_product, $id_product_attribute, $areacode)
    {
        $req = 'SELECT mc.`id_rjmakito_cart`
                FROM `'._DB_PREFIX_.'rjmakito_cart` mc
                WHERE mc.`id_cart` = '.(int)$id_cart.'
                AND mc.`id_product` = '.(int)$id_product.'
                AND mc.`id_product_attribute` = '.(int)$id_product_attribute.'
                AND mc.`areacode` = '.(int)$areacode;
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($req);

        return (int)$row['id_rjmakito_cart'];
    }

    public static function getIdMakitoCart($id_cart, $id_product, $id_product_attribute)
    {
        $query = Db::getInstance()->executes(
            'SELECT mc.id_rjmakito_cart, mc.id_customization FROM `' . _DB_PREFIX_ . 'rjmakito_cart` mc
            WHERE mc.id_cart = ' . (int) $id_cart . '
            AND mc.id_product = ' . (int) $id_product . '
            AND mc.id_product_attribute = ' . (int) $id_product_attribute
        );
        if (!$query) {
            return false;
        }
        foreach ($query as $value) {
            $resp['id_rjmakito_cart'][] = $value['id_rjmakito_cart'];
            $resp['id_customization'] = $value['id_customization'];
        }

        return $resp;
    }

    public static function getValuesMakitoCart($id_cart, $id_product, $id_product_attribute)
    {
        $dataPrint = [];

        $dataPrintCart = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'rjmakito_cart` mc
            WHERE mc.id_cart = ' . (int) $id_cart . '
            AND mc.id_product = ' . (int) $id_product . '
            AND mc.id_product_attribute = ' . (int) $id_product_attribute
        );

        foreach ($dataPrintCart as $data) {
            $areacode = $data['areacode'];
            $dataPrint[$areacode]['areacode'] = $areacode;
            $dataPrint[$areacode]['reference'] = $data['reference'];
            $dataPrint[$areacode]['teccode'] = $data['teccode'];
            $dataPrint[$areacode]['areawidth'] = $data['areawidth'];
            $dataPrint[$areacode]['areahight'] = $data['areahight'];
            $dataPrint[$areacode]['qcolors'] = $data['qcolors'];
            $dataPrint[$areacode]['clicheactive'] = $data['cliche'];
            $dataPrint[$areacode]['qty'] = $data['qty'];
        }

        return $dataPrint;
    }
}