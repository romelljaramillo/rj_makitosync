<?php

/**
 * 2016-2018 ROANJA.COM
 *
 * NOTICE OF LICENSE
 *
 *  @author Romell Jaramillo <info@roanja.com>
 *  @copyright 2016-2018 ROANJA.COM
 *  @license GNU General Public License version 2
 *
 * You can not resell or redistribute this software.
 */

class AdminMakitoSyncController extends ModuleAdminController
{
    // private $cache;
    /**
     * @var Rj_MakitoSync
     */
    public $module;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'list';
        $this->lang = false;
        parent::__construct();
        $this->context = Context::getContext();
        $this->table = 'rjmakito_printjobs';
        $this->className = 'RjMakitoPrintjobs';
        $this->allow_export = true;
        $this->identifier = 'teccode';
        $this->_defaultOrderBy = 'teccode';
        $this->_defaultOrderWay = 'ASC';


        if (!$this->module->active) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminHome'));
        }

        $this->addRowAction('view');
        // $this->addRowAction('edit');

        if (!Tools::getValue('realedit')) {
            $this->deleted = false;
        }
        // $this->bulk_actions['exportToTxtCorreos'] = array(
        //     'text' => $correos->getMessage(0),
        //     'icon' => 'icon-file-text-o',
        // );
        $this->bulk_actions = array(
            'generateLabel' => array(
                'text' => $this->trans('Generate labels', array(), 'Modules.Rj_MakitoSync.Admin'),
                'confirm' => $this->trans('Genereate labels for selected items?', array(), 'Modules.Rj_MakitoSync.Admin'),
                'icon' => 'icon-file-text-o',
            ),
            'updateStatus' => array(
                'text' => $this->trans('Update Status', array(), 'Modules.Rj_MakitoSync.Admin'),
                'confirm' => $this->trans('Update status for selected items?', array(), 'Modules.Rj_MakitoSync.Admin'),
                'icon' => 'icon-edit'
            )
        );
        $this->getData();
    }

    public function renderFormManualImport()
    {
        $content = $this->context->smarty->fetch($this->module->getLocalPath() . '/views/templates/admin/manual_import.tpl');

        $this->context->smarty->assign(
            array(
                'content' => $this->content . $content,
                'link' => $this->context->link
            )
        );
    }

    public function initContent()
    {
        parent::initContent();
        $this->renderFormManualImport();
    }

    public function getData() {
        
        $this->getList((int)$this->context->language->id);

        if (!empty($this->_list)){
            foreach ($this->_list[0] as $key => $value) {
                if ($key !== 'terms') {
                    $this->fields_list[$key] =  array(
                        'title' => $key,
                        'width' => 'auto'
                    );
                }
            }
        } else {
            $this->fields_list = array(
                'teccode' => array(
                    'title' => $this->l('teccode'),
                    'width' => 50,
                ),
                'code' => array(
                    'title' => $this->l('code'),
                    'width' => 'auto',
                ),
                'name' => array(
                    'title' => $this->l('name'),
                    'width' => 'auto',
                ),
                'cliche' => array(
                    'title' => $this->l('cliche'),
                    'width' => 'auto',
                )
            );
        }

    }

    public function renderList()
    {   
        return parent::renderList();
    }

    /*public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_turnotipo'] = array(
                'href' => self::$currentIndex . '&addRj_MakitoSync&token=' . $this->token,
                'desc' => $this->trans('Add new Type of shift', array(), 'Modules.Rj_MakitoSync.Admin'),
                'icon' => 'process-icon-new'
            );
        }

        parent::initPageHeaderToolbar();
    }*/

    public function postProcess()
    {
        if (Tools::isSubmit('manual_import')) {
            // $this->module->printLabel((int) Tools::getValue($this->identifier));
            // dump('manual_import');
            $this->module->importAchives();
            // $this->module->readXML();
        }

        return parent::postProcess();
    }

    public function renderForm()
    {

        return parent::renderForm();
    }
}
