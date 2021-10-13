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

if (!defined('_PS_VERSION_')) {
    exit;
}

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

include_once(__DIR__ . '/classes/RjMakitoPrintjobs.php');
include_once(__DIR__ . '/classes/RjMakitoPrintArea.php');
include_once(__DIR__ . '/classes/RjMakitoItemPrint.php');
include_once(__DIR__ . '/classes/RjMakitoCart.php');

class Rj_MakitoSync extends Module
{
    private $reference;
    protected $_html = '';
    protected $config_form = false;
    private $url_import = '';
    private $ficheroDescargado;
    private $errors = array();
    private $newsPrintJobs = 0;
    private $newsItemPrint = 0;
    private $duplicadosPrintJobs = 0;
    private $duplicadosItemPrint = 0;

    /**
     * url de donde se descargan los xml
     *
     * @var array
     */
    // protected $nodesDowload = ['PrintJobsPrices'];
    protected $nodesDowload = ['PrintJobsPrices', 'ItemPrintingFile'];

    protected $nodeActual;
    /**
     * Nombre del parametro de api_key
     *
     * @var string
     */
    protected $namekey = 'pszinternal';

    public function __construct()
    {
        $this->name = 'rj_makitosync';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Roanja';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Makito sync ');
        $this->description = $this->l('makito web service sync module with prestashop');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        $this->ps_versions_compliancy = [
            'min' => '1.6',
            'max' => _PS_VERSION_
        ];
        // $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

