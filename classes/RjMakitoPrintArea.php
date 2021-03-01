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
     * @var string
     */
    public $areacode;
    
    /**
     * @var string
     */
    public $areaname;
    
    /**
     * @var string
     */
    public $areawidth;
    
    /**
     * @var string
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
        'table' => 'rj_makito_printareas',
        'primary' => 'areacode',
        'fields' => [
            // Config fields
            'reference'     => ['type' => self::TYPE_STRING, 'required' => true],
            'name'          => ['type' => self::TYPE_STRING, 'required' => true],
            'teccode'       => ['type' => self::TYPE_STRING],
            'tecname'       => ['type' => self::TYPE_STRING],
            'maxcolour'     => ['type' => self::TYPE_INT],
            'includedcolour'=> ['type' => self::TYPE_INT],
            'areacode'      => ['type' => self::TYPE_INT],
            'areaname'      => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'],
            'areawidth'     => ['type' => self::TYPE_FLOAT],
            'areahight'     => ['type' => self::TYPE_FLOAT],
            'areaimg'       => ['type' => self::TYPE_STRING],
        ],
    ];

    public function __construct($areacode = null, $id_lang = null, $id_shop = null, Context $context = null)
	{
		parent::__construct($areacode, $id_lang, $id_shop);
	}

    public function existe($reference, $teccode, $areacode) {
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow("
			SELECT p.*
			FROM `"._DB_PREFIX_."rj_makito_printareas` p
			WHERE p.`reference` = '".$reference."'
            AND p.`teccode` = '".$teccode."'
            AND p.`areacode` = '".$areacode."'"
		);

        if($result){
            return true;
        }
        return false;
    }
} 