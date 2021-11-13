<?php
use PrestaShop\PrestaShop\Core\Product\ProductInterface;
include_once(dirname(__FILE__) . '/RjMakitoPrintjobs.php');
include_once(dirname(__FILE__) . '/RjMakitoPrintArea.php');
include_once(dirname(__FILE__) . '/RjMakitoItemPrint.php');
include_once(dirname(__FILE__) . '/../rj_makitosync.php');

class RjMakitoImport extends Module
{
    private $reference;
    protected $_html = '';
    private $url_import = '';
    private $errors = array();
    private $newsPrintJobs = 0;
    private $newsItemPrint = 0;
    private $duplicadosPrintJobs = 0;
    private $duplicadosItemPrint = 0;
    protected $nodesDowload = [
        'PrintJobsPrices', 
        'ItemPrintingFile'
    ];
    protected $nodeActual;
    protected $namekey = 'pszinternal';
    
    public function __construct()
    {
        parent::__construct();

        $this->url_import = dirname(__FILE__) . '/../import/';
    }

    public function processImport()
    {
        foreach ($this->nodesDowload as $node) {
            $this->nodeActual = $node;
            $data_ws = Rj_MakitoSync::getConfigFormValuesUrlService();
            $url = $data_ws['rj_makitosync_URL_SERVICE_URL'] . '/' . $this->nodeActual . '.php?' . $this->namekey . '=' . $data_ws['rj_makitosync_URL_SERVICE_KEY_API'];
            $nameFile = date("Y-m-d") . '-' . $this->nodeActual . '.xml';

            if (!file_exists($nameFile)) {
                if(!$this->getAPI($url, $nameFile)){
                    return false;
                }
            }

            if ($this->nodeActual) {
                $this->setData($nameFile);
            }
        }

        if (count($this->errors)) {
            return false;
        } else {
            return true;
        }
    }

    protected function getAPI($url, $nameFile)
    {
        $archivo = fopen($this->url_import . $nameFile, "w+");
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 360);
        curl_setopt($ch, CURLOPT_FILE, $archivo);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $datos = curl_exec($ch);
        $infoCurl = curl_getinfo($ch);

        curl_close($ch);
        fclose($archivo);

        $numIntentos = 0;
        $correcto = false;

        if ($infoCurl['http_code'] == 200 && $numIntentos <= 10) {
            $correcto = true;
        }

        if ($correcto) {
            $resultado = $nameFile;
        } else {
            $resultado = false;
        }

