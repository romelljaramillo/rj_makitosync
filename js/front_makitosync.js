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
    $('body').on('change touchspin.on.startspin', '#accordionPrintJobs [name]', function (event) {
        event.preventDefault();
        console.log(this);
        let areacode = $(this).parents('.areacode').attr('data-areacode');
        if(activeInputs(areacode, false) && validaWidthSize(areacode) && validaHeigthSize(areacode)){
            prestashop.emit('updateProduct',event);
        }

        /* let name = this.name;
        let areacode = $(this).parents('.areacode').attr('data-areacode');
        let nameSelect = name.substr(0,name.indexOf('_'+areacode,0));

        switch (nameSelect) {
            case 'areacode':
                    getPrintArea(areacode);
                break;
            case 'teccode':
                    getColors(areacode, action = 'typeprint');
                break;
            case 'qcolors':
                    getColors(areacode);
                break;
            case 'cliche':
                    getColors(areacode);
                break;
        
            default:
                break;
        } */

    });

    function activeInputs(areacode, active = true) {
        $('#teccode_' + areacode).prop('disabled', active);
        $('#qcolors_' + areacode).prop('disabled', active);
        $('#areawidth_' + areacode).prop('disabled', active);
        $('#areahight_' + areacode).prop('disabled', active);
        $('#cliche_' + areacode).prop('disabled', active);
        $('#clicherep_' + areacode).prop('disabled', active);
        $('#price_' + areacode).prop('disabled', active);
        return true;
    }

    function getDataAreaForm(areacode) {
        let reference = $('input#product_reference').val();
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

    function getTypePrint(areacode, dataarea) {
        // console.log(rjmakitosync_front);

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
                // console.log(data);
                let optiontypeprint = optionsTypeprint(data, dataarea.teccode);
                $('#teccode_' + areacode).html(optiontypeprint).fadeIn();
                setCliche(areacode, data[0]);
                calculaPrecioPrint(data[0]);
            },
            error: function (err) {
                console.log(err);
            }
        });
    }

    function validaWidthSize(areacode) {
        if ($('#areawidth_' + areacode).val() > parseFloat($('#areawidth_' + areacode).attr('max'))
        || $('#areawidth_' + areacode).val() < parseFloat($('#areawidth_' + areacode).attr('min'))) {
            $('#areawidth_' + areacode).parent().addClass("has-error");
            return false;
        } else {
            $('#areawidth_' + areacode).parent().removeClass("has-error");
            return true;
        }
    }

    function validaHeigthSize(areacode) {
        if ($('#areahight_' + areacode).val() > parseFloat($('#areahight_' + areacode).attr('max'))
        || $('#areahight_' + areacode).val() < parseFloat($('#areahight_' + areacode).attr('min'))) {
            $('#areahight_' + areacode).parent().addClass("has-error");
            return false;
        } else {
            $('#areahight_' + areacode).parent().removeClass("has-error");
            return true;
        }
    }
    
    function getPrintArea(areacode) {
        let checked = $('input[name=areacode_' + areacode +']').is(':checked');
        if(checked) {
            let dataarea = getDataAreaForm(areacode);
            activeInputs(areacode, false);
            getTypePrint(areacode, dataarea);
        } else {
            activeInputs(areacode);
            changePrice(areacode);
        }
    }

    // function getColors(dataarea, action) {
    function getColors(areacode, action = 'colors') {
        // console.log('selecciona tipo de marcaje');

        let dataarea = getDataAreaForm(areacode);

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
                if(action === 'typeprint'){
                    let optionscolors = optionsColors(data)
                    $('#qcolors_' + dataarea.areacode).html(optionscolors).fadeIn();
                }
                setCliche(dataarea.areacode, data);
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

    function calculaPrecioPrint(data) {
        var cantidad = parseInt($('#quantity_wanted').val());
        const areacode = data['areacode'];
        var cantidadcolor = parseInt($('#qcolors_' + areacode).val());

        var typetarifa = '';
        var amountunder = 0;
        var precioprint = 0;
        var preciounidad = 0;

        for (let i = 1; i <= 7; i++) {
            amountunder = parseInt(data['amountunder' + i]);

            if (amountunder > 0 && cantidad <= amountunder) {
                typetarifa = i;
                // console.log(amountunder);
                break;
            }
        }

        precioprint = cantidad * data['price' + typetarifa];
        if (precioprint < data['minjob']) {
            precioprint = data['minjob'];
            preciounidad = precioprint / cantidad;
            precioprint = precioprint * cantidadcolor;
        } else {
            if(cantidadcolor > 1 ){
                let precioprintcoloradicional = cantidad * data['priceaditionalcol' + typetarifa] * cantidadcolor;
                precioprint += precioprintcoloradicional;
            }
        }

        let cliche = getCliche(areacode) * cantidadcolor;
        changePriceCliche(areacode, cantidadcolor);
        changePrice(areacode, precioprint + cliche );
    }

    function setCliche(areacode, data, cantidadcolor = 1) {
        // console.log(data);
        let cliche = parseFloat(data.cliche).toFixed(2);
        let clicherep = parseFloat(data.clicherep).toFixed(2);

        $("label[for=cliche_" + areacode + "] span").html(cliche).fadeIn();
        $("label[for=clicherep_" + areacode + "] span").html(clicherep).fadeIn();

    }
    
    function getCliche(areacode) {
        let cliche = parseFloat($('input[name=cliche_' + areacode +']:checked').attr('data-value'));
        return cliche;
    }

    function changePriceCliche(areacode, cantidadcolor) {
        let idCliche = $('input[name=cliche_' + areacode +']:checked').attr('id');
        let priceCliche = parseFloat($('input[name=cliche_' + areacode +']:checked').attr('data-value')).toFixed(2);

        $('label[for='+idCliche+'] span').html(priceCliche * cantidadcolor).fadeIn();
    }

    function changePrice(areacode, precioprint = 0) {
        $('#price-print_' + areacode).text(precioprint);
        $('#price_' + areacode).val(precioprint);
        changuePriceProduct();
    }

    function changuePriceProduct() {
        console.log('changuePriceProduct');
        let dataUpdatePrint;
        // prestashop.emit('updateProduct');
        let price = parseFloat($('.current-price span[itemprop=price]').attr('content'));
        let cantidad = parseInt($('#quantity_wanted').val());
        let pricePrintArea = getPricePrintArea();
        let newprice = (price * cantidad) + pricePrintArea;
        $('.current-price span[itemprop=price]').text(newprice.toFixed(2) + ' €');
    }

    function getPricePrintArea() {
        let sumaPricePrint= 0;
        $('#accordionPrintJobs .current-price-print input*').each(function() {
            sumaPricePrint += parseFloat(this.value);
        })
        return sumaPricePrint;
    }
});
