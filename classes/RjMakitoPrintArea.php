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
class RjMakitoPrintArea extends ObjectModel
{
    /**
     * @var int
     */
    public $areacode;

    /**
     * @var int
     */
    public $reference;

    /**
     * @var string
     */
    public $areaname;
    
    /**
     * @var float
     */
    public $areawidth;
    
    /**
     * @var float
     */
    public $areahight;
    
    /**
     * @var string
     */
    public $areaimg;
    
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'rjmakito_printarea',
        'primary' => 'id_rjmakito_printarea',
        'fields' => [
            // Config fields
            'areacode'      => ['type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true],
            'reference'     => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'required' => true],
            'areaname'      => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'],
            'areawidth'     => ['type' => self::TYPE_FLOAT],
            'areahight'     => ['type' => self::TYPE_FLOAT],
            'areaimg'       => ['type' => self::TYPE_STRING],
        ],
    ];

    public static function getNamePrintAreaByAreacodeRef($areacode, $reference)
    {
        $sql = new DbQuery();
        $sql->select('it.*');
        $sql->from('rjmakito_printarea', 'it');
        $sql->where('it.reference = "' . $reference . '" AND it.areacode = "' . $areacode . '"');
        $sql->groupby('it.areacode');
        
        if ($query = Db::getInstance()->getRow($sql)){
            return $query;
        }

        return false;
    }

} 