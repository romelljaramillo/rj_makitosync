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


    public static function getItemsAreas($reference)
    {
        $sql = new DbQuery();
        $sql->select('it.*, pa.*, pj.cliche, pj.clicherep');
        $sql->from('rjmakito_itemprint', 'it');
        $sql->rightJoin('rjmakito_printarea', 'pa', 'it.areacode = pa.areacode AND it.reference = pa.reference');
        $sql->rightJoin('rjmakito_printjobs', 'pj', 'it.teccode = pj.teccode');
        $sql->where('it.reference = "' . $reference . '"');
        $sql->groupby('it.areacode');
        
        if($query = Db::getInstance()->executeS($sql)){
            foreach ($query as $key => $item) {
                $resp[$item['areacode']] = $item;
                $resp[$item['areacode']]['priceprint'] = 0;
                $resp[$item['areacode']]['active'] = false;
                $resp[$item['areacode']]['qcolors'] = 1;
                $resp[$item['areacode']]['clicheactive'] = 1;
                $resp[$item['areacode']]['printjobs'] = self::getTypePrint($item['areacode'], $item['reference']);
            }
            return $resp;
        }
    }

    public static function getTypePrint($areacode, $reference, $teccode = null)
    {
        $sql = new DbQuery();
        $sql->select('j.*, it.includedcolour, it.maxcolour, it.areacode');
        $sql->from('rjmakito_itemprint', 'it');
        if(is_null($teccode)){
            $sql->rightJoin('rjmakito_printjobs', 'j', 'it.teccode = j.teccode');
            $sql->where('it.areacode = ' . (int)$areacode . ' and it.reference ='. $reference);
        } else {
            $sql->innerJoin('rjmakito_printjobs', 'j', 'it.teccode = j.teccode');
            $sql->where('it.areacode = ' . (int)$areacode . ' and it.reference ='. $reference . ' and it.teccode ='. $teccode);
        }
        $sql->groupby('j.teccode');

        // $resp = Db::getInstance()->executeS($sql);

        if($query = Db::getInstance()->executeS($sql)){
            foreach ($query as $key => $item) {
                $resp[$item['teccode']] = $item;
            }
        }

        if(is_null($teccode)){
            return $resp;
        }
        
        return $resp[$teccode];
    }

    public static function calculaPrecioPrint($dataPrint)
    {
        $reference = $dataPrint['reference'];
        $cantidad = (int)$dataPrint['qty'];
        $areacode = $dataPrint['areacode'];
        $teccode = $dataPrint['teccode'];
        $cantidadcolor = (int)$dataPrint['qcolors'];
        $cliche = (int)$dataPrint['clicheactive'];;

        $dataTypePrint = self::getTypePrint($areacode, $reference, $teccode);
         
        for ($i=1; $i <= 7; $i++) { 
            $amountunder = (int)$dataTypePrint['amountunder' . $i];
            if ($amountunder > 0 && $cantidad <= $amountunder) {
                $typetarifa = $i;
                break;
            }
        }
          
        $priceTarifa = $dataTypePrint['price' . $typetarifa];
        $precioprint = $cantidad * $priceTarifa;
        if ($precioprint < $dataTypePrint['minjob']) {
            $precioprint = $dataTypePrint['minjob'];
            $preciounidad = $precioprint / $cantidad;
            $precioprint = $precioprint * $cantidadcolor;
        } else {
            if($cantidadcolor > 1){
                $precioprintcoloradicional = $cantidad * $dataTypePrint['priceaditionalcol' . $typetarifa] * $cantidadcolor;
                $precioprint += $precioprintcoloradicional;
            }
        }

        $priceCliche = ($cliche == 1) ? $dataTypePrint['cliche'] * $cantidadcolor : $dataTypePrint['clicherep'] * $cantidadcolor;

        return $precioprint + $priceCliche;
    }

} 