        $this->url_import = dirname(__FILE__) . '/import/';
    }

    public function install()
    {
        // Configuration::updateValue('rj_makitosync_URL_SERVICE_FTP', false);
        if (
            parent::install()
            && $this->registerHook(
                array(
                    'actionFrontControllerSetMedia',
                    'header',
                    'backOfficeHeader',
                    'actionProductAdd',
                    'actionProductUpdate',
                    'displayBackOfficeHeader',
                    'displayHeader',
                    'displayAdminProductsExtra',
                    'displayProductAdditionalInfo',
                    'displayReassurance',
                    'displayProductListFunctionalButtons',
                    'actionCartSave',
                    'displayCustomization',
                    'actionObjectProductInCartDeleteAfter',
                    'actionCartUpdateQuantityBefore'
                )
            )
        ) {
            include(dirname(__FILE__) . '/sql/install.php');

            $this->installTab('AdminParentRJmakitosync', 'RJ Makito Sync');
            $this->installTab('AdminConfigMakitoSync', 'Configuration', 'AdminParentRJmakitosync');
            $this->installTab('AdminMakitoSync', 'Makito Sync', 'AdminParentRJmakitosync');

            return true;
        }

        return false;
    }

    public function installTab($className, $tabName, $tabParentName = false)
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = $className;
        $tab->name = array();

        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $tabName;
        }
        if ($tabParentName) {
            $tab->id_parent = (int)Tab::getIdFromClassName($tabParentName);
        } else {
            $tab->id_parent = 0;
        }

        $tab->module = $this->name;
        return $tab->add();
    }

    public function uninstall()
    {
        // OJO descomentar
        // Configuration::deleteByName('rj_makitosync_URL_SERVICE_FTP');
        // Configuration::deleteByName('rj_makitosync_URL_SERVICE_URL');
        // Configuration::deleteByName('rj_makitosync_URL_SERVICE_KEY_API');
        // Configuration::deleteByName('rj_makitosync_URL_SERVICE_PROVEEDOR');

        // include(dirname(__FILE__) . '/sql/uninstall.php');
        if (parent::uninstall()) {
            $this->uninstallTab('AdminParentRJmakitosync');
            $this->uninstallTab('AdminConfigMakitoSync');
            $this->uninstallTab('AdminMakitoSync');


            return true;
        }

        return false;
    }

    public function uninstallTab($tabName)
    {
        $id_tab = (int) Tab::getIdFromClassName($tabName);
        $tab = new Tab($id_tab);
        return $tab->delete();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (Tools::isSubmit('submitUrlService') || Tools::isSubmit('submitPriceIncrement')) {
            $this->postProcess();
        }

        if (Tools::isSubmit('manual_import')) {
            $this->importAchives();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $this->_html .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');
        $this->_html .= $this->renderFormPrice();
        $this->_html .= $this->renderFormUrlService();
        $this->_html .= $this->renderFormManualImport();
        $this->_html .= $this->renderList();

        return $this->_html;
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = [];
        if(Tools::isSubmit('submitUrlService')){
            $form_values = $this->getConfigFormValuesUrlService();
        }

        if(Tools::isSubmit('submitPriceIncrement')){
            $form_values = $this->getConfigFieldsFormPrice();
        }

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderFormUrlService()
    {
        // Get default language
        $defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');

        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $defaultLang;
        $helper->allow_employee_form_lang = $defaultLang;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitUrlService';
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValuesUrlService(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigFormUrlService()));
    }

    public function renderFormPrice(){
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->getTranslator()->trans('Settings', array(), 'Admin.Global'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->getTranslator()->trans('Increment', array(), 'Modules.Rj_makitosync.Admin'),
                        'name' => 'RJ_PRICE_INCREMENT',
                        'class' => 'fixed-width-sm',
                        'desc' => $this->getTranslator()->trans('Valor de incremento en precio.', array(), 'Modules.Rj_makitosync.Admin')
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->getTranslator()->trans('Tipo de incremento', array(), 'Modules.Rj_makitosync.Admin'),
                        'name' => 'RJ_PRICE_INCREMENT_TYPE',
                        'desc' => $this->getTranslator()->trans('Seleccione SI = Porcentaje ó NO = Valor', array(), 'Modules.Rj_makitosync.Admin'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->getTranslator()->trans('Porcentaje', array(), 'Modules.Rj_makitosync.Admin')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->getTranslator()->trans('Valor', array(), 'Modules.Rj_makitosync.Admin')
                            )
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->getTranslator()->trans('Alcance de incremento total', array(), 'Modules.Imageslider.Admin'),
                        'name' => 'RJ_PRICE_ALCANCE',
                        'desc' => $this->getTranslator()->trans('incremento al total del precio de todos los productos o solo la impresión.', array(), 'Modules.Imageslider.Admin'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->getTranslator()->trans('Enabled', array(), 'Admin.Global')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->getTranslator()->trans('Disabled', array(), 'Admin.Global')
                            )
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->getTranslator()->trans('Save', array(), 'Admin.Actions'),
                )
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form = array();

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitPriceIncrement';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsFormPrice(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($fields_form));
    }

    public function getConfigFieldsFormPrice()
    {
        return array(
            'RJ_PRICE_INCREMENT' => Configuration::get('RJ_PRICE_INCREMENT', true),
            'RJ_PRICE_INCREMENT_TYPE' => Configuration::get('RJ_PRICE_INCREMENT_TYPE', true),
            'RJ_PRICE_ALCANCE' => Configuration::get('RJ_PRICE_ALCANCE', null)
        );
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigFormUrlService()
    {
        $options = [
            [
                'id' => 1,
                'name' => 'Makito'
            ],
            [
                'id' => 2,
                'name' => 'Roanja'
            ]
        ];

        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Usa FTP'),
                        'name' => 'rj_makitosync_URL_SERVICE_FTP',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            ]
                        ],
                    ],
                    [
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-globe"></i>',
                        'name' => 'rj_makitosync_URL_SERVICE_URL',
                        'label' => $this->l('Url Servicio Web')
                    ],
                    [
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-unlock"></i>',
                        'desc' => $this->l('pszinternal'),
                        'name' => 'rj_makitosync_URL_SERVICE_KEY_API',
                        'label' => $this->l('Key API')
                    ],
                    [
                        'type' => 'select',
                        'lang' => true,
                        'label' => $this->l('Link Target'),
                        'name' => 'rj_makitosync_URL_SERVICE_PROVEEDOR',
                        'desc' => $this->l('Please Eneter Web Site URL Address.'),
                        'options' => [
                            'query' => $options,
                            'id' => 'id',
                            'name' => 'name'
                        ]
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValuesUrlService()
    {
        return array(
            'rj_makitosync_URL_SERVICE_FTP' => Configuration::get('rj_makitosync_URL_SERVICE_FTP', true),
            'rj_makitosync_URL_SERVICE_URL' => Configuration::get('rj_makitosync_URL_SERVICE_URL', null, null, null, 'http://print.makito.es:8080/user/xml'),
            'rj_makitosync_URL_SERVICE_KEY_API' => Configuration::get('rj_makitosync_URL_SERVICE_KEY_API', null),
            'rj_makitosync_URL_SERVICE_PROVEEDOR' => Configuration::get('rj_makitosync_URL_SERVICE_PROVEEDOR', null),
        );
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function renderFormManualImport()
    {
        $this->context->smarty->assign(
            array(
                'link' => $this->context->link
            )
        );
        return $this->display(__FILE__, 'manual_import.tpl');
    }

    public function importAchives()
    {
        foreach ($this->nodesDowload as $node) {
            $this->nodeActual = $node;
            $data_ws = $this->getConfigFormValuesUrlService();
            $url = $data_ws['rj_makitosync_URL_SERVICE_URL'] . '/' . $this->nodeActual . '.php?' . $this->namekey . '=' . $data_ws['rj_makitosync_URL_SERVICE_KEY_API'];
            $nameFile = date("Y-m-d") . '-' . $this->nodeActual . '.xml';

            if (file_exists($nameFile)) {
                $this->_html .= $this->displayInformation("El fichero $nameFile existe");
            } else {
                $this->getAPI($url, $nameFile);
            }

            if ($this->nodeActual) {
                $this->setData($nameFile);
            }
        }

        if (count($this->errors)) {
            $this->_html .= $this->displayError(implode('<br />', $this->errors));
        } else {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true) . '&conf=3&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name);
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

    private function processItemPrint($datos)
    {
        $this->reference = '';
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
        }
    }

    private function processPrintJobs($datos)
    {
        foreach ($datos as $data) {
            $idPrintJobs = $this->existePrintJobs($data['teccode']);

            if (!isset($idPrintJobs)) {
                $printjobs = new RjMakitoPrintjobs();
                $this->newsPrintJobs++;
            } else {
                $printjobs = new RjMakitoPrintjobs($idPrintJobs);
                $this->duplicadosPrintJobs++;
            }

            foreach ($data as $key => $value) {
                $printjobs->$key = (isset($value)) ? $value : NULL;
            }

            if (!isset($idPrintJobs)) {
                if (!$printjobs->add()) {
                    die();
                    $this->errors[] = $this->displayError($this->l('The item print could not be added.'));
                }
            } elseif (!$printjobs->update()) {
                $this->errors[] = $this->displayError($this->l('The item print could not be added.'));
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

    public function existePrintJobs($teccode)
    {
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            "SELECT `id_rjmakito_printjobs` as id
			FROM `" . _DB_PREFIX_ . "rjmakito_printjobs` p
			WHERE p.`teccode` = '" . $teccode . "'",
            false
        );

        return $row['id'];
    }

    public function existeItemPrint($teccode, $areacode)
    {
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            "SELECT `id_rjmakito_itemprint` as id
			FROM `" . _DB_PREFIX_ . "rjmakito_itemprint` p
			WHERE p.`reference` = '" . $this->reference . "'
            AND p.`teccode` = '" . $teccode . "'
            AND p.`areacode` = " . (int)$areacode,
            false
        );

        return $row['id'];
    }

    public function existePrintArea($areacode)
    {
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            "SELECT `id_rjmakito_printarea` as id
			FROM `" . _DB_PREFIX_ . "rjmakito_printarea` p
			WHERE p.`areacode` = " . (int)$areacode . "
            AND p.`reference` = '" . $this->reference . "'",
            false
        );

        return $row['id'];
    }

    protected function saveItemPrint($arrayItemPrint)
    {
        $id = $this->existeItemPrint($arrayItemPrint['teccode'], $arrayItemPrint['areacode']);

        if (!isset($id)) {
            $itemPrint = new RjMakitoItemPrint();
            $this->newsItemPrint++;
        } else {
            $itemPrint = new RjMakitoItemPrint($id);
            $this->duplicadosItemPrint++;
        }

        $itemPrint->reference = $arrayItemPrint['reference'];
        $itemPrint->teccode = $arrayItemPrint['teccode'];
        $itemPrint->maxcolour = $arrayItemPrint['maxcolour'];
        $itemPrint->includedcolour = $arrayItemPrint['includedcolour'];
        $itemPrint->areacode = $arrayItemPrint['areacode'];

        if (!isset($id)) {
            if (!$itemPrint->add()) {
                $this->errors[] = $this->displayError($this->l('The item print could not be added.'));
            }
        } elseif (!$itemPrint->update()) {
            $this->errors[] = $this->displayError($this->l('The item print could not be added.'));
        }

        return true;
    }

    protected function savePrintArea($arrayPrintArea)
    {
        $id = $this->existePrintArea($arrayPrintArea['areacode']);
        if (!isset($id)) {
            $PrintArea = new RjMakitoPrintArea();
            $this->newsPrintArea++;
        } else {
            $PrintArea = new RjMakitoPrintArea($id);
            $this->duplicadosPrintArea++;
        }

        $PrintArea->areacode = $arrayPrintArea['areacode'];
        $PrintArea->reference = $this->reference;
        $PrintArea->areaname = $arrayPrintArea['areaname'];
        $PrintArea->areawidth = $arrayPrintArea['areawidth'];
        $PrintArea->areahight = $arrayPrintArea['areahight'];
        $PrintArea->areaimg = $arrayPrintArea['areaimg'];

        if (!isset($id)) {
            if (!$PrintArea->add()) {
                $this->errors[] = $this->displayError($this->l('The print area could not be added.'));
            }
        } elseif (!$PrintArea->update()) {
            $this->errors[] = $this->displayError($this->l('The print area could not be update.'));
        }

        return true;
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

    public function getData()
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
        SELECT * FROM ' . _DB_PREFIX_ . 'rjmakito_printjobs');
    }

    public function renderList()
    {
        $printjobs = $this->getData();

        $fields_list = array(
            'teccode' => array(
                'title' => $this->l('teccode'),
                'width' => 50,
                'search' => false
            ),
            'code' => array(
                'title' => $this->l('code'),
                'width' => 'auto',
                'search' => false
            ),
            'name' => array(
                'title' => $this->l('name'),
                'width' => 'auto',
                'search' => false
            ),
            'minamount' => array(
                'title' => $this->l('minamount'),
                'width' => 'auto',
                'search' => false
            ),
            'cliche' => array(
                'title' => $this->l('cliche'),
                'width' => 'auto',
                'search' => false
            ),
            'clicherep' => array(
                'title' => $this->l('clicherep'),
                'width' => 'auto',
                'search' => false
            ),
            'minjob' => array(
                'title' => $this->l('minjob'),
                'width' => 'auto',
                'search' => false
            ),
            'amountunder1' => array(
                'title' => $this->l('amountunder1'),
                'width' => 'auto',
                'search' => false
            ),
            'price1' => array(
                'title' => $this->l('price1'),
                'width' => 'auto',
                'search' => false
            ),
            'priceaditionalcol1' => array(
                'title' => $this->l('priceaditionalcol1'),
                'width' => 'auto',
                'search' => false
            ),
            'pricecm1' => array(
                'title' => $this->l('pricecm1'),
                'width' => 'auto',
                'search' => false
            )
        );

        $helper_list = new HelperList();
        $helper_list->module = $this;
        $helper_list->title = $this->l('printjobs');
        $helper_list->shopLinkType = '';
        $helper_list->no_link = true;
        $helper_list->title_icon = 'icon-folder';
        $helper_list->show_toolbar = false;
        $helper_list->simple_header = false;
        $helper_list->identifier = 'id_rjmakito_printjobs';
        $helper_list->table = 'rjmakito_printjobs';
        $helper_list->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name;
        $helper_list->token = Tools::getAdminTokenLite('AdminModules');
        $helper_list->listTotal = count($printjobs);

        $page = ($page = Tools::getValue('submitFilter' . $helper_list->table)) ? $page : 1;
        $pagination = ($pagination = Tools::getValue($helper_list->table . '_pagination')) ? $pagination : 50;
        $printjobs = $this->paginatePrintjobs($printjobs, $page, $pagination);

        return $helper_list->generateList($printjobs, $fields_list);
    }

    public function paginatePrintjobs($printjobs, $page = 1, $pagination = 50)
    {
        if (count($printjobs) > $pagination) {
            $printjobs = array_slice($printjobs, $pagination * ($page - 1), $pagination);
        }

        return $printjobs;
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookBackOfficeHeader()
    {
        $this->context->controller->addJS($this->_path . 'views/js/back.js');
        $this->context->controller->addCSS($this->_path . 'views/css/back.css');
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        // $this->context->controller->addJS($this->_path . '/views/js/front_makito.js');
        // $this->context->controller->registerJavascript('modules-rjmakitosync', 'modules/' . $this->name . '/js/front_makitosync.js');

        // $this->context->controller->addCSS($this->_path . '/views/css/front.css');
    }

    public function hookDisplayAdminProductsExtra($params)
    {
        $id_shop = (int)Shop::getContextShopID();
        $id_lang = (int)$this->context->language->id;

        $idProduct = (int) $params['id_product'];
        $product = new Product((int)$idProduct);

        $printjobs = $this->getPrintJobsItemsAreas($product->reference);

        $this->context->smarty->assign(
            array(
                'printjobs' => $printjobs,
                'idProduct' => $idProduct
            )
        );
        return $this->display(__FILE__, 'admin_product.tpl');
    }

    public function getPrintJobsItemsAreas($reference)
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('rjmakito_itemprint', 'it');
        $sql->rightJoin('rjmakito_printarea', 'a', 'it.areacode = a.areacode');
        $sql->rightJoin('rjmakito_printjobs', 'j', 'it.teccode = j.teccode');
        $sql->where('it.reference = ' . $reference);

        return Db::getInstance()->executeS($sql);
    }

    public function hookActionProductAdd($params)
    {
        // dump($params);
    }

    public function getCartProductQuantity($id_cart, $id_product, $id_product_attribute)
    {
        $req = 'SELECT cp.`quantity`
                FROM `'._DB_PREFIX_.'cart_product` cp
                WHERE cp.`id_cart` = '.(int)$id_cart.'
                AND cp.`id_product` = '.(int)$id_product.'
                AND cp.`id_product_attribute` = '.(int)$id_product_attribute;
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($req);
    }

    public function setMakitoCart($id_cart, $id_shop, $id_customer=null)
    {
        $_GET;
        $_POST;
        $id_product_attribute = 0;
        $id_product = (int)Tools::getValue('id_product');

        if (Tools::getIsset('group')) {
            $id_product_attribute = (int) Product::getIdProductAttributeByIdAttributes(
                $id_product,
                Tools::getValue('group'),
                true
            );
        }
        
        $getvalues = self::getValuesPrintJobs();
        if($getvalues){
            foreach ($getvalues as $key => $value) {
                $id_rjmakito_cart = RjMakitoCart::makitoCartExists($id_cart, $id_product, $id_product_attribute, $value['areacode']);
                if(!$id_rjmakito_cart){
                    $rjMakitoCart = new RjMakitoCart();
                    $rjMakitoCart->qty = (int)$value['qty'];
                }else{
                    $rjMakitoCart = new RjMakitoCart((int)$id_rjmakito_cart);
                    $qty = $this->getCartProductQuantity($id_cart, $id_product, $id_product_attribute);
                    $rjMakitoCart->qty = (int)$qty;
                }
                
                $rjMakitoCart->cliche = (int)$value['clicheactive'];
                $rjMakitoCart->qcolors = (int)$value['qcolors'];
                $rjMakitoCart->areahight = $value['areahight'];
                $rjMakitoCart->areawidth = $value['areawidth'];
                $rjMakitoCart->teccode = $value['teccode'];
                $rjMakitoCart->reference = $value['reference'];
                $rjMakitoCart->areacode = (int)$value['areacode'];

                $rjMakitoCart->id_cart = (int)$id_cart;
                $rjMakitoCart->id_product_attribute = (int)$id_product_attribute;
                $rjMakitoCart->id_shop = (int)$id_shop;
                $rjMakitoCart->id_product = (int)$id_product;
                $rjMakitoCart->id_customer = (int)$id_customer;
                    
                if(!$id_rjmakito_cart){
                    $rjMakitoCart->add();
                }else{
                    $rjMakitoCart->update();
                }
            }
        }
    }

    public function hookActionCartSave($params)
    {
        $cart = $params['cart'];
        if(Tools::getValue('controller') == "cart" && $cart){
            if (Tools::getIsset('add') || Tools::getIsset('update')) {
                $this->setMakitoCart($cart->id, $cart->id_shop, $cart->id_customer);
            } 
        }
    }

    public function hookDisplayCustomization($params)
    {
        $values = $params['customization']['value'];
        $customizations = json_decode($values,true);
        $arraycustomization = [];
        foreach ($customizations as $id_rjmakito_cart) {
            $rjMakitoCart =RjMakitoCart::getMakitoCartById($id_rjmakito_cart);
            $typePrint = RjMakitoItemPrint::getTypePrint($rjMakitoCart['areacode'], $rjMakitoCart['reference'], $rjMakitoCart['teccode']);
            $areaPrint = RjMakitoPrintArea::getNamePrintAreaByAreacodeRef($rjMakitoCart['areacode'], $rjMakitoCart['reference']);
            $rjMakitoCart['areaname'] = $areaPrint['areaname'];
            $rjMakitoCart['areaimg'] = $areaPrint['areaimg'];
            $rjMakitoCart['typeprint'] = $typePrint['name'];
            $arraycustomization[] = $rjMakitoCart;
        }

        $this->context->smarty->assign(
            array(
                'customizations' => $arraycustomization,
            )
        );

        if($params['cart']){
            return $this->display(__FILE__, 'customization.tpl');
        }else{
            return $this->display(__FILE__, 'customization-order.tpl');
        }
    }

    public function hookActionObjectProductInCartDeleteAfter($params)
    {
        $id_cart = $params['id_cart'];
        $id_product = $params['id_product'];
        $id_product_attribute = $params['id_product_attribute'];

        $result = Db::getInstance()->execute('
        DELETE FROM `' . _DB_PREFIX_ . 'rjmakito_cart`
        WHERE `id_cart` = '. (int)$id_cart .'
        AND `id_product` = ' . (int)$id_product . '
        AND `id_product_attribute` = ' . (int)$id_product_attribute);
    }

    public function getCustomizationFieldIds($id_product)
    {
        if (!Customization::isFeatureActive()) {
            return [];
        }

        return Db::getInstance()->executeS('
            SELECT *
            FROM `' . _DB_PREFIX_ . 'customization_field`
            WHERE `id_product` = ' . (int) $id_product);
    }

    public function addCustomization($id_customization)
    {
        $_GET;
        $_POST;
        $this->context = Context::getContext();
        $id_shop = $this->context->shop->id;
        // $context_cart = Context::getContext()->cart;
        if (Tools::getValue('areacode')) {
            
            $price = 0;
            $index = 0;
            
            if (!$this->context->cart->id) {
                if (Context::getContext()->cookie->id_guest) {
                    $guest = new Guest(Context::getContext()->cookie->id_guest);
                    $this->context->cart->mobile_theme = $guest->mobile_theme;
                }
                $this->context->cart->add();
                if ($this->context->cart->id) {
                    Context::getContext()->cookie->id_cart = (int)$this->context->cart->id;
                }
            }
    
            if (!isset($this->context->cart->id)) {
                return false;
            }
    
            $id_cart = $this->context->cart->id;
            $id_product = Tools::getValue('id_product');
            $quantity = Tools::getValue('qty');

            if (!$field_ids = self::getCustomizationFieldIds($id_product)) {
                return false;
            }

            foreach ($field_ids as $field_id) {
                if ($field_id['is_module']) {
                    $index = (int)$field_id['id_customization_field'];
                }
            }

            if ($index) {
                if (Tools::getIsset('group')) {
                    $id_product_attribute = (int) Product::getIdProductAttributeByIdAttributes(
                        $id_product,
                        Tools::getValue('group'),
                        true
                    );
                }
                
                $rjmakito_cart = RjMakitoCart::getIdMakitoCart($id_cart, $id_product, $id_product_attribute);

                if ($rjmakito_cart) {
                    if(!$rjmakito_cart['id_customization']){
                        $id_customization = $context_cart->_addCustomization($id_product, $id_product_attribute, $index, Product::CUSTOMIZE_TEXTFIELD, json_encode($rjmakito_cart['id_rjmakito_cart']), $quantity, true);
                        self::updateCustomizedData($id_customization);
                        
                        foreach ($rjmakito_cart['id_rjmakito_cart'] as $id) {
                            $rjMakitoCart = new RjMakitoCart($id);
                            $rjMakitoCart->id_customization = (int)$id_customization;
                            $rjMakitoCart->update();
                        }

                    } else {
                        return $rjmakito_cart['id_customization']; 
                    }
                } elseif($id_cart) {
                    $this->setMakitoCart($id_cart, $id_shop);
                    $this->addCustomization($id_customization);
                } else {
                    $context_cart->deleteCustomizationToProduct((int) $id_product, $index);
                }
            }
            return $id_customization;
        }
    }


    /**
     * Update customized data entry
     * @param $id_customization
     * @param $index
     * @param $id_module
     * @param $cart_unit_collection
     */
    // public function updateCustomizedData($id_customization, $index, $price, $value)
    public static function updateCustomizedData($id_customization)
    {
        $module = Module::getInstanceByName('rj_makitosync');
        Db::getInstance()->update(
            'customized_data',
            array(
                'id_customization' => (int)$id_customization,
                'id_module' => (int)$module->id,
            ),
            'id_customization = ' . (int)$id_customization
        );
    }

    public static function getValuesPrintJobs()
    {
        $_POST;
        $_GET;
        if (Tools::getValue('areacode')) {
            $dataPrint = [];
            $areacodes = Tools::getValue('areacode');
            $reference = Tools::getValue('reference');
            $teccode = Tools::getValue('teccode');
            $areawidth = Tools::getValue('areawidth');
            $areahight = Tools::getValue('areahight');
            $qcolors = Tools::getValue('qcolors');
            $clicheactive = Tools::getValue('cliche');
            $qty = (int)Tools::getValue('qty');

            foreach ($areacodes as $areacode) {
                $dataPrint[$areacode]['areacode'] = $areacode;
                $dataPrint[$areacode]['reference'] = $reference;
                $dataPrint[$areacode]['teccode'] = $teccode[$areacode];
                $dataPrint[$areacode]['areawidth'] = $areawidth[$areacode];
                $dataPrint[$areacode]['areahight'] = $areahight[$areacode];
                $dataPrint[$areacode]['qcolors'] = $qcolors[$areacode];
                $dataPrint[$areacode]['clicheactive'] = $clicheactive[$areacode];
                $dataPrint[$areacode]['qty'] = $qty;
            }
            return $dataPrint;
    
        } 
        
        return false;
    }

    public function hookDisplayProductAdditionalInfo($params)
    {
        $paramsProduct = $params['product'];
        $dataProduct=[];

        $id_product = (int)$params['product']['id_product'];
        $reference = $params['product']['reference'];        
        $printjobs = RjMakitoItemPrint::getItemsAreas($reference);
        
        // ojo eliminar
        $dataget = $_GET;
        $_POST;

        $activar = 0;
        $getvalues = self::getValuesPrintJobs();
        if($getvalues){
            foreach ($getvalues as $areacode => $dataprint) {
                $printjobs[$areacode]['maxcolour'] = $printjobs[$areacode]['printjobs'][$dataprint['teccode']]['maxcolour'];
                $dataprint['qcolors'] = ($printjobs[$areacode]['maxcolour'] < $dataprint['qcolors']) ? $printjobs[$areacode]['maxcolour'] : $dataprint['qcolors'];
                $printjobs[$areacode]['cliche'] = $printjobs[$areacode]['printjobs'][$dataprint['teccode']]['cliche'] * $dataprint['qcolors'];
                $printjobs[$areacode]['clicherep'] = $printjobs[$areacode]['printjobs'][$dataprint['teccode']]['clicherep'] * $dataprint['qcolors'];
                $activar = $areacode;  
                $printjobs[$areacode]['active'] = true;

                $printjobs[$areacode]['priceprint'] = RjMakitoItemPrint::calculaPrecioPrint($dataprint);
                $printjobs[$areacode] = array_merge($printjobs[$areacode],$dataprint);
            }

        }

        if($printjobs){
            $this->context->smarty->assign(
                array(
                    'reference' => $reference,
                    'printjobs' => $printjobs,
                    'idProduct' => $id_product,
                    'dataget' => $dataget,
                    'getvalues' => $getvalues,
                    'activar' => (!$activar)?1:$activar,
                )
            );

            return $this->display(__FILE__, 'printjobs_product.tpl');
        }
    }

    public function hookActionFrontControllerSetMedia()
    {
        Media::addJsDef([
            'rjmakitosync_front' => $this->context->link->getModuleLink($this->name, 'frontsync'),
        ]);

        // $this->context->controller->registerJavascript('modules-rjmakitosync', 'modules/' . $this->name . '/js/front_makitosync.js');
        $this->context->controller->registerJavascript('modules-makitosync', 'modules/' . $this->name . '/js/front_makitosync.js', ['position' => 'bottom', 'priority' => 80]);

    }

    public function hookActionProductUpdate()
    {
        /* Place your code here. */
    }

    public function hookDisplayBackOfficeHeader()
    {
        $this->context->controller->addJS($this->_path . 'views/js/back.js');
        $this->context->controller->addCSS($this->_path . 'views/css/back.css');
    }

    public function hookDisplayHeader($params)
    {
        $this->context->controller->registerStylesheet('modules-makitosync', 'modules/' . $this->name . '/css/front_makitosync.css', ['media' => 'all', 'priority' => 150]);

        // Media::addJsDef([
        //     'url_makitosync' => $this->context->link->getModuleLink($this->name, 'makitosync', [], true),
        // ]);
        
        // $this->context->controller->registerJavascript('modules-makitosync', 'modules/' . $this->name . '/js/front_makitosync.js', ['position' => 'bottom', 'priority' => 150]);
    }

    public function hookDisplayProductListFunctionalButtons()
    {
    }
}
