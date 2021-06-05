<?php
/**
* 2007-2021 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2021 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/
$sql = array();

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'rjmakito_printjobs` (
    `id_rjmakito_printjobs` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `teccode` varchar(50) NOT NULL,
    `code` varchar(50) NULL,
    `name` varchar(100) NOT NULL,
    `minamount` INT(10) UNSIGNED NULL,
    `cliche` DECIMAL(20,6) NULL,
    `clicherep` DECIMAL(20,6) NULL,
    `minjob` INT(10) UNSIGNED NULL,
    `amountunder1` INT(10) UNSIGNED NULL,
    `price1` DECIMAL(20,6) NULL, 
    `priceaditionalcol1` DECIMAL(20,6) NULL, 
    `pricecm1` DECIMAL(20,6) NULL, 
    `amountunder2` INT(10) UNSIGNED NULL,
    `price2` DECIMAL(20,6) NULL, 
    `priceaditionalcol2` DECIMAL(20,6) NULL, 
    `pricecm2` DECIMAL(20,6) NULL, 
    `amountunder3` INT(10) UNSIGNED NULL,
    `price3` DECIMAL(20,6) NULL, 
    `priceaditionalcol3` DECIMAL(20,6) NULL, 
    `pricecm3` DECIMAL(20,6) NULL, 
    `amountunder4` INT(10) UNSIGNED NULL, 
    `price4` DECIMAL(20,6) NULL, 
    `priceaditionalcol4` DECIMAL(20,6) NULL, 
    `pricecm4` DECIMAL(20,6) NULL, 
    `amountunder5` INT(10) UNSIGNED NULL, 
    `price5` DECIMAL(20,6) NULL, 
    `priceaditionalcol5` DECIMAL(20,6) NULL, 
    `pricecm5` DECIMAL(20,6) NULL, 
    `amountunder6` INT(10) UNSIGNED NULL, 
    `price6` DECIMAL(20,6) NULL, 
    `priceaditionalcol6` DECIMAL(20,6) NULL, 
    `pricecm6` DECIMAL(20,6) NULL, 
    `amountunder7` INT(10) UNSIGNED NULL, 
    `price7` DECIMAL(20,6) NULL, 
    `priceaditionalcol7` DECIMAL(20,6) NULL, 
    `pricecm7` DECIMAL(20,6) NULL, 
    `terms` TEXT NULL DEFAULT NULL COLLATE utf8mb4_general_ci,
    PRIMARY KEY  (`id_rjmakito_printjobs`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'rjmakito_printarea` (
    `id_rjmakito_printarea` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `areacode` INT(10) UNSIGNED NOT NULL,
    `reference` varchar(50) NOT NULL,
    `areaname` varchar(50) NULL,
    `areawidth` DECIMAL(20,6) NULL,
    `areahight` DECIMAL(20,6) NULL,
    `areaimg` varchar(250) NULL,
    PRIMARY KEY  (`id_rjmakito_printarea`,`areacode`,`reference`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'rjmakito_itemprint` (
    `id_rjmakito_itemprint` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `reference` varchar(50) NOT NULL,
    `teccode` varchar(50) NULL,
    `maxcolour` INT(10) UNSIGNED NULL,
    `includedcolour` INT(10) UNSIGNED NULL,
    `areacode` INT(10) UNSIGNED NULL,
    PRIMARY KEY  (`id_rjmakito_itemprint`,`reference`,`teccode`,`areacode`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';



foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}


