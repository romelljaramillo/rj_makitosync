{*
* 2010-2018 Webkul.
*
* NOTICE OF LICENSE
*
* All rights is reserved,
* Please go through this link for complete license : https://store.webkul.com/license.html
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade this module to newer
* versions in the future. If you wish to customize this module for your
* needs please refer to https://store.webkul.com/customisation-guidelines/ for more information.
*
* @author Webkul IN <support@webkul.com>
    * @copyright 2010-2018 Webkul IN
    * @license https://store.webkul.com/license.html
    *}

<div class="col-md-12">
    <div class="row">
        <fieldset class="col-md-4 form-group">
            <label class="form-control-label">{l s='Test Input Box' mod='rj_makitosync'}</label>
            <input type="text" id="print_input" class="form-control" name="print_input" value="{$idProduct}">
            <small class="form-text text-muted"><em>{l s='This is test input field' mod='rj_makitosync'}</em></small>
        </fieldset>
    </div>
</div>

<div class="printjobs-list">
    <table class="table">
        <thead class="thead-default" id="printjobs_thead">
            <tr>
                <th><input type="checkbox" id="toggle-all-printjobs"></th>
                <th>Imagen</th>
                <th>Area</th>
                <th>Medida impresión</th>
                <th>Unidades min</th>
                <th>Color incluido</th>
                <th>Colores max</th>
                <th>Tipo</th> 
                <th scope="col" class="text-right">predeterminada</th>
                <th scope="col" class="text-right">estado</th> 
                <th scope="col" class="text-right" style="width: 3rem; padding-right: 2rem">
                    Acciones
                </th>
            </tr>
        </thead>
        <tbody class="rj-printjobs-list panel-group accordion" id="accordion_printjobs">
        {foreach from=$printjobs item=printjob}
            <tr class="printjob loaded" id="areacode_{$printjob.areacode}" data="{$printjob.areacode}" data-index="{$printjob.areacode}" style="display: table-row;">
                <td width="1%">
                    <input class="rj-printjob" type="checkbox" data-id="{$printjob.areacode}" data-index="{$printjob.areacode}">
                </td>
                <td class="img">
                    <img src="{$printjob.areaimg}"  class="img-responsive">
                </td>
                <td>
                    {$printjob.areaname}
                </td>
                <td>
                    {$printjob.areawidth|string_format:"%.2f"} - {$printjob.areahight|string_format:"%.2f"}
                </td>
                <td>
                    <input type="number" value="{$printjob.minjob}" class="form-control text-sm-right">
                </td>
                <td>
                    <input type="number" value="{$printjob.includedcolour}" class="form-control text-sm-right">
                </td>
                <td>
                    <input type="number" value="{$printjob.maxcolour}" class="form-control text-sm-right">
                </td>
                <td>
                    {$printjob.name}
                </td>
                <td class="text-center">
                    <input class="attribute-default" type="radio" data-id="{$printjob.areacode}">
                </td> 
                <td class="text-center">
                    <a href="#" onclick="unitProductAction(this, 'deactivate'); return false;">
                        <i class="material-icons action-enabled ">check</i>
                    </a>
                </td>
                <td class="text-right">
                    <div class="btn-group-action">
                        <div class="btn-group">
                            <a href="#"
                                title="" class="btn tooltip-link product-edit">
                                <i class="material-icons">mode_edit</i>
                            </a>
                            <button class="btn btn-link dropdown-toggle dropdown-toggle-split product-edit" data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                            </button>
                            <div class="dropdown-menu dropdown-menu-right" x-placement="bottom-start">
                                <a class="dropdown-item product-edit"
                                    href="#"
                                    target="_blank">
                                    <i class="material-icons">remove_red_eye</i>
                                    Vista previa
                                </a>
                                <a class="dropdown-item product-edit" href="#" onclick="unitProductAction(this, 'duplicate');">
                                    <i class="material-icons">content_copy</i>
                                    Duplicar
                                </a>
                                <a class="dropdown-item product-edit" href="#" onclick="unitProductAction(this, 'delete');">
                                    <i class="material-icons">delete</i>
                                    Eliminar
                                </a>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        {/foreach}

        </tbody>
    </table>
</div>