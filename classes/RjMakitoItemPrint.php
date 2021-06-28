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
class RjMakitoItemPrint extends ObjectModel
{
    /**
     * @var string
     */
    public $reference;
    
    /**
     * @var string
     */
    public $teccode;
    
    /**
     * @var int
     */
    public $maxcolour;
    
    /**
     * @var int
     */
    public $includedcolour;
    
    /**
     * @var int
     */
    public $areacode;
    
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'rjmakito_itemprint',
        'primary' => 'id_rjmakito_itemprint',
        'fields' => [
            // Config fields
            'reference'     => ['type' => self::TYPE_STRING, 'required' => true],
            'teccode'       => ['type' => self::TYPE_STRING, 'required' => true],
            'maxcolour'     => ['type' => self::TYPE_INT],
            'includedcolour'=> ['type' => self::TYPE_INT],
            'areacode'      => ['type' => self::TYPE_INT],
        ],
    ];

} 