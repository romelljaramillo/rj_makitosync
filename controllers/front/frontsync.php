<?php
/**
 * 2007-2020 PrestaShop.
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
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

/**
 * @since 1.5.0
 */
class Rj_MakitoSyncFrontSyncModuleFrontController extends ModuleFrontController
{
    private $variables = [];
    private $printJobs;

    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        
        $areacode = Tools::getValue('areacode');
        $reference = Tools::getValue('reference');
        $action = Tools::getValue('action');
        $teccode = Tools::getValue('teccode');

        if ($action == 'selectArea') {
            $this->printJobs = $this->module->getTypePrint($areacode, $reference);
        }

        if ($action == 'selectColor') {
            
            $printJobs = $this->module->getTypePrint($areacode, $reference, $teccode);
            $this->printJobs = $printJobs[0];
        }
        

        if ($this->ajax) {
            header('Content-Type: application/json');
            $this->ajaxDie(json_encode($this->printJobs));
        }
    }

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();

        $this->context->smarty->assign('printJobs', $this->printJobs);
        $this->setTemplate('module:rj_makitosync/views/templates/hook/optionsprintJobs.tpl');
    }
}
