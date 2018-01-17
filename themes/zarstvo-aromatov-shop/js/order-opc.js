/*
* 2007-2015 PrestaShop
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

$(document).ready(function(){

    $('.address1').hide();
    $("#phone").mask('+7 (999) 999-99-99');
    $('#postcode').mask('999999');
    updateAddressDisplay();
    $('.order-note-delivery').hide();

    $(document).on('change', '#id_delivery', function() {
        updateAddressDisplay();
    });

    if (productNumber)
        splitInitialAddress();

    $('#street, #house, #housing, #apartment').on('change, keyup', function(e) {
        updateAddressField();
    });

    // if ($('#postcode').val() != '' && $('#postcode').val() != null && typeof(requestDeliveryCost) !== 'undefined') {
    //     // requestDeliveryCost();
    //     updateCarrierSelectionAndGift();
    // }


    function setupAddressFieldsAutocomplete() {
        // Setup autocomplete options for russian post delivery
        // if ($('#id_delivery').val() == '22,') {

            // Region field autocomplete
            $('#address2').autocomplete(
                'index.php?controller=order-helper',
                {
                    minChars: 3,
                    max: 10,
                    width: 500,
                    selectFirst: false,
                    scroll: false,
                    dataType: "json",
                    formatItem: function (data, i, max, value, term) {
                        return value;
                    },
                    parse: function (data) {
                        var mytab = new Array();
                        for (var i = 0; i < data.length; i++)
                            mytab[mytab.length] = {data: data[i], value: data[i].text};
                        return mytab;
                    },
                    extraParams: {
                        autocomplete: 1,
                        id_lang: id_lang,
                        field: 'region'
                    }
                }
            )
            .result(function (event, data, formatted) {
                $('#address2').val(data.value);
                $('#city').setOptions({
                    extraParams: {
                        autocomplete: 1,
                        id_lang: id_lang,
                        field: 'city',
                        region: data.value
                    }
                });
            });

            // City field autocomplete
            $('#city').autocomplete(
                'index.php?controller=order-helper',
                {
                    minChars: 3,
                    max: 10,
                    width: 500,
                    selectFirst: false,
                    scroll: false,
                    dataType: "json",
                    formatItem: function (data, i, max, value, term) {
                        return value;
                    },
                    parse: function (data) {
                        var mytab = new Array();
                        for (var i = 0; i < data.length; i++)
                            mytab[mytab.length] = {data: data[i], value: data[i].text};
                        return mytab;
                    },
                    extraParams: {
                        autocomplete: 1,
                        id_lang: id_lang,
                        field: 'city',
                        region: ''
                    }
                }
            )
            .result(function (event, data, formatted) {
                $('#city').val(data.value);
                $('#postcode').setOptions({
                    extraParams: {
                        autocomplete: 1,
                        id_lang: id_lang,
                        field: 'postcode',
                        city: data.value
                    }
                });

                $('#postcode').val('');
            });

            // Postcode field autocomplete
            $('#postcode').autocomplete(
                'index.php?controller=order-helper',
                {
                    minChars: 0,
                    max: 10,
                    width: 500,
                    selectFirst: false,
                    scroll: false,
                    dataType: "json",
                    formatItem: function (data, i, max, value, term) {
                        return value;
                    },
                    parse: function (data) {
                        var mytab = new Array();
                        for (var i = 0; i < data.length; i++)
                            mytab[mytab.length] = {data: data[i], value: data[i].text};
                        return mytab;
                    },
                    extraParams: {
                        autocomplete: 1,
                        id_lang: id_lang,
                        field: 'postcode',
                        city: ''
                    }
                }
            )
            .result(function (event, data, formatted) {
                $('#postcode').val(data.value);
                $('#address2').val(data.region);
                $('#city').val(data.city);
                $('#city').setOptions({
                    extraParams: {
                        autocomplete: 1,
                        id_lang: id_lang,
                        field: 'city',
                        region: data.region
                    }
                });

                if ($('#opc_id_customer').val() !== '0') {
                    if (!saveAddress('delivery')) {
                        return false;
                    }
                    updateCarrierSelectionAndGift();
                } else {
                    if (typeof(requestDeliveryCost) !== 'undefined')
                        requestDeliveryCost();
                }
            });
        // }
    }

    setupAddressFieldsAutocomplete();

    $("#placeOrder").click(function() {
        $('html, body').animate({
            scrollTop: $("#new_account_form").offset().top
        }, 2000);
    });

    $(document).on('click', '.payment-label', function() {
        if ($(this).parent().hasClass('disabled'))
            return;
        $(this).parent().find('input[name=id_payment]').prop('checked', true);
        $(this).parent().find('input[name=id_payment]').click();
        $('#id_payment .payment_method .radio span').removeClass('checked');
        $(this).parent().find('.radio span').addClass('checked');
    });

    // VALIDATION / CREATION AJAX
    $(document).on('click', '#submitAccount', function(e){
        e.preventDefault();

        // Check if products quantities are lower than available.
        // Interrupt order placing if quantities were changed and show notification.
        if (updateProductsQuantities()) {
            return;
        }

        $('#submitAccount').prop('disabled', 'disabled');

        var callingFile = '';
        var params = '';

        if (parseInt($('#opc_id_customer').val()) === 0) {
            callingFile = authenticationUrl;
            params = 'submitAccount=true&';
        } else {
            callingFile = orderOpcUrl;
            params = 'method=editCustomer&';
        }

        // Update address if pickup method was selected with point of delivery address
        if ($('#id_delivery').find(':selected').data('is_pickup')) {
            $('#address2').val(point_of_delivery_address.region);
            $('#city').val(point_of_delivery_address.city);
            $('#postcode').val(point_of_delivery_address.postcode);
            $('#street').val(point_of_delivery_address.street);
            $('#house').val(point_of_delivery_address.house);
            $('#housing').val(point_of_delivery_address.housing);
            $('#apartment').val(point_of_delivery_address.apartment);
            $('#alias').val(point_of_delivery_address.name);
            updateAddressField();
        }

        var result = false;
        $('.form-group.required input').each(function(i, el) {
            if ($(el).val() === '') {
                $(el).parent('.form-group.required').addClass('form-error');
                result = true;
            }
            if ($(el).data('validate') == 'isPhoneNumber' && !validate_isPhoneNumber($(el).val())) {
                $(el).parent('.form-group.required').addClass('form-error');
                result = true;
            }
        });

        if (result) {
            $('#submitAccount').prop('disabled', false);
            $('#opc_account_errors').slideUp('fast', function(){
                $(this).html(txtRequiredFieldsAreNotFilled).slideDown('slow', function(){
                    $.scrollTo('#opc_account_errors', 800);
                });
            });
            return;
        }

        var p = $('input[name=id_payment]:checked');
        if (p.prop('disabled') || typeof p.val() === undefined || !p.val()) {
            $('#submitAccount').prop('disabled', false);
            $('#opc_account_errors').slideUp('fast', function(){
                $(this).html(txtPaymentMethodNotChoosed).slideDown('slow', function(){
                    $.scrollTo('#opc_account_errors', 800);
                });
            });
            return;
        }

        if (p.attr('id') == 'payment_method_prepayment' && window.total_products >= 3000 &&
            ($('#select_bonus input[name=bonus]:checked').length === 0 || $('#select_bonus input[name=bonus]:checked').val() === '')
        ) {
            $('#submitAccount').prop('disabled', false);
            $('#opc_account_errors').slideUp('fast', function(){
                $(this).html(txtBonusNotChoosed).slideDown('slow', function(){
                    $.scrollTo('#opc_account_errors', 800);
                });
            });
            return;
        }

        $('#opc_account_form input, #opc_account_form input[type=hidden]').each(function() {
            if ($(this).is('input[type=checkbox]')) {
                if ($(this).is(':checked')) {
                    params += encodeURIComponent($(this).attr('name'))+'=1&';
                }
            } else if ($(this).is('input[type=radio]')) {
                if ($(this).is(':checked')) {
                    params += encodeURIComponent($(this).attr('name'))+'='+encodeURIComponent($(this).val())+'&';
                }
            } else {
                params += encodeURIComponent($(this).attr('name'))+'='+encodeURIComponent($(this).val())+'&';
            }
        });

        $('#opc_account_form select:visible').each(function() {
            params += encodeURIComponent($(this).attr('name'))+'='+encodeURIComponent($(this).val())+'&';
        });
        // Clean the last &
        params = params.substr(0, params.length-1);

        $.ajax({
            type: 'POST',
            headers: { "cache-control": "no-cache" },
            url: callingFile + '?rand=' + new Date().getTime(),
            async: true,
            cache: false,
            dataType : "json",
            data: 'ajax=true&'+params+'&token=' + static_token,
            success: function(jsonData)
            {
                $('#submitAccount').prop('disabled', false);
                if (jsonData.hasError)
                {
                    var tmp = '';
                    var i = 0;
                    for(var error in jsonData.errors)
                        //IE6 bug fix
                        if(error !== 'indexOf')
                        {
                            i = i+1;
                            tmp += '<li>'+jsonData.errors[error]+'</li>';
                            if (error == 'firstname' || error == 'lastname')
                                $('#customer_'+error).parent('.form-group').addClass('form-error');
                            else if (error == 'address1')
                            {
                                if ($('#street').val() === '')
                                    $('#street').parent('.form-group').addClass('form-error');
                                if ($('#house').val() === '')
                                    $('#house').parent('.form-group').addClass('form-error');
                            }
                            else
                                $('#'+error).parent('.form-group').addClass('form-error');
                        }
                    tmp += '</ol>';
                    var errors = '<b>'+txtThereisNextErrors+':</b><ol>'+tmp;
                    $('#opc_account_errors').slideUp('fast', function(){
                        $(this).html(errors).slideDown('slow', function(){
                            $.scrollTo('#opc_account_errors', 800);
                        });
                    });
                }
                else
                {
                    $('#opc_account_errors').slideUp('slow', function(){
                        $(this).html('');
                    });
                }

                isGuest = parseInt($('#is_new_customer').val()) == 1 ? 0 : 1;
                // update addresses id
                if (jsonData.id_address_delivery !== undefined && jsonData.id_address_delivery > 0)
                    $('#opc_id_address_delivery').val(jsonData.id_address_delivery);
                if (jsonData.id_address_invoice !== undefined && jsonData.id_address_invoice > 0)
                    $('#opc_id_address_invoice').val(jsonData.id_address_invoice);

                if (jsonData.id_customer !== undefined && jsonData.id_customer !== 0 && jsonData.isSaved)
                {
                    // update token
                    static_token = jsonData.token;

                    // It's not a new customer
                    if ($('#opc_id_customer').val() !== '0')
                        if (!saveAddress('delivery'))
                            return false;

                    // update id_customer
                    $('#opc_id_customer').val(jsonData.id_customer);

                    // checkout order on successful information update
                    isLogged = 1;
                    if (updateAddressSelection()) {
                        if (updateCartBonus()) {
                            var link = $('input[name=id_payment]:checked').val();
                            if (typeof yaCounter31092596 !== 'undefined')
                                yaCounter31092596.reachGoal('ORDER_PLACED', yaParams);
                            document.location.href = link;
                        }
                    }
                }
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                $('#submitAccount').prop('disabled', false);
                if (textStatus !== 'abort')
                {
                    error = "txtErrorsHappened";
                    if (!!$.prototype.fancybox)
                        $.fancybox.open([
                            {
                                type: 'inline',
                                autoScale: true,
                                minHeight: 30,
                                content: '<p class="fancybox-error">' + error + '</p>'
                            }
                        ], {
                            padding: 0
                        });
                    else
                        alert(error);
                }
            }
        });
    });

    bindInputs();

    updateProductsQuantities();
});

function updateProductsQuantities() {
    var result = false;

    $.ajax({
        type: 'POST',
        headers: {"cache-control": "no-cache"},
        url: orderOpcUrl + '?rand=' + new Date().getTime(),
        async: false,
        cache: false,
        dataType: "json",
        data: 'allow_refresh=1&ajax=true&method=checkQuantityAvailable&token=' + static_token,
        success: function (jsonData) {
            if (jsonData.status) {
                result = true;

                $('#shopping_cart_content').replaceWith(jsonData.template);

                if (!!$.prototype.fancybox && typeof jsonData.notification != 'undefined' && jsonData.additional_text != 'undefined') {
                    $.fancybox.open([
                        {
                            type: 'inline',
                            autoScale: true,
                            minHeight: 30,
                            content: '<p class="fancybox-error">' + jsonData.notification + '<br>' + jsonData.additional_text + '</p>'
                        }
                    ], {
                        padding: 0
                    });
                }
            }
        }
    });

    return result;
}

function updateCarrierList(json)
{
    var html = json.carrier_block;
    $('#carrier_area').replaceWith(html);
    bindInputs();
}

function updatePaymentMethods(json, checked_payment)
{
    $('#id_payment').html(json.HOOK_PAYMENT);
    if (checked_payment)
        $('input#'+checked_payment).prop('checked', true);
}

function updatePaymentMethodsDisplay()
{
    var checked = '';
    if ($('#cgv:checked').length !== 0)
        checked = 1;
    else
    checked = 0;
    $.ajax({
        type: 'POST',
        headers: { "cache-control": "no-cache" },
        url: orderOpcUrl + '?rand=' + new Date().getTime(),
        async: true,
        cache: false,
        dataType : "json",
        data: 'ajax=true&method=updateTOSStatusAndGetPayments&checked=' + checked + '&token=' + static_token,
        success: function(json)
        {
            updatePaymentMethods(json,false);
            if (typeof bindUniform !=='undefined')
                bindUniform();
        }
    });
}

function updateAddressSelection()
{
    var idAddress_delivery = ($('#opc_id_address_delivery').length == 1 ? $('#opc_id_address_delivery').val() : $('#id_address_delivery').val());
    var idAddress_invoice = ($('#opc_id_address_invoice').length == 1 ? $('#opc_id_address_invoice').val() : ($('#addressesAreEquals:checked').length == 1 ? idAddress_delivery : ($('#id_address_invoice').length == 1 ? $('#id_address_invoice').val() : idAddress_delivery)));
    var result = false;

    $.ajax({
        type: 'POST',
        headers: { "cache-control": "no-cache" },
        url: orderOpcUrl + '?rand=' + new Date().getTime(),
        async: false,
        cache: false,
        dataType : "json",
        data: 'allow_refresh=1&ajax=true&method=updateAddressesSelected&id_address_delivery=' + idAddress_delivery + '&id_address_invoice=' + idAddress_invoice + '&token=' + static_token,
        success: function(jsonData)
        {
            if (jsonData.hasError)
            {
                var errors = '';
                for(var error in jsonData.errors)
                    //IE6 bug fix
                    if(error !== 'indexOf')
                        errors += $('<div />').html(jsonData.errors[error]).text() + "\n";
                if (!!$.prototype.fancybox)
                    $.fancybox.open([
                        {
                            type: 'inline',
                            autoScale: true,
                            minHeight: 30,
                            content: '<p class="fancybox-error">' + errors + '</p>'
                        }
                    ], {
                        padding: 0
                    });
                else {
                    alert(errors);
                }
            }
            else
            {
                if (jsonData.refresh) {
                    location.reload();
                }

                // Update all product keys with the new address id
                $('#cart_summary .address_'+deliveryAddress).each(function() {
                    $(this)
                        .removeClass('address_'+deliveryAddress)
                        .addClass('address_'+idAddress_delivery);
                    $(this).attr('id', $(this).attr('id').replace(/_\d+$/, '_'+idAddress_delivery));
                    if ($(this).find('.cart_unit span').length > 0 && $(this).find('.cart_unit span').attr('id').length > 0)
                        $(this).find('.cart_unit span').attr('id', $(this).find('.cart_unit span').attr('id').replace(/_\d+$/, '_'+idAddress_delivery));

                    if ($(this).find('.cart_total span').length > 0 && $(this).find('.cart_total span').attr('id').length > 0)
                        $(this).find('.cart_total span').attr('id', $(this).find('.cart_total span').attr('id').replace(/_\d+$/, '_'+idAddress_delivery));

                    if ($(this).find('.cart_quantity_input').length > 0 && $(this).find('.cart_quantity_input').attr('name').length > 0)
                    {
                        var name = $(this).find('.cart_quantity_input').attr('name')+'_hidden';
                        $(this).find('.cart_quantity_input').attr('name', $(this).find('.cart_quantity_input').attr('name').replace(/_\d+$/, '_'+idAddress_delivery));
                        if ($(this).find('[name="' + name + '"]').length > 0)
                            $(this).find('[name="' + name +' "]').attr('name', name.replace(/_\d+_hidden$/, '_'+idAddress_delivery+'_hidden'));
                    }

                    if ($(this).find('.cart_quantity_delete').length > 0 && $(this).find('.cart_quantity_delete').attr('id').length > 0)
                    {
                        $(this).find('.cart_quantity_delete')
                            .attr('id', $(this).find('.cart_quantity_delete').attr('id').replace(/_\d+$/, '_'+idAddress_delivery))
                            .attr('href', $(this).find('.cart_quantity_delete').attr('href').replace(/id_address_delivery=\d+&/, 'id_address_delivery='+idAddress_delivery+'&'));
                    }

                    if ($(this).find('.cart_quantity_down').length > 0 && $(this).find('.cart_quantity_down').attr('id').length > 0)
                    {
                        $(this).find('.cart_quantity_down')
                            .attr('id', $(this).find('.cart_quantity_down').attr('id').replace(/_\d+$/, '_'+idAddress_delivery))
                            .attr('href', $(this).find('.cart_quantity_down').attr('href').replace(/id_address_delivery=\d+&/, 'id_address_delivery='+idAddress_delivery+'&'));
                    }

                    if ($(this).find('.cart_quantity_up').length > 0 && $(this).find('.cart_quantity_up').attr('id').length > 0)
                    {
                        $(this).find('.cart_quantity_up')
                            .attr('id', $(this).find('.cart_quantity_up').attr('id').replace(/_\d+$/, '_'+idAddress_delivery))
                            .attr('href', $(this).find('.cart_quantity_up').attr('href').replace(/id_address_delivery=\d+&/, 'id_address_delivery='+idAddress_delivery+'&'));
                    }
                });

                // Update global var deliveryAddress
                deliveryAddress = idAddress_delivery;
                if (window.ajaxCart !== undefined)
                {
                    $('.cart_block_list dd, .cart_block_list dt').each(function(){
                        if (typeof($(this).attr('id')) != 'undefined')
                            $(this).attr('id', $(this).attr('id').replace(/_\d+$/, '_' + idAddress_delivery));
                    });
                }
                // updateCarrierList(jsonData.carrier_data);
                // updatePaymentMethods(jsonData);
                updateCartSummary(jsonData.summary);
                // updateHookShoppingCart(jsonData.HOOK_SHOPPING_CART);
                // updateHookShoppingCartExtra(jsonData.HOOK_SHOPPING_CART_EXTRA);
                if ($('#gift-price').length == 1)
                    $('#gift-price').html(jsonData.gift_price);

                result = true;
            }
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {
            if (textStatus !== 'abort')
            {
                error = "TECHNICAL ERROR: unable to save adresses \n\nDetails:\nError thrown: " + XMLHttpRequest + "\n" + 'Text status: ' + textStatus;
                if (!!$.prototype.fancybox)
                    $.fancybox.open([
                        {
                            type: 'inline',
                            autoScale: true,
                            minHeight: 30,
                            content: '<p class="fancybox-error">' + error + '</p>'
                        }
                    ], {
                        padding: 0
                    });
                else
                    alert(error);
            }
        }
    });
    return result;
}

function updateCartBonus()
{
    var cart_rule_id = 0;
    if ($('input[name=id_payment]:checked').attr('id') === 'payment_method_prepayment')
    {
        if (window.total_products >= 3000)
        {
            if ($('#select_bonus input[name=bonus]:checked').length === 0)
                return false;
            else
                cart_rule_id = $('#select_bonus input[name=bonus]:checked').val();
        }
        else
        {
            cart_rule_id = $('#select_bonus input#discount_id').val();
        }
    }
    else
    {
        cart_rule_id = $('#select_bonus input#discount_id').val();
    }

    var result = false;

    $.ajax({
        type: 'POST',
        headers: { "cache-control": "no-cache" },
        url: orderOpcUrl + '?rand=' + new Date().getTime(),
        async: false,
        cache: false,
        dataType : "json",
        data: 'ajax=true&method=updateCartDiscounts&cart_rule_id=' + cart_rule_id + '&token=' + static_token,
        success: function(jsonData)
        {
            result = true;
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {
            if (textStatus !== 'abort')
            {
                error = "TECHNICAL ERROR: unable to save adresses \n\nDetails:\nError thrown: " + XMLHttpRequest + "\n" + 'Text status: ' + textStatus;
                if (!!$.prototype.fancybox)
                    $.fancybox.open([
                        {
                            type: 'inline',
                            autoScale: true,
                            minHeight: 30,
                            content: '<p class="fancybox-error">' + error + '</p>'
                        }
                    ], {
                        padding: 0
                    });
                else
                    alert(error);
            }
        }
    });
    return result;
}

function getCarrierListAndUpdate()
{
    $.ajax({
        type: 'POST',
        headers: { "cache-control": "no-cache" },
        url: orderOpcUrl + '?rand=' + new Date().getTime(),
        async: true,
        cache: false,
        dataType : "json",
        data: 'ajax=true&method=getCarrierList&token=' + static_token,
        success: function(jsonData)
        {
            if (jsonData.hasError)
            {
                var errors = '';
                for(var error in jsonData.errors)
                    //IE6 bug fix
                    if(error !== 'indexOf')
                        errors += $('<div />').html(jsonData.errors[error]).text() + "\n";
                if (!!$.prototype.fancybox)
                {
                    $.fancybox.open([
                        {
                            type: 'inline',
                            autoScale: true,
                            minHeight: 30,
                            content: '<p class="fancybox-error">' + errors + '</p>'
                        }
                    ], {
                        padding: 0
                    });
                }
                else
                {
                    if (!!$.prototype.fancybox)
                        $.fancybox.open([
                            {
                                type: 'inline',
                                autoScale: true,
                                minHeight: 30,
                                content: '<p class="fancybox-error">' + errors + '</p>'
                            }
                        ], {
                            padding: 0
                        });
                    else
                        alert(errors);
                    return false;
                }
                return false;
            }
            else {
                // updateCarrierList(jsonData);
            }
        }
    });
}

function updateCarrierSelectionAndGift()
{
    var recyclablePackage = 0;
    var gift = 0;
    var giftMessage = '';
    var paymentOption = false;

    var delivery_option = $('select[name=id_delivery]').find(':selected');
    var delivery_option_params = '&' + $(delivery_option).attr('name') + '=' + $(delivery_option).val() + '&';

    if (delivery_option_params == '&')
        delivery_option_params = '&delivery_option=&';

    if ($('input#recyclable:checked').length)
        recyclablePackage = 1;
    if ($('input#gift:checked').length)
    {
        gift = 1;
        giftMessage = encodeURIComponent($('#gift_message').val());
    }

    if ($('#id_payment input[name=id_payment]:checked').length && $('#id_payment input[name=id_payment]:checked').val())
        paymentOption = $('#id_payment input[name=id_payment]:checked').attr('id');

    $.ajax({
        type: 'POST',
        headers: { "cache-control": "no-cache" },
        url: orderOpcUrl + '?rand=' + new Date().getTime(),
        async: true,
        cache: false,
        dataType : "json",
        data: 'ajax=true&method=updateCarrierAndGetPayments' + delivery_option_params + 'recyclable=' + recyclablePackage + '&gift=' + gift + '&gift_message=' + giftMessage + '&token=' + static_token ,
        success: function(jsonData)
        {
            if (jsonData.hasError)
            {
                var errors = '';
                for(var error in jsonData.errors)
                    //IE6 bug fix
                    if(error !== 'indexOf')
                        errors += $('<div />').html(jsonData.errors[error]).text() + "\n";
                if (!!$.prototype.fancybox)
                    $.fancybox.open([
                        {
                            type: 'inline',
                            autoScale: true,
                            minHeight: 30,
                            content: '<p class="fancybox-error">' + errors + '</p>'
                        }
                    ], {
                        padding: 0
                    });
                else
                    alert(errors);
                return false;
            }
            else
            {
                updateCartSummary(jsonData.summary);
                updatePaymentMethods(jsonData, paymentOption);
                // updateHookShoppingCart(jsonData.summary.HOOK_SHOPPING_CART);
                // updateHookShoppingCartExtra(jsonData.summary.HOOK_SHOPPING_CART_EXTRA);
                // updateCarrierList(jsonData.carrier_data);
                refreshDeliveryOptions();
                if (typeof bindUniform !=='undefined')
                    bindUniform();

                if (typeof requestDeliveryCost !=='undefined')
                    requestDeliveryCost();
            }
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {
            if (textStatus !== 'abort')
                alert("TECHNICAL ERROR: unable to save carrier \n\nDetails:\nError thrown: " + XMLHttpRequest + "\n" + 'Text status: ' + textStatus);
            return false;
        }
    });
}

function confirmFreeOrder()
{
    $('#confirmOrder').prop('disabled', 'disabled');
    $.ajax({
        type: 'POST',
        headers: { "cache-control": "no-cache" },
        url: orderOpcUrl + '?rand=' + new Date().getTime(),
        async: true,
        cache: false,
        dataType : "html",
        data: 'ajax=true&method=makeFreeOrder&token=' + static_token ,
        success: function(html)
        {
            $('#confirmOrder').prop('disabled', false);
            var array_split = html.split(':');
            if (array_split[0] == 'freeorder')
            {
                if (isGuest)
                    document.location.href = guestTrackingUrl+'?id_order='+encodeURIComponent(array_split[1])+'&email='+encodeURIComponent(array_split[2]);
                else
                    document.location.href = historyUrl;
            }
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {
            if (textStatus !== 'abort')
            {
                error = "TECHNICAL ERROR: unable to confirm the order \n\nDetails:\nError thrown: " + XMLHttpRequest + "\n" + 'Text status: ' + textStatus;
                if (!!$.prototype.fancybox)
                    $.fancybox.open([
                        {
                            type: 'inline',
                            autoScale: true,
                            minHeight: 30,
                            content: '<p class="fancybox-error">' + error + '</p>'
                        }
                    ], {
                        padding: 0
                    });
                else
                    alert(error);
            }
        }
    });
}

function saveAddress(type)
{
    if (type !== 'delivery' && type !== 'invoice')
        return false;

    var params = 'firstname=' + encodeURIComponent($('#customer_firstname' + (type == 'invoice' ? '_invoice' : '')).val()) + '&lastname=' + encodeURIComponent($('#customer_lastname'+(type == 'invoice' ? '_invoice' : '')).val()) + '&';
    if ($('#company' + (type == 'invoice' ? '_invoice' : '')).length)
        params += 'company=' + encodeURIComponent($('#company' + (type == 'invoice' ? '_invoice' : '')).val()) + '&';
    params += 'address1=' + encodeURIComponent($('#address1' + (type == 'invoice' ? '_invoice' : '')).val()) + '&';
    params += 'address2=' + encodeURIComponent($('#address2' + (type == 'invoice' ? '_invoice' : '')).val()) + '&';
    params += 'postcode=' + encodeURIComponent($('#postcode' + (type == 'invoice' ? '_invoice' : '')).val()) + '&';
    params += 'city=' + encodeURIComponent($('#city' + (type == 'invoice' ? '_invoice' : '')).val()) + '&';
    params += 'id_country=' + parseInt($('#id_country' + (type == 'invoice' ? '_invoice' : '')).val()) + '&';
    if ($('#id_state'+(type == 'invoice' ? '_invoice' : '')).length)
        params += 'id_state='+encodeURIComponent($('#id_state'+(type == 'invoice' ? '_invoice' : '')).val()) + '&';
    params += 'phone=' + encodeURIComponent($('#phone' + (type == 'invoice' ? '_invoice' : '')).val()) + '&';
    params += 'alias=' + encodeURIComponent($('#alias' + (type == 'invoice' ? '_invoice' : '')).val()) + '&';
    if (type == 'delivery' && $('#opc_id_address_delivery').val() != undefined && parseInt($('#opc_id_address_delivery').val()) > 0)
        params += 'opc_id_address_delivery=' + parseInt($('#opc_id_address_delivery').val()) + '&';
    if (type == 'invoice' && $('#opc_id_address_invoice').val() != undefined && parseInt($('#opc_id_address_invoice').val()) > 0)
        params += 'opc_id_address_invoice=' + parseInt($('#opc_id_address_invoice').val()) + '&';
    // Clean the last &
    params = params.substr(0, params.length-1);

    var result = false;

    $.ajax({
        type: 'POST',
        headers: { "cache-control": "no-cache" },
        url: addressUrl + '?rand=' + new Date().getTime(),
        async: false,
        cache: false,
        dataType : "json",
        data: 'ajax=true&submitAddress=true&type='+type+'&'+params+'&token=' + static_token,
        success: function(jsonData)
        {
            if (jsonData.hasError)
            {
                var tmp = '';
                var i = 0;
                for(var error in jsonData.errors)
                    //IE6 bug fix
                    if(error !== 'indexOf')
                    {
                        i = i+1;
                        tmp += '<li>'+jsonData.errors[error]+'</li>';
                        if (error == 'firstname' || error == 'lastname')
                            $('#customer_'+error).parent('.form-group').addClass('form-error');
                        else if (error == 'address1')
                        {
                            if ($('#street').val() === '')
                                $('#street').parent('.form-group').addClass('form-error');
                            if ($('#house').val() === '')
                                $('#house').parent('.form-group').addClass('form-error');
                        }
                        else
                            $('#'+error).parent('.form-group').addClass('form-error');
                    }
                tmp += '</ol>';
                var errors = '<b>'+txtThereisNextErrors+':</b><ol>'+tmp;
                $('#opc_account_errors').slideUp('fast', function(){
                    $(this).html(errors).slideDown('slow', function(){
                        $.scrollTo('#opc_account_errors', 800);
                    });
                });
                result = false;
            }
            else
            {
                // update addresses id
                $('input#opc_id_address_delivery').val(jsonData.id_address_delivery);
                $('input#opc_id_address_invoice').val(jsonData.id_address_invoice);
                result = true;
            }
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {
            if (textStatus !== 'abort')
            {
                error = "TECHNICAL ERROR: unable to save adresses \n\nDetails:\nError thrown: " + XMLHttpRequest + "\n" + 'Text status: ' + textStatus;
                if (!!$.prototype.fancybox)
                    $.fancybox.open([
                        {
                            type: 'inline',
                            autoScale: true,
                            minHeight: 30,
                            content: '<p class="fancybox-error">' + error + '</p>'
                        }
                    ], {
                        padding: 0
                    });
                else
                    alert(error);
            }
        }
        });

    return result;
}

function updateNewAccountToAddressBlock()
{
    $.ajax({
        type: 'POST',
        headers: { "cache-control": "no-cache" },
        url: orderOpcUrl + '?rand=' + new Date().getTime(),
        async: true,
        cache: false,
        dataType : "json",
        data: 'ajax=true&method=getAddressBlockAndCarriersAndPayments&token=' + static_token ,
        success: function(json)
        {
            if (json.hasError)
            {
                var errors = '';
                for(var error in json.errors)
                    //IE6 bug fix
                    if(error !== 'indexOf')
                        errors += $('<div />').html(json.errors[error]).text() + "\n";
                alert(errors);
            }
            else
            {
                isLogged = 1;
                if (json.no_address == 1)
                    document.location.href = addressUrl;

                if (typeof json.formatedAddressFieldsValuesList !== 'undefined' && json.formatedAddressFieldsValuesList )
                    formatedAddressFieldsValuesList = json.formatedAddressFieldsValuesList;
                if (typeof json.order_opc_adress !== 'undefined' && json.order_opc_adress)
                    $('#opc_new_account').html(json.order_opc_adress);
                // update block user info
                if (json.block_user_info !== '' && $('#header_user').length == 1)
                {
                    var elt = $(json.block_user_info).find('#header_user_info').html();
                    $('#header_user_info').fadeOut('nortmal', function() {
                        $(this).html(elt).fadeIn();
                    });
                }
                $(this).fadeIn('fast', function() {
                    //After login, the products are automatically associated to an address
                    $.each(json.summary.products, function() {
                        updateAddressId(this.id_product, this.id_product_attribute, '0', this.id_address_delivery);
                    });
                    updateAddressesDisplay(true);
                    // updateCarrierList(json.carrier_data);
                    updateCarrierSelectionAndGift();
                    // updatePaymentMethods(json);
                    // if ($('#gift-price').length == 1)
                    //     $('#gift-price').html(json.gift_price);
                });
            }
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {
            if (textStatus !== 'abort')
                alert("TECHNICAL ERROR: unable to send login informations \n\nDetails:\nError thrown: " + XMLHttpRequest + "\n" + 'Text status: ' + textStatus);
        }
    });
}

function bindInputs()
{
    // Order message update
    $('#message').blur(function() {
        $.ajax({
            type: 'POST',
            headers: { "cache-control": "no-cache" },
            url: orderOpcUrl + '?rand=' + new Date().getTime(),
            async: false,
            cache: false,
            dataType : "json",
            data: 'ajax=true&method=updateMessage&message=' + encodeURIComponent($('#message').val()) + '&token=' + static_token ,
            success: function(jsonData)
            {
                if (jsonData.hasError)
                {
                    var errors = '';
                    for(var error in jsonData.errors)
                        //IE6 bug fix
                        if(error !== 'indexOf')
                            errors += $('<div />').html(jsonData.errors[error]).text() + "\n";
                    alert(errors);
                }
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                if (textStatus !== 'abort')
                    alert("TECHNICAL ERROR: unable to save message \n\nDetails:\nError thrown: " + XMLHttpRequest + "\n" + 'Text status: ' + textStatus);
            }
        });
        if (typeof bindUniform !=='undefined')
            bindUniform();
    });

    // Recyclable checkbox
    // $('#recyclable').on('click', function(e){
    //     updateCarrierSelectionAndGift();
    // });

    // Gift checkbox update
    // $('#gift').off('click').on('click', function(e){
    //     if ($('#gift').is(':checked'))
    //         $('#gift_div').show();
    //     else
    //         $('#gift_div').hide();
    //     updateCarrierSelectionAndGift();
    // });

    // if ($('#gift').is(':checked'))
    //     $('#gift_div').show();
    // else
    //     $('#gift_div').hide();

    // Gift message update
    // $('#gift_message').on('change', function() {
    //     updateCarrierSelectionAndGift();
    // });

    // Term Of Service (TOS)
    $('#cgv').on('click', function(e){
        updatePaymentMethodsDisplay();
        if ($('#cgv:checked').length !== 0)
            $('#submitAccount').show();
        else
            $('#submitAccount').hide();
    });
}

function multishippingMode(it)
{
    if ($(it).prop('checked'))
    {
        $('#address_delivery, .address_delivery').hide();
        $('#address_delivery, .address_delivery').parent().hide();
        $('#address_invoice').removeClass('alternate_item').addClass('item');
        $('#multishipping_mode_box').addClass('on');
        $('.addressesAreEquals').hide();
        $('#address_invoice_form').show();

        $(document).on('click', '#link_multishipping_form', function(e){e.preventDefault();});
        $('.address_add a').attr('href', addressMultishippingUrl);

        $(document).on('click', '#link_multishipping_form', function(e){
            if(!!$.prototype.fancybox)
                $.fancybox({
                    'openEffect': 'elastic',
                    'closeEffect': 'elastic',
                    'type': 'ajax',
                    'href':     this.href,
                    'beforeClose': function(){
                        // Reload the cart
                        $.ajax({
                            type: 'POST',
                            headers: { "cache-control": "no-cache" },
                            url: orderOpcUrl + '?rand=' + new Date().getTime(),
                            data: 'ajax=true&method=cartReload',
                            dataType : 'html',
                            cache: false,
                            success: function(data) {
                                $('#cart_summary').replaceWith($(data).find('#cart_summary'));
                                $('.cart_quantity_input').typeWatch({ highlight: true, wait: 600, captureLength: 0, callback: function(val) { updateQty(val, true, this.el); } });
                            }
                        });
                        updateCarrierSelectionAndGift();
                    },
                    'beforeLoad': function(){
                        // Removing all ids on the cart to avoid conflic with the new one on the fancybox
                        // This action could "break" the cart design, if css rules use ids of the cart
                        $.each($('#cart_summary *'), function(it, el) {
                            $(el).attr('id', '');
                        });
                    },
                    'afterLoad': function(){
                        $('.fancybox-inner .cart_quantity_input').typeWatch({ highlight: true, wait: 600, captureLength: 0, callback: function(val) { updateQty(val, false, this.el);} });
                        cleanSelectAddressDelivery();
                        $('.fancybox-outer').append($('<div class="multishipping_close_container"><a id="multishipping-close" class="btn btn-default button button-small" href="#"><span>'+CloseTxt+'</span></a></div>'));
                        $(document).on('click', '#multishipping-close', function(e){
                            var newTotalQty = 0;
                            $('.fancybox-inner .cart_quantity_input').each(function(){
                                newTotalQty += parseInt($(this).val());
                            });
                            if (newTotalQty !== totalQty) {
                                if(!confirm(QtyChanged)) {
                                    return false;
                                }
                            }
                            $.fancybox.close();
                            return false;
                        });
                        totalQty = 0;
                        $('.fancybox-inner .cart_quantity_input').each(function(){
                            totalQty += parseInt($(this).val());
                        });
                    }
                });
        });
    }
    else
    {
        $('#address_delivery, .address_delivery').show();
        $('#address_invoice').removeClass('item').addClass('alternate_item');
        $('#multishipping_mode_box').removeClass('on');
        $('.addressesAreEquals').show();
        if ($('.addressesAreEquals').find('input:checked').length)
            $('#address_invoice_form').hide();
        else
            $('#address_invoice_form').show();
        $('.address_add a').attr('href', addressUrl);

        // Disable multi address shipping
        $.ajax({
            type: 'POST',
            headers: { "cache-control": "no-cache" },
            url: orderOpcUrl + '?rand=' + new Date().getTime(),
            async: true,
            cache: false,
            data: 'ajax=true&method=noMultiAddressDelivery'
        });

        // Reload the cart
        $.ajax({
            type: 'POST',
            headers: { "cache-control": "no-cache" },
            url: orderOpcUrl + '?rand=' + new Date().getTime(),
            async: true,
            cache: false,
            data: 'ajax=true&method=cartReload',
            dataType : 'html',
            success: function(data) {
                $('#cart_summary').replaceWith($(data).find('#cart_summary'));
            }
        });
    }
    if (typeof bindUniform !=='undefined')
        bindUniform();
}

function updateAddressDisplay() {
    if ($('#id_delivery').find(':selected').data('is_pickup')) {
        $('.address_block').hide();
        $('#opc_id_address_delivery, #opc_id_address_invoice').val(0);
    } else {
        $('.address_block').show();
        if (informations)
            $('#opc_id_address_delivery, #opc_id_address_invoice').val(informations.id_address_delivery);
    }
}

function updateAddressField() {
    var street = $('#street').val(),
        house = $('#house').val(),
        housing = $('#housing').val(),
        apartment = $('#apartment').val(),
        address = '';

    if (street)
        address = 'ул.' + street;
    if (house)
        address += ', д.' + house;
    if (housing)
        address += ', к.' + housing;
    if (apartment)
        address += ', кв.' + apartment;

    $('#address1').val(address);
}

// Initially fill address inputs
function splitInitialAddress() {
    var address_split = $('#address1').val().split(', ');
    address_split.forEach(function(entry) {
        var split = entry.split('.');
        if (split[0] == 'ул')
            $('#street').val(split[1]);
        else if (split[0] == 'д')
            $('#house').val(split[1]);
        else if (split[0] == 'к')
            $('#housing').val(split[1]);
        else if (split[0] == 'кв')
            $('#apartment').val(split[1]);
    });
}

function requestDeliveryCost() {
    if ($('#opc_id_customer').val() !== '0') {
        return false;
    }

    if ($('#postcode').length == 0) {
        return false;
    }

    var params = 'method=requestDeliveryCost';
    var postcode = $('#postcode').val();

    var delivery_option = $('select[name=id_delivery]').find(':selected').val();

    // If russian post
    if (delivery_option == '22,') {
        $.ajax({
            type: 'POST',
            headers: {"cache-control": "no-cache"},
            url: orderOpcUrl + '?rand=' + new Date().getTime(),
            async: false,
            cache: false,
            dataType: "json",
            data: 'ajax=true&' + params + '&token=' + static_token + '&postcode=' + postcode + '&delivery_option=' + delivery_option,
            success: function (jsonData) {
                console.log(jsonData);

                if (jsonData.error) {
                    $('.order-note-delivery').hide();

                    return false;
                } else {
                    var shipping_price = formatCurrency(jsonData.delivery_cost, currencyFormat, currencySign, currencyBlank);
                    var total_price = formatCurrency(jsonData.order_total_with_shipping, currencyFormat, currencySign, currencyBlank);

                    console.log(shipping_price, total_price);

                    window.total_shipping_cost = jsonData.delivery_cost;
                    window.total_price = jsonData.order_total_with_shipping;

                    $('#total_shipping_cost').html(shipping_price);

                    showTotalShippingNotes(jsonData.delivery_cost);

                    if (jsonData.delivery_cost > 0) {
                        $('.cart_total_shipping').show();
                    } else {
                        $('.cart_total_shipping').hide();
                    }

                    updateCartSummaryTotalPrice();
                }
            }
            // error: function(XMLHttpRequest, textStatus, errorThrown) {
            //     $('#submitAccount').prop('disabled', false);
            //     if (textStatus !== 'abort')
            //     {
            //         error = "txtErrorsHappened";
            //         if (!!$.prototype.fancybox)
            //             $.fancybox.open([
            //                 {
            //                     type: 'inline',
            //                     autoScale: true,
            //                     minHeight: 30,
            //                     content: '<p class="fancybox-error">' + error + '</p>'
            //                 }
            //             ], {
            //                 padding: 0
            //             });
            //         else
            //             alert(error);
            //     }
            // }
        });
    } else {
        $('#total_shipping_cost').html(0);
        $('.order-note-delivery-cost').html(0);
        $('.order-note-delivery').hide();
        $('.cart_total_shipping').hide();

        updateCartSummaryTotalPrice();
    }
}

function showTotalShippingNotes(delivery_cost) {
    if (delivery_cost > 0) {
        $('.order-note-delivery-cost').html(formatCurrency(delivery_cost, currencyFormat, currencySign, currencyBlank));
        $('.order-note-delivery').show();
        $('.cart_total_shipping').show();
    } else {
        $('.order-note-delivery-cost').html(0);
        $('.order-note-delivery').hide();
        $('.cart_total_shipping').hide();
    }
}