        return $resultado;
    }

    public function setData($file)
    {
        $datos = $this->readXML($file);

        if ($datos) {
            if ($this->nodeActual === 'PrintJobsPrices') {
                $this->processPrintJobs($datos);
            } else {
                $this->processItemPrint($datos);
            }
        }
    }

    public function readXML($nameFile)
    {
        if (!is_null($nameFile)) {
            $xml = simplexml_load_file($this->url_import . $nameFile);
            if ($this->nodeActual === 'PrintJobsPrices') {
                $data = json_decode(json_encode($xml->printjobs), true);
                return $data['printjob'];
            } else {
                $data = json_decode(json_encode($xml), true);
                return $data['product'];
            }
        } else {
            return false;
        }
    }

    private function processItemPrint($datos)
    {
        $this->reference = '';
        ini_set( 'max_execution_time', 0);

        foreach ($datos as $data) {
            $arrayPrintjob = array();
            $arrayPrintjob['reference'] = $data['ref'];
            $this->reference = $data['ref'];
            if (isset($data['printjobs']['printjob'])) {
                foreach ($data['printjobs'] as $printjob) {
                    if (isset($printjob['teccode'])) {
                        $arrayPrintjob['teccode'] = $printjob['teccode'];
                        $arrayPrintjob['maxcolour'] = $printjob['maxcolour'];
                        $arrayPrintjob['includedcolour'] = $printjob['includedcolour'];
                        if (isset($printjob['areas']['area'])) {
                            if (isset($printjob['areas']['area']['areacode'])) {
                                $arrayPrintjob['areacode'] = $this->processAreas($printjob['areas']['area']);
                                $this->saveItemPrint($arrayPrintjob);
                            } else {
                                foreach ($printjob['areas']['area'] as $area) {
                                    $arrayPrintjob['areacode'] = $this->processAreas($area);
                                    $this->saveItemPrint($arrayPrintjob);
                                }
                            }
                        } else {
                            $arrayPrintjob['areacode'] = null;
                            $this->saveItemPrint($arrayPrintjob);
                        }
                    } else {
                        foreach ($printjob as $job) {
                            $arrayPrintjob['teccode'] = $job['teccode'];
                            $arrayPrintjob['maxcolour'] = $job['maxcolour'];
                            $arrayPrintjob['includedcolour'] = $job['includedcolour'];
                            if (isset($job['areas']['area'])) {
                                if (isset($job['areas']['area']['areacode'])) {
                                    $arrayPrintjob['areacode'] = $this->processAreas($job['areas']['area']);
                                    $this->saveItemPrint($arrayPrintjob);
                                } else {
                                    foreach ($job['areas']['area'] as $area) {
                                        $arrayPrintjob['areacode'] = $this->processAreas($area);
                                        $this->saveItemPrint($arrayPrintjob);
                                    }
                                }
                            } else {
                                $arrayPrintjob['areacode'] = null;
                                $this->saveItemPrint($arrayPrintjob);
                            }
                        }
                    }
                }
            }
            
            $id_product = $this->getIdProductByReference();
            if($id_product){
                $resp = $this->addCustomizationField($id_product);
                if($resp)
                    $resp = $this->updateProductCustomization($id_product);

            }
        }
    }

    private function processPrintJobs($datos)
    {
        foreach ($datos as $data) {
            $idPrintJobs = $this->existePrintJobs($data['teccode']);

            if (!$idPrintJobs) {
                $printjobs = new RjMakitoPrintjobs();
                $this->newsPrintJobs++;
            } else {
                $printjobs = new RjMakitoPrintjobs($idPrintJobs);
                $this->duplicadosPrintJobs++;
            }

            foreach ($data as $key => $value) {
                $printjobs->$key = (isset($value)) ? $value : NULL;
            }

            if (!$idPrintJobs) {
                if (!$printjobs->add()) {
                    $this->errors[] = $this->displayError(
                        $this->l('Error al intentar guardar PrintJobs de la referencia. ') .
                        $printjobs->teccode
                    );
                }
            } elseif (!$printjobs->update()) {
                $this->errors[] = $this->displayError(
                    $this->l('Error al intentar actualizar PrintJobs de la referencia. ') .
                    $printjobs->teccode
                );
            }
        }
    }

    private function processAreas($area = null)
    {
        if (isset($area['areacode'])) {
            $this->savePrintArea($area);
            return $area['areacode'];
        }

        return null;
    }

    protected function savePrintArea($arrayPrintArea)
    {
        $id_rjmakito_printarea = $this->existePrintArea($arrayPrintArea['areacode']);
        if (!$id_rjmakito_printarea) {
            $PrintArea = new RjMakitoPrintArea();
            $this->newsPrintArea++;
        } else {
            $PrintArea = new RjMakitoPrintArea($id_rjmakito_printarea);
            $this->duplicadosPrintArea++;
        }

        $PrintArea->areacode = $arrayPrintArea['areacode'];
        $PrintArea->reference = $this->reference;
        $PrintArea->areaname = $arrayPrintArea['areaname'];
        $PrintArea->areawidth = $arrayPrintArea['areawidth'];
        $PrintArea->areahight = $arrayPrintArea['areahight'];
        $PrintArea->areaimg = $arrayPrintArea['areaimg'];

        if (!$id_rjmakito_printarea) {
            if (!$PrintArea->add()) {
                $this->errors[] = $this->displayError(
                    $this->l('Error al intentar guardar printarea de la referencia. ') . 
                    $this->reference
                );
            }
        } elseif (!$PrintArea->update()) {
            $this->errors[] = $this->displayError(
                $this->l('Error al intentar actualizar printarea de la referencia. ') . 
                $this->reference
            );
        }

        return true;
    }

    public function existePrintJobs($teccode)
    {
        $sql = "SELECT p.`id_rjmakito_printjobs` as id
			FROM `" . _DB_PREFIX_ . "rjmakito_printjobs` p
			WHERE p.`teccode` = '" . $teccode . "'";
            
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql, false);
    }

    public function existeItemPrint($teccode, $areacode)
    {
        $sql = "SELECT p.`id_rjmakito_itemprint` as id
                FROM `" . _DB_PREFIX_ . "rjmakito_itemprint` p
                WHERE p.`reference` = '" . $this->reference . "'
                AND p.`teccode` = '" . $teccode . "'
                AND p.`areacode` = " . (int)$areacode;
        
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql, false);
    }

    public function existePrintArea($areacode)
    {
        $sql = "SELECT p.`id_rjmakito_printarea` as id
                FROM `" . _DB_PREFIX_ . "rjmakito_printarea` p
                WHERE p.`areacode` = " . (int)$areacode . "
                AND p.`reference` = '" . $this->reference . "'";

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql, false);
    }

    protected function saveItemPrint($arrayItemPrint)
    {
        $id_rjmakito_itemprint = $this->existeItemPrint($arrayItemPrint['teccode'], $arrayItemPrint['areacode']);

        if (!$id_rjmakito_itemprint) {
            $itemPrint = new RjMakitoItemPrint();
            $this->newsItemPrint++;
        } else {
            $itemPrint = new RjMakitoItemPrint($id_rjmakito_itemprint);
            $this->duplicadosItemPrint++;
        }

        $itemPrint->reference = $arrayItemPrint['reference'];
        $itemPrint->teccode = $arrayItemPrint['teccode'];
        $itemPrint->maxcolour = $arrayItemPrint['maxcolour'];
        $itemPrint->includedcolour = $arrayItemPrint['includedcolour'];
        $itemPrint->areacode = $arrayItemPrint['areacode'];

        if (!$id_rjmakito_itemprint) {
            if (!$itemPrint->add()) {
                $this->errors[] = $this->displayError(
                    $this->l('Error al intentar guardar ItemPrint de la referencia. ') . 
                    $arrayItemPrint['reference']);
            }
        } elseif (!$itemPrint->update()) {
            $this->errors[] = $this->displayError(
                $this->l('Error al intentar actualizar ItemPrint de la referencia. ') . 
                $arrayItemPrint['reference']);
        }

        return true;
    }

    protected function getIdProductByReference()
    {
        $sql ="SELECT p.`id_product` FROM " . _DB_PREFIX_ . "product p
                WHERE p.`reference` = '" . $this->reference . "'";
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql, false);

    }

    protected function addCustomizationField($id_product)
    {
        $id_customization_field = $this->getCustomizationFieldIdByIdProduct($id_product);
        if($id_customization_field){
            $customizationField = new CustomizationField((int)$id_customization_field);
        } else {
            $customizationField = new CustomizationField();
        }

        $customizationField->id_product = (int)$id_product;
        $customizationField->type = 1;
        $customizationField->required = 0;
        $customizationField->is_module = 1;
        $customizationField->is_deleted = 0;

        $languages = Language::getLanguages(false);
        foreach ($languages as $language) {
            $customizationField->name[$language['id_lang']] = 'printJobs';
        }

        if(!$id_customization_field){
            if(!$customizationField->add()){
                $this->errors[] = $this->displayError(
                    $this->l('Error al intentar guardar la customization product. ') .
                    $id_product
                );
            }
        } else {
            if(!$customizationField->update()){
                $this->errors[] = $this->displayError(
                    $this->l('Error al intentar actualizar la customization product. ') . 
                    $id_product
                );
            }
        }

        return true;
    }

    protected function updateProductCustomization($id_product)
    {
        $product = new Product($id_product);
        $product->id_type_redirected = 0;
        $product->redirect_type = ProductInterface::REDIRECT_TYPE_CATEGORY_MOVED_PERMANENTLY;
        $product->customizable = 1;

        $countField = $this->getCustomizationFieldIdByIdProductNoPrintJob($id_product);
        if($countField == 0){
            $product->text_fields = 1;
        } elseif($countField > 0 && $countField == $product->text_fields) {
            $product->text_fields += 1;
        }

        $product->update();
    }

    public function getCustomizationFieldIdByIdProductNoPrintJob($id_product)
    {
        $sql = "SELECT cf.`id_customization_field` FROM " . _DB_PREFIX_ . "customization_field cf
        INNER JOIN " . _DB_PREFIX_ . "customization_field_lang cfl 
        ON cf.`id_customization_field` = cfl.`id_customization_field`
        WHERE cf.`id_product` = " . (int)$id_product . " AND cfl.`name` != 'printJobs'";
        Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql, true, false);

        return Db::getInstance()->NumRows();
    }

    public function getCustomizationFieldIdByIdProduct($id_product)
    {
        $sql = "SELECT cf.`id_customization_field` FROM " . _DB_PREFIX_ . "customization_field cf
        INNER JOIN " . _DB_PREFIX_ . "customization_field_lang cfl 
        ON cf.`id_customization_field` = cfl.`id_customization_field`
        WHERE cf.`id_product` = " . (int)$id_product . " AND cf.`is_module` = 1 AND cfl.`name` = 'printJobs'";

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql, false);
    }
}
