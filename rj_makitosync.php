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
include_once(__DIR__ . '/classes/RjMakitoImport.php');

class Rj_MakitoSync extends Module
{
    protected $_html = '';

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

        include(dirname(__FILE__) . '/sql/uninstall.php');
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
        if (Tools::isSubmit('submitUrlService') 
            || Tools::isSubmit('submitPriceIncrement')
            || Tools::isSubmit('manual_import')
        ) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $this->_html .= $this->displayInfoCron();
        $this->_html .= $this->renderFormPrice();
        $this->_html .= $this->renderFormUrlService();
        $this->_html .= $this->renderFormManualImport();
        $this->_html .= $this->renderList();
        $this->_html .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $this->_html;
    }

    /**
     * Get tarea programada CRON
     *
     * @return void
     */
    public function displayInfoCron()
    {
        $cron_url = Tools::getShopDomain(true, true) . __PS_BASE_URI__ . basename(_PS_MODULE_DIR_);
        $cron_url .= '/' .$this->name . '/cron_import.php?secure_key=' . md5(_COOKIE_KEY_ . Configuration::get('PS_SHOP_NAME'));

        $mens = $this->l('To run your cron tasks, please insert the following line into your cron task manager.');
        $output = '
        <div class="bootstrap">
        <div class="module_info info alert alert-info">
            <p>' . $mens . '</p>
            <ul class="list-unstyled">
                <li><code>0 * * * * curl "' . $cron_url . '"</code></li>
            </ul>
        </div>
        </div>';

        return $output;
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = [];
        if (Tools::isSubmit('manual_import')) {
            $this->importDataMakito();
        }

        if(Tools::isSubmit('submitUrlService')){
            $form_values = self::getConfigFormValuesUrlService();
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
            'fields_value' => self::getConfigFormValuesUrlService(), /* Add values for your inputs */
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
    public static function getConfigFormValuesUrlService()
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

    public function importDataMakito()
    {
        $this->_html .= date('d-m-Y H:i:s');
        $rjMakitoImport = new RjMakitoImport();
        $resp = $rjMakitoImport->processImport();
        $this->_html .= date('d-m-Y H:i:s');

        if(!$resp){
            $this->_html .= $this->displayError($this->l('Error in process import.'));
        }else{
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true) . '&conf=3&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name);
        }
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

    public function getCartProductQuantity($id_cart, $id_product, $id_product_attribute)
    {
        $req = 'SELECT cp.`quantity`
                FROM `'._DB_PREFIX_.'cart_product` cp
                WHERE cp.`id_cart` = '.(int)$id_cart.'
                AND cp.`id_product` = '.(int)$id_product.'
                AND cp.`id_product_attribute` = '.(int)$id_product_attribute;
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($req);
    }

    public static function updateMakitoCartQuantity($id_cart, $id_product, $id_product_attribute){
        $id_rjmakito_cart = RjMakitoCart::getIdMakitoCart($id_cart, $id_product, $id_product_attribute);
        $qty = self::getCartProductQuantity($id_cart, $id_product, $id_product_attribute);

        if($id_rjmakito_cart['id_rjmakito_cart']){
            foreach ($id_rjmakito_cart['id_rjmakito_cart'] as $id) {
                $rjMakitoCart = new RjMakitoCart((int)$id);
                $rjMakitoCart->qty = (int)$qty;
                $rjMakitoCart->update();
            }
        }
    }


    public static function updatePriceCustomizedData($id_cart, $id_product, $id_product_attribute, $id_customization)
    {
        $dataprint = RjMakitoCart::getValuesMakitoCart($id_cart, $id_product, $id_product_attribute);
        $priceprint = 0;
        if($dataprint){
            foreach ($dataprint as $datacode) {
                $priceprint += RjMakitoItemPrint::calculaPrecioPrint($datacode) / (int)$datacode['qty'];
            }
        }

        Db::getInstance()->update(
            'customized_data',
            ['price' => $priceprint],
            'id_customization = ' . (int)$id_customization
        );
    }

    public function setMakitoCart($id_cart)
    {
        $dataget = array_merge($_GET,$_POST);

        $id_product_attribute = 0;
        $id_product = (int)Tools::getValue('id_product');

        $id_shop = Shop::getContext();
        // revisar con y sin usuario
        $id_customer = Context::getContext()->customer->id;

        $id_product_attribute = 0;
        $group = Tools::getValue('group');
        if (!empty($group)) {
        // if (Tools::getIsset('group')) {
            $id_product_attribute = (int) Product::getIdProductAttributeByIdAttributes(
                $id_product,
                $group,
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
                
                $rjMakitoCart->price = (float)$value['price'];
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

    public function addCustomization($id_customization)
    {
        $dataget = array_merge($_GET,$_POST);

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
                $id_product_attribute = 0;
                $group = Tools::getValue('group');
                if (!empty($group)) {
                    $id_product_attribute = (int) Product::getIdProductAttributeByIdAttributes(
                        $id_product,
                        $group,
                        true
                    );
                }
                
                $rjmakito_cart = RjMakitoCart::getIdMakitoCart($id_cart, $id_product, $id_product_attribute);

                if ($rjmakito_cart) {
                    if(!$rjmakito_cart['id_customization']){
                        $id_customization = $this->context->cart->_addCustomization($id_product, $id_product_attribute, $index, Product::CUSTOMIZE_TEXTFIELD, json_encode($rjmakito_cart['id_rjmakito_cart']), 0, true);
                        self::updateCustomizedData($id_customization);
                        
                        foreach ($rjmakito_cart['id_rjmakito_cart'] as $id) {
                            $rjMakitoCart = new RjMakitoCart($id);
                            $rjMakitoCart->id_customization = (int)$id_customization;
                            $rjMakitoCart->update();
                        }
                        return $id_customization;
                    } else {
                        $id_customization = $rjmakito_cart['id_customization'];
                        $this->setMakitoCart($id_cart);

                        $rjmakito_cart = RjMakitoCart::getIdMakitoCart($id_cart, $id_product, $id_product_attribute);

                        foreach ($rjmakito_cart['id_rjmakito_cart'] as $id) {
                            $rjMakitoCart = new RjMakitoCart($id);
                            $rjMakitoCart->id_customization = (int)$id_customization;
                            $rjMakitoCart->update();
                        }

                        self::updateCustomizedData($id_customization, json_encode($rjmakito_cart['id_rjmakito_cart']));

                        return $id_customization; 
                    }
                } elseif($id_cart) {
                    $this->setMakitoCart($id_cart);
                    return $this->addCustomization($id_customization);
                } else {
                    $this->context->cart->deleteCustomizationToProduct((int) $id_product, $index);
                }
            }
            
            return $id_customization;
        }

        return $id_customization;
    }

    public static function updateCustomizedData($id_customization, $value = null)
    {
        $dataget = array_merge($_GET,$_POST);
        $module = Module::getInstanceByName('rj_makitosync');

        if($value){
            $data = [
                'id_module' => (int)$module->id,
                'value' => $value
            ];
        } else {
            $data = ['id_module' => (int)$module->id];
        }
        Db::getInstance()->update(
            'customized_data',
            $data,
            'id_customization = ' . (int)$id_customization
        );
    }

    public static function getValuesPrintJobs()
    {
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
                $priceprint = RjMakitoItemPrint::calculaPrecioPrint($dataPrint[$areacode]);
                $dataPrint[$areacode]['price'] = (float)$priceprint;
            }
            return $dataPrint;
    
        } 
        
        return false;
    }

    public static function incrementPriceRoanja($price)
    {
         $price_increment = Configuration::get('RJ_PRICE_INCREMENT', true);
         if (Configuration::get('RJ_PRICE_ALCANCE', true)) {
             if (Configuration::get('RJ_PRICE_INCREMENT_TYPE', true)) {
                 $price += $price * $price_increment / 100;
             } else {
                 $price += $price_increment;
             }
         }
         return $price;
    }

    public static function calculaPricePrintMakito($id_cart, $id_product,  $id_product_attribute, $quantity)
    {
        // ojo borrar
        $data = array_merge($_GET, $_POST);

        $pricePrint = 0;
        $dataprint = [];
        $id_shop = (int)Shop::getContextShopID();
        
        if(!$dataprint = Rj_MakitoSync::getValuesPrintJobs()){
            $dataprint = RjMakitoCart::getValuesMakitoCart($id_cart, $id_product,  $id_product_attribute);
        }

        if($dataprint){
            foreach ($dataprint as $datacode) {
                $pricePrint += RjMakitoItemPrint::calculaPrecioPrint($datacode) / $quantity;
            }
        }
        return $pricePrint;
    }

    public function hookActionProductAdd($params)
    {
        // dump($params);
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
        // $id_shop = (int)Shop::getContextShopID();
        // $id_lang = (int)$this->context->language->id;

        // $idProduct = (int) $params['id_product'];
        // $product = new Product((int)$idProduct);

        // $printjobs = $this->getPrintJobsItemsAreas($product->reference);

        // $this->context->smarty->assign(
        //     array(
        //         'printjobs' => $printjobs,
        //         'idProduct' => $idProduct
        //     )
        // );
        // return $this->display(__FILE__, 'admin_product.tpl');
    }

    public function hookActionCartSave($params)
    {
        $data = array_merge($_GET, $_POST);
        $cart = $params['cart'];
        if(Tools::getValue('controller') == "cart" && $cart){
            if (Tools::getIsset('add') || Tools::getIsset('update')) {
                $this->setMakitoCart($cart->id);
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

    public function hookDisplayProductAdditionalInfo($params)
    {
        $tpl_vars = $this->context->smarty->tpl_vars;

        $id_product = (int)$params['product']['id_product'];
        $reference = $params['product']['reference'];        
        $printjobs = RjMakitoItemPrint::getItemsAreas($reference);

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

                $priceprint = RjMakitoItemPrint::calculaPrecioPrint($dataprint);
                $priceprint = self::incrementPriceRoanja($priceprint);
                if($tpl_vars['tax_rate']->value && $tpl_vars['tax_enabled']->value && !$tpl_vars['customer_group_without_tax']->value){
                    $priceprint += $priceprint * $tpl_vars['tax_rate']->value / 100;
                }

                $printjobs[$areacode]['priceprint'] = $priceprint;

                $printjobs[$areacode] = array_merge($printjobs[$areacode],$dataprint);
            }

        }

        if($printjobs){
            $this->context->smarty->assign(
                array(
                    'reference' => $reference,
                    'printjobs' => $printjobs,
                    'idProduct' => $id_product,
                    'getvalues' => $getvalues,
                    'activar' => (!$activar)?1:$activar,
                    // 'dataget' => $dataget,
                    // 'params' => $params,
                    // 'productparam' => $tpl_vars
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
    }

    public function hookDisplayProductListFunctionalButtons()
    {
    }
}
