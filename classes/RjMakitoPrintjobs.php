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
class RjMakitoPrintjobs extends ObjectModel
{
    /**
     * @var string
     */
    public $teccode;

    /**
     * @var string
     */
    public $code;

    /**
     * @var string
     */
    public $name;

    /**
     * @var int
     */
    public $minamount;

    /**
     * @var float
     */
    public $cliche;

    /**
     * @var float
     */
    public $clicherep;

    /**
     * @var int
     */
    public $minjob;

    /**
     * @var int
     */
    public $amountunder1;

    /**
     * @var float
     */
    public $price1;

    /**
     * @var float
     */
    public $priceaditionalcol1;

    /**
     * @var float
     */
    public $pricecm1;

    /**
     * @var int
     */
    public $amountunder2;

    /**
     * @var float
     */
    public $price2;

    /**
     * @var float
     */
    public $priceaditionalcol2;

    /**
     * @var float
     */
    public $pricecm2;
    /**
     * @var int
     */
    public $amountunder3;

    /**
     * @var float
     */
    public $price3;

    /**
     * @var float
     */
    public $priceaditionalcol3;

    /**
     * @var float
     */
    public $pricecm3;
    /**
     * @var int
     */
    public $amountunder4;

    /**
     * @var float
     */
    public $price4;

    /**
     * @var float
     */
    public $priceaditionalcol4;

    /**
     * @var float
     */
    public $pricecm4;
    /**
     * @var int
     */
    public $amountunder5;

    /**
     * @var float
     */
    public $price5;

    /**
     * @var float
     */
    public $priceaditionalcol5;

    /**
     * @var float
     */
    public $pricecm5;
    /**
     * @var int
     */
    public $amountunder6;

    /**
     * @var float
     */
    public $price6;

    /**
     * @var float
     */
    public $priceaditionalcol6;

    /**
     * @var float
     */
    public $pricecm6;
    /**
     * @var int
     */
    public $amountunder7;

    /**
     * @var float
     */
    public $price7;

    /**
     * @var float
     */
    public $priceaditionalcol7;

    /**
     * @var float
     */
    public $pricecm7;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'rj_makito_printjobs',
        'primary' => 'teccode',
        'fields' => [
            // Config fields
            'teccode'            => ['type' => self::TYPE_STRING, 'required' => true],
            'code'               => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'],
            'name'               => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'],
            'minamount'          => ['type' => self::TYPE_INT],
            'cliche'             => ['type' => self::TYPE_FLOAT],
            'clicherep'          => ['type' => self::TYPE_FLOAT],
            'minjob'             => ['type' => self::TYPE_INT],
            'amountunder1'       => ['type' => self::TYPE_INT],
            'price1'             => ['type' => self::TYPE_FLOAT],
            'priceaditionalcol1' => ['type' => self::TYPE_FLOAT],
            'pricecm1'           => ['type' => self::TYPE_FLOAT],
            'amountunder2'       => ['type' => self::TYPE_INT],
            'price2'             => ['type' => self::TYPE_FLOAT],
            'priceaditionalcol2' => ['type' => self::TYPE_FLOAT],
            'pricecm2'           => ['type' => self::TYPE_FLOAT],
            'amountunder3'       => ['type' => self::TYPE_INT],
            'price3'             => ['type' => self::TYPE_FLOAT],
            'priceaditionalcol3' => ['type' => self::TYPE_FLOAT],
            'pricecm3'           => ['type' => self::TYPE_FLOAT],
            'amountunder4'       => ['type' => self::TYPE_INT],
            'price4'             => ['type' => self::TYPE_FLOAT],
            'priceaditionalcol4' => ['type' => self::TYPE_FLOAT],
            'pricecm4'           => ['type' => self::TYPE_FLOAT],
            'amountunder5'       => ['type' => self::TYPE_INT],
            'price5'             => ['type' => self::TYPE_FLOAT],
            'priceaditionalcol5' => ['type' => self::TYPE_FLOAT],
            'pricecm5'           => ['type' => self::TYPE_FLOAT],
            'amountunder6'       => ['type' => self::TYPE_INT],
            'price6'             => ['type' => self::TYPE_FLOAT],
            'priceaditionalcol6' => ['type' => self::TYPE_FLOAT],
            'pricecm6'           => ['type' => self::TYPE_FLOAT],
            'amountunder7'       => ['type' => self::TYPE_INT],
            'price7'             => ['type' => self::TYPE_FLOAT],
            'priceaditionalcol7' => ['type' => self::TYPE_FLOAT],
            'pricecm7'           => ['type' => self::TYPE_FLOAT],
            'terms'              => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'],
        ],
    ];
}