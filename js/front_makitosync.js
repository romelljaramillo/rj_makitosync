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
*
* Don't forget to prefix your containers with your own identifier
* to avoid any conflicts with others containers.
*/

$(document).ready(function () {
    $('body').on('change touchspin.on.startspin', '#accordionPrintJobs .printarea[name]', function (event) {
        event.preventDefault();
        let areacode = $(this).val();
        // var areacode2 = $(this).parents('.areacode').attr('data-areacode');
        console.log('selecciona posición de marcaje ' + areacode);
        // console.log('selecciona posición de marcaje ' + areacode2);
        let dataarea = getDataAreaForm(areacode);
        console.log(dataarea);

        if($(this).is(':checked')) {
            console.log('Seleccionado ');
            activeInputs(areacode, false);
            selectTypeprint(areacode, dataarea);
        } else {
            console.log('Des-seleccionado ');
            activeInputs(areacode);
        }
    });

    $('body').on('change touchspin.on.startspin', '#accordionPrintJobs .typeprint[name]', function (event) {
        event.preventDefault();
        let areacode = $(this).parents('.areacode').attr('data-areacode');
        console.log('selecciona tipo de marcaje ' + areacode);
        let dataarea = getDataAreaForm(areacode);
        console.log(dataarea);
        selectColors(areacode, dataarea)
    });

    prestashop.on('updatedProduct', (args) => {
        console.log(args);
        const {eventType} = args;
        const {event} = args;
    }); 

    function getDataAreaForm(areacode) {
        let reference = $('input#productreference_printjobs').val();
        let teccode = $('#teccode_' + areacode).val();
        let qcolors = $('#qcolors_' + areacode).val();
        let cliche = $('input:radio[name=cliche_' + areacode + ']:checked').val();

        return {
            areacode: areacode,
            reference: reference,
            teccode: teccode,
            qcolors: qcolors,
            cliche: cliche,
            action: ''
        };
    }

    function activeInputs(areacode, active = true) {
        $('#teccode_' + areacode).prop('disabled', active);
        $('#qcolors_' + areacode).prop('disabled', active);
        $('#width_' + areacode).prop('disabled', active);
        $('#heigth_' + areacode).prop('disabled', active);
        $('#cliche_' + areacode).prop('disabled', active);
        $('#clicherep_' + areacode).prop('disabled', active);
    }

    function selectTypeprint(areacode, dataarea) {
        console.log('selecciona area de marcaje');

        if (typeof rjmakitosync_front === 'undefined') {
            console.error('url peticion ajax no definida');
            return true;
        }

        dataarea.action = 'selectArea';

        $.ajax({
            type: 'POST',
            dataType: 'JSON',
            url: rjmakitosync_front,
            cache: false,
            data: dataarea,
            success: function (data) {
                console.log(data);
                let optiontypeprint = optionsTypeprint(data, dataarea.teccode);
                $('#teccode_' + areacode).html(optiontypeprint).fadeIn();
                clicheInput(areacode, data[0]);
                calculaPrecioPrint(data[0]);
            },
            error: function (err) {
                console.log(err);
            }
        });
    }

    function selectColors(areacode, dataarea) {
        console.log('selecciona tipo de marcaje');

        if (typeof rjmakitosync_front === 'undefined') {
            return true;
        }

        dataarea.action = 'selectColor';

        $.ajax({
            type: 'POST',
            dataType: 'JSON',
            url: rjmakitosync_front,
            cache: false,
            data: dataarea,
            success: function (data) {
                let optionscolors = optionsColors(data)
                $('#qcolors_' + areacode).html(optionscolors).fadeIn();
                clicheInput(areacode, data);
                calculaPrecioPrint(data);
            },
            error: function (err) {
                console.log(err);
            }
        });
    }

    $('#accordionPrintJobs .width').on('keyup', function () {
        validaSize(this);
    });

    $('#accordionPrintJobs .heigth').on('keyup', function () {
        validaSize(this);
    });

    var validaSize = (obj) => {
        if ($(obj).val() > parseFloat($(obj).attr('max'))) {
            $(obj).parent().addClass("has-error");
        } else if ($(obj).val() < parseFloat($(obj).attr('min'))) {
            $(obj).parent().addClass("has-error");
        } else {
            $(obj).parent().removeClass("has-error");
        }
    }

    function optionsTypeprint(params, teccode) {
        let options = '';
        params.forEach(element => {
            let optionselected = '';
            if(element.teccode == teccode) {
                optionselected = 'selected="selected"';
            }
            options += '<option value="' + element.teccode + '" title="' + element.name + '"' + optionselected +'>' + element.name + '</option>';
        });
        return options;
    }

    function optionsColors(params) {
        let maxcolour = params.maxcolour;
        let options = '';
        for (i = 1; i <= maxcolour; i++) {
            colorText = (i == 1) ? 'color' : 'colors';
            options += '<option value="' + i + '">' + i + ' - ' + colorText + '  </option>';
        }
        return options;
    }

    function clicheInput(areacode, data) {
        // console.log(data);
        let cliche = parseFloat(data.cliche).toFixed(2);
        let clicherep = parseFloat(data.clicherep).toFixed(2);;

        $("label[for=cliche_" + areacode + "]").html('Cliché = ' + cliche).fadeIn();
        $("label[for=clicherep_" + areacode + "]").html('Repetición Cliché = ' + clicherep).fadeIn();
    }

    function calculaPrecioPrint(data) {
        var cantidad = $('#quantity_wanted').val();
        var price = $('.current-price span[itemprop=price]').attr('content');
        var cantidadcolor = $('#qcolors_' + data['areacode']).val();
        console.log('Cantidad colors = ' + cantidadcolor);

        console.log(data);

        var typetarifa = '';
        var amountunder = 0;
        var precioprint = 0;
        var preciounidad = 0;

        for (let i = 1; i <= 7; i++) {
            amountunder = parseInt(data['amountunder' + i]);

            if (amountunder > 0 && cantidad <= amountunder) {
                typetarifa = i;
                console.log(amountunder);
                break;
            }
        }

        console.log('cantidad = ' + cantidad);
        console.log('amountunder = ' + amountunder);
        console.log('tarifa = ' + data['price' + typetarifa]);
        precioprint = cantidad * data['price' + typetarifa];
        console.log('cantidad * tarifa = ' + precioprint);
        if (precioprint < data['minjob']) {
            precioprint = data['minjob'];
            preciounidad = precioprint / cantidad;
            precioprint = precioprint * cantidadcolor;
        } else {
            let precioprintcoloradicional = cantidad * data['priceaditionalcol' + typetarifa] * cantidadcolor;
            precioprint += precioprintcoloradicional;
        }

        console.log('precio unidad = ' + precioprint / cantidad);
        console.log('precio colores add = ' + precioprint);
    }

});
