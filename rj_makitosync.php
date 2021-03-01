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

class Rj_MakitoSync extends Module
{
    protected $_html = '';
    protected $config_form = false;
    private $url_import = '';
    private $ficheroDescargado;

    /**
     * url de donde se descargan los xml
     *
     * @var array
     */
    protected $nodesDowload = ['ItemPrintingFile'];
    // protected $nodesDowload = ['PrintJobsPrices','ItemPrintingFile'];

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

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        // Configuration::updateValue('rj_makitosync_URL_SERVICE_FTP', false);

        if (parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('actionProductAdd') &&
            $this->registerHook('actionProductUpdate') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('displayProductListFunctionalButtons')
        ) {

            include(dirname(__FILE__).'/sql/install.php');

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
        Configuration::deleteByName('rj_makitosync_URL_SERVICE_FTP');
        Configuration::deleteByName('rj_makitosync_URL_SERVICE_URL');
        Configuration::deleteByName('rj_makitosync_URL_SERVICE_KEY_API');
        Configuration::deleteByName('rj_makitosync_URL_SERVICE_PROVEEDOR');

        include(dirname(__FILE__).'/sql/uninstall.php');
        if (parent::uninstall()){
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
        if (((bool)Tools::isSubmit('submit_url_service')) == true) {
            $this->postProcess();
        }

        if (Tools::isSubmit('manual_import')){
            $this->importAchives();
            
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $this->_html .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');
        $this->_html .= $this->renderFormUrlService();
        $this->_html .= $this->renderFormManualImport();
        $this->_html .= $this->renderList();

        return $this->_html;
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
        $helper->submit_action = 'submit_url_service';
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValuesUrlService(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigFormUrlService()));
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
            $url = $data_ws['rj_makitosync_URL_SERVICE_URL'] . '/'.$this->nodeActual.'.php?'.$this->namekey.'=' . $data_ws['rj_makitosync_URL_SERVICE_KEY_API'];
            $nameFile = date("Y-m-d").'-'. $this->nodeActual .'.xml';
            
            if (file_exists($nameFile)) {
                $this->_html .= $this->displayInformation("El fichero $nameFile existe");
            } else {
                // $this->getAPI($url, $nameFile);
            }

            if($this->nodeActual){
                $this->setData($nameFile);
            }
        }
    }   

