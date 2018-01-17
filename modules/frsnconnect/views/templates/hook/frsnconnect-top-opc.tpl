{*
* Social Network connect modules
* frsnconnect 0.15 by froZZen
*}
{if isset($html)}
    {literal}
    <script type="text/javascript">
        var authpopup;    
        $('document').ready( function() {
        var form = $('#login_form'); 
        if (!form)
            return;
                
        var headId = document.getElementsByTagName('head')[0];
        var cssNode = document.createElement('link');
        cssNode.type = 'text/css';
        cssNode.rel = 'stylesheet';
        cssNode.media = 'all';
        {/literal}
        cssNode.href = '{$auth_css_path}';
        {literal}
        headId.appendChild(cssNode);
    
        var newForm = document.createElement('form');
        newForm.setAttribute('action', 'javascript:;');
        newForm.setAttribute('method', 'post');
        newForm.setAttribute('id', 'frsnconnect_form');
        newForm.setAttribute('class', 'std clearfix');
        {/literal}
        newForm.innerHTML = '{$html}';
        {literal}
        $(newForm).insertAfter(form);

        $('form#frsnconnect_form input[id^="SubmitCreate_"]').click(function() {
            var sn = $(this).attr('name');
            auth(sn);    
        });
    });

function auth(sn) {
    $('#opc_sn_login_errors').html('').slideUp('fast');
    if (authpopup !== undefined)
        authpopup.close();
    {/literal}
        var url = '{$link->getModuleLink("frsnconnect", "actions")}';
    {literal}
    var urlparam = 'process=accAuth&js=true&' + sn + '=true&token=' + static_token;
    url += (url.indexOf('?') >= 0 ? '&' : '?') + urlparam;
    var popWidth = screen.width/ 1.5;
    var popHeight  = screen.height/ 1.5;   
    var centerWidth = ($(window).width() - popWidth) / 2;
    var centerHeight = ($(window).height() - popHeight) / 2;
				
    authpopup = window.open(url, "Authentification", "width=" + popWidth + ",height=" + popHeight + ",left=" + centerWidth + ",top=" + centerHeight + ",resizable=yes,scrollbars=no,toolbar=no,menubar=no,location=no,directories=no,status=yes");
    authpopup.focus();
	        
    return false;
};
  
function OnCloseAuthPopup(jsonData) {
    if (jsonData.hasError) {
        var errors = '<b>'+txtThereis+' '+jsonData.errors.length+' '+txtErrors+':</b><ol>';
	for(error in jsonData.errors)
            //IE6 bug fix
            if(error != 'indexOf')
                errors += '<li>'+jsonData.errors[error]+'</li>';
        errors += '</ol>';
			
        $('#opc_sn_login_errors').html(errors).slideDown('slow');
    }
    else {
        if (jsonData.isLogin) {
            // update token
            static_token = jsonData.token;
            updateNewAccountToAddressBlock();
        }
        else {
            $("form#frsnconnect_form").slideUp("slow", function() {
                $("form#frsnconnect_form").html('<div id="account-creation_form">' + jsonData.page + '</div>');
            });
                        
            $("form#frsnconnect_form").slideDown("slow");
            $('form#frsnconnect_form').bind("submit", function(e) {
                submitAccount_SN_click();
                return false;
            });
        }    
    }
    
    return false;
}

    function submitAccount_SN_click() {
        $('#opc_new_account-overlay').fadeIn('slow');
	$('#opc_delivery_methods-overlay').fadeIn('slow');
	$('#opc_payment_methods-overlay').fadeIn('slow');
			
	// RESET ERROR(S) MESSAGE(S)
	$('#frsn_createaccount_errors').html('').slideUp('slow');
			
	var params = 'process=create&'; 

	$('form#frsnconnect_form input:visible, form#frsnconnect_form input[type=hidden]').each(function() {
            if ($(this).is('input[type=checkbox]')) {
                if ($(this).is(':checked'))
                    params += encodeURIComponent($(this).attr('name'))+'=1&';
            }
            else if ($(this).is('input[type=radio]')) {
                if ($(this).is(':checked'))
                    params += encodeURIComponent($(this).attr('name'))+'='+encodeURIComponent($(this).val())+'&';
            }
            else
                params += encodeURIComponent($(this).attr('name'))+'='+encodeURIComponent($(this).val())+'&';
        });
	$('form#frsnconnect_form select:visible').each(function() {
            params += encodeURIComponent($(this).attr('name'))+'='+encodeURIComponent($(this).val())+'&';
        });
        // Clean the last &
        params = params.substr(0, params.length-1);

        $.ajax({
            type: 'POST',
        {/literal}
            url: '{$link->getModuleLink("frsnconnect", "actions")}',
        {literal}
            async: false,
            cache: false,
            dataType : "json",
            data: 'ajax=true&'+params+'&token=' + static_token ,
            success: function(jsonData) {
                if (jsonData.hasError) {
                    var tmp = '';
                    var i = 0;
                    for(error in jsonData.errors)
                        //IE6 bug fix
                        if(error != 'indexOf') {
                            i = i+1;
                            tmp += '<li>'+jsonData.errors[error]+'</li>';
                        }
                    tmp += '</ol>';
                    var errors = '<b>'+txtThereis+' '+i+' '+txtErrors+':</b><ol>'+tmp;
                    $('#frsn_createaccount_errors').html(errors).slideDown('slow');
                    $.scrollTo('#frsn_createaccount_errors', 800);
		}
                else {
                    // update token
                    static_token = jsonData.token;
                    updateNewAccountToAddressBlock();
                }
		$('#opc_new_account-overlay').fadeOut('slow');
		$('#opc_delivery_methods-overlay').fadeOut('slow');
		$('#opc_payment_methods-overlay').fadeOut('slow');
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                if (textStatus != 'abort')
                    alert("TECHNICAL ERROR: unable to save account \n\nDetails:\nError thrown: " + XMLHttpRequest + "\n" + 'Text status: ' + textStatus);
		$('#opc_new_account-overlay').fadeOut('slow');
		$('#opc_delivery_methods-overlay').fadeOut('slow');
		$('#opc_payment_methods-overlay').fadeOut('slow')
            }
	});
    }
    </script>
    {/literal}
{else}
    {if $not_services}    
        <input type="hidden" value="{$back}" name="back"/>                            
        {include file="$tpl_path"} 
        <div style="display: none;" class="error" id="opc_sn_login_errors">
        </div>
    {/if} 
{/if}
