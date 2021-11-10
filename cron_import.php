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
 *  @author    CorreosExpress/Departamento de integracion y desarrollo
 *  @copyright 2015-2020 Correos Express - Grupo Correos
 *  @license   LICENSE.txt
 *  @email peticiones@correosexpress.com
 */

include_once(dirname(__FILE__).'/../../config/config.inc.php');
// include_once(dirname(__FILE__).'/../../init.php');
include_once(dirname(__FILE__)."/classes/RjMakitoImport.php");


if (Tools::getIsset('secure_key')) {
    $secureKey = md5(_COOKIE_KEY_.Configuration::get('PS_SHOP_NAME'));
    if (!empty($secureKey) && $secureKey === $_GET['secure_key']) {
        $hora_inicial           = date('d-m-Y H:i:s');
        file_put_contents("log_cron_function.txt", "Comenzamos ejecucion Cron -> $hora_inicial".PHP_EOL);

        $cron = new RjMakitoImport();
        $resp = $cron->processImport();
        
        $hora_final       = date('d-m-Y H:i:s');
        $hora_final       = new DateTime($hora_final);
        $hora_inicial     = new DateTime($hora_inicial);
        $tiempo_ejecucion = $hora_inicial->diff($hora_final);

        if(!$resp){
            echo 'Error en el proceso';
            file_put_contents("log_cron_rjmakitosync.txt", "Tiempo transcurrido en ejecución => ".$tiempo_ejecucion->format('%h horas %i minutos %s segundos').PHP_EOL, FILE_APPEND);
            echo "Error: Finalizado cron con error<br>";
        } else {
            
            file_put_contents("log_cron_rjmakitosync.txt", "Tiempo transcurrido en ejecución => ".$tiempo_ejecucion->format('%h horas %i minutos %s segundos').PHP_EOL, FILE_APPEND);
            echo "Success: Finalizada cron con exito<br>";
        }
    }
}

