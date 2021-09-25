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
    public $areacode;
    public $reference;
    public $teccode;
    public $id_cart;
    public $id_product;
    public $id_customer;
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
        'primary' => 'id_cart',
        'fields' => [
            // Config fields
            'id_cart'       => ['type' => self::TYPE_INT],
            'id_shop'       => ['type' => self::TYPE_INT],
            'areacode'      => ['type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true],
            'reference'     => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'required' => true],
            'teccode'       => ['type' => self::TYPE_STRING, 'required' => true],
            'id_order'       => ['type' => self::TYPE_INT],
            'id_product'    => ['type' => self::TYPE_INT],
            'id_customer'    => ['type' => self::TYPE_INT],
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
}