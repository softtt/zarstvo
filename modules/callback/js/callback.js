$(document).ready(function() {
    $("#id_callback_form input[name=phone]").mask('+7 (999) 999-99-99');

    if (!!$.prototype.fancybox)
        $('#callback-link').fancybox({
            'autoSize' : false,
            'height' : 'auto',
            'width' : '285',
            'hideOnContentClick': false,
            'padding': 25,
        });

    $('body').on('submit', '#id_callback_form', function(e) {
        e.preventDefault();
        var errors = false;
        var submit_button = $('#id_callback_form button#submitCallback');

        console.log('Submit callback form');
        submit_button.prop('disabled', true);

        $('#id_callback_form .required').each(function (i, el) {
            if (validateField($(el)) === false) {
                errors = true;
            }
        });

        console.log(errors);
        if (errors) {
            showNotification('error_validation');
            submit_button.prop('disabled', false);
            return;
        } else {
            var data = {};
            var form_data = $('#id_callback_form').serializeArray();

            showNotification('none');

            $.each(form_data, function() {
                if (data[this.name] !== undefined) {
                    if (!data[this.name].push)
                        data[this.name] = [data[this.name]];
                    data[this.name].push(this.value || '');
                }
                else
                    data[this.name] = this.value || '';
            });
            data['action'] = 'place_callback_order';

            $.ajax({
                url: callback_controller_url + '?rand=' + new Date().getTime(),
                data: data,
                type: 'POST',
                headers: { "cache-control": "no-cache" },
                dataType : "json",
                success: function(jsonData) {
                    if (jsonData.errors === true)
                    {
                        showNotification(jsonData.error_type);
                        submit_button.prop('disabled', false);
                    }
                    else if (jsonData.errors === false && jsonData.send === true )
                    {
                        $('#callback_form #callback_form_data').hide();
                        $('#callback_form #callback_form_success').show();
                    }
                    console.log('Callback ajax success', jsonData);
                },
                error: function(XMLHttpRequest, textStatus, errorThrown) {
                    showNotification('error_callback');
                    submit_button.prop('disabled', false);
                }
            });
        }

    });

    $('#id_callback_form input').on('change, keyup', function () {
        validateField($(this));
    });
});

function validateField(field) {
    var result = true;

    if (field.hasClass('required')) {
        if (field.val() === '') {
            field.removeClass('ok').addClass('error');
            result = false;
        }
        else
            field.removeClass('error').addClass('ok');
    } else {
        if (field.val() === '')
            field.removeClass('ok');
        else
            field.addClass('ok');
    }

    if (field.attr('data-validate')) {
        result = window['validate_'+field.attr('data-validate')](field.val());
        if (!result) {
            field.removeClass('ok').addClass('error');
            result = false;
        }
    }

    return result;
}

function showNotification(type) {
    var type = type || 'none';

    var notifications = {
        'error_validation' : $('#id_callback_form .error.validation'),
        'error_callback' : $('#id_callback_form .error.callback'),
    };

    $.each(notifications, function() {
        $(this).hide();
    });

    if (type === 'none')
        return;

    if (type in notifications)
        notifications[type].show();
}