    protected function getAPI($url, $nameFile) {
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

    public function setData($file) {

        $errors = array();

        $datos = $this->readXML($file);
        
        if($datos) {
            if($this->nodeActual === 'PrintJobsPrices')
            {
                foreach ($datos as $data) {
                    $update = true;
                    $printjobs = new RjMakitoPrintjobs((int)$data['teccode']);
                    if(is_null($printjobs->teccode))
                    {
                        // dump($printjobs->teccode);
                        $printjobs = new RjMakitoPrintjobs();
                        $update = false;
                        $printjobs->teccode = $data['teccode'];

                    }
                    
                    $printjobs->code = $data['code'];
                    $printjobs->name = $data['name'];
                    $printjobs->minamount = $data['minamount'];
                    $printjobs->cliche = $data['cliche'];
                    $printjobs->clicherep = $data['clicherep'];
                    $printjobs->minjob = $data['minjob'];
                    $printjobs->amountunder1 = $data['amountunder1'];
                    $printjobs->price1 = $data['price1'];
                    $printjobs->priceaditionalcol1 = $data['priceaditionalcol1'];
                    $printjobs->pricecm1 = $data['pricecm1'];
                    $printjobs->amountunder2 = $data['amountunder2'];
                    $printjobs->price2 = $data['price2'];
                    $printjobs->priceaditionalcol2 = $data['priceaditionalcol2'];
                    $printjobs->pricecm2 = $data['pricecm2'];
                    $printjobs->amountunder3 = $data['amountunder3'];
                    $printjobs->price3 = $data['price3'];
                    $printjobs->priceaditionalcol3 = $data['priceaditionalcol3'];
                    $printjobs->pricecm3 = $data['pricecm3'];
                    $printjobs->amountunder4 = $data['amountunder4'];
                    $printjobs->price4 = $data['price4'];
                    $printjobs->priceaditionalcol4 = $data['priceaditionalcol4'];
                    $printjobs->pricecm4 = $data['pricecm4'];
                    $printjobs->amountunder5 = $data['amountunder5'];
                    $printjobs->price5 = $data['price5'];
                    $printjobs->priceaditionalcol5 = $data['priceaditionalcol5'];
                    $printjobs->pricecm5 = $data['pricecm5'];
                    $printjobs->amountunder6 = $data['amountunder6'];
                    $printjobs->price6 = $data['price6'];
                    $printjobs->priceaditionalcol6 = $data['priceaditionalcol6'];
                    $printjobs->pricecm6 = $data['pricecm6'];
                    $printjobs->amountunder7 = $data['amountunder7'];
                    $printjobs->price7 = $data['price7'];
                    $printjobs->priceaditionalcol7 = $data['priceaditionalcol7'];
                    $printjobs->pricecm7 = $data['pricecm7'];
                    $printjobs->terms = $data['terms'];
                    
                    if (!$update) {
                        if (!$printjobs->add()) {
                            $errors[] = $this->displayError($this->getTranslator()->trans('The slide could not be added.', array(), 'Modules.Imageslider.Admin'));
                        }
                    } elseif (!$printjobs->update()) {
                        $errors[] = $this->displayError($this->getTranslator()->trans('The slide could not be updated.', array(), 'Modules.Imageslider.Admin'));
                    }
                }
            } else {
                $reference = '';
                $name = '';
                $teccode = '';
                $tecname = '';
                $maxcolour = '';
                $includedcolour = '';
                $count = 0;
                // proceso de guardado node ItemPrintingFile
                // dump(count($datos));
                foreach ($datos as $data) {
                    $printArea = new RjMakitoPrintArea();

                    $reference =$data['ref'];
                    $name = $data['name'];
                    if($data['printjobs']['printjob']){
                        foreach ($data['printjobs'] as $printjob) {
                            if($printjob['teccode']){
                                $teccode = $printjob['teccode'];
                                $tecname = $printjob['tecname'];
                                $maxcolour = $printjob['maxcolour'];
                                $includedcolour = $printjob['includedcolour'];
                                if($printjob['areas']['area']['areacode']){
                                    $printArea->reference = $reference;
                                    $printArea->name = $name;
                                    $printArea->teccode = $teccode;
                                    $printArea->tecname = $tecname;
                                    $printArea->maxcolour = $maxcolour;
                                    $printArea->includedcolour = $includedcolour;
                                    $printArea->areacode = $printjob['areas']['area']['areacode'];
                                    $printArea->areaname = $printjob['areas']['area']['areaname'];
                                    $printArea->areawidth = $printjob['areas']['area']['areawidth'];
                                    $printArea->areahight = $printjob['areas']['area']['areahight'];
                                    $printArea->areaimg = $printjob['areas']['area']['areaimg'];
                                    // $printArea->add();

                                    $result = $printArea->existe($reference, $teccode, $printArea->areacode);

                                    if($result){
                                        if (!$printArea->update()) {
                                            $errors[] = $this->displayError($this->getTranslator()->trans('The slide could not be added.', array(), 'Modules.Imageslider.Admin'));
                                        }
                                        $count++;
                                    } elseif(!$printArea->add()) {
                                            $errors[] = $this->displayError($this->getTranslator()->trans('The slide could not be added.', array(), 'Modules.Imageslider.Admin'));
                                    }

                                } else {
                                    foreach ($printjob['areas']['area'] as $area) {
                                        $printArea->reference = $reference;
                                        $printArea->name = $name;
                                        $printArea->teccode = $teccode;
                                        $printArea->tecname = $tecname;
                                        $printArea->maxcolour = $maxcolour;
                                        $printArea->includedcolour = $includedcolour;
                                        $printArea->areacode = $area['areacode'];
                                        $printArea->areaname = $area['areaname'];
                                        $printArea->areawidth = $area['areawidth'];
                                        $printArea->areahight = $area['areahight'];
                                        $printArea->areaimg = $area['areaimg'];
                                        
                                        $result = $printArea->existe($reference, $teccode, $printArea->areacode);

                                        if($result){
                                            if (!$printArea->update()) {
                                                $errors[] = $this->displayError($this->getTranslator()->trans('The slide could not be added.', array(), 'Modules.Imageslider.Admin'));
                                            }
                                            $count++;
                                        } elseif(!$printArea->add()) {
                                             $errors[] = $this->displayError($this->getTranslator()->trans('The slide could not be added.', array(), 'Modules.Imageslider.Admin'));
                                        }
                                    }
                                }
                            } else {
                                foreach ($printjob as $job) {
                                    $teccode = $job['teccode'];
                                    $tecname = $job['tecname'];
                                    $maxcolour = $job['maxcolour'];
                                    $includedcolour = $job['includedcolour'];

                                    if($job['areas']['area']['areacode']){

                                        $printArea->reference = $reference;
                                        $printArea->name = $name;
                                        $printArea->teccode = $teccode;
                                        $printArea->tecname = $tecname;
                                        $printArea->maxcolour = $maxcolour;
                                        $printArea->includedcolour = $includedcolour;
                                        $printArea->areacode = $job['areas']['area']['areacode'];
                                        $printArea->areaname = $job['areas']['area']['areaname'];
                                        $printArea->areawidth = $job['areas']['area']['areawidth'];
                                        $printArea->areahight = $job['areas']['area']['areahight'];
                                        $printArea->areaimg = $job['areas']['area']['areaimg'];
                                        
                                        $result = $printArea->existe($reference, $teccode, $printArea->areacode);

                                        if($result){
                                            if (!$printArea->update()) {
                                                $errors[] = $this->displayError($this->getTranslator()->trans('The slide could not be added.', array(), 'Modules.Imageslider.Admin'));
                                            }
                                            $count++;
                                        } elseif(!$printArea->add()) {
                                             $errors[] = $this->displayError($this->getTranslator()->trans('The slide could not be added.', array(), 'Modules.Imageslider.Admin'));
                                        }

                                    } else {
                                        foreach ($job['areas']['area'] as $area) {

                                            $printArea->reference = $reference;
                                            $printArea->name = $name;
                                            $printArea->teccode = $teccode;
                                            $printArea->tecname = $tecname;
                                            $printArea->maxcolour = $maxcolour;
                                            $printArea->includedcolour = $includedcolour;
                                            $printArea->areacode = $area['areacode'];
                                            $printArea->areaname = $area['areaname'];
                                            $printArea->areawidth = $area['areawidth'];
                                            $printArea->areahight = $area['areahight'];
                                            $printArea->areaimg = $area['areaimg'];
                                            
                                            $result = $printArea->existe($reference, $teccode, $printArea->areacode);

                                            if($result){
                                                if (!$printArea->update()) {
                                                    $errors[] = $this->displayError($this->getTranslator()->trans('The slide could not be added.', array(), 'Modules.Imageslider.Admin'));
                                                }
                                                $count++;
                                            } elseif(!$printArea->add()) {
                                                $errors[] = $this->displayError($this->getTranslator()->trans('The slide could not be added.', array(), 'Modules.Imageslider.Admin'));
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                dump($count);

                // return false;
            }   

            if (count($errors)) {
                $this->_html .= $this->displayError(implode('<br />', $errors));
            } elseif (Tools::isSubmit('manual_import') && $update) {
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true) . '&conf=4&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name);
            } elseif (Tools::isSubmit('manual_import') && !$update) {
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true) . '&conf=3&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name);
            }
        }
    }

    public function readXML($nameFile)
    {
        if(!is_null($nameFile)){
            $xml = simplexml_load_file($this->url_import . $nameFile);
            if($this->nodeActual === 'PrintJobsPrices'){
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

    public function getData() {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
        SELECT * FROM '._DB_PREFIX_.'rj_makito_printjobs');
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
        $helper_list->identifier = 'teccode';
        $helper_list->table = 'rj_makito_printjobs';
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
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValuesUrlService();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    public function hookActionProductAdd()
    {
        /* Place your code here. */
    }

    public function hookActionProductUpdate()
    {
        /* Place your code here. */
    }

    public function hookDisplayBackOfficeHeader()
    {
        /* Place your code here. */
    }

    public function hookDisplayHeader()
    {
        /* Place your code here. */
    }

    public function hookDisplayProductListFunctionalButtons()
    {
        /* Place your code here. */
    }
}
